<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

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
        'phone',
        'avatar',
        'password',
        'role',
        'status',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'created_at',
        'updated_at',
        'super_admin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string',
        'role' => 'string',
        'super_admin' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'role' => 'user',
        'status' => 'active',
    ];

    // ==================== CONSTANTS ====================

    /**
     * Role constants.
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';
    const ROLE_STAFF = 'staff';

    /**
     * Status constants.
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_BANNED = 'banned';

    /**
     * Get all available roles.
     *
     * @return array
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_USER,
            self::ROLE_STAFF,
        ];
    }

    /**
     * Get role labels.
     *
     * @return array
     */
    public static function getRoleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_USER => 'User',
            self::ROLE_STAFF => 'Staff',
        ];
    }

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
            self::STATUS_SUSPENDED,
            self::STATUS_BANNED,
        ];
    }

    /**
     * Get status labels.
     *
     * @return array
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_BANNED => 'Banned',
        ];
    }

    /**
     * Get status badge class.
     *
     * @param string $status
     * @return string
     */
    public static function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_SUSPENDED => 'warning',
            self::STATUS_BANNED => 'danger',
            default => 'secondary',
        };
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the orders for the user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }

    /**
     * Get the preferences for the user.
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class, 'user_id', 'id');
    }

    /**
     * Get the invoices for the user.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id', 'id');
    }

    /**
     * Get the reviews for the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id', 'id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the role label attribute.
     */
    public function getRoleLabelAttribute(): string
    {
        return self::getRoleLabels()[$this->role] ?? ucfirst($this->role);
    }

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
     * Get the full address attribute.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];
        
        if (!empty($this->address)) {
            $parts[] = $this->address;
        }
        
        if (!empty($this->city)) {
            $parts[] = $this->city;
        }
        
        if (!empty($this->state)) {
            $parts[] = $this->state;
        }
        
        if (!empty($this->postal_code)) {
            $parts[] = $this->postal_code;
        }
        
        if (!empty($this->country)) {
            $parts[] = $this->country;
        }
        
        return implode(', ', $parts);
    }

    /**
     * Get the avatar URL attribute.
     */
    public function getAvatarUrlAttribute(): string
    {
        if (!empty($this->avatar)) {
            // If it's a full URL
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            
            // If it's a storage path
            if (str_starts_with($this->avatar, 'avatars/')) {
                return asset('storage/' . $this->avatar);
            }
            
            return asset($this->avatar);
        }
        
        // Default avatar using UI Avatars API
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random';
    }

    /**
     * Get the initials attribute.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return substr($initials, 0, 2);
    }

    /**
     * Check if user is admin.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return (int) $this->super_admin === 1;
    }
    /**
     * Check if user is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

        /**
     * Scope to only include super admins.
     */
    public function scopeSuperAdmin($query)
    {
        return $query->where('super_admin', 1);
    }
    // ==================== MUTATORS ====================

    /**
     * Hash password on set.
     *
     * @param string $value
     * @return void
     */
    public function setPasswordAttribute(string $value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope to only include suspended users.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    /**
     * Scope to only include banned users.
     */
    public function scopeBanned($query)
    {
        return $query->where('status', self::STATUS_BANNED);
    }

    /**
     * Scope to only include admin users.
     */
    public function scopeAdmin($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope to only include regular users.
     */
    public function scopeUser($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope to only include staff users.
     */
    public function scopeStaff($query)
    {
        return $query->where('role', self::ROLE_STAFF);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeOfRole($query, ?string $role)
    {
        if ($role && $role !== 'all') {
            return $query->where('role', $role);
        }
        return $query;
    }

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
     * Scope to search users.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%")
                    ->orWhere('city', 'LIKE', "%{$search}%")
                    ->orWhere('state', 'LIKE', "%{$search}%")
                    ->orWhere('postal_code', 'LIKE', "%{$search}%")
                    ->orWhere('country', 'LIKE', "%{$search}%");
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
     * Scope to order by name.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('name', 'ASC');
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get the admin user.
     * 
     * @return User|null
     */
    public function getAdmin(): ?User
    {
        return $this->admin()
            ->active()
            ->first();
    }

    /**
     * Get all regular users.
     * 
     * @return Collection
     */
    public function getUsers(): Collection
    {
        return $this->user()->get();
    }

    /**
     * Get all active regular users.
     * 
     * @return Collection
     */
    public function getActiveUsers(): Collection
    {
        return $this->user()
            ->active()
            ->get();
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get users with pagination.
     * 
     * @param string|null $search
     * @param string|null $role
     * @param string|null $status
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedUsers(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        int $perPage = 20
    ) {
        return $this->search($search)
            ->ofRole($role)
            ->ofStatus($status)
            ->latestFirst()
            ->paginate($perPage);
    }

    /**
     * Get user statistics.
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total' => $this->count(),
            'active' => $this->active()->count(),
            'inactive' => $this->inactive()->count(),
            'suspended' => $this->suspended()->count(),
            'banned' => $this->banned()->count(),
            'admins' => $this->admin()->count(),
            'users' => $this->user()->count(),
            'staff' => $this->staff()->count(),
        ];
    }

    /**
     * Get users by role with count.
     * 
     * @return array
     */
    public function getUsersByRole(): array
    {
        return [
            'admin' => $this->admin()->count(),
            'user' => $this->user()->count(),
            'staff' => $this->staff()->count(),
        ];
    }

    /**
     * Get users by status with count.
     * 
     * @return array
     */
    public function getUsersByStatus(): array
    {
        return [
            'active' => $this->active()->count(),
            'inactive' => $this->inactive()->count(),
            'suspended' => $this->suspended()->count(),
            'banned' => $this->banned()->count(),
        ];
    }

    /**
     * Get user by email.
     * 
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Get user by phone.
     * 
     * @param string $phone
     * @return User|null
     */
    public function getUserByPhone(string $phone): ?User
    {
        return $this->where('phone', $phone)->first();
    }

    /**
     * Check if email exists.
     * 
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if phone exists.
     * 
     * @param string $phone
     * @param int|null $excludeId
     * @return bool
     */
    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        $query = $this->where('phone', $phone);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Toggle user status.
     * 
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::getStatuses())) {
            return false;
        }

        return (bool) $this->where('id', $id)
            ->update(['status' => $status]);
    }

    /**
     * Activate a user.
     * 
     * @param int $id
     * @return bool
     */
    public function activate(int $id): bool
    {
        return $this->updateStatus($id, self::STATUS_ACTIVE);
    }

    /**
     * Suspend a user.
     * 
     * @param int $id
     * @return bool
     */
    public function suspend(int $id): bool
    {
        return $this->updateStatus($id, self::STATUS_SUSPENDED);
    }

    /**
     * Ban a user.
     * 
     * @param int $id
     * @return bool
     */
    public function ban(int $id): bool
    {
        return $this->updateStatus($id, self::STATUS_BANNED);
    }

    /**
     * Bulk update status for users.
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
     * Bulk delete users.
     * 
     * @param array $ids
     * @return int Number of deleted rows
     */
    public function bulkDelete(array $ids): int
    {
        // Prevent deleting admin users
        $adminIds = $this->admin()->whereIn('id', $ids)->pluck('id')->toArray();
        $ids = array_diff($ids, $adminIds);

        if (empty($ids)) {
            return 0;
        }

        return $this->whereIn('id', $ids)->delete();
    }

    /**
     * Export users to array for CSV/Excel.
     * 
     * @param string|null $role
     * @param string|null $status
     * @return array
     */
    public function exportUsers(?string $role = null, ?string $status = null): array
    {
        $query = $this->ofRole($role)->ofStatus($status)->latestFirst();

        return $query->get()
            ->map(function ($user) {
                return [
                    'ID' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Phone' => $user->phone,
                    'Role' => $user->role_label,
                    'Status' => $user->status_label,
                    'Address' => $user->address,
                    'City' => $user->city,
                    'State' => $user->state,
                    'Postal Code' => $user->postal_code,
                    'Country' => $user->country,
                    'Created At' => $user->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $user->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get users by country.
     * 
     * @param string $country
     * @param bool $activeOnly
     * @return Collection
     */
    public function getUsersByCountry(string $country, bool $activeOnly = true): Collection
    {
        $query = $this->where('country', $country);

        if ($activeOnly) {
            $query->active();
        }

        return $query->latestFirst()->get();
    }

    /**
     * Get users by city.
     * 
     * @param string $city
     * @param bool $activeOnly
     * @return Collection
     */
    public function getUsersByCity(string $city, bool $activeOnly = true): Collection
    {
        $query = $this->where('city', $city);

        if ($activeOnly) {
            $query->active();
        }

        return $query->latestFirst()->get();
    }

    /**
     * Get users registered today.
     * 
     * @return Collection
     */
    public function getUsersRegisteredToday(): Collection
    {
        return $this->whereDate('created_at', today())->latestFirst()->get();
    }

    /**
     * Get users registered in date range.
     * 
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getUsersRegisteredInRange(string $startDate, string $endDate): Collection
    {
        return $this->whereBetween('created_at', [$startDate, $endDate])
            ->latestFirst()
            ->get();
    }

    /**
     * Get user count by date.
     * 
     * @param int $days
     * @return Collection
     */
    public function getUserRegistrationStats(int $days = 30): Collection
    {
        return $this->where('created_at', '>=', now()->subDays($days))
            ->select(\DB::raw('DATE(created_at) as date'))
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
    }

    /**
     * Check if user is admin.
     * 
     * @param int $id
     * @return bool
     */
    public function isAdmin(int $id): bool
    {
        $user = $this->find($id);
        return $user && $user->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is active.
     * 
     * @param int $id
     * @return bool
     */
    public function isActive(int $id): bool
    {
        $user = $this->find($id);
        return $user && $user->status === self::STATUS_ACTIVE;
    }

    /**
     * Get user with preferences.
     * 
     * @param int $id
     * @return User|null
     */
    public function getUserWithPreferences(int $id): ?User
    {
        return $this->with('preferences')->find($id);
    }

    /**
     * Get user with orders.
     * 
     * @param int $id
     * @param int $limit
     * @return array
     */
    public function getUserWithOrders(int $id, int $limit = 10): array
    {
        $user = $this->with(['orders' => function ($query) use ($limit) {
            $query->latestFirst()->limit($limit);
        }])->find($id);

        return $user ? $user->toArray() : [];
    }

    /**
     * Get user dashboard summary.
     * 
     * @param int $userId
     * @return array
     */
    public function getUserDashboardSummary(int $userId): array
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return [];
        }

        return [
            'user' => $user->toArray(),
            'total_orders' => $user->orders()->count(),
            'total_spent' => $user->orders()->sum('total') ?? 0,
            'recent_orders' => $user->orders()->latestFirst()->limit(5)->get(),
            'preferences' => $user->preferences,
        ];
    }

    /**
     * Update user profile.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateProfile(int $id, array $data): bool
    {
        // Remove password if empty
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return (bool) $this->where('id', $id)->update($data);
    }

    /**
     * Get user count by role.
     * 
     * @param string $role
     * @param bool $activeOnly
     * @return int
     */
    public function countByRole(string $role, bool $activeOnly = true): int
    {
        $query = $this->where('role', $role);

        if ($activeOnly) {
            $query->active();
        }

        return $query->count();
    }
}