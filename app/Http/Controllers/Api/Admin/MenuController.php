<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    /**
     * @var NavigationMenu
     */
    protected $menuModel;

    /**
     * MenuController constructor.
     */
    public function __construct()
    {

        $this->menuModel = new NavigationMenu();
    }

    /**
     * List all menus.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $menus = $this->menuModel->getAllMenus();

        // Get statistics
        $stats = [
            'total' => $this->menuModel->count(),
            'active' => $this->menuModel->where('status', true)->count(),
            'inactive' => $this->menuModel->where('status', false)->count(),
            'header' => $this->menuModel->where('display_header', true)->count(),
            'footer' => $this->menuModel->where('display_footer', true)->count(),
            'both' => $this->menuModel->where('display_header', true)
                ->where('display_footer', true)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $menus,
            'stats' => $stats,
        ]);
    }

    /**
     * Get data for menu creation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'menu' => null,
                'next_sort_order' => $this->menuModel->getNextSortOrder(),
            ],
        ]);
    }

    /**
     * Store a new menu.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_name' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'display_header' => 'nullable|boolean',
            'display_footer' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate at least one display option
        $displayHeader = $request->display_header ? true : false;
        $displayFooter = $request->display_footer ? true : false;

        if (!$displayHeader && !$displayFooter) {
            return response()->json([
                'success' => false,
                'message' => 'At least one display option (Header or Footer) must be selected.',
            ], 422);
        }

        $data = [
            'menu_name' => $request->menu_name,
            'url' => $request->url,
            'display_header' => $displayHeader,
            'display_footer' => $displayFooter,
            'sort_order' => $request->sort_order ?? $this->menuModel->getNextSortOrder(),
            'status' => $request->status ?? true,
        ];

        $menu = $this->menuModel->create($data);

        Log::info('Menu created: ' . $menu->menu_name . ' (ID: ' . $menu->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully.',
            'data' => $menu,
        ], 201);
    }

    /**
     * Get menu for editing.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $menu = $this->menuModel->find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    /**
     * Update a menu.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $menu = $this->menuModel->find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'menu_name' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'display_header' => 'nullable|boolean',
            'display_footer' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate at least one display option
        $displayHeader = $request->display_header ? true : false;
        $displayFooter = $request->display_footer ? true : false;

        if (!$displayHeader && !$displayFooter) {
            return response()->json([
                'success' => false,
                'message' => 'At least one display option (Header or Footer) must be selected.',
            ], 422);
        }

        $data = [
            'menu_name' => $request->menu_name,
            'url' => $request->url,
            'display_header' => $displayHeader,
            'display_footer' => $displayFooter,
            'sort_order' => $request->sort_order ?? $menu->sort_order,
            'status' => $request->status ?? $menu->status,
        ];

        $menu->update($data);

        Log::info('Menu updated: ' . $menu->menu_name . ' (ID: ' . $menu->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully.',
            'data' => $menu,
        ]);
    }

    /**
     * Delete a menu.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $menu = $this->menuModel->find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found.',
            ], 404);
        }

        $menuName = $menu->menu_name;
        $menu->delete();

        Log::info('Menu deleted: ' . $menuName . ' (ID: ' . $id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully.',
        ]);
    }

    /**
     * Toggle menu status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $menu = $this->menuModel->find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found.',
            ], 404);
        }

        $newStatus = !$menu->status;
        $menu->update(['status' => $newStatus]);

        Log::info('Menu status toggled: ' . $menu->menu_name . ' (ID: ' . $menu->id . ') to ' . ($newStatus ? 'active' : 'inactive') . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status' => $newStatus,
            'data' => $menu,
        ]);
    }

    /**
     * Update sort order (for drag and drop).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSortOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sortData' => 'required|array',
            'sortData.*.id' => 'required|integer|exists:navigation_menus,id',
            'sortData.*.sort_order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sortData = $request->sortData;
        $success = true;

        foreach ($sortData as $item) {
            $updated = $this->menuModel->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);

            if (!$updated) {
                $success = false;
            }
        }

        if ($success) {
            $this->menuModel->clearMenuCache();

            Log::info('Menu sort order updated by admin');

            return response()->json([
                'success' => true,
                'message' => 'Sort order updated successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update sort order.',
        ], 500);
    }

    /**
     * Bulk delete menus.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:navigation_menus,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deleted = $this->menuModel->whereIn('id', $request->ids)->delete();

        Log::info('Bulk delete: ' . $deleted . ' menus deleted by admin');

        return response()->json([
            'success' => true,
            'message' => $deleted . ' menus deleted successfully.',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Bulk update menu status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:navigation_menus,id',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->menuModel->whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        Log::info('Bulk status update: ' . $updated . ' menus updated to ' . ($request->status ? 'active' : 'inactive') . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $updated . ' menus updated successfully.',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get menu statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total' => $this->menuModel->count(),
            'active' => $this->menuModel->where('status', true)->count(),
            'inactive' => $this->menuModel->where('status', false)->count(),
            'header' => $this->menuModel->where('display_header', true)->count(),
            'footer' => $this->menuModel->where('display_footer', true)->count(),
            'both' => $this->menuModel->where('display_header', true)
                ->where('display_footer', true)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get menus by location (for frontend).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByLocation(Request $request)
    {
        $location = $request->get('location');
        $activeOnly = $request->get('active_only', true);

        if (!in_array($location, ['header', 'footer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid location. Must be "header" or "footer".',
            ], 422);
        }

        $menus = $this->menuModel->getMenusByLocation($location, $activeOnly);

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    /**
     * Get menus for frontend (cached).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
 * Public endpoint — no auth required.
 * Returns header + footer menus as arrays.
 */
