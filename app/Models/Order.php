<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

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
        'order_number',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'payment_method_display',
        'total',
        'payment_status',
        'order_status',
        'shipping_address',
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
        'user_id' => 'integer',
        'total' => 'decimal:2',
        'payment_status' => 'string',
        'order_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'shipping_address' => 'array',
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
        'payment_status' => 'pending',
        'order_status' => 'pending',
    ];

    // ==================== CONSTANTS ====================

    /**
     * Payment status constants.
     */
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    /**
     * Order status constants.
     */
    const ORDER_PENDING = 'pending';
    const ORDER_PROCESSING = 'processing';
    const ORDER_SHIPPED = 'shipped';
    const ORDER_DELIVERED = 'delivered';
    const ORDER_CANCELLED = 'cancelled';
    const ORDER_REFUNDED = 'refunded';

    /**
     * Get all available payment statuses.
     *
     * @return array
     */
    public static function getPaymentStatuses(): array
    {
        return [
            self::PAYMENT_PENDING,
            self::PAYMENT_PAID,
            self::PAYMENT_FAILED,
            self::PAYMENT_REFUNDED,
        ];
    }

    /**
     * Get all available order statuses.
     *
     * @return array
     */
    public static function getOrderStatuses(): array
    {
        return [
            self::ORDER_PENDING,
            self::ORDER_PROCESSING,
            self::ORDER_SHIPPED,
            self::ORDER_DELIVERED,
            self::ORDER_CANCELLED,
            self::ORDER_REFUNDED,
        ];
    }

    /**
     * Get payment status labels.
     *
     * @return array
     */
    public static function getPaymentStatusLabels(): array
    {
        return [
            self::PAYMENT_PENDING => 'Pending',
            self::PAYMENT_PAID => 'Paid',
            self::PAYMENT_FAILED => 'Failed',
            self::PAYMENT_REFUNDED => 'Refunded',
        ];
    }

    /**
     * Get order status labels.
     *
     * @return array
     */
    public static function getOrderStatusLabels(): array
    {
        return [
            self::ORDER_PENDING => 'Pending',
            self::ORDER_PROCESSING => 'Processing',
            self::ORDER_SHIPPED => 'Shipped',
            self::ORDER_DELIVERED => 'Delivered',
            self::ORDER_CANCELLED => 'Cancelled',
            self::ORDER_REFUNDED => 'Refunded',
        ];
    }

    /**
     * Get payment status badge class.
     *
     * @param string $status
     * @return string
     */
    public static function getPaymentStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::PAYMENT_PENDING => 'warning',
            self::PAYMENT_PAID => 'success',
            self::PAYMENT_FAILED => 'danger',
            self::PAYMENT_REFUNDED => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get order status badge class.
     *
     * @param string $status
     * @return string
     */
    public static function getOrderStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::ORDER_PENDING => 'warning',
            self::ORDER_PROCESSING => 'info',
            self::ORDER_SHIPPED => 'primary',
            self::ORDER_DELIVERED => 'success',
            self::ORDER_CANCELLED => 'danger',
            self::ORDER_REFUNDED => 'secondary',
            default => 'secondary',
        };
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * Get the invoice for the order.
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'order_id', 'id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the payment status label attribute.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return self::getPaymentStatusLabels()[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    /**
     * Get the order status label attribute.
     */
    public function getOrderStatusLabelAttribute(): string
    {
        return self::getOrderStatusLabels()[$this->order_status] ?? ucfirst($this->order_status);
    }

    /**
     * Get the payment status badge class attribute.
     */
    public function getPaymentStatusBadgeAttribute(): string
    {
        return self::getPaymentStatusBadgeClass($this->payment_status);
    }

    /**
     * Get the order status badge class attribute.
     */
    public function getOrderStatusBadgeAttribute(): string
    {
        return self::getOrderStatusBadgeClass($this->order_status);
    }

    /**
     * Get the formatted total attribute.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Get the formatted created at attribute.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('M d, Y H:i') : '';
    }

    /**
     * Get the shipping address as string.
     */
    public function getShippingAddressStringAttribute(): string
    {
        if (is_array($this->shipping_address)) {
            return implode(', ', array_filter($this->shipping_address));
        }
        return (string) $this->shipping_address;
    }

    /**
     * Check if order is paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    /**
     * Check if order is cancelled.
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->order_status === self::ORDER_CANCELLED;
    }

    /**
     * Check if order is delivered.
     */
    public function getIsDeliveredAttribute(): bool
    {
        return $this->order_status === self::ORDER_DELIVERED;
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by payment status.
     */
    public function scopePaymentStatus($query, ?string $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('payment_status', $status);
        }
        return $query;
    }

    /**
     * Scope to filter by order status.
     */
    public function scopeOrderStatus($query, ?string $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('order_status', $status);
        }
        return $query;
    }

    /**
     * Scope to only include paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    /**
     * Scope to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('order_status', self::ORDER_PENDING);
    }

    /**
     * Scope to only include processing orders.
     */
    public function scopeProcessing($query)
    {
        return $query->where('order_status', self::ORDER_PROCESSING);
    }

    /**
     * Scope to only include shipped orders.
     */
    public function scopeShipped($query)
    {
        return $query->where('order_status', self::ORDER_SHIPPED);
    }

    /**
     * Scope to only include delivered orders.
     */
    public function scopeDelivered($query)
    {
        return $query->where('order_status', self::ORDER_DELIVERED);
    }

    /**
     * Scope to only include cancelled orders.
     */
    public function scopeCancelled($query)
    {
        return $query->where('order_status', self::ORDER_CANCELLED);
    }

    /**
     * Scope to order by latest first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'DESC');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, ?string $dateFrom, ?string $dateTo)
    {
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Scope to search orders.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhere('razorpay_order_id', 'LIKE', "%{$search}%")
                    ->orWhere('razorpay_payment_id', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }
        return $query;
    }

    /**
     * Scope to include user relationship.
     */
    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    /**
     * Scope to include items relationship.
     */
    public function scopeWithItems($query)
    {
        return $query->with('items');
    }

    /**
     * Scope to include all relationships.
     */
    public function scopeWithDetails($query)
    {
        return $query->with(['user', 'items']);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get orders with user details.
     * 
     * @return array
     */
    public function getOrdersWithUser(): array
    {
        return $this->with('user')
            ->latestFirst()
            ->get()
            ->map(function ($order) {
                $data = $order->toArray();
                $data['name'] = $order->user?->name;
                $data['email'] = $order->user?->email;
                return $data;
            })
            ->toArray();
    }

    /**
     * Get orders with user details as collection.
     * 
     * @return Collection
     */
    public function getOrdersWithUserCollection(): Collection
    {
        return $this->with('user')
            ->latestFirst()
            ->get();
    }

    /**
     * Get order details with user.
     * 
     * @param int $orderId
     * @return array|null
     */
    public function getOrderDetails(int $orderId): ?array
    {
        $order = $this->with('user')->find($orderId);

        if (!$order) {
            return null;
        }

        $data = $order->toArray();
        $data['name'] = $order->user?->name;
        $data['email'] = $order->user?->email;

        return $data;
    }

    /**
     * Get order details as model with relationships.
     * 
     * @param int $orderId
     * @return Order|null
     */
    public function getOrderDetailsModel(int $orderId): ?Order
    {
        return $this->with(['user', 'items'])->find($orderId);
    }

    /**
     * Get order by Razorpay order ID.
     * 
     * @param string $razorpayOrderId
     * @return Order|null
     */
    public function getOrderByRazorpayId(string $razorpayOrderId): ?Order
    {
        return $this->where('razorpay_order_id', $razorpayOrderId)->first();
    }

    /**
     * Get order by Razorpay payment ID.
     * 
     * @param string $razorpayPaymentId
     * @return Order|null
     */
    public function getOrderByRazorpayPaymentId(string $razorpayPaymentId): ?Order
    {
        return $this->where('razorpay_payment_id', $razorpayPaymentId)->first();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Generate a unique order number.
     * 
     * @return string
     */
    public function generateOrderNumber(): string
    {
        $prefix = 'ORD-';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . $date . '-' . $random;
    }

    /**
     * Get orders by user.
     * 
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getOrdersByUser(int $userId, int $limit = 10): Collection
    {
        return $this->where('user_id', $userId)
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Get orders with pagination.
     * 
     * @param string|null $search
     * @param string|null $paymentStatus
     * @param string|null $orderStatus
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedOrders(
        ?string $search = null,
        ?string $paymentStatus = null,
        ?string $orderStatus = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 20
    ) {
        return $this->search($search)
            ->paymentStatus($paymentStatus)
            ->orderStatus($orderStatus)
            ->dateRange($dateFrom, $dateTo)
            ->withUser()
            ->latestFirst()
            ->paginate($perPage);
    }

    /**
     * Get order statistics.
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_orders' => $this->count(),
            'total_revenue' => $this->sum('total') ?? 0,
            'paid_orders' => $this->paid()->count(),
            'pending_orders' => $this->pending()->count(),
            'processing_orders' => $this->processing()->count(),
            'shipped_orders' => $this->shipped()->count(),
            'delivered_orders' => $this->delivered()->count(),
            'cancelled_orders' => $this->cancelled()->count(),
            'paid_revenue' => $this->paid()->sum('total') ?? 0,
        ];
    }

    /**
     * Update order payment status.
     * 
     * @param int $id
     * @param string $paymentStatus
     * @param array $additionalData
     * @return bool
     */
    public function updatePaymentStatus(int $id, string $paymentStatus, array $additionalData = []): bool
    {
        $data = ['payment_status' => $paymentStatus];

        if ($paymentStatus === self::PAYMENT_PAID) {
            $data['order_status'] = self::ORDER_PROCESSING;
        }

        return (bool) $this->where('id', $id)->update(array_merge($data, $additionalData));
    }

    /**
     * Update order status.
     * 
     * @param int $id
     * @param string $orderStatus
     * @return bool
     */
    public function updateOrderStatus(int $id, string $orderStatus): bool
    {
        return (bool) $this->where('id', $id)
            ->update(['order_status' => $orderStatus]);
    }

    /**
     * Cancel order.
     * 
     * @param int $id
     * @param string|null $reason
     * @return bool
     */
    public function cancelOrder(int $id, ?string $reason = null): bool
    {
        return (bool) $this->where('id', $id)
            ->update([
                'order_status' => self::ORDER_CANCELLED,
                // You can add a 'cancellation_reason' field if needed
            ]);
    }

    /**
     * Get daily order statistics.
     * 
     * @param int $days
     * @return Collection
     */
    public function getDailyStats(int $days = 30): Collection
    {
        return $this->where('created_at', '>=', now()->subDays($days))
            ->select(\DB::raw('DATE(created_at) as date'))
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total) as total_revenue')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
    }

    /**
     * Get monthly order statistics.
     * 
     * @param int $months
     * @return Collection
     */
    public function getMonthlyStats(int $months = 12): Collection
    {
        return $this->where('created_at', '>=', now()->subMonths($months))
            ->select(\DB::raw('YEAR(created_at) as year, MONTH(created_at) as month'))
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total) as total_revenue')
            ->groupBy('year', 'month')
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->get();
    }

    /**
     * Get order items with product details.
     * 
     * @param int $orderId
     * @return Collection
     */
    public function getOrderItemsWithDetails(int $orderId): Collection
    {
        return (new OrderItem())->getOrderItemsWithDetails($orderId);
    }

    /**
     * Get orders by payment method.
     * 
     * @param string $paymentMethod
     * @param int $limit
     * @return Collection
     */
    public function getOrdersByPaymentMethod(string $paymentMethod, int $limit = 100): Collection
    {
        return $this->where('payment_method_display', $paymentMethod)
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Get total revenue by date range.
     * 
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    public function getRevenueByDateRange(string $startDate, string $endDate): float
    {
        return $this->paid()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total') ?? 0;
    }

    /**
     * Check if order can be cancelled.
     * 
     * @param int $id
     * @return bool
     */
    public function canBeCancelled(int $id): bool
    {
        $order = $this->find($id);

        if (!$order) {
            return false;
        }

        return !in_array($order->order_status, [
            self::ORDER_SHIPPED,
            self::ORDER_DELIVERED,
            self::ORDER_CANCELLED,
            self::ORDER_REFUNDED,
        ]);
    }

    /**
     * Export orders to array for CSV/Excel.
     * 
     * @param string|null $paymentStatus
     * @param string|null $orderStatus
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function exportOrders(
        ?string $paymentStatus = null,
        ?string $orderStatus = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        return $this->paymentStatus($paymentStatus)
            ->orderStatus($orderStatus)
            ->dateRange($dateFrom, $dateTo)
            ->withUser()
            ->latestFirst()
            ->get()
            ->map(function ($order) {
                return [
                    'Order Number' => $order->order_number,
                    'Customer' => $order->user?->name,
                    'Email' => $order->user?->email,
                    'Total' => $order->formatted_total,
                    'Payment Status' => $order->payment_status_label,
                    'Order Status' => $order->order_status_label,
                    'Payment Method' => $order->payment_method_display,
                    'Razorpay Order ID' => $order->razorpay_order_id,
                    'Razorpay Payment ID' => $order->razorpay_payment_id,
                    'Created At' => $order->formatted_created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get order by order number.
     * 
     * @param string $orderNumber
     * @return Order|null
     */
    public function getOrderByNumber(string $orderNumber): ?Order
    {
        return $this->where('order_number', $orderNumber)->first();
    }

    /**
     * Check if order number exists.
     * 
     * @param string $orderNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function orderNumberExists(string $orderNumber, ?int $excludeId = null): bool
    {
        $query = $this->where('order_number', $orderNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get latest orders for dashboard.
     * 
     * @param int $limit
     * @return Collection
     */
    public function getLatestOrders(int $limit = 10): Collection
    {
        return $this->withUser()
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Get orders by user email.
     * 
     * @param string $email
     * @param int $limit
     * @return Collection
     */
    public function getOrdersByEmail(string $email, int $limit = 10): Collection
    {
        return $this->whereHas('user', function ($q) use ($email) {
            $q->where('email', $email);
        })
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Get total orders and revenue for dashboard.
     * 
     * @return array
     */
    public function getDashboardSummary(): array
    {
        $today = today();
        $weekAgo = now()->subDays(7);
        $monthAgo = now()->subMonth();

        return [
            'today' => [
                'orders' => $this->whereDate('created_at', $today)->count(),
                'revenue' => $this->whereDate('created_at', $today)->sum('total') ?? 0,
            ],
            'week' => [
                'orders' => $this->where('created_at', '>=', $weekAgo)->count(),
                'revenue' => $this->where('created_at', '>=', $weekAgo)->sum('total') ?? 0,
            ],
            'month' => [
                'orders' => $this->where('created_at', '>=', $monthAgo)->count(),
                'revenue' => $this->where('created_at', '>=', $monthAgo)->sum('total') ?? 0,
            ],
        ];
    }

    /**
     * Update Razorpay payment details.
     * 
     * @param int $id
     * @param string $razorpayPaymentId
     * @param string|null $razorpaySignature
     * @param string $paymentStatus
     * @return bool
     */
    public function updateRazorpayDetails(
        int $id,
        string $razorpayPaymentId,
        ?string $razorpaySignature = null,
        string $paymentStatus = self::PAYMENT_PAID
    ): bool {
        $data = [
            'razorpay_payment_id' => $razorpayPaymentId,
            'payment_status' => $paymentStatus,
        ];

        if ($razorpaySignature) {
            $data['razorpay_signature'] = $razorpaySignature;
        }

        if ($paymentStatus === self::PAYMENT_PAID) {
            $data['order_status'] = self::ORDER_PROCESSING;
        }

        return (bool) $this->where('id', $id)->update($data);
    }
}