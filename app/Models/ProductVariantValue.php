<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProductVariantValue extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variant_values';

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
        'variant_id',
        'feature_id',
        'value',
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
        'variant_id' => 'integer',
        'feature_id' => 'integer',
        'value' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
     * The attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'display_value',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the variant that owns the value.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    /**
     * Get the feature that owns the value.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    /**
     * Get the feature value (for dropdown/select features).
     */
    public function featureValue(): BelongsTo
    {
        return $this->belongsTo(FeatureValue::class, 'value', 'id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the display value attribute.
     * If value is numeric and has a feature value, use the feature value name.
     * Otherwise, use the raw value.
     *
     * @return string
     */
    public function getDisplayValueAttribute(): string
    {
        // If value is numeric and has a feature value relationship
        if (is_numeric($this->value) && $this->relationLoaded('featureValue') && $this->featureValue) {
            return $this->featureValue->value ?? (string) $this->value;
        }

        // If value is numeric but feature value is not loaded, try to load it
        if (is_numeric($this->value) && $this->featureValue()->exists()) {
            $featureValue = $this->featureValue()->first();
            return $featureValue ? ($featureValue->value ?? (string) $this->value) : (string) $this->value;
        }

        return (string) $this->value;
    }

    /**
     * Get the feature name attribute (for convenience).
     *
     * @return string|null
     */
    public function getFeatureNameAttribute(): ?string
    {
        return $this->relationLoaded('feature') && $this->feature 
            ? $this->feature->name 
            : null;
    }

    /**
     * Get the feature input type attribute (for convenience).
     *
     * @return string|null
     */
    public function getInputTypeAttribute(): ?string
    {
        return $this->relationLoaded('feature') && $this->feature 
            ? $this->feature->input_type 
            : null;
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to filter by variant.
     */
    public function scopeByVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope a query to filter by feature.
     */
    public function scopeByFeature($query, int $featureId)
    {
        return $query->where('feature_id', $featureId);
    }

    /**
     * Scope a query to order by feature.
     */
    public function scopeOrderByFeature($query)
    {
        return $query->orderBy('feature_id', 'ASC');
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get all values for a specific variant with feature and feature value data.
     * 
     * @param int $variantId
     * @return array
     */
    public function getVariantValues(int $variantId): array
    {
        $results = $this->with(['feature', 'featureValue'])
            ->where('variant_id', $variantId)
            ->get();

        if ($results->isEmpty()) {
            return [];
        }

        // Process results and add display value
        $processed = $results->map(function ($item) {
            $data = $item->toArray();
            
            // Add display value using the accessor
            $data['display_value'] = $item->display_value;
            
            // Add feature name and input type if not already in array
            if (!isset($data['feature_name']) && $item->feature) {
                $data['feature_name'] = $item->feature->name;
            }
            if (!isset($data['input_type']) && $item->feature) {
                $data['input_type'] = $item->feature->input_type;
            }
            
            // Add option value if available
            if ($item->featureValue) {
                $data['option_value'] = $item->featureValue->value;
            }

            return $data;
        });

        return $processed->toArray();
    }

    /**
     * Save variant values (delete existing, insert new).
     * 
     * @param int $variantId
     * @param array $values Array of [feature_id => value]
     * @return void
     */
    public function saveVariantValues(int $variantId, array $values): void
    {
        // Start a database transaction for data consistency
        \DB::transaction(function () use ($variantId, $values) {
            // Delete existing values
            $this->where('variant_id', $variantId)->delete();
            
            // Prepare new values for insertion
            $rows = [];
            foreach ($values as $featureId => $value) {
                $trimmedValue = trim((string) $value);
                if (!empty($trimmedValue)) {
                    $rows[] = [
                        'variant_id' => $variantId,
                        'feature_id' => (int) $featureId,
                        'value' => $trimmedValue,
                    ];
                }
            }
            
            // Bulk insert new values
            if (!empty($rows)) {
                $this->insert($rows);
            }
        });
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get values for a variant with eager loaded relationships.
     * 
     * @param int $variantId
     * @return Collection
     */
    public function getVariantValuesWithRelations(int $variantId): Collection
    {
        return $this->with(['feature', 'featureValue'])
            ->where('variant_id', $variantId)
            ->get();
    }

    /**
     * Check if a variant has a specific value for a feature.
     * 
     * @param int $variantId
     * @param int $featureId
     * @param string $value
     * @return bool
     */
    public function hasValue(int $variantId, int $featureId, string $value): bool
    {
        return $this->where('variant_id', $variantId)
            ->where('feature_id', $featureId)
            ->where('value', $value)
            ->exists();
    }

    /**
     * Get all unique values for a specific feature across all variants.
     * 
     * @param int $featureId
     * @return Collection
     */
    public function getUniqueValuesForFeature(int $featureId): Collection
    {
        return $this->where('feature_id', $featureId)
            ->select('value')
            ->distinct()
            ->with('featureValue')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->value,
                    'display_value' => $item->display_value,
                ];
            });
    }

    /**
     * Delete all values for a variant.
     * 
     * @param int $variantId
     * @return int Number of deleted records
     */
    public function deleteVariantValues(int $variantId): int
    {
        return $this->where('variant_id', $variantId)->delete();
    }

    /**
     * Delete values for multiple variants.
     * 
     * @param array $variantIds
     * @return int Number of deleted records
     */
    public function deleteVariantValuesMultiple(array $variantIds): int
    {
        return $this->whereIn('variant_id', $variantIds)->delete();
    }

    /**
     * Get variant values grouped by feature.
     * 
     * @param int $variantId
     * @return array
     */
    public function getVariantValuesGroupedByFeature(int $variantId): array
    {
        $values = $this->with(['feature', 'featureValue'])
            ->where('variant_id', $variantId)
            ->get();

        $grouped = [];
        foreach ($values as $value) {
            $featureName = $value->feature->name ?? 'Unknown';
            $grouped[$featureName][] = [
                'feature_id' => $value->feature_id,
                'value' => $value->value,
                'display_value' => $value->display_value,
            ];
        }

        return $grouped;
    }

    /**
     * Update or create variant values.
     * 
     * @param int $variantId
     * @param array $values Array of [feature_id => value]
     * @return void
     */
    public function updateOrCreateVariantValues(int $variantId, array $values): void
    {
        \DB::transaction(function () use ($variantId, $values) {
            foreach ($values as $featureId => $value) {
                $trimmedValue = trim((string) $value);
                if (!empty($trimmedValue)) {
                    $this->updateOrCreate(
                        [
                            'variant_id' => $variantId,
                            'feature_id' => (int) $featureId,
                        ],
                        [
                            'value' => $trimmedValue,
                        ]
                    );
                } else {
                    // If value is empty, delete the record
                    $this->where('variant_id', $variantId)
                        ->where('feature_id', (int) $featureId)
                        ->delete();
                }
            }
        });
    }

    /**
     * Get variant values as a key-value array (feature_name => display_value).
     * 
     * @param int $variantId
     * @return array
     */
    public function getVariantValuesAsKeyValue(int $variantId): array
    {
        $values = $this->with(['feature', 'featureValue'])
            ->where('variant_id', $variantId)
            ->get();

        $result = [];
        foreach ($values as $value) {
            $key = $value->feature->name ?? 'feature_' . $value->feature_id;
            $result[$key] = $value->display_value;
        }

        return $result;
    }

    /**
     * Check if any variant has a specific value.
     * 
     * @param int $featureId
     * @param string $value
     * @return bool
     */
    public function valueExistsForFeature(int $featureId, string $value): bool
    {
        return $this->where('feature_id', $featureId)
            ->where('value', $value)
            ->exists();
    }

    /**
     * Get all variants that have a specific value.
     * 
     * @param int $featureId
     * @param string $value
     * @return Collection
     */
    public function getVariantsByValue(int $featureId, string $value): Collection
    {
        return $this->where('feature_id', $featureId)
            ->where('value', $value)
            ->with('variant')
            ->get()
            ->pluck('variant')
            ->filter();
    }

    /**
     * Count values for a variant.
     * 
     * @param int $variantId
     * @return int
     */
    public function countVariantValues(int $variantId): int
    {
        return $this->where('variant_id', $variantId)->count();
    }

    /**
     * Get values for multiple variants at once (for optimization).
     * 
     * @param array $variantIds
     * @return Collection
     */
    public function getValuesForVariants(array $variantIds): Collection
    {
        return $this->with(['feature', 'featureValue'])
            ->whereIn('variant_id', $variantIds)
            ->get()
            ->groupBy('variant_id');
    }
}