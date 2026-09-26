<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminProductCategoryController extends Controller
{
    /**
     * @var ProductCategory
     */
    protected $categoryModel;

    /**
     * AdminProductCategoryController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->categoryModel = new ProductCategory();
    }

    /**
     * List top-level categories with category tree.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $categories = $this->categoryModel->getCategoryTree();

        // Get statistics
        $stats = [
            'total' => $this->categoryModel->count(),
            'active' => $this->categoryModel->where('is_active', true)->count(),
            'inactive' => $this->categoryModel->where('is_active', false)->count(),
            'parent_categories' => $this->categoryModel->whereNull('parent_id')->count(),
            'subcategories' => $this->categoryModel->whereNotNull('parent_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $categories,
            'stats' => $stats,
        ]);
    }

    /**
     * Get category details with subcategories.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view($id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found!',
            ], 404);
        }

        $subcategories = $this->categoryModel->getSubcategories($id);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'subcategories' => $subcategories,
            ],
        ]);
    }

    /**
     * Get data for category creation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $parentId = $request->get('parent_id');

        $parentCategories = $this->categoryModel->getParentCategories();

        return response()->json([
            'success' => true,
            'data' => [
                'parent_id' => $parentId,
                'parent_categories' => $parentCategories,
            ],
        ]);
    }

    /**
     * Store a new category.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|integer|exists:product_categories,id',
            'icon' => 'nullable|string|max:50',
            'item_count' => 'nullable|integer',
            'link' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parentId = $request->parent_id ?: null;

        $data = [
            'parent_id' => $parentId,
            'name' => $request->name,
            'icon' => $request->icon ?? 'bi-box',
            'item_count' => $request->item_count ?? 0,
            'link' => $request->link ?? '#',
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? 1,
        ];

        $category = $this->categoryModel->create($data);

        Log::info('Product category created: ' . $category->name . ' (ID: ' . $category->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => $parentId ? 'Subcategory added successfully!' : 'Category added successfully!',
            'data' => $category,
            'parent_id' => $parentId,
        ], 201);
    }

    /**
     * Get category for editing.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found!',
            ], 404);
        }

        $parentCategories = $this->categoryModel->getParentCategories();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'parent_categories' => $parentCategories,
            ],
        ]);
    }

    /**
     * Update a category.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found!',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:product_categories,id',
                Rule::notIn([$id]), // Prevent self-parent
            ],
            'icon' => 'nullable|string|max:50',
            'item_count' => 'nullable|integer',
            'link' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parentId = $request->parent_id ?: null;

        // Guard against a category becoming its own parent
        if ($parentId == $id) {
            $parentId = $category->parent_id;
        }

        $data = [
            'parent_id' => $parentId,
            'name' => $request->name,
            'icon' => $request->icon ?? 'bi-box',
            'item_count' => $request->item_count ?? 0,
            'link' => $request->link ?? '#',
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? 1,
        ];

        $category->update($data);

        Log::info('Product category updated: ' . $category->name . ' (ID: ' . $category->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully!',
            'data' => $category,
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Delete a category.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found!',
            ], 404);
        }

        $parentId = $category->parent_id;
        $categoryName = $category->name;

        // Delete category (FK ON DELETE CASCADE will handle subcategories)
        $category->delete();

        Log::info('Product category deleted: ' . $categoryName . ' (ID: ' . $id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully!',
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Toggle category status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found!',
            ], 404);
        }

        $newStatus = $category->is_active ? 0 : 1;
        $category->update(['is_active' => $newStatus]);

        Log::info('Product category status toggled: ' . $category->name . ' (ID: ' . $category->id . ') to ' . ($newStatus ? 'active' : 'inactive') . ' by admin');

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus ? 'Category activated' : 'Category deactivated',
            'data' => $category,
        ]);
    }

    /**
     * Get active subcategories for a parent category (AJAX).
     *
     * @param int $parentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function subcategories($parentId)
    {
        $parentCategory = $this->categoryModel->find($parentId);

        if (!$parentCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Parent category not found!',
            ], 404);
        }

        $subcategories = $this->categoryModel->getSubcategories($parentId, true);

        return response()->json([
            'success' => true,
            'data' => $subcategories,
        ]);
    }

    /**
     * Get category tree (all categories with their children).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryTree()
    {
        $categories = $this->categoryModel->getCategoryTree();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get categories for dropdown (nested).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForDropdown(Request $request)
    {
        $activeOnly = $request->get('active_only', false);
        $excludeId = $request->get('exclude_id');

        $categories = $this->getNestedCategoryList($activeOnly, $excludeId);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Helper method to get nested category list for dropdown.
     *
     * @param bool $activeOnly
     * @param int|null $excludeId
     * @param int|null $parentId
     * @param string $prefix
     * @return array
     */
    private function getNestedCategoryList(bool $activeOnly = false, ?int $excludeId = null, ?int $parentId = null, string $prefix = '')
    {
        $query = $this->categoryModel->where('parent_id', $parentId)
            ->orderBy('sort_order', 'ASC');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $categories = $query->get();

        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'name' => $prefix . $category->name,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
                'level' => $prefix ? substr_count($prefix, '--') + 1 : 0,
            ];

            $children = $this->getNestedCategoryList($activeOnly, $excludeId, $category->id, $prefix . '-- ');
            $result = array_merge($result, $children);
        }

        return $result;
    }

    /**
     * Get category statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total' => $this->categoryModel->count(),
            'active' => $this->categoryModel->where('is_active', true)->count(),
            'inactive' => $this->categoryModel->where('is_active', false)->count(),
            'parent_categories' => $this->categoryModel->whereNull('parent_id')->count(),
            'subcategories' => $this->categoryModel->whereNotNull('parent_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Bulk delete categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:product_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->ids;

        // Check if any of these categories have subcategories
        $hasSubcategories = $this->categoryModel->whereIn('parent_id', $ids)->exists();

        if ($hasSubcategories) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete categories that have subcategories',
            ], 422);
        }

        $deleted = $this->categoryModel->whereIn('id', $ids)->delete();

        Log::info('Bulk delete: ' . $deleted . ' product categories deleted by admin');

        return response()->json([
            'success' => true,
            'message' => $deleted . ' categories deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Bulk update category status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:product_categories,id',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->categoryModel->whereIn('id', $request->ids)
            ->update(['is_active' => $request->status]);

        Log::info('Bulk status update: ' . $updated . ' product categories updated to ' . ($request->status ? 'active' : 'inactive') . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $updated . ' categories updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Reorder categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:product_categories,id',
            'orders.*.sort_order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->orders as $order) {
            $this->categoryModel->where('id', $order['id'])
                ->update(['sort_order' => $order['sort_order']]);
        }

        Log::info('Product categories reordered by admin');

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully!',
        ]);
    }

    /**
     * Get category by slug/link.
     *
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySlug($slug)
    {
        $category = $this->categoryModel->where('link', $slug)
            ->orWhere('name', 'LIKE', '%' . str_replace('-', ' ', $slug) . '%')
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $subcategories = $this->categoryModel->getSubcategories($category->id, true);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'subcategories' => $subcategories,
            ],
        ]);
    }

    /**
     * Get category with product count.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWithProductCount($id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found!',
            ], 404);
        }

        $productCount = $category->products()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'product_count' => $productCount,
            ],
        ]);
    }

    /**
     * Get category hierarchy (for breadcrumb).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHierarchy($id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found!',
            ], 404);
        }

        $breadcrumb = $this->categoryModel->getBreadcrumb($id);

        return response()->json([
            'success' => true,
            'data' => $breadcrumb,
        ]);
    }
}