<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Category extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

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
        'parent_id',
        'name',
        'slug',
        'icon',
        'description',
        'status',
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
        'parent_id' => 'integer',
        'status' => 'boolean',
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
     * Get the parent category that owns this category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /**
     * Get the subcategories for this category.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    /**
     * Get the active subcategories for this category.
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('status', true);
    }

    /**
     * Get all parent categories (categories with no parent).
     * 
     * @param bool $activeOnly If true, only return active categories
     * @return Collection
     */
    public function getParentCategories(bool $activeOnly = false): Collection
    {
        $query = $this->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC');

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * Get direct subcategories of a given parent.
     * 
     * @param int $parentId The parent category ID
     * @param bool $activeOnly If true, only return active subcategories
     * @return Collection
     */
    public function getSubcategories(int $parentId, bool $activeOnly = false): Collection
    {
        $query = $this->where('parent_id', $parentId)
            ->orderBy('sort_order', 'ASC');

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * Get full parent -> children tree, one level deep.
     * 
     * @param bool $activeOnly If true, only include active categories
     * @return Collection
     */
    public function getCategoryTree(bool $activeOnly = false): Collection
    {
        $parents = $this->getParentCategories($activeOnly);

        // Eager load children to avoid N+1 query
        $childrenQuery = $this->whereNotNull('parent_id')
            ->orderBy('sort_order', 'ASC');

        if ($activeOnly) {
            $childrenQuery->where('status', true);
        }

        $allChildren = $childrenQuery->get()->groupBy('parent_id');

        // Attach children to parent categories
        $parents->each(function ($parent) use ($allChildren) {
            $parent->children = $allChildren->get($parent->id, collect());
        });

        return $parents;
    }

    /**
     * Get all IDs in the branch (self + subcategories).
     * Useful for "show products in this category or any of its subcategories".
     * 
     * @param int $categoryId The category ID
     * @return array<int>
     */
    public function getBranchIds(int $categoryId): array
    {
        $ids = [$categoryId];
        
        $children = $this->where('parent_id', $categoryId)->get();
        
        foreach ($children as $child) {
            $ids[] = $child->id;
        }
        
        return $ids;
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include parent categories.
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    /**
     * Get products belonging to this category.
     * Assuming there's a Product model with category_id foreign key.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    /**
     * Get products belonging to this category OR any subcategory.
     * This is useful for product filtering.
     */
    public function productsIncludingSubcategories()
    {
        $categoryIds = $this->getBranchIds($this->id);
        return Product::whereIn('category_id', $categoryIds);
    }
}