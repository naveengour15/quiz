<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\QuizUserFeedback;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QuizUserFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $feedbacks = QuizUserFeedback::query()
            ->where('tenant_id', getSuperAdminTenantId()) // Aapke helper function ke hisaab se
            ->with(['user', 'reviewedBy', 'repliedBy', 'closedBy'])
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($query) use ($search) {
                    $query->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.quiz-user-feedback.index', compact('feedbacks'));
    }

    public function show($id)
    {
        try {
            $feedback = QuizUserFeedback::with(['user', 'reviewedBy', 'repliedBy', 'closedBy'])->findOrFail($id);

            $data = [
                'id'         => $feedback->id,
                'subject'    => $feedback->subject ?? '—',
                'message'    => $feedback->message ?? '—',
                'status'     => ucfirst($feedback->status ?? 'pending'),
                'created_at' => $feedback->created_at ? $feedback->created_at->format('d M Y, h:i A') : '—',

                // ✅ User Data (Null-Safe using optional())
                'user' => [
                    'name'   => optional($feedback->user)->name ?? 'Deleted User',
                    'email'  => optional($feedback->user)->email ?? '—',
                    'mobile' => optional($feedback->user)->mobile ?? '—',
                ],

                // ✅ Admin Actions Data (Null-Safe)
                'review' => [
                    'note' => $feedback->review_note ?? '—',
                    'by'   => optional($feedback->reviewedBy)->name ?? '—',
                    'at'   => $feedback->reviewed_at ? $feedback->reviewed_at->format('d M Y, h:i A') : '—',
                ],
                'reply' => [
                    'message' => $feedback->reply_message ?? '—',
                    'by'      => optional($feedback->repliedBy)->name ?? '—',
                    'at'      => $feedback->replied_at ? $feedback->replied_at->format('d M Y, h:i A') : '—',
                ],
                'close' => [
                    'note' => $feedback->close_note ?? '—',
                    'by'   => optional($feedback->closedBy)->name ?? '—',
                    'at'   => $feedback->closed_at ? $feedback->closed_at->format('d M Y, h:i A') : '—',
                ],
            ];

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            // ✅ Exact error log karein taaki pata chale kya issue hai
            \Log::error('Quiz Feedback Show Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:quiz_user_feedback,id',
            'status' => 'required|in:reviewed,replied,closed',
            'note'   => 'required|string|max:1000', // Feedback ke liye note zaroori hai audit ke liye
        ]);

        try {
            $feedback = QuizUserFeedback::findOrFail($request->id);

            if ($feedback->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'This feedback is already closed and cannot be modified.',
                ], 422);
            }

            DB::beginTransaction();

            $adminId = auth()->id(); // Logged in admin/superadmin ID
            $now = now();
            $updateData = ['status' => $request->status];

            // Status ke hisaab se specific columns update karein
            if ($request->status === 'reviewed') {
                $updateData['review_note'] = $request->note;
                $updateData['reviewed_by'] = $adminId;
                $updateData['reviewed_at'] = $now;
            } elseif ($request->status === 'replied') {
                $updateData['reply_message'] = $request->note;
                $updateData['replied_by'] = $adminId;
                $updateData['replied_at'] = $now;
            } elseif ($request->status === 'closed') {
                $updateData['close_note'] = $request->note;
                $updateData['closed_by'] = $adminId;
                $updateData['closed_at'] = $now;
            }

            $feedback->update($updateData);

            AuditLog::log(
                'Updated feedback status to ' . $request->status,
                'Quiz User Feedback',
                'Feedback ID: ' . $feedback->id . ' | Note: ' . $request->note
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Feedback status updated to ' . ucfirst($request->status) . ' successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Feedback status update failed', [
                'feedback_id' => $request->id,
                'error'       => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the status.',
            ], 500);
        }
    }
}