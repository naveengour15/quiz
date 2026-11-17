<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppUpdation;
use App\Models\AppUpdationHistory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppUpdationController extends Controller
{
    public function index(Request $request)
    {
        $query = AppUpdation::query()
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

        $appUpdations = $query
            ->paginate(20)
            ->withQueryString();

        return view(
            'superadmin.app_updations.index',
            compact('appUpdations')
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

            $appUpdation = DB::transaction(function () use ($data) {
                return AppUpdation::create($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'App updation sent successfully.',
                'data' => $appUpdation,
            ]);

        } catch (\Throwable $e) {
            Log::error('App Updation Store Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send app updation.',
            ], 500);
        }
    }

    public function show(AppUpdation $app_updation)
    {
        $app_updation->load('sender');

        $users = [];
        $retailers = [];

        if (
            $app_updation->recipient_type === 'individual_user' &&
            !empty($app_updation->user_id)
        ) {
            $users = User::whereIn(
                'id',
                $app_updation->user_id
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
            $app_updation->recipient_type === 'individual_retailer' &&
            !empty($app_updation->tenant_id)
        ) {
            $retailers = Tenant::whereIn(
                'id',
                $app_updation->tenant_id
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
            'data' => $app_updation,
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

    public function reads(AppUpdation $app_updation)
    {
        $reads = AppUpdationHistory::query()
            ->with(
                'user:id,name,email,mobile'
            )
            ->where(
                'app_updation_id',
                $app_updation->id
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
