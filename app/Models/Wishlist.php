<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class Wishlist extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wishlist';

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
        'user_id',
        'product_id',
        'variant_id',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     * Only created_at is used, updated_at is not.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns the wishlist item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the product that is in the wishlist.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the variant that is in the wishlist.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
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
     * Scope to order by latest first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'DESC');
    }

    /**
     * Scope to only include active products.
     */
    public function scopeWithActiveProducts($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('status', 'active');
        });
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get wishlist items with product details.
     * 
     * @param int $userId
     * @return Collection
     */
    public function getWishlistItems(int $userId): Collection
    {
        return $this->with('product')
            ->byUser($userId)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->latestFirst()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'created_at' => $item->created_at,
                    'product' => [
                        'id' => $item->product->id ?? null,
                        'name' => $item->product->name ?? null,
                        'slug' => $item->product->slug ?? null,
                        'price' => $item->product->price ?? null,
                        'image' => $item->product->image ?? null,
                        'stock' => $item->product->stock ?? null,
                        'description' => $item->product->description ?? null,
                    ]
                ];
            });
    }

    /**
     * Get wishlist items with variant details.
     * 
     * @param int $userId
     * @return Collection
     */
    public function getWishlistItemsWithVariants(int $userId): Collection
    {
        return $this->with(['product', 'variant'])
            ->byUser($userId)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->latestFirst()
            ->get()
            ->map(function ($item) {
                $data = [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'created_at' => $item->created_at,
                    'product_id' => $item->product->id ?? null,
                    'name' => $item->product->name ?? null,
                    'slug' => $item->product->slug ?? null,
                    'price' => $item->product->price ?? null,
                    'sale_price' => $item->product->sale_price ?? null,
                    'image' => $item->product->image ?? null,
                    'stock' => $item->product->stock ?? null,
                    'description' => $item->product->description ?? null,
                ];

                if ($item->variant) {
                    $data['variant_id'] = $item->variant->id;
                    $data['variant_price'] = $item->variant->price;
                    $data['variant_sale_price'] = $item->variant->sale_price;
                    $data['variant_stock'] = $item->variant->stock;
                    $data['sku'] = $item->variant->sku;
                }

                return $data;
            });
    }

    /**
     * Get wishlist items as array (with product details).
     * 
     * @param int $userId
     * @return array
     */
    public function getWishlistItemsArray(int $userId): array
    {
        return $this->getWishlistItems($userId)->toArray();
    }

    /**
     * Get wishlist items with variants as array.
     * 
     * @param int $userId
     * @return array
     */
    public function getWishlistItemsWithVariantsArray(int $userId): array
    {
        return $this->getWishlistItemsWithVariants($userId)->toArray();
    }

    /**
     * Check if product is in user's wishlist.
     * 
     * @param int $userId
     * @param int $productId
     * @param int $variantId
     * @return bool
     */
    public function isInWishlist(int $userId, int $productId, int $variantId = 0): bool
    {
        $query = $this->byUser($userId)
            ->byProduct($productId)
            ->byVariant($variantId);

        return $query->exists();
    }

    /**
     * Toggle wishlist item (add/remove).
     * 
     * @param int $userId
     * @param int $productId
     * @param int $variantId
     * @return array
     */
    public function toggleWishlist(int $userId, int $productId, int $variantId = 0): array
    {
        try {
            // Check if exists
            $exists = $this->byUser($userId)
                ->byProduct($productId)
                ->byVariant($variantId)
                ->exists();

            if ($exists) {
                // Remove from wishlist
                $deleted = $this->byUser($userId)
                    ->byProduct($productId)
                    ->byVariant($variantId)
                    ->delete();

                if ($deleted) {
                    return ['action' => 'removed', 'message' => 'Product removed from wishlist'];
                }

                return ['action' => 'error', 'message' => 'Failed to remove from wishlist'];
            }

            // Add to wishlist
            $data = [
                'user_id' => $userId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'created_at' => now(),
            ];

            $inserted = $this->create($data);

            if ($inserted) {
                return ['action' => 'added', 'message' => 'Product added to wishlist'];
            }

            Log::error('Wishlist insert failed');
            return ['action' => 'error', 'message' => 'Failed to add to wishlist'];
        } catch (\Exception $e) {
            Log::error('Wishlist toggle exception: ' . $e->getMessage());
            return ['action' => 'error', 'message' => 'An error occurred'];
        }
    }

    /**
     * Get wishlist count for a user.
     * 
     * @param int $userId
     * @return int
     */
    public function getWishlistCount(int $userId): int
    {
        return $this->byUser($userId)->count();
    }

    /**
     * Get wishlist product IDs for a user.
     * 
     * @param int $userId
     * @return array
     */
    public function getWishlistProductIds(int $userId): array
    {
        return $this->byUser($userId)
            ->pluck('product_id')
            ->toArray();
    }

    /**
     * Get wishlist status for multiple products.
     * 
     * @param int $userId
     * @param array $productIds
     * @return array
     */
    public function getWishlistStatus(int $userId, array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return $this->byUser($userId)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->toArray();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get wishlist items with product and variant details (optimized with eager loading).
     * 
     * @param int $userId
     * @return Collection
     */
    public function getWishlistItemsOptimized(int $userId): Collection
    {
        return $this->with(['product' => function ($q) {
                $q->select('id', 'name', 'slug', 'price', 'sale_price', 'image', 'stock', 'description', 'status');
            }, 'variant' => function ($q) {
                $q->select('id', 'product_id', 'price', 'sale_price', 'stock', 'sku', 'variant_name');
            }])
            ->byUser($userId)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->latestFirst()
            ->get();
    }

    /**
     * Get wishlist item IDs for a user.
     * 
     * @param int $userId
     * @return array
     */
    public function getWishlistItemIds(int $userId): array
    {
        return $this->byUser($userId)->pluck('id')->toArray();
    }

    /**
     * Get wishlist items by product IDs.
     * 
     * @param int $userId
     * @param array $productIds
     * @return Collection
     */
    public function getWishlistItemsByProducts(int $userId, array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return $this->byUser($userId)
            ->whereIn('product_id', $productIds)
            ->with('product')
            ->get();
    }

    /**
     * Clear all wishlist items for a user.
     * 
     * @param int $userId
     * @return int Number of deleted items
     */
    public function clearWishlist(int $userId): int
    {
        return $this->byUser($userId)->delete();
    }

    /**
     * Move wishlist items to cart.
     * 
     * @param int $userId
     * @return int Number of items moved
     */
    public function moveAllToCart(int $userId): int
    {
        $items = $this->byUser($userId)->with('product')->get();
        $moved = 0;

        foreach ($items as $item) {
            // Check if product is in stock
            $product = $item->product;
            if (!$product || $product->stock <= 0) {
                continue;
            }

            // Add to cart logic here (using Cart model)
            // This is a placeholder - implement based on your cart system
            $moved++;
        }

        // Clear wishlist after moving
        $this->clearWishlist($userId);

        return $moved;
    }

    /**
     * Get wishlist items count by product.
     * 
     * @param int $productId
     * @return int
     */
    public function getWishlistCountByProduct(int $productId): int
    {
        return $this->byProduct($productId)->count();
    }

    /**
     * Get most wished products.
     * 
     * @param int $limit
     * @param bool $activeOnly
     * @return Collection
     */
    public function getMostWishedProducts(int $limit = 10, bool $activeOnly = true): Collection
    {
        $query = $this->select('product_id')
            ->selectRaw('COUNT(*) as wishlist_count')
            ->groupBy('product_id')
            ->orderBy('wishlist_count', 'DESC')
            ->limit($limit);

        if ($activeOnly) {
            $query->whereHas('product', function ($q) {
                $q->where('status', 'active');
            });
        }

        return $query->get();
    }

    /**
     * Export wishlist to array for CSV/Excel.
     * 
     * @param int $userId
     * @return array
     */
    public function exportWishlist(int $userId): array
    {
        return $this->with(['product', 'variant'])
            ->byUser($userId)
            ->latestFirst()
            ->get()
            ->map(function ($item) {
                return [
                    'ID' => $item->id,
                    'Product' => $item->product?->name,
                    'Product SKU' => $item->product?->sku,
                    'Variant' => $item->variant?->variant_name,
                    'Variant SKU' => $item->variant?->sku,
                    'Price' => $item->product?->price,
                    'Added Date' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Check if multiple products are in wishlist.
     * 
     * @param int $userId
     * @param array $productIds
     * @return array
     */
    public function checkMultipleProducts(int $userId, array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $wishlisted = $this->byUser($userId)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->toArray();

        $result = [];
        foreach ($productIds as $productId) {
            $result[$productId] = in_array($productId, $wishlisted);
        }

        return $result;
    }

    /**
     * Get wishlist items with pagination.
     * 
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedWishlist(int $userId, int $perPage = 20)
    {
        return $this->with(['product', 'variant'])
            ->byUser($userId)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->latestFirst()
            ->paginate($perPage);
    }

    /**
     * Get wishlist count by product for multiple products.
     * 
     * @param array $productIds
     * @return Collection
     */
    public function getWishlistCountsByProducts(array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return $this->whereIn('product_id', $productIds)
            ->select('product_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('product_id')
            ->get();
    }

    /**
     * Get wishlist items with variant info for frontend.
     * 
     * @param int $userId
     * @return Collection
     */
    public function getFrontendWishlist(int $userId): Collection
    {
        return $this->with(['product' => function ($q) {
                $q->select('id', 'name', 'slug', 'price', 'sale_price', 'image', 'stock', 'description', 'rating');
            }, 'variant' => function ($q) {
                $q->select('id', 'product_id', 'price', 'sale_price', 'stock', 'sku', 'variant_name');
            }])
            ->byUser($userId)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->latestFirst()
            ->get()
            ->map(function ($item) {
                $product = $item->product;
                $variant = $item->variant;

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'created_at' => $item->created_at,
                    'product' => [
                        'id' => $product?->id,
                        'name' => $product?->name,
                        'slug' => $product?->slug,
                        'price' => $product?->price,
                        'sale_price' => $product?->sale_price,
                        'image' => $product?->image,
                        'stock' => $product?->stock,
                        'description' => $product?->description,
                        'rating' => $product?->rating,
                    ],
                    'variant' => $variant ? [
                        'id' => $variant->id,
                        'price' => $variant->price,
                        'sale_price' => $variant->sale_price,
                        'stock' => $variant->stock,
                        'sku' => $variant->sku,
                        'variant_name' => $variant->variant_name,
                    ] : null,
                ];
            });
    }

    /**
     * Get wishlist summary for user.
     * 
     * @param int $userId
     * @return array
     */
    public function getWishlistSummary(int $userId): array
    {
        $items = $this->byUser($userId)->get();
        $totalItems = $items->count();

        // Calculate total price (with variants)
        $totalValue = 0;
        foreach ($items as $item) {
            if ($item->variant) {
                $totalValue += $item->variant->price ?? 0;
            } elseif ($item->product) {
                $totalValue += $item->product->price ?? 0;
            }
        }

        return [
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'formatted_total' => '$' . number_format($totalValue, 2),
        ];
    }
}