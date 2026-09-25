<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Get complete cart data for order calculation.
     */
    public function getCartData(array $cart): array
    {
        if (empty($cart)) {
            return [
                'items'    => [],
                'subtotal' => 0,
                'tax'      => 0,
                'shipping' => 0,
                'total'    => 0,
            ];
        }

        $cartItems = [];
        $subtotal  = 0;

        foreach ($cart as $key => $item) {

            if (!empty($item['variant_id'])) {

                // ---------- Variant product ----------
                $variant = ProductVariant::find($item['variant_id']);

                if (!$variant) {
                    continue;
                }

                $product = Product::find($variant->product_id);
                if (!$product) {
                    continue;
                }

                // Get variant features
                $variantFeatures = ProductVariantValue::query()
                    ->select(
                        'product_variant_values.*',
                        'features.name as feature_name',
                        'feature_values.value as option_value'
                    )
                    ->join('features', 'features.id', '=', 'product_variant_values.feature_id')
                    ->leftJoin('feature_values', 'feature_values.id', '=', 'product_variant_values.value')
                    ->where('product_variant_values.variant_id', $item['variant_id'])
                    ->get();

                $featureParts  = [];
                $featureValues = [];

                foreach ($variantFeatures as $feature) {
                    $featureName  = $feature->feature_name ?? '';
                    $featureValue = $feature->option_value ?? $feature->value ?? '';

                    if ($featureName !== '' && $featureValue !== '') {
                        $featureParts[]               = $featureValue;
                        $featureValues[$featureName]  = $featureValue;
                    }
                }

                $price = ($variant->sale_price > 0)
                    ? $variant->sale_price
                    : $variant->price;

                $subtotal += $price * $item['quantity'];

                // Get variant image
                $variantImage = ProductVariantImage::query()
                    ->where('variant_id', $item['variant_id'])
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->first();

                $image = $variantImage->image
                    ?? $variant->image
                    ?? $product->image
                    ?? 'assets/images/default-product.jpg';

                $cartItems[] = [
                    'key'                   => $key,
                    'product_id'            => $product->id,
                    'variant_id'            => $item['variant_id'],
                    'name'                  => $product->name,
                    'variant_features'      => $featureValues,
                    'variant_features_list' => $featureParts,
                    'slug'                  => $variant->slug ?? $product->slug,
                    'image'                 => $image,
                    'price'                 => $price,
                    'quantity'              => $item['quantity'],
                    'max_qty'               => $variant->stock ?? 0,
                    'is_variant'            => true,
                    'stock'                 => $variant->stock ?? 0,
                ];

            } else {

                // ---------- Simple product ----------
                $product = Product::find($item['product_id'] ?? 0);
                if (!$product) {
                    continue;
                }

                $price = ($product->sale_price > 0)
                    ? $product->sale_price
                    : $product->price;

                $subtotal += $price * $item['quantity'];

                $cartItems[] = [
                    'key'                   => $key,
                    'product_id'            => $product->id,
                    'variant_id'            => 0,
                    'name'                  => $product->name,
                    'variant_features'      => [],
                    'variant_features_list' => [],
                    'slug'                  => $product->slug,
                    'image'                 => $product->image ?? 'assets/images/default-product.jpg',
                    'price'                 => $price,
                    'quantity'              => $item['quantity'],
                    'max_qty'               => $product->stock ?? 0,
                    'is_variant'            => false,
                    'stock'                 => $product->stock ?? 0,
                ];
            }
        }

        // Tax + shipping — mirror CartController logic
        $tax      = $subtotal * 0.10;
        $shipping = $subtotal > 100 ? 0 : 10;
        $total    = $subtotal + $tax + $shipping;

        return [
            'items'              => $cartItems,
            'subtotal'           => $subtotal,
            'tax'                => $tax,
            'tax_rate'           => 10,
            'shipping'           => $shipping,
            'shipping_threshold' => 100,
            'total'              => $total,
        ];
    }

    /**
     * Create a pending order (used before Razorpay redirect).
     *
     * @return array{order_id:int, order_number:string}
     */
    public function createPendingOrder(array $customerData, array $cartData, string $paymentMethod = 'razorpay'): array
    {
        Log::debug('OrderService - Cart Data Items: ' . json_encode($cartData['items'] ?? []));

        // ---------- Find or create user ----------
        $user = User::where('email', $customerData['email'])->first();

        if (!$user) {
            $password = $customerData['password'] ?? '';

            if (!empty($password) && strlen($password) >= 8) {
                $hashedPassword = Hash::make($password);
            } else {
                $randomPassword = bin2hex(random_bytes(8));
                $hashedPassword = Hash::make($randomPassword);
                Log::debug('OrderService - Generated random password for guest user');
            }

            $user = User::create([
                'name'        => $customerData['name'],
                'email'       => $customerData['email'],
                'phone'       => $customerData['phone']       ?? '',
                'address'     => $customerData['address']     ?? '',
                'city'        => $customerData['city']        ?? '',
                'postal_code' => $customerData['postal_code'] ?? '',
                'password'    => $hashedPassword,
                'role'        => 'user',
                'status'      => 'active',
            ]);

            $userId = $user->id;
            Log::debug('OrderService - New user created: ' . $userId);

        } else {
            $userId = $user->id;
            Log::debug('OrderService - Existing user: ' . $userId);

            $updateData = [];
            foreach (['phone', 'address', 'city', 'postal_code'] as $field) {
                if (!empty($customerData[$field]) && $user->{$field} != $customerData[$field]) {
                    $updateData[$field] = $customerData[$field];
                }
            }

            if (!empty($updateData)) {
                $user->update($updateData);
                Log::debug('OrderService - User updated: ' . json_encode($updateData));
            }
        }

        // ---------- Generate order number ----------
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $shippingAddress = json_encode([
            'address'     => $customerData['address']     ?? '',
            'city'        => $customerData['city']        ?? '',
            'postal_code' => $customerData['postal_code'] ?? '',
            'phone'       => $customerData['phone']       ?? '',
        ]);

        $orderData = [
            'user_id'                => $userId,
            'order_number'           => $orderNumber,
            'total'                  => $cartData['total'],
            'payment_status'         => 'pending',
            'order_status'           => 'pending',
            'shipping_address'       => $shippingAddress,
            'payment_method_display' => $paymentMethod,
        ];

        Log::debug('OrderService - Order data: ' . json_encode($orderData));

        $order = Order::create($orderData);
        $orderId = $order->id;

        Log::debug('OrderService - Order created with ID: ' . $orderId);

        // ---------- Create order items ----------
        if (!empty($cartData['items'])) {
            $cartSubtotal = (float) ($cartData['subtotal'] ?? 0);
            $cartTax      = (float) ($cartData['tax']      ?? 0);
            $cartShipping = (float) ($cartData['shipping'] ?? 0);

            foreach ($cartData['items'] as $index => $item) {

                $itemSubtotal = (float) $item['price'] * (int) $item['quantity'];

                // Distribute tax proportionally
                $itemTax = 0;
                if ($cartSubtotal > 0) {
                    $itemTax = ($itemSubtotal / $cartSubtotal) * $cartTax;
                }

                // Distribute shipping proportionally
                $itemShipping = 0;
                if ($cartSubtotal > 0 && $cartShipping > 0) {
                    $itemShipping = ($itemSubtotal / $cartSubtotal) * $cartShipping;
                }

                $orderItemData = [
                    'order_id'   => $orderId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? 0,
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'subtotal'   => $itemSubtotal,
                    'shipping'   => $itemShipping,
                    'tax'        => $itemTax,
                ];

                Log::debug("OrderService - Order item {$index}: " . json_encode($orderItemData));

                try {
                    OrderItem::create($orderItemData);
                    Log::debug('OrderService - Order item inserted: success');
                } catch (\Throwable $e) {
                    Log::error('OrderService - Failed to insert order item: ' . $e->getMessage());
                }
            }

        } else {
            Log::error('OrderService - No cart items found!');
        }

        return [
            'order_id'     => $orderId,
            'order_number' => $orderNumber,
        ];
    }

    /**
     * Update order with Razorpay details.
     */
    public function updateOrderPayment(int $orderId, array $razorpayData): bool
    {
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }

        return $order->update([
            'razorpay_order_id'   => $razorpayData['razorpay_order_id']   ?? null,
            'razorpay_payment_id' => $razorpayData['razorpay_payment_id'] ?? null,
            'razorpay_signature'  => $razorpayData['razorpay_signature']  ?? null,
            'payment_status'      => 'paid',
            'order_status'        => 'confirmed',
        ]);
    }
}