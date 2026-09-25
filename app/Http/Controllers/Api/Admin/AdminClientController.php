<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AdminClientController extends Controller
{
    /**
     * @var User
     */
    protected $userModel;

    /**
     * AdminClientController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->userModel = new User();
    }

    /**
     * List clients with filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $role = $request->get('role');
        $dateFrom = $request->get('date_from');
        $perPage = $request->get('per_page', 20);

        $query = $this->userModel->where('role', '!=', 'admin');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply role filter
        if ($role) {
            $query->where('role', $role);
        }

        // Apply date filter
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        $clients = $query->orderBy('created_at', 'DESC')->paginate($perPage);

        // Get statistics
        $stats = [
            'total' => $this->userModel->where('role', '!=', 'admin')->count(),
            'active' => $this->userModel->where('role', '!=', 'admin')->where('status', 'active')->count(),
            'inactive' => $this->userModel->where('role', '!=', 'admin')->where('status', 'inactive')->count(),
            'users' => $this->userModel->where('role', 'user')->count(),
            'admins' => $this->userModel->where('role', 'admin')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $clients,
            'stats' => $stats,
        ]);
    }

    /**
     * Get client details with order history.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view($id)
    {
        $client = $this->userModel->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Get client orders
        $orders = Order::where('user_id', $id)
            ->orderBy('created_at', 'DESC')
            ->get();

        $totalOrders = $orders->count();
        $totalSpent = $orders->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                'client' => $client,
                'orders' => $orders,
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
            ],
        ]);
    }

    /**
     * Get data for client creation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        return response()->json([
            'success' => true,
            'roles' => ['user', 'admin'],
            'statuses' => ['active', 'inactive'],
        ]);
    }

    /**
     * Store a new client.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'password_confirm' => 'required|same:password',
            'phone' => 'nullable|string|max:20',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ];

        $client = $this->userModel->create($userData);

        Log::info('Client created: #' . $client->id . ' - ' . $client->email . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully',
            'data' => $client,
        ], 201);
    }

    /**
     * Get client for editing.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $client = $this->userModel->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Don't allow editing admin users through this endpoint
        if ($client->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit admin users',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $client,
        ]);
    }

    /**
     * Update a client.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $client = $this->userModel->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Don't allow updating admin users through this endpoint
        if ($client->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update admin users',
            ], 403);
        }

        $rules = [
            'name' => 'required|string|min:3|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'phone' => 'nullable|string|max:20',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
        ];

        // Password validation (optional on update)
        if ($request->filled('password')) {
            $rules['password'] = 'nullable|string|min:8';
            $rules['password_confirm'] = 'nullable|same:password';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
        ];

        // Update password if provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $client->update($userData);

        Log::info('Client updated: #' . $client->id . ' - ' . $client->email . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully',
            'data' => $client,
        ]);
    }

    /**
     * Toggle client status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $client = $this->userModel->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Don't allow toggling admin users
        if ($client->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot toggle admin users',
            ], 403);
        }

        $newStatus = $client->status === 'active' ? 'inactive' : 'active';
        $client->update(['status' => $newStatus]);

        Log::info('Client #' . $client->id . ' status changed to ' . $newStatus . ' by admin');

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => 'Status updated successfully',
            'data' => $client,
        ]);
    }

    /**
     * Delete a client.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $client = $this->userModel->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found',
            ], 404);
        }

        // Don't allow deleting admin users
        if ($client->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users',
            ], 403);
        }

        $client->delete();

        Log::info('Client #' . $client->id . ' - ' . $client->email . ' deleted by admin');

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully',
        ]);
    }

    /**
     * Export clients to CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(Request $request)
    {
        $status = $request->get('status');
        $role = $request->get('role');

        $query = $this->userModel->where('role', '!=', 'admin');

        if ($status) {
            $query->where('status', $status);
        }

        if ($role) {
            $query->where('role', $role);
        }

        $clients = $query->orderBy('created_at', 'DESC')->get();

        $filename = 'clients_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($clients) {
            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'Address', 'City', 'State', 'Postal Code', 'Country', 'Joined']);

            // Data
            foreach ($clients as $client) {
                fputcsv($output, [
                    $client->id,
                    $client->name,
                    $client->email,
                    $client->phone ?? 'N/A',
                    $client->role,
                    $client->status,
                    $client->address ?? 'N/A',
                    $client->city ?? 'N/A',
                    $client->state ?? 'N/A',
                    $client->postal_code ?? 'N/A',
                    $client->country ?? 'N/A',
                    $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Check for updates (new clients).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUpdates(Request $request)
    {
        $lastCheck = $request->get('last_check');

        $query = $this->userModel->where('role', '!=', 'admin');

        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }

        $newClients = $query->orderBy('created_at', 'DESC')->get();

        return response()->json([
            'success' => true,
            'has_updates' => $newClients->isNotEmpty(),
            'new_clients' => $newClients,
            'count' => $newClients->count(),
        ]);
    }

    /**
     * Bulk delete clients.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->ids;

        // Remove admin users from the list
        $adminIds = $this->userModel->whereIn('id', $ids)
            ->where('role', 'admin')
            ->pluck('id')
            ->toArray();

        $ids = array_diff($ids, $adminIds);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid clients to delete (admin users excluded)',
            ], 422);
        }

        $deleted = $this->userModel->whereIn('id', $ids)->delete();

        Log::info('Bulk delete: ' . $deleted . ' clients deleted by admin');

        return response()->json([
            'success' => true,
            'message' => $deleted . ' clients deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Bulk update client status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->ids;

        // Remove admin users from the list
        $adminIds = $this->userModel->whereIn('id', $ids)
            ->where('role', 'admin')
            ->pluck('id')
            ->toArray();

        $ids = array_diff($ids, $adminIds);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid clients to update (admin users excluded)',
            ], 422);
        }

        $updated = $this->userModel->whereIn('id', $ids)
            ->update(['status' => $request->status]);

        Log::info('Bulk status update: ' . $updated . ' clients updated to ' . $request->status . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $updated . ' clients updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get client statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total' => $this->userModel->where('role', '!=', 'admin')->count(),
            'active' => $this->userModel->where('role', '!=', 'admin')->where('status', 'active')->count(),
            'inactive' => $this->userModel->where('role', '!=', 'admin')->where('status', 'inactive')->count(),
            'users' => $this->userModel->where('role', 'user')->count(),
            'admins' => $this->userModel->where('role', 'admin')->count(),
            'new_today' => $this->userModel->where('role', '!=', 'admin')
                ->whereDate('created_at', today())
                ->count(),
            'new_this_week' => $this->userModel->where('role', '!=', 'admin')
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'new_this_month' => $this->userModel->where('role', '!=', 'admin')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get client registration chart data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartData(Request $request)
    {
        $days = $request->get('days', 30);

        $data = $this->userModel->where('role', '!=', 'admin')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(\DB::raw('DATE(created_at) as date'))
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Search clients (autocomplete).
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

        $clients = $this->userModel->where('role', '!=', 'admin')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'email', 'phone', 'status']);

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }
}