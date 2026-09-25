<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Language extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'languages';

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
        'code',
        'name',
        'native_name',
        'flag',
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
     * Get the full language name (name + native name).
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->name];
        
        if ($this->native_name && $this->native_name !== $this->name) {
            $parts[] = '(' . $this->native_name . ')';
        }
        
        return implode(' ', $parts);
    }

    /**
     * Get the display name for dropdown.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->native_name ? $this->native_name : $this->name;
    }

    /**
     * Get the flag HTML attribute.
     */
    public function getFlagHtmlAttribute(): string
    {
        if (empty($this->flag)) {
            return $this->code;
        }
        
        // If flag is an emoji or font icon
        if (strlen($this->flag) <= 2 || strpos($this->flag, 'fa-') !== false) {
            return $this->flag;
        }
        
        // If flag is an image path
        return '<img src="' . asset($this->flag) . '" alt="' . $this->name . '" class="flag-icon" style="width:20px;height:auto;">';
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include inactive languages.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to only include default language.
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
     * Scope to filter by language code.
     */
    public function scopeOfCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get all active languages.
     * 
     * @return Collection
     */
    public function getActiveLanguages(): Collection
    {
        return $this->active()
            ->ordered()
            ->get();
    }

    /**
     * Get the default language.
     * 
     * @return Language|null
     */
    public function getDefaultLanguage(): ?Language
    {
        return $this->default()
            ->active()
            ->first();
    }

    /**
     * Set a language as the default.
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
     * Get active languages for dropdown.
     * 
     * @return array
     */
    public function getActiveLanguagesForDropdown(): array
    {
        return $this->active()
            ->ordered()
            ->get()
            ->pluck('full_name', 'id')
            ->toArray();
    }

    /**
     * Get languages by codes.
     * 
     * @param array $codes
     * @param bool $activeOnly
     * @return Collection
     */
    public function getLanguagesByCodes(array $codes, bool $activeOnly = true): Collection
    {
        $query = $this->whereIn('code', $codes);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get language by code.
     * 
     * @param string $code
     * @param bool $activeOnly
     * @return Language|null
     */
    public function getLanguageByCode(string $code, bool $activeOnly = true): ?Language
    {
        $query = $this->where('code', $code);

        if ($activeOnly) {
            $query->active();
        }

        return $query->first();
    }

    /**
     * Toggle language status.
     * 
     * @param int $id
     * @return bool
     */
    public function toggleStatus(int $id): bool
    {
        $language = $this->find($id);
        
        if (!$language) {
            return false;
        }

        // Prevent deactivating the default language
        if ($language->is_default && $language->is_active) {
            return false;
        }

        return (bool) $this->where('id', $id)
            ->update(['is_active' => !$language->is_active]);
    }

    /**
     * Get language statistics.
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
     * Check if a language code exists.
     * 
     * @param string $code
     * @param int|null $excludeId
     * @return bool
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get languages with pagination.
     * 
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedLanguages(int $perPage = 20)
    {
        return $this->ordered()->paginate($perPage);
    }

    /**
     * Get all active languages as key-value pairs.
     * 
     * @return array
     */
    public function getActiveKeyValuePairs(): array
    {
        return $this->active()
            ->ordered()
            ->get()
            ->mapWithKeys(function ($language) {
                return [$language->code => $language->display_name];
            })
            ->toArray();
    }

    /**
     * Get the next available language code.
     * 
     * @param string $prefix
     * @return string
     */
    public function getNextCode(string $prefix = 'en'): string
    {
        $existing = $this->where('code', 'LIKE', $prefix . '%')
            ->pluck('code')
            ->toArray();

        $counter = 1;
        $newCode = $prefix;

        while (in_array($newCode, $existing)) {
            $newCode = $prefix . $counter;
            $counter++;
        }

        return $newCode;
    }

    /**
     * Clone a language.
     * 
     * @param int $id
     * @param array $overrides
     * @return Language|null
     */
    public function cloneLanguage(int $id, array $overrides = []): ?Language
    {
        $language = $this->find($id);
        
        if (!$language) {
            return null;
        }

        $newLanguage = $language->replicate();
        $newLanguage->fill($overrides);
        $newLanguage->is_default = 0;
        $newLanguage->is_active = 0;
        $newLanguage->save();

        return $newLanguage;
    }

    /**
     * Bulk update status for languages.
     * 
     * @param array $ids
     * @param bool $status
     * @return int Number of affected rows
     */
    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        // Prevent deactivating the default language
        if (!$status) {
            $defaultId = $this->default()->value('id');
            if ($defaultId && in_array($defaultId, $ids)) {
                // Remove default language from bulk update
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
     * Bulk delete languages.
     * 
     * @param array $ids
     * @return int Number of deleted rows
     */
    public function bulkDelete(array $ids): int
    {
        // Prevent deleting the default language
        $defaultId = $this->default()->value('id');
        if ($defaultId && in_array($defaultId, $ids)) {
            // Remove default language from bulk delete
            $ids = array_diff($ids, [$defaultId]);
        }

        if (empty($ids)) {
            return 0;
        }

        return $this->whereIn('id', $ids)->delete();
    }

    /**
     * Export languages to array for CSV/Excel.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function exportLanguages(bool $activeOnly = false): array
    {
        $query = $this->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()
            ->map(function ($language) {
                return [
                    'ID' => $language->id,
                    'Code' => $language->code,
                    'Name' => $language->name,
                    'Native Name' => $language->native_name,
                    'Flag' => $language->flag,
                    'Status' => $language->status_label,
                    'Default' => $language->default_label,
                    'Created At' => $language->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $language->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get languages by multiple codes.
     * 
     * @param array $codes
     * @param bool $activeOnly
     * @return Collection
     */
    public function getLanguagesByMultipleCodes(array $codes, bool $activeOnly = true): Collection
    {
        $query = $this->whereIn('code', $codes);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get default language or fallback.
     * 
     * @param string $fallbackCode
     * @return Language|null
     */
    public function getDefaultOrFallback(string $fallbackCode = 'en'): ?Language
    {
        $default = $this->getDefaultLanguage();
        
        if ($default) {
            return $default;
        }

        return $this->ofCode($fallbackCode)->active()->first();
    }

    /**
     * Check if a language is the default.
     * 
     * @param int $id
     * @return bool
     */
    public function isDefault(int $id): bool
    {
        $language = $this->find($id);
        
        return $language && $language->is_default;
    }

    /**
     * Check if a language is active.
     * 
     * @param int $id
     * @return bool
     */
    public function isActive(int $id): bool
    {
        $language = $this->find($id);
        
        return $language && $language->is_active;
    }

    /**
     * Get all language codes.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getAllCodes(bool $activeOnly = true): array
    {
        $query = $this->select('code');

        if ($activeOnly) {
            $query->active();
        }

        return $query->pluck('code')->toArray();
    }
}