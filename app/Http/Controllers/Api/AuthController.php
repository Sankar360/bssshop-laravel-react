<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    protected $userModel;
    protected $preferenceModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->preferenceModel = new Preference();
    }

    public function checkAuth(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
                'is_logged_in' => false,
            ], 401);
        }

        $preferences = $this->preferenceModel->getUserPreferences($user->id);

        return response()->json([
            'success' => true,
            'is_logged_in' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar_url,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'preferences' => [
                    'theme' => $preferences->theme ?? 'light',
                    'language' => $preferences->language ?? 'en',
                    'notifications' => $preferences->notifications ?? 'on',
                ],
            ],
        ]);
    }

    /**
     * Customer login — returns Sanctum bearer token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        // ✅ Return a Sanctum bearer token for the SPA
        $token = $user->createToken('auth-token')->plainTextToken;

        $preferences = $this->preferenceModel->getUserPreferences($user->id);

        Log::info('User logged in: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Welcome back, ' . $user->name . '!',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar_url,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'preferences' => [
                    'theme' => $preferences->theme ?? 'light',
                    'language' => $preferences->language ?? 'en',
                    'notifications' => $preferences->notifications ?? 'on',
                ],
                'token'      => $token,
                'token_type' => 'Bearer',
                'is_admin'   => $user->role === 'admin',
            ],
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'password_confirm' => 'required|same:password',
            'phone' => 'nullable|string|max:20',
            'terms' => 'required|accepted',
            'newsletter' => 'nullable|boolean',
        ], [
            'email.unique' => 'This email is already registered. Please login or use a different email.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password_confirm.same' => 'Passwords do not match.',
            'terms.accepted' => 'You must agree to the Terms of Service.',
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
            'password' => $request->password,
            'role' => 'user',
            'status' => 'active',
            'phone' => $request->phone,
        ];

        $user = $this->userModel->create($userData);

        $this->preferenceModel->createForUser($user->id);

        if ($request->newsletter) {
            Log::info('User subscribed to newsletter: ' . $user->email);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $preferences = $this->preferenceModel->getUserPreferences($user->id);

        Log::info('User registered: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Welcome to BSSShop, ' . $user->name . '! Your account has been created successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar_url,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'preferences' => [
                    'theme' => $preferences->theme ?? 'light',
                    'language' => $preferences->language ?? 'en',
                    'notifications' => $preferences->notifications ?? 'on',
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'is_admin' => false,
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $user->currentAccessToken()?->delete();
            Log::info('User logged out: ' . $user->email . ' (ID: ' . $user->id . ')');
        }

        return response()->json([
            'success' => true,
            'message' => 'You have been logged out successfully.',
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent to: ' . $request->email);
            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send reset link. Please try again.',
        ], 500);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8',
            'password_confirm' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirm', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Password reset successful for: ' . $request->email);
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reset password. Invalid token or email.',
        ], 422);
    }

    public function refreshToken(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => false, 'message' => 'Email already verified.'], 422);
        }

        $user->markEmailAsVerified();

        Log::info('Email verified for user: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json(['success' => true, 'message' => 'Email verified successfully.']);
    }

    public function resendVerification(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => false, 'message' => 'Email already verified.'], 422);
        }

        $user->sendEmailVerificationNotification();

        Log::info('Verification email resent to: ' . $user->email);

        return response()->json(['success' => true, 'message' => 'Verification email has been sent.']);
    }

    public function profile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $preferences = $this->preferenceModel->getUserPreferences($user->id);

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
                    'theme' => $preferences->theme ?? 'light',
                    'language' => $preferences->language ?? 'en',
                    'notifications' => $preferences->notifications ?? 'on',
                    'newsletter' => $preferences->newsletter ?? false,
                ],
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
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

        if ($request->hasFile('avatar')) {
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        Log::info('User profile updated: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar_url,
                ],
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

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
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update(['password' => $request->new_password]);

        Log::info('User password changed: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

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

        $preferences = $this->preferenceModel->getUserPreferences($user->id);

        $updateData = [];
        foreach (['notifications', 'newsletter', 'language', 'timezone', 'theme'] as $key) {
            if ($request->has($key)) {
                $updateData[$key] = $request->input($key);
            }
        }

        if (!empty($updateData)) {
            $preferences->update($updateData);
        }

        Log::info('User preferences updated: ' . $user->email . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.',
            'data' => $preferences->fresh(),
        ]);
    }

    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $exists = $this->userModel->where('email', $request->email)->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
        ]);
    }

    public function socialLogin($provider, Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Social login not implemented yet.',
        ], 501);
    }
}