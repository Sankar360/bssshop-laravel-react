<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';
    protected $primaryKey = 'id';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'id'         => 'integer',
        'key'        => 'string',
        'value'      => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    protected $hidden = [];

    const CACHE_PREFIX   = 'setting_';
    const CACHE_DURATION = 3600;

    // ==================== MODEL EVENTS ====================

    protected static function booted()
    {
        static::saved(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
            Cache::forget('settings_all');
        });

        static::deleted(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
            Cache::forget('settings_all');
        });
    }

    // ==================== SCOPES ====================

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('key', 'LIKE', "%{$search}%")
                  ->orWhere('value', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }

    public function scopeOrderByKey($query)
    {
        return $query->orderBy('key', 'ASC');
    }

    // ==================== CORE METHODS ====================

    /**
     * Get a setting value by key.
     */
    public static function getSetting(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Get a setting value with caching.
     */
    public function getCachedSetting(string $key, $default = null, int $ttl = self::CACHE_DURATION)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, $ttl, function () use ($key, $default) {
            return self::getSetting($key, $default);
        });
    }

    /**
     * Set a setting value (update or create).
     * Coerces null → '' so NOT NULL columns don't blow up.
     */
    public static function setSetting(string $key, $value): bool
    {
        // ✅ Coerce null to empty string (NOT NULL columns reject null)
        if ($value === null) {
            $value = '';
        }

        // ✅ Convert boolean true/false to '1'/'0'
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        // ✅ Convert arrays/objects to JSON strings
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        // ✅ Cast everything else to string (int, float, etc.)
        if (!is_string($value)) {
            $value = (string) $value;
        }

        $existing = static::where('key', $key)->first();

        if ($existing) {
            return (bool) static::where('key', $key)->update(['value' => $value]);
        }

        return (bool) static::create(['key' => $key, 'value' => $value]);
    }

    /**
     * Set a setting value using Laravel's updateOrCreate.
     */
    public function setSettingUpsert(string $key, $value): bool
    {
        if ($value === null) {
            $value = '';
        }

        return (bool) $this->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );
    }

    // ==================== HELPERS ====================

    public function getAllSettings(bool $cached = true): array
    {
        if ($cached) {
            return Cache::remember('settings_all', self::CACHE_DURATION, function () {
                return self::query()->pluck('value', 'key')->toArray();
            });
        }

        return self::query()->pluck('value', 'key')->toArray();
    }

    public function getAllSettingsCollection(): \Illuminate\Support\Collection
    {
        return self::query()->orderBy('key', 'ASC')->get();
    }

    public function getPaginatedSettings(int $perPage = 20, ?string $search = null)
    {
        return self::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('key', 'LIKE', "%{$search}%")
                        ->orWhere('value', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('key', 'ASC')
            ->paginate($perPage);
    }

    public function getSettingsByPrefix(string $prefix): \Illuminate\Support\Collection
    {
        return self::query()
            ->where('key', 'LIKE', $prefix . '%')
            ->orderBy('key', 'ASC')
            ->get();
    }

    public function getSettingsByPrefixArray(string $prefix): array
    {
        return self::query()
            ->where('key', 'LIKE', $prefix . '%')
            ->pluck('value', 'key')
            ->toArray();
    }

    public function settingExists(string $key): bool
    {
        return self::query()->where('key', $key)->exists();
    }

    public function deleteSetting(string $key): bool
    {
        Cache::forget(self::CACHE_PREFIX . $key);
        return (bool) self::query()->where('key', $key)->delete();
    }

    public function bulkSet(array $settings): int
    {
        $count = 0;

        foreach ($settings as $key => $value) {
            if (self::setSetting($key, $value)) {
                $count++;
            }
        }

        Cache::forget('settings_all');

        return $count;
    }

    public function bulkDelete(array $keys): int
    {
        $result = self::query()->whereIn('key', $keys)->delete();

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
        Cache::forget('settings_all');

        return $result;
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        $value = static::getSetting($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function getInteger(string $key, int $default = 0): int
    {
        $value = static::getSetting($key);
        return $value === null ? $default : (int) $value;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = static::getSetting($key);
        return $value === null ? $default : (float) $value;
    }

    public function getArray(string $key, array $default = []): array
    {
        $value = static::getSetting($key);

        if ($value === null || $value === '') {
            return $default;
        }

        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $default;
    }

    public function setArray(string $key, array $value): bool
    {
        return self::setSetting($key, json_encode($value));
    }

    public function getObject(string $key, ?object $default = null): ?object
    {
        $value = static::getSetting($key);

        if ($value === null || $value === '') {
            return $default;
        }

        $decoded = json_decode($value);
        return $decoded !== null ? $decoded : $default;
    }

    public function exportSettings(?string $prefix = null): array
    {
        $query = self::query()->orderBy('key', 'ASC');

        if ($prefix) {
            $query->where('key', 'LIKE', $prefix . '%');
        }

        return $query->get()
            ->map(function ($setting) {
                return [
                    'Key'        => $setting->key,
                    'Value'      => $setting->value,
                    'Created At' => $setting->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $setting->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    public function clearCache(): void
    {
        Cache::forget('settings_all');

        $keys = self::query()->pluck('key')->toArray();
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    public function getSettingsGroupedByPrefix(): \Illuminate\Support\Collection
    {
        $settings = self::all();
        $grouped = [];

        foreach ($settings as $setting) {
            $parts = explode('.', $setting->key);
            $group = $parts[0] ?? 'general';

            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][$setting->key] = $setting->value;
        }

        return collect($grouped);
    }

    public function getValidationRules(): array
    {
        return [
            'key'   => 'required|string|max:255|unique:settings,key',
            'value' => 'nullable|string',
        ];
    }

    public function getTyped(string $key, string $type = 'string', $default = null)
    {
        $value = static::getSetting($key, $default);

        return match ($type) {
            'int', 'integer'  => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array'           => is_string($value) ? (json_decode($value, true) ?? (array) $value) : (array) $value,
            'object'          => is_string($value) ? (json_decode($value) ?? (object) $value) : (object) $value,
            default           => (string) $value,
        };
    }

    public function countByPrefix(string $prefix): int
    {
        return self::query()->where('key', 'LIKE', $prefix . '%')->count();
    }

    public function getForDropdown(): array
    {
        return self::query()
            ->orderBy('key', 'ASC')
            ->pluck('value', 'key')
            ->toArray();
    }
}