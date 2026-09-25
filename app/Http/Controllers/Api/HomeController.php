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

        // Cache for 10 minutes
        Cache::put($cacheKey, $data, 600);

        $loadTime = round((microtime(true) - $startTime) * 1000, 2);
        Log::info('Home page loaded in ' . $loadTime . 'ms with cache miss');

        return response()->json([
            'success' => true,
            'data' => $data,
            'cached' => false,
            'load_time_ms' => $loadTime,
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
        $categories = $categoryModel->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->toArray();

        if (empty($categories)) {
            $categories = $this->getDefaultCategories();
        }

        Cache::put($cacheKey, $categories, 3600);
        return $categories;
    }

    /**
     * Get home products.
     *
     * @return array
     */
    private function getHomeProducts(): array
    {
        $cacheKey = 'home_products_data';
        $products = Cache::get($cacheKey);

        if ($products !== null) {
            return $products;
        }

        $productModel = new Product();
        $products = $productModel->getHomeListingProducts(
            ['is_home' => 1],
            1000,
            0,
            'p.created_at DESC'
        );

        foreach ($products as &$product) {
            $product['has_variants'] = isset($product['has_variants']) && $product['has_variants'] == 1;
            $product['product_id'] = $product['id'];
            $product['variant_id'] = $product['variant_id'] ?? 0;
        }

        Cache::put($cacheKey, $products, 300);
        return $products;
    }

    /**
     * Get featured products.
     *
     * @return array
     */
    private function getFeaturedProducts(): array
    {
        $cacheKey = 'featured_products_data';
        $products = Cache::get($cacheKey);

        if ($products !== null) {
            return $products;
        }

        $productModel = new Product();
        $products = $productModel->getHomeListingProducts(
            ['is_featured' => 1],
            1000,
            0,
            'p.created_at DESC'
        );

        foreach ($products as &$product) {
            $product['product_id'] = $product['id'];
            $product['variant_id'] = $product['variant_id'] ?? 0;
        }

        Cache::put($cacheKey, $products, 300);
        return $products;
    }

    /**
     * Get random products.
     *
     * @return array
     */
    private function getRandomProducts(): array
    {
        $cacheKey = 'random_products_data';
        $products = Cache::get($cacheKey);

        if ($products !== null) {
            return $products;
        }

        $productModel = new Product();

        $total = $productModel->where('status', 'active')->count();
        $offset = max(0, rand(0, max(0, $total - 8)));

        $products = $productModel->getHomeListingProducts([], 8, $offset, 'p.id ASC');

        if (count($products) < 8) {
            $products = $productModel->getHomeListingProducts([], 8, 0, 'p.id ASC');
        }

        foreach ($products as &$product) {
            $product['product_id'] = $product['id'];
            $product['variant_id'] = $product['variant_id'] ?? 0;
        }

        Cache::put($cacheKey, $products, 300);
        return $products;
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
            'home_products_data',
            'featured_products_data',
            'random_products_data',
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