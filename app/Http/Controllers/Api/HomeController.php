<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\FAQ;
use App\Models\Blog;
use App\Models\HomeBanner;
use App\Models\ProductCategory;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * HomeController constructor.
     */
    public function __construct()
    {
        // Share common data from base controller
        parent::__construct();
    }

    /**
     * Get homepage data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $lang = $request->header('Accept-Language', 'en');
        $cacheKey = 'home_page_data_' . $lang;

        // Try to get from cache
        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            return response()->json([
                'success' => true,
                'data' => $cachedData,
                'cached' => true,
            ]);
        }

        $startTime = microtime(true);

        // Build the data
        $data = [
            'banner' => $this->getBannerData(),
            'categories' => $this->getCategoryData(),
            'home_products' => $this->getHomeProducts(),
            'featured_products' => $this->getFeaturedProducts(),
            'random_products' => $this->getRandomProducts(),
        ];

        // ✅ Only cache if we actually have products to show
        $hasProducts = !empty($data['home_products'])
            || !empty($data['featured_products'])
            || !empty($data['random_products']);

        if ($hasProducts) {
            Cache::put($cacheKey, $data, 600);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'cached' => false,
            'load_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);
    }

    /**
     * Get banner data.
     *
     * @return array
     */
    private function getBannerData(): array
    {
        $cacheKey = 'home_banner_data';
        $banner = Cache::get($cacheKey);

        if ($banner !== null) {
            return $banner;
        }

        $bannerModel = new HomeBanner();
        $banner = $bannerModel->where('is_active', true)->first();

        if (!$banner) {
            $banner = [
                'badge_text' => 'Premium Collection',
                'title_line1' => 'Shop with',
                'title_line2' => 'Style & Elegance',
                'subtitle' => 'Discover our curated collection of premium products',
                'button_text' => 'Explore All Products',
                'button_link' => '#products',
                'button_icon' => 'bi-arrow-right',
            ];
        } else {
            $banner = $banner->toArray();   // ← ADD THIS
        }

        Cache::put($cacheKey, $banner, 3600);
        return $banner;
    }

    /**
     * Get category data.
     *
     * @return array
     */
    private function getCategoryData(): array
    {
        $cacheKey = 'home_categories_data';
        $categories = Cache::get($cacheKey);

        if ($categories !== null) {
            return $categories;
        }

        $categoryModel = new ProductCategory();
        $rows = $categoryModel->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get();

        if ($rows->isEmpty()) {
            $categories = $this->getDefaultCategories();
        } else {
            $categories = $rows->map(function ($cat) {
                // Build a slug from the DB column, or derive from name
                $slug = $cat->slug ?? \Illuminate\Support\Str::slug($cat->name);

                return [
                    'id'         => $cat->id,
                    'parent_id'  => $cat->parent_id,
                    'name'       => $cat->name,
                    'slug'       => $slug,
                    'icon'       => $cat->icon ?? 'bi-box',
                    'item_count' => $cat->item_count ?? 0,
                    // Keep `link` for backwards compatibility — now built from slug
                    'link'       => '/category/' . $slug,
                ];
            })->toArray();
        }

        Cache::put($cacheKey, $categories, 3600);
        return $categories;
    }

    /* ==============================================================
 |  HOME PRODUCTS (hero slider) — variant-aware
 ============================================================== */
    private function getHomeProducts(): array
    {
        $cacheKey = 'home_products_data_v2';
        $cached = Cache::get($cacheKey);
        if (!empty($cached)) return $cached;

        $rows = Product::where('status', 'active')
            ->where('is_home', 1)
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get()
            ->toArray();

        $products = [];
        foreach ($rows as $row) {
            $normalized = $this->normalizeProductWithVariant($row);
            if ($normalized !== null) {
                $products[] = $normalized;
            }
        }

        if (!empty($products)) {
            Cache::put($cacheKey, $products, 300);
        }

        return $products;
    }

    /* ==============================================================
 |  FEATURED PRODUCTS — variant-aware
 ============================================================== */
    private function getFeaturedProducts(): array
    {
        $cacheKey = 'featured_products_data_v2';
        $cached = Cache::get($cacheKey);
        if (!empty($cached)) return $cached;

        $rows = Product::where('status', 'active')
            ->where('is_featured', 1)
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get()
            ->toArray();

        $products = [];
        foreach ($rows as $row) {
            $normalized = $this->normalizeProductWithVariant($row);
            if ($normalized !== null) {
                $products[] = $normalized;
            }
        }

        if (!empty($products)) {
            Cache::put($cacheKey, $products, 300);
        }

        return $products;
    }

    /* ==============================================================
 |  RANDOM PRODUCTS — variant-aware
 ============================================================== */
    private function getRandomProducts(): array
    {
        $cacheKey = 'random_products_data_v2';
        $cached = Cache::get($cacheKey);
        if (!empty($cached)) return $cached;

        // Fetch more than we need so we can drop out-of-stock variant products
        $rows = Product::where('status', 'active')
            ->inRandomOrder()
            ->limit(24)
            ->get()
            ->toArray();

        $products = [];
        foreach ($rows as $row) {
            $normalized = $this->normalizeProductWithVariant($row);
            if ($normalized !== null) {
                $products[] = $normalized;
                if (count($products) >= 8) break;   // stop at 8
            }
        }

        if (!empty($products)) {
            Cache::put($cacheKey, $products, 300);
        }

        return $products;
    }

    /* ==============================================================
 |  NORMALIZER — picks first in-stock variant when product has them
 |
 |  Returns null when:
 |    - product has variants AND all are out of stock  → hide
 |    - product has no variants AND product stock <= 0  → hide
 |
 |  Output includes:
 |    product_id, variant_id, has_variants, image_url,
 |    price, sale_price, discount, rating, stock,
 |    sku, slug, name, description, short_description,
 |    variant_name
 ============================================================== */
    private function normalizeProductWithVariant(array $product): ?array
    {
        $productId = (int) ($product['id'] ?? 0);
        if ($productId <= 0) return null;

        // 1. Load variants (status=1 only) with their primary image
        $variants = \Illuminate\Support\Facades\DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->toArray();

        // 2. No variants → plain product flow (unchanged behaviour)
        if (empty($variants)) {
            if ((int) ($product['stock'] ?? 0) <= 0) {
                return null;   // out of stock → hide
            }
            return $this->finalizeProductRow($product, null);
        }

        // 3. Has variants → find first with stock > 0
        $selectedVariant = null;
        foreach ($variants as $v) {
            if ((int) ($v->stock ?? 0) > 0) {
                $selectedVariant = $v;
                break;
            }
        }

        // 4. All variants out of stock → hide product
        if ($selectedVariant === null) {
            return null;
        }

        // 5. Attach variant's primary image (if any) and overlay variant data
        $variantImage = \Illuminate\Support\Facades\DB::table('product_variant_images')
            ->where('variant_id', $selectedVariant->id)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->value('image');

        // Overlay variant-specific values onto a copy of the product
        $merged = $product;   // keep name, description, slug, category, etc.
        $merged['variant_id']      = (int) $selectedVariant->id;
        $merged['variant_name']    = $selectedVariant->variant_name ?? null;
        $merged['price']           = $selectedVariant->price        ?? $product['price'];
        $merged['sale_price']      = $selectedVariant->sale_price   ?? $product['sale_price'];
        $merged['discount']        = $selectedVariant->discount     ?? $product['discount'];
        $merged['rating']          = $selectedVariant->rating       ?? $product['rating'];
        $merged['stock']           = (int) ($selectedVariant->stock ?? 0);
        $merged['sku']             = $selectedVariant->sku          ?? $product['sku'];

        // Prefer variant image; fall back to product image
        if (!empty($variantImage)) {
            $merged['image'] = $variantImage;
        }

        return $this->finalizeProductRow($merged, $selectedVariant);
    }

    /**
     * Prepare the final row for JSON output.
     */
    private function finalizeProductRow(array $row, $variant = null): array
    {
        $out = $row;
        $out['product_id']   = (int) $row['id'];
        $out['variant_id']   = $variant ? (int) $variant->id : (int) ($row['variant_id'] ?? 0);
        $out['has_variants'] = $variant !== null;
        $out['stock']        = (int) ($row['stock'] ?? 0);

        // Resolve image URL
        if (!empty($row['image'])) {
            $out['image_url'] = $this->resolveImageUrl($row['image']);
        } else {
            $out['image_url'] = null;
        }

        return $out;
    }

    /* ==============================================================
 |  SHARED PRODUCT NORMALIZER
 |
 |  Adds:
 |   - product_id      (alias for id)
 |   - variant_id      (default 0)
 |   - has_variants    (bool)
 |   - image_url       (absolute path the frontend uses directly)
 ============================================================== */
    private function normalizeProduct(array $product): array
    {
        $product['product_id'] = $product['id'];
        $product['variant_id'] = $product['variant_id'] ?? 0;
        $product['has_variants'] = !empty($product['has_variants']);

        // ✅ Resolve image path to a real URL the frontend can load
        if (!empty($product['image'])) {
            $product['image_url'] = $this->resolveImageUrl($product['image']);
        } else {
            $product['image_url'] = null;
        }

        return $product;
    }

    /**
     * Convert a stored image path into a public URL.
     *
     *   "products/xyz.jpg"        → "/storage/products/xyz.jpg"
     *   "uploads/products/x.jpg"  → "/storage/uploads/products/x.jpg"
     *   "/storage/products/x.jpg" → unchanged
     *   "http://..."              → unchanged
     */
    private function resolveImageUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return $path;                       // already absolute
        }
        if (str_starts_with($path, 'storage/')) {
            return '/' . $path;
        }
        return '/storage/' . ltrim($path, '/'); // default: assume storage/app/public/
    }

    /**
     * Get default categories.
     *
     * @return array
     */
    private function getDefaultCategories(): array
    {
        return [
            ['id' => 1, 'name' => 'Electronics', 'icon' => 'bi-laptop', 'item_count' => 245, 'link' => '/category/electronics'],
            ['id' => 2, 'name' => 'Fashion', 'icon' => 'bi-bag', 'item_count' => 189, 'link' => '/category/fashion'],
            ['id' => 3, 'name' => 'Furniture', 'icon' => 'bi-house', 'item_count' => 134, 'link' => '/category/furniture'],
            ['id' => 4, 'name' => 'Beauty', 'icon' => 'bi-flower1', 'item_count' => 156, 'link' => '/category/beauty'],
            ['id' => 5, 'name' => 'Sports', 'icon' => 'bi-bicycle', 'item_count' => 87, 'link' => '/category/sports'],
            ['id' => 6, 'name' => 'Appliances', 'icon' => 'bi-microwave', 'item_count' => 98, 'link' => '/category/appliances'],
        ];
    }

    /**
     * Get blog posts.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function blog(Request $request)
    {
        $category = $request->get('category');
        $search = $request->get('search');
        $page = (int) ($request->get('page') ?? 1);
        $perPage = 9;

        $blogModel = new Blog();
        $query = $blogModel->where('status', 'published');

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%")
                    ->orWhere('excerpt', 'LIKE', "%{$search}%");
            });
        }

        $totalPosts = $query->count();
        $posts = $query->orderBy('created_at', 'DESC')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $categories = $blogModel->getDistinctCategories();
        $recentPosts = $blogModel->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $posts,
                'categories' => $categories,
                'recent_posts' => $recentPosts,
                'total_posts' => $totalPosts,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($totalPosts / $perPage),
                'category_filter' => $category,
                'search_query' => $search,
            ],
        ]);
    }

    /**
     * Get blog detail.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function blogDetail($id)
    {
        $blogModel = new Blog();
        $blog = $blogModel->find($id);

        if (!$blog || $blog->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Blog post not found',
            ], 404);
        }

        // Increment view count
        $blog->increment('views');

        // Get related posts
        $relatedPosts = $blogModel->where('category', $blog->category)
            ->where('id', '!=', $id)
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->take(3)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'blog' => $blog,
                'related_posts' => $relatedPosts,
            ],
        ]);
    }

    /**
     * Get FAQs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function faq()
    {
        $faqModel = new FAQ();

        $faqs = $faqModel->where('status', 'active')
            ->orderBy('order', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->get();

        $categories = $faqModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'data' => [
                'faqs' => $faqs,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Get contact page data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function contact()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'title' => 'Contact Us',
                'company_info' => [
                    'address' => '123 Main Street, New York, NY 10001',
                    'phone' => '+1 (555) 123-4567',
                    'email' => 'info@bssshop.com',
                    'working_hours' => 'Mon-Fri: 9:00 AM - 6:00 PM',
                ],
            ],
        ]);
    }

    /**
     * Send contact message.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:100',
            'subject' => 'required|string|min:3|max:200',
            'message' => 'required|string|min:10',
            'agree' => 'required|accepted',
        ], [
            'agree.accepted' => 'You must agree to the terms and conditions.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contactModel = new ContactMessage();

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'unread',
        ];

        $contactModel->create($data);

        Log::info('Contact message received from: ' . $request->email);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully! We\'ll get back to you soon.',
        ]);
    }

    /**
     * Clear home cache.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        // Clear cache keys
        $cacheKeys = [
            'home_page_data_',
            'home_banner_data',
            'home_categories_data',
            'home_products_data',     // ← add _v2
            'home_products_data_v2',  // ✅
            'featured_products_data',
            'featured_products_data_v2', // ✅
            'random_products_data',
            'random_products_data_v2',   // ✅
            'header_menus_',
            'footer_menus_',
        ];

        foreach ($cacheKeys as $key) {
            // Clear all language variants for prefix keys
            if (str_ends_with($key, '_')) {
                $languages = ['en', 'fr', 'es', 'de']; // Add supported languages
                foreach ($languages as $lang) {
                    Cache::forget($key . $lang);
                }
            } else {
                Cache::forget($key);
            }
        }

        Log::info('Home cache cleared manually.');

        return response()->json([
            'success' => true,
            'message' => 'Home cache cleared successfully.',
        ]);
    }

    /**
     * Get homepage stats (for admin/dashboard).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $productCount = Product::where('status', 'active')->count();
        $blogCount = Blog::where('status', 'published')->count();
        $faqCount = FAQ::where('status', 'active')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'active_products' => $productCount,
                'published_posts' => $blogCount,
                'active_faqs' => $faqCount,
                'categories' => ProductCategory::where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * Search products (AJAX autocomplete).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $limit = $request->get('limit', 10);

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $products = Product::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('short_description', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%")
                    ->orWhere('tags', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'image', 'price', 'sale_price']);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get product quick view.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickView($id)
    {
        $product = Product::with(['category', 'images'])
            ->where('status', 'active')
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Check if product has variants
        $hasVariants = $product->variants()->where('status', 1)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'has_variants' => $hasVariants,
            ],
        ]);
    }
}
