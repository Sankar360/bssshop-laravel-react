<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FAQ extends Model
{
    use HasFactory;

    protected $table = 'faqs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'question',
        'answer',
        'category',
        'status',
        'order',
    ];

    protected $casts = [
        'id'         => 'integer',
        'order'      => 'integer',
        'status'     => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    protected $hidden = [];

    protected $attributes = [
        'status' => 'active',
        'order'  => 0,
    ];

    // ==================== CONSTANTS ====================

    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';

    public static function getStatuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_ACTIVE   => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    public static function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE   => 'success',
            self::STATUS_INACTIVE => 'secondary',
            default               => 'secondary',
        };
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::getStatusBadgeClass($this->status);
    }

    public function getQuestionExcerptAttribute(): string
    {
        return strlen($this->question) > 100
            ? substr($this->question, 0, 100) . '...'
            : $this->question;
    }

    public function getAnswerExcerptAttribute(): string
    {
        return strlen($this->answer) > 200
            ? substr($this->answer, 0, 200) . '...'
            : $this->answer;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * ✅ NEW — Filter by status. Accepts 'active', 'inactive', or 'all'.
     */
    public function scopeOfStatus($query, ?string $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeOfCategory($query, ?string $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                  ->orWhere('answer', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'ASC');
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'DESC');
    }

    // ==================== HELPERS (sticky-query safe) ====================

    public function getFAQsWithFilters(?string $search = null, ?string $category = null): array
    {
        return self::query()
            ->search($search)
            ->ofCategory($category)
            ->ordered()
            ->get()
            ->toArray();
    }

    public function getFAQsWithFiltersCollection(?string $search = null, ?string $category = null): Collection
    {
        return self::query()
            ->search($search)
            ->ofCategory($category)
            ->ordered()
            ->get();
    }

    public function getFAQStats(): array
    {
        return [
            'total'    => self::query()->count(),
            'active'   => self::query()->where('status', self::STATUS_ACTIVE)->count(),
            'inactive' => self::query()->where('status', self::STATUS_INACTIVE)->count(),
        ];
    }

    public function getDistinctCategories(): array
    {
        return self::query()
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category', 'ASC')
            ->pluck('category')
            ->toArray();
    }

    public function getCategoriesWithCounts(): Collection
    {
        return self::query()
            ->select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category', 'ASC')
            ->get();
    }

    public function getActiveFAQsGroupedByCategory(): Collection
    {
        return self::query()
            ->where('status', self::STATUS_ACTIVE)
            ->ordered()
            ->get()
            ->groupBy('category');
    }

    public function getFAQsByCategory(string $category, bool $activeOnly = true): Collection
    {
        $query = self::query()->where('category', $category);

        if ($activeOnly) {
            $query->where('status', self::STATUS_ACTIVE);
        }

        return $query->ordered()->get();
    }

    public function getPaginatedFAQs(?string $search = null, ?string $category = null, int $perPage = 20)
    {
        return self::query()
            ->search($search)
            ->ofCategory($category)
            ->ordered()
            ->paginate($perPage);
    }

    public function getNextOrder(): int
    {
        $max = self::query()->max('order');
        return ($max ?? 0) + 1;
    }

    public function reorderFAQs(array $orderedIds): bool
    {
        return DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                self::query()
                    ->where('id', $id)
                    ->update(['order' => $index + 1]);
            }
            return true;
        });
    }

    public function toggleStatus(int $id): bool
    {
        $faq = self::query()->find($id);

        if (!$faq) {
            return false;
        }

        $newStatus = $faq->status === self::STATUS_ACTIVE
            ? self::STATUS_INACTIVE
            : self::STATUS_ACTIVE;

        return (bool) self::query()
            ->where('id', $id)
            ->update(['status' => $newStatus]);
    }

    public function bulkUpdateStatus(array $ids, string $status): int
    {
        if (!in_array($status, self::getStatuses(), true)) {
            return 0;
        }

        return self::query()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    public function bulkDelete(array $ids): int
    {
        return self::query()->whereIn('id', $ids)->delete();
    }

    public function questionExists(string $question, ?int $excludeId = null): bool
    {
        $query = self::query()->where('question', $question);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function countByCategory(string $category, bool $activeOnly = true): int
    {
        $query = self::query()->where('category', $category);

        if ($activeOnly) {
            $query->where('status', self::STATUS_ACTIVE);
        }

        return $query->count();
    }

    public function getRandomFAQs(int $limit = 5, bool $activeOnly = true): Collection
    {
        $query = self::query()->inRandomOrder();

        if ($activeOnly) {
            $query->where('status', self::STATUS_ACTIVE);
        }

        return $query->limit($limit)->get();
    }

    public function searchFAQsWithRelevance(string $search, int $limit = 10): Collection
    {
        return self::query()
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                  ->orWhere('answer', 'LIKE', "%{$search}%");
            })
            ->select('*')
            ->selectRaw(
                "CASE
                    WHEN question LIKE ? THEN 10
                    WHEN answer LIKE ? THEN 5
                    ELSE 1
                END as relevance",
                ["%{$search}%", "%{$search}%"]
            )
            ->orderBy('relevance', 'DESC')
            ->orderBy('order', 'ASC')
            ->limit($limit)
            ->get();
    }

    public function getActiveCategoriesWithCounts(): array
    {
        return self::query()
            ->where('status', self::STATUS_ACTIVE)
            ->select('category', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category', 'ASC')
            ->get()
            ->toArray();
    }

    public function cloneFAQ(int $id, array $overrides = []): ?FAQ
    {
        $faq = self::query()->find($id);

        if (!$faq) {
            return null;
        }

        $newFaq = $faq->replicate();
        $newFaq->fill($overrides);
        $newFaq->order = $this->getNextOrder();
        $newFaq->save();

        return $newFaq;
    }

    public function exportFAQs(?string $category = null): array
    {
        $query = self::query()->ordered();

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        return $query->get()
            ->map(function ($faq) {
                return [
                    'ID'         => $faq->id,
                    'Question'   => $faq->question,
                    'Answer'     => $faq->answer,
                    'Category'   => $faq->category,
                    'Status'     => $faq->status_label,
                    'Order'      => $faq->order,
                    'Created At' => $faq->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $faq->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }
}