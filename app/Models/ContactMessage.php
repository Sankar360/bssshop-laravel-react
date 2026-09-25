<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContactMessage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_messages';

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
        'name',
        'email',
        'subject',
        'message',
        'status',
        'admin_reply',
        'replied_at',
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
        'replied_at' => 'datetime',
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
        'status' => 'unread',
    ];

    // ==================== CONSTANTS ====================

    /**
     * Status constants for better code readability.
     */
    const STATUS_UNREAD = 'unread';
    const STATUS_READ = 'read';
    const STATUS_REPLIED = 'replied';

    /**
     * Get all available statuses.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNREAD,
            self::STATUS_READ,
            self::STATUS_REPLIED,
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
            self::STATUS_UNREAD => 'Unread',
            self::STATUS_READ => 'Read',
            self::STATUS_REPLIED => 'Replied',
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
            self::STATUS_UNREAD => 'danger',
            self::STATUS_READ => 'secondary',
            self::STATUS_REPLIED => 'success',
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
     * Get the formatted created at attribute.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('M d, Y H:i') : '';
    }

    /**
     * Get the formatted replied at attribute.
     */
    public function getFormattedRepliedAtAttribute(): string
    {
        return $this->replied_at ? $this->replied_at->format('M d, Y H:i') : '';
    }

    /**
     * Get a truncated message for list views.
     */
    public function getMessageExcerptAttribute(): string
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    /**
     * Scope to only include read messages.
     */
    public function scopeRead($query)
    {
        return $query->where('status', self::STATUS_READ);
    }

    /**
     * Scope to only include replied messages.
     */
    public function scopeReplied($query)
    {
        return $query->where('status', self::STATUS_REPLIED);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        if ($status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope to search messages.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%")
                    ->orWhere('message', 'LIKE', "%{$search}%");
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
     * Scope to get messages that need attention (unread).
     */
    public function scopeNeedsAttention($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get the count of unread messages.
     * 
     * @return int
     */
    public function getUnreadCount(): int
    {
        return $this->where('status', self::STATUS_UNREAD)->count();
    }

    /**
     * Get messages with optional search and status filtering.
     * 
     * @param string|null $search Search term
     * @param string|null $status Status filter
     * @return array
     */
    public function getMessagesWithFilter(?string $search = null, ?string $status = null): array
    {
        return $this->search($search)
            ->ofStatus($status ?? 'all')
            ->latestFirst()
            ->get()
            ->toArray();
    }

    /**
     * Get messages with filter as a collection.
     * 
     * @param string|null $search
     * @param string|null $status
     * @return Collection
     */
    public function getMessagesWithFilterCollection(?string $search = null, ?string $status = null): Collection
    {
        return $this->search($search)
            ->ofStatus($status ?? 'all')
            ->latestFirst()
            ->get();
    }

    /**
     * Mark a message as read.
     * 
     * @param int $id
     * @return bool
     */
    public function markAsRead(int $id): bool
    {
        return (bool) $this->where('id', $id)
            ->update(['status' => self::STATUS_READ]);
    }

    /**
     * Mark a message as replied.
     * 
     * @param int $id
     * @param string $reply
     * @return bool
     */
    public function markAsReplied(int $id, string $reply): bool
    {
        return (bool) $this->where('id', $id)
            ->update([
                'status' => self::STATUS_REPLIED,
                'admin_reply' => $reply,
                'replied_at' => now(),
            ]);
    }

    /**
     * Get a message with all details.
     * 
     * @param int $id
     * @return array|null
     */
    public function getMessageWithDetails(int $id): ?array
    {
        return $this->find($id)?->toArray();
    }

    /**
     * Get a message as a model instance.
     * 
     * @param int $id
     * @return ContactMessage|null
     */
    public function getMessage(int $id): ?ContactMessage
    {
        return $this->find($id);
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Mark multiple messages as read.
     * 
     * @param array $ids
     * @return int Number of affected rows
     */
    public function markMultipleAsRead(array $ids): int
    {
        return $this->whereIn('id', $ids)
            ->update(['status' => self::STATUS_READ]);
    }

    /**
     * Delete old messages (soft delete or permanent).
     * 
     * @param int $days Number of days to keep
     * @return int Number of deleted records
     */
    public function deleteOldMessages(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);
        
        return $this->where('created_at', '<', $cutoffDate)
            ->where('status', self::STATUS_REPLIED)
            ->delete();
    }

    /**
     * Get status summary counts.
     * 
     * @return array
     */
    public function getStatusSummary(): array
    {
        $total = $this->count();
        $unread = $this->unread()->count();
        $read = $this->read()->count();
        $replied = $this->replied()->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'replied' => $replied,
        ];
    }

    /**
     * Check if a message is unread.
     * 
     * @return bool
     */
    public function isUnread(): bool
    {
        return $this->status === self::STATUS_UNREAD;
    }

    /**
     * Check if a message is read.
     * 
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->status === self::STATUS_READ;
    }

    /**
     * Check if a message is replied.
     * 
     * @return bool
     */
    public function isReplied(): bool
    {
        return $this->status === self::STATUS_REPLIED;
    }

    /**
     * Get reply count (admin replies).
     * 
     * @return int
     */
    public function getReplyCount(): int
    {
        return $this->whereNotNull('admin_reply')
            ->where('admin_reply', '!=', '')
            ->count();
    }

    /**
     * Get today's messages.
     * 
     * @return Collection
     */
    public function getTodayMessages(): Collection
    {
        return $this->whereDate('created_at', today())
            ->latestFirst()
            ->get();
    }

    /**
     * Get messages by email.
     * 
     * @param string $email
     * @param int $limit
     * @return Collection
     */
    public function getMessagesByEmail(string $email, int $limit = 10): Collection
    {
        return $this->where('email', $email)
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Search messages with pagination.
     * 
     * @param string|null $search
     * @param string|null $status
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedMessages(?string $search = null, ?string $status = null, int $perPage = 20)
    {
        return $this->search($search)
            ->ofStatus($status ?? 'all')
            ->latestFirst()
            ->paginate($perPage);
    }

    /**
     * Send auto-reply email (hook for email integration).
     * 
     * @param string $reply
     * @return bool
     */
    public function sendAutoReply(string $reply): bool
    {
        // This is a placeholder - implement email sending logic here
        // You can use Laravel's Mail facade or a notification system
        return true;
    }

    /**
     * Mark as replied and send auto-reply.
     * 
     * @param int $id
     * @param string $reply
     * @param bool $sendEmail
     * @return bool
     */
    public function markAsRepliedAndNotify(int $id, string $reply, bool $sendEmail = true): bool
    {
        $result = $this->markAsReplied($id, $reply);
        
        if ($result && $sendEmail) {
            $message = $this->find($id);
            if ($message) {
                $message->sendAutoReply($reply);
            }
        }
        
        return $result;
    }

    /**
     * Get messages with reply status for admin dashboard.
     * 
     * @return array
     */
    public function getDashboardStats(): array
    {
        $today = $this->getTodayMessages()->count();
        $unread = $this->getUnreadCount();
        $total = $this->count();
        $replied = $this->replied()->count();

        return [
            'today' => $today,
            'unread' => $unread,
            'total' => $total,
            'replied' => $replied,
            'needs_attention' => $unread > 0,
        ];
    }

    /**
     * Export messages to array for CSV/Excel.
     * 
     * @param string|null $status
     * @return array
     */
    public function exportMessages(?string $status = null): array
    {
        $query = $this->latestFirst();
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        return $query->get()
            ->map(function ($message) {
                return [
                    'ID' => $message->id,
                    'Name' => $message->name,
                    'Email' => $message->email,
                    'Subject' => $message->subject,
                    'Message' => $message->message,
                    'Status' => $message->status_label,
                    'Admin Reply' => $message->admin_reply,
                    'Created At' => $message->formatted_created_at,
                    'Replied At' => $message->formatted_replied_at,
                ];
            })
            ->toArray();
    }
}