public function getFrontendMenus()
{
    try {
        $header = \App\Models\NavigationMenu::where('display_header', 1)
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->toArray();

        $footer = \App\Models\NavigationMenu::where('display_footer', 1)
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->toArray();

        return response()->json([
            'success' => true,
            'data'    => [
                'header' => $header,
                'footer' => $footer,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => ['header' => [], 'footer' => []],
        ], 500);
    }
}

    /**
     * Get next sort order.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextSortOrder()
    {
        $nextOrder = $this->menuModel->getNextSortOrder();

        return response()->json([
            'success' => true,
            'data' => [
                'next_sort_order' => $nextOrder,
            ],
        ]);
    }

    /**
     * Clone a menu.
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cloneMenu($id, Request $request)
    {
        $menu = $this->menuModel->find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found.',
            ], 404);
        }

        $overrides = $request->only(['menu_name', 'url']);

        $newMenu = $this->menuModel->cloneMenu($id, $overrides);

        if ($newMenu) {
            Log::info('Menu cloned: ' . $menu->menu_name . ' -> ' . $newMenu->menu_name . ' by admin');

            return response()->json([
                'success' => true,
                'message' => 'Menu cloned successfully.',
                'data' => $newMenu,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to clone menu.',
        ], 500);
    }

    /**
     * Search menus (autocomplete).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $limit = $request->get('limit', 10);

        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $menus = $this->menuModel->search($query)
            ->ordered()
            ->limit($limit)
            ->get(['id', 'menu_name', 'url', 'status', 'display_header', 'display_footer']);

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    /**
     * Export menus to CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(Request $request)
    {
        $activeOnly = $request->get('active_only', false);

        $menus = $this->menuModel->ordered();

        if ($activeOnly) {
            $menus->active();
        }

        $menus = $menus->get();

        $filename = 'menus_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($menus) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'ID',
                'Menu Name',
                'URL',
                'Display Header',
                'Display Footer',
                'Sort Order',
                'Status',
                'Created At',
            ]);

            foreach ($menus as $menu) {
                fputcsv($output, [
                    $menu->id,
                    $menu->menu_name,
                    $menu->url,
                    $menu->display_header ? 'Yes' : 'No',
                    $menu->display_footer ? 'Yes' : 'No',
                    $menu->sort_order,
                    $menu->status_label,
                    $menu->created_at ? $menu->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clear menu cache.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        $this->menuModel->clearMenuCache();

        Log::info('Menu cache cleared by admin');

        return response()->json([
            'success' => true,
            'message' => 'Menu cache cleared successfully.',
        ]);
    }
}