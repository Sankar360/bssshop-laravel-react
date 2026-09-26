<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoices';

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
        'invoice_number',
        'user_id',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'discount',
        'total',
        'status',
        'notes',
        'payment_method',
        'payment_date',
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
        'order_id' => 'integer',
        'user_id' => 'integer',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string',
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
        'status' => 'unpaid',
        'tax' => 0,
        'discount' => 0,
        'total' => 0,
    ];

    // ==================== CONSTANTS ====================

    /**
     * Status constants.
     */
    const STATUS_PAID = 'paid';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get all available statuses.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PAID,
            self::STATUS_UNPAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
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
            self::STATUS_PAID => 'Paid',
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_CANCELLED => 'Cancelled',
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
            self::STATUS_PAID => 'success',
            self::STATUS_UNPAID => 'warning',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the order that owns the invoice.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Get the user that owns the invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
     * Get the formatted issue date attribute.
     */
    public function getFormattedIssueDateAttribute(): string
    {
        return $this->issue_date ? $this->issue_date->format('M d, Y') : '';
    }

    /**
     * Get the formatted due date attribute.
     */
    public function getFormattedDueDateAttribute(): string
    {
        return $this->due_date ? $this->due_date->format('M d, Y') : '';
    }

    /**
     * Get the formatted total attribute.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Get the formatted subtotal attribute.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get the formatted tax attribute.
     */
    public function getFormattedTaxAttribute(): string
    {
        return '$' . number_format($this->tax, 2);
    }

    /**
     * Get the formatted discount attribute.
     */
    public function getFormattedDiscountAttribute(): string
    {
        return '$' . number_format($this->discount, 2);
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_PAID && 
               $this->status !== self::STATUS_CANCELLED && 
               $this->due_date && 
               $this->due_date->isPast();
    }

    /**
     * Check if invoice is paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is unpaid.
     */
    public function getIsUnpaidAttribute(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    /**
     * Get days until due date.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, ?string $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to only include unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    /**
     * Scope to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    /**
     * Scope to only include cancelled invoices.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, ?string $dateFrom, ?string $dateTo)
    {
        if ($dateFrom) {
            $query->where('issue_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('issue_date', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Scope to search invoices.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('order', function ($q2) use ($search) {
                        $q2->where('order_number', 'LIKE', "%{$search}%");
                    });
            });
        }
        return $query;
    }

    /**
     * Scope to order by latest first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'DESC');
    }

    /**
     * Scope to include relationships.
     */
    public function scopeWithDetails($query)
    {
        return $query->with(['order', 'user']);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Generate a unique invoice number.
     * Format: INV-YYYYMMXXXXXX
     * 
     * @return string
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = date('Y');
        $month = date('m');

        // Get last invoice number
        $lastInvoice = $this->orderBy('id', 'DESC')->first();

        if ($lastInvoice) {
            $lastNumber = intval(substr($lastInvoice->invoice_number, -6));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return $prefix . $year . $month . $newNumber;
    }

    /**
     * Get invoice with details (order and user).
     * 
     * @param int $id
     * @return array|null
     */
    public function getInvoiceWithDetails(int $id): ?array
    {
        $invoice = $this->with(['order', 'user'])
            ->find($id);

        if (!$invoice) {
            return null;
        }

        $result = $invoice->toArray();
        
        // Add order number directly for convenience
        if ($invoice->order) {
            $result['order_number'] = $invoice->order->order_number;
        }
        
        // Add user details for convenience
        if ($invoice->user) {
            $result['user_name'] = $invoice->user->name;
            $result['user_email'] = $invoice->user->email;
            $result['user_phone'] = $invoice->user->phone ?? null;
        }

        return $result;
    }

    /**
     * Get invoice as model with relationships loaded.
     * 
     * @param int $id
     * @return Invoice|null
     */
    public function getInvoiceWithDetailsModel(int $id): ?Invoice
    {
        return $this->with(['order', 'user'])->find($id);
    }

    /**
     * Get invoices with optional filters.
     * 
     * @param string|null $search
     * @param string|null $status
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getInvoicesWithFilters(?string $search = null, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->search($search)
            ->ofStatus($status)
            ->dateRange($dateFrom, $dateTo)
            ->withDetails()
            ->latestFirst()
            ->get()
            ->toArray();
    }

    /**
     * Get invoices with filters as a collection.
     * 
     * @param string|null $search
     * @param string|null $status
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return Collection
     */
    public function getInvoicesWithFiltersCollection(?string $search = null, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return $this->search($search)
            ->ofStatus($status)
            ->dateRange($dateFrom, $dateTo)
            ->withDetails()
            ->latestFirst()
            ->get();
    }

    /**
     * Get invoice statistics.
     * 
     * @return array
     */
    public function getInvoiceStats(): array
    {
        return [
            'total' => $this->count(),
            'paid' => $this->paid()->count(),
            'unpaid' => $this->unpaid()->count(),
            'overdue' => $this->overdue()->count(),
            'cancelled' => $this->cancelled()->count(),
            'total_amount' => $this->sum('total') ?? 0,
            'paid_amount' => $this->paid()->sum('total') ?? 0,
            'unpaid_amount' => $this->unpaid()->sum('total') ?? 0,
            'overdue_amount' => $this->overdue()->sum('total') ?? 0,
        ];
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get invoices by user.
     * 
     * @param int $userId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getInvoicesByUser(int $userId, bool $activeOnly = false): Collection
    {
        $query = $this->where('user_id', $userId)
            ->latestFirst();

        if ($activeOnly) {
            $query->whereNotIn('status', [self::STATUS_CANCELLED]);
        }

        return $query->get();
    }

    /**
     * Get invoices by order.
     * 
     * @param int $orderId
     * @return Invoice|null
     */
    public function getInvoiceByOrder(int $orderId): ?Invoice
    {
        return $this->where('order_id', $orderId)->first();
    }

    /**
     * Mark invoice as paid.
     * 
     * @param int $id
     * @param string|null $paymentMethod
     * @param string|null $paymentDate
     * @return bool
     */
    public function markAsPaid(int $id, ?string $paymentMethod = null, ?string $paymentDate = null): bool
    {
        return (bool) $this->where('id', $id)->update([
            'status' => self::STATUS_PAID,
            'payment_method' => $paymentMethod,
            'payment_date' => $paymentDate ?? now(),
        ]);
    }

    /**
     * Mark invoice as overdue.
     * 
     * @param int $id
     * @return bool
     */
    public function markAsOverdue(int $id): bool
    {
        return (bool) $this->where('id', $id)
            ->update(['status' => self::STATUS_OVERDUE]);
    }

    /**
     * Mark invoice as cancelled.
     * 
     * @param int $id
     * @return bool
     */
    public function markAsCancelled(int $id): bool
    {
        return (bool) $this->where('id', $id)
            ->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Get invoices with pagination.
     * 
     * @param string|null $search
     * @param string|null $status
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedInvoices(?string $search = null, ?string $status = null, int $perPage = 20)
    {
        return $this->search($search)
            ->ofStatus($status)
            ->withDetails()
            ->latestFirst()
            ->paginate($perPage);
    }

    /**
     * Check for overdue invoices and update status.
     * 
     * @return int Number of invoices marked as overdue
     */
    public function checkOverdueInvoices(): int
    {
        $count = 0;

        // Find unpaid invoices where due date is in the past
        $overdueInvoices = $this->where('status', self::STATUS_UNPAID)
            ->where('due_date', '<', now()->toDateString())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->status = self::STATUS_OVERDUE;
            $invoice->save();
            $count++;
        }

        return $count;
    }

    /**
     * Get invoice total by period.
     * 
     * @param string $period (daily, weekly, monthly, yearly)
     * @param bool $onlyPaid
     * @return float
     */
    public function getTotalByPeriod(string $period = 'monthly', bool $onlyPaid = true): float
    {
        $query = $this->query();

        if ($onlyPaid) {
            $query->paid();
        }

        return match ($period) {
            'daily' => $query->whereDate('issue_date', today())->sum('total'),
            'weekly' => $query->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('total'),
            'monthly' => $query->whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->sum('total'),
            'yearly' => $query->whereYear('issue_date', now()->year)->sum('total'),
            default => $query->sum('total'),
        };
    }

    /**
     * Get invoice statistics by month.
     * 
     * @param int $months
     * @return Collection
     */
    public function getMonthlyStats(int $months = 12): Collection
    {
        $startDate = now()->subMonths($months)->startOfMonth();

        return $this->where('issue_date', '>=', $startDate)
            ->select(\DB::raw('YEAR(issue_date) as year, MONTH(issue_date) as month'))
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total) as total')
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as paid_total")
            ->groupBy('year', 'month')
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->get();
    }

    /**
     * Get invoice by invoice number.
     * 
     * @param string $invoiceNumber
     * @return Invoice|null
     */
    public function getInvoiceByNumber(string $invoiceNumber): ?Invoice
    {
        return $this->where('invoice_number', $invoiceNumber)->first();
    }

    /**
     * Check if invoice number exists.
     * 
     * @param string $invoiceNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function invoiceNumberExists(string $invoiceNumber, ?int $excludeId = null): bool
    {
        $query = $this->where('invoice_number', $invoiceNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Export invoices to array for CSV/Excel.
     * 
     * @param string|null $status
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function exportInvoices(?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->ofStatus($status)
            ->dateRange($dateFrom, $dateTo)
            ->with(['order', 'user'])
            ->latestFirst()
            ->get()
            ->map(function ($invoice) {
                return [
                    'Invoice Number' => $invoice->invoice_number,
                    'Order Number' => $invoice->order?->order_number,
                    'Customer' => $invoice->user?->name,
                    'Email' => $invoice->user?->email,
                    'Issue Date' => $invoice->formatted_issue_date,
                    'Due Date' => $invoice->formatted_due_date,
                    'Subtotal' => $invoice->formatted_subtotal,
                    'Tax' => $invoice->formatted_tax,
                    'Discount' => $invoice->formatted_discount,
                    'Total' => $invoice->formatted_total,
                    'Status' => $invoice->status_label,
                    'Payment Method' => $invoice->payment_method,
                    'Payment Date' => $invoice->payment_date?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get invoice summary for dashboard.
     * 
     * @return array
     */
    public function getDashboardSummary(): array
    {
        $today = today();

        return [
            'today_count' => $this->whereDate('issue_date', $today)->count(),
            'today_total' => $this->whereDate('issue_date', $today)->sum('total'),
            'week_count' => $this->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'week_total' => $this->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('total'),
            'month_count' => $this->whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->count(),
            'month_total' => $this->whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->sum('total'),
            'overdue_count' => $this->overdue()->count(),
            'overdue_total' => $this->overdue()->sum('total'),
        ];
    }

    /**
     * Get uncollectable invoices (old overdue invoices).
     * 
     * @param int $days
     * @return Collection
     */
    public function getUncollectableInvoices(int $days = 90): Collection
    {
        $cutoffDate = now()->subDays($days);

        return $this->where('status', self::STATUS_OVERDUE)
            ->where('due_date', '<', $cutoffDate)
            ->get();
    }

    /**
     * Send invoice notification (placeholder).
     * 
     * @param string $type (email, sms)
     * @return bool
     */
    public function sendNotification(string $type = 'email'): bool
    {
        // This is a placeholder - implement notification logic here
        // You can use Laravel's Mail facade or notification system
        return true;
    }
}