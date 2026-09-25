<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductCategory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_categories';

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
        'icon',
        'item_count',
        'link',
        'sort_order',
        'is_active',
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
        'item_count' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
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
        'is_active' => 1,
        'sort_order' => 0,
        'item_count' => 0,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the parent category that owns this category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id', 'id');
    }

    /**
     * Get the subcategories for this category.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id', 'id');
    }

    /**
     * Get the active subcategories for this category.
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Get the products belonging to this category.
     * Note: This references the products table's category_id field.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    /**
     * Get products belonging to this category OR any subcategory.
     */
    public function productsIncludingSubcategories()
    {
        $categoryIds = $this->getBranchIds($this->id);
        return Product::whereIn('category_id', $categoryIds);
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include parent categories (top-level).
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include child categories.
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    /**
     * Scope a query to order by name.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('name', 'ASC');
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get parent categories (top-level categories with no parent).
     * 
     * @param bool $activeOnly If true, only return active categories
     * @return Collection
     */
    public function getParentCategories(bool $activeOnly = false): Collection
    {
        $query = $this->parents()->sorted();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get subcategories of a parent category.
     * 
     * @param int $parentId The parent category ID
     * @param bool $activeOnly If true, only return active subcategories
     * @return Collection
     */
    public function getSubcategories(int $parentId, bool $activeOnly = false): Collection
    {
        $query = $this->where('parent_id', $parentId)->sorted();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get category by slug.
     * Tries by link first, then falls back to name (slugified).
     * 
     * @param string $slug The slug to search for
     * @return ProductCategory|null
     */
    public function getCategoryBySlug(string $slug): ?ProductCategory
    {
        // Try by link first
        $category = $this->where('link', $slug)
            ->where('is_active', 1)
            ->first();

        if ($category) {
            return $category;
        }

        // Fallback: try by name (slugified)
        $name = str_replace('-', ' ', $slug);
        return $this->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get full category tree with children (one level deep).
     * 
     * @param bool $activeOnly If true, only include active categories
     * @return Collection
     */
    public function getCategoryTree(bool $activeOnly = false): Collection
    {
        $parents = $this->getParentCategories($activeOnly);

        // Eager load children to avoid N+1 query
        $childrenQuery = $this->whereNotNull('parent_id')->sorted();

        if ($activeOnly) {
            $childrenQuery->active();
        }

        $allChildren = $childrenQuery->get()->groupBy('parent_id');

        // Attach children to parent categories
        $parents->each(function ($parent) use ($allChildren) {
            $parent->children = $allChildren->get($parent->id, collect());
        });

        return $parents;
    }

    /**
     * Get category with its subcategories.
     * 
     * @param int $categoryId The category ID
     * @param bool $activeOnly If true, only return active subcategories
     * @return array|null
     */
    public function getCategoryWithSubcategories(int $categoryId, bool $activeOnly = false): ?array
    {
        $category = $this->find($categoryId);
        
        if (!$category) {
            return null;
        }

        $result = $category->toArray();
        $result['subcategories'] = $this->getSubcategories($categoryId, $activeOnly)->toArray();

        return $result;
    }

    /**
     * Get all category IDs in a branch (self + descendants).
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
     * Check if category has subcategories.
     * 
     * @param int $categoryId The category ID
     * @return bool
     */
    public function hasSubcategories(int $categoryId): bool
    {
        return $this->where('parent_id', $categoryId)->exists();
    }

    /**
     * Get active subcategories for dropdown (AJAX).
     * Returns only id and name fields.
     * 
     * @param int $parentId The parent category ID
     * @return array
     */
    public function getActiveSubcategories(int $parentId): array
    {
        return $this->select('id', 'name')
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->get()
            ->toArray();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get all descendant category IDs (recursive).
     * 
     * @param int $categoryId
     * @return array
     */
    public function getAllDescendantIds(int $categoryId): array
    {
        $ids = [];
        $children = $this->where('parent_id', $categoryId)->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllDescendantIds($child->id));
        }

        return $ids;
    }

    /**
     * Get full hierarchical category tree (all levels).
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getFullCategoryTree(bool $activeOnly = false): Collection
    {
        $parents = $this->getParentCategories($activeOnly);

        // Eager load all children recursively
        $this->loadChildrenRecursively($parents, $activeOnly);

        return $parents;
    }

    /**
     * Recursively load children for a collection of categories.
     * 
     * @param Collection $categories
     * @param bool $activeOnly
     */
    protected function loadChildrenRecursively(Collection $categories, bool $activeOnly = false): void
    {
        $categoryIds = $categories->pluck('id')->toArray();
        
        if (empty($categoryIds)) {
            return;
        }

        $childrenQuery = $this->whereIn('parent_id', $categoryIds)->sorted();

        if ($activeOnly) {
            $childrenQuery->active();
        }

        $children = $childrenQuery->get()->groupBy('parent_id');

        $categories->each(function ($category) use ($children, $activeOnly) {
            $category->children = $children->get($category->id, collect());
            $this->loadChildrenRecursively($category->children, $activeOnly);
        });
    }

    /**
     * Update item count for a category.
     * 
     * @param int $categoryId
     * @return bool
     */
    public function updateItemCount(int $categoryId): bool
    {
        $count = Product::where('category_id', $categoryId)
            ->where('status', 'active')
            ->count();

        return (bool) $this->where('id', $categoryId)
            ->update(['item_count' => $count]);
    }

    /**
     * Update item counts for all categories.
     * 
     * @return void
     */
    public function updateAllItemCounts(): void
    {
        $categories = $this->all();
        
        foreach ($categories as $category) {
            $this->updateItemCount($category->id);
        }
    }

    /**
     * Get categories with product counts.
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getCategoriesWithCounts(bool $activeOnly = false): Collection
    {
        $query = $this->select('product_categories.*')
            ->selectRaw('(SELECT COUNT(*) FROM products WHERE products.category_id = product_categories.id AND products.status = "active") as product_count');

        if ($activeOnly) {
            $query->active();
        }

        return $query->sorted()->get();
    }

    /**
     * Get breadcrumb trail for a category.
     * 
     * @param int $categoryId
     * @return Collection
     */
    public function getBreadcrumb(int $categoryId): Collection
    {
        $breadcrumb = collect();
        $current = $this->find($categoryId);

        while ($current) {
            $breadcrumb->prepend($current);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    /**
     * Get all categories as a flat list for dropdowns.
     * 
     * @param bool $activeOnly
     * @param string $prefix
     * @param int|null $parentId
     * @return array
     */
    public function getCategoriesForDropdown(bool $activeOnly = false, string $prefix = '', ?int $parentId = null): array
    {
        $query = $this->orderBy('sort_order', 'ASC');

        if ($activeOnly) {
            $query->active();
        }

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        $categories = $query->get();
        $result = [];

        foreach ($categories as $category) {
            $result[$category->id] = $prefix . $category->name;
            
            // Recursively get children
            $children = $this->getCategoriesForDropdown($activeOnly, $prefix . '-- ', $category->id);
            $result = $result + $children;
        }

        return $result;
    }
}