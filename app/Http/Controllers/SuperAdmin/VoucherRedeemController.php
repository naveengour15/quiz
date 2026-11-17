<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\RedeemRequest;
use App\Models\EGiftVoucherRequest;
use App\Models\WalletHistory;
use App\Models\AuditLog;
use App\Repositories\WhatsAppRepositoryInterface; // ✅ WhatsApp Repository Import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoucherRedeemController extends Controller
{
    protected $whatsAppRepo;

    // ✅ Constructor mein WhatsApp Repository inject karein
    public function __construct(WhatsAppRepositoryInterface $whatsAppRepo)
    {
        $this->whatsAppRepo = $whatsAppRepo;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default 15 rakha hai, aap 10 bhi kar sakte hain
        $search = $request->get('search');
        $status = $request->get('status');
        $sourceType = $request->get('source_type', 'all');

        // 1. Redeem Query
        $redeemQuery = RedeemRequest::query()
            ->select(
                'id',
                DB::raw("'Redeem' as source_table"),
                'request_type as type_name',
                'user_id',
                'mobile',
                'redeem_amount as amount',
                'consumer_number',
                'company',
                'status',
                'approver_note as note',
                'user_note',
                'created_at'
            )
            ->where('tenant_id', getSuperAdminTenantId())
            ->when($sourceType === 'Voucher', fn($q) => $q->whereRaw('1=0'))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('mobile', 'like', "%{$search}%")
                          ->orWhere('consumer_number', 'like', "%{$search}%")
                          ->orWhere('company', 'like', "%{$search}%")
                          ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"));
                });
            });

        // 2. Voucher Query
        $voucherQuery = EGiftVoucherRequest::query()
            ->select(
                'id',
                DB::raw("'Voucher' as source_table"),
                DB::raw("'E-Gift Voucher' as type_name"),
                'user_id',
                DB::raw("NULL as mobile"),
                'total_amount as amount',
                DB::raw("NULL as consumer_number"),
                DB::raw("NULL as company"),
                'status',
                'remark as note',
                DB::raw("NULL as user_note"),
                'created_at'
            )
            ->where('tenant_id', getSuperAdminTenantId())
            ->when($sourceType === 'Redeem', fn($q) => $q->whereRaw('1=0'))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"))
                          ->orWhereHas('voucher', fn($vq) => $vq->where('name', 'like', "%{$search}%"));
                });
            });

        // 3. Union & Paginate
        $unionQuery = $redeemQuery->unionAll($voucherQuery);
        $paginated = DB::query()->fromSub($unionQuery, 'unified')->orderBy('created_at', 'desc')->paginate($perPage);
        
        // ✅ FIX: Yeh line ensure karti hai ki 'per_page', 'search', 'status', 'source_type' 
        // sab pagination links (Page 1, 2, 3...) ke saath jud kar rahenge.
        $paginated->appends(request()->query());

        // 4. Eager Load Relationships
        $redeemIds = collect($paginated->items())->where('source_table', 'Redeem')->pluck('id')->toArray();
        $voucherIds = collect($paginated->items())->where('source_table', 'Voucher')->pluck('id')->toArray();

        $redeemModels = RedeemRequest::with(['user', 'electricityZone'])->whereIn('id', $redeemIds)->get()->keyBy('id');
        $voucherModels = EGiftVoucherRequest::with(['user', 'voucher'])->whereIn('id', $voucherIds)->get()->keyBy('id');

        foreach ($paginated->getCollection() as $item) {
            $item->model = $item->source_table === 'Redeem' ? ($redeemModels[$item->id] ?? null) : ($voucherModels[$item->id] ?? null);
        }

        return view('superadmin.redeem-request.voucher-redeem.index', compact('paginated', 'sourceType', 'status', 'search'));
    }

   

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'source_table' => 'required|in:Redeem,Voucher',
            'status' => 'required|in:approved,rejected,cancelled',
            'note' => 'nullable|string|max:1000',
            'voucher_code' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:1000',
            'voucher_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        DB::beginTransaction();
        try {
            if ($request->source_table === 'Redeem') {
                $model = RedeemRequest::with('user')->findOrFail($request->id);
                if ($model->status !== 'pending') return response()->json(['success' => false, 'message' => 'Already processed.'], 422);
                if ($request->status === 'rejected' && empty(trim($request->note))) return response()->json(['success' => false, 'message' => 'Rejection note required.'], 422);

                $updateData = ['status' => $request->status];
                if ($request->filled('note')) $updateData['approver_note'] = trim($request->note);

                if (in_array($request->status, ['rejected', 'cancelled'])) {
                    $user = $model->user;
                    $user->wallet_balance += $model->redeem_amount;
                    $user->save();
                    WalletHistory::create([
                        'tenant_id' => $model->tenant_id, 'user_id' => $user->id, 'amount' => $model->redeem_amount,
                        'type' => 'credit', 'title' => 'Refund - ' . $model->request_type,
                        'description' => 'Points refunded for ' . $request->status . ' request',
                        'transaction_type' => 'refund', 'reference_id' => $model->id,
                        'remarks' => 'Redeem request ' . $request->status . ' - ' . ($request->note ?? 'No note'),
                    ]);
                }
                $model->update($updateData);
                AuditLog::log('Updated Unified Redeem', 'Redeem Request', 'ID: ' . $model->id);

            } else {
                $model = EGiftVoucherRequest::with(['user', 'voucher'])->findOrFail($request->id);
                if ($model->status !== 'pending') return response()->json(['success' => false, 'message' => 'Already processed.'], 422);
                if ($request->status === 'rejected' && empty(trim($request->note))) return response()->json(['success' => false, 'message' => 'Rejection note required.'], 422);

                $updateData = ['status' => $request->status, 'approver_id' => Auth::id(), 'approve_date' => now()];
                if ($request->filled('note')) $updateData['remark'] = trim($request->note);

                if ($request->status === 'approved') {
                    if ($request->filled('voucher_code')) $updateData['voucher_code'] = trim($request->voucher_code);
                    if ($request->filled('url')) {
                        $cleanUrl = trim($request->url);
                        if (Str::startsWith(strtolower($cleanUrl), 'http://')) {
                            $cleanUrl = 'https://' . substr($cleanUrl, 7);
                        } elseif (!Str::startsWith(strtolower($cleanUrl), 'https://')) {
                            $cleanUrl = 'https://' . $cleanUrl;
                        }
                        $updateData['url'] = $cleanUrl;
                    }
                    if ($request->hasFile('voucher_image')) {
                        $file = $request->file('voucher_image');
                        $path = "vouchers/" . uniqid(time(), true) . '.webp';
                        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');
                        $updateData['image'] = Storage::disk('s3')->url($path);
                    }
                } elseif (in_array($request->status, ['rejected', 'cancelled'])) {
                    $user = $model->user;
                    $refundPoints = (float) ($model->total_amount ?: ($model->voucher_amount ?: 0));
                    $user->wallet_balance += $refundPoints;
                    $user->save();
                    WalletHistory::create([
                        'tenant_id' => $model->tenant_id, 'user_id' => $user->id, 'amount' => $refundPoints,
                        'type' => 'credit', 'title' => 'Refund - E-Gift Voucher',
                        'description' => 'Points refunded for ' . $request->status . ' voucher request',
                        'transaction_type' => 'refund', 'reference_id' => $model->id,
                        'remarks' => 'Voucher request ' . $request->status . ' - ' . ($request->note ?? 'No note'),
                    ]);
                }
                $model->update($updateData);
                AuditLog::log('Updated Unified Voucher', 'Voucher Request', 'ID: ' . $model->id);

                // 📱 ✅ WhatsApp Notification bhejna (Voucher ke liye)
                $this->sendVoucherNotificationToUser($model->fresh(['user', 'voucher']), $request->status, $request->note);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unified Status Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($source_table, $id)
    {
        try {
            if ($source_table === 'Redeem') {
                $req = RedeemRequest::with(['user', 'electricityZone'])->findOrFail($id);
                $data = [
                    'source' => 'Redeem Request',
                    'user' => $req->user->name ?? '—',
                    'user_mobile' => $req->user->mobile ?? '—',
                    'mobile' => $req->mobile ?? '—',
                    'type' => $req->request_type,
                    'amount' => $req->redeem_amount,
                    'company' => $req->company ?? '—',
                    'consumer_no' => $req->consumer_number ?? '—',
                    'zone' => $req->electricityZone ? $req->electricityZone->zone : '—',
                    'status' => $req->status,
                    'note' => $req->approver_note ?? '—',
                    'user_note' => $req->user_note ?? '—',
                    'date' => $req->created_at->format('d M Y, h:i A'),
                ];
            } else {
                $req = EGiftVoucherRequest::with(['user', 'voucher'])->findOrFail($id);
                $data = [
                    'source' => 'E-Gift Voucher Request',
                    'user' => $req->user->name ?? '—',
                    'mobile' => $req->mobile ?? '—',
                    'user_mobile' => $req->user->mobile ?? '—',
                    'type' => 'E-Gift Voucher',
                    'amount' => $req->total_amount,
                    'company' => $req->voucher->name ?? '—',
                    'consumer_no' => '—',
                    'zone' => '—',
                    'status' => $req->status,
                    'note' => $req->remark ?? '—',
                    'user_note' => '—',
                    'date' => $req->created_at->format('d M Y, h:i A'),
                    'voucher_code' => $req->voucher_code ?? '—',
                    'url' => $req->url ?? '—',
                    'image' => $req->image ?? '—',
                ];
            }
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch details.'], 500);
        }
    }

    // ==========================================
    // ✅ WhatsApp Notification Method (Copied from old controller)
    // ==========================================
    protected function sendVoucherNotificationToUser($voucherRequest, string $status, ?string $note = null)
    {
        try {
            $user = $voucherRequest->user;
            if (!$user || empty($user->mobile)) {
                return false;
            }

            $cleanMobile = preg_replace('/\D/', '', (string) $user->mobile);
            if (empty($cleanMobile)) {
                return false;
            }

            $userName = $user->name ?: ($user->player_name ?: 'Valued Player');
            $voucherName = $voucherRequest->voucher->name ?? 'E-Gift Voucher';
            $voucherCode = $voucherRequest->voucher_code;
            $voucherUrl = $voucherRequest->url;
            $voucherImage = null;
            
            if (!empty($voucherRequest->image)) {
                if (filter_var($voucherRequest->image, FILTER_VALIDATE_URL) || Str::startsWith($voucherRequest->image, ['http://', 'https://'])) {
                    $voucherImage = $voucherRequest->image;
                } else {
                    $voucherImage = Storage::disk('s3')->url($voucherRequest->image);
                }
            }
            
            $tenantId = $voucherRequest->tenant_id ?? 1;

            if ($status === 'approved') {
                $voucherDetail = '';
                if (!empty($voucherUrl)) {
                    $voucherDetail = "Voucher Link: " . $voucherUrl;
                } elseif (!empty($voucherImage)) {
                    $voucherDetail = "Voucher Image: " . $voucherImage;
                } elseif (!empty($voucherCode)) {
                    $voucherDetail = "Voucher Code: " . $voucherCode;
                } else {
                    $voucherDetail = "Voucher Code: " . ($voucherRequest->voucher->code ?? 'N/A');
                }

                $bodyParameters = [
                    $userName,
                    $voucherName,
                    $voucherDetail,
                ];

                return $this->whatsAppRepo->sendWhatsAppMessage(
                    $cleanMobile,
                    'VOUCHER_REQUEST_APPROVED',
                    $bodyParameters,
                    [],
                    (object) ['id' => $tenantId]
                );

            } elseif ($status === 'rejected' || $status === 'cancelled') {
                $reason = !empty($note) ? $note : ($voucherRequest->remark ?: 'Request could not be processed');
                $refundBalance = number_format((float) ($voucherRequest->total_amount ?: ($voucherRequest->voucher_amount ?: 0)));

                $bodyParameters = [
                    $userName,
                    $voucherName,
                    $reason,
                    $refundBalance,
                ];

                return $this->whatsAppRepo->sendWhatsAppMessage(
                    $cleanMobile,
                    'VOUCHER_REQUEST_REJECTED',
                    $bodyParameters,
                    [],
                    (object) ['id' => $tenantId]
                );
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('Error sending voucher WhatsApp message: ' . $e->getMessage(), [
                'request_id' => $voucherRequest->id ?? null,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}