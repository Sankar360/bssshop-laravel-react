<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * @var User
     */
    protected $userModel;

    /**
     * @var UserPreference
     */
    protected $preferenceModel;

    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        $this->userModel = new User();
        $this->preferenceModel = new Preference();
    }

    /**
     * Get user profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view your profile.',
            ], 401);
        }

        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please login again.',
            ], 401);
        }

        // Get user data
        $user = $this->userModel->find($userId);

        if (!$user) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'User not found. Please login again.',
            ], 401);
        }

        // Get user preferences
        $preferences = $this->preferenceModel->getUserPreferences($userId);

        // Get user statistics
        $stats = $this->getUserStats($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar_url,
                    'address' => $user->address,
                    'city' => $user->city,
                    'state' => $user->state,
                    'postal_code' => $user->postal_code,
                    'country' => $user->country,
                    'role' => $user->role,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ],
                'preferences' => [
                    'theme' => $preferences->theme ?? 'light',
                    'language' => $preferences->language ?? 'en',
                    'notifications' => $preferences->notifications ?? 'on',
                    'newsletter' => $preferences->newsletter ?? false,
                    'timezone' => $preferences->timezone ?? 'UTC',
                ],
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get user statistics.
     *
     * @param int $userId
     * @return array
     */
    private function getUserStats(int $userId): array
    {
        // Get order statistics
        $orderModel = new \App\Models\Order();
        $orders = $orderModel->where('user_id', $userId)->get();

        $totalOrders = $orders->count();
        $totalSpent = $orders->sum('total') ?? 0;

        // Get completed orders count
        $completedOrders = $orderModel->where('user_id', $userId)
            ->where('order_status', 'delivered')
            ->count();

        // Get pending orders count
        $pendingOrders = $orderModel->where('user_id', $userId)
            ->whereIn('order_status', ['pending', 'processing'])
            ->count();

        // Get wishlist count
        $wishlistModel = new \App\Models\Wishlist();
        $wishlistCount = $wishlistModel->getWishlistCount($userId);

        return [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'formatted_total_spent' => '$' . number_format($totalSpent, 2),
            'completed_orders' => $completedOrders,
            'pending_orders' => $pendingOrders,
            'wishlist_count' => $wishlistCount,
            'member_since' => $this->getMemberSince(),
        ];
    }

    /**
     * Get member since date.
     *
     * @return string
     */
    private function getMemberSince(): string
    {
        $user = Auth::user();
        return $user->created_at ? $user->created_at->format('F Y') : 'N/A';
    }

    /**
     * Get profile edit data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit()
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to edit your profile.',
            ], 401);
        }

        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'city' => $user->city,
                    'state' => $user->state,
                    'postal_code' => $user->postal_code,
                    'country' => $user->country,
                ],
                'preferences' => $this->preferenceModel->getUserPreferences($userId),
            ],
        ]);
    }

    /**
     * Update user profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to update your profile.',
            ], 401);
        }

        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
        ];

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        Log::info('User profile updated: ' . $user->email . ' (ID: ' . $user->id . ')');

        // Get updated user data
        $updatedUser = $this->userModel->find($userId);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'phone' => $updatedUser->phone,
                    'avatar' => $updatedUser->avatar_url,
                    'address' => $updatedUser->address,
                    'city' => $updatedUser->city,
                    'state' => $updatedUser->state,
                    'postal_code' => $updatedUser->postal_code,
                    'country' => $updatedUser->country,
                ],
            ],
        ]);
    }

    /**
     * Update user preferences.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePreferences(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to update preferences.',
            ], 401);
        }

        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'theme' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'notifications' => 'nullable|string|in:on,off',
            'newsletter' => 'nullable|boolean',
            'timezone' => 'nullable|string|max:50',
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
        if ($request->has('theme')) {
            $updateData['theme'] = $request->theme;
        }
        if ($request->has('language')) {
            $updateData['language'] = $request->language;
        }
        if ($request->has('notifications')) {
            $updateData['notifications'] = $request->notifications;
        }
        if ($request->has('newsletter')) {
            $updateData['newsletter'] = $request->newsletter;
        }
        if ($request->has('timezone')) {
            $updateData['timezone'] = $request->timezone;
        }

        if (!empty($updateData)) {
            $preferences->update($updateData);
        }

        Log::info('User preferences updated: User ID ' . $userId);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.',
            'data' => $preferences->fresh(),
        ]);
    }

    /**
     * Upload avatar (standalone endpoint).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to upload avatar.',
            ], 401);
        }

        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Delete old avatar if exists
        if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
            \Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully.',
            'data' => [
                'avatar' => $path,
                'avatar_url' => \Storage::url($path),
            ],
        ]);
    }

    /**
     * Remove avatar.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAvatar()
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to remove avatar.',
            ], 401);
        }

        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
            \Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar removed successfully.',
        ]);
    }

    /**
     * Change user password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to change password.',
            ], 401);
        }

        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
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
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        // Update password
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
        ]);

        Log::info('User password changed: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Get user orders.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrders(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view orders.',
            ], 401);
        }

        $userId = Auth::id();
        $perPage = $request->get('per_page', 10);
        $status = $request->get('status');

        $orderModel = new \App\Models\Order();
        $query = $orderModel->where('user_id', $userId)
            ->orderBy('created_at', 'DESC');

        if ($status && $status !== 'all') {
            $query->where('order_status', $status);
        }

        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get user wishlist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWishlist()
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view wishlist.',
            ], 401);
        }

        $userId = Auth::id();

        $wishlistModel = new \App\Models\Wishlist();
        $wishlistItems = $wishlistModel->getWishlistItemsWithVariants($userId);

        return response()->json([
            'success' => true,
            'data' => $wishlistItems,
            'count' => count($wishlistItems),
        ]);
    }

    /**
     * Get user dashboard summary.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboardSummary()
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login.',
            ], 401);
        }

        $userId = Auth::id();
        $stats = $this->getUserStats($userId);
        $preferences = $this->preferenceModel->getUserPreferences($userId);

        // Get recent orders (last 5)
        $orderModel = new \App\Models\Order();
        $recentOrders = $orderModel->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'preferences' => [
                    'theme' => $preferences->theme ?? 'light',
                    'language' => $preferences->language ?? 'en',
                ],
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    /**
     * Delete user account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login.',
            ], 401);
        }

        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if (!$request->input('confirm')) {
            return response()->json([
                'success' => false,
                'message' => 'Please confirm account deletion.',
            ], 422);
        }

        // Delete user preferences
        $this->preferenceModel->where('user_id', $userId)->delete();

        // Delete user
        $user->delete();

        // Revoke tokens
        $request->user()->tokens()->delete();

        Log::info('User account deleted: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ]);
    }
}