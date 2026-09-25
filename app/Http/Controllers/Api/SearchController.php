<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    /**
     * @var Product
     */
    protected $productModel;

    /**
     * @var ProductVariant
     */
    protected $variantModel;

    /**
     * @var ProductVariantImage
     */
    protected $variantImageModel;

    /**
     * SearchController constructor.
     */
    public function __construct()
    {
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
        $this->variantImageModel = new ProductVariantImage();
    }

    /**
     * Search products.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = $request->get('q');

        // If it's a search request with query
        if (!empty($query)) {
            return $this->searchProducts($request);
        }

        // Return empty search state
        return response()->json([
            'success' => true,
            'data' => [
                'products' => [],
                'query' => null,
                'total' => 0,
                'suggestions' => $this->getSearchSuggestions(),
            ],
        ]);
    }

    /**
     * Search products with query.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function searchProducts(Request $request)
    {
        $query = $request->get('q');
        $limit = $request->get('limit', 20);
        $page = $request->get('page', 1);
        $category = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => [],
                    'total' => 0,
                    'query' => $query,
                ],
            ]);
        }

        // Search products
        $productsQuery = $this->productModel
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('short_description', 'LIKE', "%{$query}%")
                    ->orWhere('tags', 'LIKE', "%{$query}%");
            });

        // Apply category filter
        if ($category) {
            $productsQuery->where('category_id', $category);
        }

        // Apply price filters
        if ($minPrice !== null) {
            $productsQuery->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $productsQuery->where('price', '<=', $maxPrice);
        }

        $total = $productsQuery->count();
        $products = $productsQuery->orderBy('name', 'ASC')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $results = [];

        foreach ($products as $product) {
            $variants = $this->variantModel
                ->where('product_id', $product->id)
                ->where('status', 1)
                ->get();

            if ($variants->isNotEmpty()) {
                foreach ($variants as $variant) {
                    $variantImage = $this->variantImageModel
                        ->where('variant_id', $variant->id)
                        ->orderBy('is_primary', 'DESC')
                        ->orderBy('sort_order', 'ASC')
                        ->first();

                    // Get variant details with color hex
                    $variantDetails = $this->getVariantDetails($variant->id);

                    // Build display name with color name instead of hex for readability
                    $displayName = $this->formatVariantDisplay($variantDetails);

                    $results[] = [
                        'id' => $variant->id,
                        'product_id' => $product->id,
                        'name' => !empty($displayName)
                            ? $product->name . ' (' . $displayName . ')'
                            : $product->name,
                        'slug' => $variant->slug,
                        'image' => $variantImage->image ?? $variant->image ?? $product->image ?? 'assets/images/default-product.jpg',
                        'price' => $variant->sale_price > 0 ? $variant->sale_price : $variant->price,
                        'original_price' => $variant->price,
                        'sale_price' => $variant->sale_price,
                        'category' => $product->category_name ?? '',
                        'is_variant' => true,
                        'variant_id' => $variant->id,
                        'variant_name' => $displayName,
                        'variant_color_hex' => $variantDetails['color_hex'],
                        'variant_features' => $variantDetails['features'],
                        'stock' => $variant->stock ?? 0,
                        'sku' => $variant->sku ?? '',
                        'in_stock' => ($variant->stock ?? 0) > 0,
                    ];
                }
            } else {
                $results[] = [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->image ?? 'assets/images/default-product.jpg',
                    'price' => $product->sale_price > 0 ? $product->sale_price : $product->price,
                    'original_price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'category' => $product->category_name ?? '',
                    'is_variant' => false,
                    'variant_id' => 0,
                    'variant_name' => '',
                    'variant_color_hex' => null,
                    'variant_features' => [],
                    'stock' => $product->stock ?? 0,
                    'sku' => $product->sku ?? '',
                    'in_stock' => ($product->stock ?? 0) > 0,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $results,
                'total' => $total,
                'query' => $query,
                'page' => $page,
                'per_page' => $limit,
                'total_pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Format variant display with color name instead of hex.
     *
     * @param array $variantDetails
     * @return string
     */
    private function formatVariantDisplay(array $variantDetails): string
    {
        $displayParts = [];

        foreach ($variantDetails['features'] as $feature) {
            $value = trim($feature['value'] ?? '');

            // Skip HEX color values from text display
            if (preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                continue;
            }

            if ($value !== '') {
                $displayParts[] = $value;
            }
        }

        return implode(' / ', $displayParts);
    }

    /**
     * Get variant details with features and color hex.
     *
     * @param int $variantId
     * @return array
     */
    private function getVariantDetails(int $variantId): array
    {
        $values = \DB::table('product_variant_values')
            ->select('product_variant_values.value', 'features.name as feature_name')
            ->join('features', 'features.id', '=', 'product_variant_values.feature_id')
            ->where('product_variant_values.variant_id', $variantId)
            ->get();

        $displayNames = [];
        $colorHex = null;
        $features = [];

        foreach ($values as $val) {
            $featureName = trim($val->feature_name ?? '');
            $value = trim($val->value ?? '');

            $features[] = [
                'feature' => $featureName,
                'value' => $value,
            ];

            // If value is a HEX color
            if (preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                $colorHex = $value;
                if (!empty($featureName)) {
                    $displayNames[] = $featureName;
                }
            } else {
                $displayNames[] = $value;
            }
        }

        return [
            'display_name' => implode(' / ', $displayNames),
            'color_hex' => $colorHex,
            'features' => $features,
        ];
    }

    /**
     * Get search suggestions (popular searches).
     *
     * @return array
     */
    private function getSearchSuggestions(): array
    {
        // This is a placeholder - implement with popular searches
        return [
            'Electronics',
            'Fashion',
            'Furniture',
            'Beauty',
            'Sports',
            'Appliances',
        ];
    }

    /**
     * Get autocomplete suggestions (AJAX).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocomplete(Request $request)
    {
        $query = $request->get('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $products = $this->productModel
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('tags', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'slug', 'image', 'price', 'sale_price']);

        $results = [];

        foreach ($products as $product) {
            $results[] = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image ?? 'assets/images/default-product.jpg',
                'price' => $product->sale_price > 0 ? $product->sale_price : $product->price,
                'formatted_price' => '$' . number_format($product->sale_price > 0 ? $product->sale_price : $product->price, 2),
                'url' => '/product/' . $product->slug,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get search filters (categories, price range, etc.).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilters(Request $request)
    {
        $query = $request->get('q');

        $categories = [];
        $priceRange = [
            'min' => 0,
            'max' => 10000,
        ];

        if (!empty($query)) {
            // Get categories with counts
            $categories = \DB::table('products as p')
                ->join('product_categories as pc', 'pc.id', '=', 'p.category_id')
                ->where('p.status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('p.name', 'LIKE', "%{$query}%")
                        ->orWhere('p.description', 'LIKE', "%{$query}%")
                        ->orWhere('p.tags', 'LIKE', "%{$query}%");
                })
                ->select('pc.id', 'pc.name', \DB::raw('COUNT(*) as count'))
                ->groupBy('pc.id', 'pc.name')
                ->get();

            // Get price range
            $range = $this->productModel
                ->where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%")
                        ->orWhere('tags', 'LIKE', "%{$query}%");
                })
                ->select(\DB::raw('MIN(price) as min_price, MAX(price) as max_price'))
                ->first();

            if ($range) {
                $priceRange = [
                    'min' => $range->min_price ?? 0,
                    'max' => $range->max_price ?? 10000,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'price_range' => $priceRange,
            ],
        ]);
    }

    /**
     * Get popular searches.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function popularSearches()
    {
        // This is a placeholder - implement with actual popular searches
        $popular = [
            ['term' => 'Electronics', 'count' => 150],
            ['term' => 'Fashion', 'count' => 120],
            ['term' => 'Furniture', 'count' => 100],
            ['term' => 'Beauty', 'count' => 85],
            ['term' => 'Sports', 'count' => 70],
        ];

        return response()->json([
            'success' => true,
            'data' => $popular,
        ]);
    }

    /**
     * Search with advanced filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function advanced(Request $request)
    {
        $query = $request->get('q');
        $category = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort = $request->get('sort', 'relevance');
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $inStock = $request->get('in_stock', false);
        $hasVariants = $request->get('has_variants', false);

        $queryBuilder = $this->productModel->where('status', 'active');

        // Search query
        if (!empty($query) && strlen($query) >= 2) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('short_description', 'LIKE', "%{$query}%")
                    ->orWhere('tags', 'LIKE', "%{$query}%");
            });
        }

        // Category filter
        if ($category) {
            $queryBuilder->where('category_id', $category);
        }

        // Price filters
        if ($minPrice !== null) {
            $queryBuilder->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $queryBuilder->where('price', '<=', $maxPrice);
        }

        // In stock filter
        if ($inStock) {
            $queryBuilder->where('stock', '>', 0);
        }

        // Has variants filter
        if ($hasVariants) {
            $queryBuilder->whereHas('variants', function ($q) {
                $q->where('status', 1);
            });
        }

        // Sorting
        switch ($sort) {
            case 'price_low':
                $queryBuilder->orderBy('price', 'ASC');
                break;
            case 'price_high':
                $queryBuilder->orderBy('price', 'DESC');
                break;
            case 'rating':
                $queryBuilder->orderBy('rating', 'DESC');
                break;
            case 'newest':
                $queryBuilder->orderBy('created_at', 'DESC');
                break;
            case 'relevance':
            default:
                // For relevance, keep default ordering
                $queryBuilder->orderBy('name', 'ASC');
                break;
        }

        $total = $queryBuilder->count();
        $products = $queryBuilder->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Get variant information for each product
        $results = [];
        foreach ($products as $product) {
            $variants = $this->variantModel
                ->where('product_id', $product->id)
                ->where('status', 1)
                ->get();

            $hasVariantsFlag = $variants->isNotEmpty();
            $firstVariant = $variants->first();

            $results[] = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image ?? 'assets/images/default-product.jpg',
                'price' => $product->sale_price > 0 ? $product->sale_price : $product->price,
                'original_price' => $product->price,
                'sale_price' => $product->sale_price,
                'rating' => $product->rating,
                'stock' => $product->stock,
                'in_stock' => $product->stock > 0,
                'has_variants' => $hasVariantsFlag,
                'variant_count' => $variants->count(),
                'first_variant_price' => $firstVariant ? ($firstVariant->sale_price > 0 ? $firstVariant->sale_price : $firstVariant->price) : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $results,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
                'query' => $query,
            ],
        ]);
    }
}