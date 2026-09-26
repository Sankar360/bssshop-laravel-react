<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Preference extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_preferences';

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
        'language',
        'timezone',
        'theme',
        'notifications',
        'newsletter',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'language' => 'string',
        'timezone' => 'string',
        'theme' => 'string',
        'notifications' => 'string',
        'newsletter' => 'boolean',
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
        'language' => 'en',
        'timezone' => 'UTC',
        'theme' => 'light',
        'notifications' => 'on',
        'newsletter' => false,
    ];

    // ==================== CONSTANTS ====================

    /**
     * Notification status constants.
     */
    const NOTIFICATIONS_ON = 'on';
    const NOTIFICATIONS_OFF = 'off';

    /**
     * Get all available notification statuses.
     *
     * @return array
     */
    public static function getNotificationStatuses(): array
    {
        return [
            self::NOTIFICATIONS_ON,
            self::NOTIFICATIONS_OFF,
        ];
    }

    /**
     * Get notification status labels.
     *
     * @return array
     */
    public static function getNotificationStatusLabels(): array
    {
        return [
            self::NOTIFICATIONS_ON => 'Enabled',
            self::NOTIFICATIONS_OFF => 'Disabled',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns the preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the notifications label attribute.
     */
    public function getNotificationsLabelAttribute(): string
    {
        return self::getNotificationStatusLabels()[$this->notifications] ?? ucfirst($this->notifications);
    }

    /**
     * Get the newsletter label attribute.
     */
    public function getNewsletterLabelAttribute(): string
    {
        return $this->newsletter ? 'Subscribed' : 'Not Subscribed';
    }

    /**
     * Get the newsletter badge class attribute.
     */
    public function getNewsletterBadgeAttribute(): string
    {
        return $this->newsletter ? 'success' : 'secondary';
    }

    /**
     * Get the notifications badge class attribute.
     */
    public function getNotificationsBadgeAttribute(): string
    {
        return $this->notifications === self::NOTIFICATIONS_ON ? 'success' : 'secondary';
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to include users with notifications enabled.
     */
    public function scopeNotificationsEnabled($query)
    {
        return $query->where('notifications', self::NOTIFICATIONS_ON);
    }

    /**
     * Scope to include users subscribed to newsletter.
     */
    public function scopeNewsletterSubscribed($query)
    {
        return $query->where('newsletter', true);
    }

    /**
     * Scope to filter by language.
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope to filter by timezone.
     */
    public function scopeByTimezone($query, string $timezone)
    {
        return $query->where('timezone', $timezone);
    }

    /**
     * Scope to filter by theme.
     */
    public function scopeByTheme($query, string $theme)
    {
        return $query->where('theme', $theme);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get user preferences, creating default if not exists.
     * 
     * @param int $userId
     * @return self
     */
    public function getUserPreferences(int $userId): self
{
    $preference = self::where('user_id', $userId)->first();

    if (!$preference) {
        $preference = self::create([
            'user_id'       => $userId,
            'language'      => $this->getDefaultLanguage(),
            'timezone'      => $this->getDefaultTimezone(),
            'theme'         => $this->getDefaultTheme(),
            'notifications' => self::NOTIFICATIONS_ON,
            'newsletter'    => false,
        ]);
    }

    return $preference;
}

    /**
     * Get user preferences as array.
     * 
     * @param int $userId
     * @return array
     */
    public function getUserPreferencesArray(int $userId): array
    {
        return $this->getUserPreferences($userId)->toArray();
    }

    /**
     * Get the default language from the languages table.
     * 
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        $language = Language::where('is_default', true)
            ->where('is_active', true)
            ->first();

        return $language ? $language->code : 'en';
    }

    /**
     * Get the default timezone from the timezones table.
     * 
     * @return string
     */
    public function getDefaultTimezone(): string
    {
        $timezone = \DB::table('timezones')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        return $timezone ? $timezone->name : 'UTC';
    }

    /**
     * Get the default theme from the themes table.
     * 
     * @return string
     */
    public function getDefaultTheme(): string
    {
        $theme = \DB::table('themes')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();

        return $theme ? $theme->name : 'light';
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Update user preferences.
     * 
     * @param int $userId
     * @param array $data
     * @return self
     */
    public function updatePreferences(int $userId, array $data): self
    {
        $preference = $this->getUserPreferences($userId);
        $preference->update($data);

        // Clear cache for this user's preferences
        Cache::forget('user_preferences_' . $userId);

        return $preference;
    }

    /**
     * Get cached user preferences.
     * 
     * @param int $userId
     * @param int $ttl Cache time in seconds (default 1 hour)
     * @return self
     */
    public function getCachedPreferences(int $userId, int $ttl = 3600): self
{
    return Cache::remember(
        'user_preferences_' . $userId,
        $ttl,
        fn () => static::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'language'      => (new static)->getDefaultLanguage(),
                'timezone'      => (new static)->getDefaultTimezone(),
                'theme'         => (new static)->getDefaultTheme(),
                'notifications' => self::NOTIFICATIONS_ON,
                'newsletter'    => false,
            ]
        )
    );
}

    /**
     * Get a specific preference value.
     * 
     * @param int $userId
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreferenceValue(int $userId, string $key, $default = null)
    {
        $preference = $this->getUserPreferences($userId);
        
        return $preference->{$key} ?? $default;
    }

    /**
     * Set a specific preference value.
     * 
     * @param int $userId
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setPreferenceValue(int $userId, string $key, $value): bool
{
    $allowed = ['language', 'timezone', 'theme', 'notifications', 'newsletter'];

    if (!in_array($key, $allowed, true)) {
        return false;
    }

    $preference = $this->getUserPreferences($userId);
    $preference->{$key} = $value;
    $saved = $preference->save();

    // Clear cache so getCachedPreferences() picks up the change
    Cache::forget('user_preferences_' . $userId);

    return $saved;
}

    /**
     * Toggle notifications for a user.
     * 
     * @param int $userId
     * @return bool
     */
    public function toggleNotifications(int $userId): bool
    {
        $preference = $this->getUserPreferences($userId);
        
        $newStatus = $preference->notifications === self::NOTIFICATIONS_ON 
            ? self::NOTIFICATIONS_OFF 
            : self::NOTIFICATIONS_ON;

        return $this->setPreferenceValue($userId, 'notifications', $newStatus);
    }

    /**
     * Toggle newsletter subscription for a user.
     * 
     * @param int $userId
     * @return bool
     */
    public function toggleNewsletter(int $userId): bool
    {
        $preference = $this->getUserPreferences($userId);
        
        return $this->setPreferenceValue($userId, 'newsletter', !$preference->newsletter);
    }

    /**
     * Get users by language preference.
     * 
     * @param string $language
     * @return \Illuminate\Support\Collection
     */
    public function getUsersByLanguage(string $language): Collection
{
    return self::query()
        ->where('language', $language)
        ->with('user')
        ->get()
        ->pluck('user')
        ->filter();
}

    /**
     * Get users subscribed to newsletter.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getNewsletterSubscribers(): Collection
{
    return self::query()
        ->where('newsletter', true)
        ->with('user')
        ->get()
        ->pluck('user')
        ->filter();
}


    /**
     * Get users with notifications enabled.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getNotificationUsers(): Collection
{
    return self::query()
        ->where('notifications', self::NOTIFICATIONS_ON)
        ->with('user')
        ->get()
        ->pluck('user')
        ->filter();
}

    /**
     * Get preference statistics.
     * 
     * @return array
     */
    public function getStats(): array
{
    return [
        'total'                     => self::query()->count(),
        'notifications_on'          => self::query()->where('notifications', self::NOTIFICATIONS_ON)->count(),
        'notifications_off'         => self::query()->where('notifications', self::NOTIFICATIONS_OFF)->count(),
        'newsletter_subscribed'     => self::query()->where('newsletter', true)->count(),
        'newsletter_unsubscribed'   => self::query()->where('newsletter', false)->count(),

        'languages' => self::query()
            ->select('language')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('language')
            ->pluck('count', 'language')
            ->toArray(),

        'themes' => self::query()
            ->select('theme')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('theme')
            ->pluck('count', 'theme')
            ->toArray(),

        'timezones' => self::query()
            ->select('timezone')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('timezone')
            ->pluck('count', 'timezone')
            ->toArray(),
    ];
}

    /**
     * Bulk update preferences for multiple users.
     * 
     * @param array $userIds
     * @param array $data
     * @return int Number of affected rows
     */
    public function bulkUpdate(array $userIds, array $data): int
    {
        $result = self::query()->whereIn('user_id', $userIds)->update($data);

        // Clear cache for all affected users
        foreach ($userIds as $userId) {
            Cache::forget('user_preferences_' . $userId);
        }

        return $result;
    }

    /**
     * Create preferences for a new user.
     * 
     * @param int $userId
     * @param array $overrides
     * @return self
     */
    public function createForUser(int $userId, array $overrides = []): self
    {
        $defaults = [
            'user_id' => $userId,
            'language' => $this->getDefaultLanguage(),
            'timezone' => $this->getDefaultTimezone(),
            'theme' => $this->getDefaultTheme(),
            'notifications' => self::NOTIFICATIONS_ON,
            'newsletter' => false,
        ];

        return $this->create(array_merge($defaults, $overrides));
    }

    /**
     * Check if user has preferences.
     * 
     * @param int $userId
     * @return bool
     */
    public function hasPreferences(int $userId): bool
{
    return self::query()->where('user_id', $userId)->exists();
}

    /**
     * Delete user preferences.
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteForUser(int $userId): bool
{
    Cache::forget('user_preferences_' . $userId);
    return (bool) self::query()->where('user_id', $userId)->delete();
}
    /**
     * Export preferences to array for CSV/Excel.
     * 
     * @param bool $onlySubscribed
     * @return array
     */
    public function exportPreferences(bool $onlySubscribed = false): array
{
    $query = self::query()->with('user');

    if ($onlySubscribed) {
        $query->where('newsletter', true);
    }

    return $query->get()
        ->map(fn ($preference) => [
            'User ID'       => $preference->user_id,
            'User Name'     => $preference->user?->name,
            'User Email'    => $preference->user?->email,
            'Language'      => $preference->language,
            'Timezone'      => $preference->timezone,
            'Theme'         => $preference->theme,
            'Notifications' => $preference->notifications_label,
            'Newsletter'    => $preference->newsletter_label,
            'Created At'    => $preference->created_at?->format('Y-m-d H:i:s'),
            'Updated At'    => $preference->updated_at?->format('Y-m-d H:i:s'),
        ])
        ->toArray();
}


    /**
     * Get user preference by user ID with caching.
     * 
     * @param int $userId
     * @return self
     */
    public function getByUserId(int $userId): self
    {
        return $this->getCachedPreferences($userId);
    }

    /**
     * Get all users with their preferences.
     * 
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPreferencesWithUsers(int $perPage = 20)
{
    return self::query()
        ->with('user')
        ->orderBy('updated_at', 'DESC')
        ->paginate($perPage);
}

    /**
     * Get language usage statistics.
     * 
     * @return \Illuminate\Support\Collection
     */
   public function getLanguageStats(): Collection
{
    return self::query()
        ->select('language')
        ->selectRaw('COUNT(*) as count')
        ->groupBy('language')
        ->orderBy('count', 'DESC')
        ->get();
}

    /**
     * Get theme usage statistics.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getThemeStats(): Collection
{
    return self::query()
        ->select('theme')
        ->selectRaw('COUNT(*) as count')
        ->groupBy('theme')
        ->orderBy('count', 'DESC')
        ->get();
}
}