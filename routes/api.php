<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OrdersController;

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminBlogController;
use App\Http\Controllers\Api\Admin\AdminClientController;
use App\Http\Controllers\Api\Admin\AdminFaqController;
use App\Http\Controllers\Api\Admin\AdminFeatureController;
use App\Http\Controllers\Api\Admin\AdminHomeBannerController;
use App\Http\Controllers\Api\Admin\AdminInvoiceController;
use App\Http\Controllers\Api\Admin\AdminMessageController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminProductCategoryController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminProfileController;
use App\Http\Controllers\Api\Admin\AdminSettingController;
use App\Http\Controllers\Api\Admin\MenuController;
use App\Http\Controllers\Api\SettingController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================
// PUBLIC FRONTEND ROUTES
// ============================================================

Route::get('/', [HomeController::class, 'index']);
Route::get('/blog', [HomeController::class, 'blog']);
Route::get('/blog/{id}', [HomeController::class, 'blogDetail']);
Route::get('/faq', [HomeController::class, 'faq']);
Route::get('/contact', [HomeController::class, 'contact']);
Route::post('/contact/send', [HomeController::class, 'sendContact']);
Route::get('/redisTest', [HomeController::class, 'redisTest']);
Route::get('/testTables', [HomeController::class, 'testTables']);
Route::post('/home/clear-cache', [HomeController::class, 'clearCache']);
Route::get('/home/stats', [HomeController::class, 'stats']);
Route::get('/home/search', [HomeController::class, 'search']);
Route::get('/home/quick-view/{id}', [HomeController::class, 'quickView']);
Route::get('/settings/public', [SettingController::class, 'publicSettings']);


// ============================================================
// PRODUCT ROUTES
// ============================================================

Route::get('/product/{slug}', [ProductController::class, 'detail']);
Route::post('/product/find-variant', [ProductController::class, 'findVariant']);
Route::get('/product/quick-view/{id}', [ProductController::class, 'quickView']);
Route::get('/product/{id}/price', [ProductController::class, 'getPrice']);
Route::get('/product/{id}/variants', [ProductController::class, 'getVariants']);
Route::get('/product/{id}/reviews', [ProductController::class, 'getReviews']);

// ============================================================
// CATEGORY ROUTES
// ============================================================

// Public category routes
Route::get ('/category/tree',                  [CategoryController::class, 'getCategoryTree']);
Route::get ('/category/{slug1?}/{slug2?}',     [CategoryController::class, 'index']);

// Category AJAX endpoints
Route::post('/category/filter-products',       [CategoryController::class, 'getCategoryProductsFiltered']);
Route::post('/category/get-features',          [CategoryController::class, 'getFeaturesAjax']);
Route::post('/category/get-products',          [CategoryController::class, 'getProducts']);
Route::post('/category/get-price-range',       [CategoryController::class, 'getPriceRangeAjax']);
Route::get ('/category/subcategories/{parentId}', [CategoryController::class, 'getSubcategories']);
Route::get ('/category/by-slug/{slug}',        [CategoryController::class, 'getCategoryBySlug']);
Route::get ('/category/{categoryId}/products', [CategoryController::class, 'getCategoryProducts']);
Route::get ('/category/{categoryId}/with-subcategories', [CategoryController::class, 'getCategoryWithSubcategories']);

// ============================================================
// SEARCH ROUTES
// ============================================================

Route::get('/search', [SearchController::class, 'index']);
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete']);
Route::get('/search/filters', [SearchController::class, 'getFilters']);
Route::get('/search/popular', [SearchController::class, 'popularSearches']);
Route::get('/search/advanced', [SearchController::class, 'advanced']);

// ============================================================
// AUTH ROUTES (Public)
// ============================================================

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/check-email', [AuthController::class, 'checkEmail']);
});

// ============================================================
// ADMIN LOGIN ROUTES (Public)
// ============================================================

