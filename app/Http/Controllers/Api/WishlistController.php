<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    /**
     * @var Wishlist
     */
    protected $wishlistModel;

    /**
     * @var Product
     */
    protected $productModel;

    /**
     * @var ProductVariant
     */
    protected $variantModel;

    /**
     * WishlistController constructor.
     */
    public function __construct()
    {
        $this->wishlistModel = new Wishlist();
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
    }

    public function index(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to access your wishlist.',
            ], 401);
        }

        $userId = Auth::id();

        try {
            // Fetch raw rows directly — no dependency on model helper methods
            $rows = $this->wishlistModel
                ->where('user_id', $userId)
                ->get(['id', 'product_id', 'variant_id']);

            $pairs = $rows->map(fn($r) => [
                'product_id' => (int) $r->product_id,
                'variant_id' => (int) ($r->variant_id ?? 0),
            ])->values()->all();

            // Try to enrich with product/variant detail — but never fail if it breaks
            $items = [];
            try {
                if (method_exists($this->wishlistModel, 'getWishlistItemsWithVariants')) {
                    $items = $this->wishlistModel->getWishlistItemsWithVariants($userId);
                }
            } catch (\Throwable $e) {
                Log::warning('getWishlistItemsWithVariants failed: ' . $e->getMessage());
                $items = $pairs; // fall back to bare pairs
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'items'       => $items,
                    'pairs'       => $pairs,
                    'count'       => count($pairs),
                    'product_ids' => array_column($pairs, 'product_id'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Wishlist index failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not load wishlist.',
            ], 500);
        }
    }

    /**
     * Toggle wishlist item (add/remove).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to manage your wishlist.',
                'redirect' => '/auth/login',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $productId = $request->product_id;
        $variantId = $request->variant_id ?? 0;

        // Check if product exists
        $product = $this->productModel->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $userId = Auth::id();

        // Toggle wishlist using both product ID and variant ID
        $result = $this->wishlistModel->toggleWishlist($userId, $productId, $variantId);

        if ($result['action'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }

        // Get updated count
        $count = $this->wishlistModel->getWishlistCount($userId);

        Log::info('User ' . $userId . ' ' . $result['action'] . ' product ' . $productId . ' (variant: ' . $variantId . ') to wishlist');

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'message' => $result['message'],
            'count' => $count,
            'in_wishlist' => $result['action'] === 'added',
        ]);
    }

    public function status(Request $request)
    {
        $items = $request->input('items');
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => true,
                'data' => ['wishlist' => [], 'count' => 0],
            ]);
        }

        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (empty($items) || !is_array($items)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'wishlist' => [],
                    'count' => $this->wishlistModel->getWishlistCount($userId),
                ],
            ]);
        }

        // ✅ One query to rule them all — filter wishlist by the given pairs
        $productIds = array_column($items, 'product_id');
        $variantIds = array_column($items, 'variant_id');

        $rows = $this->wishlistModel
            ->where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'variant_id']);

        // Build a lookup set: "product_id-variant_id"
        $existing = [];
        foreach ($rows as $r) {
            $existing[$r->product_id . '-' . ($r->variant_id ?? 0)] = true;
        }

        // Return only the pairs actually in the wishlist
        $wishlistItems = [];
        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $vid = (int) ($item['variant_id'] ?? 0);
            if ($pid > 0 && isset($existing[$pid . '-' . $vid])) {
                $wishlistItems[] = ['product_id' => $pid, 'variant_id' => $vid];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'wishlist' => $wishlistItems,
                'count' => $this->wishlistModel->getWishlistCount($userId),
            ],
        ]);
    }

    /**
     * Check if a specific product is in wishlist.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => true,
                'in_wishlist' => false,
                'count' => 0,
            ]);
        }

        $productId = $request->product_id;
        $variantId = $request->variant_id ?? 0;

        $inWishlist = $this->wishlistModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->exists();

        return response()->json([
            'success' => true,
            'in_wishlist' => $inWishlist,
            'count' => $this->wishlistModel->getWishlistCount($userId),
        ]);
    }

    /**
     * Remove an item from wishlist.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to manage your wishlist.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        $productId = $request->product_id;
        $variantId = $request->variant_id ?? 0;

        $deleted = $this->wishlistModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->delete();

        if ($deleted) {
            $count = $this->wishlistModel->getWishlistCount($userId);

            Log::info('User ' . $userId . ' removed product ' . $productId . ' (variant: ' . $variantId . ') from wishlist');

            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist.',
                'count' => $count,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove product from wishlist.',
        ], 500);
    }

    /**
     * Clear entire wishlist.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to manage your wishlist.',
            ], 401);
        }

        $userId = Auth::id();

        $deleted = $this->wishlistModel->where('user_id', $userId)->delete();

        if ($deleted) {
            Log::info('User ' . $userId . ' cleared wishlist (' . $deleted . ' items)');

            return response()->json([
                'success' => true,
                'message' => 'Wishlist cleared successfully.',
                'count' => 0,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to clear wishlist.',
        ], 500);
    }

    /**
     * Get wishlist count only.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function count()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => true,
                'count' => 0,
            ]);
        }

        $userId = Auth::id();
        $count = $this->wishlistModel->getWishlistCount($userId);

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Move wishlist item to cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveToCart(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to manage your wishlist.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        $productId = $request->product_id;
        $variantId = $request->variant_id ?? 0;
        $quantity = $request->quantity ?? 1;

        // Check if item exists in wishlist
        $exists = $this->wishlistModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in wishlist.',
            ], 404);
        }

        // Add to cart (using the CartController logic)
        $cart = session()->get('cart', []);
        $key = $variantId > 0 ? 'variant_' . $variantId : 'product_' . $productId;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        session()->put('cart', $cart);

        // Remove from wishlist
        $this->wishlistModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->delete();

        $wishlistCount = $this->wishlistModel->getWishlistCount($userId);
        $cartCount = $this->getCartCount();

        Log::info('User ' . $userId . ' moved product ' . $productId . ' from wishlist to cart');

        return response()->json([
            'success' => true,
            'message' => 'Item moved to cart successfully.',
            'wishlist_count' => $wishlistCount,
            'cart_count' => $cartCount,
        ]);
    }

    /**
     * Move all wishlist items to cart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveAllToCart()
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to manage your wishlist.',
            ], 401);
        }

        $userId = Auth::id();
        $items = $this->wishlistModel->where('user_id', $userId)->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist is empty.',
            ], 422);
        }

        $cart = session()->get('cart', []);
        $movedCount = 0;

        foreach ($items as $item) {
            $key = $item->variant_id > 0 ? 'variant_' . $item->variant_id : 'product_' . $item->product_id;

            if (isset($cart[$key])) {
                $cart[$key]['quantity'] += 1;
            } else {
                $cart[$key] = [
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => 1,
                ];
            }
            $movedCount++;
        }

        session()->put('cart', $cart);

        // Clear wishlist
        $this->wishlistModel->where('user_id', $userId)->delete();

        $cartCount = $this->getCartCount();

        Log::info('User ' . $userId . ' moved all ' . $movedCount . ' items from wishlist to cart');

        return response()->json([
            'success' => true,
            'message' => 'All items moved to cart successfully.',
            'moved_count' => $movedCount,
            'cart_count' => $cartCount,
            'wishlist_count' => 0,
        ]);
    }

    /**
     * Get cart count from session.
     *
     * @return int
     */
    protected function getCartCount(): int
    {
        $cart = session()->get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Bulk add products to wishlist.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAdd(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to manage your wishlist.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        $addedCount = 0;

        foreach ($request->items as $item) {
            $productId = $item['product_id'];
            $variantId = $item['variant_id'] ?? 0;

            // Check if already exists
            $exists = $this->wishlistModel
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->exists();

            if (!$exists) {
                $this->wishlistModel->create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                ]);
                $addedCount++;
            }
        }

        $count = $this->wishlistModel->getWishlistCount($userId);

        return response()->json([
            'success' => true,
            'message' => $addedCount . ' items added to wishlist.',
            'added_count' => $addedCount,
            'count' => $count,
        ]);
    }
}
