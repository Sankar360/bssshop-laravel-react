<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    /**
     * @var Category
     */
    protected $categoryModel;

    /**
     * AdminCategoryController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->categoryModel = new Category();
    }

    /**
     * List categories with subcategories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $perPage = $request->get('per_page', 20);

        $query = $this->categoryModel->newQuery();

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status === 'active' ? 1 : 0);
        }

        // Get categories with parent relationship
        $categories = $query->with('children')
            ->orderBy('sort_order', 'ASC')
            ->paginate($perPage);

        // Get parent categories for dropdown
        $parentCategories = $this->categoryModel->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get();

        // Get statistics
        $stats = [
            'total' => $this->categoryModel->count(),
            'active' => $this->categoryModel->where('status', 1)->count(),
            'inactive' => $this->categoryModel->where('status', 0)->count(),
            'parent' => $this->categoryModel->whereNull('parent_id')->count(),
            'subcategories' => $this->categoryModel->whereNotNull('parent_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $categories,
            'parent_categories' => $parentCategories,
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
        $category = $this->categoryModel->with(['children' => function ($query) {
            $query->orderBy('sort_order', 'ASC');
        }])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        // Get parent categories for dropdown
        $parentCategories = $this->categoryModel->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $category,
            'parent_categories' => $parentCategories,
            'subcategories' => $category->children,
        ]);
    }

    /**
     * Get data for category creation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $parentCategories = $this->categoryModel->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'parent_categories' => $parentCategories,
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
            'name' => 'required|string|min:2|max:100',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|integer|in:0,1',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate slug from name
        $slug = Str::slug($request->name);

        // Check if slug exists
        $existing = $this->categoryModel->where('slug', $slug)->first();
        if ($existing) {
            $slug = $slug . '-' . uniqid();
        }

        $data = [
            'parent_id' => $request->parent_id ?: null,
            'name' => $request->name,
            'slug' => $slug,
            'icon' => $request->icon ?? 'bi-folder',
            'description' => $request->description,
            'status' => $request->status ?? 1,
            'sort_order' => $request->sort_order ?? 0,
        ];

        $category = $this->categoryModel->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Get category data for editing.
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
                'message' => 'Category not found',
            ], 404);
        }

        // Get parent categories excluding current category
        $parentCategories = $this->categoryModel->whereNull('parent_id')
            ->where('id', '!=', $id)
            ->orderBy('sort_order', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $category,
            'parent_categories' => $parentCategories,
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
                'message' => 'Category not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:100',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$id]), // Prevent self-parent
            ],
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|integer|in:0,1',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'parent_id' => $request->parent_id ?: null,
            'name' => $request->name,
            'icon' => $request->icon ?? 'bi-folder',
            'description' => $request->description,
            'status' => $request->status ?? 1,
            'sort_order' => $request->sort_order ?? 0,
        ];

        // Update slug if name changed
        if ($category->name !== $request->name) {
            $slug = Str::slug($request->name);
            $existing = $this->categoryModel->where('slug', $slug)
                ->where('id', '!=', $id)
                ->first();
            if ($existing) {
                $slug = $slug . '-' . uniqid();
            }
            $data['slug'] = $slug;
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
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
                'message' => 'Category not found',
            ], 404);
        }

        // Check if category has subcategories
        $hasSubcategories = $this->categoryModel->where('parent_id', $id)->exists();

        if ($hasSubcategories) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with subcategories',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
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
                'message' => 'Category not found',
            ], 404);
        }

        $newStatus = $category->status == 1 ? 0 : 1;
        $category->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus == 1 ? 'Category activated' : 'Category deactivated',
            'data' => $category,
        ]);
    }

    /**
     * Get subcategories for a category.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubcategories($id)
    {
        $category = $this->categoryModel->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $subcategories = $this->categoryModel->where('parent_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subcategories,
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
            'ids.*' => 'integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->ids;

        // Check if any category has subcategories
        $hasSubcategories = $this->categoryModel->whereIn('parent_id', $ids)->exists();

        if ($hasSubcategories) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete categories that have subcategories',
            ], 422);
        }

        $deleted = $this->categoryModel->whereIn('id', $ids)->delete();

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
            'ids.*' => 'integer|exists:categories,id',
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
            ->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => $updated . ' categories updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get category tree (all categories with their children).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoryTree()
    {
        $categories = $this->categoryModel->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('sort_order', 'ASC');
            }])
            ->orderBy('sort_order', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
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
            'orders.*.id' => 'required|integer|exists:categories,id',
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

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully',
        ]);
    }

    /**
     * Get categories for dropdown (nested).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNestedCategories()
    {
        $categories = $this->getNestedCategoryList();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Helper method to get nested category list.
     *
     * @param int|null $parentId
     * @param string $prefix
     * @return array
     */
    private function getNestedCategoryList($parentId = null, $prefix = '')
    {
        $categories = $this->categoryModel->where('parent_id', $parentId)
            ->orderBy('sort_order', 'ASC')
            ->get();

        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'name' => $prefix . $category->name,
                'slug' => $category->slug,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
                'level' => $prefix ? substr_count($prefix, '--') + 1 : 0,
            ];

            $children = $this->getNestedCategoryList($category->id, $prefix . '-- ');
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
            'active' => $this->categoryModel->where('status', 1)->count(),
            'inactive' => $this->categoryModel->where('status', 0)->count(),
            'parent_categories' => $this->categoryModel->whereNull('parent_id')->count(),
            'subcategories' => $this->categoryModel->whereNotNull('parent_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get categories with product count.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoriesWithProductCount()
    {
        $categories = $this->categoryModel->withCount('products')
            ->orderBy('sort_order', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get category by slug.
     *
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySlug($slug)
    {
        $category = $this->categoryModel->with(['children' => function ($query) {
            $query->orderBy('sort_order', 'ASC');
        }])->where('slug', $slug)->first();

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
}