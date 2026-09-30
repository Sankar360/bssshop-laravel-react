<?php
// app/Http/Controllers/Api/CheckoutController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
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
use App\Models\Cart;   // ← add this at the top


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
        $this->orderService    = new OrderService();
        $this->razorpayService = new RazorpayService();
    }

    /* =================================================================
     |  CHECKOUT PAGE
     ================================================================= */

    /**
     * Display checkout page with cart data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
{
    // ✅ Read from DB cart, not session
    $userId = auth()->id();

    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'Please log in to checkout.',
        ], 401);
    }

    $cartRows = Cart::where('user_id', $userId)->get();

    if ($cartRows->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Your cart is empty. Please add items before checkout.',
        ], 422);
    }

    $cartData = $this->buildCartDataFromRows($cartRows);

    if (empty($cartData['items'])) {
        return response()->json([
            'success' => false,
            'message' => 'Some items in your cart are no longer available.',
        ], 422);
    }

    $userData    = null;
    $isLoggedIn  = false;

    if (auth()->check()) {
        $user       = auth()->user();
        $userData   = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'address'     => $user->address ?? '',
            'city'        => $user->city ?? '',
            'state'       => $user->state ?? '',
            'postal_code' => $user->postal_code ?? '',
            'country'     => $user->country ?? '',
        ];
        $isLoggedIn = true;
    }

    $paymentMethods = $this->getEnabledPaymentMethods();

    return response()->json([
        'success' => true,
        'data' => [
            'cart_data'        => $cartData,
            'user_data'        => $userData,
            'payment_methods'  => $paymentMethods,
            'razorpay_key'     => $this->razorpayService->isEnabled() ? $this->razorpayService->getKeyId() : null,
            'razorpay_enabled' => $this->razorpayService->isEnabled(),
            'cart_count'       => $cartRows->sum('quantity'),
            'is_logged_in'     => $isLoggedIn,
        ],
    ]);
}

/**
 * Build cart data from `carts` table rows.
 */
private function buildCartDataFromRows($cartRows): array
{
    $items    = [];
    $subtotal = 0;

    foreach ($cartRows as $row) {
        $item = $this->getCartRowDetails($row);
        if ($item) {
            $items[]   = $item;
            $subtotal += $item['price'] * $item['quantity'];
        }
    }

    $taxRate           = 10;
    $tax               = round($subtotal * 0.10, 2);
    $shippingThreshold = 100;
    $shipping          = $subtotal > $shippingThreshold ? 0 : 10;
    $total             = $subtotal + $tax + $shipping;

    return [
        'items'              => $items,
        'subtotal'           => $subtotal,
        'formatted_subtotal' => '₹' . number_format($subtotal, 2),
        'tax'                => $tax,
        'tax_rate'           => $taxRate,
        'formatted_tax'      => '₹' . number_format($tax, 2),
        'shipping'           => $shipping,
        'shipping_threshold' => $shippingThreshold,
        'formatted_shipping' => '₹' . number_format($shipping, 2),
        'total'              => $total,
        'formatted_total'    => '₹' . number_format($total, 2),
    ];
}

/**
 * Get cart item details from a `carts` table row.
 */
