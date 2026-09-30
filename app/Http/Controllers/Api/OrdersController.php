<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrdersController extends Controller
{
    /**
     * @var Order
     */
    protected $orderModel;

    /**
     * @var OrderItem
     */
    protected $orderItemModel;

    /**
     * OrdersController constructor.
     */
    public function __construct()
    {
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }

    /**
     * Get user orders.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view your orders.',
            ], 401);
        }

        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please login again.',
            ], 401);
        }

        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');

        // Get orders for this user
        $query = $this->orderModel->where('user_id', $userId)
            ->orderBy('created_at', 'DESC');

        if ($status && $status !== 'all') {
            $query->where('order_status', $status);
        }

        $orders = $query->paginate($perPage);

        // Get order items count for each order
        foreach ($orders as &$order) {
            $order['items_count'] = $this->orderItemModel
                ->where('order_id', $order['id'])
                ->count();
        }

        // Get order status counts
        $statusCounts = [
            'all' => $this->orderModel->where('user_id', $userId)->count(),
            'pending' => $this->orderModel->where('user_id', $userId)->where('order_status', 'pending')->count(),
            'processing' => $this->orderModel->where('user_id', $userId)->where('order_status', 'processing')->count(),
            'shipped' => $this->orderModel->where('user_id', $userId)->where('order_status', 'shipped')->count(),
            'delivered' => $this->orderModel->where('user_id', $userId)->where('order_status', 'delivered')->count(),
            'cancelled' => $this->orderModel->where('user_id', $userId)->where('order_status', 'cancelled')->count(),
            'refunded' => $this->orderModel->where('user_id', $userId)->where('order_status', 'refunded')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'status_counts' => $statusCounts,
            ],
        ]);
    }

    /**
     * Get order details.
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function view($orderId)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view order details.',
            ], 401);
        }

        $userId = Auth::id();

        // Get order with user relationship
        $order = $this->orderModel->with('user')->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Verify order belongs to logged-in user (or allow admin to view)
        if ($order->user_id != $userId && !(Auth::user()->role === 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this order.',
            ], 403);
        }

        // Get order items with product and variant details
        $orderItems = $this->getOrderItemsWithDetails($orderId);

        // Calculate totals from order_items table
        $subtotal = 0;
        $shipping = 0;
        $tax = 0;

        foreach ($orderItems as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
            $shipping += (float) ($item['shipping'] ?? 0);
            $tax += (float) ($item['tax'] ?? 0);
        }

        $total = $subtotal + $shipping + $tax;

        // Get shipping address
        $shippingAddress = $order->shipping_address;
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        // Get order timeline (status history)
        $timeline = $this->getOrderTimeline($order);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'order_items' => $orderItems,
                'subtotal' => $subtotal,
                'formatted_subtotal' => '₹' . number_format($subtotal, 2),
                'shipping' => $shipping,
                'formatted_shipping' => '₹' . number_format($shipping, 2),
                'tax' => $tax,
                'formatted_tax' => '₹' . number_format($tax, 2),
                'total' => $total,
                'formatted_total' => '₹' . number_format($total, 2),
                'shipping_address' => $shippingAddress,
                'timeline' => $timeline,
                'can_cancel' => $this->canCancelOrder($order),
            ],
        ]);
    }

    /**
     * Get order items with product and variant details.
     *
     * @param int $orderId
     * @return array
     */
    private function getOrderItemsWithDetails(int $orderId): array
    {
        $items = $this->orderItemModel->where('order_id', $orderId)->get()->toArray();

        foreach ($items as &$item) {
            // Get product details
            $product = Product::find($item['product_id']);
            $item['product_name'] = $product->name ?? 'Unknown Product';
            $item['product_image'] = $product->image ?? null;

            // Check if it's a variant
            if (!empty($item['variant_id']) && $item['variant_id'] > 0) {
                $variant = ProductVariant::find($item['variant_id']);

                if ($variant) {
                    // Get variant features
                    $variantFeatures = ProductVariantValue::select(
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
                        ->where('product_variant_values.variant_id', $item['variant_id'])
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

                    $item['variant_features'] = $featureValues;
                    $item['variant_features_list'] = $featureParts;
                    $item['is_variant'] = true;
                    $item['variant_sku'] = $variant->sku ?? '';
                    $item['variant_display_name'] = $product->name ?? 'Unknown Product' . (!empty($featureParts) ? ' / ' . implode(' / ', $featureParts) : '');

                    // Get variant image
                    $variantImage = ProductVariantImage::where('variant_id', $item['variant_id'])
                        ->orderBy('is_primary', 'DESC')
                        ->orderBy('sort_order', 'ASC')
                        ->first();

                    if ($variantImage) {
                        $item['product_image'] = $variantImage->image;
                    } elseif (!empty($variant->image)) {
                        $item['product_image'] = $variant->image;
                    }

                    // Use variant price
                    $item['price'] = ($variant->sale_price > 0 && $variant->sale_price < $variant->price)
                        ? $variant->sale_price
                        : $variant->price;
                } else {
                    $item['is_variant'] = false;
                    $item['variant_features'] = [];
                    $item['variant_features_list'] = [];
                    $item['variant_sku'] = '';
                    $item['variant_display_name'] = $product->name ?? 'Unknown Product';
                }
            } else {
                $item['is_variant'] = false;
                $item['variant_features'] = [];
                $item['variant_features_list'] = [];
                $item['variant_sku'] = '';
                $item['variant_display_name'] = $product->name ?? 'Unknown Product';

                // Use product price with sale price
                if (!empty($product->sale_price) && $product->sale_price > 0 && $product->sale_price < $product->price) {
                    $item['price'] = $product->sale_price;
                }
            }
        }

        return $items;
    }

    /**
     * Get order timeline.
     *
     * @param Order $order
     * @return array
     */
    private function getOrderTimeline(Order $order): array
    {
        $timeline = [];

        // Order created
        $timeline[] = [
            'status' => 'Order Placed',
            'description' => 'Order #' . $order->order_number . ' was placed',
            'date' => $order->created_at?->format('Y-m-d H:i:s'),
            'icon' => 'bi-check-circle',
            'color' => 'primary',
        ];

        // Payment confirmed (if paid)
        if ($order->payment_status === 'paid') {
            $timeline[] = [
                'status' => 'Payment Confirmed',
                'description' => 'Payment was confirmed successfully',
                'date' => $order->updated_at?->format('Y-m-d H:i:s'),
                'icon' => 'bi-credit-card',
                'color' => 'success',
            ];
        }

        // Processing
        if (in_array($order->order_status, ['processing', 'shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'Processing',
                'description' => 'Order is being processed',
                'date' => $order->updated_at?->format('Y-m-d H:i:s'),
                'icon' => 'bi-arrow-repeat',
                'color' => 'info',
            ];
        }

        // Shipped
        if (in_array($order->order_status, ['shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'Shipped',
                'description' => 'Order has been shipped',
                'date' => $order->updated_at?->format('Y-m-d H:i:s'),
                'icon' => 'bi-truck',
                'color' => 'warning',
            ];
        }

        // Delivered
        if ($order->order_status === 'delivered') {
            $timeline[] = [
                'status' => 'Delivered',
                'description' => 'Order has been delivered',
                'date' => $order->updated_at?->format('Y-m-d H:i:s'),
                'icon' => 'bi-house-check',
                'color' => 'success',
            ];
        }

        // Cancelled
        if ($order->order_status === 'cancelled') {
            $timeline[] = [
                'status' => 'Cancelled',
                'description' => 'Order has been cancelled',
                'date' => $order->updated_at?->format('Y-m-d H:i:s'),
                'icon' => 'bi-x-circle',
                'color' => 'danger',
            ];
        }

        return $timeline;
    }

    /**
     * Check if order can be cancelled.
     *
     * @param Order $order
     * @return bool
     */
    private function canCancelOrder(Order $order): bool
    {
        return in_array($order->order_status, ['pending', 'processing'])
            && $order->payment_status !== 'paid';
    }

    /**
     * Cancel an order.
     *
     * @param int $orderId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($orderId, Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to cancel orders.',
            ], 401);
        }

        $userId = Auth::id();
        $order = $this->orderModel->find($orderId);

        if (!$order || $order->user_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Only allow cancellation for pending or processing orders
        if (!in_array($order->order_status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled.',
            ], 422);
        }

        // Don't allow cancellation if already paid
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a paid order. Please contact support.',
            ], 422);
        }

        // Update order status
        $order->update(['order_status' => 'cancelled']);

        // Restore stock for cancelled order
        $this->restoreStock($orderId);

        Log::info('Order cancelled: #' . $order->order_number . ' by user ID: ' . $userId);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => $order,
        ]);
    }

    /**
     * Restore stock for cancelled order.
     *
     * @param int $orderId
     * @return void
     */
    private function restoreStock(int $orderId): void
    {
        try {
            $orderItems = $this->orderItemModel->where('order_id', $orderId)->get();

            foreach ($orderItems as $item) {
                $quantity = (int) ($item->quantity ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                // Variant product
                if ($item->variant_id && $item->variant_id > 0) {
                    $variant = ProductVariant::find($item->variant_id);
                    if ($variant) {
                        $variant->increment('stock', $quantity);
                        Log::debug('Stock restored for variant ID: ' . $item->variant_id . ', Quantity: ' . $quantity);
                    }
                } else {
                    // Normal product
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock', $quantity);
                        Log::debug('Stock restored for product ID: ' . $item->product_id . ', Quantity: ' . $quantity);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to restore stock for order ID: ' . $orderId . ', Error: ' . $e->getMessage());
        }
    }

    /**
     * Get recent orders (for dashboard/API).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recent(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view your orders.',
            ], 401);
        }

        $userId = Auth::id();
        $limit = $request->get('limit', 5);

        $orders = $this->orderModel->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get order statuses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatuses()
    {
        $statuses = [
            ['value' => 'all', 'label' => 'All Orders'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'processing', 'label' => 'Processing'],
            ['value' => 'shipped', 'label' => 'Shipped'],
            ['value' => 'delivered', 'label' => 'Delivered'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
            ['value' => 'refunded', 'label' => 'Refunded'],
        ];

        return response()->json([
            'success' => true,
            'data' => $statuses,
        ]);
    }

    /**
     * Track order (by order number).
     *
     * @param string $orderNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function track($orderNumber)
    {
        $order = $this->orderModel->where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Check if user is authenticated and owns the order
        if (Auth::check() && $order->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this order.',
            ], 403);
        }

        $timeline = $this->getOrderTimeline($order);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'timeline' => $timeline,
                'estimated_delivery' => $order->created_at?->addDays(7)->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Download order invoice (PDF).
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function downloadInvoice($orderId)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to download invoice.',
            ], 401);
        }

        $userId = Auth::id();
        $order = $this->orderModel->find($orderId);

        if (!$order || $order->user_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Check if invoice exists
        $invoice = $order->invoice;
        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this order.',
            ], 404);
        }

        // Generate PDF or return invoice data
        $orderItems = $this->getOrderItemsWithDetails($orderId);

        $data = [
            'order' => $order,
            'items' => $orderItems,
            'invoice' => $invoice,
        ];

        // Return JSON for now (PDF generation will be handled by frontend)
        return response()->json([
            'success' => true,
            'message' => 'Invoice data retrieved successfully.',
            'data' => $data,
        ]);
    }
}
