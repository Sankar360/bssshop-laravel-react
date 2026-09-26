<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FeatureValue extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feature_values';

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
        'value',
        'sort_order',
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
        'feature_id' => 'integer',
        'sort_order' => 'integer',
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
        'sort_order' => 0,
    ];

    /**
     * The validation rules for the model.
     *
     * @var array
     */
    public static $rules = [
        'feature_id' => 'required|integer|exists:features,id',
        'value' => 'required|string|min:1|max:255',
        'sort_order' => 'nullable|integer',
        'status' => 'nullable|in:0,1',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the feature that owns the value.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    /**
     * Get the product feature values for this feature value.
     */
    public function productFeatureValues()
    {
        return $this->hasMany(ProductFeatureValue::class, 'value', 'id');
    }

    /**
     * Get the product variant values for this feature value.
     */
    public function productVariantValues()
    {
        return $this->hasMany(ProductVariantValue::class, 'value', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active values.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include inactive values.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope a query to filter by feature.
     */
    public function scopeByFeature($query, int $featureId)
    {
        return $query->where('feature_id', $featureId);
    }

    /**
     * Scope a query to order by sort order then value.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'ASC')
            ->orderBy('value', 'ASC');
    }

    /**
     * Scope a query to order by value.
     */
    public function scopeOrderByValue($query)
    {
        return $query->orderBy('value', 'ASC');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status ? 'Active' : 'Inactive';
    }

    /**
     * Get the formatted value attribute (with feature name).
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->relationLoaded('feature')) {
            return $this->feature->name . ': ' . $this->value;
        }
        return $this->value;
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get active values for a feature (ordered by sort_order).
     * 
     * @param int $featureId
     * @return array
     */
    public function getActiveValuesForFeature(int $featureId): array
    {
        return $this->byFeature($featureId)
            ->active()
            ->ordered()
            ->get()
            ->toArray();
    }

    /**
     * Get all values for a feature (ordered by sort_order).
     * 
     * @param int $featureId
     * @return array
     */
    public function getValuesForFeature(int $featureId): array
    {
        return $this->byFeature($featureId)
            ->ordered()
            ->get()
            ->toArray();
    }

    /**
     * Get values for a feature as a collection.
     * 
     * @param int $featureId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getValuesForFeatureCollection(int $featureId, bool $activeOnly = false): Collection
    {
        $query = $this->byFeature($featureId)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Check if a value already exists for a feature.
     * 
     * @param int $featureId
     * @param string $value
     * @param int|null $excludeId
     * @return bool
     */
    public function valueExists(int $featureId, string $value, ?int $excludeId = null): bool
    {
        $query = $this->where('feature_id', $featureId)
            ->where('value', $value);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get values as key-value pairs for dropdown.
     * 
     * @param int $featureId
     * @return array
     */
    public function getValuesForDropdown(int $featureId): array
    {
        $values = $this->getActiveValuesForFeature($featureId);
        $options = [];

        foreach ($values as $value) {
            $options[$value['id']] = $value['value'];
        }

        return $options;
    }

    /**
     * Get values for a feature with only id, value, sort_order.
     * 
     * @param int $featureId
     * @return array
     */
    public function getValuesForFeatureWithIds(int $featureId): array
    {
        return $this->select('id', 'value', 'sort_order')
            ->byFeature($featureId)
            ->active()
            ->ordered()
            ->get()
            ->toArray();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Create or update feature values with validation.
     * 
     * @param int $featureId
     * @param array $values
     * @return void
     * @throws ValidationException
     */
    public function syncFeatureValues(int $featureId, array $values): void
    {
        \DB::transaction(function () use ($featureId, $values) {
            // Delete existing values
            $this->where('feature_id', $featureId)->delete();

            foreach ($values as $index => $value) {
                $this->create([
                    'feature_id' => $featureId,
                    'value' => $value,
                    'sort_order' => $index + 1,
                    'status' => 1,
                ]);
            }
        });
    }

    /**
     * Update feature values (replace all).
     * 
     * @param int $featureId
     * @param array $values
     * @return void
     */
    public function updateFeatureValues(int $featureId, array $values): void
    {
        $this->syncFeatureValues($featureId, $values);
    }

    /**
     * Get values by feature with pagination.
     * 
     * @param int $featureId
     * @param int $perPage
     * @param bool $activeOnly
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedValues(int $featureId, int $perPage = 20, bool $activeOnly = false)
    {
        $query = $this->byFeature($featureId)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get values count for a feature.
     * 
     * @param int $featureId
     * @param bool $activeOnly
     * @return int
     */
    public function countValuesForFeature(int $featureId, bool $activeOnly = false): int
    {
        $query = $this->where('feature_id', $featureId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->count();
    }

    /**
     * Check if a value is being used by any product or variant.
     * 
     * @param int $valueId
     * @return bool
     */
    public function isValueInUse(int $valueId): bool
    {
        $value = $this->find($valueId);

        if (!$value) {
            return false;
        }

        // Check if used in product feature values
        if (ProductFeatureValue::where('value', $value->value)
            ->where('feature_id', $value->feature_id)
            ->exists()) {
            return true;
        }

        // Check if used in product variant values
        if (ProductVariantValue::where('value', $value->value)
            ->where('feature_id', $value->feature_id)
            ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Get values by feature with feature name included.
     * 
     * @param int $featureId
     * @param bool $activeOnly
     * @return array
     */
    public function getValuesWithFeatureName(int $featureId, bool $activeOnly = false): array
    {
        $values = $this->with('feature')
            ->byFeature($featureId);

        if ($activeOnly) {
            $values->active();
        }

        return $values->ordered()
            ->get()
            ->map(function ($value) {
                return [
                    'id' => $value->id,
                    'value' => $value->value,
                    'sort_order' => $value->sort_order,
                    'status' => $value->status,
                    'feature_name' => $value->feature->name ?? null,
                    'feature_input_type' => $value->feature->input_type ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Get values as a simple array for dropdown.
     * 
     * @param int $featureId
     * @param bool $activeOnly
     * @return array
     */
    public function getSimpleDropdownOptions(int $featureId, bool $activeOnly = true): array
    {
        $query = $this->byFeature($featureId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()
            ->pluck('value', 'id')
            ->toArray();
    }

    /**
     * Bulk update status for feature values.
     * 
     * @param array $valueIds
     * @param bool $status
     * @return int Number of affected rows
     */
    public function bulkUpdateStatus(array $valueIds, bool $status): int
    {
        return $this->whereIn('id', $valueIds)
            ->update(['status' => $status]);
    }

    /**
     * Bulk delete feature values.
     * 
     * @param array $valueIds
     * @return int Number of deleted rows
     */
    public function bulkDelete(array $valueIds): int
    {
        // Check if any values are in use before deleting
        $inUse = [];
        foreach ($valueIds as $id) {
            if ($this->isValueInUse($id)) {
                $inUse[] = $id;
            }
        }

        if (!empty($inUse)) {
            throw new \Exception('Cannot delete values that are in use: ' . implode(', ', $inUse));
        }

        return $this->whereIn('id', $valueIds)->delete();
    }

    /**
     * Get the next sort order for a feature.
     * 
     * @param int $featureId
     * @return int
     */
    public function getNextSortOrder(int $featureId): int
    {
        $max = $this->where('feature_id', $featureId)
            ->max('sort_order');

        return ($max ?? 0) + 1;
    }

    /**
     * Reorder values for a feature.
     * 
     * @param int $featureId
     * @param array $orderedIds Array of value IDs in desired order
     * @return bool
     */
    public function reorderValues(int $featureId, array $orderedIds): bool
    {
        return \DB::transaction(function () use ($featureId, $orderedIds) {
            foreach ($orderedIds as $index => $valueId) {
                $this->where('id', $valueId)
                    ->where('feature_id', $featureId)
                    ->update(['sort_order' => $index + 1]);
            }
            return true;
        });
    }

    /**
     * Get values by multiple feature IDs.
     * 
     * @param array $featureIds
     * @param bool $activeOnly
     * @return Collection
     */
    public function getValuesForMultipleFeatures(array $featureIds, bool $activeOnly = false): Collection
    {
        $query = $this->whereIn('feature_id', $featureIds);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get values grouped by feature.
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getValuesGroupedByFeature(bool $activeOnly = false): Collection
    {
        $query = $this->with('feature');

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()
            ->get()
            ->groupBy('feature_id')
            ->map(function ($values) {
                return [
                    'feature' => $values->first()->feature,
                    'values' => $values->pluck('value', 'id')->toArray(),
                ];
            });
    }

    /**
     * Validate model data.
     * 
     * @param array $data
     * @param int|null $excludeId
     * @return bool
     * @throws ValidationException
     */
    public function validateData(array $data, ?int $excludeId = null): bool
    {
        $validator = \Validator::make($data, self::$rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }
}