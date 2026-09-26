<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\Models\Wishlist;
use App\Models\Language;
use App\Models\Timezone;
use App\Models\Theme;
use App\Models\NavigationMenu;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @var array Shared data for all views
     */
    protected $data = [];

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
     * @var NavigationMenu
     */
    protected $menuModel;

    /**
     * @var Wishlist
     */
    protected $wishlistModel;

    /**
     * Controller constructor.
     * Loads common data for all views.
     */
    public function __construct()
    {
        $this->languageModel = new Language();
        $this->timezoneModel = new Timezone();
        $this->themeModel = new Theme();
        $this->menuModel = new NavigationMenu();
        $this->wishlistModel = new Wishlist();

        // Share data with all views
        $this->shareCommonData();
    }

    /**
     * Share common data with all views.
     *
     * @return void
     */
    protected function shareCommonData(): void
    {
        $user = Auth::user();
        $isLoggedIn = Auth::check();
        $isAdmin = $user && $user->role === 'admin';

        // Get cart count from session
        $cartCount = $this->getCartCount();

        // Get wishlist count
        $wishlistCount = $this->getWishlistCount();

        // Get languages, timezones, themes
        $languages = $this->getActiveLanguages();
        $timezones = $this->getActiveTimezones();
        $themes = $this->getActiveThemes();

        // Get menus
        $headerMenus = $this->getHeaderMenus();
        $footerMenus = $this->getFooterMenus();

        // Get user theme from session or default
        $userTheme = Session::get('user_theme', 'light');
        $userLanguage = Session::get('user_language', 'en');

        $this->data = [
            'user' => $user,
            'is_logged_in' => $isLoggedIn,
            'is_admin' => $isAdmin,
            'cart_count' => $cartCount,
            'wishlist_count' => $wishlistCount,
            'languages' => $languages,
            'timezones' => $timezones,
            'themes' => $themes,
            'headerMenus' => $headerMenus,
            'footerMenus' => $footerMenus,
            'user_theme' => $userTheme,
            'user_language' => $userLanguage,
        ];

        // Share with all views
        View::share($this->data);
    }

    /**
     * Get cart count from session.
     *
     * @return int
     */
    protected function getCartCount(): int
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Get wishlist count.
     *
     * @return int
     */
    protected function getWishlistCount(): int
    {
        // Check if user is logged in
        if (Auth::check()) {
            $userId = Auth::id();
            return $this->wishlistModel->getWishlistCount($userId);
        }

        // For guests, use session-based wishlist
        $wishlist = Session::get('wishlist', []);
        return count($wishlist);
    }

    /**
     * Get active languages.
     *
     * @return array
     */
    protected function getActiveLanguages(): array
    {
        $isAdmin = Auth::check() && Auth::user()->role === 'admin';

        try {
            $languages = $this->languageModel->where('is_active', true)
                ->orderBy('is_default', 'DESC')
                ->orderBy('name', 'ASC')
                ->get()
                ->toArray();

            if (!empty($languages)) {
                return $languages;
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        // Return default languages for admin
        if ($isAdmin) {
            return [
                ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag' => '🇬🇧', 'is_default' => 1],
                ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'flag' => '🇪🇸', 'is_default' => 0],
                ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'flag' => '🇫🇷', 'is_default' => 0],
                ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪', 'is_default' => 0],
            ];
        }

        return [];
    }

    /**
     * Get active timezones.
     *
     * @return array
     */
    protected function getActiveTimezones(): array
    {
        $isAdmin = Auth::check() && Auth::user()->role === 'admin';

        try {
            $timezones = $this->timezoneModel->where('is_active', true)
                ->orderBy('is_default', 'DESC')
                ->orderBy('name', 'ASC')
                ->get()
                ->toArray();

            if (!empty($timezones)) {
                return $timezones;
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        if ($isAdmin) {
            return [
                ['name' => 'UTC', 'offset' => '+00:00', 'abbreviation' => 'UTC', 'is_default' => 1],
                ['name' => 'America/New_York', 'offset' => '-05:00', 'abbreviation' => 'EST', 'is_default' => 0],
                ['name' => 'America/Chicago', 'offset' => '-06:00', 'abbreviation' => 'CST', 'is_default' => 0],
                ['name' => 'America/Denver', 'offset' => '-07:00', 'abbreviation' => 'MST', 'is_default' => 0],
                ['name' => 'America/Los_Angeles', 'offset' => '-08:00', 'abbreviation' => 'PST', 'is_default' => 0],
            ];
        }

        return [];
    }

    /**
     * Get active themes.
     *
     * @return array
     */
    protected function getActiveThemes(): array
    {
        $isAdmin = Auth::check() && Auth::user()->role === 'admin';

        try {
            $themes = $this->themeModel->where('is_active', true)
                ->orderBy('is_default', 'DESC')
                ->orderBy('display_name', 'ASC')
                ->get()
                ->toArray();

            if (!empty($themes)) {
                return $themes;
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        if ($isAdmin) {
            return [
                ['name' => 'light', 'display_name' => 'Light', 'description' => 'Light theme', 'is_default' => 1],
                ['name' => 'dark', 'display_name' => 'Dark', 'description' => 'Dark theme', 'is_default' => 0],
                ['name' => 'auto', 'display_name' => 'Auto', 'description' => 'Auto detect system theme', 'is_default' => 0],
                ['name' => 'blue', 'display_name' => 'Blue', 'description' => 'Blue color theme', 'is_default' => 0],
                ['name' => 'green', 'display_name' => 'Green', 'description' => 'Green color theme', 'is_default' => 0],
            ];
        }

        return [];
    }

    /**
     * Get header menus.
     *
     * @return array
     */
    protected function getHeaderMenus(): array
    {
        try {
            $menus = $this->menuModel->getHeaderMenus();
            return $menus instanceof \Illuminate\Support\Collection
                ? $menus->toArray()
                : (array) $menus;
        } catch (\Exception $e) {
            return [];
        }
    }


    /**
     * Get footer menus.
     *
     * @return array
     */
    protected function getFooterMenus(): array
    {
        try {
            $menus = $this->menuModel->getFooterMenus();
            return $menus instanceof \Illuminate\Support\Collection
                ? $menus->toArray()
                : (array) $menus;
        } catch (\Exception $e) {
            return [];
        }
    }
    /**
     * Get shared data for use in controllers.
     *
     * @return array
     */
    public function getSharedData(): array
    {
        return $this->data;
    }

    /**
     * Add additional data to be shared with views.
     *
     * @param array $data
     * @return void
     */
    public function addSharedData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
        View::share($data);
    }

    /**
     * Get a specific shared data value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSharedDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if user is logged in.
     *
     * @return bool
     */
    protected function isLoggedIn(): bool
    {
        return Auth::check();
    }

    /**
     * Check if user is admin.
     *
     * @return bool
     */
    protected function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    /**
     * Get current user.
     *
     * @return \App\Models\User|null
     */
    protected function getCurrentUser()
    {
        return Auth::user();
    }

    /**
     * Get current user ID.
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        return Auth::id();
    }

    /**
     * Get user theme from session.
     *
     * @return string
     */
    protected function getUserTheme(): string
    {
        return Session::get('user_theme', 'light');
    }

    /**
     * Get user language from session.
     *
     * @return string
     */
    protected function getUserLanguage(): string
    {
        return Session::get('user_language', 'en');
    }

    /**
     * Set user theme in session.
     *
     * @param string $theme
     * @return void
     */
    protected function setUserTheme(string $theme): void
    {
        Session::put('user_theme', $theme);
    }

    /**
     * Set user language in session.
     *
     * @param string $language
     * @return void
     */
    protected function setUserLanguage(string $language): void
    {
        Session::put('user_language', $language);
    }

    /**
     * Get cart from session.
     *
     * @return array
     */
    protected function getCart(): array
    {
        return Session::get('cart', []);
    }

    /**
     * Set cart in session.
     *
     * @param array $cart
     * @return void
     */
    protected function setCart(array $cart): void
    {
        Session::put('cart', $cart);
    }

    /**
     * Clear cart from session.
     *
     * @return void
     */
    protected function clearCart(): void
    {
        Session::forget('cart');
    }

    /**
     * Add item to cart.
     *
     * @param int $productId
     * @param int $quantity
     * @param int|null $variantId
     * @return void
     */
    protected function addToCart(int $productId, int $quantity = 1, ?int $variantId = null): void
    {
        $cart = $this->getCart();

        $key = $variantId ? $productId . '-' . $variantId : $productId;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        $this->setCart($cart);
    }

    /**
     * Remove item from cart.
     *
     * @param int $productId
     * @param int|null $variantId
     * @return void
     */
    protected function removeFromCart(int $productId, ?int $variantId = null): void
    {
        $cart = $this->getCart();
        $key = $variantId ? $productId . '-' . $variantId : $productId;

        if (isset($cart[$key])) {
            unset($cart[$key]);
            $this->setCart($cart);
        }
    }

    /**
     * Update cart item quantity.
     *
     * @param int $productId
     * @param int $quantity
     * @param int|null $variantId
     * @return void
     */
    protected function updateCartQuantity(int $productId, int $quantity, ?int $variantId = null): void
    {
        $cart = $this->getCart();
        $key = $variantId ? $productId . '-' . $variantId : $productId;

        if (isset($cart[$key])) {
            if ($quantity <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['quantity'] = $quantity;
            }
            $this->setCart($cart);
        }
    }

    /**
     * Get cart total items.
     *
     * @return int
     */
    protected function getCartTotalItems(): int
    {
        return $this->getCartCount();
    }

    /**
     * Get wishlist from session (for guests).
     *
     * @return array
     */
    protected function getGuestWishlist(): array
    {
        return Session::get('wishlist', []);
    }

    /**
     * Set guest wishlist in session.
     *
     * @param array $wishlist
     * @return void
     */
    protected function setGuestWishlist(array $wishlist): void
    {
        Session::put('wishlist', $wishlist);
    }

    /**
     * Add item to guest wishlist.
     *
     * @param int $productId
     * @param int|null $variantId
     * @return void
     */
    protected function addToGuestWishlist(int $productId, ?int $variantId = null): void
    {
        $wishlist = $this->getGuestWishlist();
        $key = $variantId ? $productId . '-' . $variantId : $productId;

        if (!in_array($key, $wishlist)) {
            $wishlist[] = $key;
            $this->setGuestWishlist($wishlist);
        }
    }

    /**
     * Remove item from guest wishlist.
     *
     * @param int $productId
     * @param int|null $variantId
     * @return void
     */
    protected function removeFromGuestWishlist(int $productId, ?int $variantId = null): void
    {
        $wishlist = $this->getGuestWishlist();
        $key = $variantId ? $productId . '-' . $variantId : $productId;

        $wishlist = array_filter($wishlist, function ($item) use ($key) {
            return $item !== $key;
        });

        $this->setGuestWishlist($wishlist);
    }

    /**
     * Clear guest wishlist.
     *
     * @return void
     */
    protected function clearGuestWishlist(): void
    {
        Session::forget('wishlist');
    }

    /**
     * Success JSON response.
     *
     * @param string $message
     * @param array $data
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse(string $message, array $data = [], int $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Error JSON response.
     *
     * @param string $message
     * @param array $errors
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, array $errors = [], int $statusCode = 422)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    /**
     * Not found JSON response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Unauthorized JSON response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Forbidden JSON response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
