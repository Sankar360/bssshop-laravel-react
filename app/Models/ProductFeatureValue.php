<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductFeatureValue extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_feature_values';

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
        'product_id',
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
        'product_id' => 'integer',
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

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the product that owns the feature value.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the feature that owns the value.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to filter by product.
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to filter by feature.
     */
    public function scopeByFeature($query, int $featureId)
    {
        return $query->where('feature_id', $featureId);
    }

    /**
     * Scope a query to order by feature name.
     */
    public function scopeOrderByFeatureName($query)
    {
        return $query->orderBy('features.name', 'ASC');
    }

    /**
     * Scope a query to only include active features.
     */
    public function scopeWithActiveFeatures($query)
    {
        return $query->whereHas('feature', function ($q) {
            $q->where('status', 1);
        });
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Save product feature values (replace all).
     * 
     * @param int $productId
     * @param array $features Array of feature_id => value pairs
     * @return void
     */
    public function saveProductFeatures(int $productId, array $features): void
    {
        DB::transaction(function () use ($productId, $features) {
            // Delete existing values
            $this->where('product_id', $productId)->delete();

            $rows = [];

            foreach ($features as $featureId => $values) {
                // Convert single value to array
                if (!is_array($values)) {
                    $values = [$values];
                }

                foreach ($values as $value) {
                    if ($value === '' || $value === null) {
                        continue;
                    }

                    $rows[] = [
                        'product_id' => $productId,
                        'feature_id' => (int) $featureId,
                        'value' => (string) $value,
                    ];
                }
            }

            if (!empty($rows)) {
                $this->insert($rows);
            }
        });
    }

    /**
     * Save product feature values with validation against allowed values.
     * 
     * @param int $productId
     * @param array $features Array of feature_id => value pairs
     * @param array $allowedFeatureIds Array of feature IDs allowed for this product's category
     * @return array Returns array of saved feature IDs
     */
    public function saveProductFeaturesWithValidation(int $productId, array $features, array $allowedFeatureIds = []): array
    {
        $savedFeatureIds = [];

        DB::transaction(function () use ($productId, $features, $allowedFeatureIds, &$savedFeatureIds) {
            // Delete existing values
            $this->where('product_id', $productId)->delete();

            // Filter features based on allowed IDs
            if (!empty($allowedFeatureIds)) {
                $features = array_intersect_key($features, array_flip($allowedFeatureIds));
            }

            // Insert new values
            $rows = [];

            foreach ($features as $featureId => $value) {
                // Skip empty values
                if ($value === '' || $value === null || $value === []) {
                    continue;
                }

                // Handle multiple values (checkbox)
                $finalValue = is_array($value) ? implode(', ', array_filter($value)) : $value;

                if ($finalValue === '') {
                    continue;
                }

                $rows[] = [
                    'product_id' => $productId,
                    'feature_id' => (int) $featureId,
                    'value' => $finalValue,
                ];

                $savedFeatureIds[] = (int) $featureId;
            }

            if (!empty($rows)) {
                $this->insert($rows);
            }
        });

        return $savedFeatureIds;
    }

    /**
     * Get feature values for a product as [feature_id => value] map.
     * 
     * @param int $productId
     * @return array
     */
    public function getValuesForProduct(int $productId): array
    {
        $rows = $this->where('product_id', $productId)->get();
        $map = [];

        foreach ($rows as $row) {
            if (!isset($map[$row->feature_id])) {
                $map[$row->feature_id] = [];
            }
            $map[$row->feature_id][] = $row->value;
        }

        return $map;
    }

    /**
     * Get feature values for a product with feature details.
     * 
     * @param int $productId
     * @return array
     */
    public function getProductFeatureValues(int $productId): array
    {
        return $this->with('feature')
            ->where('product_id', $productId)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['name'] = $item->feature?->name;
                $data['input_type'] = $item->feature?->input_type;
                $data['options'] = $item->feature?->options;
                return $data;
            })
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    /**
     * Get product specifications with feature names for frontend.
     * 
     * @param int $productId
     * @return array
     */
    public function getSpecificationsForProduct(int $productId): array
    {
        $results = $this->with('feature')
            ->where('product_id', $productId)
            ->whereHas('feature', function ($query) {
                $query->where('status', 1);
            })
            ->get()
            ->map(function ($item) {
                $data = [
                    'name' => $item->feature?->name,
                    'input_type' => $item->feature?->input_type,
                    'value' => $item->value,
                ];

                // For color features, add color preview data
                if ($item->feature?->input_type === 'color' && !empty($item->value)) {
                    $data['color_code'] = $item->value;
                    $data['display_value'] = '<span class="color-swatch" style="display:inline-block;width:20px;height:20px;border-radius:4px;background:' . $item->value . ';border:1px solid #ddd;vertical-align:middle;margin-right:8px;"></span> ' . $item->value;
                }

                return $data;
            })
            ->sortBy('name')
            ->values()
            ->toArray();

        return $results;
    }

    /**
     * Get product features with full details including options and saved values.
     * 
     * @param int $productId
     * @return array
     */
    public function getProductFeaturesWithDetails(int $productId): array
    {
        $savedValues = $this->getValuesForProduct($productId);
        
        // Get features with their values
        $featureValues = $this->with('feature')
            ->where('product_id', $productId)
            ->whereHas('feature', function ($query) {
                $query->where('status', 1);
            })
            ->get();

        $featureModel = new Feature();
        $featureValueModel = new FeatureValue();

        $features = [];
        
        foreach ($featureValues as $fv) {
            $feature = $fv->feature;
            if (!$feature) {
                continue;
            }

            $featureData = $feature->toArray();
            $featureData['saved_value'] = $savedValues[$feature->id] ?? '';

            // Get options from feature_values table first
            $featureValuesOptions = $featureValueModel->getActiveValuesForFeature($feature->id);
            if (!empty($featureValuesOptions)) {
                $featureData['options_array'] = array_column($featureValuesOptions, 'value');
                $featureData['options_full'] = $featureValuesOptions;
            } else {
                // Fallback to options column
                $featureData['options_array'] = $featureModel->getOptionsArray($feature);
                $featureData['options_full'] = [];
            }

            $features[] = $featureData;
        }

        // Sort by name
        usort($features, function ($a, $b) {
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });

        return $features;
    }

    /**
     * Get product features with values formatted for display.
     * 
     * @param int $productId
     * @return array
     */
    public function getFormattedProductFeatures(int $productId): array
    {
        $features = $this->getProductFeatureValues($productId);
        $formatted = [];

        foreach ($features as $feature) {
            $formatted[] = [
                'id' => $feature['id'],
                'feature_id' => $feature['feature_id'],
                'name' => $feature['name'],
                'value' => $feature['value'],
                'input_type' => $feature['input_type'],
                'display_value' => $this->formatValueForDisplay($feature['value'], $feature['input_type'] ?? 'text'),
            ];
        }

        return $formatted;
    }

    /**
     * Format value based on input type for display.
     * 
     * @param string $value
     * @param string $inputType
     * @return string
     */
    private function formatValueForDisplay(string $value, string $inputType): string
    {
        if ($inputType === 'color') {
            return '<span class="color-swatch" style="display:inline-block;width:20px;height:20px;border-radius:4px;background:' . $value . ';border:1px solid #ddd;vertical-align:middle;margin-right:8px;"></span> ' . $value;
        }
        return $value;
    }

    /**
     * Delete all feature values for a product.
     * 
     * @param int $productId
     * @return bool
     */
    public function deleteProductFeatures(int $productId): bool
    {
        return (bool) $this->where('product_id', $productId)->delete();
    }

    /**
     * Get feature count for a product.
     * 
     * @param int $productId
     * @return int
     */
    public function getFeatureCountForProduct(int $productId): int
    {
        return $this->where('product_id', $productId)->count();
    }

    /**
     * Get product with all feature values (for frontend display).
     * 
     * @param int $productId
     * @return array
     */
    public function getProductWithFeatures(int $productId): array
    {
        $product = Product::find($productId);

        if (!$product) {
            return [];
        }

        $productData = $product->toArray();
        $productData['features'] = $this->getFormattedProductFeatures($productId);
        $productData['specifications'] = $this->getSpecificationsForProduct($productId);

        return $productData;
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get feature values as key-value pairs (feature_name => value).
     * 
     * @param int $productId
     * @return array
     */
    public function getFeatureValuesAsKeyValue(int $productId): array
    {
        $values = $this->with('feature')
            ->where('product_id', $productId)
            ->get();

        $result = [];
        foreach ($values as $value) {
            $key = $value->feature?->name ?? 'feature_' . $value->feature_id;
            $result[$key] = $value->value;
        }

        return $result;
    }

    /**
     * Get features grouped by feature ID.
     * 
     * @param int $productId
     * @return Collection
     */
    public function getFeaturesGrouped(int $productId): Collection
    {
        return $this->with('feature')
            ->where('product_id', $productId)
            ->get()
            ->groupBy('feature_id');
    }

    /**
     * Update or create feature values for a product.
     * 
     * @param int $productId
     * @param array $features Array of feature_id => value pairs
     * @return void
     */
    public function updateOrCreateFeatures(int $productId, array $features): void
    {
        DB::transaction(function () use ($productId, $features) {
            foreach ($features as $featureId => $value) {
                if ($value === '' || $value === null) {
                    // Delete if value is empty
                    $this->where('product_id', $productId)
                        ->where('feature_id', (int) $featureId)
                        ->delete();
                } else {
                    $this->updateOrCreate(
                        [
                            'product_id' => $productId,
                            'feature_id' => (int) $featureId,
                        ],
                        [
                            'value' => (string) $value,
                        ]
                    );
                }
            }
        });
    }

    /**
     * Get products by feature value.
     * 
     * @param int $featureId
     * @param string $value
     * @return Collection
     */
    public function getProductsByFeatureValue(int $featureId, string $value): Collection
    {
        return $this->where('feature_id', $featureId)
            ->where('value', $value)
            ->with('product')
            ->get()
            ->pluck('product')
            ->filter();
    }

    /**
     * Get featured values for multiple products (for listing pages).
     * 
     * @param array $productIds
     * @return Collection
     */
    public function getFeaturesForProducts(array $productIds): Collection
    {
        return $this->with('feature')
            ->whereIn('product_id', $productIds)
            ->get()
            ->groupBy('product_id');
    }

    /**
     * Check if a product has a specific feature.
     * 
     * @param int $productId
     * @param int $featureId
     * @return bool
     */
    public function productHasFeature(int $productId, int $featureId): bool
    {
        return $this->where('product_id', $productId)
            ->where('feature_id', $featureId)
            ->exists();
    }

    /**
     * Get distinct values for a feature across all products.
     * 
     * @param int $featureId
     * @return Collection
     */
    public function getDistinctValuesForFeature(int $featureId): Collection
    {
        return $this->where('feature_id', $featureId)
            ->select('value')
            ->distinct()
            ->get()
            ->pluck('value');
    }

    /**
     * Get value count for a feature across all products.
     * 
     * @param int $featureId
     * @param string $value
     * @return int
     */
    public function getFeatureValueCount(int $featureId, string $value): int
    {
        return $this->where('feature_id', $featureId)
            ->where('value', $value)
            ->count();
    }
}