private function getCartRowDetails(Cart $row): ?array
{
    $productId = (int) $row->product_id;
    $variantId = (int) $row->variant_id;

    // ---- Variant product ----
    if ($variantId > 0) {
        $variant = ProductVariant::find($variantId);
        if (!$variant) return null;

        $product = Product::find($variant->product_id);
        if (!$product) return null;

        // (Keep your existing feature lookup logic — it's fine.)
        $variantFeatures = ProductVariantValue::select(
            'product_variant_values.*',
            'features.name as feature_name',
            'feature_values.value as option_value'
        )
            ->join('features', 'features.id', '=', 'product_variant_values.feature_id')
            ->leftJoin('feature_values', 'feature_values.id', '=', 'product_variant_values.value')
            ->where('product_variant_values.variant_id', $variantId)
            ->get()
            ->toArray();

        $featureParts  = [];
        $featureValues = [];

        foreach ($variantFeatures as $feature) {
            $name  = $feature['feature_name'] ?? '';
            $value = $feature['option_value'] ?? $feature['value'] ?? '';
            if ($name && $value) {
                $featureParts[]   = $value;
                $featureValues[$name] = $value;
            }
        }

        $price = ($variant->sale_price > 0 && $variant->sale_price < $variant->price)
            ? $variant->sale_price
            : $variant->price;

        $variantImage = ProductVariantImage::where('variant_id', $variantId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->first();

        $image = $variantImage->image
            ?? $variant->image
            ?? $product->image
            ?? 'assets/images/default-product.jpg';

        return [
            'key'                   => $productId . '-' . $variantId,
            'product_id'            => $product->id,
            'variant_id'            => $variantId,
            'name'                  => $product->name,
            'display_name'          => $product->name . ($featureParts ? ' / ' . implode(' / ', $featureParts) : ''),
            'variant_features'      => $featureValues,
            'variant_features_list' => $featureParts,
            'slug'                  => $variant->slug ?? $product->slug,
            'image'                 => $image,
            'price'                 => $price,
            'formatted_price'       => '₹' . number_format($price, 2),
            'quantity'              => (int) $row->quantity,
            'max_qty'               => $variant->stock ?? 0,
            'is_variant'            => true,
            'stock'                 => $variant->stock ?? 0,
            'subtotal'              => $price * $row->quantity,
            'formatted_subtotal'    => '₹' . number_format($price * $row->quantity, 2),
        ];
    }

    // ---- Simple product ----
    $product = Product::find($productId);
    if (!$product) return null;

    $price = ($product->sale_price > 0 && $product->sale_price < $product->price)
        ? $product->sale_price
        : $product->price;

    return [
        'key'                   => $productId . '-0',
        'product_id'            => $product->id,
        'variant_id'            => 0,
        'name'                  => $product->name,
        'display_name'          => $product->name,
        'variant_features'      => [],
        'variant_features_list' => [],
        'slug'                  => $product->slug,
        'image'                 => $product->image ?? 'assets/images/default-product.jpg',
        'price'                 => $price,
        'formatted_price'       => '₹' . number_format($price, 2),
        'quantity'              => (int) $row->quantity,
        'max_qty'               => $product->stock ?? 0,
        'is_variant'            => false,
        'stock'                 => $product->stock ?? 0,
        'subtotal'              => $price * $row->quantity,
        'formatted_subtotal'    => '₹' . number_format($price * $row->quantity, 2),
    ];
}


    /* =================================================================
     |  CART HELPERS
     ================================================================= */

    /**
     * Get cart data for checkout.
     *
     * @param array $cart
     * @return array
     */
    private function getCartDataForCheckout(array $cart): array
    {
        $cartItems = [];
        $subtotal  = 0;

        if (!empty($cart)) {
            foreach ($cart as $key => $item) {
                $cartItem = $this->getCartItemDetails($key, $item);
                if ($cartItem) {
                    $cartItems[] = $cartItem;
                    $subtotal   += $cartItem['price'] * $cartItem['quantity'];
                }
            }
        }

        $taxRate           = 10;
        $tax               = $subtotal * 0.10;
        $shippingThreshold = 100;
        $shippingCost      = 10;
        $shipping          = $subtotal > $shippingThreshold ? 0 : $shippingCost;
        $total             = $subtotal + $tax + $shipping;

        return [
            'items'               => $cartItems,
            'subtotal'            => $subtotal,
            'formatted_subtotal'  => '₹' . number_format($subtotal, 2),
            'tax'                 => $tax,
            'tax_rate'            => $taxRate,
            'formatted_tax'       => '₹' . number_format($tax, 2),
            'shipping'            => $shipping,
            'shipping_threshold'  => $shippingThreshold,
            'formatted_shipping'  => '₹' . number_format($shipping, 2),
            'total'               => $total,
            'formatted_total'     => '₹' . number_format($total, 2),
        ];
    }

    /**
     * Get cart item details.
     *
     * @param string $key
     * @param array  $item
     * @return array|null
     */
    private function getCartItemDetails(string $key, array $item): ?array
    {
        // ---- Variant product ----
        if (isset($item['variant_id']) && $item['variant_id'] > 0) {
            $variant = ProductVariant::find($item['variant_id']);
            if (!$variant) {
                return null;
            }

            $product = Product::find($variant->product_id);
            if (!$product) {
                return null;
            }

            // ✅ 4-arg joins instead of broken string concat
            $variantFeatures = ProductVariantValue::select(
                'product_variant_values.*',
                'features.name as feature_name',
                'feature_values.value as option_value'
            )
                ->join('features', 'features.id', '=', 'product_variant_values.feature_id')
                ->leftJoin('feature_values', 'feature_values.id', '=', 'product_variant_values.value')
                ->where('product_variant_values.variant_id', $item['variant_id'])
                ->get()
                ->toArray();

            $featureParts  = [];
            $featureValues = [];

            foreach ($variantFeatures as $feature) {
                $featureName  = $feature['feature_name'] ?? '';
                $featureValue = $feature['option_value'] ?? $feature['value'] ?? '';

                if (!empty($featureName) && !empty($featureValue)) {
                    $featureParts[]              = $featureValue;
                    $featureValues[$featureName] = $featureValue;
                }
            }

            $price = ($variant->sale_price > 0 && $variant->sale_price < $variant->price)
                ? $variant->sale_price
                : $variant->price;

            $variantImage = ProductVariantImage::where('variant_id', $item['variant_id'])
                ->orderBy('is_primary', 'DESC')
                ->orderBy('sort_order', 'ASC')
                ->first();

            $image = $variantImage->image
                ?? $variant->image
                ?? $product->image
                ?? 'assets/images/default-product.jpg';

            return [
                'key'                     => $key,
                'product_id'              => $product->id,
                'variant_id'              => $item['variant_id'],
                'name'                    => $product->name,
                'display_name'            => $product->name . (!empty($featureParts) ? ' / ' . implode(' / ', $featureParts) : ''),
                'variant_features'        => $featureValues,
                'variant_features_list'   => $featureParts,
                'slug'                    => $variant->slug ?? $product->slug,
                'image'                   => $image,
                'price'                   => $price,
                'formatted_price'         => '₹' . number_format($price, 2),
                'quantity'                => $item['quantity'],
                'max_qty'                 => $variant->stock ?? 0,
                'is_variant'              => true,
                'stock'                   => $variant->stock ?? 0,
                'subtotal'                => $price * $item['quantity'],
                'formatted_subtotal'      => '₹' . number_format($price * $item['quantity'], 2),
            ];
        }

        // ---- Simple product ----
        $product = Product::find($item['product_id']);
        if (!$product) {
            return null;
        }

        $price = ($product->sale_price > 0 && $product->sale_price < $product->price)
            ? $product->sale_price
            : $product->price;

        return [
            'key'                     => $key,
            'product_id'              => $product->id,
            'variant_id'              => 0,
            'name'                    => $product->name,
            'display_name'            => $product->name,
            'variant_features'        => [],
            'variant_features_list'   => [],
            'slug'                    => $product->slug,
            'image'                   => $product->image ?? 'assets/images/default-product.jpg',
            'price'                   => $price,
            'formatted_price'         => '₹' . number_format($price, 2),
            'quantity'                => $item['quantity'],
            'max_qty'                 => $product->stock ?? 0,
            'is_variant'              => false,
            'stock'                   => $product->stock ?? 0,
            'subtotal'                => $price * $item['quantity'],
            'formatted_subtotal'      => '₹' . number_format($price * $item['quantity'], 2),
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

    /* =================================================================
     |  RAZORPAY
     ================================================================= */

    /**
     * Create Razorpay order.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRazorpayOrder(Request $request)
    {
        Log::debug('createRazorpayOrder - Request received');

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|min:3',
            'email'          => 'required|email',
            'phone'          => 'required|string|min:10',
            'address'        => 'required|string|min:10',
            'city'           => 'required|string|min:3',
            'postal_code'    => 'required|string|min:4',
            'state'          => 'nullable|string',
            'country'        => 'nullable|string',
            'create_account' => 'nullable|boolean',
            'password'       => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

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

        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Please log in.'], 401);
        }

        $cartRows = Cart::where('user_id', $userId)->get();
        if ($cartRows->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your cart is empty.'], 422);
        }

        $cartData = $this->getCartDataForCheckout($cartRows);

        if (empty($cartData['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'Some items in your cart are no longer available.',
            ], 422);
        }

        if (!$this->razorpayService->isEnabled()) {
            Log::error('Razorpay is not enabled in settings');
            return response()->json([
                'success' => false,
                'message' => 'Razorpay payment is not enabled. Please contact support.',
            ], 422);
        }

        $razorpayKey = $this->razorpayService->getKeyId();
        if (empty($razorpayKey)) {
            Log::error('Razorpay Key ID is missing');
            return response()->json([
                'success' => false,
                'message' => 'Razorpay configuration is incomplete. Please contact support.',
            ], 422);
        }

        try {
            $customerData = $request->all();
            if ($createAccount) {
                $customerData['password'] = $request->password;
            }

            Log::debug('Creating pending order with data: ' . json_encode($customerData));

            $orderResult = $this->orderService->createPendingOrder($customerData, $cartData, 'razorpay');

            Log::debug('Order created: ' . json_encode($orderResult));

            $razorpayOrder = $this->razorpayService->createOrder(
                $cartData['total'],
                $this->getCurrency(),
                'order_' . $orderResult['order_number']
            );

            Log::debug('Razorpay order created: ' . json_encode($razorpayOrder));

            Order::where('id', $orderResult['order_id'])->update([
                'razorpay_order_id' => $razorpayOrder['id'],
            ]);

            Session::put('pending_order_id', $orderResult['order_id']);
            Session::put('pending_cart', $cart);

            return response()->json([
                'success' => true,
                'data' => [
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'razorpay_key'      => $razorpayKey,
                    'amount'            => $cartData['total'],
                    'currency'          => $this->getCurrency(),
                    'order_number'      => $orderResult['order_number'],
                    'name'              => $customerData['name'],
                    'email'             => $customerData['email'],
                    'phone'             => $customerData['phone'],
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
     * Verify Razorpay payment. Also auto-creates the invoice.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        $razorpayOrderId   = $request->input('razorpay_order_id');
        $razorpayPaymentId = $request->input('razorpay_payment_id');
        $razorpaySignature = $request->input('razorpay_signature');

        Log::debug('verifyPayment - Received: order_id=' . $razorpayOrderId . ', payment_id=' . $razorpayPaymentId . ', signature=' . $razorpaySignature);

        if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
            Log::error('verifyPayment - Missing required Razorpay data');
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment data received.',
            ], 422);
        }

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

        $orderId = Session::get('pending_order_id');
        Log::debug('verifyPayment - pending_order_id from session: ' . $orderId);

        if (!$orderId) {
            Log::error('verifyPayment - No pending order ID in session');
            return response()->json([
                'success' => false,
                'message' => 'Order not found. Please contact support.',
            ], 404);
        }

        $order = Order::find($orderId);
        if (!$order) {
            Log::error('verifyPayment - Order not found: ' . $orderId);
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        Log::debug('verifyPayment - Order found: ' . json_encode($order));

        // ---- Already paid? (idempotent path) ----
        if ($order->payment_status === 'paid') {
            Log::debug('verifyPayment - Order already paid');

            // ✅ Ensure an invoice exists even for repeat calls
            $this->generateInvoiceForOrder($order);

            Session::forget('pending_cart');
            Session::forget('cart');
            Session::put('last_order_id', $orderId);
            Session::forget('pending_order_id');

            return response()->json([
                'success'  => true,
                'message'  => 'Payment already confirmed.',
                'redirect' => '/checkout/success',
            ]);
        }

        // ---- Mark order paid ----
        $order->update([
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature'  => $razorpaySignature,
            'payment_status'      => 'paid',
            'order_status'        => 'confirmed',
        ]);

        Cart::where('user_id', $order->user_id)->delete();
        Log::info('Cart cleared for user ' . $order->user_id . ' after payment');

        Log::debug('verifyPayment - Order updated successfully');

        // ✅ Auto-create invoice now that order is paid
        $this->generateInvoiceForOrder($order);

        // ---- Reduce stock ----
        try {
            $orderItems = OrderItem::where('order_id', $orderId)->get();
            Log::debug('verifyPayment - Order items found: ' . $orderItems->count());

            foreach ($orderItems as $orderItem) {
                $quantity = (int) ($orderItem->quantity ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                if ($orderItem->variant_id && $orderItem->variant_id > 0) {
                    $variant = ProductVariant::find($orderItem->variant_id);
                    if ($variant) {
                        $newStock = max(0, ($variant->stock ?? 0) - $quantity);
                        $variant->update(['stock' => $newStock]);
                        Log::debug('Variant stock updated - Variant ID: ' . $orderItem->variant_id . ', New Stock: ' . $newStock);
                    }
                } else {
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
        }

        Session::forget('pending_cart');
        Session::forget('cart');
        Session::put('last_order_id', $orderId);
        Session::forget('pending_order_id');

        Log::debug('verifyPayment - Cart cleared successfully');

        return response()->json([
            'success'  => true,
            'message'  => 'Payment confirmed successfully!',
            'redirect' => '/checkout/success',
        ]);
    }

    /* =================================================================
     |  INVOICE AUTO-GENERATION
     ================================================================= */

    /**
     * Auto-generate an invoice when an order is paid.
     * Idempotent — won't create duplicates.
     *
     * @param  Order  $order
     * @return Invoice|null
     */
    protected function generateInvoiceForOrder(Order $order): ?Invoice
    {
        try {
            // Already has an invoice? Return it.
            $existing = Invoice::where('order_id', $order->id)->first();
            if ($existing) {
                Log::debug('Invoice already exists for order #' . $order->order_number . ' (#' . $existing->invoice_number . ')');
                return $existing;
            }

            // Sum item totals from order_items
            $items = OrderItem::where('order_id', $order->id)->get();

            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += (float) $item->price * (int) $item->quantity;
            }

            // Match the checkout scheme
            $taxRate  = 10;
            $tax      = round($subtotal * ($taxRate / 100), 2);
            $shipping = $subtotal > 100 ? 0 : 10;
            $discount = 0;

            $total = round($subtotal + $tax + $shipping - $discount, 2);

            $invoice = Invoice::create([
                'order_id'       => $order->id,
                'user_id'        => $order->user_id,
                'invoice_number' => (new Invoice())->generateInvoiceNumber(),
                'issue_date'     => now()->toDateString(),
                'due_date'       => now()->addDays(30)->toDateString(),
                'subtotal'       => $subtotal,
                'tax'            => $tax,
                'discount'       => $discount,
                'total'          => $total,
                'status'         => Invoice::STATUS_PAID,
                'payment_method' => $order->payment_method_display ?? 'razorpay',
                'payment_date'   => now(),
                'notes'          => 'Auto-generated from order #' . $order->order_number,
            ]);

            Log::info('Invoice auto-created: ' . $invoice->invoice_number . ' for order #' . $order->order_number);

            return $invoice;
        } catch (\Exception $e) {
            Log::error('Invoice auto-creation failed for order #' . $order->order_number . ': ' . $e->getMessage());
            // Never fail the payment flow because of an invoice problem
            return null;
        }
    }

    /* =================================================================
     |  PAYMENT METHODS
     ================================================================= */

    /**
     * Get enabled payment methods.
     *
     * @return array
     */
    private function getEnabledPaymentMethods(): array
    {
        $methods = [];

        if ($this->razorpayService->isEnabled()) {
            $methods[] = [
                'id'   => 'razorpay',
                'name' => 'Razorpay',
                'icon' => 'bi bi-credit-card',
            ];
        }

        if (Setting::getSetting('payment_stripe_enabled') == '1') {
            $methods[] = [
                'id'   => 'stripe',
                'name' => 'Stripe',
                'icon' => 'bi bi-credit-card',
            ];
        }

        if (Setting::getSetting('payment_ideal_enabled') == '1') {
            $methods[] = [
                'id'   => 'ideal',
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
        $currency = Setting::getSetting('store_currency') ?? 'INR';

        $supported = ['INR', 'USD', 'EUR', 'GBP'];
        return in_array($currency, $supported) ? $currency : 'INR';
    }

    /* =================================================================
     |  SUCCESS PAGE
     ================================================================= */

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

        $processedItems = [];
        $subtotal       = 0;

        foreach ($items as $item) {
            $product  = Product::find($item->product_id);
            $itemData = $item->toArray();
            $itemData['product'] = $product;

            $itemSubtotal = $item->price * $item->quantity;
            $subtotal    += $itemSubtotal;

            if ($item->variant_id && $item->variant_id > 0) {
                $variant = ProductVariant::find($item->variant_id);
                if ($variant) {
                    $itemData['variant']    = $variant;
                    $itemData['is_variant'] = true;

                    // ✅ 4-arg joins
                    $variantFeatures = ProductVariantValue::select(
                        'product_variant_values.*',
                        'features.name as feature_name',
                        'feature_values.value as option_value'
                    )
                        ->join('features', 'features.id', '=', 'product_variant_values.feature_id')
                        ->leftJoin('feature_values', 'feature_values.id', '=', 'product_variant_values.value')
                        ->where('product_variant_values.variant_id', $item->variant_id)
                        ->get()
                        ->toArray();

                    $featureParts  = [];
                    $featureValues = [];

                    foreach ($variantFeatures as $feature) {
                        $featureName  = $feature['feature_name'] ?? '';
                        $featureValue = $feature['option_value'] ?? $feature['value'] ?? '';

                        if (!empty($featureName) && !empty($featureValue)) {
                            $featureParts[]              = $featureValue;
                            $featureValues[$featureName] = $featureValue;
                        }
                    }

                    $itemData['variant_features']      = $featureValues;
                    $itemData['variant_features_list'] = $featureParts;
                    $itemData['variant_display_name']  = ($product->name ?? '') . (!empty($featureParts) ? ' / ' . implode(' / ', $featureParts) : '');

                    $variantImage = ProductVariantImage::where('variant_id', $item->variant_id)
                        ->orderBy('is_primary', 'DESC')
                        ->orderBy('sort_order', 'ASC')
                        ->first();

                    $itemData['image'] = $variantImage->image
                        ?? $variant->image
                        ?? $product->image
                        ?? 'assets/images/default-product.jpg';
                } else {
                    $itemData['is_variant']             = false;
                    $itemData['image']                  = $product->image ?? 'assets/images/default-product.jpg';
                    $itemData['variant_features']       = [];
                    $itemData['variant_features_list']  = [];
                    $itemData['variant_display_name']   = $product->name ?? '';
                }
            } else {
                $itemData['is_variant']            = false;
                $itemData['image']                 = $product->image ?? 'assets/images/default-product.jpg';
                $itemData['variant_features']      = [];
                $itemData['variant_features_list'] = [];
                $itemData['variant_display_name']  = $product->name ?? '';
            }

            $processedItems[] = $itemData;
        }

        $taxRate           = 10;
        $tax               = $subtotal * 0.10;
        $shippingThreshold = 100;
        $shippingCost      = 10;
        $shipping          = $subtotal > $shippingThreshold ? 0 : $shippingCost;
        $total             = $subtotal + $tax + $shipping;

        return response()->json([
            'success' => true,
            'data' => [
                'order'              => $order,
                'items'              => $processedItems,
                'subtotal'           => $subtotal,
                'formatted_subtotal' => '₹' . number_format($subtotal, 2),
                'tax'                => $tax,
                'tax_rate'           => $taxRate,
                'formatted_tax'      => '₹' . number_format($tax, 2),
                'shipping'           => $shipping,
                'formatted_shipping' => '₹' . number_format($shipping, 2),
                'total'              => $total,
                'formatted_total'    => '₹' . number_format($total, 2),
            ],
        ]);
    }

    /* =================================================================
     |  MISC
     ================================================================= */

    /**
     * Place order (redirect to checkout).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder()
    {
        return response()->json([
            'success'  => true,
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
            $testOrder = $this->razorpayService->createOrder(10, 'INR', 'test_order_' . time());

            return response()->json([
                'success' => true,
                'message' => 'Razorpay is working!',
                'data' => [
                    'test_order_id'     => $testOrder['id'],
                    'test_order_status' => $testOrder['status'],
                    'test_amount'       => '₹' . ($testOrder['amount'] / 100),
                    'razorpay_key'      => $this->razorpayService->getKeyId(),
                    'razorpay_enabled'  => $this->razorpayService->isEnabled(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [
                    'razorpay_key'     => $this->razorpayService->getKeyId(),
                    'razorpay_enabled' => $this->razorpayService->isEnabled(),
                    'error'            => $e->getMessage(),
                ],
            ], 500);
        }
    }
}