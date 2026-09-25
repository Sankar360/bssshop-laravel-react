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
    /**
     * @var ContactMessage
     */
    protected $messageModel;

    /**
     * AdminMessageController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->messageModel = new ContactMessage();
    }

    /**
     * List messages with filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $perPage = $request->get('per_page', 20);

        $messages = $this->messageModel->search($search)
            ->ofStatus($status)
            ->latestFirst()
            ->paginate($perPage);

        $stats = $this->messageModel->getStatusSummary();

        return response()->json([
            'success' => true,
            'data' => $messages,
            'stats' => $stats,
            'total_count' => $messages->total(),
            'unread_count' => $stats['unread'] ?? 0,
        ]);
    }

    /**
     * View message details.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view($id)
    {
        $message = $this->messageModel->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        // Mark as read if unread
        if ($message->isUnread()) {
            $message->markAsRead($id);
            $message->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }

    /**
     * Reply to a message.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reply(Request $request, $id)
    {
        $message = $this->messageModel->find($id);

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
                'errors' => $validator->errors(),
            ], 422);
        }

        $reply = $request->reply;

        // Save reply in database
        $updated = $message->markAsReplied($id, $reply);

        if ($updated) {
            // Send email reply
            $emailSent = $this->sendReplyEmail(
                $message->email,
                $message->name,
                $reply,
                $message->subject
            );

            Log::info('Reply sent to message #' . $id . ' from ' . $message->email . ' by admin');

            return response()->json([
                'success' => true,
                'message' => 'Reply sent successfully',
                'email_sent' => $emailSent,
                'data' => $message,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send reply',
        ], 500);
    }

    /**
     * Delete a message.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $message = $this->messageModel->find($id);

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

    /**
     * Bulk actions on messages.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['mark_read', 'mark_unread', 'delete'])],
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:contact_messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $action = $request->action;
        $messageIds = $request->message_ids;

        try {
            switch ($action) {
                case 'mark_read':
                    $this->messageModel->whereIn('id', $messageIds)
                        ->update(['status' => ContactMessage::STATUS_READ]);
                    $message = 'Messages marked as read';
                    break;

                case 'mark_unread':
                    $this->messageModel->whereIn('id', $messageIds)
                        ->update(['status' => ContactMessage::STATUS_UNREAD]);
                    $message = 'Messages marked as unread';
                    break;

                case 'delete':
                    $this->messageModel->whereIn('id', $messageIds)->delete();
                    $message = 'Messages deleted successfully';
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid action',
                    ], 422);
            }

            Log::info('Bulk action: ' . $action . ' on ' . count($messageIds) . ' messages by admin');

            return response()->json([
                'success' => true,
                'message' => $message,
                'affected_count' => count($messageIds),
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check for new messages (AJAX).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUpdates()
    {
        $unreadCount = $this->messageModel->getUnreadCount();

        return response()->json([
            'has_updates' => $unreadCount > 0,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Export messages to CSV.
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

        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = $this->messageModel->search($search)
            ->ofStatus($status)
            ->latestFirst();

        $messages = $query->get();

        $filename = 'messages_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($messages) {
            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, [
                'ID',
                'Name',
                'Email',
                'Subject',
                'Message',
                'Status',
                'Admin Reply',
                'Replied At',
                'Created At',
            ]);

            // Data
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

    /**
     * Get message statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = $this->messageModel->getStatusSummary();

        // Get today's messages count
        $todayCount = $this->messageModel->whereDate('created_at', today())->count();

        // Get last 7 days message count
        $weekCount = $this->messageModel->where('created_at', '>=', now()->subDays(7))->count();

        $stats['today'] = $todayCount;
        $stats['this_week'] = $weekCount;

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get messages by email.
     *
     * @param string $email
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByEmail($email, Request $request)
    {
        $limit = $request->get('limit', 10);

        $messages = $this->messageModel->getMessagesByEmail($email, $limit);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Mark multiple messages as read.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markMultipleRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:contact_messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->messageModel->markMultipleAsRead($request->ids);

        return response()->json([
            'success' => true,
            'message' => $updated . ' messages marked as read',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get message for editing (admin reply).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForReply($id)
    {
        $message = $this->messageModel->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'subject' => $message->subject,
                'message' => $message->message,
                'status' => $message->status,
                'admin_reply' => $message->admin_reply,
            ],
        ]);
    }

    /**
     * Send reply email to customer.
     *
     * @param string $to
     * @param string $name
     * @param string $reply
     * @param string|null $subject
     * @return bool
     */
    private function sendReplyEmail(string $to, string $name, string $reply, ?string $subject = null): bool
    {
        try {
            $emailSubject = 'Re: ' . ($subject ?? 'Contact Form Inquiry');

            $data = [
                'name' => $name,
                'reply' => $reply,
                'subject' => $subject,
                'year' => date('Y'),
            ];

            // Send email using Laravel Mail
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

    /**
     * Preview reply email (for testing).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewReply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'reply' => 'required|string',
            'subject' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'reply' => $request->reply,
            'subject' => $request->subject,
            'year' => date('Y'),
        ];

        // Return HTML preview
        $html = view('emails.contact-reply', $data)->render();

        return response()->json([
            'success' => true,
            'data' => [
                'html' => $html,
            ],
        ]);
    }

    /**
     * Get unread message count (for notification badge).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCount()
    {
        $unreadCount = $this->messageModel->getUnreadCount();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get latest messages.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatest(Request $request)
    {
        $limit = $request->get('limit', 5);

        $messages = $this->messageModel->latestFirst()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Search messages (autocomplete).
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

        $messages = $this->messageModel->search($query)
            ->latestFirst()
            ->limit($limit)
            ->get(['id', 'name', 'email', 'subject', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Get message dashboard summary.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary()
    {
        $stats = $this->messageModel->getStatusSummary();

        // Get messages for the last 7 days
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = $this->messageModel->whereDate('created_at', $date)->count();
            $weeklyData[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $stats,
                'weekly' => $weeklyData,
            ],
        ]);
    }
}