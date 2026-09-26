<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductVariantImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variant_images';

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
        'variant_id' => 'integer',
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
     * Get the variant that owns the image.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    /**
     * Get the product that owns the image (for convenience).
     * Note: product_id is denormalized for faster queries.
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
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC');
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    /**
     * Scope a query to filter by variant.
     */
    public function scopeByVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
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
     * Get all images for a specific variant.
     * Returns images ordered by primary flag first, then sort order.
     * 
     * @param int $variantId
     * @return array
     */
    public function getImagesByVariant(int $variantId): array
    {
        return $this->where('variant_id', $variantId)
            ->ordered()
            ->get()
            ->toArray();
    }

    /**
     * Set a specific image as the primary image for a variant.
     * Resets all other images for the variant to non-primary.
     * 
     * @param int $variantId
     * @param int $imageId
     * @return bool
     */
    public function setPrimaryImage(int $variantId, int $imageId): bool
    {
        // Start a database transaction to ensure consistency
        return \DB::transaction(function () use ($variantId, $imageId) {
            // Reset all primary flags for this variant
            $this->where('variant_id', $variantId)
                ->update(['is_primary' => 0]);
            
            // Set the selected image as primary
            return (bool) $this->where('id', $imageId)
                ->where('variant_id', $variantId)
                ->update(['is_primary' => 1]);
        });
    }

    /**
     * Delete an image and remove the physical file.
     * 
     * @param int $imageId
     * @return bool
     */
    public function deleteImage(int $imageId): bool
    {
        $image = $this->find($imageId);
        
        if (!$image) {
            return false;
        }
        
        // Start a database transaction
        return \DB::transaction(function () use ($image) {
            // Delete the physical file
            $deleted = $this->deleteImageFile($image->image);
            
            if (!$deleted) {
                // Log but don't fail - file might already be deleted
                Log::warning('Image file not found for variant image ID: ' . $image->id);
            }
            
            // Delete the database record
            return (bool) $image->delete();
        });
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Delete the physical image file.
     * 
     * @param string $imagePath
     * @return bool
     */
    public function deleteImageFile(string $imagePath): bool
    {
        // Remove leading slash if present
        $path = ltrim($imagePath, '/');
        
        // Check if file exists in storage
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        
        // Also check in the old CI4 path (uploads directory)
        $ci4Path = public_path($path);
        if (file_exists($ci4Path)) {
            return @unlink($ci4Path);
        }
        
        return false;
    }

    /**
     * Get the primary image for a variant.
     * 
     * @param int $variantId
     * @return ProductVariantImage|null
     */
    public function getPrimaryImage(int $variantId): ?ProductVariantImage
    {
        return $this->where('variant_id', $variantId)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Check if a variant has any images.
     * 
     * @param int $variantId
     * @return bool
     */
    public function hasImages(int $variantId): bool
    {
        return $this->where('variant_id', $variantId)->exists();
    }

    /**
     * Get the number of images for a variant.
     * 
     * @param int $variantId
     * @return int
     */
    public function countImages(int $variantId): int
    {
        return $this->where('variant_id', $variantId)->count();
    }

    /**
     * Delete all images for a variant (including physical files).
     * 
     * @param int $variantId
     * @return int Number of deleted records
     */
    public function deleteAllForVariant(int $variantId): int
    {
        $images = $this->where('variant_id', $variantId)->get();
        $count = 0;

        \DB::transaction(function () use ($images, &$count) {
            foreach ($images as $image) {
                // Delete physical file
                $this->deleteImageFile($image->image);
                
                // Delete database record
                if ($image->delete()) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Reorder images for a variant based on provided IDs.
     * 
     * @param int $variantId
     * @param array $orderedIds Array of image IDs in desired order
     * @return bool
     */
    public function reorderImages(int $variantId, array $orderedIds): bool
    {
        return \DB::transaction(function () use ($variantId, $orderedIds) {
            foreach ($orderedIds as $index => $imageId) {
                $this->where('id', $imageId)
                    ->where('variant_id', $variantId)
                    ->update(['sort_order' => $index + 1]);
            }
            return true;
        });
    }

    /**
     * Upload and create a new variant image.
     * 
     * @param int $variantId
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $options ['is_primary' => false, 'sort_order' => 0, 'product_id' => null]
     * @return ProductVariantImage|null
     */
    public function uploadImage(int $variantId, $file, array $options = []): ?ProductVariantImage
    {
        try {
            $path = $file->store('variant-images', 'public');
            
            if (!$path) {
                return null;
            }

            $data = [
                'variant_id' => $variantId,
                'image' => $path,
                'is_primary' => $options['is_primary'] ?? 0,
                'sort_order' => $options['sort_order'] ?? 0,
                'product_id' => $options['product_id'] ?? null,
            ];

            // If this is the first image or set as primary, make it primary
            if ($data['is_primary'] || !$this->hasImages($variantId)) {
                $data['is_primary'] = 1;
                // Reset other primary images
                $this->where('variant_id', $variantId)
                    ->where('id', '!=', 0) // This is a placeholder, we'll handle differently
                    ->update(['is_primary' => 0]);
            }

            return $this->create($data);
        } catch (\Exception $e) {
            Log::error('Variant image upload error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload multiple images for a variant.
     * 
     * @param int $variantId
     * @param array $files
     * @param int|null $productId
     * @return Collection
     */
    public function uploadMultipleImages(int $variantId, array $files, ?int $productId = null): Collection
    {
        $uploaded = collect();

        foreach ($files as $index => $file) {
            $isPrimary = $index === 0 && !$this->hasImages($variantId);
            $image = $this->uploadImage($variantId, $file, [
                'is_primary' => $isPrimary,
                'sort_order' => $index,
                'product_id' => $productId,
            ]);
            
            if ($image) {
                $uploaded->push($image);
            }
        }

        return $uploaded;
    }

    /**
     * Get the full URL for the image.
     * 
     * @param bool $secure Whether to use HTTPS
     * @return string|null
     */
    public function getImageUrl(bool $secure = false): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        // Check if it's a full URL already
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Check in storage
        if (Storage::disk('public')->exists($this->image)) {
            return Storage::disk('public')->url($this->image);
        }

        // Check in public directory (old CI4 path)
        $publicPath = public_path(ltrim($this->image, '/'));
        if (file_exists($publicPath)) {
            return asset(ltrim($this->image, '/'), $secure);
        }

        return null;
    }

    /**
     * Get image URL accessor.
     */
    public function getUrlAttribute(): ?string
    {
        return $this->getImageUrl();
    }

    /**
     * Clone images from one variant to another.
     * 
     * @param int $sourceVariantId
     * @param int $targetVariantId
     * @param int|null $productId
     * @return int Number of cloned images
     */
    public function cloneVariantImages(int $sourceVariantId, int $targetVariantId, ?int $productId = null): int
    {
        $sourceImages = $this->where('variant_id', $sourceVariantId)->get();
        $count = 0;

        \DB::transaction(function () use ($sourceImages, $targetVariantId, $productId, &$count) {
            foreach ($sourceImages as $image) {
                // Note: This only clones the database records, not the physical files
                // For a full clone, you'd need to copy the physical files as well
                $newImage = $image->replicate();
                $newImage->variant_id = $targetVariantId;
                if ($productId !== null) {
                    $newImage->product_id = $productId;
                }
                if ($newImage->save()) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Get all images for a product across all variants.
     * 
     * @param int $productId
     * @return Collection
     */
    public function getImagesForProduct(int $productId): Collection
    {
        return $this->where('product_id', $productId)
            ->ordered()
            ->get();
    }

    /**
     * Get distinct images for a product (one per variant).
     * 
     * @param int $productId
     * @return Collection
     */
    public function getDistinctProductImages(int $productId): Collection
    {
        return $this->where('product_id', $productId)
            ->select('*')
            ->selectRaw('MIN(id) as min_id')
            ->groupBy('variant_id')
            ->ordered()
            ->get();
    }
}