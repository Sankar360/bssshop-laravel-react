<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\Preference;
use App\Models\Language;
use App\Models\Timezone;
use App\Models\Theme;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    /**
     * @var User
     */
    protected $userModel;

    /**
     * @var Setting
     */
    protected $settingModel;

    /**
     * @var UserPreference
     */
    protected $preferenceModel;

    /**
     * @var Language
     */
    protected $languageModel;

    /**
     * @var Timezone
     */
    protected $timezoneModel;

    /**
     * @var Theme
     */
    protected $themeModel;

    /**
     * AdminProfileController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->userModel = new User();
        $this->settingModel = new Setting();
        $this->preferenceModel = new Preference();
        $this->languageModel = new Language();
        $this->timezoneModel = new Timezone();
        $this->themeModel = new Theme();
    }

    /**
     * Get admin profile with preferences and stats.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Get user preferences
        $preferences = $this->preferenceModel->getUserPreferences($userId);

        // Get user stats
        $stats = $this->getUserStats($userId);

        // Get active languages, timezones, themes
        $languages = $this->languageModel->getActiveLanguages();
        $timezones = $this->timezoneModel->getActiveTimezones();
        $themes = $this->themeModel->getActiveThemes();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar_url,
                    'role' => $user->role,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ],
                'preferences' => [
                    'notifications' => $preferences->notifications ?? 'on',
                    'newsletter' => $preferences->newsletter ?? false,
                    'language' => $preferences->language ?? 'en',
                    'timezone' => $preferences->timezone ?? 'UTC',
                    'theme' => $preferences->theme ?? 'light',
                ],
                'stats' => $stats,
                'languages' => $languages,
                'timezones' => $timezones,
                'themes' => $themes,
            ],
        ]);
    }

    /**
     * Update admin profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => 'nullable|string|min:10|max:20',
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
        ];

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        // Update session data (for token response)
        $userData = $user->fresh();

        Log::info('Admin profile updated: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => [
                    'id' => $userData->id,
                    'name' => $userData->name,
                    'email' => $userData->email,
                    'phone' => $userData->phone,
                    'avatar' => $userData->avatar_url,
                ],
            ],
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
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
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

        // Update password
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

        Log::info('Admin preferences updated for user ID: ' . $userId);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => $preferences->fresh(),
        ]);
    }

    /**
     * Delete admin account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        $userId = Auth::id();

        if (!$request->input('confirm')) {
            return response()->json([
                'success' => false,
                'message' => 'Please confirm account deletion',
            ], 422);
        }

        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Check if user has admin role
        if ($user->role === 'admin') {
            // Check if this is the only admin
            $adminCount = $this->userModel->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the only admin account',
                ], 422);
            }
        }

        // Delete user preferences
        $this->preferenceModel->where('user_id', $userId)->delete();

        // Delete user
        $user->delete();

        // Logout user - revoke tokens
        $request->user()->tokens()->delete();

        Log::info('Admin account deleted: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    /**
     * Get user preferences (AJAX).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPreferences()
    {
        $userId = Auth::id();
        $preferences = $this->preferenceModel->getUserPreferences($userId);

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Switch theme (AJAX).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchTheme(Request $request)
    {
        $theme = $request->input('theme');
        $userId = Auth::id();

        // Get active themes from database
        $activeThemes = $this->themeModel->getActiveThemes();
        $validThemes = $activeThemes->pluck('name')->toArray();

        // If no themes in database, use default list
        if (empty($validThemes)) {
            $validThemes = ['light', 'dark', 'auto', 'blue', 'green'];
        }

        if ($theme && in_array($theme, $validThemes)) {
            // Update preferences
            $preferences = $this->preferenceModel->getUserPreferences($userId);
            $preferences->update(['theme' => $theme]);

            Log::info('Admin theme switched to ' . $theme . ' for user ID: ' . $userId);

            return response()->json([
                'success' => true,
                'message' => 'Theme updated successfully',
                'theme' => $theme,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid theme',
        ], 422);
    }

    /**
     * Switch language (AJAX).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function switchLanguage(Request $request)
    {
        $language = $request->input('language');
        $userId = Auth::id();

        // Get active languages from database
        $activeLanguages = $this->languageModel->getActiveLanguages();
        $validLanguages = $activeLanguages->pluck('code')->toArray();

        // If no languages in database, use default list
        if (empty($validLanguages)) {
            $validLanguages = ['en', 'fr', 'es', 'de'];
        }

        if ($language && in_array($language, $validLanguages)) {
            // Update preferences
            $preferences = $this->preferenceModel->getUserPreferences($userId);
            $preferences->update(['language' => $language]);

            Log::info('Admin language switched to ' . $language . ' for user ID: ' . $userId);

            return response()->json([
                'success' => true,
                'message' => 'Language updated successfully',
                'language' => $language,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid language',
        ], 422);
    }

    /**
     * Get user statistics.
     *
     * @param int $userId
     * @return array
     */
    private function getUserStats(int $userId): array
    {
        $orders = Order::where('user_id', $userId)->get();

        return [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total'),
            'last_login' => now()->toDateTimeString(),
            'member_since' => now()->toDateTimeString(),
        ];
    }

    /**
     * Upload avatar (standalone endpoint).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
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

        // Delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'data' => [
                'avatar' => $path,
                'avatar_url' => Storage::url($path),
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
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar removed successfully',
        ]);
    }

    /**
     * Get available languages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLanguages()
    {
        $languages = $this->languageModel->getActiveLanguages();

        return response()->json([
            'success' => true,
            'data' => $languages,
        ]);
    }

    /**
     * Get available timezones.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTimezones()
    {
        $timezones = $this->timezoneModel->getActiveTimezones();

        return response()->json([
            'success' => true,
            'data' => $timezones,
        ]);
    }

    /**
     * Get available themes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getThemes()
    {
        $themes = $this->themeModel->getActiveThemes();

        return response()->json([
            'success' => true,
            'data' => $themes,
        ]);
    }

    /**
     * Get dashboard summary with user stats.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary()
    {
        $userId = Auth::id();
        $stats = $this->getUserStats($userId);
        $preferences = $this->preferenceModel->getUserPreferences($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'preferences' => [
                    'theme' => $preferences->theme ?? 'light',
                    'language' => $preferences->language ?? 'en',
                    'notifications' => $preferences->notifications ?? 'on',
                ],
            ],
        ]);
    }

    /**
     * Get activity log for user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityLog(Request $request)
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
}