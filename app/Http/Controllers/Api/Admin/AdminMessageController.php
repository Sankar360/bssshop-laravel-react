<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminMessageController extends Controller
{
    protected $messageModel;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->messageModel = new ContactMessage();
    }

    // ==================== LIST ====================

    /**
     * List messages with filters + stats.
     * Returns a predictable shape the frontend can rely on.
     */
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $status   = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $perPage  = (int) $request->get('per_page', 20);

        // ✅ Always start with a fresh builder (avoids sticky-query bugs)
        $paginator = ContactMessage::query()
            ->search($search)
            ->ofStatus($status)
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->latestFirst()
            ->paginate($perPage);

        $stats = (new ContactMessage())->getStatusSummary();

        return response()->json([
            'success' => true,
            'data' => [
                'messages'   => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
                'stats' => $stats,
            ],
            'total_count'  => $paginator->total(),
            'unread_count' => $stats['unread'] ?? 0,
        ]);
    }

    // ==================== VIEW ====================

    public function view($id)
{
    $message = ContactMessage::query()->find($id);

    if (!$message) {
        return response()->json([
            'success' => false,
            'message' => 'Message not found',
        ], 404);
    }

    if ($message->isUnread()) {
        $message->update(['status' => ContactMessage::STATUS_READ]);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'message' => $message->fresh(),   // ✅ wrapped
        ],
    ]);
}

    // ==================== REPLY ====================

    public function reply(Request $request, $id)
    {
        $message = ContactMessage::query()->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $reply = $request->reply;

        $updated = (new ContactMessage())->markAsReplied($id, $reply);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save reply',
            ], 500);
        }

        // Send email (best effort)
        $emailSent = $this->sendReplyEmail(
            $message->email,
            $message->name,
            $reply,
            $message->subject
        );

        Log::info('Reply sent to message #' . $id . ' from ' . $message->email . ' by admin');

        return response()->json([
            'success'    => true,
            'message'    => 'Reply sent successfully',
            'email_sent' => $emailSent,
            'data'       => $message->fresh(),
        ]);
    }

    // ==================== DELETE ====================

    public function delete($id)
    {
        $message = ContactMessage::query()->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        $message->delete();

        Log::info('Message #' . $id . ' deleted by admin');

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }

    // ==================== BULK ACTION ====================

    public function bulkAction(Request $request)
    {
        // ✅ Accept BOTH 'message_ids' (preferred) and 'ids' (legacy)
        $rawIds = $request->input('message_ids', $request->input('ids', []));

        $validator = Validator::make(
            ['action' => $request->input('action'), 'message_ids' => $rawIds],
            [
                'action'         => ['required', Rule::in(['mark_read', 'mark_unread', 'delete'])],
                'message_ids'    => 'required|array|min:1',
                'message_ids.*'  => 'integer|exists:contact_messages,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $action     = $request->input('action');
        $messageIds = $rawIds;

        try {
            switch ($action) {
                case 'mark_read':
                    $affected = ContactMessage::query()
                        ->whereIn('id', $messageIds)
                        ->update(['status' => ContactMessage::STATUS_READ]);
                    $msg = 'Messages marked as read';
                    break;

                case 'mark_unread':
                    $affected = ContactMessage::query()
                        ->whereIn('id', $messageIds)
                        ->update(['status' => ContactMessage::STATUS_UNREAD]);
                    $msg = 'Messages marked as unread';
                    break;

                case 'delete':
                    $affected = ContactMessage::query()
                        ->whereIn('id', $messageIds)
                        ->delete();
                    $msg = 'Messages deleted successfully';
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid action',
                    ], 422);
            }

            Log::info('Bulk action: ' . $action . ' on ' . count($messageIds) . ' messages by admin');

            return response()->json([
                'success'        => true,
                'message'        => $msg,
                'affected_count' => $affected,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk action error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== CHECK UPDATES ====================

    public function checkUpdates()
    {
        $unreadCount = (new ContactMessage())->getUnreadCount();

        return response()->json([
            'success'      => true,
            'has_updates'  => $unreadCount > 0,
            'unread_count' => $unreadCount,
        ]);
    }

    // ==================== EXPORT CSV ====================

    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        if ($format !== 'csv') {
            return response()->json([
                'success' => false,
                'message' => 'Export format not supported',
            ], 400);
        }

        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $messages = ContactMessage::query()
            ->search($search)
            ->ofStatus($status)
            ->latestFirst()
            ->get();

        $filename = 'messages_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($messages) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'ID', 'Name', 'Email', 'Subject', 'Message',
                'Status', 'Admin Reply', 'Replied At', 'Created At',
            ]);

            foreach ($messages as $message) {
                fputcsv($output, [
                    $message->id,
                    $message->name,
                    $message->email,
                    $message->subject ?? 'N/A',
                    substr($message->message, 0, 100) . '...',
                    $message->status_label,
                    substr($message->admin_reply ?? '', 0, 100) . '...',
                    $message->replied_at ? $message->replied_at->format('Y-m-d H:i:s') : '',
                    $message->created_at ? $message->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==================== STATS ====================

    public function getStats()
    {
        $stats = (new ContactMessage())->getStatusSummary();

        $stats['today']     = ContactMessage::query()->whereDate('created_at', today())->count();
        $stats['this_week'] = ContactMessage::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    // ==================== BY EMAIL ====================

    public function getByEmail($email, Request $request)
    {
        $limit = (int) $request->get('limit', 10);

        $messages = (new ContactMessage())->getMessagesByEmail($email, $limit);

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    // ==================== MARK MULTIPLE READ ====================

    public function markMultipleRead(Request $request)
    {
        $ids = $request->input('ids', $request->input('message_ids', []));

        $validator = Validator::make(
            ['ids' => $ids],
            [
                'ids'   => 'required|array|min:1',
                'ids.*' => 'integer|exists:contact_messages,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $updated = (new ContactMessage())->markMultipleAsRead($ids);

        return response()->json([
            'success'       => true,
            'message'       => $updated . ' messages marked as read',
            'updated_count' => $updated,
        ]);
    }

    // ==================== FOR REPLY ====================

    public function getForReply($id)
    {
        $message = ContactMessage::query()->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'          => $message->id,
                'name'        => $message->name,
                'email'       => $message->email,
                'subject'     => $message->subject,
                'message'     => $message->message,
                'status'      => $message->status,
                'admin_reply' => $message->admin_reply,
            ],
        ]);
    }

    // ==================== PREVIEW REPLY ====================

    public function previewReply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string',
            'reply'   => 'required|string',
            'subject' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = [
            'name'    => $request->name,
            'reply'   => $request->reply,
            'subject' => $request->subject,
            'year'    => date('Y'),
        ];

        // Wrap in try/catch — the view may not exist yet
        try {
            $html = view('emails.contact-reply', $data)->render();
        } catch (\Exception $e) {
            $html = '<p>Preview unavailable: ' . e($e->getMessage()) . '</p>';
        }

        return response()->json([
            'success' => true,
            'data'    => ['html' => $html],
        ]);
    }

    // ==================== UNREAD COUNT ====================

    public function getUnreadCount()
    {
        $unreadCount = (new ContactMessage())->getUnreadCount();

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
        ]);
    }

    // ==================== LATEST ====================

    public function getLatest(Request $request)
    {
        $limit = (int) $request->get('limit', 5);

        $messages = ContactMessage::query()
            ->latestFirst()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    // ==================== SEARCH ====================

    public function search(Request $request)
    {
        $query = $request->get('q');
        $limit = (int) $request->get('limit', 10);

        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }

        $messages = ContactMessage::query()
            ->search($query)
            ->latestFirst()
            ->limit($limit)
            ->get(['id', 'name', 'email', 'subject', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    // ==================== DASHBOARD SUMMARY ====================

    public function getDashboardSummary()
    {
        $stats = (new ContactMessage())->getStatusSummary();

        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i)->toDateString();
            $count = ContactMessage::query()->whereDate('created_at', $date)->count();
            $weeklyData[] = ['date' => $date, 'count' => $count];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $stats,
                'weekly'  => $weeklyData,
            ],
        ]);
    }

    // ==================== EMAIL HELPER ====================

    private function sendReplyEmail(string $to, string $name, string $reply, ?string $subject = null): bool
    {
        try {
            $emailSubject = 'Re: ' . ($subject ?? 'Contact Form Inquiry');

            $data = [
                'name'    => $name,
                'reply'   => $reply,
                'subject' => $subject,
                'year'    => date('Y'),
            ];

            Mail::send('emails.contact-reply', $data, function ($message) use ($to, $emailSubject) {
                $message->to($to)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject($emailSubject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send reply email: ' . $e->getMessage());
            return false;
        }
    }
}