<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    protected $productModel;
    protected $variantModel;
    protected $variantValueModel;
    protected $variantImageModel;
    protected $cartModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
        $this->variantValueModel = new ProductVariantValue();
        $this->variantImageModel = new ProductVariantImage();
        $this->cartModel = new Cart();
    }

    /**
     * GET /api/cart
     */
    public function index()
    {
        $userId = Auth::id();
        $cartItems = $this->buildCartItems($userId);
        $subtotal = array_sum(array_column($cartItems, 'subtotal'));

        $tax = round($subtotal * 0.10, 2);
        $shipping = $subtotal > 100 ? 0 : 10;
        $total = $subtotal + $tax + $shipping;

        return response()->json([
            'success' => true,
            'data' => [
                'cart_items' => $cartItems,
                'subtotal' => $subtotal,
                'formatted_subtotal' => '₹' . number_format($subtotal, 2),
                'tax' => $tax,
                'formatted_tax' => '₹' . number_format($tax, 2),
                'shipping' => $shipping,
                'formatted_shipping' => '₹' . number_format($shipping, 2),
                'total' => $total,
                'formatted_total' => '₹' . number_format($total, 2),
                'cart_count' => $this->getCartCount($userId),
                'free_shipping_threshold' => 100,
                'shipping_threshold_met' => $subtotal >= 100,
            ],
        ]);
    }

    /**
     * POST /api/cart/add
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        $productId = (int) $request->product_id;
        $variantId = (int) ($request->variant_id ?? 0);
        $quantity = (int) ($request->quantity ?? 1);

        // If variant_id is 0 but product has variants, auto-pick the first available
        if ($variantId === 0) {
            $hasVariants = $this->variantModel
                ->where('product_id', $productId)
                ->where('status', 1)
                ->exists();

            if ($hasVariants) {
                $firstVariant = $this->variantModel
                    ->where('product_id', $productId)
                    ->where('status', 1)
                    ->where('stock', '>', 0)
                    ->orderBy('id')
                    ->first();

                if (!$firstVariant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No available variants in stock.',
                        'has_variants' => true,
                        'out_of_stock' => true,
                    ], 422);
                }

                $variantId = (int) $firstVariant->id;
            }
        }

        // Validate stock
        if ($variantId > 0) {
            $variant = $this->variantModel->find($variantId);
            if (!$variant || $variant->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . ($variant->stock ?? 0),
                ], 422);
            }
        } else {
            $product = $this->productModel->find($productId);
            if (!$product || $product->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . ($product->stock ?? 0),
                ], 422);
            }
        }

        // Upsert into carts
        $row = $this->cartModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($row) {
            $newQty = $row->quantity + $quantity;

            // Stock check for new quantity
            $max = $variantId > 0
                ? ($this->variantModel->find($variantId)->stock ?? 0)
                : ($this->productModel->find($productId)->stock ?? 0);

            if ($newQty > $max) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $max,
                ], 422);
            }

            $row->update(['quantity' => $newQty]);
        } else {
            $this->cartModel->create([
                'user_id'    => $userId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
            ]);
        }

        Log::info("Cart add: user={$userId} product={$productId} variant={$variantId} qty={$quantity}");

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'cart_count' => $this->getCartCount($userId),
        ]);
    }

    /**
     * POST /api/cart/update
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key'      => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        [$productId, $variantId] = $this->parseKey($request->key);

        $row = $this->cartModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart.',
            ], 404);
        }

        $qty = (int) $request->quantity;

        if ($qty <= 0) {
            $row->delete();
            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart_count' => $this->getCartCount($userId),
            ]);
        }

        // Stock check
        $max = $variantId > 0
            ? ($this->variantModel->find($variantId)->stock ?? 0)
            : ($this->productModel->find($productId)->stock ?? 0);

        if ($qty > $max) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $max,
            ], 422);
        }

        $row->update(['quantity' => $qty]);

        // Return fresh totals
        $cartItems = $this->buildCartItems($userId);
        $subtotal = array_sum(array_column($cartItems, 'subtotal'));
        $tax = round($subtotal * 0.10, 2);
        $shipping = $subtotal > 100 ? 0 : 10;

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
            'cart_count' => $this->getCartCount($userId),
            'subtotal' => $subtotal,
            'formatted_subtotal' => '₹' . number_format($subtotal, 2),
            'tax' => $tax,
            'formatted_tax' => '₹' . number_format($tax, 2),
            'shipping' => $shipping,
            'formatted_shipping' => '₹' . number_format($shipping, 2),
            'total' => $subtotal + $tax + $shipping,
            'formatted_total' => '₹' . number_format($subtotal + $tax + $shipping, 2),
        ]);
    }

    /**
     * POST /api/cart/remove
     */
    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), ['key' => 'required|string']);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = Auth::id();
        [$productId, $variantId] = $this->parseKey($request->key);

        $deleted = $this->cartModel
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_count' => $this->getCartCount($userId),
        ]);
    }

    /**
     * POST /api/cart/clear
     */
    public function clear()
    {
        $userId = Auth::id();
        $this->cartModel->where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
            'cart_count' => 0,
        ]);
    }

    /**
     * GET /api/cart/summary
     */
    public function summary()
    {
        $userId = Auth::id();
        $items = $this->buildCartItems($userId);
        $subtotal = array_sum(array_column($items, 'subtotal'));

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $this->getCartCount($userId),
                'subtotal' => $subtotal,
                'formatted_subtotal' => '₹' . number_format($subtotal, 2),
            ],
        ]);
    }

    /**
     * GET /api/cart/has-items
     */
    public function hasItems()
    {
        $userId = Auth::id();
        return response()->json([
            'success' => true,
            'data' => [
                'has_items' => $this->getCartCount($userId) > 0,
                'count' => $this->getCartCount($userId),
            ],
        ]);
    }

    /**
     * GET /api/cart/items
     */
    public function items()
    {
        $userId = Auth::id();
        $items = $this->buildCartItems($userId);
        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => $this->getCartCount($userId),
        ]);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Build cart items with product/variant details.
     * Key format: "{productId}-{variantId}" (e.g., "21-11" or "23-0").
     */
    private function buildCartItems(int $userId): array
    {
        $rows = $this->cartModel->where('user_id', $userId)->get();
        $out = [];

        foreach ($rows as $row) {
            $product = $this->productModel->find($row->product_id);
            if (!$product) continue;

            $variantId = (int) $row->variant_id;
            $isVariant = $variantId > 0;
            $variant = $isVariant ? $this->variantModel->find($variantId) : null;

            // Price: variant sale > variant price > product sale > product price
            if ($isVariant && $variant) {
                $price = ($variant->sale_price > 0) ? $variant->sale_price : $variant->price;
            } else {
                $price = ($product->sale_price > 0) ? $product->sale_price : $product->price;
            }

            // Feature values (portable lookup)
            $featureParts = [];
            $featureValues = [];
            if ($isVariant) {
                $featureRows = \DB::table('product_variant_values as pvv')
                    ->join('features as f', 'f.id', '=', 'pvv.feature_id')
                    ->where('pvv.variant_id', $variantId)
                    ->select('f.id as feature_id', 'f.name as feature_name', 'pvv.value as feature_value')
                    ->get();

                $featureIds = $featureRows->pluck('feature_id')->unique()->filter()->values()->toArray();
                $valueMap = [];
                if (!empty($featureIds)) {
                    foreach (\DB::table('feature_values')->whereIn('feature_id', $featureIds)->get(['id', 'feature_id', 'value']) as $fv) {
                        $valueMap[$fv->feature_id . ':' . $fv->id] = $fv->value;
                    }
                }

                foreach ($featureRows as $fr) {
                    $name = $fr->feature_name;
                    $value = $valueMap[$fr->feature_id . ':' . $fr->feature_value] ?? $fr->feature_value;
                    if ($name && $value) {
                        $featureParts[] = $value;
                        $featureValues[$name] = $value;
                    }
                }
            }

            // Image
            $image = null;
            if ($isVariant) {
                $variantImage = \DB::table('product_variant_images')
                    ->where('variant_id', $variantId)
                    ->first();
                $image = $variantImage->image ?? $variant->image ?? $product->image ?? null;
            } else {
                $image = $product->image ?? null;
            }

            $subtotal = $price * $row->quantity;

            $out[] = [
                'key'                   => $row->product_id . '-' . $variantId,
                'cart_id'               => $row->id,
                'product_id'            => $product->id,
                'variant_id'            => $variantId,
                'name'                  => $product->name,
                'variant_name'          => $variant->variant_name ?? '',
                'display_name'          => $product->name . (!empty($featureParts) ? ' / ' . implode(' / ', $featureParts) : ''),
                'variant_features'      => $featureValues,
                'variant_features_list' => $featureParts,
                'slug'                  => $variant->slug ?? $product->slug,
                'image'                 => $image,
                'price'                 => $price,
                'formatted_price'       => '₹' . number_format($price, 2),
                'quantity'              => $row->quantity,
                'max_qty'               => $isVariant ? ($variant->stock ?? 0) : ($product->stock ?? 0),
                'is_variant'            => $isVariant,
                'stock'                 => $isVariant ? ($variant->stock ?? 0) : ($product->stock ?? 0),
                'subtotal'              => $subtotal,
                'formatted_subtotal'    => '₹' . number_format($subtotal, 2),
            ];
        }

        return $out;
    }

    private function getCartCount(int $userId): int
    {
        return (int) $this->cartModel->where('user_id', $userId)->sum('quantity');
    }

    /**
     * Parse "21-11" or "21-0" into [productId, variantId].
     */
    private function parseKey(string $key): array
    {
        // Accept "21-11", "product_21", "variant_11", or "cart_5"
        if (preg_match('/^(\d+)-(\d+)$/', $key, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        if (preg_match('/^product_(\d+)$/', $key, $m)) {
            return [(int) $m[1], 0];
        }
        if (preg_match('/^variant_(\d+)$/', $key, $m)) {
            $variant = $this->variantModel->find((int) $m[1]);
            return $variant ? [(int) $variant->product_id, (int) $variant->id] : [0, 0];
        }
        if (preg_match('/^cart_(\d+)$/', $key, $m)) {
            $row = $this->cartModel->find((int) $m[1]);
            return $row ? [(int) $row->product_id, (int) $row->variant_id] : [0, 0];
        }
        return [0, 0];
    }

    public function moveFromWishlist(Request $request)
    {
        return $this->add($request);
    }

    public function applyCoupon(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Coupon functionality not implemented yet.',
        ], 501);
    }

    public function removeCoupon()
    {
        return response()->json([
            'success' => true,
            'message' => 'Coupon removed successfully.',
        ]);
    }
}