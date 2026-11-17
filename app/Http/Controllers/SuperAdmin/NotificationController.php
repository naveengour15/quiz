<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Quiz;
use Illuminate\Http\Request;
use App\Models\QuizNotification;
use Illuminate\Support\Facades\DB;
use App\Models\ActionTrail;
use App\Exports\QuizNotificationLeadsExport;
use Maatwebsite\Excel\Facades\Excel;


class NotificationController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::latest()->paginate(15);

        return view('superadmin.notifications.index', compact('campaigns'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'channels.*' => 'nullable|string',
            'segment' => 'required|string',
            'schedule_type' => 'required|in:now,later',
            'scheduled_at' => 'nullable|date',
            'title' => 'required|string|max:191',
            'message' => 'required|string',
            'deep_link' => 'nullable|string',
        ]);
        $data['channels'] = $request->input('channels', []);
        $data['status'] = $data['schedule_type'] === 'now' ? 'sent' : 'scheduled';
        if ($data['status'] === 'sent') {
            $data['sent_at'] = now();
        }

        Campaign::create($data);
        AuditLog::log('Created campaign', 'Engage', $data['name']);

        return redirect()->route('superadmin.notifications.index')->with('success', 'Campaign saved.');
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:campaigns,id',
            'status' => 'required|in:draft,scheduled,sent',
        ]);

        $campaign = Campaign::findOrFail($request->id);

        $campaign->status = $request->status;

        if ($request->status == 'sent') {
            $campaign->sent_at = now();
        }

        $campaign->save();

        AuditLog::log(
            'Updated campaign status',
            'Engage',
            $campaign->name
        );

        return response()->json([
            'success' => true,
            'message' => 'Campaign status updated.'
        ]);
    }

      public function quizNotificationHistory(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $notifications = QuizNotification::with(['quiz', 'user']) // Search (Quiz Name + User Name + Message)
        ->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('quiz_name', 'LIKE', "%{$search}%")
                  ->orWhere('user_name', 'LIKE', "%{$search}%")
                  ->orWhere('msg', 'LIKE', "%{$search}%");
            });
        })

        // Status Filter
        ->when($request->filled('status'), function ($query) use ($request) {
            $query->where('status', $request->status);
        })

        // Quiz Filter
        ->when($request->filled('quiz'), function ($query) use ($request) {
            $query->where('quiz_name', 'LIKE', "%{$request->quiz}%");
        })

        // From Date Filter
        ->when($request->filled('from_date'), function ($query) use ($request) {
            $query->whereDate('sent_date_time', '>=', $request->from_date);
        })

        // To Date Filter
        ->when($request->filled('to_date'), function ($query) use ($request) {
            $query->whereDate('sent_date_time', '<=', $request->to_date);
        })
        ->latest()->paginate($perPage)
        ->withQueryString();

        // $quizzes = QuizNotification::query()
        //         ->select('quiz_id', 'quiz_name')
        //         ->whereNotNull('quiz_id')
        //         ->whereNotNull('quiz_name')
        //         ->distinct()
        //         ->orderBy('quiz_name')
        //         ->get();

  

        $quizzes = QuizNotification::query()
            ->select('quiz_notifications.quiz_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_notifications.quiz_id')
            ->whereNotNull('quiz_notifications.quiz_id')
            ->where('quizzes.complete_status', '!=', 'completed')
            ->selectRaw('quizzes.id as quiz_id, quizzes.title as quiz_name')
            ->distinct()
            ->orderBy('quizzes.title')
            ->get();

        return view('superadmin.notifications.quiz_notification_history', compact('notifications', 'quizzes'));
    }

    public function updateQuizNotificationStatus(Request $request)
    {
        $data = $request->validate([
            'quiz_id' => 'required',
            'current_status' => 'required|in:sent,pending,failed',
            'new_status' => 'required|in:sent,pending,failed|different:current_status',
        ]);

        // $query = QuizNotification::query();

        $query = QuizNotification::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_notifications.quiz_id')
            ->where('quizzes.complete_status', '!=', 'completed');

        // Particular quiz selected
        if ($data['quiz_id'] !== 'all') {
            // $query->where('quiz_id', $data['quiz_id']);
            $query->where('quiz_notifications.quiz_id', $data['quiz_id']);
        }

        // Current status filter
        // $query->where('status', $data['current_status']);
        $query->where('quiz_notifications.status', $data['current_status']);

        // Update status
        $updatedCount = $query->update([
            // 'status' => $data['new_status'],
             'quiz_notifications.status' => $data['new_status'],
        ]);

        return redirect()->route('superadmin.notifications.quizHistory')
            ->with(
                'success',
                $updatedCount . ' notification(s) status updated successfully.'
            );
    }

    public function createLeads(Request $request)
    {
        $data = $request->validate([
            'quiz_id'  => 'required|integer',
            'category' => 'required|string|max:100',
            'leadtype' => 'required|in:customer,retailer,other',
        ]);

        // Selected quiz ki saari notification entries
        // $notifications = QuizNotification::with('user')
        $notifications = QuizNotification::with(['user', 'quiz'])
            ->where('quiz_id', $data['quiz_id'])
            ->get();

        if ($notifications->isEmpty()) {
            return redirect()->route('superadmin.notifications.quizHistory')->with('error', 'Selected quiz ki koi notification entry nahi mili.');
        }

        $createdCount = 0;
        $createdLeadIds = [];

        foreach ($notifications as $notification) {
            $user = $notification->user;
            if (!$user) {
                continue;
            }

            $leadId = DB::connection('mysql_staff')
                ->table('leads')
                ->insertGetId([
                    'scraper_id'    => null,
                    // 'business_name' => $notification->quiz_name,
                    'business_name' => $notification->quiz->title ?? $notification->quiz_name,
                    'contact_name'  => $user->name ?? $notification->user_name,
                    'email'         => $user->email ?? null,
                    'phone'         => $user->mobile ?? null,
                    'website'       => null,
                    'address'       => null,
                    'city'          => null,
                    'category'      => $data['category'],
                    'status'        => 'new',
                    'notes'         => 'Fsrquiz',
                    'leadtype'      => $data['leadtype'],
                    'quiz_time'     => $notification->quiz_schedule_time,
                    'quiz_url'      => $notification->quiz_id . '?auto_join=1',

                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

            $createdLeadIds[] = $leadId;
            $createdCount++;
        }

    
        if ($createdCount > 0) {
            ActionTrail::create([
                'tenant_id' => null,
                'quiz_id'            => $notification->quiz_id,
                 'user_id'            => $notification->user_id,
                'table_name'         => 'leads',
                'reference_id'      => $data['quiz_id'],
                'action'             => 'Created Leads from Fsrquiz',
                'updated_by'         => auth()->id(),
                'updated_by_role_id' => auth()->user()->role_id ?? null,
                'old_details'       => json_encode(null),
                'details'            => json_encode([
                'quiz_id'          => $data['quiz_id'],
                 // 'quiz_name'        => $notifications->first()->quiz_name ?? null,
                'quiz_name' => $notifications->first()->quiz->title ?? $notifications->first()->quiz_name ?? null,
                  
                'quiz_time'    => $notifications->first()->quiz_schedule_time ?? null,
                'quiz_url'     => $data['quiz_id'] . '?auto_join=1',
                'category'         => $data['category'],
                'leadtype'         => $data['leadtype'],
                'source'           => 'Fsrquiz',
                'status'           => 'new',
                'total_leads'      => $createdCount,
                'lead_ids'         => $createdLeadIds,
                ]),
                  'status'              =>  'success',
            ]);
        }

        return redirect()->route('superadmin.notifications.quizHistory')->with(
                'success',
                $createdCount . ' lead(s) created successfully.'
            );
    }

    public function downloadLeadsExcel(Request $request)
    {
        $data = $request->validate([
            'quiz_id'  => 'required|integer',
            'category' => 'required|string|max:100',
            'leadtype' => 'required|in:customer,retailer,other',
        ]);

        $quiz = Quiz::findOrFail($data['quiz_id']);

        $fileName = 'fsrquiz_leads_' . $quiz->id . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new QuizNotificationLeadsExport(
                $data['quiz_id'],
                $data['category'],
                $data['leadtype']
            ),
            $fileName
        );
    }
    
}
