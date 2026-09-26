<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureValue;
use App\Models\FeatureCategoryMapping;
use App\Models\ProductCategory;
use App\Models\ProductFeatureValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AdminFeatureController extends Controller
{
    /**
     * @var Feature
     */
    protected $featureModel;

    /**
     * @var FeatureValue
     */
    protected $featureValueModel;

    /**
     * @var FeatureCategoryMapping
     */
    protected $mappingModel;

    /**
     * @var ProductCategory
     */
    protected $categoryModel;

    /**
     * AdminFeatureController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->featureModel = new Feature();
        $this->featureValueModel = new FeatureValue();
        $this->mappingModel = new FeatureCategoryMapping();
        $this->categoryModel = new ProductCategory();
    }

    /**
     * List all features.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $perPage = $request->get('per_page', 20);

        $query = $this->featureModel->orderBy('name', 'ASC');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if ($status !== 'all') {
            $query->where('status', $status === 'active' ? 1 : 0);
        }

        $features = $query->paginate($perPage);

        // Get statistics
        $stats = [
            'total' => $this->featureModel->count(),
            'active' => $this->featureModel->where('status', 1)->count(),
            'inactive' => $this->featureModel->where('status', 0)->count(),
            'text' => $this->featureModel->where('input_type', 'text')->count(),
            'dropdown' => $this->featureModel->where('input_type', 'dropdown')->count(),
            'checkbox' => $this->featureModel->where('input_type', 'checkbox')->count(),
            'color' => $this->featureModel->where('input_type', 'color')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $features,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a new feature.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:150',
            'input_type' => ['required', Rule::in(['text', 'dropdown', 'checkbox', 'color'])],
            'options' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'input_type' => $request->input_type,
            'options' => $request->options,
            'status' => $request->status ?? 1,
        ];

        $feature = $this->featureModel->create($data);

        Log::info('Feature created: ' . $feature->name . ' (ID: ' . $feature->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Feature added successfully',
            'data' => $feature,
        ]);
    }

    /**
     * Update a feature.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $feature = $this->featureModel->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:150',
            'input_type' => ['required', Rule::in(['text', 'dropdown', 'checkbox', 'color'])],
            'options' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'input_type' => $request->input_type,
            'options' => $request->options,
            'status' => $request->status ?? $feature->status,
        ];

        $feature->update($data);

        Log::info('Feature updated: ' . $feature->name . ' (ID: ' . $feature->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Feature updated successfully',
            'data' => $feature,
        ]);
    }

    /**
     * Delete a feature.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $feature = $this->featureModel->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        // Delete all feature values
        $this->featureValueModel->where('feature_id', $id)->delete();

        // Delete feature-category mappings
        $this->mappingModel->where('feature_id', $id)->delete();

        // Delete the feature
        $feature->delete();

        Log::info('Feature deleted: ' . $feature->name . ' (ID: ' . $feature->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Feature deleted successfully',
        ]);
    }

    /**
     * Toggle feature status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $feature = $this->featureModel->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        $newStatus = $feature->status == 1 ? 0 : 1;
        $feature->update(['status' => $newStatus]);

        Log::info('Feature status toggled: ' . $feature->name . ' (ID: ' . $feature->id . ') to ' . $newStatus . ' by admin');

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus == 1 ? 'Feature activated' : 'Feature deactivated',
            'data' => $feature,
        ]);
    }

    /**
     * Get feature assignment data.
     *
     * @param int $featureId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign($featureId)
    {
        $feature = $this->featureModel->find($featureId);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        // Get feature values
        $featureValues = $this->featureValueModel->getActiveValuesForFeature($featureId);

        // Get assigned category IDs
        $assignedCategoryIds = $this->mappingModel->getCategoryIdsForFeature($featureId);

        // Get category tree
        $categoryTree = $this->categoryModel->getCategoryTree();

        return response()->json([
            'success' => true,
            'data' => [
                'feature' => $feature,
                'feature_values' => $featureValues,
                'category_tree' => $categoryTree,
                'assigned_category_ids' => $assignedCategoryIds,
            ],
        ]);
    }

    /**
     * Save feature-category assignment.
     *
     * @param Request $request
     * @param int $featureId
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAssignment(Request $request, $featureId)
    {
        try {
            $feature = $this->featureModel->find($featureId);

            if (!$feature) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feature not found',
                ], 404);
            }

            $categoryIds = $request->get('category_ids', []);
            $subCategoryIds = $request->get('sub_category_ids', []);

            Log::debug('Category IDs: ' . json_encode($categoryIds));
            Log::debug('Sub Category IDs: ' . json_encode($subCategoryIds));

            $result = $this->mappingModel->saveAssignment(
                (int) $featureId,
                $categoryIds,
                $subCategoryIds
            );

            if ($result) {
                $saved = $this->mappingModel->where('feature_id', $featureId)->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Assignment saved successfully',
                    'total_saved' => $saved->count(),
                    'saved_records' => $saved,
                    'category_ids' => $categoryIds,
                    'sub_category_ids' => $subCategoryIds,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to save assignment',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Save Assignment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new feature value.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeValue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feature_id' => 'required|integer|exists:features,id',
            'value' => 'required|string|min:1|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'feature_id' => $request->feature_id,
            'value' => $request->value,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status ?? 1,
        ];

        $value = $this->featureValueModel->create($data);

        Log::info('Feature value created: ' . $value->value . ' (ID: ' . $value->id . ') for feature ' . $value->feature_id . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Feature value added successfully',
            'data' => $value,
        ]);
    }

    /**
     * Update a feature value.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateValue(Request $request, $id)
    {
        $value = $this->featureValueModel->find($id);

        if (!$value) {
            return response()->json([
                'success' => false,
                'message' => 'Feature value not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'value' => 'required|string|min:1|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'value' => $request->value,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status ?? $value->status,
        ];

        $value->update($data);

        Log::info('Feature value updated: ' . $value->value . ' (ID: ' . $value->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Feature value updated successfully',
            'data' => $value,
        ]);
    }

    /**
     * Delete a feature value.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteValue($id)
    {
        $value = $this->featureValueModel->find($id);

        if (!$value) {
            return response()->json([
                'success' => false,
                'message' => 'Feature value not found',
            ], 404);
        }

        $value->delete();

        Log::info('Feature value deleted: ' . $value->value . ' (ID: ' . $value->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Feature value deleted successfully',
        ]);
    }

    /**
     * Toggle feature value status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleValueStatus($id)
    {
        $value = $this->featureValueModel->find($id);

        if (!$value) {
            return response()->json([
                'success' => false,
                'message' => 'Feature value not found',
            ], 404);
        }

        $newStatus = $value->status == 1 ? 0 : 1;
        $value->update(['status' => $newStatus]);

        Log::info('Feature value status toggled: ' . $value->value . ' (ID: ' . $value->id . ') to ' . $newStatus . ' by admin');

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus == 1 ? 'Value activated' : 'Value deactivated',
            'data' => $value,
        ]);
    }

    /**
     * Get feature values for a feature (AJAX).
     *
     * @param int $featureId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getValues($featureId)
    {
        $feature = $this->featureModel->find($featureId);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        $values = $this->featureValueModel->getActiveValuesForFeature($featureId);

        return response()->json([
            'success' => true,
            'values' => $values,
            'input_type' => $feature->input_type,
        ]);
    }

    /**
     * Get features assigned to a category (for product edit).
     *
     * @param Request $request
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function forCategory(Request $request, $categoryId)
    {
        $productId = $request->get('product_id');

        $features = $this->mappingModel->getFeaturesForCategory($categoryId);

        $savedValues = [];
        if ($productId) {
            $valueModel = new ProductFeatureValue();
            $savedValues = $valueModel->getValuesForProduct($productId);
        }

        foreach ($features as &$feature) {
            $featureValues = $this->featureValueModel->getActiveValuesForFeature($feature['id']);
            if (!empty($featureValues)) {
                $feature['options_array'] = array_column($featureValues, 'value');
                $feature['options_full'] = $featureValues;
            } else {
                $feature['options_array'] = $this->featureModel->getOptionsArray($feature);
                $feature['options_full'] = [];
            }
            $feature['saved_value'] = $savedValues[$feature['id']] ?? '';
        }

        return response()->json([
            'success' => true,
            'features' => $features,
        ]);
    }

    /**
     * Get features with their values (for frontend).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeaturesWithValues(Request $request)
    {
        $featureIds = $request->get('feature_ids', []);

        if (empty($featureIds)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $features = $this->featureModel->getFeaturesWithOptions($featureIds);

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    /**
     * Get all feature categories with assignment info.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeatureCategories()
    {
        $features = $this->featureModel->with('categoryMappings')
            ->orderBy('name', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    /**
     * Bulk delete features.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:features,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->ids;

        // Delete feature values for these features
        $this->featureValueModel->whereIn('feature_id', $ids)->delete();

        // Delete mappings
        $this->mappingModel->whereIn('feature_id', $ids)->delete();

        // Delete features
        $deleted = $this->featureModel->whereIn('id', $ids)->delete();

        Log::info('Bulk delete: ' . $deleted . ' features deleted by admin');

        return response()->json([
            'success' => true,
            'message' => $deleted . ' features deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Bulk update feature status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:features,id',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->featureModel->whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        Log::info('Bulk status update: ' . $updated . ' features updated to ' . $request->status . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $updated . ' features updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get feature statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total' => $this->featureModel->count(),
            'active' => $this->featureModel->where('status', 1)->count(),
            'inactive' => $this->featureModel->where('status', 0)->count(),
            'text' => $this->featureModel->where('input_type', 'text')->count(),
            'dropdown' => $this->featureModel->where('input_type', 'dropdown')->count(),
            'checkbox' => $this->featureModel->where('input_type', 'checkbox')->count(),
            'color' => $this->featureModel->where('input_type', 'color')->count(),
            'total_values' => $this->featureValueModel->count(),
            'total_mappings' => $this->mappingModel->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get feature by ID with values.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeature($id)
    {
        $feature = $this->featureModel->with('featureValues')
            ->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $feature,
        ]);
    }

    /**
     * Get all features for dropdown.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForDropdown(Request $request)
    {
        $activeOnly = $request->get('active_only', true);

        $query = $this->featureModel->orderBy('name', 'ASC');

        if ($activeOnly) {
            $query->where('status', 1);
        }

        $features = $query->get(['id', 'name', 'input_type']);

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }
}