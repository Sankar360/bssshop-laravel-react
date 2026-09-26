<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $table = 'blog';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'content',
        'excerpt',
        'image',
        'status',
        'author',
        'views',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'published_at',
    ];

    protected $casts = [
        'views' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get published blog posts.
     */
    public static function getPublishedPosts(
        ?int $limit = null,
        int $offset = 0
    ) {
        $query = static::where('status', 'published')
            ->orderByDesc('created_at');

        if ($limit !== null) {
            $query->offset($offset)->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get blog posts with filters.
     */
    public static function getBlogsWithFilters(
    ?string $search = null,
    ?string $category = null,
    ?string $status = null,
    int $perPage = 20
) {
    $query = static::query();

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('title',   'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%");
        });
    }

    if ($category && $category !== 'all') {
        $query->where('category', $category);
    }

    if ($status && $status !== 'all') {
        $query->where('status', $status);
    }

    return $query->orderByDesc('created_at')->paginate($perPage);
}
    /**
     * Get blog statistics.
     */
    public static function getBlogStats(): array
{
    return [
        'total'       => static::count(),
        'published'   => static::where('status', 'published')->count(),
        'draft'       => static::where('status', 'draft')->count(),
        'archived'    => static::where('status', 'archived')->count(),
        'total_views' => (int) static::sum('views'),
    ];
}

    /**
     * Get distinct published categories.
     */
    public static function getDistinctCategories()
{
    return static::query()
        ->select('category')
        ->where('status', 'published')
        ->whereNotNull('category')
        ->distinct()
        ->orderBy('category')
        ->pluck('category');   // ← returns a Collection of strings
}

    /**
     * Increment view count.
     */
    public static function incrementViews(int $id): bool
    {
        return static::where('id', $id)
            ->increment('views');
    }

    /**
     * Get related published posts.
     */
    public static function getRelatedPosts(
        string $category,
        int $excludeId,
        int $limit = 3
    ) {
        return static::where('category', $category)
            ->where('id', '!=', $excludeId)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}