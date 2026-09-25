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
     * @var UserPreference
     */
    protected $preferenceModel;

    /**
     * AdminController constructor.
     */
    public function __construct()
    {
        #$this->middleware('auth:sanctum');
        #$this->middleware('admin')->except(['login', 'doLogin']);

        $this->preferenceModel = new Preference();
    }

    /**
     * Get admin dashboard statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        $userModel = new User();
        $orderModel = new Order();
        $productModel = new Product();
        $settingModel = new Setting();

        // Statistics
        $totalClients = $userModel->where('role', 'user')->count();
        $totalProducts = $productModel->count();
        $totalOrders = $orderModel->count();
        $totalRevenue = $orderModel->where('payment_status', 'paid')->sum('total') ?? 0;

        // Recent orders (limit 5)
        $recentOrders = $orderModel->with('user')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        // Get user preferences
        $userId = Auth::id();
        // $preferences = $this->preferenceModel->getUserPreferences($userId);

        // // Get user's theme and language
        // $theme = $preferences->theme ?? 'light';
        // $language = $preferences->language ?? 'en';

        // // Update session for API (will be sent in response)
        // $preferencesData = [
        //     'notifications' => $preferences->notifications ?? 'on',
        //     'newsletter' => $preferences->newsletter ?? false,
        //     'language' => $language,
        //     'timezone' => $preferences->timezone ?? 'UTC',
        //     'theme' => $theme,
        // ];

        // Get revenue chart data (last 30 days)
        $chartData = $this->getRevenueChartData();

        // Get order status distribution
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
                //'preferences' => $preferencesData,
                'chart_data' => $chartData,
                'order_status_distribution' => $orderStatusDistribution,
            ],
        ]);
    }

    /**
     * Get revenue chart data for the last 30 days.
     *
     * @return array
     */
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

    /**
     * Get order status distribution.
     *
     * @return array
     */
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
     * Admin login (public endpoint).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Attempt login
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();

        // Check if user is admin
        if ($user->role !== 'admin') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        // Check if admin is active
        if ($user->status !== 'active') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive. Please contact support.',
            ], 403);
        }

        // Create Sanctum token
        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;

        Log::info('Admin login: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Welcome back, ' . $user->name,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Admin logout.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            Log::info('Admin logout: ' . $user->email . ' (ID: ' . $user->id . ')');
            
            // Revoke all tokens for this user
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get admin profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
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
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update admin profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
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

    /**
     * Change admin password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        Log::info('Admin password changed: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get admin preferences.
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Update admin preferences.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
        if ($request->has('notifications')) {
            $updateData['notifications'] = $request->notifications;
        }
        if ($request->has('newsletter')) {
            $updateData['newsletter'] = $request->newsletter;
        }
        if ($request->has('language')) {
            $updateData['language'] = $request->language;
        }
        if ($request->has('timezone')) {
            $updateData['timezone'] = $request->timezone;
        }
        if ($request->has('theme')) {
            $updateData['theme'] = $request->theme;
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

    /**
     * Get dashboard stats only.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $userModel = new User();
        $orderModel = new Order();
        $productModel = new Product();

        $totalClients = $userModel->where('role', 'user')->count();
        $totalProducts = $productModel->count();
        $totalOrders = $orderModel->count();
        $totalRevenue = $orderModel->where('payment_status', 'paid')->sum('total') ?? 0;

        // Get today's stats
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

    /**
     * Get recent orders (for dashboard widget).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAuth()
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
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Get system settings for admin.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        $settings = Setting::all();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update system setting.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Get system health status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function healthCheck()
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'database' => 'connected',
            'storage' => \Storage::disk('public')->exists('/') ? 'writable' : 'unwritable',
            'cache' => cache()->get('health_check', 'ok') === 'ok' ? 'working' : 'failed',
        ];

        // Test cache
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

    /**
     * Get admin activity logs (recent admin actions).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityLogs(Request $request)
    {
        $limit = $request->get('limit', 20);

        // This is a placeholder - implement with actual logging system
        // You can use Laravel's activity log package or custom logging

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Activity logs feature - implement with logging system',
                'logs' => [],
            ],
        ]);
    }

    /**
 * TEMPORARY TEST — delete after debugging.
 * Diagnoses why admin login fails.
 */
public function testLogin(Request $request)
{
    $email    = $request->input('email', 'admin@bssshop.com');
    $password = $request->input('password', 'admin123');

    // 1. Does the user exist?
    $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json([
            'step'    => 'user_lookup',
            'success' => false,
            'message' => "No user found with email {$email}",
            'hint'    => 'Check the exact email in the users table.',
        ], 404);
    }

    // 2. Raw DB row (bypasses model casts/mutators)
    $rawRow = \DB::table('users')->where('email', $email)->first();

    // 3. Does the stored hash match the password?
    $hashMatches = \Hash::check($password, $rawRow->password);

    // 4. Would Auth::attempt work?
    $attemptOk = \Auth::attempt(['email' => $email, 'password' => $password]);

    // If attempt succeeded, log back out so we don't leave a session
    if ($attemptOk) {
        \Auth::logout();
    }

    // 5. Is the default guard correct?
    $defaultGuard = config('auth.defaults.guard');
    $providerUser = config('auth.providers.users.model');

    return response()->json([
        'input' => [
            'email'    => $email,
            'password' => $password,
        ],
        'user_found' => [
            'id'     => $user->id,
            'name'   => $user->name,
            'role'   => $user->role,
            'status' => $user->status,
        ],
        'db_row' => [
            'password_hash_prefix' => substr($rawRow->password, 0, 7),   // "$2y$10$"
            'password_length'      => strlen($rawRow->password),          // bcrypt = 60
            'role'                 => $rawRow->role,
            'status'               => $rawRow->status,
        ],
        'diagnostics' => [
            'hash_matches'          => $hashMatches,
            'auth_attempt_succeeds' => $attemptOk,
            'default_guard'         => $defaultGuard,
            'user_provider_model'   => $providerUser,
            'password_length_ok'    => strlen($rawRow->password) === 60,
        ],
        'verdict' => $hashMatches
            ? ($user->role !== 'admin'
                ? 'Password OK but role is NOT admin'
                : ($user->status !== 'active'
                    ? 'Password OK but status is NOT active'
                    : ($attemptOk
                        ? '✅ Login should work — problem is elsewhere (frontend payload?)'
                        : '❌ Password OK but Auth::attempt still fails — check guard/provider config')))
            : '❌ Password in DB does NOT match "admin123" — re-seed the admin user',
    ]);
}

/**
 * TEMPORARY TEST — resets the admin password using the model mutator.
 * Delete after debugging.
 */
public function testResetPassword(Request $request)
{
    $email    = $request->input('email', 'admin@bssshop.com');
    $password = $request->input('password', 'admin123');

    $user = User::where('email', $email)->first();
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    // IMPORTANT: assign PLAIN TEXT — the mutator will Hash::make() it once.
    $user->password = $password;
    $user->role     = 'admin';
    $user->status   = 'active';
    $user->save();

    $fresh = $user->fresh();

    return response()->json([
        'success'         => true,
        'message'         => 'Password reset done',
        'new_hash_prefix' => substr($fresh->password, 0, 7),
        'hash_length'     => strlen($fresh->password),
        'hash_matches'    => \Hash::check($password, $fresh->password),
        'attempt_ok'      => \Auth::attempt(['email' => $email, 'password' => $password]),
    ]);
}
}