<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

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
    'name', 'slug', 'description', 'short_description',
    'price', 'sale_price', 'stock', 'sku', 'image',
    'status', 'category_id', 'subcategory_id',
    'rating', 'discount', 'is_featured', 'is_home',
    'tags', 'weight', 'length', 'width', 'height',
];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'category_id' => 'integer',
        'subcategory_id' => 'integer',
        'rating' => 'decimal:2',
        'discount' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_home' => 'boolean',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
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
        'status' => 'active',
        'stock' => 0,
        'rating' => 0,
        'discount' => 0,
        'is_featured' => 0,
        'is_home' => 0,
    ];

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($product) {
            $product->cleanData();
        });

        static::updating(function ($product) {
            $product->cleanData();
        });
    }

    /**
     * Clean empty string and null values before saving.
     * Replaces CI4's cleanData callback.
     */
   protected function cleanData(): void
{
    $protectedFields = ['name', 'price', 'stock', 'status', 'is_featured', 'is_home'];

    foreach ($this->attributes as $key => $value) {
        if (in_array($key, $protectedFields, true)) {
            continue;
        }
        if ($value === '') {
            $this->attributes[$key] = null;
        }
    }
}

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Get the subcategory that owns the product.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id', 'id');
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }

    /**
     * Get the active variants for the product.
     */
    public function activeVariants(): HasMany
    {
        return $this->variants()->where('status', 1)->where('stock', '>', 0);
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_id', 'id')
            ->where('is_primary', 1)
            ->orderBy('sort_order', 'ASC');
    }

    /**
     * Get the feature values for the product.
     */
    public function featureValues(): HasMany
    {
        return $this->hasMany(ProductFeatureValue::class, 'product_id', 'id');
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include in-stock products.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', 1)->active()->inStock();
    }

    /**
     * Scope a query to only include home page products.
     */
    public function scopeHome($query)
    {
        return $query->where('is_home', 1)->active()->inStock();
    }

    /**
     * Scope a query to order by latest.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'DESC');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by subcategory.
     */
    public function scopeBySubcategory($query, $subcategoryId)
    {
        return $query->where('subcategory_id', $subcategoryId);
    }

    /**
     * Scope a query to filter by price range.
     */
    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get all active products with stock.
     * 
     * @return Collection
     */
    public function getActiveProducts(): Collection
    {
        return $this->active()
            ->where('stock', '>', 0)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Get product by slug.
     * 
     * @param string $slug
     * @return Product|null
     */
    public function getProductBySlug(string $slug): ?Product
    {
        return $this->where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get featured products.
     * 
     * @return Collection
     */
    public function getFeaturedProducts(): Collection
    {
        return $this->featured()->get();
    }

    /**
     * Get product with category data.
     * 
     * @param int $id
     * @return array|null
     */
    public function getProductWithCategory(int $id): ?array
    {
        $product = $this->with('category:id,name')
            ->find($id);
            
        if (!$product) {
            return null;
        }

        $result = $product->toArray();
        $result['category_name'] = $product->category?->name;

        return $result;
    }

    /**
     * Get all products with category data.
     * 
     * @return array
     */
    public function getProductsWithCategories(): array
    {
        $products = $this->with('category:id,name')->get();
        
        $result = [];
        foreach ($products as $product) {
            $data = $product->toArray();
            $data['category_name'] = $product->category?->name;
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Get product with its images.
     * 
     * @param int $id
     * @return array|null
     */
    public function getProductWithImages(int $id): ?array
    {
        $product = $this->with('images')->find($id);
        
        if (!$product) {
            return null;
        }

        $result = $product->toArray();
        $result['images'] = $product->images->toArray();

        return $result;
    }

    /**
     * Get product data for inline editing.
     * 
     * @param int $id
     * @return array|null
     */
    public function getProductForInlineEdit(int $id): ?array
    {
        return $this->select('id', 'name', 'price', 'sale_price', 'stock', 'image', 'status')
            ->find($id)
            ?->toArray();
    }

    /**
     * Update product price.
     * 
     * @param int $id
     * @param float $price
     * @param float|null $salePrice
     * @return bool
     */
    public function updatePrice(int $id, float $price, ?float $salePrice = null): bool
    {
        return (bool) $this->where('id', $id)->update([
            'price' => $price,
            'sale_price' => $salePrice,
        ]);
    }

    /**
     * Update product stock.
     * 
     * @param int $id
     * @param int $stock
     * @return bool
     */
    public function updateStock(int $id, int $stock): bool
    {
        return (bool) $this->where('id', $id)->update([
            'stock' => $stock,
        ]);
    }

    /**
     * Get products for home listing with variant data.
     * Preserves the complex SQL logic from CI4.
     * 
     * @param array $conditions
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @return array
     */
    public function getHomeListingProducts(array $conditions = [], int $limit = 8, int $offset = 0, string $orderBy = 'p.created_at DESC'): array
    {
        // Build WHERE conditions
        $where = ["p.status = 'active'"];
        $bindings = [];

        foreach ($conditions as $column => $value) {
            if (!in_array($column, $this->fillable, true)) {
                continue;
            }
            $where[] = "p.`{$column}` = ?";
            $bindings[] = $value;
        }

        $whereSql = implode(' AND ', $where);
        $limit = max(0, (int) $limit);
        $offset = max(0, (int) $offset);

        $sql = "
            SELECT
                p.id,
                p.name,
                p.short_description AS description,
                p.category_id,
                p.created_at,
                p.image AS product_image,
                v.id AS variant_id,
                v.sku AS variant_sku,
                v.slug AS variant_slug,
                v.price AS variant_price,
                v.sale_price AS variant_sale_price,
                v.discount AS variant_discount,
                v.rating AS variant_rating,
                v.stock AS variant_stock,
                (
                    SELECT vi.image
                    FROM product_variant_images vi
                    WHERE vi.variant_id = v.id
                    ORDER BY vi.is_primary DESC, vi.sort_order ASC, vi.id ASC
                    LIMIT 1
                ) AS variant_image,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.price
                    ELSE p.price
                END AS price,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.sale_price
                    ELSE p.sale_price
                END AS sale_price,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.discount
                    ELSE p.discount
                END AS discount,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.rating
                    ELSE p.rating
                END AS rating,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.stock
                    ELSE p.stock
                END AS stock,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.slug
                    ELSE p.slug
                END AS slug,
                CASE 
                    WHEN v.id IS NOT NULL AND (
                        SELECT COUNT(*) FROM product_variant_images vi WHERE vi.variant_id = v.id
                    ) > 0 THEN (
                        SELECT vi.image FROM product_variant_images vi 
                        WHERE vi.variant_id = v.id 
                        ORDER BY vi.is_primary DESC, vi.sort_order ASC, vi.id ASC 
                        LIMIT 1
                    )
                    ELSE p.image
                END AS image,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM product_variants pvx 
                        WHERE pvx.product_id = p.id AND pvx.status = 1
                    ) THEN 1
                    ELSE 0
                END AS has_variants
            FROM products p
            LEFT JOIN (
                SELECT pv1.*
                FROM product_variants pv1
                INNER JOIN (
                    SELECT 
                        product_id,
                        MAX(stock) AS max_stock,
                        MIN(id) AS min_id
                    FROM product_variants
                    WHERE status = 1 
                      AND stock > 0
                    GROUP BY product_id
                ) best_variant
                    ON best_variant.product_id = pv1.product_id
                   AND best_variant.max_stock = pv1.stock
                   AND best_variant.min_id = pv1.id
                WHERE pv1.status = 1 
                  AND pv1.stock > 0
            ) v ON v.product_id = p.id
            WHERE {$whereSql}
              AND (
                    (NOT EXISTS (
                        SELECT 1 FROM product_variants pvx
                        WHERE pvx.product_id = p.id 
                          AND pvx.status = 1
                    ) AND p.stock > 0)
                    OR
                    v.id IS NOT NULL
                  )
            GROUP BY p.id
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}
        ";

        $results = DB::select($sql, $bindings);

        if (empty($results)) {
            return [];
        }

        // Convert to array
        $results = array_map(function ($item) {
            return (array) $item;
        }, $results);

        // Batch fetch variant feature values
        $variantIds = array_values(array_filter(array_column($results, 'variant_id')));
        $featuresByVariant = [];

        if (!empty($variantIds)) {
            $featureRows = DB::table('product_variant_values as pvv')
                ->select('pvv.variant_id', 'pvv.value', 'features.name as feature_name')
                ->join('features', 'features.id', '=', 'pvv.feature_id')
                ->whereIn('pvv.variant_id', $variantIds)
                ->get()
                ->toArray();

            foreach ($featureRows as $row) {
                $row = (array) $row;
                if (!isset($featuresByVariant[$row['variant_id']])) {
                    $featuresByVariant[$row['variant_id']] = [];
                }
                $featuresByVariant[$row['variant_id']][] = [
                    'name' => $row['feature_name'],
                    'value' => $row['value'],
                ];
            }
        }

        // Normalize results for views
        foreach ($results as &$row) {
            $isVariant = !empty($row['variant_id']);
            $row['sku'] = $isVariant ? $row['variant_sku'] : null;
            $row['features'] = $isVariant ? ($featuresByVariant[$row['variant_id']] ?? []) : [];
            $row['variant_id'] = $isVariant ? $row['variant_id'] : null;

            unset(
                $row['product_image'],
                $row['variant_sku'],
                $row['variant_price'],
                $row['variant_sale_price'],
                $row['variant_discount'],
                $row['variant_rating'],
                $row['variant_stock'],
                $row['variant_image']
            );
        }
        unset($row);

        return $results;
    }

    /**
     * Get filtered products for a specific category.
     * Preserves the complex SQL logic from CI4.
     * 
     * @param array $filters
     * @return array
     */
    public function getFilteredCategoryProducts(array $filters): array
    {
        $categoryId = $filters['category_id'] ?? 0;
        $subcategoryId = $filters['subcategory_id'] ?? null;
        $minPrice = $filters['min_price'] ?? 0;
        $maxPrice = $filters['max_price'] ?? 10000;
        $sort = $filters['sort'] ?? 'latest';
        $page = $filters['page'] ?? 1;
        $featureFilters = $filters['features'] ?? [];
        $perPage = $filters['per_page'] ?? 12;

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT 
                p.id,
                p.name,
                p.slug,
                p.description,
                p.short_description,
                p.category_id,
                p.subcategory_id,
                p.created_at,
                p.updated_at,
                p.image AS product_image,
                v.id AS variant_id,
                v.sku AS variant_sku,
                v.slug AS variant_slug,
                v.price AS variant_price,
                v.sale_price AS variant_sale_price,
                v.discount AS variant_discount,
                v.rating AS variant_rating,
                v.stock AS variant_stock,
                (
                    SELECT vi.image
                    FROM product_variant_images vi
                    WHERE vi.variant_id = v.id
                    ORDER BY vi.is_primary DESC, vi.sort_order ASC, vi.id ASC
                    LIMIT 1
                ) AS variant_image,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.price
                    ELSE p.price
                END AS price,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.sale_price
                    ELSE p.sale_price
                END AS sale_price,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.discount
                    ELSE p.discount
                END AS discount,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.rating
                    ELSE p.rating
                END AS rating,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.stock
                    ELSE p.stock
                END AS stock,
                CASE 
                    WHEN v.id IS NOT NULL THEN v.slug
                    ELSE p.slug
                END AS final_slug,
                CASE 
                    WHEN v.id IS NOT NULL AND (
                        SELECT COUNT(*) FROM product_variant_images vi WHERE vi.variant_id = v.id
                    ) > 0 THEN (
                        SELECT vi.image FROM product_variant_images vi 
                        WHERE vi.variant_id = v.id 
                        ORDER BY vi.is_primary DESC, vi.sort_order ASC, vi.id ASC 
                        LIMIT 1
                    )
                    ELSE p.image
                END AS final_image,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM product_variants pvx 
                        WHERE pvx.product_id = p.id AND pvx.status = 1
                    ) THEN 1
                    ELSE 0
                END AS has_variants
            FROM products p
            LEFT JOIN (
                SELECT pv1.*
                FROM product_variants pv1
                INNER JOIN (
                    SELECT 
                        product_id,
                        MAX(stock) AS max_stock,
                        MIN(id) AS min_id
                    FROM product_variants
                    WHERE status = 1 
                      AND stock > 0
                    GROUP BY product_id
                ) best_variant
                    ON best_variant.product_id = pv1.product_id
                   AND best_variant.max_stock = pv1.stock
                   AND best_variant.min_id = pv1.id
                WHERE pv1.status = 1 
                  AND pv1.stock > 0
            ) v ON v.product_id = p.id
            WHERE p.status = 'active'
              AND p.category_id = ?
              AND (
                    (NOT EXISTS (
                        SELECT 1 FROM product_variants pvx
                        WHERE pvx.product_id = p.id 
                          AND pvx.status = 1
                    ) AND p.stock > 0)
                    OR
                    v.id IS NOT NULL
                  )";

        $bindings = [$categoryId];

        // Subcategory filter
        if ($subcategoryId) {
            $sql .= " AND p.subcategory_id = ?";
            $bindings[] = $subcategoryId;
        }

        // Price filter
        $sql .= " AND (CASE 
                WHEN v.id IS NOT NULL THEN v.price
                ELSE p.price
              END) BETWEEN ? AND ?";
        $bindings[] = $minPrice;
        $bindings[] = $maxPrice;

        // Feature filters
        if (!empty($featureFilters)) {
            $featureConditions = [];
            $featureBindings = [];

            foreach ($featureFilters as $featureId => $values) {
                if (empty($values)) continue;

                if (!is_array($values)) {
                    $values = [$values];
                }

                $valuePlaceholders = implode(',', array_fill(0, count($values), '?'));

                $featureConditions[] = "EXISTS (
                SELECT 1 FROM product_feature_values pfv
                WHERE pfv.product_id = p.id
                AND pfv.feature_id = ?
                AND pfv.value IN ({$valuePlaceholders})
            )";

                $featureBindings[] = $featureId;
                foreach ($values as $value) {
                    $featureBindings[] = $value;
                }
            }

            $variantFeatureConditions = [];
            foreach ($featureFilters as $featureId => $values) {
                if (empty($values)) continue;

                if (!is_array($values)) {
                    $values = [$values];
                }

                $valuePlaceholders = implode(',', array_fill(0, count($values), '?'));

                $variantFeatureConditions[] = "EXISTS (
                SELECT 1 FROM product_variant_values pvv
                WHERE pvv.variant_id = v.id
                AND pvv.feature_id = ?
                AND pvv.value IN ({$valuePlaceholders})
            )";

                $featureBindings[] = $featureId;
                foreach ($values as $value) {
                    $featureBindings[] = $value;
                }
            }

            $allFeatureConditions = [];
            if (!empty($featureConditions)) {
                $allFeatureConditions[] = "(" . implode(' AND ', $featureConditions) . ")";
            }
            if (!empty($variantFeatureConditions)) {
                $allFeatureConditions[] = "(" . implode(' AND ', $variantFeatureConditions) . ")";
            }

            if (!empty($allFeatureConditions)) {
                $sql .= " AND (" . implode(' OR ', $allFeatureConditions) . ")";
                $bindings = array_merge($bindings, $featureBindings);
            }
        }

        // Count total products
        $countSql = "SELECT COUNT(DISTINCT p.id) as total FROM ({$sql}) as product_query";
        $countResult = DB::select($countSql, $bindings);
        $total = isset($countResult[0]) ? (int) $countResult[0]->total : 0;

        // Apply sorting
        $orderBy = match ($sort) {
            'oldest' => 'created_at ASC',
            'price_low' => 'price ASC',
            'price_high' => 'price DESC',
            'rating_high' => 'rating DESC',
            'discount_high' => 'discount DESC',
            default => 'created_at DESC'
        };

        // Add sorting and pagination to main query
        $fullSql = "SELECT * FROM ({$sql}) as product_query 
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $results = DB::select($fullSql, $bindings);
        $results = array_map(function ($item) {
            return (array) $item;
        }, $results);

        // Normalize results
        foreach ($results as &$row) {
            $isVariant = !empty($row['variant_id']);
            $row['sku'] = $isVariant ? $row['variant_sku'] : null;
            $row['image'] = $row['final_image'];
            $row['slug'] = $row['final_slug'];

            unset(
                $row['product_image'],
                $row['variant_sku'],
                $row['variant_slug'],
                $row['variant_price'],
                $row['variant_sale_price'],
                $row['variant_discount'],
                $row['variant_rating'],
                $row['variant_stock'],
                $row['variant_image'],
                $row['final_slug'],
                $row['final_image']
            );
        }

        return [
            'products' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get filtered products with combined product and variant logic.
     * 
     * @param array $filters
     * @return array
     */
    public function getFilteredProducts(array $filters): array
    {
        try {
            $categoryId = !empty($filters['category_id']) ? (int) $filters['category_id'] : null;
            $subcategoryId = !empty($filters['subcategory_id']) ? (int) $filters['subcategory_id'] : null;
            $minPrice = isset($filters['min_price']) ? (float) $filters['min_price'] : null;
            $maxPrice = isset($filters['max_price']) ? (float) $filters['max_price'] : null;
            $features = $filters['features'] ?? [];
            $sort = $filters['sort'] ?? 'latest';
            $page = max(1, (int) ($filters['page'] ?? 1));
            $perPage = 12;
            $offset = ($page - 1) * $perPage;

            // Non-variant products
            $sql = "SELECT 
                p.id AS product_id,
                NULL::integer AS variant_id,                p.name,
                p.slug,
                p.rating,
                p.stock,
                p.price,
                p.sale_price,
                p.discount,
                p.created_at,
                p.updated_at,
                (SELECT pi.image FROM product_images pi 
                WHERE pi.product_id = p.id 
                ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS gallery_image,
                p.image AS fallback_image,
                0 AS has_variants
            FROM products p
            WHERE p.status = 'active' 
            AND p.stock > 0
            AND NOT EXISTS (
                SELECT 1 FROM product_variants pvx 
                WHERE pvx.product_id = p.id AND pvx.status = 1
            )";

            $bindings = [];

            if ($subcategoryId) {
                $sql .= " AND p.subcategory_id = ?";
                $bindings[] = $subcategoryId;
            } elseif ($categoryId) {
                $sql .= " AND p.category_id = ?";
                $bindings[] = $categoryId;
            }

            if ($minPrice !== null && $maxPrice !== null) {
                $sql .= " AND p.price BETWEEN ? AND ?";
                $bindings[] = $minPrice;
                $bindings[] = $maxPrice;
            }

            if (!empty($features)) {
                foreach ($features as $featureId => $valueIds) {
                    if (empty($valueIds)) continue;
                    if (!is_array($valueIds)) {
                        $valueIds = [$valueIds];
                    }
                    $placeholders = implode(',', array_fill(0, count($valueIds), '?'));
                    $sql .= " AND EXISTS (
                SELECT 1 FROM product_feature_values pfv
                WHERE pfv.product_id = p.id
                AND pfv.feature_id = ?
                AND pfv.value IN ({$placeholders})
            )";
                    $bindings[] = (int) $featureId;
                    foreach ($valueIds as $vid) {
                        $bindings[] = $vid;
                    }
                }
            }

            // Variant products: pick the cheapest in-stock variant per product
            $sqlVariant = "SELECT 
                p.id AS product_id,
                v.id AS variant_id,
                p.name,
                v.slug,
                v.rating,
                v.stock,
                v.price,
                v.sale_price,
                v.discount,
                p.created_at,
                p.updated_at,
                (SELECT vi.image FROM product_variant_images vi 
                WHERE vi.variant_id = v.id 
                ORDER BY vi.is_primary DESC, vi.sort_order ASC, vi.id ASC LIMIT 1) AS gallery_image,
                p.image AS fallback_image,
                1 AS has_variants
            FROM products p
            INNER JOIN (
                SELECT pv1.*
                FROM product_variants pv1
                INNER JOIN (
                    SELECT product_id, MIN(price) AS min_price
                    FROM product_variants
                    WHERE status = 1 AND stock > 0
                    GROUP BY product_id
                ) cheapest ON cheapest.product_id = pv1.product_id
                            AND cheapest.min_price = pv1.price
                INNER JOIN (
                    SELECT product_id, price, MIN(id) AS min_id
                    FROM product_variants
                    WHERE status = 1 AND stock > 0
                    GROUP BY product_id, price
                ) tie ON tie.product_id = pv1.product_id
                       AND tie.price = pv1.price
                       AND tie.min_id = pv1.id
                WHERE pv1.status = 1 AND pv1.stock > 0
            ) v ON v.product_id = p.id
            WHERE p.status = 'active' 
            AND v.status = 1 
            AND v.stock > 0";

            $bindingsVariant = [];

            if ($subcategoryId) {
                $sqlVariant .= " AND p.subcategory_id = ?";
                $bindingsVariant[] = $subcategoryId;
            } elseif ($categoryId) {
                $sqlVariant .= " AND p.category_id = ?";
                $bindingsVariant[] = $categoryId;
            }

            if ($minPrice !== null && $maxPrice !== null) {
                $sqlVariant .= " AND v.price BETWEEN ? AND ?";
                $bindingsVariant[] = $minPrice;
                $bindingsVariant[] = $maxPrice;
            }

            if (!empty($features)) {
                foreach ($features as $featureId => $valueIds) {
                    if (empty($valueIds)) continue;
                    if (!is_array($valueIds)) {
                        $valueIds = [$valueIds];
                    }
                    $placeholders = implode(',', array_fill(0, count($valueIds), '?'));
                    $sqlVariant .= " AND EXISTS (
                SELECT 1 FROM product_variant_values pvv
                WHERE pvv.variant_id = v.id
                AND pvv.feature_id = ?
                AND pvv.value IN ({$placeholders})
            )";
                    $bindingsVariant[] = (int) $featureId;
                    foreach ($valueIds as $vid) {
                        $bindingsVariant[] = $vid;
                    }
                }
            }

            // Combine
            $unionSql = "({$sql}) UNION ALL ({$sqlVariant})";
            $allBindings = array_merge($bindings, $bindingsVariant);

            $orderBy = match ($sort) {
                'oldest' => 'created_at ASC',
                'price_low' => 'price ASC',
                'price_high' => 'price DESC',
                'rating_high' => 'rating DESC',
                'discount_high' => 'discount DESC',
                default => 'created_at DESC'
            };

            $countSql = "SELECT COUNT(*) AS total FROM ({$unionSql}) AS combined";
            $countResult = DB::select($countSql, $allBindings);
            $total = isset($countResult[0]) ? (int) $countResult[0]->total : 0;

            $pagedSql = "SELECT * FROM ({$unionSql}) AS combined 
                ORDER BY {$orderBy} 
                LIMIT {$perPage} OFFSET {$offset}";
            $rows = DB::select($pagedSql, $allBindings);
            $rows = array_map(function ($item) {
                return (array) $item;
            }, $rows);

            if (!empty($rows)) {
                $variantIds = array_values(array_filter(array_column($rows, 'variant_id')));
                $variantFeatures = [];

                if (!empty($variantIds)) {
                    $featureRows = DB::table('product_variant_values as pvv')
                        ->select('pvv.variant_id', 'features.name AS feature_name', 'feature_values.value AS feature_value')
                        ->join('features', 'features.id', '=', 'pvv.feature_id')
                        ->leftJoin('feature_values', 'feature_values.id', '=', 'pvv.value')
                        ->whereIn('pvv.variant_id', $variantIds)
                        ->get()
                        ->toArray();

                    foreach ($featureRows as $fr) {
                        $fr = (array) $fr;
                        $variantFeatures[$fr['variant_id']][] = [
                            'name' => $fr['feature_name'],
                            'value' => $fr['feature_value'] ?? '',
                        ];
                    }
                }

                foreach ($rows as &$row) {
                    $row['image'] = !empty($row['gallery_image']) ? $row['gallery_image'] : $row['fallback_image'];
                    $row['features'] = $row['variant_id'] ? ($variantFeatures[$row['variant_id']] ?? []) : [];
                    unset($row['gallery_image'], $row['fallback_image']);
                }
                unset($row);
            }

            return [
                'rows' => $rows,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ];
        } catch (\Exception $e) {
            Log::error('getFilteredProducts error: ' . $e->getMessage());
            return [
                'rows' => [],
                'total' => 0,
                'per_page' => 12,
                'current_page' => 1,
                'last_page' => 1,
            ];
        }
    }

    /**
     * Get the first available variant for a product.
     * 
     * @param int $productId
     * @return array|null
     */
    public function getFirstAvailableVariant(int $productId): ?array
    {
        $result = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->orderBy('stock', 'DESC')
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->first();

        return $result ? (array) $result : null;
    }
}