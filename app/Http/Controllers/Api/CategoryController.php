<?php

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
    /**
     * @var ProductCategory
     */
    protected $categoryModel;

    /**
     * @var Product
     */
    protected $productModel;

    /**
     * @var Feature
     */
    protected $featureModel;

    /**
     * @var FeatureCategoryMapping
     */
    protected $featureCategoryMappingModel;

    /**
     * @var FeatureValue
     */
    protected $featureValueModel;

    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        $this->categoryModel = new ProductCategory();
        $this->productModel = new Product();
        $this->featureModel = new Feature();
        $this->featureCategoryMappingModel = new FeatureCategoryMapping();
        $this->featureValueModel = new FeatureValue();
    }

    /**
     * Get category overview with categories, subcategories, features, and price range.
     *
     * @param Request $request
     * @param string|null $categorySlug
     * @param string|null $subcategorySlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $categorySlug = null, $subcategorySlug = null)
    {
        // Get all parent categories
        $parentCategories = $this->categoryModel->getParentCategories(true);

        // Add children to each parent
        foreach ($parentCategories as &$parent) {
            $parent['children'] = $this->categoryModel->getSubcategories($parent['id'], true);
        }

        // Determine active category and subcategory
        $activeCategory = null;
        $activeSubcategory = null;

        // First, try to find by category slug
        if ($categorySlug) {
            $category = $this->categoryModel->getCategoryBySlug($categorySlug);

            if ($category) {
                if ($category['parent_id'] !== null) {
                    // It's a subcategory
                    $activeSubcategory = $category;
                    $activeCategory = $this->categoryModel->find($category['parent_id']);
                } else {
                    $activeCategory = $category;
                }
            }
        }

        // If subcategory slug is provided, find it
        if ($subcategorySlug && $activeCategory) {
            $subcategory = $this->categoryModel->getCategoryBySlug($subcategorySlug);
            if ($subcategory && $subcategory['parent_id'] == $activeCategory['id']) {
                $activeSubcategory = $subcategory;
            }
        }

        // If no category found, use first active parent
        if (!$activeCategory && !empty($parentCategories)) {
            $activeCategory = $parentCategories[0];
        }

        // Get subcategories for active category
        if ($activeCategory && !$activeSubcategory) {
            $subcategories = $this->categoryModel->getSubcategories($activeCategory['id'], true);
            if (!empty($subcategories) && !$subcategorySlug) {
                $activeSubcategory = $subcategories[0];
            }
        }

        // Get features for the active category/subcategory
        $features = [];
        if ($activeCategory) {
            $categoryId = $activeCategory['id'];
            $subcategoryId = $activeSubcategory ? $activeSubcategory['id'] : null;
            $features = $this->getFeaturesForCategory($categoryId, $subcategoryId);
        }

        // Get price range
        $priceRange = $this->getPriceRange($activeCategory, $activeSubcategory);

        return response()->json([
            'success' => true,
            'data' => [
                'parent_categories' => $parentCategories,
                'active_category' => $activeCategory,
                'active_subcategory' => $activeSubcategory,
                'features' => $features,
                'price_range' => [
                    'min' => $priceRange['min'] ?? 0,
                    'max' => $priceRange['max'] ?? 10000,
                ],
                'default_min_price' => $priceRange['min'] ?? 0,
                'default_max_price' => $priceRange['max'] ?? 10000,
                'title' => $activeCategory['name'] ?? 'Category Overview',
                'meta_description' => 'Browse our product categories and find the best deals.',
            ],
        ]);
    }

    /**
     * AJAX endpoint for getting filtered products.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts(Request $request)
    {
        $categoryId = (int) $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id') ? (int) $request->input('subcategory_id') : null;
        $minPrice = (float) ($request->input('min_price') ?? 0);
        $maxPrice = (float) ($request->input('max_price') ?? 10000);
        $sort = $request->input('sort', 'latest');
        $page = (int) ($request->input('page') ?? 1);
        $perPage = (int) ($request->input('per_page') ?? 12);

        // Parse features from JSON
        $features = $request->input('features');
        if (is_string($features)) {
            $features = json_decode($features, true);
        }
        if (!is_array($features)) {
            $features = [];
        }

        // Validate category exists
        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid category',
            ], 404);
        }

        // Validate subcategory if provided
        if ($subcategoryId) {
            $subcategory = $this->categoryModel->find($subcategoryId);
            if (!$subcategory || $subcategory['parent_id'] != $categoryId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid subcategory',
                ], 404);
            }
        }

        // Build filters array
        $filterData = [
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort' => $sort,
            'page' => $page,
            'features' => $features,
            'per_page' => $perPage,
        ];

        // Get products using the working method
        $result = $this->productModel->getFilteredProducts($filterData);

        return response()->json([
            'status' => true,
            'products' => $result['rows'] ?? [],
            'total' => $result['total'] ?? 0,
            'per_page' => $result['per_page'] ?? 12,
            'current_page' => $result['current_page'] ?? 1,
            'last_page' => $result['last_page'] ?? 1,
            'count_text' => ($result['total'] ?? 0) . ' ' . (($result['total'] ?? 0) == 1 ? 'Product' : 'Products'),
        ]);
    }

    /**
     * AJAX endpoint for getting price range.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPriceRangeAjax(Request $request)
    {
        $categoryId = (int) $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id') ? (int) $request->input('subcategory_id') : null;

        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid category',
            ], 404);
        }

        $subcategory = $subcategoryId ? $this->categoryModel->find($subcategoryId) : null;
        $priceRange = $this->getPriceRange($category, $subcategory);

        return response()->json([
            'status' => true,
            'min' => $priceRange['min'] ?? 0,
            'max' => $priceRange['max'] ?? 10000,
        ]);
    }

    /**
     * AJAX endpoint for getting features.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeaturesAjax(Request $request)
    {
        $categoryId = (int) $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id') ? (int) $request->input('subcategory_id') : null;

        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid category',
            ], 404);
        }

        $features = $this->getFeaturesForCategory($categoryId, $subcategoryId);

        return response()->json([
            'status' => true,
            'features' => $features,
        ]);
    }

    /**
     * Get features for a category/subcategory.
     *
     * @param int $categoryId
     * @param int|null $subcategoryId
     * @return array
     */
    private function getFeaturesForCategory(int $categoryId, ?int $subcategoryId = null): array
    {
        // Get feature IDs mapped to this category
        $featureIds = $this->featureCategoryMappingModel->getFeatureIdsForCategory($categoryId, $subcategoryId);

        if (empty($featureIds)) {
            return [];
        }

        // Get features with their values
        return $this->featureModel->getFeaturesWithOptions($featureIds);
    }

    /**
     * Get price range for a category/subcategory.
     *
     * @param array|null $category
     * @param array|null $subcategory
     * @return array
     */
    private function getPriceRange(?array $category, ?array $subcategory = null): array
    {
        $categoryId = $category ? $category['id'] : 0;
        $subcategoryId = $subcategory ? $subcategory['id'] : null;

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

    /**
     * Get all categories with their subcategories (for dropdown/navigation).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryTree()
    {
        $categories = $this->categoryModel->getCategoryTree(true);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get subcategories for a parent category.
     *
     * @param int $parentId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        $subcategories = $this->categoryModel->getSubcategories($parentId, $activeOnly);

        return response()->json([
            'success' => true,
            'data' => $subcategories,
        ]);
    }

    /**
     * Get category details by slug.
     *
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryBySlug($slug)
    {
        $category = $this->categoryModel->getCategoryBySlug($slug);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Get products for a specific category (public endpoint).
     *
     * @param int $categoryId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
        $page = $request->input('page', 1);

        $products = $this->productModel->where('category_id', $categoryId)
            ->where('status', 'active')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'products' => $products->items(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Get category with its subcategories and product count.
     *
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryWithSubcategories($categoryId)
    {
        $category = $this->categoryModel->find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $subcategories = $this->categoryModel->getSubcategories($categoryId, true);

        // Add product count to each subcategory
        foreach ($subcategories as &$subcategory) {
            $subcategory['product_count'] = $this->productModel
                ->where('subcategory_id', $subcategory['id'])
                ->where('status', 'active')
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'subcategories' => $subcategories,
            ],
        ]);
    }

    /**
     * Get category products with filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryProductsFiltered(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|exists:product_categories,id',
            'subcategory_id' => 'nullable|integer|exists:product_categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort' => 'nullable|string|in:latest,oldest,price_low,price_high,rating_high,discount_high',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $categoryId = $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id');
        $minPrice = $request->input('min_price', 0);
        $maxPrice = $request->input('max_price', 10000);
        $sort = $request->input('sort', 'latest');
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $features = $request->input('features', []);

        // Build filters
        $filterData = [
            'category_id' => (int) $categoryId,
            'subcategory_id' => $subcategoryId ? (int) $subcategoryId : null,
            'min_price' => (float) $minPrice,
            'max_price' => (float) $maxPrice,
            'sort' => $sort,
            'page' => (int) $page,
            'features' => $features,
            'per_page' => (int) $perPage,
        ];

        $result = $this->productModel->getFilteredProducts($filterData);

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $result['rows'] ?? [],
                'total' => $result['total'] ?? 0,
                'per_page' => $result['per_page'] ?? 20,
                'current_page' => $result['current_page'] ?? 1,
                'last_page' => $result['last_page'] ?? 1,
            ],
        ]);
    }
}