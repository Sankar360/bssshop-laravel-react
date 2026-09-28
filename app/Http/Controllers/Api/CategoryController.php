<?php
// app/Http/Controllers/Api/CategoryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Feature;
use App\Models\FeatureCategoryMapping;
use App\Models\FeatureValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    protected $categoryModel;
    protected $productModel;
    protected $featureModel;
    protected $featureCategoryMappingModel;
    protected $featureValueModel;

    public function __construct()
    {
        $this->categoryModel = new ProductCategory();
        $this->productModel = new Product();
        $this->featureModel = new Feature();
        $this->featureCategoryMappingModel = new FeatureCategoryMapping();
        $this->featureValueModel = new FeatureValue();
    }

    /* ============================================================
     |  INDEX — category overview
     ============================================================ */
    public function index(Request $request, $categorySlug = null, $subcategorySlug = null)
    {
        $parentCategories = $this->toArray(
            $this->categoryModel->getParentCategories(true)
        );

        foreach ($parentCategories as &$parent) {
            $parent['children'] = $this->toArray(
                $this->categoryModel->getSubcategories($parent['id'], true)
            );
        }
        unset($parent);

        $activeCategory = null;
        $activeSubcategory = null;

        if ($categorySlug) {
            $category = $this->toArray(
                $this->categoryModel->getCategoryBySlug($categorySlug)
            );

            if ($category) {
                if (!empty($category['parent_id'])) {
                    $activeSubcategory = $category;
                    $activeCategory = $this->toArray(
                        $this->categoryModel->find($category['parent_id'])
                    );
                } else {
                    $activeCategory = $category;
                }
            }
        }

        if ($subcategorySlug && $activeCategory) {
            $subcategory = $this->toArray(
                $this->categoryModel->getCategoryBySlug($subcategorySlug)
            );
            if ($subcategory && (int) $subcategory['parent_id'] === (int) $activeCategory['id']) {
                $activeSubcategory = $subcategory;
            }
        }

        if (!$activeCategory && !empty($parentCategories)) {
            $activeCategory = $parentCategories[0];
        }

        if ($activeCategory && !$activeSubcategory) {
            $subcategories = $this->toArray(
                $this->categoryModel->getSubcategories($activeCategory['id'], true)
            );
            if (!empty($subcategories) && !$subcategorySlug) {
                $activeSubcategory = $subcategories[0];
            }
        }

        $features = [];
        if ($activeCategory) {
            $features = $this->getFeaturesForCategory(
                (int) $activeCategory['id'],
                $activeSubcategory ? (int) $activeSubcategory['id'] : null
            );
        }

        $priceRange = $this->getPriceRange($activeCategory, $activeSubcategory);

        return response()->json([
            'success' => true,
            'data' => [
                'parent_categories'   => $parentCategories,
                'active_category'     => $activeCategory,
                'active_subcategory'  => $activeSubcategory,
                'features'            => $features,
                'price_range' => [
                    'min' => $priceRange['min'] ?? 0,
                    'max' => $priceRange['max'] ?? 10000,
                ],
                'default_min_price'   => $priceRange['min'] ?? 0,
                'default_max_price'   => $priceRange['max'] ?? 10000,
                'title'               => $activeCategory['name'] ?? 'Category Overview',
                'meta_description'    => 'Browse our product categories and find the best deals.',
            ],
        ]);
    }

    /* ============================================================
     |  GET PRODUCTS — legacy AJAX endpoint
     ============================================================ */
    public function getProducts(Request $request)
    {
        $categoryId    = (int) $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id') ? (int) $request->input('subcategory_id') : null;
        $minPrice      = (float) ($request->input('min_price') ?? 0);
        $maxPrice      = (float) ($request->input('max_price') ?? 10000);
        $sort          = $request->input('sort', 'latest');
        $page          = (int) ($request->input('page') ?? 1);
        $perPage       = (int) ($request->input('per_page') ?? 12);

        $features = $request->input('features');
        if (is_string($features)) {
            $features = json_decode($features, true);
        }
        if (!is_array($features)) {
            $features = [];
        }

        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid category',
            ], 404);
        }

        if ($subcategoryId) {
            $subcategory = $this->categoryModel->find($subcategoryId);
            if (!$subcategory || $subcategory['parent_id'] != $categoryId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid subcategory',
                ], 404);
            }
        }

        $filterData = [
            'category_id'    => $categoryId,
            'subcategory_id' => $subcategoryId,
            'min_price'      => $minPrice,
            'max_price'      => $maxPrice,
            'sort'           => $sort,
            'page'           => $page,
            'features'       => $features,
            'per_page'       => $perPage,
        ];

        $result = $this->productModel->getFilteredProducts($filterData);

        return response()->json([
    'success' => true,
    'debug_result' => $result,
]);

        $rows = $result['rows'] ?? [];
        $rows = array_map(fn($row) => $this->normalizeProduct((array) $row), $rows);


        return response()->json([
            'status'       => true,
            'products'     => $result['rows'] ?? [],
            'total'        => $result['total'] ?? 0,
            'per_page'     => $result['per_page'] ?? 12,
            'current_page' => $result['current_page'] ?? 1,
            'last_page'    => $result['last_page'] ?? 1,
            'count_text'   => ($result['total'] ?? 0) . ' ' . (($result['total'] ?? 0) == 1 ? 'Product' : 'Products'),
        ]);
    }

    /* ============================================================
     |  GET PRICE RANGE (AJAX)
     ============================================================ */
    public function getPriceRangeAjax(Request $request)
    {
        $categoryId    = (int) $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id') ? (int) $request->input('subcategory_id') : null;

        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid category',
            ], 404);
        }

        $subcategory = $subcategoryId ? $this->categoryModel->find($subcategoryId) : null;
        $priceRange  = $this->getPriceRange(
            $this->toArray($category),
            $this->toArray($subcategory)
        );

        return response()->json([
            'status' => true,
            'min'    => $priceRange['min'] ?? 0,
            'max'    => $priceRange['max'] ?? 10000,
        ]);
    }

    /* ============================================================
     |  GET FEATURES (AJAX)
     ============================================================ */
    public function getFeaturesAjax(Request $request)
    {
        $categoryId    = (int) $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id') ? (int) $request->input('subcategory_id') : null;

        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid category',
            ], 404);
        }

        $features = $this->getFeaturesForCategory($categoryId, $subcategoryId);

        return response()->json([
            'status'   => true,
            'features' => $features,
        ]);
    }

    /* ============================================================
     |  HELPERS
     ============================================================ */
    private function getFeaturesForCategory(int $categoryId, ?int $subcategoryId = null): array
    {
        $featureIds = $this->featureCategoryMappingModel
            ->getFeatureIdsForCategory($categoryId, $subcategoryId);

        if (empty($featureIds)) {
            return [];
        }

        return $this->toArray(
            $this->featureModel->getFeaturesWithOptions($featureIds)
        );
    }

    private function getPriceRange($category, $subcategory = null): array
    {
        if ($category instanceof \Illuminate\Database\Eloquent\Model) {
            $category = $category->toArray();
        }
        if ($subcategory instanceof \Illuminate\Database\Eloquent\Model) {
            $subcategory = $subcategory->toArray();
        }

        $categoryId    = !empty($category['id']) ? $category['id'] : 0;
        $subcategoryId = !empty($subcategory['id']) ? $subcategory['id'] : null;

        $categoryClause = '';
        $clauseBindings = [];

        if ($subcategoryId) {
            $categoryClause = ' AND p.subcategory_id = ?';
            $clauseBindings[] = $subcategoryId;
        } elseif ($categoryId) {
            $categoryClause = ' AND p.category_id = ?';
            $clauseBindings[] = $categoryId;
        }

        $sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM (
                SELECT p.price
                FROM products p
                WHERE p.status = 'active'
                  AND p.stock > 0
                  AND NOT EXISTS (
                        SELECT 1 FROM product_variants pvx
                        WHERE pvx.product_id = p.id
                          AND pvx.status = 1
                  )
                  {$categoryClause}

                UNION ALL

                SELECT v.price
                FROM products p
                INNER JOIN product_variants v ON v.product_id = p.id
                WHERE p.status = 'active'
                  AND v.status = 1
                  AND v.stock > 0
                  {$categoryClause}
            ) combined";

        $allBindings = array_merge($clauseBindings, $clauseBindings);

        $result = DB::select($sql, $allBindings);

        return [
            'min' => (float) ($result[0]->min_price ?? 0),
            'max' => (float) ($result[0]->max_price ?? 10000),
        ];
    }

    private function toArray($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if ($value instanceof \Illuminate\Database\Eloquent\Model) {
            return $value->toArray();
        }
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }
        return (array) $value;
    }

    /* ============================================================
     |  PUBLIC ENDPOINTS
     ============================================================ */

    public function getCategoryTree()
    {
        $categories = $this->toArray(
            $this->categoryModel->getCategoryTree(true)
        );

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    public function getSubcategories($parentId, Request $request)
    {
        $activeOnly = $request->input('active_only', true);

        $category = $this->categoryModel->find($parentId);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $subcategories = $this->toArray(
            $this->categoryModel->getSubcategories($parentId, $activeOnly)
        );

        return response()->json([
            'success' => true,
            'data'    => $subcategories,
        ]);
    }

    public function getCategoryBySlug($slug)
    {
        $category = $this->toArray(
            $this->categoryModel->getCategoryBySlug($slug)
        );

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $category,
        ]);
    }

    /**
     * Normalize a product row for the frontend.
     * Ensures product_id, variant_id, has_variants, image_url are always present.
     */
    private function normalizeProduct(array $product): array
    {
        $productId = (int) ($product['id'] ?? $product['product_id'] ?? 0);

        // Load the first in-stock variant (if any) — same logic as HomeController
        $variant = \Illuminate\Support\Facades\DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->orderBy('id', 'ASC')
            ->first();

        $hasVariants = \Illuminate\Support\Facades\DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->exists();

        $out = $product;
        $out['product_id']   = $productId;
        $out['id']           = $productId;
        $out['has_variants'] = (bool) $hasVariants;
        $out['variant_id']   = $variant ? (int) $variant->id : 0;
        $out['variant_name'] = $variant ? ($variant->variant_name ?? null) : null;

        // If a variant is present, overlay its price/stock
        if ($variant) {
            $out['price']      = (float) $variant->price;
            $out['sale_price'] = ($variant->sale_price !== null && (float) $variant->sale_price > 0)
                ? (float) $variant->sale_price
                : null;
            $out['stock']      = (int) $variant->stock;
            $out['sku']        = $variant->sku ?? $out['sku'] ?? null;
            $out['rating']     = $variant->rating ?? $out['rating'] ?? 0;
        }

        // Resolve image URL
        $imagePath = $variant->image ?? null;
        if (!$imagePath) {
            $imagePath = \Illuminate\Support\Facades\DB::table('product_variant_images')
                ->where('variant_id', $variant ? $variant->id : 0)
                ->orderBy('is_primary', 'DESC')
                ->orderBy('sort_order', 'ASC')
                ->value('image');
        }
        if (!$imagePath && !empty($product['image'])) {
            $imagePath = $product['image'];
        }

        if ($imagePath) {
            $out['image_url'] = $this->resolveImageUrl($imagePath);
            $out['image']     = $imagePath;
        }

        return $out;
    }

    private function resolveImageUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) return $path;
        if (str_starts_with($path, '/'))     return $path;
        if (str_starts_with($path, 'storage/')) return '/' . $path;
        return '/storage/' . ltrim($path, '/');
    }

    public function getCategoryProducts($categoryId, Request $request)
    {
        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $page    = $request->input('page', 1);

        $products = $this->productModel
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'category'     => $this->toArray($category),
                'products'     => $products->items(),
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    public function getCategoryWithSubcategories($categoryId)
    {
        $category = $this->categoryModel->find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $subcategories = $this->toArray(
            $this->categoryModel->getSubcategories($categoryId, true)
        );

        foreach ($subcategories as &$subcategory) {
            $subcategory['product_count'] = $this->productModel
                ->where('subcategory_id', $subcategory['id'])
                ->where('status', 'active')
                ->count();
        }
        unset($subcategory);

        return response()->json([
            'success' => true,
            'data' => [
                'category'      => $this->toArray($category),
                'subcategories' => $subcategories,
            ],
        ]);
    }

    public function getCategoryProductsFiltered(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'    => 'nullable|integer|exists:product_categories,id',
            'subcategory_id' => 'nullable|integer|exists:product_categories,id',
            'min_price'      => 'nullable|numeric|min:0',
            'max_price'      => 'nullable|numeric|min:0',
            'sort'           => 'nullable|string|in:latest,oldest,price_low,price_high,rating_high,discount_high',
            'per_page'       => 'nullable|integer|min:1|max:100',
            'page'           => 'nullable|integer|min:1',
            'features'       => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $categoryId    = $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id');
        $minPrice      = $request->input('min_price', 0);
        $maxPrice      = $request->input('max_price', 10000);
        $sort          = $request->input('sort', 'latest');
        $perPage       = $request->input('per_page', 20);
        $page          = $request->input('page', 1);
        $features      = $request->input('features', []);

        if (empty($categoryId) && !empty($subcategoryId)) {
            $sub = $this->categoryModel->find($subcategoryId);
            if ($sub) {
                $categoryId = $sub->parent_id;
            }
        }

        $filterData = [
            'category_id'    => $categoryId ? (int) $categoryId : null,
            'subcategory_id' => $subcategoryId ? (int) $subcategoryId : null,
            'min_price'      => (float) $minPrice,
            'max_price'      => (float) $maxPrice,
            'sort'           => $sort,
            'page'           => (int) $page,
            'features'       => is_array($features) ? $features : [],
            'per_page'       => (int) $perPage,
        ];

        $result = $this->productModel->getFilteredProducts($filterData);

        return response()->json([
            'success' => true,
            'debug' => [
                'filterData'    => $filterData,
                'result_rows'   => count($result['rows'] ?? []),
                'result_total'  => $result['total'] ?? 0,
                'result_raw'    => $result,
            ],
        ]);

        $rows = $result['rows'] ?? [];
        $rows = array_map(fn($row) => $this->normalizeProduct((array) $row), $rows);

        return response()->json([
            'success' => true,
            'data' => [
                'products'     => $rows,
                'total'        => $result['total'] ?? 0,
                'per_page'     => $result['per_page'] ?? 20,
                'current_page' => $result['current_page'] ?? 1,
                'last_page'    => $result['last_page'] ?? 1,
            ],
        ]);
    }
}
