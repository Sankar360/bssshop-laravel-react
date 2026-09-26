<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class NavigationMenu extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'navigation_menus';

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
        'menu_name',
        'url',
        'display_header',
        'display_footer',
        'sort_order',
        'status',
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
        'display_header' => 'boolean',
        'display_footer' => 'boolean',
        'sort_order' => 'integer',
        'status' => 'boolean',
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
        'status' => 1,
        'display_header' => 0,
        'display_footer' => 0,
        'sort_order' => 0,
    ];

    /**
     * The cache prefix for menu caches.
     */
    const CACHE_PREFIX = 'navigation_menu_';

    /**
     * The cache duration in seconds (1 hour).
     */
    const CACHE_DURATION = 3600;

    // ==================== RELATIONSHIPS ====================

    /**
     * No relationships - standalone model
     */

    // ==================== ACCESSORS ====================

    /**
     * Get the status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status ? 'Active' : 'Inactive';
    }

    /**
     * Get the status badge class attribute.
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->status ? 'success' : 'secondary';
    }

    /**
     * Get the display locations label attribute.
     */
    public function getDisplayLocationsAttribute(): string
    {
        $locations = [];
        
        if ($this->display_header) {
            $locations[] = 'Header';
        }
        
        if ($this->display_footer) {
            $locations[] = 'Footer';
        }
        
        return implode(', ', $locations) ?: 'None';
    }

    /**
     * Get the display locations as array.
     */
    public function getDisplayLocationsArrayAttribute(): array
    {
        $locations = [];
        
        if ($this->display_header) {
            $locations[] = 'header';
        }
        
        if ($this->display_footer) {
            $locations[] = 'footer';
        }
        
        return $locations;
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only include active menus.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to only include inactive menus.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope to only include header menus.
     */
    public function scopeHeader($query)
    {
        return $query->where('display_header', true);
    }

    /**
     * Scope to only include footer menus.
     */
    public function scopeFooter($query)
    {
        return $query->where('display_footer', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'ASC');
    }

    /**
     * Scope to search menus.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('menu_name', 'LIKE', "%{$search}%")
                    ->orWhere('url', 'LIKE', "%{$search}%");
            });
        }
        return $query;
    }

    // ==================== MODEL EVENTS ====================

    /**
     * The "booted" method of the model.
     * Register model event listeners for cache clearing.
     */
    protected static function booted()
    {
        static::saved(function () {
            static::clearMenuCache();
        });

        static::deleted(function () {
            static::clearMenuCache();
        });
    }

    // ==================== ORIGINAL CI4 METHODS ====================

    /**
     * Get menus for header with caching.
     * 
     * @return array
     */
    public static function getHeaderMenus()
{
    return static::where('display_header', true)
        ->where('status', true)
        ->orderBy('sort_order', 'ASC')
        ->get();
}

    /**
     * Get menus for footer with caching.
     * 
     * @return array
     */
    public static function getFooterMenus()
{
    return static::where('display_footer', true)
        ->where('status', true)
        ->orderBy('sort_order', 'ASC')
        ->get();
}

    /**
     * Get all menus with proper ordering.
     * 
     * @return array
     */
    public function getAllMenus(): array
    {
        return $this->ordered()->get()->toArray();
    }

    /**
     * Clear all menu caches.
     * 
     * @return void
     */
    public static function clearMenuCache(): void
{
    $languages = ['en'];   // add others as needed

    foreach ($languages as $lang) {
        Cache::forget(self::CACHE_PREFIX . 'header_'   . $lang);
        Cache::forget(self::CACHE_PREFIX . 'footer_'   . $lang);
        Cache::forget(self::CACHE_PREFIX . 'frontend_' . $lang);
    }
}

    /**
     * Get unique cache key based on current state.
     * 
     * @param string $type
     * @return string
     */
    protected function getCacheKey(string $type): string
{
    $language = Session::get('user_language', config('app.locale', 'en'));
    return self::CACHE_PREFIX . $type . '_' . $language;
}

    /**
     * Toggle menu status.
     * 
     * @param int $id
     * @return bool
     */
    public function toggleStatus(int $id): bool
    {
        $menu = $this->find($id);
        
        if (!$menu) {
            return false;
        }

        $newStatus = !$menu->status;
        
        return (bool) $this->where('id', $id)
            ->update(['status' => $newStatus]);
    }

    /**
     * Validate that at least one display option is selected.
     * 
     * @param array $data
     * @return bool
     */
    public function validateDisplayOptions(array $data): bool
    {
        $header = $data['display_header'] ?? false;
        $footer = $data['display_footer'] ?? false;
        
        return !empty($header) || !empty($footer);
    }

    // ==================== ADDITIONAL HELPER METHODS ====================

    /**
     * Get menus for a specific location.
     * 
     * @param string $location (header, footer)
     * @param bool $activeOnly
     * @return \Illuminate\Support\Collection
     */
    public function getMenusByLocation(string $location, bool $activeOnly = true)
    {
        $query = $this->ordered();

        if ($activeOnly) {
            $query->active();
        }

        if ($location === 'header') {
            $query->header();
        } elseif ($location === 'footer') {
            $query->footer();
        }

        return $query->get();
    }

    /**
     * Get menus with pagination.
     * 
     * @param int $perPage
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedMenus(int $perPage = 20, ?string $search = null)
    {
        return $this->search($search)
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Get menu statistics.
     * 
     * @return array
     */
    public static function getStats(): array
{
    return [
        'total'    => static::count(),
        'active'   => static::active()->count(),
        'inactive' => static::inactive()->count(),
        'header'   => static::header()->count(),
        'footer'   => static::footer()->count(),
        'both'     => static::header()->footer()->count(),
    ];
}

    /**
     * Bulk update status for menus.
     * 
     * @param array $ids
     * @param bool $status
     * @return int Number of affected rows
     */
    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        $result = $this->whereIn('id', $ids)
            ->update(['status' => $status]);
        
        if ($result) {
            self::clearMenuCache();
        }
        
        return $result;
    }

    /**
     * Bulk delete menus.
     * 
     * @param array $ids
     * @return int Number of deleted rows
     */
    public function bulkDelete(array $ids): int
    {
        $result = $this->whereIn('id', $ids)->delete();
        
        if ($result) {
            self::clearMenuCache();
        }
        
        return $result;
    }

    /**
     * Get the next available sort order.
     * 
     * @return int
     */
    public function getNextSortOrder(): int
    {
        $max = $this->max('sort_order');
        return ($max ?? 0) + 1;
    }

    /**
     * Reorder menus.
     * 
     * @param array $orderedIds Array of menu IDs in desired order
     * @return bool
     */
    public function reorderMenus(array $orderedIds): bool
    {
        $result = \DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                $this->where('id', $id)
                    ->update(['sort_order' => $index + 1]);
            }
            return true;
        });
        
        if ($result) {
            self::clearMenuCache();
        }
        
        return $result;
    }

    /**
     * Clone a menu.
     * 
     * @param int $id
     * @param array $overrides
     * @return NavigationMenu|null
     */
    public function cloneMenu(int $id, array $overrides = []): ?NavigationMenu
    {
        $menu = $this->find($id);
        
        if (!$menu) {
            return null;
        }

        $newMenu = $menu->replicate();
        $newMenu->fill($overrides);
        $newMenu->sort_order = $this->getNextSortOrder();
        $newMenu->status = 0; // Set as inactive by default
        $newMenu->save();
        
        self::clearMenuCache();
        
        return $newMenu;
    }

    /**
     * Get active menus for frontend (with caching).
     * 
     * @return array
     */
    public function getFrontendMenus(): array
{
    $cacheKey = $this->getCacheKey('frontend');

    return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
        return [
            'header' => static::getHeaderMenus(),
            'footer' => static::getFooterMenus(),
        ];
    });
}

    /**
     * Check if a menu has a specific display location.
     * 
     * @param int $id
     * @param string $location (header, footer)
     * @return bool
     */
    public function hasDisplayLocation(int $id, string $location): bool
    {
        $menu = $this->find($id);
        
        if (!$menu) {
            return false;
        }

        if ($location === 'header') {
            return (bool) $menu->display_header;
        } elseif ($location === 'footer') {
            return (bool) $menu->display_footer;
        }

        return false;
    }

    /**
     * Export menus to array for CSV/Excel.
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function exportMenus(bool $activeOnly = false): array
    {
        $query = $this->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()
            ->map(function ($menu) {
                return [
                    'ID' => $menu->id,
                    'Menu Name' => $menu->menu_name,
                    'URL' => $menu->url,
                    'Display Header' => $menu->display_header ? 'Yes' : 'No',
                    'Display Footer' => $menu->display_footer ? 'Yes' : 'No',
                    'Sort Order' => $menu->sort_order,
                    'Status' => $menu->status_label,
                    'Created At' => $menu->created_at?->format('Y-m-d H:i:s'),
                    'Updated At' => $menu->updated_at?->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get menus by multiple IDs.
     * 
     * @param array $ids
     * @param bool $activeOnly
     * @return \Illuminate\Support\Collection
     */
    public function getMenusByIds(array $ids, bool $activeOnly = true)
    {
        $query = $this->whereIn('id', $ids);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Search menus for autocomplete.
     * 
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function autocompleteSearch(string $query, int $limit = 10): array
    {
        return $this->where('menu_name', 'LIKE', "%{$query}%")
            ->orWhere('url', 'LIKE', "%{$query}%")
            ->ordered()
            ->limit($limit)
            ->get(['id', 'menu_name', 'url'])
            ->toArray();
    }

    /**
     * Update display locations for a menu.
     * 
     * @param int $id
     * @param bool $displayHeader
     * @param bool $displayFooter
     * @return bool
     */
    public function updateDisplayLocations(int $id, bool $displayHeader, bool $displayFooter): bool
    {
        $result = (bool) $this->where('id', $id)
            ->update([
                'display_header' => $displayHeader,
                'display_footer' => $displayFooter,
            ]);
        
        if ($result) {
            self::clearMenuCache();
        }
        
        return $result;
    }
}