<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\EGiftVoucher;
use App\Models\EGiftVoucherRequest;
use App\Models\EGiftFrom;
use App\Models\WalletHistory;
use App\Models\AuditLog;
use App\Models\WhatsAppTemplateSetting;
use App\Repositories\WhatsAppRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class EGiftVoucherController extends Controller
{
    protected $whatsAppRepo;

    public function __construct(WhatsAppRepositoryInterface $whatsAppRepo)
    {
        $this->whatsAppRepo = $whatsAppRepo;
    }


    private function compressVoucherImage($file)
    {
        $sourcePath = $file->getRealPath();

        if (!$sourcePath || !file_exists($sourcePath)) {
            throw new \Exception('Voucher image file not found.');
        }

        $originalSize = filesize($sourcePath);

        $info = getimagesize($sourcePath);

        if (!$info) {
            throw new \Exception('Invalid voucher image.');
        }

        $mime = $info['mime'];

        /*GIF ko WebP me convert nahi karenge.*/
        if ($mime === 'image/gif') {
            return [
                'content'         => file_get_contents($sourcePath),
                'extension'       => 'gif',
                'mime'            => 'image/gif',
                'original_size'   => $originalSize,
                'compressed_size' => $originalSize,
            ];
        }

        /* Read Im-- */
        switch ($mime) {

            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;

            case 'image/png':
                $image = imagecreatefrompng($sourcePath);

                // PNG transparency preserve
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;

            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                break;

            default:
                throw new \Exception('Unsupported voucher image format.');
        }

        if (!$image) {
            throw new \Exception('Unable to read voucher image.');
        }

        /* WebP Compression*/
        $qualities = [75, 65, 55, 45, 35, 25];

        $bestCompressed = null;
        $bestSize = PHP_INT_MAX;

        foreach ($qualities as $quality) {

            ob_start();

            imagewebp(
                $image,
                null,
                $quality
            );

            $compressed = ob_get_clean();

            if ($compressed === false) {
                continue;
            }

            $compressedSize = strlen($compressed);

            if ($compressedSize < $bestSize) {
                $bestCompressed = $compressed;
                $bestSize = $compressedSize;
            }

            // Original se chhota mil gaya
            if ($compressedSize < $originalSize) {
                break;
            }
        }

        imagedestroy($image);

        /*Aggressive Compression Fallback */
        if ($bestSize >= $originalSize) {

            switch ($mime) {

                case 'image/jpeg':
                    $image = imagecreatefromjpeg($sourcePath);
                    break;

                case 'image/png':
                    $image = imagecreatefrompng($sourcePath);

                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    break;

                case 'image/webp':
                    $image = imagecreatefromwebp($sourcePath);
                    break;
            }

            if ($image) {

                ob_start();

                imagewebp(
                    $image,
                    null,
                    10
                );

                $aggressiveCompressed = ob_get_clean();

                imagedestroy($image);

                if (
                    $aggressiveCompressed !== false &&
                    strlen($aggressiveCompressed) < $originalSize
                ) {
                    $bestCompressed = $aggressiveCompressed;
                    $bestSize = strlen($aggressiveCompressed);
                }
            }
        }

        /* Final Result  */
        return [
            'content'          => $bestCompressed,
            'extension'        => 'webp',
            'mime'             => 'image/webp',
            'original_size'    => $originalSize,
            'compressed_size'  => $bestSize,
        ];
    }

    // ==========================================
    // 1. E-Gift Vouchers List (Super Admin View)
    // ==========================================
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $vouchers = EGiftVoucher::query()
            ->where('tenant_id', getSuperAdminTenantId())
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('type', 'like', "%{$request->search}%");
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $giftFroms = EGiftFrom::query()
            ->where('tenant_id', getSuperAdminTenantId())
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get();

        return view('superadmin.e-gift-voucher.index', compact('vouchers', 'giftFroms'));
    }

    // ✅ FALLBACK: Agar kahi se create route call ho jaye, toh error na aaye
    public function create()
    {
        return redirect()->route('superadmin.e_gift_voucher.index');
    }

    // ✅ FALLBACK: Agar kahi se edit route call ho jaye, toh error na aaye
    public function edit($id)
    {
        return redirect()->route('superadmin.e_gift_voucher.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'no_of_voucher' => 'required|integer|min:1',
            'amount'        => 'required|numeric|min:0',
            'status'        => 'required|in:active,inactive',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:1024',
        ]);

       

        /*if ($request->hasFile('image')) {
            $file = $request->file('image');

            if ($file && $file->isValid()) {

                $imageName = uniqid(time(), true) . '.webp';
                $path = "vouchers/{$imageName}";

                try {
                    $manager = new ImageManager(new Driver);

                    $image = $manager->decode($file);

                    if ($image->width() > 1920) {
                        $image->scaleDown(width: 1920);
                    }

                    $encoded = $image->encode(new WebpEncoder(90));

                    Storage::disk('s3')->put(
                        $path,
                        (string) $encoded,
                        'public'
                    );

                } catch (\Exception $imgEx) {

                    Log::warning(
                        'EGift voucher store image fallback: ' .
                        $imgEx->getMessage()
                    );

                    Storage::disk('s3')->put(
                        $path,
                        file_get_contents($file->getRealPath()),
                        'public'
                    );
                }

                // Only S3 path will be saved in DB
                $validated['image'] = $path;
            }
        }*/
            
         if ($request->hasFile('image')) {

            $file = $request->file('image');

            if ($file && $file->isValid()) {

                try {

                    $compressedImage = $this->compressVoucherImage($file);

                    $imageName = uniqid(time(), true) . '.' .
                        $compressedImage['extension'];

                    $path = "vouchers/{$imageName}";

                    Storage::disk('s3')->put(
                        $path,
                        $compressedImage['content'],
                        [
                            'visibility'  => 'public',
                            'ContentType' => $compressedImage['mime'],
                        ]
                    );

                    // Only S3 path will be saved in DB
                    $validated['image'] = $path;

                     /*Log::info('Compression Update', [
                        'original_size'   => round($compressedImage['original_size'] / 1024, 2) . ' KB',
                        'compressed_size' => round($compressedImage['compressed_size'] / 1024, 2) . ' KB',
                    ]);*/

                } catch (\Exception $imgEx) {

                    Log::warning(
                        'EGift voucher image compression failed: ' .
                        $imgEx->getMessage()
                    );

                    // Fallback: original image upload
                    $imageName = uniqid(time(), true) . '.' .
                        $file->getClientOriginalExtension();

                    $path = "vouchers/{$imageName}";

                    Storage::disk('s3')->put(
                        $path,
                        file_get_contents($file->getRealPath()),
                        [
                            'visibility'  => 'public',
                            'ContentType' => $file->getMimeType(),
                        ]
                    );

                    $validated['image'] = $path;
                }
            }
        }   

        $validated['no_of_used'] = 0;
        $validated['tenant_id'] = getSuperAdminTenantId();

        EGiftVoucher::create($validated);

        return redirect()->route('superadmin.e_gift_voucher.index')
                         ->with('success', 'Gift Voucher added successfully!');
    }

    public function update(Request $request, $id)
    {
        $voucher = EGiftVoucher::where('tenant_id', getSuperAdminTenantId())->findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'no_of_voucher' => 'required|integer|min:1',
            'amount'        => 'required|numeric|min:0',
            'status'        => 'required|in:active,inactive',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:1024',
        ]);

           /*if ($request->hasFile('image')) {

                $file = $request->file('image');

                if ($file && $file->isValid()) {

                    // Delete old image from S3
                    if ($voucher->image) {
                        try {
                            $oldPath = ltrim(
                                parse_url($voucher->image, PHP_URL_PATH),
                                '/'
                            );

                            Storage::disk('s3')->delete($oldPath);

                        } catch (\Exception $e) {
                            Log::warning(
                                'Old voucher image delete failed: ' .
                                $e->getMessage()
                            );
                        }
                    }

                    // New image name
                    $imageName = uniqid(time(), true) . '.webp';

                    // S3 path
                    $path = "vouchers/{$imageName}";

                    try {

                        $manager = new ImageManager(new Driver);

                        $image = $manager->decode($file);

                        // Maximum width 1920px
                        if ($image->width() > 1920) {
                            $image->scaleDown(width: 1920);
                        }

                        // Convert to WebP
                        $encoded = $image->encode(
                            new WebpEncoder(90)
                        );

                        Storage::disk('s3')->put(
                            $path,
                            (string) $encoded,
                            'public'
                        );

                    } catch (\Exception $imgEx) {

                        Log::warning(
                            'EGift voucher update image fallback: ' .
                            $imgEx->getMessage()
                        );

                        // Fallback: original image upload
                        Storage::disk('s3')->put(
                            $path,
                            file_get_contents($file->getRealPath()),
                            'public'
                        );
                    }

                    // Save only S3 path in database
                    $validated['image'] = $path;
                }
            }    */



                if ($request->hasFile('image')) {

                    $file = $request->file('image');

                    if ($file && $file->isValid()) {

                        // Delete old image from S3
                        if ($voucher->image) {
                            try {
                                Storage::disk('s3')->delete($voucher->image);
                            } catch (\Exception $e) {
                                Log::warning(
                                    'Old voucher image delete failed: ' .
                                    $e->getMessage()
                                );
                            }
                        }

                        try {

                            $compressedImage = $this->compressVoucherImage($file);

                            $imageName = uniqid(time(), true) . '.' .
                                $compressedImage['extension'];

                            $path = "vouchers/{$imageName}";

                            Storage::disk('s3')->put(
                                $path,
                                $compressedImage['content'],
                                [
                                    'visibility'  => 'public',
                                    'ContentType' => $compressedImage['mime'],
                                ]
                            );

                            // Save only S3 path in database
                            $validated['image'] = $path;

                            /* Log::info('Compression Update', [
                                    'original_size'   => round($compressedImage['original_size'] / 1024, 2) . ' KB',
                                    'compressed_size' => round($compressedImage['compressed_size'] / 1024, 2) . ' KB',
                                ]); */

                        } catch (\Exception $imgEx) {

                            Log::warning(
                                'EGift voucher update image compression failed: ' .
                                $imgEx->getMessage()
                            );

                            // Fallback: original image upload
                            $imageName = uniqid(time(), true) . '.' .
                                $file->getClientOriginalExtension();

                            $path = "vouchers/{$imageName}";

                            Storage::disk('s3')->put(
                                $path,
                                file_get_contents($file->getRealPath()),
                                [
                                    'visibility'  => 'public',
                                    'ContentType' => $file->getMimeType(),
                                ]
                            );

                            $validated['image'] = $path;
                        }
                    }
                }

        $voucher->update($validated);

        return redirect()->route('superadmin.e_gift_voucher.index')
                         ->with('success', 'Gift Voucher updated successfully!');
    }

    public function destroy($id)
    {
        $voucher = EGiftVoucher::where('tenant_id', getSuperAdminTenantId())->findOrFail($id);
        $voucher->delete();

        return redirect()->route('superadmin.e_gift_voucher.index')
                         ->with('success', 'Gift Voucher deleted successfully!');
    }

    // ==========================================
    // 2. E-Gift Voucher Requests List
    // ==========================================
    public function requests(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $requests = EGiftVoucherRequest::query()
            ->where('tenant_id', getSuperAdminTenantId())
            ->with(['user', 'voucher'])
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('user', function ($uq) use ($request) {
                    $uq->where('name', 'like', "%{$request->search}%")
                       ->orWhere('mobile', 'like', "%{$request->search}%");
                })->orWhereHas('voucher', function ($vq) use ($request) {
                    $vq->where('name', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.e-gift-voucher.requests', compact('requests'));
    }

    // ==========================================
    // 3. Show Request Details (For Modal)
    // ==========================================
    public function showRequest($id)
    {
        try {
            $request = EGiftVoucherRequest::with(['user', 'voucher', 'approver'])->findOrFail($id);
            $points = (float) ($request->total_amount ?: ($request->voucher_amount ?: 0));
            $currentBal = (float) ($request->user->wallet_balance ?? 0);
            $walletWithPoints = $request->wollet_balance ? (float) $request->wollet_balance : ($currentBal + $points);

            $imageUrl = null;
            if (!empty($request->image)) {
                if (filter_var($request->image, FILTER_VALIDATE_URL) || Str::startsWith($request->image, ['http://', 'https://'])) {
                    $imageUrl = $request->image;
                } else {
                    $imageUrl = Storage::disk('s3')->url($request->image);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->id,
                    'voucher_name' => $request->voucher->name ?? 'N/A',
                    'voucher_amount' => $request->voucher_amount,
                    'voucher_code' => $request->voucher_code,
                    'fee_percent' => $request->fee_percent ?? 0,
                    'fee_amount' => $request->fee_amount ?? 0,
                    'total_amount' => $points,
                    'status' => $request->status,
                    'remark' => $request->remark,
                    'url' => $request->url,
                    'image' => $imageUrl,
                    'user' => [
                        'name' => $request->user->name ?? '—',
                        'mobile' => $request->user->mobile ?? '—',
                        'wallet_balance' => $walletWithPoints,
                        'current_balance' => $currentBal,
                    ],
                    'approver' => $request->approver ? $request->approver->name : '—',
                    'approve_date' => $request->approve_date ? \Carbon\Carbon::parse($request->approve_date)->format('d M Y, h:i A') : '—',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch details.'], 500);
        }
    }

    // ==========================================
    // 4. Approve / Reject / Cancel Logic
    // ==========================================
    public function approveChangeStatus(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:e_gift_voucher_requests,id',
            'status'        => 'required|in:approved,rejected,cancelled',
            'note'          => 'nullable|string|max:1000',
            'voucher_code'  => 'nullable|string|max:255',
            'url'           => 'nullable|string|max:1000',
            'voucher_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB for S3 Upload
        ]);

        try {
            $voucherRequest = EGiftVoucherRequest::with(['user', 'voucher'])->findOrFail($request->id);

            if ($voucherRequest->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'This request has already been processed.'], 422);
            }

            if ($request->status === 'rejected' && empty(trim($request->note))) {
                return response()->json(['success' => false, 'message' => 'Rejection note is required.'], 422);
            }

            DB::beginTransaction();

            $updateData = [
                'status'       => $request->status,
                'approver_id'  => Auth::id(), 
                'approve_date' => now(),
            ];

            if ($request->filled('note')) {
                $updateData['remark'] = trim($request->note);
            }
            
            // ✅ LOGIC: Approve - Handle voucher_code, url, and S3 image upload with public visibility
            if ($request->status === 'approved') {
                if ($request->filled('voucher_code')) {
                    $updateData['voucher_code'] = trim($request->voucher_code);
                }

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
                    try {
                        $file = $request->file('voucher_image');
                        if ($file && $file->isValid()) {
                            $imageName = uniqid(time(), true) . '.webp';
                            $path = "vouchers/{$imageName}";

                            try {
                                $manager = new ImageManager(new Driver);
                                $image = $manager->decode($file);
                                if ($image->width() > 1920) {
                                    $image->scaleDown(width: 1920);
                                }
                                $encoded = $image->encode(new WebpEncoder(90));

                                Storage::disk('s3')->put(
                                    $path,
                                    (string) $encoded,
                                    'public'
                                );
                            } catch (\Exception $imgEx) {
                                Log::warning('Intervention image encoding failed, falling back to direct upload: ' . $imgEx->getMessage());
                                Storage::disk('s3')->put(
                                    $path,
                                    file_get_contents($file->getRealPath()),
                                    'public'
                                );
                            }

                            $updateData['image'] = Storage::disk('s3')->url($path);
                        }
                    } catch (\Exception $e) {
                        Log::error('S3 Voucher Image Upload Failed: ' . $e->getMessage());
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'Failed to upload image to S3: ' . $e->getMessage()], 500);
                    }
                }
            }

            // ✅ LOGIC: Reject/Cancel - Refund Points (including fee) back to user's wallet
            if ($request->status === 'rejected' || $request->status === 'cancelled') {
                $refundPoints = (float) ($voucherRequest->total_amount ?: ($voucherRequest->voucher_amount ?: 0));
                $user = $voucherRequest->user;
                if ($user) {
                    $user->wallet_balance += $refundPoints;
                    $user->save();

                    WalletHistory::create([
                        'tenant_id'        => $voucherRequest->tenant_id,
                        'user_id'          => $user->id,
                        'amount'           => $refundPoints,
                        'type'             => 'credit',
                        'title'            => 'Refund - E-Gift Voucher',
                        'description'      => 'Points refunded for ' . $request->status . ' voucher request',
                        'transaction_type' => 'refund',
                        'reference_id'     => $voucherRequest->id,
                        'remarks'          => 'Voucher request ' . $request->status . ' - ' . ($request->note ?? 'No note'),
                    ]);
                }
            }

            $voucherRequest->update($updateData);

            if (class_exists('App\Models\AuditLog')) {
                $deliveryInfo = 'Status: ' . $request->status;
                if (!empty($updateData['voucher_code'])) $deliveryInfo .= ' | Code: ' . $updateData['voucher_code'];
                if (!empty($updateData['url'])) $deliveryInfo .= ' | URL: ' . $updateData['url'];
                if (!empty($updateData['image'])) $deliveryInfo .= ' | S3 Image Uploaded';
                AuditLog::log(
                    'Updated E-Gift Voucher request status to ' . $request->status,
                    'E-Gift Voucher Request',
                    'Request ID: ' . $voucherRequest->id . ' | ' . $deliveryInfo
                );
            }

            DB::commit();

            // 📱 Send WhatsApp Notification to User
            $this->sendVoucherNotificationToUser($voucherRequest->fresh(['user', 'voucher']), $request->status, $request->note);

            return response()->json([
                'success' => true,
                'message' => 'E-Gift Voucher request ' . $request->status . ' successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('E-Gift Voucher request status update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send WhatsApp notification to user regarding voucher request status
     */
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

                // Parameters matching Meta live template 'approved_voucher' ({{1}}, {{2}}, {{3}})
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

                // Parameters matching Meta live template 'voucher_request_update' ({{1}}, {{2}}, {{3}}, {{4}})
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
    // ***************gift forms***************


    // ==========================================
    // E-Gift Froms Management Methods
    // ==========================================

    public function indexFroms(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $forms = EGiftFrom::query()
            ->where('tenant_id', getSuperAdminTenantId())
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.e-gift-voucher.froms', compact('forms'));
    }

    public function storeFroms(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['tenant_id'] = getSuperAdminTenantId();

        EGiftFrom::create($validated);

        return redirect()->route('superadmin.e_gift_froms.index')
                        ->with('success', 'E-Gift From added successfully!');
    }

    public function updateFroms(Request $request, $id)
    {
        $form = EGiftFrom::where('tenant_id', getSuperAdminTenantId())->findOrFail($id);

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $form->update($validated);

        return redirect()->route('superadmin.e_gift_froms.index')
                        ->with('success', 'E-Gift From updated successfully!');
    }

    public function destroyFroms($id)
    {
        $form = EGiftFrom::where('tenant_id', getSuperAdminTenantId())->findOrFail($id);
        
        $form->delete(); // Soft delete

        return redirect()->route('superadmin.e_gift_froms.index')
                        ->with('success', 'E-Gift From deleted successfully!');
    }
    // ***************gift forms end***************
}