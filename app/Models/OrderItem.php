<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_items';

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
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'subtotal',
        'shipping',
        'tax',
        'created_at',
    'updated_at',   // ← add back
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'order_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',   // ← add back
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

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'quantity' => 1,
        'shipping' => 0,
        'tax' => 0,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Get the product that owns the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the variant that owns the item.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the formatted price attribute.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the formatted subtotal attribute.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get the formatted shipping attribute.
     */
    public function getFormattedShippingAttribute(): string
    {
        return '$' . number_format($this->shipping, 2);
    }

    /**
     * Get the formatted tax attribute.
     */
    public function getFormattedTaxAttribute(): string
    {
        return '$' . number_format($this->tax, 2);
    }

    /**
     * Get the total (subtotal + shipping + tax).
     */
    public function getTotalAttribute(): float
    {
        return $this->subtotal + $this->shipping + $this->tax;
    }

    /**
     * Get the formatted total attribute.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Get the item name (product name with variant if applicable).
     */
    public function getItemNameAttribute(): string
    {
        $name = $this->product?->name ?? 'Product #' . $this->product_id;
        
        if ($this->variant) {
            $name .= ' - ' . $this->variant->variant_name ?? 'Variant #' . $this->variant_id;
        }
        
        return $name;
    }

    /**
     * Get the item SKU.
     */
    public function getItemSkuAttribute(): ?string
    {
        if ($this->variant) {
            return $this->variant->sku;
        }
        
        return $this->product?->sku;
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by order.
     */
    public function scopeByOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope to filter by product.
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to filter by variant.
     */
    public function scopeByVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope to include product relationship.
     */
    public function scopeWithProduct($query)
    {
        return $query->with('product');
    }

    /**
     * Scope to include variant relationship.
     */
    public function scopeWithVariant($query)
    {
        return $query->with('variant');
    }

    /**
     * Scope to include all relationships.
     */
    public function scopeWithDetails($query)
    {
        return $query->with(['product', 'variant']);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get order items with product details.
     * 
     * @param int $orderId
     * @return array
     */
    public function getOrderItemsWithProduct(int $orderId): array
    {
        return $this->with('product')
            ->where('order_id', $orderId)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['name'] = $item->product?->name;
                $data['image'] = $item->product?->image;
                return $data;
            })
            ->toArray();
    }

    /**
     * Get order items with variant details.
     * 
     * @param int $orderId
     * @return array
     */
    public function getOrderItemsWithVariant(int $orderId): array
    {
        return $this->with(['product', 'variant'])
            ->where('order_id', $orderId)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['name'] = $item->product?->name;
                $data['image'] = $item->product?->image;
                $data['variant_sku'] = $item->variant?->sku;
                $data['variant_price'] = $item->variant?->price;
                return $data;
            })
            ->toArray();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get order items with product and variant details as collection.
     * 
     * @param int $orderId
     * @return Collection
     */
    public function getOrderItemsWithDetails(int $orderId): Collection
    {
        return $this->with(['product', 'variant'])
            ->where('order_id', $orderId)
            ->get();
    }

    /**
     * Calculate subtotal for an order item.
     * 
     * @param int $id
     * @return bool
     */
    public function calculateSubtotal(int $id): bool
    {
        $item = $this->find($id);
        
        if (!$item) {
            return false;
        }

        $subtotal = $item->price * $item->quantity;
        
        return (bool) $this->where('id', $id)
            ->update(['subtotal' => $subtotal]);
    }

    /**
     * Calculate subtotals for all items in an order.
     * 
     * @param int $orderId
     * @return int Number of updated items
     */
    public function calculateOrderSubtotals(int $orderId): int
    {
        $items = $this->where('order_id', $orderId)->get();
        $count = 0;

        foreach ($items as $item) {
            $subtotal = $item->price * $item->quantity;
            $this->where('id', $item->id)
                ->update(['subtotal' => $subtotal]);
            $count++;
        }

        return $count;
    }

    /**
     * Get order items summary for an order.
     * 
     * @param int $orderId
     * @return array
     */
    public function getOrderItemsSummary(int $orderId): array
    {
        $items = $this->where('order_id', $orderId)->get();

        return [
            'total_items' => $items->sum('quantity'),
            'total_subtotal' => $items->sum('subtotal'),
            'total_shipping' => $items->sum('shipping'),
            'total_tax' => $items->sum('tax'),
            'total' => $items->sum(function ($item) {
                return $item->subtotal + $item->shipping + $item->tax;
            }),
        ];
    }

    /**
     * Get order items grouped by product.
     * 
     * @param int $orderId
     * @return Collection
     */
    public function getOrderItemsGroupedByProduct(int $orderId): Collection
    {
        return $this->with('product')
            ->where('order_id', $orderId)
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                return [
                    'product' => $items->first()->product,
                    'total_quantity' => $items->sum('quantity'),
                    'total_price' => $items->sum('subtotal'),
                    'items' => $items,
                ];
            });
    }

    /**
     * Create order items from cart items.
     * 
     * @param int $orderId
     * @param array $cartItems
     * @return Collection
     */
    public function createFromCart(int $orderId, array $cartItems): Collection
    {
        $createdItems = collect();

        foreach ($cartItems as $cartItem) {
            $item = $this->create([
                'order_id' => $orderId,
                'product_id' => $cartItem['product_id'],
                'variant_id' => $cartItem['variant_id'] ?? null,
                'quantity' => $cartItem['quantity'],
                'price' => $cartItem['price'],
                'subtotal' => $cartItem['price'] * $cartItem['quantity'],
                'shipping' => $cartItem['shipping'] ?? 0,
                'tax' => $cartItem['tax'] ?? 0,
            ]);
            
            $createdItems->push($item);
        }

        return $createdItems;
    }

    /**
     * Get order items with product images.
     * 
     * @param int $orderId
     * @return Collection
     */
    public function getOrderItemsWithImages(int $orderId): Collection
    {
        return $this->with(['product' => function ($query) {
                $query->with('primaryImage');
            }, 'variant' => function ($query) {
                $query->with('primaryImage');
            }])
            ->where('order_id', $orderId)
            ->get();
    }

    /**
     * Update quantity for an order item.
     * 
     * @param int $id
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity(int $id, int $quantity): bool
    {
        $item = $this->find($id);
        
        if (!$item) {
            return false;
        }

        $subtotal = $item->price * $quantity;
        
        return (bool) $this->where('id', $id)
            ->update([
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ]);
    }

    /**
     * Get top selling products.
     * 
     * @param int $limit
     * @param int|null $orderId
     * @return Collection
     */
    public function getTopSellingProducts(int $limit = 10, ?int $orderId = null): Collection
    {
        $query = $this->select('product_id')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_revenue')
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'DESC')
            ->limit($limit);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->get();
    }

    /**
     * Get order items by date range.
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $orderId
     * @return Collection
     */
    public function getItemsByDateRange(string $startDate, string $endDate, ?int $orderId = null): Collection
    {
        $query = $this->whereHas('order', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        });

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->with(['product', 'variant'])->get();
    }

    /**
     * Get total revenue from order items.
     * 
     * @param int|null $orderId
     * @return float
     */
    public function getTotalRevenue(?int $orderId = null): float
    {
        $query = $this->query();

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->sum('subtotal') ?? 0;
    }

    /**
     * Get item count statistics.
     * 
     * @param int|null $orderId
     * @return array
     */
    public function getItemStats(?int $orderId = null): array
    {
        $query = $this->query();

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return [
            'total_items' => $query->count(),
            'total_quantity' => $query->sum('quantity'),
            'total_subtotal' => $query->sum('subtotal'),
            'total_shipping' => $query->sum('shipping'),
            'total_tax' => $query->sum('tax'),
            'total_revenue' => $query->sum('subtotal'),
        ];
    }

    /**
     * Get order items by product category.
     * 
     * @param int $categoryId
     * @param int|null $orderId
     * @return Collection
     */
    public function getItemsByCategory(int $categoryId, ?int $orderId = null): Collection
    {
        $query = $this->whereHas('product', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->with(['product', 'variant'])->get();
    }

    /**
     * Get average order value for items.
     * 
     * @param int|null $orderId
     * @return float
     */
    public function getAverageItemValue(?int $orderId = null): float
    {
        $query = $this->query();

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $count = $query->count();
        
        if ($count === 0) {
            return 0;
        }

        return $query->sum('subtotal') / $count;
    }

    /**
     * Export order items to array for CSV/Excel.
     * 
     * @param int $orderId
     * @return array
     */
    public function exportOrderItems(int $orderId): array
    {
        return $this->with(['product', 'variant'])
            ->where('order_id', $orderId)
            ->get()
            ->map(function ($item) {
                return [
                    'ID' => $item->id,
                    'Product' => $item->product?->name,
                    'Product SKU' => $item->product?->sku,
                    'Variant' => $item->variant?->variant_name,
                    'Variant SKU' => $item->variant?->sku,
                    'Quantity' => $item->quantity,
                    'Price' => $item->formatted_price,
                    'Subtotal' => $item->formatted_subtotal,
                    'Shipping' => $item->formatted_shipping,
                    'Tax' => $item->formatted_tax,
                    'Total' => $item->formatted_total,
                ];
            })
            ->toArray();
    }

    /**
     * Check if order item has a variant.
     * 
     * @param int $id
     * @return bool
     */
    public function hasVariant(int $id): bool
    {
        $item = $this->find($id);
        
        return $item && !empty($item->variant_id);
    }

    /**
     * Get order item with all nested relationships.
     * 
     * @param int $id
     * @return array|null
     */
    public function getItemWithFullDetails(int $id): ?array
    {
        $item = $this->with(['order', 'product', 'variant'])
            ->find($id);
            
        if (!$item) {
            return null;
        }

        return $item->toArray();
    }

    /**
     * Apply discount to order item.
     * 
     * @param int $id
     * @param float $discountAmount
     * @return bool
     */
    public function applyDiscount(int $id, float $discountAmount): bool
    {
        $item = $this->find($id);
        
        if (!$item) {
            return false;
        }

        $newSubtotal = max(0, $item->subtotal - $discountAmount);
        
        return (bool) $this->where('id', $id)
            ->update(['subtotal' => $newSubtotal]);
    }
}