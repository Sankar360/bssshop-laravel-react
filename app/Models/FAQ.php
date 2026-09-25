<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FAQ extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'faqs';

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
        'question',
        'answer',
        'category',
        'status',
        'order',
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
        'order' => 'integer',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
        'order' => 0,
    ];

    // ==================== CONSTANTS ====================

    /**
     * Status constants.
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get all available statuses.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }

    /**
     * Get statuses with labels for display.
     *
     * @return array
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * Get status badge color for UI.
     *
     * @param string $status
     * @return string
     */
    public static function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            default => 'secondary',
        };
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the status badge class attribute.
     */
    public function getStatusBadgeAttribute(): string
    {
        return self::getStatusBadgeClass($this->status);
    }

    /**
     * Get the truncated question attribute.
     */
    public function getQuestionExcerptAttribute(): string
    {
        return strlen($this->question) > 100 
            ? substr($this->question, 0, 100) . '...' 
            : $this->question;
    }

    /**
     * Get the truncated answer attribute.
     */
    public function getAnswerExcerptAttribute(): string
    {
        return strlen($this->answer) > 200 
            ? substr($this->answer, 0, 200) . '...' 
            : $this->answer;
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include active FAQs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to only include inactive FAQs.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeOfCategory($query, ?string $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Scope to search FAQs.
     */
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

    /**
     * Scope to order by the order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'ASC');
    }

    /**
     * Scope to order by created at descending.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'DESC');
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get FAQs with optional search and category filtering.
     * 
     * @param string|null $search Search term
     * @param string|null $category Category filter
     * @return array
     */
    public function getFAQsWithFilters(?string $search = null, ?string $category = null): array
    {
        return $this->search($search)
            ->ofCategory($category)
            ->ordered()
            ->get()
            ->toArray();
    }

    /**
     * Get FAQs with filters as a collection.
     * 
     * @param string|null $search
     * @param string|null $category
     * @return Collection
     */
    public function getFAQsWithFiltersCollection(?string $search = null, ?string $category = null): Collection
    {
        return $this->search($search)
            ->ofCategory($category)
            ->ordered()
            ->get();
    }

    /**
     * Get FAQ statistics.
     * 
     * @return array
     */
    public function getFAQStats(): array
    {
        return [
            'total' => $this->count(),
            'active' => $this->active()->count(),
            'inactive' => $this->inactive()->count(),
        ];
    }

    /**
     * Get distinct categories.
     * 
     * @return array
     */
    public function getDistinctCategories(): array
    {
        return $this->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category', 'ASC')
            ->get()
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get distinct categories with counts.
     * 
     * @return Collection
     */
    public function getCategoriesWithCounts(): Collection
    {
        return $this->select('category', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category', 'ASC')
            ->get();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get active FAQs grouped by category.
     * 
     * @return Collection
     */
    public function getActiveFAQsGroupedByCategory(): Collection
    {
        return $this->active()
            ->ordered()
            ->get()
            ->groupBy('category');
    }

    /**
     * Get FAQs by category.
     * 
     * @param string $category
     * @param bool $activeOnly
     * @return Collection
     */
    public function getFAQsByCategory(string $category, bool $activeOnly = true): Collection
    {
        $query = $this->where('category', $category);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get FAQs with pagination.
     * 
     * @param string|null $search
     * @param string|null $category
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedFAQs(?string $search = null, ?string $category = null, int $perPage = 20)
    {
        return $this->search($search)
            ->ofCategory($category)
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Get the next available order number.
     * 
     * @return int
     */
    public function getNextOrder(): int
    {
        $max = $this->max('order');
        return ($max ?? 0) + 1;
    }

    /**
     * Reorder FAQs.
     * 
     * @param array $orderedIds Array of FAQ IDs in desired order
     * @return bool
     */
    public function reorderFAQs(array $orderedIds): bool
    {
        return \DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                $this->where('id', $id)
                    ->update(['order' => $index + 1]);
            }
            return true;
        });
    }

    /**
     * Toggle FAQ status.
     * 
     * @param int $id
     * @return bool
     */
    public function toggleStatus(int $id): bool
    {
        $faq = $this->find($id);
        
        if (!$faq) {
            return false;
        }

        $newStatus = $faq->status === self::STATUS_ACTIVE 
            ? self::STATUS_INACTIVE 
            : self::STATUS_ACTIVE;

        return (bool) $this->where('id', $id)
            ->update(['status' => $newStatus]);
    }

    /**
     * Bulk update status for FAQs.
     * 
     * @param array $ids
     * @param string $status
     * @return int Number of affected rows
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        if (!in_array($status, self::getStatuses())) {
            return 0;
        }

        return $this->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    /**
     * Bulk delete FAQs.
     * 
     * @param array $ids
     * @return int Number of deleted rows
     */
    public function bulkDelete(array $ids): int
    {
        return $this->whereIn('id', $ids)->delete();
    }

    /**
     * Check if an FAQ exists by question.
     * 
     * @param string $question
     * @param int|null $excludeId
     * @return bool
     */
    public function questionExists(string $question, ?int $excludeId = null): bool
    {
        $query = $this->where('question', $question);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get FAQs count by category.
     * 
     * @param string $category
     * @param bool $activeOnly
     * @return int
     */
    public function countByCategory(string $category, bool $activeOnly = true): int
    {
        $query = $this->where('category', $category);

        if ($activeOnly) {
            $query->active();
        }

        return $query->count();
    }

    /**
     * Get random FAQs.
     * 
     * @param int $limit
     * @param bool $activeOnly
     * @return Collection
     */
    public function getRandomFAQs(int $limit = 5, bool $activeOnly = true): Collection
    {
        $query = $this->inRandomOrder();

        if ($activeOnly) {
            $query->active();
        }

        return $query->limit($limit)->get();
    }

    /**
     * Search FAQs with relevance scoring.
     * 
     * @param string $search
     * @param int $limit
     * @return Collection
     */
    public function searchFAQsWithRelevance(string $search, int $limit = 10): Collection
    {
        return $this->where('status', self::STATUS_ACTIVE)
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

    /**
     * Get categories with active FAQ counts.
     * 
     * @return array
     */
    public function getActiveCategoriesWithCounts(): array
    {
        return $this->active()
            ->select('category', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderBy('category', 'ASC')
            ->get()
            ->toArray();
    }

    /**
     * Clone FAQ to a new one.
     * 
     * @param int $id
     * @param array $overrides
     * @return FAQ|null
     */
    public function cloneFAQ(int $id, array $overrides = []): ?FAQ
    {
        $faq = $this->find($id);
        
        if (!$faq) {
            return null;
        }

        $newFaq = $faq->replicate();
        $newFaq->fill($overrides);
        $newFaq->order = $this->getNextOrder();
        $newFaq->save();

        return $newFaq;
    }

    /**
     * Export FAQs to array for CSV/Excel.
     * 
     * @param string|null $category
     * @return array
     */
    public function exportFAQs(?string $category = null): array
    {
        $query = $this->ordered();

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        return $query->get()
            ->map(function ($faq) {
                return [
                    'ID' => $faq->id,
                    'Question' => $faq->question,
                    'Answer' => $faq->answer,
                    'Category' => $faq->category,
                    'Status' => $faq->status_label,
                    'Order' => $faq->order,
                    'Created At' => $faq->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $faq->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }
}