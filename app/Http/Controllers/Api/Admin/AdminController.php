<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * @var Preference
     */
    protected $preferenceModel;

    public function __construct()
    {
        $this->preferenceModel = new Preference();
    }

    /**
     * Get admin dashboard statistics.
     */
    public function dashboard(Request $request)
    {
        $userModel = new User();
        $orderModel = new Order();
        $productModel = new Product();

        $totalClients = $userModel->where('role', 'user')->count();
        $totalProducts = $productModel->count();
        $totalOrders = $orderModel->count();
        $totalRevenue = $orderModel->where('payment_status', 'paid')->sum('total') ?? 0;

        $recentOrders = $orderModel->with('user')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        $chartData = $this->getRevenueChartData();
        $orderStatusDistribution = $this->getOrderStatusDistribution();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_clients' => $totalClients,
                    'total_products' => $totalProducts,
                    'total_orders' => $totalOrders,
                    'total_revenue' => $totalRevenue,
                    'formatted_revenue' => '$' . number_format($totalRevenue, 2),
                ],
                'recent_orders' => $recentOrders,
                'chart_data' => $chartData,
                'order_status_distribution' => $orderStatusDistribution,
            ],
        ]);
    }

    protected function getRevenueChartData()
    {
        $days = 30;
        $chartData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $revenue = Order::whereDate('created_at', $date)
                ->where('payment_status', 'paid')
                ->sum('total') ?? 0;

            $chartData[] = [
                'date' => $date,
                'revenue' => $revenue,
                'formatted_revenue' => '$' . number_format($revenue, 2),
            ];
        }

        return $chartData;
    }

    protected function getOrderStatusDistribution()
    {
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        $distribution = [];

        foreach ($statuses as $status) {
            $count = Order::where('order_status', $status)->count();
            $distribution[] = [
                'status' => $status,
                'label' => ucfirst($status),
                'count' => $count,
            ];
        }

        return $distribution;
    }

    /**
     * Admin login (public endpoint). Session-based, no tokens.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->role !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => false,
                'message' => 'Account is inactive. Please contact support.',
            ], 403);
        }

        Log::info('Admin login: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Welcome back, ' . $user->name,
            'data' => [
                'user' => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'role'        => $user->role,
                    'avatar'      => $user->avatar_url,
                    'status'      => $user->status,
                    'super_admin' => (int) $user->super_admin,
                ],
                'is_admin' => true,
            ],
        ]);
    }

    /**
     * Admin logout — session based.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            Log::info('Admin logout: ' . $user->email . ' (ID: ' . $user->id . ')');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function profile()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar_url,
                'role' => $user->role,
                'status' => $user->status,
                'super_admin' => (int) $user->super_admin,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
        ];

        if ($request->hasFile('avatar')) {
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $userData['avatar'] = $path;
        }

        $user->update($userData);

        Log::info('Admin profile updated: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:current_password',
            'new_password_confirm' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->password = $request->new_password;  // mutator hashes it
        $user->save();

        Log::info('Admin password changed: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function getPreferences()
    {
        $userId = Auth::id();
        $preferences = $this->preferenceModel->getUserPreferences($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $preferences->notifications ?? 'on',
                'newsletter' => $preferences->newsletter ?? false,
                'language' => $preferences->language ?? 'en',
                'timezone' => $preferences->timezone ?? 'UTC',
                'theme' => $preferences->theme ?? 'light',
            ],
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'notifications' => 'nullable|string|in:on,off',
            'newsletter' => 'nullable|boolean',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'theme' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $preferences = $this->preferenceModel->getUserPreferences($userId);

        $updateData = [];
        foreach (['notifications', 'newsletter', 'language', 'timezone', 'theme'] as $key) {
            if ($request->has($key)) {
                $updateData[$key] = $request->input($key);
            }
        }

        if (!empty($updateData)) {
            $preferences->update($updateData);
        }

        Log::info('Admin preferences updated: User ID ' . $userId);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => $preferences,
        ]);
    }

    public function getStats()
    {
        $userModel = new User();
        $orderModel = new Order();
        $productModel = new Product();

        $totalClients = $userModel->where('role', 'user')->count();
        $totalProducts = $productModel->count();
        $totalOrders = $orderModel->count();
        $totalRevenue = $orderModel->where('payment_status', 'paid')->sum('total') ?? 0;

        $todayOrders = $orderModel->whereDate('created_at', today())->count();
        $todayRevenue = $orderModel->whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('total') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_clients' => $totalClients,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'formatted_revenue' => '$' . number_format($totalRevenue, 2),
                'today_orders' => $todayOrders,
                'today_revenue' => $todayRevenue,
                'formatted_today_revenue' => '$' . number_format($todayRevenue, 2),
            ],
        ]);
    }

    public function getRecentOrders(Request $request)
    {
        $limit = $request->get('limit', 5);

        $orders = Order::with('user')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Check admin session status.
     */
    public function checkAuth(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Authenticated',
            'data' => [
                'user' => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'avatar'      => $user->avatar_url,
                    'role'        => $user->role,
                    'status'      => $user->status,
                    'super_admin' => (int) $user->super_admin,
                ],
            ],
        ]);
    }

    public function getSettings()
    {
        $settings = Setting::all();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function updateSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settingModel = new Setting();
        $settingModel->setSetting($request->key, $request->value);

        Log::info('Setting updated: ' . $request->key . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
        ]);
    }

    public function healthCheck()
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'database' => 'connected',
            'storage' => \Storage::disk('public')->exists('/') ? 'writable' : 'unwritable',
            'cache' => cache()->get('health_check', 'ok') === 'ok' ? 'working' : 'failed',
        ];

        try {
            cache()->put('health_check', 'ok', 10);
        } catch (\Exception $e) {
            $status['cache'] = 'failed: ' . $e->getMessage();
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function getActivityLogs(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Activity logs feature - implement with logging system',
                'logs' => [],
            ],
        ]);
    }
}