// Route::prefix('admin')->group(function () {
//     Route::post('/login', [AdminController::class, 'login']);
// });

// ============================================================
// TEMPORARY DEBUG ROUTES — DELETE WHEN DONE
// ============================================================

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
});

// ============================================================
// AUTHENTICATED CUSTOMER ROUTES (Sanctum)
// ============================================================

Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::get('/auth/check', [AuthController::class, 'checkAuth']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::post('/auth/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/preferences', [AuthController::class, 'updatePreferences']);
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification']);

    // ============================================================
    // CART ROUTES
    // ============================================================

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::get('/items', [CartController::class, 'items']);
        Route::get('/summary', [CartController::class, 'summary']);
        Route::get('/has-items', [CartController::class, 'hasItems']);
        Route::post('/add', [CartController::class, 'add']);
        Route::post('/update', [CartController::class, 'update']);
        Route::post('/remove', [CartController::class, 'remove']);
        Route::post('/clear', [CartController::class, 'clear']);
        Route::post('/apply-coupon', [CartController::class, 'applyCoupon']);
        Route::post('/remove-coupon', [CartController::class, 'removeCoupon']);
        Route::post('/move-from-wishlist', [CartController::class, 'moveFromWishlist']);
    });

    // ============================================================
    // CHECKOUT ROUTES
    // ============================================================

    Route::prefix('checkout')->group(function () {
        Route::get('/', [CheckoutController::class, 'index']);
        Route::post('/place-order', [CheckoutController::class, 'placeOrder']);
        Route::get('/success', [CheckoutController::class, 'success']);
        Route::post('/create-razorpay-order', [CheckoutController::class, 'createRazorpayOrder']);
        Route::post('/verify-payment', [CheckoutController::class, 'verifyPayment']);
        Route::get('/test-razorpay', [CheckoutController::class, 'testRazorpay']);
    });

    // ============================================================
    // WISHLIST ROUTES
    // ============================================================

    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::get('/count', [WishlistController::class, 'count']);
        Route::post('/toggle', [WishlistController::class, 'toggle']);
        Route::post('/status', [WishlistController::class, 'status']);
        Route::post('/check', [WishlistController::class, 'check']);
        Route::post('/remove', [WishlistController::class, 'remove']);
        Route::post('/clear', [WishlistController::class, 'clear']);
        Route::post('/move-to-cart', [WishlistController::class, 'moveToCart']);
        Route::post('/move-all-to-cart', [WishlistController::class, 'moveAllToCart']);
        Route::post('/bulk-add', [WishlistController::class, 'bulkAdd']);
    });

    // ============================================================
    // PROFILE ROUTES
    // ============================================================

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
        Route::get('/edit', [ProfileController::class, 'edit']);
        Route::post('/update', [ProfileController::class, 'update']);
        Route::post('/update-preferences', [ProfileController::class, 'updatePreferences']);
        Route::post('/upload-avatar', [ProfileController::class, 'uploadAvatar']);
        Route::delete('/remove-avatar', [ProfileController::class, 'removeAvatar']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        Route::get('/orders', [ProfileController::class, 'getOrders']);
        Route::get('/wishlist', [ProfileController::class, 'getWishlist']);
        Route::get('/dashboard-summary', [ProfileController::class, 'dashboardSummary']);
        Route::post('/delete-account', [ProfileController::class, 'deleteAccount']);
    });

    // ============================================================
    // ORDERS ROUTES
    // ============================================================

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrdersController::class, 'index']);
        Route::get('/view/{id}', [OrdersController::class, 'view']);
        Route::post('/cancel/{id}', [OrdersController::class, 'cancel']);
        Route::get('/recent', [OrdersController::class, 'recent']);
        Route::get('/statuses', [OrdersController::class, 'getStatuses']);
        Route::get('/track/{orderNumber}', [OrdersController::class, 'track']);
        Route::get('/download-invoice/{id}', [OrdersController::class, 'downloadInvoice']);
    });
});

