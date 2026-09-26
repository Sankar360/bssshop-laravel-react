<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Feature extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'features';

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
        'name',
        'input_type',
        'options',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 1,
        'input_type' => 'text',
    ];

    // ==================== CONSTANTS ====================

    /**
     * Input type constants.
     */
    const INPUT_TYPE_TEXT = 'text';
    const INPUT_TYPE_DROPDOWN = 'dropdown';
    const INPUT_TYPE_CHECKBOX = 'checkbox';
    const INPUT_TYPE_COLOR = 'color';

    /**
     * Get all available input types.
     *
     * @return array
     */
    public static function getInputTypes(): array
    {
        return [
            self::INPUT_TYPE_TEXT,
            self::INPUT_TYPE_DROPDOWN,
            self::INPUT_TYPE_CHECKBOX,
            self::INPUT_TYPE_COLOR,
        ];
    }

    /**
     * Get input types with labels for display.
     *
     * @return array
     */
    public static function getInputTypeLabels(): array
    {
        return [
            self::INPUT_TYPE_TEXT => 'Text',
            self::INPUT_TYPE_DROPDOWN => 'Dropdown',
            self::INPUT_TYPE_CHECKBOX => 'Checkbox',
            self::INPUT_TYPE_COLOR => 'Color',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the feature values for this feature.
     */
    public function featureValues(): HasMany
    {
        return $this->hasMany(FeatureValue::class, 'feature_id', 'id');
    }

    /**
     * Get the active feature values for this feature.
     */
    public function activeFeatureValues(): HasMany
    {
        return $this->featureValues()->where('status', 1);
    }

    /**
     * Get the product feature values for this feature.
     */
    public function productFeatureValues(): HasMany
    {
        return $this->hasMany(ProductFeatureValue::class, 'feature_id', 'id');
    }

    /**
     * Get the product variant values for this feature.
     */
    public function productVariantValues(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class, 'feature_id', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active features.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to order by name.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('name', 'ASC');
    }

    /**
     * Scope a query to filter by input type.
     */
    public function scopeOfType($query, string $inputType)
    {
        return $query->where('input_type', $inputType);
    }

    /**
     * Scope a query to only include features with options (dropdown, checkbox, color).
     */
    public function scopeWithOptions($query)
    {
        return $query->whereIn('input_type', [
            self::INPUT_TYPE_DROPDOWN,
            self::INPUT_TYPE_CHECKBOX,
            self::INPUT_TYPE_COLOR,
        ]);
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the input type label attribute.
     */
    public function getInputTypeLabelAttribute(): string
    {
        return self::getInputTypeLabels()[$this->input_type] ?? ucfirst($this->input_type);
    }

    /**
     * Get the status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status ? 'Active' : 'Inactive';
    }

    /**
     * Get the options as an array attribute.
     */
    public function getOptionsArrayAttribute(): array
    {
        return $this->getOptionsArray();
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get active features only.
     * 
     * @return Collection
     */
    public function getActiveFeatures(): Collection
    {
        return $this->active()
            ->orderByName()
            ->get();
    }

    /**
     * Parse options string into array.
     * Tries feature_values table first, then falls back to options column.
     * 
     * @param array|Feature $feature Feature data or model
     * @return array
     */
    public function getOptionsArray($feature): array
{
    $featureId = is_array($feature) ? ($feature['id'] ?? null) : $feature->id;
    $rawOptions = is_array($feature) ? ($feature['options'] ?? '') : $feature->options;

    // 1. Prefer feature_values table
    if ($featureId) {
        $values = $this->getFeatureValuesArray($featureId);
        if (!empty($values)) {
            return $values;
        }
    }

    // 2. Fall back to comma-separated options column
    if (empty($rawOptions)) {
        return [];
    }

    return array_map('trim', explode(',', $rawOptions));
}

    /**
     * Get feature values as array from feature_values table.
     * 
     * @param int $featureId
     * @return array
     */
    public function getFeatureValuesArray(int $featureId): array
{
    $values = self::query()                          // ✅ fresh builder
        ->find($featureId)                            // ✅ the feature
        ?->featureValues()                            // ✅ its values
        ->where('status', 1)
        ->orderBy('sort_order', 'ASC')
        ->get() ?? collect();

    if ($values->isEmpty()) {
        return [];
    }

    return $values->map(function ($value) {
        return [
            'id' => $value->id,                       // ✅ feature_values.id
            'value' => $value->value,                 // ✅ display text (e.g., "64")
        ];
    })->toArray();
}

    /**
     * Get feature with parsed options.
     * 
     * @param int $featureId
     * @return array|null
     */
    public function getFeatureWithOptions(int $featureId): ?array
    {
        $feature = $this->with('activeFeatureValues')
            ->active()
            ->find($featureId);

        if (!$feature) {
            return null;
        }

        $result = $feature->toArray();
        $values = $feature->activeFeatureValues->toArray();
        
        $result['values'] = $values;
        $result['options_array'] = array_column($values, 'value');

        return $result;
    }

    /**
     * Get multiple features with their options.
     * 
     * @param array $featureIds
     * @return array
     */
    public function getFeaturesWithOptions(array $featureIds): array
    {
        if (empty($featureIds)) {
            return [];
        }

        $features = $this->with('activeFeatureValues')
            ->whereIn('id', $featureIds)
            ->active()
            ->get();

        $result = [];

        foreach ($features as $feature) {
            $data = $feature->toArray();
            $values = $feature->activeFeatureValues->toArray();
            
            $data['values'] = $values;
            $data['options_array'] = array_column($values, 'value');

            $result[] = $data;
        }

        return $result;
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get all features with their option values as an associative array.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getFeaturesWithOptionsArray(bool $activeOnly = true): array
    {
        $query = $this->with('activeFeatureValues');

        if ($activeOnly) {
            $query->active();
        }

        $features = $query->orderByName()->get();
        $result = [];

        foreach ($features as $feature) {
            $values = $feature->activeFeatureValues->toArray();
            $result[$feature->id] = [
                'id' => $feature->id,
                'name' => $feature->name,
                'input_type' => $feature->input_type,
                'options' => array_column($values, 'value'),
                'options_full' => $values,
            ];
        }

        return $result;
    }

    /**
     * Get features by category (based on product category features).
     * 
     * @param int $categoryId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getFeaturesByCategory(int $categoryId, bool $activeOnly = true): Collection
    {
        // This assumes you have a category_features table
        // If not, you can modify this based on your actual structure
        $query = $this->whereHas('productFeatureValues', function ($q) use ($categoryId) {
            $q->whereHas('product', function ($q2) use ($categoryId) {
                $q2->where('category_id', $categoryId);
            });
        });

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderByName()->get();
    }

    /**
     * Check if feature has options.
     * 
     * @return bool
     */
    public function hasOptions(): bool
    {
        return in_array($this->input_type, [
            self::INPUT_TYPE_DROPDOWN,
            self::INPUT_TYPE_CHECKBOX,
            self::INPUT_TYPE_COLOR,
        ]);
    }

    /**
     * Check if feature has values in feature_values table.
     * 
     * @param int $featureId
     * @return bool
     */
    public function hasFeatureValues(int $featureId): bool
    {
        return $this->featureValues()->where('feature_id', $featureId)->exists();
    }

    /**
     * Get feature value count.
     * 
     * @param int $featureId
     * @return int
     */
    public function getFeatureValueCount(int $featureId): int
    {
        return $this->featureValues()->where('feature_id', $featureId)->count();
    }

    /**
     * Get features for filter dropdown (for product filters).
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getFeaturesForFilter(bool $activeOnly = true): array
    {
        $query = $this->with('activeFeatureValues');

        if ($activeOnly) {
            $query->active();
        }

        $features = $query->orderByName()->get();
        $result = [];

        foreach ($features as $feature) {
            $result[] = [
                'id' => $feature->id,
                'name' => $feature->name,
                'input_type' => $feature->input_type,
                'values' => $feature->activeFeatureValues->map(function ($value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'color' => $value->color ?? null,
                    ];
                })->toArray(),
            ];
        }

        return $result;
    }

    /**
     * Create feature with values.
     * 
     * @param array $featureData
     * @param array $values
     * @return Feature|null
     */
    public function createWithValues(array $featureData, array $values = []): ?Feature
    {
        return \DB::transaction(function () use ($featureData, $values) {
            $feature = $this->create($featureData);

            if (!empty($values) && $feature->hasOptions()) {
                foreach ($values as $index => $value) {
                    $feature->featureValues()->create([
                        'value' => $value,
                        'sort_order' => $index,
                        'status' => 1,
                    ]);
                }
            }

            return $feature;
        });
    }

    /**
     * Update feature with values.
     * 
     * @param int $featureId
     * @param array $featureData
     * @param array $values
     * @return bool
     */
    public function updateWithValues(int $featureId, array $featureData, array $values = []): bool
    {
        $feature = $this->find($featureId);

        if (!$feature) {
            return false;
        }

        return \DB::transaction(function () use ($feature, $featureData, $values) {
            $feature->update($featureData);

            if ($feature->hasOptions()) {
                // Delete existing values
                $feature->featureValues()->delete();

                // Create new values
                foreach ($values as $index => $value) {
                    $feature->featureValues()->create([
                        'value' => $value,
                        'sort_order' => $index,
                        'status' => 1,
                    ]);
                }
            }

            return true;
        });
    }

    /**
     * Delete feature with its values.
     * 
     * @param int $featureId
     * @return bool
     */
    public function deleteWithValues(int $featureId): bool
    {
        $feature = $this->find($featureId);

        if (!$feature) {
            return false;
        }

        return \DB::transaction(function () use ($feature) {
            // Delete feature values
            $feature->featureValues()->delete();
            
            // Delete product feature values
            $feature->productFeatureValues()->delete();
            
            // Delete product variant values
            $feature->productVariantValues()->delete();
            
            // Delete the feature
            return $feature->delete();
        });
    }

    /**
     * Get features that are used in products.
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getUsedFeatures(bool $activeOnly = true): Collection
    {
        $query = $this->whereHas('productFeatureValues');

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderByName()->get();
    }

    /**
     * Get features that are used in variants.
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getUsedVariantFeatures(bool $activeOnly = true): Collection
    {
        $query = $this->whereHas('productVariantValues');

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderByName()->get();
    }

    /**
     * Sync feature values with existing feature_values table.
     * 
     * @param int $featureId
     * @param array $values
     * @return void
     */
    public function syncFeatureValues(int $featureId, array $values): void
    {
        $feature = $this->find($featureId);
        
        if (!$feature || !$feature->hasOptions()) {
            return;
        }

        \DB::transaction(function () use ($feature, $values) {
            // Delete existing values not in the new list
            $existingIds = $feature->featureValues()->pluck('id')->toArray();
            $newValues = array_column($values, 'value');
            
            $feature->featureValues()->whereNotIn('value', $newValues)->delete();

            // Update or create new values
            foreach ($values as $index => $valueData) {
                $feature->featureValues()->updateOrCreate(
                    ['value' => $valueData['value']],
                    [
                        'sort_order' => $index,
                        'status' => $valueData['status'] ?? 1,
                    ]
                );
            }
        });
    }
}