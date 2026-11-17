<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementHistory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::query()
            ->with('sender')
            ->latest();

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('recipient')) {
            $query->where(
                'recipient_type',
                $request->recipient
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('from_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->to_date
            );
        }

        $announcements = $query
            ->paginate(20)
            ->withQueryString();

        return view(
            'superadmin.announcements.index',
            compact('announcements')
        );
    }

    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'subject' => [
                    'required',
                    'string',
                    'max:200',
                ],

                'message' => [
                    'required',
                    'string',
                ],

                'recipient_type' => [
                    'required',
                    'in:all_user,all_retailer,individual_user,individual_retailer',
                ],

                'recipient_ids' => [
                    'nullable',
                    'array',
                ],

                'recipient_ids.*' => [
                    'integer',
                ],
            ]);

            $sender = Auth::guard('superadmin')->user();

            if (!$sender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Super Admin login session not found.',
                ], 401);
            }

            $data = [
                'user_id' => null,
                'tenant_id' => null,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'recipient_type' => $validated['recipient_type'],
                'sender_id' => $sender->id,
                'sender_name' => $sender->name ?? 'Super Admin',
                'status' => 'send',
            ];

            /*
            |--------------------------------------------------------------------------
            | Individual Users
            |--------------------------------------------------------------------------
            */

            if ($validated['recipient_type'] === 'individual_user') {

                $ids = array_values(
                    array_unique(
                        $validated['recipient_ids'] ?? []
                    )
                );

                if (empty($ids)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select at least one user.',
                    ], 422);
                }

                $userIds = User::whereIn('id', $ids)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->toArray();

                if (empty($userIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected users not found.',
                    ], 422);
                }

                $data['user_id'] = $userIds;
            }

            /*
            |--------------------------------------------------------------------------
            | Individual Retailers
            |--------------------------------------------------------------------------
            */

            if ($validated['recipient_type'] === 'individual_retailer') {

                $ids = array_values(
                    array_unique(
                        $validated['recipient_ids'] ?? []
                    )
                );

                if (empty($ids)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select at least one retailer.',
                    ], 422);
                }

                $tenantIds = Tenant::whereIn('id', $ids)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->toArray();

                if (empty($tenantIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected retailers not found.',
                    ], 422);
                }

                $data['tenant_id'] = $tenantIds;
            }

            $announcement = DB::transaction(function () use ($data) {
                return Announcement::create($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'Announcement sent successfully.',
                'data' => $announcement,
            ]);

        } catch (\Throwable $e) {

            Log::error('Announcement Store Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send announcement.',
            ], 500);
        }
    }

    public function show(Announcement $announcement)
    {
        $announcement->load('sender');

        $users = [];

        $retailers = [];

        if (
            $announcement->recipient_type === 'individual_user' &&
            !empty($announcement->user_id)
        ) {
            $users = User::whereIn(
                'id',
                $announcement->user_id
            )
                ->select(
                    'id',
                    'name',
                    'email',
                    'mobile'
                )
                ->get();
        }

        if (
            $announcement->recipient_type === 'individual_retailer' &&
            !empty($announcement->tenant_id)
        ) {
            $retailers = Tenant::whereIn(
                'id',
                $announcement->tenant_id
            )
                ->select(
                    'id',
                    'name',
                    'email',
                    'phone'
                )
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $announcement,
            'users' => $users,
            'retailers' => $retailers,
        ]);
    }

    public function searchUsers(Request $request)
    {
        $search = trim(
            $request->get('search', '')
        );

        $users = User::query()
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'mobile',
                            'like',
                            "%{$search}%"
                        );
                });
            })
            ->select(
                'id',
                'name',
                'email',
                'mobile'
            )
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function searchRetailers(Request $request)
    {
        $search = trim(
            $request->get('search', '')
        );

        $retailers = Tenant::query()
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        );
                });
            })
            ->select(
                'id',
                'name',
                'email',
                'phone'
            )
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $retailers,
        ]);
    }

    public function reads(Announcement $announcement)
    {
        $reads = AnnouncementHistory::query()
            ->with(
                'user:id,name,email,mobile'
            )
            ->where(
                'announcement_id',
                $announcement->id
            )
            ->whereNotNull('read_at')
            ->latest('read_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reads,
        ]);
    }
}