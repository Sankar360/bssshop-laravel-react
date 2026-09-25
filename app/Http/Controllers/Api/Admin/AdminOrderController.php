<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
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
     * @var User
     */
    protected $userModel;

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
     * @var Product
     */
    protected $productModel;

    /**
     * AdminOrderController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->userModel = new User();
        $this->variantModel = new ProductVariant();
        $this->variantValueModel = new ProductVariantValue();
        $this->variantImageModel = new ProductVariantImage();
        $this->productModel = new Product();
    }

    /**
     * List orders with filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $paymentStatus = $request->get('payment_status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $perPage = $request->get('per_page', 20);

        $query = $this->orderModel->with('user')
            ->latestFirst();

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($status) {
            $query->where('order_status', $status);
        }

        // Apply payment status filter
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        // Apply date filters
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $orders = $query->paginate($perPage);

        // Get statistics
        $stats = [
            'total' => $this->orderModel->count(),
            'pending' => $this->orderModel->where('order_status', Order::ORDER_PENDING)->count(),
            'processing' => $this->orderModel->where('order_status', Order::ORDER_PROCESSING)->count(),
            'shipped' => $this->orderModel->where('order_status', Order::ORDER_SHIPPED)->count(),
            'delivered' => $this->orderModel->where('order_status', Order::ORDER_DELIVERED)->count(),
            'cancelled' => $this->orderModel->where('order_status', Order::ORDER_CANCELLED)->count(),
            'paid' => $this->orderModel->where('payment_status', Order::PAYMENT_PAID)->count(),
            'pending_payment' => $this->orderModel->where('payment_status', Order::PAYMENT_PENDING)->count(),
            'total_revenue' => $this->orderModel->where('payment_status', Order::PAYMENT_PAID)->sum('total') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $orders,
            'stats' => $stats,
        ]);
    }

    /**
     * Get order details with items and variant information.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view($id)
    {
        $order = $this->orderModel->with('user')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Get order items with variant details
        $items = $this->getOrderItemsWithVariantDetails($id);

        // Calculate totals from order_items table
        $itemSubtotal = 0;
        $itemShipping = 0;
        $itemTax = 0;

        foreach ($items as $item) {
            $itemSubtotal += (float) ($item['subtotal'] ?? 0);
            $itemShipping += (float) ($item['shipping'] ?? 0);
            $itemTax += (float) ($item['tax'] ?? 0);
        }

        $orderData = $order->toArray();
        $orderData['subtotal'] = $itemSubtotal;
        $orderData['shipping'] = $itemShipping;
        $orderData['tax'] = $itemTax;
        $orderData['total'] = $itemSubtotal + $itemShipping + $itemTax;

        // Get shipping address
        $shippingAddress = $order->shipping_address;
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        // Get user
        $user = $order->user;

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $orderData,
                'items' => $items,
                'user' => $user,
                'shipping_address' => $shippingAddress,
            ],
        ]);
    }

    /**
     * Get order items with variant details.
     *
     * @param int $orderId
     * @return array
     */
    private function getOrderItemsWithVariantDetails(int $orderId): array
    {
        $items = $this->orderItemModel->where('order_id', $orderId)->get()->toArray();

        foreach ($items as &$item) {
            // Get product details
            $product = $this->productModel->find($item['product_id']);
            $item['product_name'] = $product->name ?? 'Unknown Product';
            $item['product_image'] = $product->image ?? null;

            // Check if it's a variant
            if (!empty($item['variant_id']) && $item['variant_id'] > 0) {
                $variant = $this->variantModel->find($item['variant_id']);

                if ($variant) {
                    // Get variant features/options
                    $variantFeatures = $this->variantValueModel
                        ->select('product_variant_values.*', 'features.name as feature_name', 'feature_values.value as option_value')
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

                    // Build variant display name
                    $variantDisplayName = $product->name ?? 'Unknown Product';
                    if (!empty($featureParts)) {
                        $variantDisplayName .= ' / ' . implode(' / ', $featureParts);
                    }

                    $item['variant_display_name'] = $variantDisplayName;
                    $item['variant_features'] = $featureValues;
                    $item['variant_features_list'] = $featureParts;
                    $item['is_variant'] = true;
                    $item['variant_sku'] = $variant->sku ?? '';

                    // Get variant image
                    $variantImage = $this->variantImageModel
                        ->where('variant_id', $item['variant_id'])
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
                    // Variant not found
                    $item['is_variant'] = false;
                    $item['variant_display_name'] = $product->name ?? 'Unknown Product';
                    $item['variant_features'] = [];
                    $item['variant_features_list'] = [];
                    $item['variant_sku'] = '';
                }
            } else {
                // Simple product
                $item['is_variant'] = false;
                $item['variant_display_name'] = $product->name ?? 'Unknown Product';
                $item['variant_features'] = [];
                $item['variant_features_list'] = [];
                $item['variant_sku'] = '';

                // Use product price with sale price
                if (!empty($product->sale_price) && $product->sale_price > 0 && $product->sale_price < $product->price) {
                    $item['price'] = $product->sale_price;
                }
            }
        }

        return $items;
    }

    /**
     * Update order status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $order = $this->orderModel->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_status' => ['nullable', Rule::in(Order::getOrderStatuses())],
            'payment_status' => ['nullable', Rule::in(Order::getPaymentStatuses())],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [];

        if ($request->has('order_status')) {
            $updateData['order_status'] = $request->order_status;
        }

        if ($request->has('payment_status')) {
            $updateData['payment_status'] = $request->payment_status;
        }

        if (empty($updateData)) {
            return response()->json([
                'success' => false,
                'message' => 'No status to update',
            ], 422);
        }

        $order->update($updateData);

        Log::info('Order #' . $order->order_number . ' updated by admin');

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order,
        ]);
    }

    /**
     * Delete an order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $order = $this->orderModel->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $orderNumber = $order->order_number;

        // Delete order items
        $this->orderItemModel->where('order_id', $id)->delete();

        // Delete order
        $order->delete();

        Log::info('Order #' . $orderNumber . ' deleted by admin');

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully',
        ]);
    }

    /**
     * Export orders to CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(Request $request)
    {
        $status = $request->get('status');
        $paymentStatus = $request->get('payment_status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = $this->orderModel->with('user')
            ->latestFirst();

        if ($status) {
            $query->where('order_status', $status);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $orders = $query->get();

        $filename = 'orders_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($orders) {
            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, [
                'Order #',
                'Customer',
                'Email',
                'Phone',
                'Total',
                'Payment Status',
                'Order Status',
                'Payment Method',
                'Date',
            ]);

            // Data
            foreach ($orders as $order) {
                fputcsv($output, [
                    $order->order_number,
                    $order->user?->name ?? 'N/A',
                    $order->user?->email ?? 'N/A',
                    $order->user?->phone ?? 'N/A',
                    number_format($order->total, 2),
                    $order->payment_status_label,
                    $order->order_status_label,
                    $order->payment_method_display ?? 'N/A',
                    $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Check for updates (AJAX).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUpdates()
    {
        $lastOrder = $this->orderModel->latestFirst()->first();

        return response()->json([
            'has_updates' => false,
            'last_order' => $lastOrder,
        ]);
    }

    /**
     * Bulk actions on orders.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in([
                'delete',
                'mark_paid',
                'mark_pending',
                'cancel',
                'process',
                'ship',
                'deliver'
            ])],
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $action = $request->action;
        $orderIds = $request->order_ids;

        $successCount = 0;
        $failedCount = 0;

        foreach ($orderIds as $orderId) {
            $order = $this->orderModel->find($orderId);

            if (!$order) {
                $failedCount++;
                continue;
            }

            $updateData = [];

            switch ($action) {
                case 'delete':
                    $this->orderItemModel->where('order_id', $orderId)->delete();
                    if ($order->delete()) {
                        $successCount++;
                    } else {
                        $failedCount++;
                    }
                    break;

                case 'mark_paid':
                    $updateData['payment_status'] = Order::PAYMENT_PAID;
                    break;

                case 'mark_pending':
                    $updateData['payment_status'] = Order::PAYMENT_PENDING;
                    break;

                case 'cancel':
                    $updateData['order_status'] = Order::ORDER_CANCELLED;
                    break;

                case 'process':
                    $updateData['order_status'] = Order::ORDER_PROCESSING;
                    break;

                case 'ship':
                    $updateData['order_status'] = Order::ORDER_SHIPPED;
                    break;

                case 'deliver':
                    $updateData['order_status'] = Order::ORDER_DELIVERED;
                    break;

                default:
                    $failedCount++;
                    continue 2;
            }

            if (!empty($updateData) && $order->update($updateData)) {
                $successCount++;
            } elseif (!empty($updateData)) {
                $failedCount++;
            }
        }

        Log::info('Bulk action: ' . $action . ' on ' . $successCount . ' orders by admin');

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed. {$successCount} updated, {$failedCount} failed.",
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);
    }

    /**
     * Get order statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total' => $this->orderModel->count(),
            'pending' => $this->orderModel->where('order_status', Order::ORDER_PENDING)->count(),
            'processing' => $this->orderModel->where('order_status', Order::ORDER_PROCESSING)->count(),
            'shipped' => $this->orderModel->where('order_status', Order::ORDER_SHIPPED)->count(),
            'delivered' => $this->orderModel->where('order_status', Order::ORDER_DELIVERED)->count(),
            'cancelled' => $this->orderModel->where('order_status', Order::ORDER_CANCELLED)->count(),
            'refunded' => $this->orderModel->where('order_status', Order::ORDER_REFUNDED)->count(),
            'paid' => $this->orderModel->where('payment_status', Order::PAYMENT_PAID)->count(),
            'pending_payment' => $this->orderModel->where('payment_status', Order::PAYMENT_PENDING)->count(),
            'failed_payment' => $this->orderModel->where('payment_status', Order::PAYMENT_FAILED)->count(),
            'refunded_payment' => $this->orderModel->where('payment_status', Order::PAYMENT_REFUNDED)->count(),
            'total_revenue' => $this->orderModel->where('payment_status', Order::PAYMENT_PAID)->sum('total') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get order dashboard summary.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary()
    {
        $summary = $this->orderModel->getDashboardSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get orders by user.
     *
     * @param int $userId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByUser($userId, Request $request)
    {
        $limit = $request->get('limit', 10);

        $orders = $this->orderModel->getOrdersByUser($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get order by order number.
     *
     * @param string $orderNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByNumber($orderNumber)
    {
        $order = $this->orderModel->with(['user', 'items'])
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Get order items.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItems($id)
    {
        $order = $this->orderModel->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $items = $this->getOrderItemsWithVariantDetails($id);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Update order item quantity.
     *
     * @param Request $request
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItemQuantity(Request $request, $itemId)
    {
        $item = $this->orderItemModel->find($itemId);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item->updateQuantity($itemId, $request->quantity);

        // Recalculate order totals
        $order = $this->orderModel->find($item->order_id);
        // Add recalculate method in Order model if needed

        Log::info('Order item #' . $itemId . ' quantity updated by admin');

        return response()->json([
            'success' => true,
            'message' => 'Item quantity updated successfully',
            'data' => $item,
        ]);
    }

    /**
     * Search orders (autocomplete).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $limit = $request->get('limit', 10);

        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $orders = $this->orderModel->with('user')
            ->where(function ($q) use ($query) {
                $q->where('order_number', 'LIKE', "%{$query}%")
                    ->orWhereHas('user', function ($q2) use ($query) {
                        $q2->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('email', 'LIKE', "%{$query}%");
                    });
            })
            ->latestFirst()
            ->limit($limit)
            ->get(['id', 'order_number', 'user_id', 'total', 'order_status', 'payment_status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get order statuses for dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatuses()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'order_statuses' => Order::getOrderStatusLabels(),
                'payment_statuses' => Order::getPaymentStatusLabels(),
            ],
        ]);
    }

    /**
     * Get monthly order stats.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyStats(Request $request)
    {
        $months = $request->get('months', 12);

        $data = $this->orderModel->getMonthlyStats($months);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get daily order stats.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDailyStats(Request $request)
    {
        $days = $request->get('days', 30);

        $data = $this->orderModel->getDailyStats($days);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}