Route::get('/menus/frontend', [MenuController::class, 'getFrontendMenus']);

// ============================================================
// ADMIN ROUTES (Sanctum + Admin Middleware)
// ============================================================

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    // ============================================================
    // ADMIN AUTHENTICATION
    // ============================================================

    Route::post('/logout', [AdminController::class, 'logout']);

    // ============================================================
    // ADMIN DASHBOARD
    // ============================================================

    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/stats', [AdminController::class, 'getStats']);
    Route::get('/recent-orders', [AdminController::class, 'getRecentOrders']);
    Route::get('/check-auth', [AdminController::class, 'checkAuth']);
    Route::get('/settings', [AdminController::class, 'getSettings']);
    Route::post('/settings/update', [AdminController::class, 'updateSetting']);
    Route::get('/health', [AdminController::class, 'healthCheck']);
    Route::get('/activity-logs', [AdminController::class, 'getActivityLogs']);

    // ============================================================
    // ADMIN PROFILE
    // ============================================================

    Route::prefix('profile')->group(function () {
        Route::get('/', [AdminProfileController::class, 'index']);
        Route::post('/update', [AdminProfileController::class, 'update']);
        Route::post('/change-password', [AdminProfileController::class, 'changePassword']);
        Route::post('/update-preferences', [AdminProfileController::class, 'updatePreferences']);
        Route::post('/delete-account', [AdminProfileController::class, 'deleteAccount']);
        Route::get('/preferences', [AdminProfileController::class, 'getPreferences']);
        Route::post('/switch-theme', [AdminProfileController::class, 'switchTheme']);
        Route::post('/switch-language', [AdminProfileController::class, 'switchLanguage']);
        Route::post('/upload-avatar', [AdminProfileController::class, 'uploadAvatar']);
        Route::delete('/remove-avatar', [AdminProfileController::class, 'removeAvatar']);
        Route::get('/languages', [AdminProfileController::class, 'getLanguages']);
        Route::get('/timezones', [AdminProfileController::class, 'getTimezones']);
        Route::get('/themes', [AdminProfileController::class, 'getThemes']);
        Route::get('/dashboard-summary', [AdminProfileController::class, 'getDashboardSummary']);
        Route::get('/activity-log', [AdminProfileController::class, 'getActivityLog']);
    });

    // ============================================================
    // CLIENT MANAGEMENT
    // ============================================================

    Route::prefix('clients')->group(function () {
        Route::get('/', [AdminClientController::class, 'index']);
        Route::get('/view/{id}', [AdminClientController::class, 'view']);
        Route::get('/create', [AdminClientController::class, 'create']);
        Route::post('/store', [AdminClientController::class, 'store']);
        Route::get('/edit/{id}', [AdminClientController::class, 'edit']);
        Route::post('/update/{id}', [AdminClientController::class, 'update']);
        Route::post('/toggle-status/{id}', [AdminClientController::class, 'toggleStatus']);
        Route::delete('/delete/{id}', [AdminClientController::class, 'delete']);
        Route::get('/export/csv', [AdminClientController::class, 'exportCsv']);
        Route::get('/check-updates', [AdminClientController::class, 'checkUpdates']);
        Route::post('/bulk-delete', [AdminClientController::class, 'bulkDelete']);
        Route::post('/bulk-update-status', [AdminClientController::class, 'bulkUpdateStatus']);
        Route::get('/stats', [AdminClientController::class, 'getStats']);
        Route::get('/chart-data', [AdminClientController::class, 'getChartData']);
        Route::get('/search', [AdminClientController::class, 'search']);
    });

    // ============================================================
    // ORDER MANAGEMENT
    // ============================================================

    Route::prefix('orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/view/{id}', [AdminOrderController::class, 'view']);
        Route::get('/items/{id}', [AdminOrderController::class, 'getItems']);
        Route::post('/update-status/{id}', [AdminOrderController::class, 'updateStatus']);
        Route::delete('/delete/{id}', [AdminOrderController::class, 'delete']);
        Route::get('/export/csv', [AdminOrderController::class, 'exportCsv']);
        Route::get('/check-updates', [AdminOrderController::class, 'checkUpdates']);
        Route::post('/bulk-action', [AdminOrderController::class, 'bulkAction']);
        Route::get('/stats', [AdminOrderController::class, 'getStats']);
        Route::get('/dashboard-summary', [AdminOrderController::class, 'getDashboardSummary']);
        Route::get('/by-user/{userId}', [AdminOrderController::class, 'getByUser']);
        Route::get('/by-number/{orderNumber}', [AdminOrderController::class, 'getByNumber']);
        Route::post('/update-item-quantity/{itemId}', [AdminOrderController::class, 'updateItemQuantity']);
        Route::get('/search', [AdminOrderController::class, 'search']);
        Route::get('/statuses', [AdminOrderController::class, 'getStatuses']);
        Route::get('/monthly-stats', [AdminOrderController::class, 'getMonthlyStats']);
        Route::get('/daily-stats', [AdminOrderController::class, 'getDailyStats']);
    });

    // ============================================================
    // PRODUCT MANAGEMENT
    // ============================================================

    Route::prefix('products')->group(function () {
        // Main CRUD
        Route::get('/', [AdminProductController::class, 'index']);
        Route::get('/create', [AdminProductController::class, 'create']);
        Route::post('/store', [AdminProductController::class, 'store']);
        Route::get('/edit/{id}', [AdminProductController::class, 'edit']);
        Route::post('/update/{id}', [AdminProductController::class, 'update']);
        Route::delete('/delete/{id}', [AdminProductController::class, 'delete']);
        Route::post('/toggle-status/{id}', [AdminProductController::class, 'toggleStatus']);
        Route::get('/stats', [AdminProductController::class, 'getStats']);
        Route::get('/export/csv', [AdminProductController::class, 'exportCsv']);

        // AJAX Helpers
        Route::post('/get-subcategories', [AdminProductController::class, 'getSubcategories']);
        Route::post('/update-price', [AdminProductController::class, 'updatePrice']);
        Route::post('/update-stock', [AdminProductController::class, 'updateStock']);

        // Image Management
        Route::post('/upload-image/{id}', [AdminProductController::class, 'uploadImage']);
        Route::post('/remove-image/{id}', [AdminProductController::class, 'removeImage']);
        Route::post('/upload-multiple-images/{id}', [AdminProductController::class, 'uploadMultipleImages']);
        Route::post('/set-primary-image/{productId}/{imageId}', [AdminProductController::class, 'setPrimaryImage']);
        Route::delete('/remove-multiple-image/{productId}/{imageId}', [AdminProductController::class, 'removeMultipleImage']);
        Route::get('/get-images/{id}', [AdminProductController::class, 'getProductImages']);
        Route::post('/upload-temp-images', [AdminProductController::class, 'uploadTempImages']);

        // Feature Management
        Route::post('/save-features/{id}', [AdminProductController::class, 'saveFeatures']);
        Route::get('/get-features/{id}', [AdminProductController::class, 'getFeaturesForProduct']);

        // Variant Management
        Route::post('/get-variants/{id}', [AdminProductController::class, 'getProductVariants']);
        Route::post('/save-variant', [AdminProductController::class, 'saveVariant']);
        Route::delete('/delete-variant/{id}', [AdminProductController::class, 'deleteVariant']);
        Route::post('/get-variant-features/{id}', [AdminProductController::class, 'getVariantFeatures']);
        Route::post('/save-variants', [AdminProductController::class, 'saveVariants']);
        Route::post('/get-combinations/{id}', [AdminProductController::class, 'getCombinations']);
        Route::delete('/delete-combination/{id}', [AdminProductController::class, 'deleteCombination']);
        Route::post('/update-combination', [AdminProductController::class, 'updateCombination']);

        // Variant Images
        Route::post('/upload-variant-image/{id}', [AdminProductController::class, 'uploadVariantImage']);
        Route::get('/get-variant-images/{id}', [AdminProductController::class, 'getVariantImages']);
        Route::delete('/delete-variant-image/{id}', [AdminProductController::class, 'deleteVariantImage']);
        Route::post('/set-primary-variant-image/{id}', [AdminProductController::class, 'setPrimaryVariantImage']);
        Route::post('/attach-variant-images', [AdminProductController::class, 'attachVariantImages']);

        // Specifications
        Route::get('/get-specifications/{id}', [AdminProductController::class, 'getSpecifications']);
        Route::post('/save-specifications', [AdminProductController::class, 'saveSpecifications']);
        Route::delete('/delete-all-specifications/{id}', [AdminProductController::class, 'deleteAllSpecifications']);

        // Bulk Operations
        Route::post('/bulk-delete', [AdminProductController::class, 'bulkDelete']);
        Route::post('/bulk-update-status', [AdminProductController::class, 'bulkUpdateStatus']);
    });

    // ============================================================
    // INVOICE MANAGEMENT
    // ============================================================

    Route::prefix('invoices')->group(function () {
        Route::get('/', [AdminInvoiceController::class, 'index']);
        Route::get('/view/{id}', [AdminInvoiceController::class, 'view']);
        Route::get('/generate/{id}', [AdminInvoiceController::class, 'generate']);
        Route::get('/download/{id}', [AdminInvoiceController::class, 'download']);
        Route::get('/preview/{id}', [AdminInvoiceController::class, 'preview']);
        Route::get('/export', [AdminInvoiceController::class, 'export']);
        Route::get('/check-updates', [AdminInvoiceController::class, 'checkUpdates']);
        Route::get('/stats', [AdminInvoiceController::class, 'getStats']);
        Route::get('/by-number/{invoiceNumber}', [AdminInvoiceController::class, 'getByNumber']);
        Route::post('/mark-paid/{id}', [AdminInvoiceController::class, 'markAsPaid']);
        Route::post('/send-email/{id}', [AdminInvoiceController::class, 'sendEmail']);
        Route::post('/bulk-delete', [AdminInvoiceController::class, 'bulkDelete']);
        Route::post('/bulk-update-status', [AdminInvoiceController::class, 'bulkUpdateStatus']);
        Route::get('/dashboard-summary', [AdminInvoiceController::class, 'getDashboardSummary']);
    });

    // ============================================================
    // BLOG MANAGEMENT
    // ============================================================

    Route::prefix('blog')->group(function () {
        Route::get('/', [AdminBlogController::class, 'index']);
        Route::get('/create', [AdminBlogController::class, 'create']);
        Route::post('/store', [AdminBlogController::class, 'store']);
        Route::post('/upload-image/{id}', [AdminBlogController::class, 'uploadImage']);
        Route::post('/remove-image/{id}', [AdminBlogController::class, 'removeImage']);
        Route::get('/edit/{id}', [AdminBlogController::class, 'edit']);
        Route::post('/update/{id}', [AdminBlogController::class, 'update']);
        Route::delete('/delete/{id}', [AdminBlogController::class, 'delete']);
        Route::post('/toggle-status/{id}', [AdminBlogController::class, 'toggleStatus']);
        Route::get('/export', [AdminBlogController::class, 'export']);
        Route::get('/categories', [AdminBlogController::class, 'getCategories']);
        Route::get('/stats', [AdminBlogController::class, 'getStats']);
    });

    // ============================================================
    // FAQ MANAGEMENT
    // ============================================================

    Route::prefix('faqs')->group(function () {
        Route::get('/', [AdminFaqController::class, 'index']);
        Route::get('/create', [AdminFaqController::class, 'create']);
        Route::post('/store', [AdminFaqController::class, 'store']);
        Route::get('/edit/{id}', [AdminFaqController::class, 'edit']);
        Route::post('/update/{id}', [AdminFaqController::class, 'update']);
        Route::delete('/delete/{id}', [AdminFaqController::class, 'delete']);
        Route::post('/toggle-status/{id}', [AdminFaqController::class, 'toggleStatus']);
        Route::post('/reorder', [AdminFaqController::class, 'reorder']);
        Route::get('/export', [AdminFaqController::class, 'export']);
        Route::get('/stats', [AdminFaqController::class, 'getStats']);
        Route::get('/categories', [AdminFaqController::class, 'getCategories']);
        Route::post('/bulk-delete', [AdminFaqController::class, 'bulkDelete']);
        Route::post('/bulk-update-status', [AdminFaqController::class, 'bulkUpdateStatus']);
        Route::get('/next-order', [AdminFaqController::class, 'getNextOrder']);
        Route::get('/category/{category}', [AdminFaqController::class, 'getByCategory']);
        Route::get('/search', [AdminFaqController::class, 'search']);
    });

    // ============================================================
    // CONTACT MESSAGES
    // ============================================================

    Route::prefix('messages')->group(function () {
        Route::get('/', [AdminMessageController::class, 'index']);
        Route::get('/check-updates', [AdminMessageController::class, 'checkUpdates']);
        Route::get('/view/{id}', [AdminMessageController::class, 'view']);
        Route::delete('/delete/{id}', [AdminMessageController::class, 'delete']);
        Route::post('/reply/{id}', [AdminMessageController::class, 'reply']);
        Route::post('/bulk-action', [AdminMessageController::class, 'bulkAction']);
        Route::get('/export', [AdminMessageController::class, 'export']);
        Route::get('/stats', [AdminMessageController::class, 'getStats']);
        Route::get('/unread-count', [AdminMessageController::class, 'getUnreadCount']);
        Route::get('/latest', [AdminMessageController::class, 'getLatest']);
        Route::get('/search', [AdminMessageController::class, 'search']);
        Route::get('/by-email/{email}', [AdminMessageController::class, 'getByEmail']);
        Route::post('/mark-multiple-read', [AdminMessageController::class, 'markMultipleRead']);
        Route::get('/{id}/reply', [AdminMessageController::class, 'getForReply']);
        Route::post('/preview-reply', [AdminMessageController::class, 'previewReply']);
        Route::get('/dashboard-summary', [AdminMessageController::class, 'getDashboardSummary']);
    });

    // ============================================================
    // SETTINGS
    // ============================================================

    Route::prefix('settings')->group(function () {
        // General Settings
        Route::get('/', [AdminSettingController::class, 'index']);
        Route::post('/update', [AdminSettingController::class, 'update']);
        Route::get('/{key}', [AdminSettingController::class, 'getSetting']);

        // Favicon
        Route::post('/upload-favicon', [AdminSettingController::class, 'uploadFavicon']);
        Route::delete('/remove-favicon', [AdminSettingController::class, 'removeFavicon']);
        Route::get('/favicon', [AdminSettingController::class, 'getFavicon']);

        // Preferences
        Route::get('/preferences', [AdminSettingController::class, 'preferences']);

        // Language Management
        Route::get('/languages', [AdminSettingController::class, 'languages']);
        Route::post('/language/add', [AdminSettingController::class, 'addLanguage']);
        Route::post('/language/edit/{id}', [AdminSettingController::class, 'editLanguage']);
        Route::delete('/language/delete/{id}', [AdminSettingController::class, 'deleteLanguage']);
        Route::post('/language/set-default/{id}', [AdminSettingController::class, 'setDefaultLanguage']);
        Route::post('/language/toggle/{id}', [AdminSettingController::class, 'toggleLanguage']);

        // Timezone Management
        Route::get('/timezones', [AdminSettingController::class, 'timezones']);
        Route::post('/timezone/add', [AdminSettingController::class, 'addTimezone']);
        Route::post('/timezone/edit/{id}', [AdminSettingController::class, 'editTimezone']);
        Route::delete('/timezone/delete/{id}', [AdminSettingController::class, 'deleteTimezone']);
        Route::post('/timezone/set-default/{id}', [AdminSettingController::class, 'setDefaultTimezone']);
        Route::post('/timezone/toggle/{id}', [AdminSettingController::class, 'toggleTimezone']);

        // Theme Management
        Route::get('/themes', [AdminSettingController::class, 'themes']);
        Route::post('/theme/add', [AdminSettingController::class, 'addTheme']);
        Route::post('/theme/edit/{id}', [AdminSettingController::class, 'editTheme']);
        Route::delete('/theme/delete/{id}', [AdminSettingController::class, 'deleteTheme']);
        Route::post('/theme/set-default/{id}', [AdminSettingController::class, 'setDefaultTheme']);
        Route::post('/theme/toggle/{id}', [AdminSettingController::class, 'toggleTheme']);
    });

    // ============================================================
    // HOME BANNER
    // ============================================================

    Route::prefix('home-banner')->group(function () {
        Route::get('/', [AdminHomeBannerController::class, 'index']);
        Route::post('/update', [AdminHomeBannerController::class, 'update']);
        Route::post('/toggle-status', [AdminHomeBannerController::class, 'toggleStatus']);
        Route::get('/stats', [AdminHomeBannerController::class, 'getStats']);
        Route::post('/reset-default', [AdminHomeBannerController::class, 'resetDefault']);
        Route::post('/preview', [AdminHomeBannerController::class, 'preview']);
        Route::post('/upload-image', [AdminHomeBannerController::class, 'uploadImage']);
        Route::delete('/remove-image', [AdminHomeBannerController::class, 'removeImage']);
        Route::get('/image', [AdminHomeBannerController::class, 'getImage']);
    });

    // ============================================================
    // PRODUCT CATEGORIES MANAGEMENT
    // ============================================================

    Route::prefix('product-categories')->group(function () {
        Route::get('/', [AdminProductCategoryController::class, 'index']);
        Route::get('/create', [AdminProductCategoryController::class, 'create']);
        Route::post('/store', [AdminProductCategoryController::class, 'store']);
        Route::get('/edit/{id}', [AdminProductCategoryController::class, 'edit']);
        Route::post('/update/{id}', [AdminProductCategoryController::class, 'update']);
        Route::delete('/delete/{id}', [AdminProductCategoryController::class, 'delete']);
        Route::post('/toggle-status/{id}', [AdminProductCategoryController::class, 'toggleStatus']);
        Route::get('/view/{id}', [AdminProductCategoryController::class, 'view']);
        Route::get('/subcategories/{parentId}', [AdminProductCategoryController::class, 'subcategories']);
        Route::get('/tree', [AdminProductCategoryController::class, 'getCategoryTree']);
        Route::get('/dropdown', [AdminProductCategoryController::class, 'getForDropdown']);
        Route::get('/stats', [AdminProductCategoryController::class, 'getStats']);
        Route::post('/bulk-delete', [AdminProductCategoryController::class, 'bulkDelete']);
        Route::post('/bulk-update-status', [AdminProductCategoryController::class, 'bulkUpdateStatus']);
        Route::post('/reorder', [AdminProductCategoryController::class, 'reorder']);
        Route::get('/slug/{slug}', [AdminProductCategoryController::class, 'getBySlug']);
        Route::get('/with-product-count/{id}', [AdminProductCategoryController::class, 'getWithProductCount']);
        Route::get('/hierarchy/{id}', [AdminProductCategoryController::class, 'getHierarchy']);
    });

    // ============================================================
    // FEATURES MANAGEMENT
    // ============================================================

    Route::prefix('features')->group(function () {
        // Feature CRUD
        Route::get('/', [AdminFeatureController::class, 'index']);
        Route::post('/store', [AdminFeatureController::class, 'store']);
        Route::post('/update/{id}', [AdminFeatureController::class, 'update']);
        Route::delete('/delete/{id}', [AdminFeatureController::class, 'delete']);
        Route::post('/toggle-status/{id}', [AdminFeatureController::class, 'toggleStatus']);
        Route::get('/stats', [AdminFeatureController::class, 'getStats']);
        Route::get('/{id}', [AdminFeatureController::class, 'getFeature']);
        Route::get('/dropdown', [AdminFeatureController::class, 'getForDropdown']);
        Route::get('/with-values', [AdminFeatureController::class, 'getFeaturesWithValues']);

        // Feature Assignment
        Route::get('/assign/{featureId}', [AdminFeatureController::class, 'assign']);
        Route::post('/save-assignment/{featureId}', [AdminFeatureController::class, 'saveAssignment']);
        Route::get('/for-category/{categoryId}', [AdminFeatureController::class, 'forCategory']);

        // Feature Values
        Route::post('/store-value', [AdminFeatureController::class, 'storeValue']);
        Route::post('/update-value/{id}', [AdminFeatureController::class, 'updateValue']);
        Route::delete('/delete-value/{id}', [AdminFeatureController::class, 'deleteValue']);
        Route::post('/toggle-value-status/{id}', [AdminFeatureController::class, 'toggleValueStatus']);
        Route::get('/get-values/{featureId}', [AdminFeatureController::class, 'getValues']);

        // Bulk Operations
        Route::post('/bulk-delete', [AdminFeatureController::class, 'bulkDelete']);
        Route::post('/bulk-update-status', [AdminFeatureController::class, 'bulkUpdateStatus']);

        // Feature Categories
        Route::get('/categories', [AdminFeatureController::class, 'getFeatureCategories']);
    });

    // ============================================================
    // MENUS MANAGEMENT
    // ============================================================

    Route::prefix('menus')->group(function () {
        Route::get('/', [MenuController::class, 'index']);
        Route::get('/create', [MenuController::class, 'create']);
        Route::post('/store', [MenuController::class, 'store']);
        Route::get('/edit/{id}', [MenuController::class, 'edit']);
        Route::post('/update/{id}', [MenuController::class, 'update']);
        Route::delete('/delete/{id}', [MenuController::class, 'delete']);
        Route::post('/toggle-status/{id}', [MenuController::class, 'toggleStatus']);
        Route::post('/update-sort-order', [MenuController::class, 'updateSortOrder']);
        Route::get('/stats', [MenuController::class, 'getStats']);
        Route::get('/by-location', [MenuController::class, 'getByLocation']);
        
        Route::get('/next-order', [MenuController::class, 'getNextSortOrder']);
        Route::post('/clone/{id}', [MenuController::class, 'cloneMenu']);
        Route::get('/search', [MenuController::class, 'search']);
        Route::get('/export/csv', [MenuController::class, 'exportCsv']);
        Route::post('/clear-cache', [MenuController::class, 'clearCache']);
        Route::post('/bulk-delete', [MenuController::class, 'bulkDelete']);
        Route::post('/bulk-update-status', [MenuController::class, 'bulkUpdateStatus']);
    });
});

// ============================================================
// PUBLIC BANNER ROUTE (for frontend)
// ============================================================

Route::get('/banner', [AdminHomeBannerController::class, 'getActiveBanner']);

// ============================================================
// PUBLIC FAQ ROUTES (for frontend)
// ============================================================

Route::get('/faqs/public', [AdminFaqController::class, 'getPublicFAQs']);
Route::get('/faqs/public/{identifier}', [AdminFaqController::class, 'getPublicFaq']);