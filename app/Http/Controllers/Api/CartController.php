<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * @var Product
     */
    protected $productModel;

    /**
     * @var ProductVariant
     */
    protected $variantModel;

    /**
     * @var ProductVariantValue
     */
    protected $variantValueModel;

    /**
     * @var ProductVariantImage
     */
    protected $variantImageModel;

    /**
     * CartController constructor.
     */
    public function __construct()
    {
        $this->productModel = new Product();
        $this->variantModel = new ProductVariant();
        $this->variantValueModel = new ProductVariantValue();
        $this->variantImageModel = new ProductVariantImage();
    }

    /**
     * Get cart details with items and totals.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];
        $subtotal = 0;

        if (!empty($cart)) {
            foreach ($cart as $key => $item) {
                $cartItem = $this->getCartItemDetails($key, $item);
                if ($cartItem) {
                    $cartItems[] = $cartItem;
                    $subtotal += $cartItem['price'] * $cartItem['quantity'];
                }
            }
        }

        // Calculate totals
        $tax = $subtotal * 0.10; // 10% tax
        $shipping = $subtotal > 100 ? 0 : 10; // Free shipping over $100
        $total = $subtotal + $tax + $shipping;

        return response()->json([
            'success' => true,
            'data' => [
                'cart_items' => $cartItems,
                'subtotal' => $subtotal,
                'formatted_subtotal' => '$' . number_format($subtotal, 2),
                'tax' => $tax,
                'formatted_tax' => '$' . number_format($tax, 2),
                'shipping' => $shipping,
                'formatted_shipping' => '$' . number_format($shipping, 2),
                'total' => $total,
                'formatted_total' => '$' . number_format($total, 2),
                'cart_count' => $this->getCartCount(),
                'free_shipping_threshold' => 100,
                'shipping_threshold_met' => $subtotal >= 100,
            ],
        ]);
    }

    /**
     * Get cart item details.
     *
     * @param string $key
     * @param array $item
     * @return array|null
     */
    private function getCartItemDetails(string $key, array $item): ?array
    {
        // Check if it's a variant
        if (isset($item['variant_id']) && $item['variant_id'] > 0) {
            $variant = $this->variantModel->find($item['variant_id']);
            if (!$variant) {
                return null;
            }

            $product = $this->productModel->find($variant->product_id);
            if (!$product) {
                return null;
            }

            // Get variant features
            $variantFeatures = $this->variantValueModel
                ->select(
                    'product_variant_values.*',
                    'features.name as feature_name',
                    'feature_values.value as option_value'
                )
                ->join(
                    'features',
                    'features.id',
                    '=',
                    'product_variant_values.feature_id'
                )
                ->leftJoin(
                    'feature_values',
                    'feature_values.id',
                    '=',
                    'product_variant_values.value'
                )
                ->where('variant_id', $item['variant_id'])
                ->get()
                ->toArray();

            $featureParts = [];
            $featureValues = [];

            foreach ($variantFeatures as $feature) {
                $featureName = $feature['feature_name'] ?? '';
                $featureValue = $feature['option_value'] ?? $feature['value'] ?? '';

                if (!empty($featureName) && !empty($featureValue)) {
                    $featureParts[] = $featureValue;
                    $featureValues[$featureName] = $featureValue;
                }
            }

            $price = ($variant->sale_price > 0) ? $variant->sale_price : $variant->price;

            // Get variant image
            $variantImage = $this->variantImageModel->where('variant_id', $item['variant_id'])
                ->orderBy('is_primary', 'DESC')
                ->orderBy('sort_order', 'ASC')
                ->first();

            $image = $variantImage->image ?? $variant->image ?? $product->image ?? 'assets/images/default-product.jpg';

            return [
                'key' => $key,
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'],
                'name' => $product->name,
                'variant_name' => $product->name,
                'display_name' => $product->name . (!empty($featureParts) ? ' / ' . implode(' / ', $featureParts) : ''),
                'variant_features' => $featureValues,
                'variant_features_list' => $featureParts,
                'slug' => $variant->slug ?? $product->slug,
                'image' => $image,
                'price' => $price,
                'formatted_price' => '$' . number_format($price, 2),
                'quantity' => $item['quantity'],
                'max_qty' => $variant->stock ?? 0,
                'is_variant' => true,
                'stock' => $variant->stock ?? 0,
                'subtotal' => $price * $item['quantity'],
                'formatted_subtotal' => '$' . number_format($price * $item['quantity'], 2),
            ];
        }

        // Simple product
        $product = $this->productModel->find($item['product_id']);
        if (!$product) {
            return null;
        }

        $price = ($product->sale_price > 0) ? $product->sale_price : $product->price;

        return [
            'key' => $key,
            'product_id' => $product->id,
            'variant_id' => 0,
            'name' => $product->name,
            'variant_name' => '',
            'display_name' => $product->name,
            'variant_features' => [],
            'variant_features_list' => [],
            'slug' => $product->slug,
            'image' => $product->image ?? 'assets/images/default-product.jpg',
            'price' => $price,
            'formatted_price' => '$' . number_format($price, 2),
            'quantity' => $item['quantity'],
            'max_qty' => $product->stock ?? 0,
            'is_variant' => false,
            'stock' => $product->stock ?? 0,
            'subtotal' => $price * $item['quantity'],
            'formatted_subtotal' => '$' . number_format($price * $item['quantity'], 2),
        ];
    }

    /**
     * Add product to cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
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

        $productId = $request->product_id;
        $variantId = $request->variant_id;
        $quantity = (int) ($request->quantity ?? 1);

        // If variant_id is provided, add variant
        if ($variantId && $variantId > 0) {
            return $this->addVariant($variantId, $quantity, $productId);
        }

        // Otherwise add simple product
        $product = $this->productModel->find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Check if product has variants
        $hasVariants = $this->variantModel->where('product_id', $productId)
            ->where('status', 1)
            ->exists();

        if ($hasVariants) {
            // Auto-select first available variant
            $firstVariant = $this->productModel->getFirstAvailableVariant($productId);

            if ($firstVariant) {
                return $this->addVariant($firstVariant['id'], $quantity, $productId);
            }

            return response()->json([
                'success' => false,
                'message' => 'No available variants in stock.',
                'has_variants' => true,
                'out_of_stock' => true,
            ], 422);
        }

        // Check stock for simple product
        if ($product->stock < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $product->stock,
            ], 422);
        }

        $cart = Session::get('cart', []);
        $key = 'product_' . $productId;

        if (isset($cart[$key])) {
            $newQuantity = $cart[$key]['quantity'] + $quantity;
            if ($product->stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock,
                ], 422);
            }
            $cart[$key]['quantity'] = $newQuantity;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'variant_id' => 0,
            ];
        }

        Session::put('cart', $cart);

        Log::info('Product added to cart: ' . $productId . ' x ' . $quantity);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'cart_count' => $this->getCartCount(),
        ]);
    }

    /**
     * Add variant to cart.
     *
     * @param int $variantId
     * @param int $quantity
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    private function addVariant(int $variantId, int $quantity, int $productId)
    {
        $variant = $this->variantModel->find($variantId);
        $product = $this->productModel->find($productId);

        if (!$variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found.',
            ], 404);
        }

        // Check stock
        if ($variant->stock < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $variant->stock,
            ], 422);
        }

        $cart = Session::get('cart', []);
        $key = 'variant_' . $variantId;

        if (isset($cart[$key])) {
            $newQuantity = $cart[$key]['quantity'] + $quantity;
            if ($variant->stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $variant->stock,
                ], 422);
            }
            $cart[$key]['quantity'] = $newQuantity;
        } else {
            $cart[$key] = [
                'product_id' => $variant->product_id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        Session::put('cart', $cart);

        Log::info('Variant added to cart: ' . $variantId . ' x ' . $quantity);

        return response()->json([
            'success' => true,
            'message' => 'Variant added to cart successfully.',
            'cart_count' => $this->getCartCount(),
        ]);
    }

    /**
     * Update cart item quantity.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $key = $request->key;
        $quantity = (int) $request->quantity;

        $cart = Session::get('cart', []);

        if (!isset($cart[$key])) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart.',
            ], 404);
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
            Session::put('cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart_count' => $this->getCartCount(),
            ]);
        }

        // Check stock
        $item = $cart[$key];
        if (isset($item['variant_id']) && $item['variant_id'] > 0) {
            $variant = $this->variantModel->find($item['variant_id']);
            if ($variant && $variant->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $variant->stock,
                ], 422);
            }
        } else {
            $product = $this->productModel->find($item['product_id']);
            if ($product && $product->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $product->stock,
                ], 422);
            }
        }

        $cart[$key]['quantity'] = $quantity;
        Session::put('cart', $cart);

        // Get updated cart details
        $cartItem = $this->getCartItemDetails($key, $cart[$key]);
        $subtotal = $this->calculateSubtotal($cart);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
            'cart_count' => $this->getCartCount(),
            'item' => $cartItem,
            'subtotal' => $subtotal,
            'formatted_subtotal' => '$' . number_format($subtotal, 2),
            'tax' => $subtotal * 0.10,
            'formatted_tax' => '$' . number_format($subtotal * 0.10, 2),
            'shipping' => $subtotal > 100 ? 0 : 10,
            'formatted_shipping' => '$' . number_format($subtotal > 100 ? 0 : 10, 2),
            'total' => $subtotal + ($subtotal * 0.10) + ($subtotal > 100 ? 0 : 10),
            'formatted_total' => '$' . number_format($subtotal + ($subtotal * 0.10) + ($subtotal > 100 ? 0 : 10), 2),
        ]);
    }

    /**
     * Remove item from cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $key = $request->key;

        Log::debug('Remove cart key received: ' . $key);

        if (empty($key)) {
            return response()->json([
                'success' => false,
                'message' => 'No item key provided.',
            ], 422);
        }

        $cart = Session::get('cart', []);

        Log::debug('Current cart keys: ' . implode(', ', array_keys($cart)));

        // Check if key exists directly
        if (isset($cart[$key])) {
            unset($cart[$key]);
            Session::put('cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart_count' => $this->getCartCount(),
            ]);
        }

        // Try to find the key with different variations
        $foundKey = null;
        foreach (array_keys($cart) as $cartKey) {
            if (trim($cartKey) === trim($key)) {
                $foundKey = $cartKey;
                break;
            }
        }

        if ($foundKey !== null) {
            unset($cart[$foundKey]);
            Session::put('cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart_count' => $this->getCartCount(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Item not found in cart. Please refresh and try again.',
        ], 404);
    }

    /**
     * Clear cart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        Session::forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
            'cart_count' => 0,
        ]);
    }

    /**
     * Get cart count.
     *
     * @return int
     */
    protected function getCartCount(): int
    {
        $cart = Session::get('cart', []);
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Calculate subtotal.
     *
     * @param array $cart
     * @return float
     */
    private function calculateSubtotal(array $cart): float
    {
        $subtotal = 0;

        foreach ($cart as $item) {
            if (isset($item['variant_id']) && $item['variant_id'] > 0) {
                $variant = $this->variantModel->find($item['variant_id']);
                if ($variant) {
                    $price = ($variant->sale_price > 0) ? $variant->sale_price : $variant->price;
                    $subtotal += $price * $item['quantity'];
                }
            } else {
                $product = $this->productModel->find($item['product_id']);
                if ($product) {
                    $price = ($product->sale_price > 0) ? $product->sale_price : $product->price;
                    $subtotal += $price * $item['quantity'];
                }
            }
        }

        return $subtotal;
    }

    /**
     * Get cart summary (for header/badge).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary()
    {
        $cart = Session::get('cart', []);
        $count = $this->getCartCount();
        $subtotal = $this->calculateSubtotal($cart);

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
                'subtotal' => $subtotal,
                'formatted_subtotal' => '$' . number_format($subtotal, 2),
            ],
        ]);
    }

    /**
     * Check if cart has items.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasItems()
    {
        $cart = Session::get('cart', []);
        $hasItems = !empty($cart);

        return response()->json([
            'success' => true,
            'data' => [
                'has_items' => $hasItems,
                'count' => $this->getCartCount(),
            ],
        ]);
    }

    /**
     * Get cart items only (without totals).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function items()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];

        if (!empty($cart)) {
            foreach ($cart as $key => $item) {
                $cartItem = $this->getCartItemDetails($key, $item);
                if ($cartItem) {
                    $cartItems[] = $cartItem;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $cartItems,
            'count' => $this->getCartCount(),
        ]);
    }

    /**
     * Move wishlist item to cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveFromWishlist(Request $request)
    {
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

        $productId = $request->product_id;
        $variantId = $request->variant_id;
        $quantity = (int) ($request->quantity ?? 1);

        // Add to cart
        $result = $this->add($request);

        if ($result->getStatusCode() === 200 && $result->getData()->success) {
            // Remove from wishlist (if logged in or guest)
            // This would be handled by the WishlistController

            return response()->json([
                'success' => true,
                'message' => 'Item moved to cart successfully.',
                'cart_count' => $this->getCartCount(),
            ]);
        }

        return $result;
    }

    /**
     * Apply coupon to cart.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // This is a placeholder - implement coupon logic
        // Check coupon in database, validate, apply discount

        $couponCode = $request->coupon_code;

        // Example response
        return response()->json([
            'success' => false,
            'message' => 'Coupon functionality not implemented yet.',
        ], 501);
    }

    /**
     * Remove coupon from cart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCoupon()
    {
        // This is a placeholder - remove coupon from session

        return response()->json([
            'success' => true,
            'message' => 'Coupon removed successfully.',
        ]);
    }
}
