<?php
// app/Http/Controllers/Api/WishlistController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ResolvesAuthUser;   // ← ADD
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
    use ResolvesAuthUser;   // ← ADD

    protected $wishlistModel;
    protected $productModel;
    protected $variantModel;

    public function __construct()
    {
        $this->wishlistModel = new Wishlist();
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
    }

    public function index(Request $request)
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to access your wishlist.',
            ], 401);
        }
        $userId = $user->id;

        try {
            $rows = $this->wishlistModel
                ->where('user_id', $userId)
                ->get(['id', 'product_id', 'variant_id']);

            $pairs = $rows->map(fn($r) => [
                'product_id' => (int) $r->product_id,
                'variant_id' => (int) ($r->variant_id ?? 0),
            ])->values()->all();

            $items = [];
            try {
                if (method_exists($this->wishlistModel, 'getWishlistItemsWithVariants')) {
                    $items = $this->wishlistModel->getWishlistItemsWithVariants($userId);
                }
            } catch (\Throwable $e) {
                Log::warning('getWishlistItemsWithVariants failed: ' . $e->getMessage());
                $items = $pairs;
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
            Log::error('Wishlist index failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not load wishlist.',
            ], 500);
        }
    }

    public function toggle(Request $request)
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
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
        $product = $this->productModel->find($productId);

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $userId = $user->id;                    // ← CHANGED

        $result = $this->wishlistModel->toggleWishlist($userId, $productId, $variantId);

        if ($result['action'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }

        $count = $this->wishlistModel->getWishlistCount($userId);

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
        $user = $this->resolveUser();           // ← CHANGED
        $userId = $user?->id;

        if (!$userId) {
            return response()->json([
                'success' => true,
                'data' => ['wishlist' => [], 'count' => 0],
            ]);
        }

        $items = $request->input('items');
        if (is_string($items)) $items = json_decode($items, true);

        if (empty($items) || !is_array($items)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'wishlist' => [],
                    'count' => $this->wishlistModel->getWishlistCount($userId),
                ],
            ]);
        }

        $productIds = array_column($items, 'product_id');

        $rows = $this->wishlistModel
            ->where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'variant_id']);

        $existing = [];
        foreach ($rows as $r) {
            $existing[$r->product_id . '-' . ($r->variant_id ?? 0)] = true;
        }

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

        $user = $this->resolveUser();           // ← CHANGED
        $userId = $user?->id;

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

    public function remove(Request $request)
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
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

        $userId = $user->id;                    // ← CHANGED
        $productId = $request->product_id;
        $variantId = $request->variant_id ?? 0;

        $deleted = $this->wishlistModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist.',
                'count' => $this->wishlistModel->getWishlistCount($userId),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove product from wishlist.',
        ], 500);
    }

    public function clear()
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to manage your wishlist.',
            ], 401);
        }

        $userId = $user->id;                    // ← CHANGED
        $deleted = $this->wishlistModel->where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wishlist cleared successfully.',
            'count' => 0,
        ]);
    }

    public function count()
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
            return response()->json(['success' => true, 'count' => 0]);
        }
        return response()->json([
            'success' => true,
            'count' => $this->wishlistModel->getWishlistCount($user->id),
        ]);
    }

    public function moveToCart(Request $request)
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login.'], 401);
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

        $userId = $user->id;                    // ← CHANGED
        $productId = $request->product_id;
        $variantId = $request->variant_id ?? 0;
        $quantity = $request->quantity ?? 1;

        $exists = $this->wishlistModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->exists();

        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Item not found in wishlist.'], 404);
        }

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

        $this->wishlistModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item moved to cart successfully.',
            'wishlist_count' => $this->wishlistModel->getWishlistCount($userId),
            'cart_count' => $this->getCartCount(),
        ]);
    }

    public function moveAllToCart()
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login.'], 401);
        }

        $userId = $user->id;                    // ← CHANGED
        $items = $this->wishlistModel->where('user_id', $userId)->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Wishlist is empty.'], 422);
        }

        $cart = session()->get('cart', []);
        $movedCount = 0;

        foreach ($items as $item) {
            $key = $item->variant_id > 0 ? 'variant_' . $item->variant_id : 'product_' . $item->product_id;
            if (isset($cart[$key])) $cart[$key]['quantity'] += 1;
            else $cart[$key] = [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => 1,
            ];
            $movedCount++;
        }

        session()->put('cart', $cart);
        $this->wishlistModel->where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All items moved to cart successfully.',
            'moved_count' => $movedCount,
            'cart_count' => $this->getCartCount(),
            'wishlist_count' => 0,
        ]);
    }

    public function bulkAdd(Request $request)
    {
        $user = $this->resolveUser();           // ← CHANGED
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login.'], 401);
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

        $userId = $user->id;                    // ← CHANGED
        $addedCount = 0;

        foreach ($request->items as $item) {
            $pid = $item['product_id'];
            $vid = $item['variant_id'] ?? 0;

            $exists = $this->wishlistModel
                ->where('user_id', $userId)
                ->where('product_id', $pid)
                ->where('variant_id', $vid)
                ->exists();

            if (!$exists) {
                $this->wishlistModel->create([
                    'user_id' => $userId,
                    'product_id' => $pid,
                    'variant_id' => $vid,
                ]);
                $addedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $addedCount . ' items added to wishlist.',
            'added_count' => $addedCount,
            'count' => $this->wishlistModel->getWishlistCount($userId),
        ]);
    }

    protected function getCartCount(): int
    {
        $cart = session()->get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }
}