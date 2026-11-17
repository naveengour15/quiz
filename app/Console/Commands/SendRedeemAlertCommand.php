<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RedeemRequest;
use App\Repositories\WhatsAppRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendRedeemAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redeem:send-whatsapp-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pending WhatsApp alert messages to admins for new redeem requests';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppRepositoryInterface $whatsAppRepository): int
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        if (\App\Models\Quiz::isAnyQuizPlayingNow()) {
            $this->info("Skipped: A quiz is currently active or calculating winners.");
            return Command::SUCCESS;
        }

        $this->processRedeemRequestAlerts($whatsAppRepository);

        return Command::SUCCESS;
    }

    /**
     * Process pending redeem request alerts to admins.
     */
    public function processRedeemRequestAlerts(WhatsAppRepositoryInterface $whatsAppRepository): void
    {
        // 1. Database se sirf 'pending' status wale redeem requests nikalo
        $pendingRedeems = RedeemRequest::where('redeem_msg_status', 'pending')
            ->with('user')
            ->get();

        if ($pendingRedeems->isEmpty()) {
            $this->info('No pending redeem requests to alert.');
            return;
        }

        $this->info("🔄 Processing {$pendingRedeems->count()} pending redeem requests...");

        // 2. 3 Admin Numbers
        $adminNumbers = [
            //'7097341395', // Number 1
            //'9981534320', // Number 2
            '8889710101', // Number 3
        ];
        foreach ($pendingRedeems as $redeemRequest) {
            $userName = $redeemRequest->user?->name ?: ($redeemRequest->user?->player_name ?: ($redeemRequest->user?->mobile ?: 'User'));
            $requestType = ucfirst(str_replace('_', ' ', $redeemRequest->request_type ?? 'Redeem'));
            $amount = (string) ($redeemRequest->redeem_amount ?? '0');
            $mobile = $redeemRequest->mobile ?: ($redeemRequest->user?->mobile ?: 'N/A');
            $consumerNumber = $redeemRequest->consumer_number ?: 'N/A';

            $bodyParameters = [];

            $tenantId = $redeemRequest->tenant_id ?? 1;
            $successCount = 0;

            // 3. Loop: Sabhi admin numbers par alert message send karega
            foreach ($adminNumbers as $number) {
                $cleanNumber = trim((string) $number);
                if (empty($cleanNumber)) {
                    continue;
                }

                try {
                    Log::info("Attempting to send Redeem Alert to Admin: {$cleanNumber} for Request ID: {$redeemRequest->id}");

                    $response = $whatsAppRepository->sendWhatsAppMessage(
                        $cleanNumber,
                        'Redeem_Request_Alert', 
                        $bodyParameters,
                        [],
                        (object) ['id' => $tenantId]
                    );

                    // Agar successfully send ho gaya
                    if ($response !== false) {
                        $successCount++;
                        Log::info("✅ SUCCESS: Redeem Alert sent to {$cleanNumber} for Request ID: {$redeemRequest->id}");
                        $this->info("✅ Redeem Alert Sent for ID: {$redeemRequest->id} to {$cleanNumber}");
                    } else {
                        Log::warning("⚠️ Failed to send Redeem Alert to {$cleanNumber} for Request ID: {$redeemRequest->id}");
                    }

                } catch (Throwable $e) {
                    Log::warning("⚠️ Exception for Admin {$cleanNumber} | Request ID: {$redeemRequest->id} | Error: " . $e->getMessage());
                }
            }

            // 4. Status update: agar kam se kam 1 number par chala gaya to 'Sent', warna 'failed'
            if ($successCount > 0) {
                $redeemRequest->update(['redeem_msg_status' => 'Sent']);
                Log::info("✅ Redeem Alert completed for Request ID: {$redeemRequest->id} (Delivered to {$successCount} admin numbers).");
            } else {
                $redeemRequest->update(['redeem_msg_status' => 'failed']);
                Log::error("🚨 CRITICAL: Redeem Alert FAILED to ALL admin numbers for Request ID: {$redeemRequest->id}");
                $this->error("❌ Redeem Alert FAILED for ID: {$redeemRequest->id} on all numbers.");
            }
        }
    }
}
