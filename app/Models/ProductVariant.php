<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class ProductVariant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variants';

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
        'sku',
        'slug',
        'price',
        'sale_price',
        'discount',
        'rating',
        'stock',
        'status',
        'variant_name',
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
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'rating' => 'decimal:2',
        'stock' => 'integer',
        'status' => 'boolean',
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
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 1,
        'stock' => 0,
        'rating' => 0,
        'discount' => 0,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the values for this variant.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class, 'variant_id', 'id');
    }

    /**
     * Get the images for this variant.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductVariantImage::class, 'variant_id', 'id');
    }

    /**
     * Get the primary image for this variant.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductVariantImage::class, 'variant_id', 'id')
            ->where('is_primary', true)
            ->orderBy('sort_order', 'ASC');
    }

    /**
     * Get the order items for this variant.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active variants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include in-stock variants.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope a query to order by price ascending.
     */
    public function scopePriceAsc($query)
    {
        return $query->orderBy('price', 'ASC');
    }

    /**
     * Scope a query to order by price descending.
     */
    public function scopePriceDesc($query)
    {
        return $query->orderBy('price', 'DESC');
    }

    /**
     * Scope a query to filter by product.
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get all variants for a product with their values.
     * 
     * @param int $productId
     * @return Collection
     */
    public function getVariantsWithValues(int $productId): Collection
    {
        $variants = $this->where('product_id', $productId)
            ->active()
            ->priceAsc()
            ->get();

        // Load values for all variants (eager loading)
        $variants->load('values');

        return $variants;
    }

    /**
     * Get a specific variant with its values.
     * 
     * @param int $variantId
     * @return array|null
     */
    public function getVariantWithValues(int $variantId): ?array
    {
        $variant = $this->with('values')->find($variantId);

        if (!$variant) {
            return null;
        }

        return $variant->toArray();
    }

    /**
     * Get a specific variant with its values and images.
     * 
     * @param int $variantId
     * @return array|null
     */
    public function getVariantWithImages(int $variantId): ?array
    {
        $variant = $this->with(['values', 'images'])->find($variantId);

        if (!$variant) {
            return null;
        }

        return $variant->toArray();
    }

    /**
     * Get all variants for a product with values and images.
     * Also generates variant_name from values.
     * 
     * @param int $productId
     * @return array
     */
    public function getVariantsWithValuesAndImages(int $productId): array
    {
        $variants = $this->where('product_id', $productId)
            ->active()
            ->priceAsc()
            ->with(['values', 'images'])
            ->get();

        $result = [];

        foreach ($variants as $variant) {
            $data = $variant->toArray();
            
            // Build variant name from values
            $variantParts = [];
            if (!empty($data['values'])) {
                foreach ($data['values'] as $value) {
                    $variantParts[] = $value['value'] ?? $value['value_id'] ?? '';
                }
            }
            $data['variant_name'] = implode(' / ', array_filter($variantParts));
            
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Get variant by slug with values and images.
     * 
     * @param string $slug
     * @return array|null
     */
    public function getVariantBySlug(string $slug): ?array
    {
        $variant = $this->where('slug', $slug)
            ->active()
            ->with(['values', 'images'])
            ->first();

        if (!$variant) {
            return null;
        }

        $data = $variant->toArray();

        // Build variant name from values
        $variantParts = [];
        if (!empty($data['values'])) {
            foreach ($data['values'] as $value) {
                $variantParts[] = $value['value'] ?? $value['value_id'] ?? '';
            }
        }
        $data['variant_name'] = implode(' / ', array_filter($variantParts));

        return $data;
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get cheapest in-stock variant for a product.
     * 
     * @param int $productId
     * @return ProductVariant|null
     */
    public function getCheapestVariant(int $productId): ?ProductVariant
    {
        return $this->where('product_id', $productId)
            ->active()
            ->inStock()
            ->priceAsc()
            ->first();
    }

    /**
     * Get most expensive in-stock variant for a product.
     * 
     * @param int $productId
     * @return ProductVariant|null
     */
    public function getMostExpensiveVariant(int $productId): ?ProductVariant
    {
        return $this->where('product_id', $productId)
            ->active()
            ->inStock()
            ->priceDesc()
            ->first();
    }

    /**
     * Get variants with available stock for a product.
     * 
     * @param int $productId
     * @return Collection
     */
    public function getAvailableVariants(int $productId): Collection
    {
        return $this->where('product_id', $productId)
            ->active()
            ->inStock()
            ->priceAsc()
            ->get();
    }

    /**
     * Get price range (min and max) for a product's variants.
     * 
     * @param int $productId
     * @return array|null ['min' => float, 'max' => float]
     */
    public function getPriceRange(int $productId): ?array
    {
        $range = $this->where('product_id', $productId)
            ->active()
            ->inStock()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        if (!$range || $range->min_price === null) {
            return null;
        }

        return [
            'min' => (float) $range->min_price,
            'max' => (float) $range->max_price,
        ];
    }

    /**
     * Check if a product has variants.
     * 
     * @param int $productId
     * @return bool
     */
    public function hasVariants(int $productId): bool
    {
        return $this->where('product_id', $productId)
            ->active()
            ->exists();
    }

    /**
     * Count active variants for a product.
     * 
     * @param int $productId
     * @return int
     */
    public function countVariants(int $productId): int
    {
        return $this->where('product_id', $productId)
            ->active()
            ->count();
    }

    /**
     * Get variant by SKU.
     * 
     * @param string $sku
     * @return ProductVariant|null
     */
    public function getVariantBySku(string $sku): ?ProductVariant
    {
        return $this->where('sku', $sku)->first();
    }

    /**
     * Update stock for a variant.
     * 
     * @param int $variantId
     * @param int $quantity
     * @return bool
     */
    public function updateStock(int $variantId, int $quantity): bool
    {
        return (bool) $this->where('id', $variantId)
            ->update(['stock' => $quantity]);
    }

    /**
     * Decrease stock for a variant (when an order is placed).
     * 
     * @param int $variantId
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock(int $variantId, int $quantity): bool
    {
        $variant = $this->find($variantId);
        
        if (!$variant || $variant->stock < $quantity) {
            return false;
        }

        return (bool) $this->where('id', $variantId)
            ->update(['stock' => $variant->stock - $quantity]);
    }

    /**
     * Increase stock for a variant (when an order is cancelled/returned).
     * 
     * @param int $variantId
     * @param int $quantity
     * @return bool
     */
    public function increaseStock(int $variantId, int $quantity): bool
    {
        $variant = $this->find($variantId);
        
        if (!$variant) {
            return false;
        }

        return (bool) $this->where('id', $variantId)
            ->update(['stock' => $variant->stock + $quantity]);
    }

    /**
     * Get variant options for a product (for dropdowns).
     * Returns formatted values by feature.
     * 
     * @param int $productId
     * @return array
     */
    public function getVariantOptions(int $productId): array
    {
        $variants = $this->where('product_id', $productId)
            ->active()
            ->with(['values' => function ($query) {
                $query->with('feature');
            }])
            ->get();

        $options = [];

        foreach ($variants as $variant) {
            foreach ($variant->values as $value) {
                $featureName = $value->feature->name ?? 'Unknown';
                if (!isset($options[$featureName])) {
                    $options[$featureName] = [];
                }
                if (!in_array($value->value, $options[$featureName])) {
                    $options[$featureName][] = $value->value;
                }
            }
        }

        return $options;
    }

    /**
     * Check if a specific combination of values exists.
     * 
     * @param int $productId
     * @param array $valueIds
     * @return ProductVariant|null
     */
    public function findVariantByValues(int $productId, array $valueIds): ?ProductVariant
    {
        $variantIds = \DB::table('product_variant_values')
            ->select('variant_id')
            ->whereIn('value_id', $valueIds)
            ->groupBy('variant_id')
            ->havingRaw('COUNT(DISTINCT value_id) = ?', [count($valueIds)])
            ->pluck('variant_id')
            ->toArray();

        return $this->where('product_id', $productId)
            ->whereIn('id', $variantIds)
            ->active()
            ->first();
    }
}