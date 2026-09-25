<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var RazorpayService
     */
    protected $razorpayService;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * CheckoutController constructor.
     */
    public function __construct()
    {
        $this->orderService = new OrderService();
        $this->razorpayService = new RazorpayService();
    }

    /**
     * Display checkout page with cart data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty. Please add items before checkout.',
            ], 422);
        }

        // Get cart data
        $cartData = $this->getCartDataForCheckout($cart);

        if (empty($cartData['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'Some items in your cart are no longer available.',
            ], 422);
        }

        // Get customer data if logged in
        $userData = null;
        $isLoggedIn = false;

        if (auth()->check()) {
            $user = auth()->user();
            $userData = $user->toArray();
            $isLoggedIn = true;
        }

        // Get enabled payment methods
        $paymentMethods = $this->getEnabledPaymentMethods();

        return response()->json([
            'success' => true,
            'data' => [
                'cart_data' => $cartData,
                'user_data' => $userData,
                'payment_methods' => $paymentMethods,
                'razorpay_key' => $this->razorpayService->isEnabled() ? $this->razorpayService->getKeyId() : null,
                'razorpay_enabled' => $this->razorpayService->isEnabled(),
                'cart_count' => $this->getCartCount(),
                'is_logged_in' => $isLoggedIn,
            ],
        ]);
    }

    /**
     * Get cart data for checkout.
     *
     * @param array $cart
     * @return array
     */
    private function getCartDataForCheckout(array $cart): array
    {
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

        // Calculations matching CartController
        $taxRate = 10;
        $tax = $subtotal * 0.10;
        $shippingThreshold = 100;
        $shippingCost = 10;
        $shipping = $subtotal > $shippingThreshold ? 0 : $shippingCost;
        $total = $subtotal + $tax + $shipping;

        return [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'formatted_subtotal' => '$' . number_format($subtotal, 2),
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'formatted_tax' => '$' . number_format($tax, 2),
            'shipping' => $shipping,
            'shipping_threshold' => $shippingThreshold,
            'formatted_shipping' => '$' . number_format($shipping, 2),
            'total' => $total,
            'formatted_total' => '$' . number_format($total, 2),
        ];
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
            $variant = ProductVariant::find($item['variant_id']);
            if (!$variant) {
                return null;
            }

            $product = Product::find($variant->product_id);
            if (!$product) {
                return null;
            }

            // Get variant features
            $variantFeatures = ProductVariantValue::select(
                'product_variant_values.*',
                'features.name as feature_name',
                'feature_values.value as option_value'
            )
            ->join('features', 'features.id = ' . 'product_variant_values.feature_id')
            ->leftJoin('feature_values', 'feature_values.id = ' . 'product_variant_values.value')
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
            $variantImage = ProductVariantImage::where('variant_id', $item['variant_id'])
                ->orderBy('is_primary', 'DESC')
                ->orderBy('sort_order', 'ASC')
                ->first();

            $image = $variantImage->image ?? $variant->image ?? $product->image ?? 'assets/images/default-product.jpg';

            return [
                'key' => $key,
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'],
                'name' => $product->name,
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
        $product = Product::find($item['product_id']);
        if (!$product) {
            return null;
        }

        $price = ($product->sale_price > 0) ? $product->sale_price : $product->price;

        return [
            'key' => $key,
            'product_id' => $product->id,
            'variant_id' => 0,
            'name' => $product->name,
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
     * Create Razorpay order.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRazorpayOrder(Request $request)
    {
        Log::debug('createRazorpayOrder - Request received');

        // Validate customer details
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'phone' => 'required|string|min:10',
            'address' => 'required|string|min:10',
            'city' => 'required|string|min:3',
            'postal_code' => 'required|string|min:4',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'create_account' => 'nullable|boolean',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate password if creating account
        $createAccount = $request->create_account ?? false;
        if ($createAccount) {
            $password = $request->password;
            if (empty($password) || strlen($password) < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is required and must be at least 8 characters when creating an account.',
                ], 422);
            }
        }

        // Get cart and validate
        $cart = Session::get('cart', []);
        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty.',
            ], 422);
        }

        // Get cart data
        $cartData = $this->getCartDataForCheckout($cart);

        if (empty($cartData['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'Some items in your cart are no longer available.',
            ], 422);
        }

        // Check if Razorpay is enabled
        if (!$this->razorpayService->isEnabled()) {
            Log::error('Razorpay is not enabled in settings');
            return response()->json([
                'success' => false,
                'message' => 'Razorpay payment is not enabled. Please contact support.',
            ], 422);
        }

        // Get Razorpay Key ID
        $razorpayKey = $this->razorpayService->getKeyId();
        if (empty($razorpayKey)) {
            Log::error('Razorpay Key ID is missing');
            return response()->json([
                'success' => false,
                'message' => 'Razorpay configuration is incomplete. Please contact support.',
            ], 422);
        }

        try {
            // Create pending order
            $customerData = $request->all();
            if ($createAccount) {
                $customerData['password'] = $request->password;
            }

            Log::debug('Creating pending order with data: ' . json_encode($customerData));

            $orderResult = $this->orderService->createPendingOrder($customerData, $cartData, 'razorpay');

            Log::debug('Order created: ' . json_encode($orderResult));

            // Create Razorpay order
            $razorpayOrder = $this->razorpayService->createOrder(
                $cartData['total'],
                $this->getCurrency(),
                'order_' . $orderResult['order_number']
            );

            Log::debug('Razorpay order created: ' . json_encode($razorpayOrder));

            // Update order with Razorpay order ID
            Order::where('id', $orderResult['order_id'])->update([
                'razorpay_order_id' => $razorpayOrder['id'],
            ]);

            // Store order ID in session for verification
            Session::put('pending_order_id', $orderResult['order_id']);
            Session::put('pending_cart', $cart);

            return response()->json([
                'success' => true,
                'data' => [
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'razorpay_key' => $razorpayKey,
                    'amount' => $cartData['total'],
                    'currency' => $this->getCurrency(),
                    'order_number' => $orderResult['order_number'],
                    'name' => $customerData['name'],
                    'email' => $customerData['email'],
                    'phone' => $customerData['phone'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            Log::error('Razorpay error trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get enabled payment methods.
     *
     * @return array
     */
    private function getEnabledPaymentMethods(): array
    {
        $methods = [];

        // Check Razorpay
        if ($this->razorpayService->isEnabled()) {
            $methods[] = [
                'id' => 'razorpay',
                'name' => 'Razorpay',
                'icon' => 'bi bi-credit-card',
            ];
        }

        // Check Stripe
        if (Setting::getSetting('payment_stripe_enabled') == '1') {
            $methods[] = [
                'id' => 'stripe',
                'name' => 'Stripe',
                'icon' => 'bi bi-credit-card',
            ];
        }

        // Check iDEAL
        if (Setting::getSetting('payment_ideal_enabled') == '1') {
            $methods[] = [
                'id' => 'ideal',
                'name' => 'iDEAL',
                'icon' => 'bi bi-bank',
            ];
        }

        return $methods;
    }

    /**
     * Get currency from settings.
     *
     * @return string
     */
    private function getCurrency(): string
    {
        $currency = Setting::getSetting('store_currency') ?? 'USD';

        // Razorpay supports these currencies
        $supported = ['INR', 'USD', 'EUR', 'GBP'];
        return in_array($currency, $supported) ? $currency : 'INR';
    }

    /**
     * Verify Razorpay payment.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        $razorpayOrderId = $request->input('razorpay_order_id');
        $razorpayPaymentId = $request->input('razorpay_payment_id');
        $razorpaySignature = $request->input('razorpay_signature');

        Log::debug('verifyPayment - Received: order_id=' . $razorpayOrderId . ', payment_id=' . $razorpayPaymentId . ', signature=' . $razorpaySignature);

        // Validate Razorpay response
        if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
            Log::error('verifyPayment - Missing required Razorpay data');
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment data received.',
            ], 422);
        }

        // Verify Razorpay payment signature
        $verification = $this->razorpayService->verifyPayment(
            $razorpayOrderId,
            $razorpayPaymentId,
            $razorpaySignature
        );

        Log::debug('verifyPayment - Verification result: ' . json_encode($verification));

        if (!$verification['success']) {
            Log::error('Payment verification failed: ' . ($verification['message'] ?? 'Unknown error'));
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please contact support.',
            ], 422);
        }

        // Get pending order
        $orderId = Session::get('pending_order_id');

        Log::debug('verifyPayment - pending_order_id from session: ' . $orderId);

        if (!$orderId) {
            Log::error('verifyPayment - No pending order ID in session');
            return response()->json([
                'success' => false,
                'message' => 'Order not found. Please contact support.',
            ], 404);
        }

        // Get order
        $order = Order::find($orderId);

        if (!$order) {
            Log::error('verifyPayment - Order not found: ' . $orderId);
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        Log::debug('verifyPayment - Order found: ' . json_encode($order));

        // Check if already paid
        if ($order->payment_status === 'paid') {
            Log::debug('verifyPayment - Order already paid');
            Session::forget('pending_cart');
            Session::forget('cart');
            Session::put('last_order_id', $orderId);
            Session::forget('pending_order_id');

            return response()->json([
                'success' => true,
                'message' => 'Payment already confirmed.',
                'redirect' => '/checkout/success',
            ]);
        }

        // Update order payment details
        $order->update([
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature,
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);

        Log::debug('verifyPayment - Order updated successfully');

        // Reduce product / variant stock
        try {
            $orderItems = OrderItem::where('order_id', $orderId)->get();

            Log::debug('verifyPayment - Order items found: ' . $orderItems->count());

            foreach ($orderItems as $orderItem) {
                $quantity = (int) ($orderItem->quantity ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                // Variant product
                if ($orderItem->variant_id && $orderItem->variant_id > 0) {
                    $variant = ProductVariant::find($orderItem->variant_id);

                    if ($variant) {
                        $newStock = max(0, ($variant->stock ?? 0) - $quantity);
                        $variant->update(['stock' => $newStock]);

                        Log::debug('Variant stock updated - Variant ID: ' . $orderItem->variant_id . ', New Stock: ' . $newStock);
                    }
                } else {
                    // Normal product
                    $product = Product::find($orderItem->product_id);

                    if ($product) {
                        $newStock = max(0, ($product->stock ?? 0) - $quantity);
                        $product->update(['stock' => $newStock]);

                        Log::debug('Product stock updated - Product ID: ' . $orderItem->product_id . ', New Stock: ' . $newStock);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('verifyPayment - Stock update failed: ' . $e->getMessage());
            // Payment is already successful, log but don't fail
        }

        // Clear cart after successful payment
        Session::forget('pending_cart');
        Session::forget('cart');
        Session::put('last_order_id', $orderId);
        Session::forget('pending_order_id');

        Log::debug('verifyPayment - Cart cleared successfully');

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed successfully!',
            'redirect' => '/checkout/success',
        ]);
    }

    /**
     * Get order success page data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function success()
    {
        $orderId = Session::get('last_order_id');

        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'No order found.',
            ], 404);
        }

        $order = Order::with('user')->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $items = OrderItem::where('order_id', $orderId)->get();

        // Process order items with product details
        $processedItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            $itemData = $item->toArray();
            $itemData['product'] = $product;

            // Calculate subtotal
            $itemSubtotal = $item->price * $item->quantity;
            $subtotal += $itemSubtotal;

            // Check if variant
            if ($item->variant_id && $item->variant_id > 0) {
                $variant = ProductVariant::find($item->variant_id);
                if ($variant) {
                    $itemData['variant'] = $variant;
                    $itemData['is_variant'] = true;

                    // Get variant features
                    $variantFeatures = ProductVariantValue::select(
                        'product_variant_values.*',
                        'features.name as feature_name',
                        'feature_values.value as option_value'
                    )
                    ->join('features', 'features.id = ' . 'product_variant_values.feature_id')
                    ->leftJoin('feature_values', 'feature_values.id = ' . 'product_variant_values.value')
                    ->where('variant_id', $item->variant_id)
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

                    $itemData['variant_features'] = $featureValues;
                    $itemData['variant_features_list'] = $featureParts;
                    $itemData['variant_display_name'] = $product->name ?? '' . (!empty($featureParts) ? ' / ' . implode(' / ', $featureParts) : '');

                    // Get variant image
                    $variantImage = ProductVariantImage::where('variant_id', $item->variant_id)
                        ->orderBy('is_primary', 'DESC')
                        ->orderBy('sort_order', 'ASC')
                        ->first();

                    $itemData['image'] = $variantImage->image ?? $variant->image ?? $product->image ?? 'assets/images/default-product.jpg';
                } else {
                    $itemData['is_variant'] = false;
                    $itemData['image'] = $product->image ?? 'assets/images/default-product.jpg';
                    $itemData['variant_features'] = [];
                    $itemData['variant_features_list'] = [];
                    $itemData['variant_display_name'] = $product->name ?? '';
                }
            } else {
                $itemData['is_variant'] = false;
                $itemData['image'] = $product->image ?? 'assets/images/default-product.jpg';
                $itemData['variant_features'] = [];
                $itemData['variant_features_list'] = [];
                $itemData['variant_display_name'] = $product->name ?? '';
            }

            $processedItems[] = $itemData;
        }

        // Calculate totals
        $taxRate = 10;
        $tax = $subtotal * 0.10;
        $shippingThreshold = 100;
        $shippingCost = 10;
        $shipping = $subtotal > $shippingThreshold ? 0 : $shippingCost;
        $total = $subtotal + $tax + $shipping;

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'items' => $processedItems,
                'subtotal' => $subtotal,
                'formatted_subtotal' => '$' . number_format($subtotal, 2),
                'tax' => $tax,
                'tax_rate' => $taxRate,
                'formatted_tax' => '$' . number_format($tax, 2),
                'shipping' => $shipping,
                'formatted_shipping' => '$' . number_format($shipping, 2),
                'total' => $total,
                'formatted_total' => '$' . number_format($total, 2),
            ],
        ]);
    }

    /**
     * Place order (redirect to checkout).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder()
    {
        return response()->json([
            'success' => true,
            'redirect' => '/checkout',
        ]);
    }

    /**
     * Test Razorpay configuration.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testRazorpay()
    {
        try {
            // Create a test order
            $testOrder = $this->razorpayService->createOrder(10, 'INR', 'test_order_' . time());

            return response()->json([
                'success' => true,
                'message' => 'Razorpay is working!',
                'data' => [
                    'test_order_id' => $testOrder['id'],
                    'test_order_status' => $testOrder['status'],
                    'test_amount' => '₹' . ($testOrder['amount'] / 100),
                    'razorpay_key' => $this->razorpayService->getKeyId(),
                    'razorpay_enabled' => $this->razorpayService->isEnabled(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [
                    'razorpay_key' => $this->razorpayService->getKeyId(),
                    'razorpay_enabled' => $this->razorpayService->isEnabled(),
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}