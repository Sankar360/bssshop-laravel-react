<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Timezone extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'timezones';

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
        'offset',
        'abbreviation',
        'is_active',
        'is_default',
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
        'offset' => 'string',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
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
        'is_active' => 1,
        'is_default' => 0,
    ];

    // ==================== ACCESSORS ====================

    /**
     * Get the status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get the status badge class attribute.
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Get the default label attribute.
     */
    public function getDefaultLabelAttribute(): string
    {
        return $this->is_default ? 'Default' : '';
    }

    /**
     * Get the default badge class attribute.
     */
    public function getDefaultBadgeAttribute(): string
    {
        return $this->is_default ? 'primary' : '';
    }

    /**
     * Get the formatted offset attribute.
     */
    public function getFormattedOffsetAttribute(): string
    {
        if (empty($this->offset)) {
            return 'UTC';
        }

        // Format offset like UTC+05:30 or UTC-05:00
        $offset = $this->offset;
        
        // If offset is numeric (e.g., 5.5)
        if (is_numeric($offset)) {
            $hours = floor($offset);
            $minutes = ($offset - $hours) * 60;
            $sign = $hours >= 0 ? '+' : '';
            return 'UTC' . $sign . $hours . ':' . str_pad(abs($minutes), 2, '0', STR_PAD_LEFT);
        }

        // If offset is already formatted (e.g., +05:30)
        if (str_starts_with($offset, '+') || str_starts_with($offset, '-')) {
            return 'UTC' . $offset;
        }

        return $offset;
    }

    /**
     * Get the display name attribute.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->name];
        
        if (!empty($this->abbreviation)) {
            $parts[] = '(' . $this->abbreviation . ')';
        }
        
        if (!empty($this->offset)) {
            $parts[] = $this->formatted_offset;
        }
        
        return implode(' ', $parts);
    }

    /**
     * Get the offset in hours as float.
     */
    public function getOffsetHoursAttribute(): float
    {
        if (empty($this->offset)) {
            return 0;
        }

        if (is_numeric($this->offset)) {
            return (float) $this->offset;
        }

        // Parse offset string like "+05:30" or "-05:00"
        $offset = $this->offset;
        $sign = 1;
        
        if (str_starts_with($offset, '-')) {
            $sign = -1;
            $offset = substr($offset, 1);
        } elseif (str_starts_with($offset, '+')) {
            $offset = substr($offset, 1);
        }

        $parts = explode(':', $offset);
        if (count($parts) === 2) {
            return $sign * ((int) $parts[0] + ((int) $parts[1] / 60));
        }

        return (float) $offset * $sign;
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include active timezones.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include inactive timezones.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to only include the default timezone.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to order by default first, then by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_default', 'DESC')
            ->orderBy('name', 'ASC');
    }

    /**
     * Scope to order by name.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('name', 'ASC');
    }

    /**
     * Scope to search timezones.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('abbreviation', 'LIKE', "%{$search}%")
                    ->orWhere('offset', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Scope to filter by offset range.
     */
    public function scopeOffsetRange($query, float $min, float $max)
    {
        return $query->whereBetween('offset', [$min, $max]);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get all active timezones.
     * 
     * @return Collection
     */
    public function getActiveTimezones(): Collection
    {
        return $this->active()
            ->ordered()
            ->get();
    }

    /**
     * Get the default timezone.
     * 
     * @return Timezone|null
     */
    public function getDefaultTimezone(): ?Timezone
    {
        return $this->default()
            ->active()
            ->first();
    }

    /**
     * Set a timezone as the default.
     * 
     * @param int $id
     * @return bool
     */
    public function setDefault(int $id): bool
    {
        return \DB::transaction(function () use ($id) {
            // Reset all defaults
            $this->query()->update(['is_default' => 0]);
            
            // Set new default
            return (bool) $this->where('id', $id)
                ->update(['is_default' => 1]);
        });
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get active timezones for dropdown.
     * 
     * @return array
     */
    public function getActiveTimezonesForDropdown(): array
    {
        return $this->active()
            ->ordered()
            ->get()
            ->pluck('display_name', 'id')
            ->toArray();
    }

    /**
     * Get timezone by name.
     * 
     * @param string $name
     * @param bool $activeOnly
     * @return Timezone|null
     */
    public function getTimezoneByName(string $name, bool $activeOnly = true): ?Timezone
    {
        $query = $this->where('name', $name);

        if ($activeOnly) {
            $query->active();
        }

        return $query->first();
    }

    /**
     * Get timezone by abbreviation.
     * 
     * @param string $abbreviation
     * @param bool $activeOnly
     * @return Timezone|null
     */
    public function getTimezoneByAbbreviation(string $abbreviation, bool $activeOnly = true): ?Timezone
    {
        $query = $this->where('abbreviation', $abbreviation);

        if ($activeOnly) {
            $query->active();
        }

        return $query->first();
    }

    /**
     * Toggle timezone status.
     * 
     * @param int $id
     * @return bool
     */
    public function toggleStatus(int $id): bool
    {
        $timezone = $this->find($id);
        
        if (!$timezone) {
            return false;
        }

        // Prevent deactivating the default timezone
        if ($timezone->is_default && $timezone->is_active) {
            return false;
        }

        return (bool) $this->where('id', $id)
            ->update(['is_active' => !$timezone->is_active]);
    }

    /**
     * Get timezone statistics.
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total' => $this->count(),
            'active' => $this->active()->count(),
            'inactive' => $this->inactive()->count(),
            'default' => $this->default()->count(),
        ];
    }

    /**
     * Check if a timezone name exists.
     * 
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = $this->where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if a timezone abbreviation exists.
     * 
     * @param string $abbreviation
     * @param int|null $excludeId
     * @return bool
     */
    public function abbreviationExists(string $abbreviation, ?int $excludeId = null): bool
    {
        $query = $this->where('abbreviation', $abbreviation);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get timezones with pagination.
     * 
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedTimezones(int $perPage = 20, ?string $search = null)
    {
        return $this->search($search)
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Get active timezones as key-value pairs.
     * 
     * @return array
     */
    public function getActiveKeyValuePairs(): array
    {
        return $this->active()
            ->ordered()
            ->get()
            ->pluck('display_name', 'id')
            ->toArray();
    }

    /**
     * Clone a timezone.
     * 
     * @param int $id
     * @param array $overrides
     * @return Timezone|null
     */
    public function cloneTimezone(int $id, array $overrides = []): ?Timezone
    {
        $timezone = $this->find($id);
        
        if (!$timezone) {
            return null;
        }

        $newTimezone = $timezone->replicate();
        $newTimezone->fill($overrides);
        $newTimezone->is_default = 0;
        $newTimezone->is_active = 0;
        
        // Ensure unique name
        $newName = $newTimezone->name . '_copy';
        $counter = 1;
        while ($this->nameExists($newName)) {
            $newName = $newTimezone->name . '_copy' . $counter;
            $counter++;
        }
        $newTimezone->name = $newName;
        
        $newTimezone->save();

        return $newTimezone;
    }

    /**
     * Bulk update status for timezones.
     * 
     * @param array $ids
     * @param bool $status
     * @return int Number of affected rows
     */
    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        // Prevent deactivating the default timezone
        if (!$status) {
            $defaultId = $this->default()->value('id');
            if ($defaultId && in_array($defaultId, $ids)) {
                $ids = array_diff($ids, [$defaultId]);
            }
        }

        if (empty($ids)) {
            return 0;
        }

        return $this->whereIn('id', $ids)
            ->update(['is_active' => $status]);
    }

    /**
     * Bulk delete timezones.
     * 
     * @param array $ids
     * @return int Number of deleted rows
     */
    public function bulkDelete(array $ids): int
    {
        // Prevent deleting the default timezone
        $defaultId = $this->default()->value('id');
        if ($defaultId && in_array($defaultId, $ids)) {
            $ids = array_diff($ids, [$defaultId]);
        }

        if (empty($ids)) {
            return 0;
        }

        return $this->whereIn('id', $ids)->delete();
    }

    /**
     * Export timezones to array for CSV/Excel.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function exportTimezones(bool $activeOnly = false): array
    {
        $query = $this->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()
            ->map(function ($timezone) {
                return [
                    'ID' => $timezone->id,
                    'Name' => $timezone->name,
                    'Offset' => $timezone->offset,
                    'Abbreviation' => $timezone->abbreviation,
                    'Status' => $timezone->status_label,
                    'Default' => $timezone->default_label,
                    'Created At' => $timezone->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $timezone->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get all timezone names.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getAllNames(bool $activeOnly = true): array
    {
        $query = $this->select('name');

        if ($activeOnly) {
            $query->active();
        }

        return $query->pluck('name')->toArray();
    }

    /**
     * Check if a timezone is the default.
     * 
     * @param int $id
     * @return bool
     */
    public function isDefault(int $id): bool
    {
        $timezone = $this->find($id);
        
        return $timezone && $timezone->is_default;
    }

    /**
     * Check if a timezone is active.
     * 
     * @param int $id
     * @return bool
     */
    public function isActive(int $id): bool
    {
        $timezone = $this->find($id);
        
        return $timezone && $timezone->is_active;
    }

    /**
     * Get the default timezone or fallback.
     * 
     * @param string $fallbackName
     * @return Timezone|null
     */
    public function getDefaultOrFallback(string $fallbackName = 'UTC'): ?Timezone
    {
        $default = $this->getDefaultTimezone();
        
        if ($default) {
            return $default;
        }

        return $this->where('name', $fallbackName)->active()->first();
    }

    /**
     * Get timezones by offset range.
     * 
     * @param float $min
     * @param float $max
     * @param bool $activeOnly
     * @return Collection
     */
    public function getTimezonesByOffsetRange(float $min, float $max, bool $activeOnly = true): Collection
    {
        $query = $this->offsetRange($min, $max);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get timezone for PHP timezone identifier.
     * 
     * @param string $phpTimezone
     * @param bool $activeOnly
     * @return Timezone|null
     */
    public function getTimezoneByPhpName(string $phpTimezone, bool $activeOnly = true): ?Timezone
    {
        // Map PHP timezone names to our timezone names
        $map = [
            'UTC' => 'UTC',
            'America/New_York' => 'America/New_York',
            'America/Chicago' => 'America/Chicago',
            'America/Denver' => 'America/Denver',
            'America/Los_Angeles' => 'America/Los_Angeles',
            'Europe/London' => 'Europe/London',
            'Europe/Paris' => 'Europe/Paris',
            'Europe/Berlin' => 'Europe/Berlin',
            'Asia/Dubai' => 'Asia/Dubai',
            'Asia/Kolkata' => 'Asia/Kolkata',
            'Asia/Tokyo' => 'Asia/Tokyo',
            'Australia/Sydney' => 'Australia/Sydney',
        ];

        $name = $map[$phpTimezone] ?? $phpTimezone;

        return $this->getTimezoneByName($name, $activeOnly);
    }

    /**
     * Get all active timezones grouped by offset.
     * 
     * @return Collection
     */
    public function getActiveTimezonesGroupedByOffset(): Collection
    {
        return $this->active()
            ->ordered()
            ->get()
            ->groupBy('offset');
    }

    /**
     * Get timezones with their usage count.
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getTimezonesWithUsageCount(bool $activeOnly = true): Collection
    {
        $query = $this->select('timezones.*')
            ->selectRaw('(SELECT COUNT(*) FROM user_preferences WHERE user_preferences.timezone = timezones.name) as usage_count')
            ->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get timezone suggestions for autocomplete.
     * 
     * @param string $query
     * @param int $limit
     * @param bool $activeOnly
     * @return array
     */
    public function autocompleteSearch(string $query, int $limit = 10, bool $activeOnly = true): array
    {
        $q = $this->where('name', 'LIKE', "%{$query}%")
            ->orWhere('abbreviation', 'LIKE', "%{$query}%");

        if ($activeOnly) {
            $q->active();
        }

        return $q->ordered()
            ->limit($limit)
            ->get(['id', 'name', 'abbreviation', 'offset'])
            ->map(function ($timezone) {
                return [
                    'id' => $timezone->id,
                    'name' => $timezone->name,
                    'abbreviation' => $timezone->abbreviation,
                    'offset' => $timezone->offset,
                    'display' => $timezone->display_name,
                ];
            })
            ->toArray();
    }
}