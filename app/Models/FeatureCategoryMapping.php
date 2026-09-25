<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeatureCategoryMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feature_category_mapping';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'feature_id',
        'category_id',
        'sub_category_id',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'feature_id' => 'integer',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the feature that owns the mapping.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    /**
     * Get the category that owns the mapping.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }

    /**
     * Get the subcategory that owns the mapping.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'sub_category_id', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to filter by feature.
     */
    public function scopeByFeature($query, int $featureId)
    {
        return $query->where('feature_id', $featureId);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by subcategory.
     */
    public function scopeBySubcategory($query, int $subcategoryId)
    {
        return $query->where('sub_category_id', $subcategoryId);
    }

    /**
     * Scope a query to filter by category or subcategory.
     */
    public function scopeByCategoryOrSubcategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId)
            ->orWhere('sub_category_id', $categoryId);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get feature IDs for a category.
     * 
     * @param int $categoryId
     * @param int|null $subcategoryId
     * @return array<int>
     */
    public function getFeatureIdsForCategory(int $categoryId, ?int $subcategoryId = null): array
    {
        $query = $this->byCategory($categoryId);

        if ($subcategoryId) {
            $query->where(function ($q) use ($subcategoryId) {
                $q->where('sub_category_id', $subcategoryId)
                    ->orWhereNull('sub_category_id');
            });
        } else {
            $query->whereNull('sub_category_id');
        }

        return $query->pluck('feature_id')->toArray();
    }

    /**
     * Get features with their values for a category.
     * 
     * @param int $categoryId
     * @param int|null $subcategoryId
     * @return array
     */
    public function getFeaturesWithValuesForCategory(int $categoryId, ?int $subcategoryId = null): array
    {
        $featureIds = $this->getFeatureIdsForCategory($categoryId, $subcategoryId);

        if (empty($featureIds)) {
            return [];
        }

        $featureModel = new Feature();
        $features = [];

        foreach ($featureIds as $featureId) {
            $feature = $featureModel->getFeatureWithOptions($featureId);
            if ($feature && ($feature['status'] ?? 0) == 1) {
                $features[] = $feature;
            }
        }

        return $features;
    }

    /**
     * Get features with their values for a category as a collection.
     * 
     * @param int $categoryId
     * @param int|null $subcategoryId
     * @return Collection
     */
    public function getFeaturesWithValuesForCategoryCollection(int $categoryId, ?int $subcategoryId = null): Collection
    {
        $featureIds = $this->getFeatureIdsForCategory($categoryId, $subcategoryId);

        if (empty($featureIds)) {
            return collect();
        }

        return Feature::with('activeFeatureValues')
            ->whereIn('id', $featureIds)
            ->active()
            ->orderByName()
            ->get();
    }

    /**
     * Get ALL category IDs where a feature is assigned.
     * Returns both parent categories and subcategories.
     * 
     * @param int $featureId
     * @return array<int>
     */
    public function getCategoryIdsForFeature(int $featureId): array
    {
        $rows = $this->byFeature($featureId)->get();
        $ids = [];

        foreach ($rows as $row) {
            // If sub_category_id exists, use it (it's a subcategory)
            if (!empty($row->sub_category_id)) {
                $ids[] = (int) $row->sub_category_id;
            }
            // If category_id exists and no sub_category_id, it's a parent category
            elseif (!empty($row->category_id)) {
                $ids[] = (int) $row->category_id;
            }
        }

        return array_unique($ids);
    }

    /**
     * Get ALL categories where a feature is assigned (with details).
     * 
     * @param int $featureId
     * @return Collection
     */
    public function getCategoriesForFeature(int $featureId): Collection
    {
        $rows = $this->byFeature($featureId)->get();
        $categoryIds = [];

        foreach ($rows as $row) {
            if (!empty($row->sub_category_id)) {
                $categoryIds[] = (int) $row->sub_category_id;
            } elseif (!empty($row->category_id)) {
                $categoryIds[] = (int) $row->category_id;
            }
        }

        return ProductCategory::whereIn('id', array_unique($categoryIds))
            ->active()
            ->sorted()
            ->get();
    }

    /**
     * Get mapping data for a feature (for debugging).
     * 
     * @param int $featureId
     * @return array
     */
    public function getMappingForFeature(int $featureId): array
    {
        return $this->byFeature($featureId)
            ->with(['category', 'subcategory'])
            ->get()
            ->toArray();
    }

    /**
     * Save feature-category assignment.
     * 
     * @param int $featureId
     * @param array $categoryIds
     * @param array $subCategoryIds
     * @return bool
     */
    public function saveAssignment(int $featureId, array $categoryIds, array $subCategoryIds = []): bool
    {
        try {
            DB::transaction(function () use ($featureId, $categoryIds, $subCategoryIds) {
                // Delete existing assignments
                $this->where('feature_id', $featureId)->delete();

                $rows = [];
                $now = now();
                $categoryModel = new ProductCategory();

                // Process subcategory IDs
                foreach ($subCategoryIds as $subCategoryId) {
                    if (empty($subCategoryId)) {
                        continue;
                    }

                    // Get the parent category ID for this subcategory
                    $subCategory = $categoryModel->find((int) $subCategoryId);
                    $parentId = $subCategory && !empty($subCategory->parent_id) ? $subCategory->parent_id : 0;

                    $rows[] = [
                        'feature_id' => $featureId,
                        'category_id' => $parentId,
                        'sub_category_id' => (int) $subCategoryId,
                        'created_at' => $now,
                    ];
                }

                // Process parent category IDs (categories without subcategories)
                foreach ($categoryIds as $categoryId) {
                    if (empty($categoryId)) {
                        continue;
                    }

                    // Check if this is a parent category (has no parent_id)
                    $category = $categoryModel->find((int) $categoryId);
                    if ($category && empty($category->parent_id)) {
                        $rows[] = [
                            'feature_id' => $featureId,
                            'category_id' => (int) $categoryId,
                            'sub_category_id' => null,
                            'created_at' => $now,
                        ];
                    }
                }

                if (!empty($rows)) {
                    $this->insert($rows);
                }

                Log::debug('Saved rows for feature ' . $featureId . ': ' . json_encode($rows));
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Save Assignment Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get features assigned to a category (either as parent or subcategory).
     * 
     * @param int $categoryId
     * @return array
     */
    public function getFeaturesForCategory(int $categoryId): array
    {
        return $this->select('features.*')
            ->join('features', 'features.id', '=', 'feature_category_mapping.feature_id')
            ->where(function ($query) use ($categoryId) {
                $query->where('feature_category_mapping.category_id', $categoryId)
                    ->orWhere('feature_category_mapping.sub_category_id', $categoryId);
            })
            ->where('features.status', 1)
            ->groupBy('features.id')
            ->orderBy('features.name', 'ASC')
            ->get()
            ->toArray();
    }

    /**
     * Get features assigned to a category as a collection.
     * 
     * @param int $categoryId
     * @return Collection
     */
    public function getFeaturesForCategoryCollection(int $categoryId): Collection
    {
        return $this->select('features.*')
            ->join('features', 'features.id', '=', 'feature_category_mapping.feature_id')
            ->where(function ($query) use ($categoryId) {
                $query->where('feature_category_mapping.category_id', $categoryId)
                    ->orWhere('feature_category_mapping.sub_category_id', $categoryId);
            })
            ->where('features.status', 1)
            ->groupBy('features.id')
            ->orderBy('features.name', 'ASC')
            ->get();
    }

    /**
     * Get features for a category with product values.
     * 
     * @param int $categoryId
     * @param int|null $productId
     * @return array
     */
    public function getFeaturesForCategoryWithProductValues(int $categoryId, ?int $productId = null): array
    {
        $features = $this->getFeaturesForCategory($categoryId);

        if ($productId) {
            $valueModel = new ProductFeatureValue();
            $savedValues = $valueModel->getValuesForProduct($productId);
            $featureModel = new Feature();

            foreach ($features as &$feature) {
                $feature['saved_value'] = $savedValues[$feature['id']] ?? '';
                $feature['options_array'] = $featureModel->getOptionsArray($feature);
            }
        }

        return $features;
    }

    /**
     * Check if a feature is assigned to a category.
     * 
     * @param int $featureId
     * @param int $categoryId
     * @return bool
     */
    public function isFeatureAssignedToCategory(int $featureId, int $categoryId): bool
    {
        return $this->where('feature_id', $featureId)
            ->where(function ($query) use ($categoryId) {
                $query->where('sub_category_id', $categoryId)
                    ->orWhere('category_id', $categoryId);
            })
            ->exists();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get all features with their assigned categories.
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getAllFeaturesWithCategories(bool $activeOnly = true): Collection
    {
        $query = $this->with(['feature', 'category', 'subcategory']);

        if ($activeOnly) {
            $query->whereHas('feature', function ($q) {
                $q->where('status', 1);
            });
        }

        return $query->get()->groupBy('feature_id');
    }

    /**
     * Get feature IDs for multiple categories.
     * 
     * @param array $categoryIds
     * @param array $subcategoryIds
     * @return array
     */
    public function getFeatureIdsForCategories(array $categoryIds, array $subcategoryIds = []): array
    {
        $query = $this->whereIn('category_id', $categoryIds);

        if (!empty($subcategoryIds)) {
            $query->orWhereIn('sub_category_id', $subcategoryIds);
        }

        return $query->pluck('feature_id')->unique()->toArray();
    }

    /**
     * Get features for multiple categories.
     * 
     * @param array $categoryIds
     * @param array $subcategoryIds
     * @return Collection
     */
    public function getFeaturesForCategories(array $categoryIds, array $subcategoryIds = []): Collection
    {
        $featureIds = $this->getFeatureIdsForCategories($categoryIds, $subcategoryIds);

        if (empty($featureIds)) {
            return collect();
        }

        return Feature::whereIn('id', $featureIds)
            ->active()
            ->orderByName()
            ->with('activeFeatureValues')
            ->get();
    }

    /**
     * Copy mappings from one category to another.
     * 
     * @param int $sourceCategoryId
     * @param int $targetCategoryId
     * @param bool $includeSubcategories
     * @return int Number of mappings copied
     */
    public function copyMappings(int $sourceCategoryId, int $targetCategoryId, bool $includeSubcategories = false): int
    {
        $query = $this->where('category_id', $sourceCategoryId);

        if ($includeSubcategories) {
            $query->orWhere('sub_category_id', $sourceCategoryId);
        }

        $mappings = $query->get();
        $count = 0;

        if ($mappings->isNotEmpty()) {
            DB::transaction(function () use ($mappings, $targetCategoryId, &$count) {
                foreach ($mappings as $mapping) {
                    $newMapping = $mapping->replicate();
                    $newMapping->category_id = $targetCategoryId;
                    $newMapping->created_at = now();

                    // Check if mapping already exists
                    $exists = $this->where('feature_id', $newMapping->feature_id)
                        ->where('category_id', $targetCategoryId)
                        ->where(function ($q) use ($newMapping) {
                            $q->where('sub_category_id', $newMapping->sub_category_id)
                                ->orWhereNull('sub_category_id');
                        })
                        ->exists();

                    if (!$exists) {
                        $newMapping->save();
                        $count++;
                    }
                }
            });
        }

        return $count;
    }

    /**
     * Delete all mappings for a category.
     * 
     * @param int $categoryId
     * @return int Number of deleted mappings
     */
    public function deleteMappingsForCategory(int $categoryId): int
    {
        return $this->where('category_id', $categoryId)
            ->orWhere('sub_category_id', $categoryId)
            ->delete();
    }

    /**
     * Delete all mappings for a feature.
     * 
     * @param int $featureId
     * @return int Number of deleted mappings
     */
    public function deleteMappingsForFeature(int $featureId): int
    {
        return $this->where('feature_id', $featureId)->delete();
    }

    /**
     * Get category IDs with feature assignments grouped by category.
     * 
     * @return Collection
     */
    public function getCategoryAssignmentsGrouped(): Collection
    {
        return $this->select('category_id', 'sub_category_id', DB::raw('COUNT(*) as feature_count'))
            ->groupBy('category_id', 'sub_category_id')
            ->get()
            ->groupBy('category_id');
    }

    /**
     * Get features for a category with their values (optimized with eager loading).
     * 
     * @param int $categoryId
     * @param int|null $productId
     * @return Collection
     */
    public function getFeaturesForCategoryOptimized(int $categoryId, ?int $productId = null): Collection
    {
        $features = $this->select('features.*')
            ->join('features', 'features.id', '=', 'feature_category_mapping.feature_id')
            ->where(function ($query) use ($categoryId) {
                $query->where('feature_category_mapping.category_id', $categoryId)
                    ->orWhere('feature_category_mapping.sub_category_id', $categoryId);
            })
            ->where('features.status', 1)
            ->groupBy('features.id')
            ->orderBy('features.name', 'ASC')
            ->with('activeFeatureValues')
            ->get();

        if ($productId) {
            $valueModel = new ProductFeatureValue();
            $savedValues = $valueModel->getValuesForProduct($productId);

            $features->each(function ($feature) use ($savedValues) {
                $feature->saved_value = $savedValues[$feature->id] ?? '';
            });
        }

        return $features;
    }
}