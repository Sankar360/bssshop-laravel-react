<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Theme extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'themes';

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
        'display_name',
        'description',
        'preview_image',
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
     * Get the preview image URL attribute.
     */
    public function getPreviewImageUrlAttribute(): ?string
    {
        if (empty($this->preview_image)) {
            return null;
        }

        // If it's a full URL already
        if (filter_var($this->preview_image, FILTER_VALIDATE_URL)) {
            return $this->preview_image;
        }

        // If it's a storage path
        if (str_starts_with($this->preview_image, 'themes/')) {
            return asset('storage/' . $this->preview_image);
        }

        // If it's a public path
        return asset($this->preview_image);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include active themes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include inactive themes.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to only include the default theme.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to order by default first, then by display name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_default', 'DESC')
            ->orderBy('display_name', 'ASC');
    }

    /**
     * Scope to order by display name.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('display_name', 'ASC');
    }

    /**
     * Scope to search themes.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('display_name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get all active themes.
     * 
     * @return Collection
     */
    public function getActiveThemes(): Collection
    {
        return $this->active()
            ->ordered()
            ->get();
    }

    /**
     * Get the default theme.
     * 
     * @return Theme|null
     */
    public function getDefaultTheme(): ?Theme
    {
        return $this->default()
            ->active()
            ->first();
    }

    /**
     * Set a theme as the default.
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
     * Get active themes for dropdown.
     * 
     * @return array
     */
    public function getActiveThemesForDropdown(): array
    {
        return $this->active()
            ->ordered()
            ->get()
            ->pluck('display_name', 'id')
            ->toArray();
    }

    /**
     * Get theme by name.
     * 
     * @param string $name
     * @param bool $activeOnly
     * @return Theme|null
     */
    public function getThemeByName(string $name, bool $activeOnly = true): ?Theme
    {
        $query = $this->where('name', $name);

        if ($activeOnly) {
            $query->active();
        }

        return $query->first();
    }

    /**
     * Toggle theme status.
     * 
     * @param int $id
     * @return bool
     */
    public function toggleStatus(int $id): bool
    {
        $theme = $this->find($id);
        
        if (!$theme) {
            return false;
        }

        // Prevent deactivating the default theme
        if ($theme->is_default && $theme->is_active) {
            return false;
        }

        return (bool) $this->where('id', $id)
            ->update(['is_active' => !$theme->is_active]);
    }

    /**
     * Get theme statistics.
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
     * Check if a theme name exists.
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
     * Get themes with pagination.
     * 
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedThemes(int $perPage = 20, ?string $search = null)
    {
        return $this->search($search)
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Get active themes as key-value pairs.
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
     * Clone a theme.
     * 
     * @param int $id
     * @param array $overrides
     * @return Theme|null
     */
    public function cloneTheme(int $id, array $overrides = []): ?Theme
    {
        $theme = $this->find($id);
        
        if (!$theme) {
            return null;
        }

        $newTheme = $theme->replicate();
        $newTheme->fill($overrides);
        $newTheme->is_default = 0;
        $newTheme->is_active = 0;
        
        // Ensure unique name
        $newName = $newTheme->name . '_copy';
        $counter = 1;
        while ($this->nameExists($newName)) {
            $newName = $newTheme->name . '_copy' . $counter;
            $counter++;
        }
        $newTheme->name = $newName;
        
        $newTheme->save();

        return $newTheme;
    }

    /**
     * Bulk update status for themes.
     * 
     * @param array $ids
     * @param bool $status
     * @return int Number of affected rows
     */
    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        // Prevent deactivating the default theme
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
     * Bulk delete themes.
     * 
     * @param array $ids
     * @return int Number of deleted rows
     */
    public function bulkDelete(array $ids): int
    {
        // Prevent deleting the default theme
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
     * Export themes to array for CSV/Excel.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function exportThemes(bool $activeOnly = false): array
    {
        $query = $this->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()
            ->map(function ($theme) {
                return [
                    'ID' => $theme->id,
                    'Name' => $theme->name,
                    'Display Name' => $theme->display_name,
                    'Description' => $theme->description,
                    'Preview Image' => $theme->preview_image,
                    'Status' => $theme->status_label,
                    'Default' => $theme->default_label,
                    'Created At' => $theme->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $theme->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get all theme names.
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
     * Check if a theme is the default.
     * 
     * @param int $id
     * @return bool
     */
    public function isDefault(int $id): bool
    {
        $theme = $this->find($id);
        
        return $theme && $theme->is_default;
    }

    /**
     * Check if a theme is active.
     * 
     * @param int $id
     * @return bool
     */
    public function isActive(int $id): bool
    {
        $theme = $this->find($id);
        
        return $theme && $theme->is_active;
    }

    /**
     * Get the default theme or fallback.
     * 
     * @param string $fallbackName
     * @return Theme|null
     */
    public function getDefaultOrFallback(string $fallbackName = 'light'): ?Theme
    {
        $default = $this->getDefaultTheme();
        
        if ($default) {
            return $default;
        }

        return $this->where('name', $fallbackName)->active()->first();
    }

    /**
     * Get themes by multiple names.
     * 
     * @param array $names
     * @param bool $activeOnly
     * @return Collection
     */
    public function getThemesByNames(array $names, bool $activeOnly = true): Collection
    {
        $query = $this->whereIn('name', $names);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Update theme preview image.
     * 
     * @param int $id
     * @param string $imagePath
     * @return bool
     */
    public function updatePreviewImage(int $id, string $imagePath): bool
    {
        return (bool) $this->where('id', $id)
            ->update(['preview_image' => $imagePath]);
    }

    /**
     * Get themes with their usage count.
     * 
     * @param bool $activeOnly
     * @return Collection
     */
    public function getThemesWithUsageCount(bool $activeOnly = true): Collection
    {
        $query = $this->select('themes.*')
            ->selectRaw('(SELECT COUNT(*) FROM user_preferences WHERE user_preferences.theme = themes.name) as usage_count')
            ->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }
}