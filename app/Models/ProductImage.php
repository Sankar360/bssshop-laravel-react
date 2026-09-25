<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProductImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_images';

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
        'image',
        'is_primary',
        'sort_order',
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
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
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
        'is_primary' => 0,
        'sort_order' => 0,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to order by primary flag first, then sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC');
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get all images for a specific product.
     * Returns images ordered by primary flag first, then sort order.
     * 
     * @param int $productId
     * @return Collection
     */
    public function getImagesByProduct(int $productId): Collection
    {
        return $this->where('product_id', $productId)
            ->ordered()
            ->get();
    }

    /**
     * Set a specific image as the primary image for a product.
     * Resets all other images for the product to non-primary.
     * 
     * @param int $productId
     * @param int $imageId
     * @return bool
     */
    public function setPrimaryImage(int $productId, int $imageId): bool
    {
        // Start a database transaction to ensure consistency
        return \DB::transaction(function () use ($productId, $imageId) {
            // Reset all primary flags for this product
            $this->where('product_id', $productId)
                ->update(['is_primary' => 0]);
            
            // Set the selected image as primary
            return (bool) $this->where('id', $imageId)
                ->where('product_id', $productId)
                ->update(['is_primary' => 1]);
        });
    }

    /**
     * Alternative method using Eloquent model instance approach.
     * This is more in line with Laravel conventions.
     * 
     * @param int $productId
     * @param int $imageId
     * @return bool
     */
    public function setPrimaryImageAlternative(int $productId, int $imageId): bool
    {
        // Find all images for this product
        $images = $this->where('product_id', $productId)->get();
        
        // Reset all primary flags
        foreach ($images as $image) {
            $image->is_primary = 0;
            $image->save();
        }
        
        // Set the selected image as primary
        $targetImage = $this->find($imageId);
        if ($targetImage && $targetImage->product_id === $productId) {
            $targetImage->is_primary = 1;
            return $targetImage->save();
        }
        
        return false;
    }

    /**
     * Get the primary image for a specific product.
     * 
     * @param int $productId
     * @return ProductImage|null
     */
    public function getPrimaryImage(int $productId): ?ProductImage
    {
        return $this->where('product_id', $productId)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Check if a product has any images.
     * 
     * @param int $productId
     * @return bool
     */
    public function hasImages(int $productId): bool
    {
        return $this->where('product_id', $productId)->exists();
    }

    /**
     * Get the number of images for a product.
     * 
     * @param int $productId
     * @return int
     */
    public function countImages(int $productId): int
    {
        return $this->where('product_id', $productId)->count();
    }

    /**
     * Delete all images for a product.
     * 
     * @param int $productId
     * @return int Number of deleted records
     */
    public function deleteAllForProduct(int $productId): int
    {
        return $this->where('product_id', $productId)->delete();
    }

    /**
     * Reorder images for a product based on provided IDs.
     * 
     * @param int $productId
     * @param array $orderedIds Array of image IDs in desired order
     * @return bool
     */
    public function reorderImages(int $productId, array $orderedIds): bool
    {
        return \DB::transaction(function () use ($productId, $orderedIds) {
            foreach ($orderedIds as $index => $imageId) {
                $this->where('id', $imageId)
                    ->where('product_id', $productId)
                    ->update(['sort_order' => $index + 1]);
            }
            return true;
        });
    }
}