<?php

namespace App\Repositories\fsrapp;

use App\Interfaces\fsrapp\EGiftvoucherRepositoryInterface;
use App\Models\EGiftVoucher;
use App\Models\EGiftVoucherRequest;
use App\Models\ProcessingFee;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\WhatsAppRepositoryInterface; // ✅ Interface Import

class EGiftvoucherRepository implements EGiftvoucherRepositoryInterface
{
    /**
     * Get all active and available e-gift vouchers
     *
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected $whatsAppRepository;
    public function __construct(WhatsAppRepositoryInterface $whatsAppRepository)
    {
        $this->whatsAppRepository = $whatsAppRepository;
    }


    public function getAvailableVouchers(int $tenantId = 1)
    {
        return EGiftVoucher::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('amount', 'asc')
            ->get();
    }

    /**
     * Get user's e-gift voucher requests
     *
     * @param int $userId
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserVoucherRequests(int $userId, int $tenantId = 1)
    {
        return EGiftVoucherRequest::with('voucher')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Create a new voucher redeem request for a user
     *
     * @param int $userId
     * @param int $voucherId
     * @param int $tenantId
     * @return array
     */
    public function createVoucherRequest(int $userId, int $voucherId, int $tenantId = 1)
    {
        try {
            return DB::transaction(function () use ($userId, $voucherId, $tenantId) {
                // 1. Fetch user with lock
                $user = User::where('id', $userId)->lockForUpdate()->first();
                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'User account not found. Please log in again.'
                    ];
                }

                // 2. Fetch voucher with lock
                $voucher = EGiftVoucher::where('id', $voucherId)
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    return [
                        'success' => false,
                        'message' => 'Selected voucher is no longer active or available.'
                    ];
                }

                // 3. Check stock if limit is set
                if ($voucher->no_of_voucher > 0 && $voucher->no_of_used >= $voucher->no_of_voucher) {
                    return [
                        'success' => false,
                        'message' => 'Sorry, this voucher is currently out of stock.'
                    ];
                }

                // 4. Fetch configured processing fee for vouchers
                $processingFee = ProcessingFee::where('tenant_id', $tenantId)->first();
                $feePercent = $processingFee ? (float) $processingFee->voucher : 0.0;

                $baseAmount = (float) $voucher->amount;
                $feeAmount = round(($baseAmount * $feePercent) / 100, 2);
                $totalPointsRequired = $baseAmount + $feeAmount;
                $userWalletBalance = (float) ($user->wallet_balance ?? 0);

                // 5. Wallet balance verification (Must have at least totalPointsRequired)
                if ($userWalletBalance < $totalPointsRequired) {
                    $needed = $totalPointsRequired - $userWalletBalance;
                    $feeMsg = $feePercent > 0 ? " (incl. {$feePercent}% processing fee)" : "";
                    return [
                        'success' => false,
                        'message' => "Insufficient points! You need " . number_format($needed) . " more points{$feeMsg} to claim this voucher."
                    ];
                }

                // 6. Deduct total points from user's wallet
                $balanceBefore = $userWalletBalance;
                $user->wallet_balance = max(0, $userWalletBalance - $totalPointsRequired);
                $user->save();

                // 7. Increment voucher used count
                $voucher->increment('no_of_used');

                // 8. Create record in e_gift_voucher_requests table
                $voucherRequest = EGiftVoucherRequest::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'e_gift_voucher_id' => $voucher->id,
                    'wollet_balance' => $balanceBefore,
                    'voucher_amount' => $baseAmount,
                    'fee_percent' => $feePercent,
                    'fee_amount' => $feeAmount,
                    'total_amount' => $totalPointsRequired,
                    // 'image' => $voucher->image,
                    'url' => null,
                    'status' => 'pending',
                    'remark' => 'Requested ' . $voucher->name,
                ]);

                // 9. Log into wallet_history
                $historyRemarks = 'E-Gift Voucher Request - ' . $voucher->name;
                if ($feePercent > 0) {
                    $historyRemarks .= ' (incl. ' . $feePercent . '% fee: ' . number_format($feeAmount) . ' pts)';
                }

                WalletHistory::create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                    'type' => 'debit',
                    'transaction_type' => 'voucher_redeem',
                    'amount' => $totalPointsRequired,
                    'old_wallet_balance' => $balanceBefore,
                    'new_wallet_balance' => $user->wallet_balance,
                    'reference_id' => $voucherRequest->id,
                    'remarks' => $historyRemarks,
                ]);

                // ✅ 10. Call Separate WhatsApp Alert Method
                $this->sendAdminWhatsAppAlert($user, $voucher, $totalPointsRequired, $tenantId);


                return [
                    'success' => true,
                    'message' => 'Your voucher Request Saved Successfully. When approve Request then share on your whatsapp and check your vouchers request',
                    'data' => $voucherRequest,
                    'remaining_balance' => $user->wallet_balance
                ];
            });
        } catch (\Throwable $e) {
            Log::error('Error creating voucher request: ' . $e->getMessage(), [
                'user_id' => $userId,
                'voucher_id' => $voucherId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'An unexpected error occurred while processing your request. Please try again.'
            ];
        }
    }

    // ***********whatsup send message**********
    private function sendAdminWhatsAppAlert(User $user, EGiftVoucher $voucher, float $totalPointsRequired, int $tenantId): void
    {
        try {
            $adminNumbers = [
                //'7097341395', // Number 1 (Commented)
                //'9981534320', // Number 2 (Commented)
                '8889710101',   // Number 3 (Active)
            ];

            // Template variables for 'Redeem_Request_Alert' 
            // (Adjust order as per your actual WhatsApp template variables)
            $bodyParameters = [];

            foreach ($adminNumbers as $number) {
                $cleanNumber = trim((string) $number);
                if (empty($cleanNumber)) {
                    continue;
                }

                // Call the injected repository method
                $this->whatsAppRepository->sendWhatsAppMessage(
                    $cleanNumber,
                    'Redeem_Request_Alert', 
                    $bodyParameters,
                    [], // Header/Media params (empty)
                    (object) ['id' => $tenantId] // Tenant context object
                );
            }
        } catch (\Exception $e) {
            // Agar WhatsApp API fail ho, toh bhi user ka request save rahega. Sirf log hoga.
            Log::warning('WhatsApp Admin Alert Failed for Voucher Request: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'voucher_id' => $voucher->id
            ]);
        }
    }
    //*************************************** */
}
