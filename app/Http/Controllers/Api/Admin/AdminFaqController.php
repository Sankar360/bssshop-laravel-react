<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminFaqController extends Controller
{
    /**
     * @var FAQ
     */
    protected $faqModel;

    /**
     * AdminFaqController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->faqModel = new FAQ();
    }

    /**
     * List FAQs with filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status', 'all');
        $perPage = $request->get('per_page', 20);

        $faqs = $this->faqModel->search($search)
            ->ofCategory($category)
            ->ofStatus($status)
            ->ordered()
            ->paginate($perPage);

        $stats = $this->faqModel->getFAQStats();
        $categories = $this->faqModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'data' => $faqs,
            'stats' => $stats,
            'categories' => $categories,
            'total_count' => $faqs->total(),
        ]);
    }

    /**
     * Get data for FAQ creation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $categories = $this->faqModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new FAQ.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|min:5|max:255',
            'answer' => 'required|string|min:10',
            'category' => 'required|string|min:3|max:100',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'status' => $request->status,
            'order' => $request->order ?? 0,
        ];

        $faq = $this->faqModel->create($data);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully',
            'data' => $faq,
        ], 201);
    }

    /**
     * Get FAQ for editing.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $faq = $this->faqModel->find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found',
            ], 404);
        }

        $categories = $this->faqModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'data' => $faq,
            'categories' => $categories,
        ]);
    }

    /**
     * Update a FAQ.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $faq = $this->faqModel->find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'question' => 'required|string|min:5|max:255',
            'answer' => 'required|string|min:10',
            'category' => 'required|string|min:3|max:100',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'status' => $request->status,
            'order' => $request->order ?? 0,
        ];

        $faq->update($data);

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faq,
        ]);
    }

    /**
     * Delete a FAQ.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $faq = $this->faqModel->find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found',
            ], 404);
        }

        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully',
        ]);
    }

    /**
     * Toggle FAQ status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $faq = $this->faqModel->find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found',
            ], 404);
        }

        $newStatus = $faq->status === 'active' ? 'inactive' : 'active';
        $faq->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => 'Status updated successfully',
            'data' => $faq,
        ]);
    }

    /**
     * Reorder FAQs.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*' => 'integer|exists:faqs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            foreach ($request->orders as $index => $id) {
                $this->faqModel->where('id', $id)
                    ->update(['order' => $index + 1]);
            }

            return response()->json([
                'success' => true,
                'message' => 'FAQs reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder FAQs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export FAQs to CSV.
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

        $faqs = $this->faqModel->ordered()->get();

        $filename = 'faqs_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($faqs) {
            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, ['ID', 'Question', 'Answer', 'Category', 'Status', 'Order', 'Created Date']);

            // Data
            foreach ($faqs as $faq) {
                fputcsv($output, [
                    $faq->id,
                    $faq->question,
                    $faq->answer,
                    $faq->category,
                    $faq->status,
                    $faq->order,
                    $faq->created_at ? $faq->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get FAQ statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = $this->faqModel->getFAQStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get all distinct categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = $this->faqModel->getDistinctCategories();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Bulk delete FAQs.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:faqs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deleted = $this->faqModel->whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . ' FAQs deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Bulk update FAQ status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:faqs,id',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->faqModel->whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => $updated . ' FAQs updated successfully',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get next available order number.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextOrder()
    {
        $nextOrder = $this->faqModel->getNextOrder();

        return response()->json([
            'success' => true,
            'data' => [
                'next_order' => $nextOrder,
            ],
        ]);
    }

    /**
     * Get FAQs by category.
     *
     * @param string $category
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategory($category, Request $request)
    {
        $activeOnly = $request->get('active_only', true);

        $faqs = $this->faqModel->getFAQsByCategory($category, $activeOnly);

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    /**
     * Search FAQs (autocomplete).
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

        $faqs = $this->faqModel->search($query)
            ->ordered()
            ->limit($limit)
            ->get(['id', 'question', 'category', 'status']);

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    /**
     * Get FAQ by slug or ID for frontend.
     *
     * @param string $identifier
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicFaq($identifier)
    {
        $faq = $this->faqModel->where('id', $identifier)
            ->orWhere('slug', $identifier)
            ->active()
            ->first();

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $faq,
        ]);
    }

    /**
     * Get all active FAQs grouped by category.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicFAQs()
    {
        $faqs = $this->faqModel->getActiveFAQsGroupedByCategory();

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }
}