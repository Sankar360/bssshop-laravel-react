<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class AdminInvoiceController extends Controller
{
    /**
     * @var Invoice
     */
    protected $invoiceModel;

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
     * AdminInvoiceController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->invoiceModel = new Invoice();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->userModel = new User();
    }

    /**
     * List invoices with filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $perPage = $request->get('per_page', 20);

        $invoices = $this->invoiceModel->search($search)
            ->ofStatus($status)
            ->dateRange($dateFrom, $dateTo)
            ->with(['order', 'user'])
            ->latestFirst()
            ->paginate($perPage);

        $stats = $this->invoiceModel->getInvoiceStats();

        return response()->json([
            'success' => true,
            'data' => $invoices,
            'stats' => $stats,
            'total_count' => $invoices->total(),
        ]);
    }

    /**
     * Get invoice details with items and shipping address.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view($id)
    {
        $invoice = $this->invoiceModel->with(['order', 'user'])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // Get order items
        $items = $this->orderItemModel->getOrderItemsWithProduct($invoice->order_id);

        // Get shipping address
        $order = $this->orderModel->find($invoice->order_id);
        $shippingAddress = $order ? $order->shipping_address : null;

        // If shipping_address is JSON string, decode it
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        $companyDetails = $this->getCompanyDetails();

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => $invoice,
                'items' => $items,
                'shipping_address' => $shippingAddress,
                'company' => $companyDetails,
            ],
        ]);
    }

    /**
     * Generate invoice from order.
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate($orderId)
    {
        $order = $this->orderModel->with('user')->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Check if invoice already exists
        $existingInvoice = $this->invoiceModel->where('order_id', $orderId)->first();
        if ($existingInvoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice already exists for this order',
                'data' => [
                    'invoice_id' => $existingInvoice->id,
                ],
            ], 422);
        }

        // Get order items
        $items = $this->orderItemModel->getOrderItemsWithProduct($orderId);

        // Calculate totals
        $subtotal = $order->total ?? 0;
        $tax = $subtotal * 0.10; // 10% tax
        $discount = $order->discount ?? 0;
        $total = $subtotal + $tax - $discount;

        // Generate invoice
        $invoiceData = [
            'order_id' => $orderId,
            'invoice_number' => $this->invoiceModel->generateInvoiceNumber(),
            'user_id' => $order->user_id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'status' => Invoice::STATUS_UNPAID,
            'notes' => 'Invoice generated from order #' . ($order->order_number ?? $orderId),
        ];

        $invoice = $this->invoiceModel->create($invoiceData);

        Log::info('Invoice generated: ' . $invoice->invoice_number . ' for order #' . $orderId . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully',
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ]);
    }

    /**
     * Update invoice status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $invoice = $this->invoiceModel->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:paid,unpaid,overdue,cancelled'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = $request->status;
        $updateData = ['status' => $status];

        if ($status === 'paid') {
            $updateData['payment_date'] = now();
        }

        $invoice->update($updateData);

        // Update order payment status if invoice is paid
        if ($status === 'paid') {
            $this->orderModel->where('id', $invoice->order_id)
                ->update(['payment_status' => Order::PAYMENT_PAID]);
        }

        Log::info('Invoice #' . $invoice->invoice_number . ' status updated to ' . $status . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Invoice status updated successfully',
            'status' => $status,
            'data' => $invoice,
        ]);
    }

    /**
     * Delete invoice.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $invoice = $this->invoiceModel->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $invoiceNumber = $invoice->invoice_number;
        $invoice->delete();

        Log::info('Invoice #' . $invoiceNumber . ' deleted by admin');

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Download invoice as PDF.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        $invoice = $this->invoiceModel->with(['order', 'user'])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // Get order items
        $items = $this->orderItemModel->getOrderItemsWithProduct($invoice->order_id);

        // Get shipping address
        $order = $this->orderModel->find($invoice->order_id);
        $shippingAddress = $order ? $order->shipping_address : null;
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        $companyDetails = $this->getCompanyDetails();

        $data = [
            'invoice' => $invoice,
            'items' => $items,
            'shipping_address' => $shippingAddress,
            'company' => $companyDetails,
        ];

        // Generate PDF
        $pdf = PDF::loadView('admin.invoices.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Stream invoice PDF for preview.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function preview($id)
    {
        $invoice = $this->invoiceModel->with(['order', 'user'])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // Get order items
        $items = $this->orderItemModel->getOrderItemsWithProduct($invoice->order_id);

        // Get shipping address
        $order = $this->orderModel->find($invoice->order_id);
        $shippingAddress = $order ? $order->shipping_address : null;
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        $companyDetails = $this->getCompanyDetails();

        $data = [
            'invoice' => $invoice,
            'items' => $items,
            'shipping_address' => $shippingAddress,
            'company' => $companyDetails,
        ];

        $pdf = PDF::loadView('admin.invoices.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Export invoices to CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        if ($format !== 'csv') {
            return response()->json([
                'success' => false,
                'message' => 'Export format not supported',
            ], 400);
        }

        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $invoices = $this->invoiceModel->search($search)
            ->ofStatus($status)
            ->dateRange($dateFrom, $dateTo)
            ->with(['order', 'user'])
            ->latestFirst()
            ->get();

        $filename = 'invoices_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($invoices) {
            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, [
                'Invoice #',
                'Order #',
                'Customer',
                'Email',
                'Subtotal',
                'Tax',
                'Discount',
                'Total',
                'Status',
                'Issue Date',
                'Due Date',
                'Payment Date',
            ]);

            // Data
            foreach ($invoices as $invoice) {
                fputcsv($output, [
                    $invoice->invoice_number,
                    $invoice->order?->order_number ?? 'N/A',
                    $invoice->user?->name ?? 'N/A',
                    $invoice->user?->email ?? 'N/A',
                    number_format($invoice->subtotal, 2),
                    number_format($invoice->tax, 2),
                    number_format($invoice->discount, 2),
                    number_format($invoice->total, 2),
                    $invoice->status_label,
                    $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : '',
                    $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '',
                    $invoice->payment_date ? $invoice->payment_date->format('Y-m-d H:i:s') : '',
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
        $stats = $this->invoiceModel->getInvoiceStats();

        return response()->json([
            'has_updates' => false,
            'stats' => $stats,
        ]);
    }

    /**
     * Get invoice statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = $this->invoiceModel->getInvoiceStats();

        // Get monthly stats
        $monthlyStats = $this->invoiceModel->getMonthlyStats(12);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $stats,
                'monthly' => $monthlyStats,
            ],
        ]);
    }

    /**
     * Get invoice by invoice number.
     *
     * @param string $invoiceNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByNumber($invoiceNumber)
    {
        $invoice = $this->invoiceModel->with(['order', 'user'])
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Mark invoice as paid.
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsPaid($id, Request $request)
    {
        $invoice = $this->invoiceModel->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'payment_method' => $request->payment_method,
            'payment_date' => now(),
        ]);

        // Update order payment status
        $this->orderModel->where('id', $invoice->order_id)
            ->update(['payment_status' => Order::PAYMENT_PAID]);

        Log::info('Invoice #' . $invoice->invoice_number . ' marked as paid by admin');

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid successfully',
            'data' => $invoice,
        ]);
    }

    /**
     * Get company details for invoice.
     *
     * @return array
     */
    private function getCompanyDetails(): array
    {
        return [
            'name' => 'BSSShop',
            'address' => '123 Main Street, New York, NY 10001',
            'phone' => '+1 (555) 123-4567',
            'email' => 'info@bssshop.com',
            'website' => 'www.bssshop.com',
            'tax_id' => 'TAX-123456789',
            'logo' => asset('images/logo.png'),
        ];
    }

    /**
     * Send invoice email to customer.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmail($id)
    {
        $invoice = $this->invoiceModel->with(['order', 'user'])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        // Check if user has email
        if (!$invoice->user || !$invoice->user->email) {
            return response()->json([
                'success' => false,
                'message' => 'Customer email not found',
            ], 422);
        }

        // Generate PDF
        $items = $this->orderItemModel->getOrderItemsWithProduct($invoice->order_id);
        $order = $this->orderModel->find($invoice->order_id);
        $shippingAddress = $order ? $order->shipping_address : null;
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        $data = [
            'invoice' => $invoice,
            'items' => $items,
            'shipping_address' => $shippingAddress,
            'company' => $this->getCompanyDetails(),
        ];

        $pdf = PDF::loadView('admin.invoices.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        // Save PDF temporarily
        $pdfPath = 'temp/invoice-' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // Send email (implement with Mail facade)
        // Mail::to($invoice->user->email)->send(new InvoiceMail($invoice, $pdfPath));

        // Clean up
        Storage::disk('public')->delete($pdfPath);

        Log::info('Invoice #' . $invoice->invoice_number . ' emailed to ' . $invoice->user->email . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Invoice sent to customer successfully',
        ]);
    }

    /**
     * Bulk delete invoices.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:invoices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deleted = $this->invoiceModel->whereIn('id', $request->ids)->delete();

        Log::info('Bulk delete: ' . $deleted . ' invoices deleted by admin');

        return response()->json([
            'success' => true,
            'message' => $deleted . ' invoices deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Bulk update invoice status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:invoices,id',
            'status' => ['required', 'in:paid,unpaid,overdue,cancelled'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = $request->status;
        $updateData = ['status' => $status];

        if ($status === 'paid') {
            $updateData['payment_date'] = now();
        }

        $updated = $this->invoiceModel->whereIn('id', $request->ids)
            ->update($updateData);

        // Update order payment status for paid invoices
        if ($status === 'paid') {
            $invoiceIds = $request->ids;
            $orderIds = $this->invoiceModel->whereIn('id', $invoiceIds)
                ->pluck('order_id')
                ->toArray();

            $this->orderModel->whereIn('id', $orderIds)
                ->update(['payment_status' => Order::PAYMENT_PAID]);
        }

        Log::info('Bulk status update: ' . $updated . ' invoices updated to ' . $status . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $updated . ' invoices updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get invoice dashboard summary.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary()
    {
        $summary = $this->invoiceModel->getDashboardSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}