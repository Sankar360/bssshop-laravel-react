<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ContactMessage extends Model
{
    use HasFactory;

    protected $table = 'contact_messages';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'admin_reply',
        'replied_at',
    ];

    protected $casts = [
        'id'         => 'integer',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status'     => 'string',
    ];

    public $timestamps = true;

    protected $hidden = [];

    protected $attributes = [
        'status' => 'unread',
    ];

    // ==================== CONSTANTS ====================

    const STATUS_UNREAD  = 'unread';
    const STATUS_READ    = 'read';
    const STATUS_REPLIED = 'replied';

    public static function getStatuses(): array
    {
        return [self::STATUS_UNREAD, self::STATUS_READ, self::STATUS_REPLIED];
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_UNREAD  => 'Unread',
            self::STATUS_READ    => 'Read',
            self::STATUS_REPLIED => 'Replied',
        ];
    }

    public static function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_UNREAD  => 'danger',
            self::STATUS_READ    => 'info',
            self::STATUS_REPLIED => 'success',
            default              => 'secondary',
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

    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('M d, Y H:i') : '';
    }

    public function getFormattedRepliedAtAttribute(): string
    {
        return $this->replied_at ? $this->replied_at->format('M d, Y H:i') : '';
    }

    public function getMessageExcerptAttribute(): string
    {
        return strlen($this->message) > 100
            ? substr($this->message, 0, 100) . '...'
            : $this->message;
    }

    // ==================== SCOPES ====================

    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    public function scopeRead($query)
    {
        return $query->where('status', self::STATUS_READ);
    }

    public function scopeReplied($query)
    {
        return $query->where('status', self::STATUS_REPLIED);
    }

    public function scopeOfStatus($query, string $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name',    'LIKE', "%{$search}%")
                  ->orWhere('email',   'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'DESC');
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    // ==================== HELPERS (sticky-query safe) ====================

    public function getUnreadCount(): int
    {
        return self::query()->where('status', self::STATUS_UNREAD)->count();
    }

    public function getMessagesWithFilter(?string $search = null, ?string $status = null): array
    {
        return self::query()
            ->search($search)
            ->ofStatus($status ?? 'all')
            ->latestFirst()
            ->get()
            ->toArray();
    }

    public function getMessagesWithFilterCollection(?string $search = null, ?string $status = null): Collection
    {
        return self::query()
            ->search($search)
            ->ofStatus($status ?? 'all')
            ->latestFirst()
            ->get();
    }

    public function markAsRead(int $id): bool
    {
        return (bool) self::query()
            ->where('id', $id)
            ->update(['status' => self::STATUS_READ]);
    }

    public function markAsReplied(int $id, string $reply): bool
    {
        return (bool) self::query()
            ->where('id', $id)
            ->update([
                'status'      => self::STATUS_REPLIED,
                'admin_reply' => $reply,
                'replied_at'  => now(),
            ]);
    }

    public function getMessageWithDetails(int $id): ?array
    {
        return self::query()->find($id)?->toArray();
    }

    public function getMessage(int $id): ?ContactMessage
    {
        return self::query()->find($id);
    }

    public function markMultipleAsRead(array $ids): int
    {
        return self::query()
            ->whereIn('id', $ids)
            ->update(['status' => self::STATUS_READ]);
    }

    public function deleteOldMessages(int $days = 30): int
    {
        $cutoff = now()->subDays($days);

        return self::query()
            ->where('created_at', '<', $cutoff)
            ->where('status', self::STATUS_REPLIED)
            ->delete();
    }

    public function getStatusSummary(): array
    {
        return [
            'total'   => self::query()->count(),
            'unread'  => self::query()->where('status', self::STATUS_UNREAD)->count(),
            'read'    => self::query()->where('status', self::STATUS_READ)->count(),
            'replied' => self::query()->where('status', self::STATUS_REPLIED)->count(),
        ];
    }

    public function isUnread(): bool
    {
        return $this->status === self::STATUS_UNREAD;
    }

    public function isRead(): bool
    {
        return $this->status === self::STATUS_READ;
    }

    public function isReplied(): bool
    {
        return $this->status === self::STATUS_REPLIED;
    }

    public function getReplyCount(): int
    {
        return self::query()
            ->whereNotNull('admin_reply')
            ->where('admin_reply', '!=', '')
            ->count();
    }

    public function getTodayMessages(): Collection
    {
        return self::query()
            ->whereDate('created_at', today())
            ->latestFirst()
            ->get();
    }

    public function getMessagesByEmail(string $email, int $limit = 10): Collection
    {
        return self::query()
            ->where('email', $email)
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    public function getPaginatedMessages(?string $search = null, ?string $status = null, int $perPage = 20)
    {
        return self::query()
            ->search($search)
            ->ofStatus($status ?? 'all')
            ->latestFirst()
            ->paginate($perPage);
    }

    public function sendAutoReply(string $reply): bool
    {
        // Placeholder — implement mail sending here if you want auto-replies
        return true;
    }

    public function markAsRepliedAndNotify(int $id, string $reply, bool $sendEmail = true): bool
    {
        $result = $this->markAsReplied($id, $reply);

        if ($result && $sendEmail) {
            $message = self::query()->find($id);
            if ($message) {
                $message->sendAutoReply($reply);
            }
        }

        return $result;
    }

    public function getDashboardStats(): array
    {
        $today   = self::query()->whereDate('created_at', today())->count();
        $unread  = self::query()->where('status', self::STATUS_UNREAD)->count();
        $total   = self::query()->count();
        $replied = self::query()->where('status', self::STATUS_REPLIED)->count();

        return [
            'today'           => $today,
            'unread'          => $unread,
            'total'           => $total,
            'replied'         => $replied,
            'needs_attention' => $unread > 0,
        ];
    }

    public function exportMessages(?string $status = null): array
    {
        $query = self::query()->latestFirst();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->get()
            ->map(function ($message) {
                return [
                    'ID'          => $message->id,
                    'Name'        => $message->name,
                    'Email'       => $message->email,
                    'Subject'     => $message->subject,
                    'Message'     => $message->message,
                    'Status'      => $message->status_label,
                    'Admin Reply' => $message->admin_reply,
                    'Created At'  => $message->formatted_created_at,
                    'Replied At'  => $message->formatted_replied_at,
                ];
            })
            ->toArray();
    }
}