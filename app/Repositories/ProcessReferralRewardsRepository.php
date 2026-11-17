<?php

namespace App\Repositories;

use App\Models\ActionTrail;
use App\Models\QuizWinner;
use App\Models\ReferralCode;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessReferralRewardsRepository implements ProcessReferralRewardsRepositoryInterface
{
    /**
     * Default referral reward points/coins.
     */
    protected float $rewardAmount = 10.00;

    /**
     * Process all eligible referral rewards.
     */
    public function processRewards(bool $isDryRun = false, ?\Closure $outputCallback = null): array
    {
        $log = function (string $type, string $message) use ($outputCallback) {
            if ($outputCallback) {
                $outputCallback($type, $message);
            }
        };

        if ($isDryRun) {
            $log('warn', '[DRY-RUN MODE] No changes will be committed to the database.');
        }

        $log('info', 'Starting referral rewards processing via referral_codes table...');

        $processedCount = 0;
        $rewardedCount = 0;
        $skippedCount = 0;

        // 1. Process records from `referral_codes` table where a user was referred
        $referralCodes = ReferralCode::whereNotNull('referral_user_id')
            ->where('referral_user_id', '>', 0)
            ->whereNotNull('user_id')
            ->where('user_id', '>', 0)
            ->where('used', 'pending')
            ->get();

        $log('line', "Found {$referralCodes->count()} referral code usage records in referral_codes table.");

        foreach ($referralCodes as $referralRecord) {
            $processedCount++;

            try {
                $rewardGiven = $this->processReferralCodeRecord($referralRecord, $isDryRun, $outputCallback);
                if ($rewardGiven) {
                    $rewardedCount++;
                } else {
                    $skippedCount++;
                }
            } catch (\Throwable $e) {
                Log::error('Error processing referral_codes reward', [
                    'referral_code_id' => $referralRecord->id,
                    'referrer_user_id' => $referralRecord->user_id,
                    'referred_user_id' => $referralRecord->referral_user_id,
                    'message'          => $e->getMessage(),
                    'trace'            => $e->getTraceAsString(),
                ]);

                $log('error', "Failed to process referral code ID {$referralRecord->id}: {$e->getMessage()}");
            }
        }

        // 2. Also check for users with referral_user_id who may not have a record in `referral_codes` table
        $usersWithReferrer = User::whereNotNull('referral_user_id')
            ->where('referral_user_id', '>', 0)
            ->whereNotIn('id', function ($query) {
                $query->select('referral_user_id')
                    ->from('referral_codes')
                    ->whereNotNull('referral_user_id');
            })
            ->get();

        if ($usersWithReferrer->isNotEmpty()) {
            $log('line', "Found {$usersWithReferrer->count()} users with referral_user_id lacking referral_codes table records.");

            foreach ($usersWithReferrer as $newUser) {
                $processedCount++;

                try {
                    $rewardGiven = $this->processUserDirectReferral($newUser, $isDryRun, $outputCallback);
                    if ($rewardGiven) {
                        $rewardedCount++;
                    } else {
                        $skippedCount++;
                    }
                } catch (\Throwable $e) {
                    Log::error('Error processing user direct referral_codes reward', [
                        'new_user_id'      => $newUser->id,
                        'referrer_user_id' => $newUser->referral_user_id,
                        'message'          => $e->getMessage(),
                    ]);

                    $log('error', "Failed to process user ID {$newUser->id}: {$e->getMessage()}");
                }
            }
        }

        $summary = "Referral processing finished. Total Checked: {$processedCount}, Rewarded: {$rewardedCount}, Skipped/Already Processed: {$skippedCount}";
        $log('info', $summary);
        Log::info($summary);

        return [
            'processed_count' => $processedCount,
            'rewarded_count'  => $rewardedCount,
            'skipped_count'   => $skippedCount,
            'message'         => $summary,
        ];
    }

    /**
     * Process a single referral record from the referral_codes table.
     */
    public function processReferralCodeRecord(ReferralCode $referralRecord, bool $isDryRun = false, ?\Closure $outputCallback = null): bool
    {
        $log = function (string $type, string $message) use ($outputCallback) {
            if ($outputCallback) {
                $outputCallback($type, $message);
            }
        };

        $referrerId = (int) $referralRecord->user_id;
        $referredId = (int) $referralRecord->referral_user_id;

        // Prevent self-referral
        if ($referrerId === $referredId) {
            return false;
        }

        // Check if wallet history already exists for this pair to avoid duplicate rewards
        $alreadyRewarded = WalletHistory::where('user_id', $referrerId)
            ->where('referral_user_id', $referredId)
            ->where('transaction_type', 'referral')
            ->exists();

        if ($alreadyRewarded) {
            return false;
        }

        // Verify that the referred user exists
        $referredUser = User::find($referredId);
        if (!$referredUser) {
            $log('warn', "Referred user ID {$referredId} not found. Skipping referral_codes ID {$referralRecord->id}.");
            return false;
        }

        // Verify that the referrer user exists
        $referrerUser = User::find($referrerId);
        if (!$referrerUser) {
            $log('warn', "Referrer user ID {$referrerId} not found. Skipping referral_codes ID {$referralRecord->id}.");
            return false;
        }

        // Check if referred user has played a quiz (quiz_winners.total_question > 0)
        $firstQuizPlayed = QuizWinner::where('user_id', $referredId)
            ->where('total_question', '>', 0)
           
            // ->where('total_correct_answer', '>', 1)
            ->orderBy('id', 'asc')
            ->first();

        if (!$firstQuizPlayed) {
            // User hasn't played/completed a quiz yet
            return false;
        }

        $reward = (float) (($referralRecord->points && $referralRecord->points > 0) ? $referralRecord->points : $this->rewardAmount);

        if ($isDryRun) {
            $log('info', "[DRY-RUN] Would reward Referrer ID {$referrerId} with {$reward} coins (Referred user ID {$referredId} played Quiz ID {$firstQuizPlayed->quiz_id}).");
            return true;
        }

        return DB::transaction(function () use ($referralRecord, $firstQuizPlayed, $referrerId, $referredId, $referredUser, $reward, $log) {
            // Lock records for update to prevent race conditions
            $lockedReferralCode = ReferralCode::where('id', $referralRecord->id)->lockForUpdate()->first();
            $lockedReferrer = User::where('id', $referrerId)->lockForUpdate()->first();

            if (!$lockedReferralCode || !$lockedReferrer) {
                return false;
            }

            // Check again inside transaction to prevent race conditions
            $alreadyRewarded = WalletHistory::where('user_id', $referrerId)
                ->where('referral_user_id', $referredId)
                ->where('transaction_type', 'referral')
                ->exists();

            if ($alreadyRewarded) {
                return false;
            }

            $oldReferralCodeSnapshot = $lockedReferralCode->toArray();
            $oldUserSnapshot = $lockedReferrer->toArray();

            $oldBalance = (float) ($lockedReferrer->wallet_balance ?? 0);
            $newBalance = $oldBalance + $reward;

            // 1. Update Referrer's wallet balance
            $lockedReferrer->wallet_balance = $newBalance;
            $lockedReferrer->save();

            // 2. Insert into wallet_history
            $walletHistory = WalletHistory::create([
                'tenant_id'          => $lockedReferrer->tenant_id ?? 1,
                'user_id'            => $lockedReferrer->id,
                'quiz_id'            => null,
                'referral_user_id'   => $referredId,
                'survey_id'          => null,
                'old_wallet_balance' => $oldBalance,
                'amount'             => $reward,
                'new_wallet_balance' => $newBalance,
                'type'               => 'credit',
                'transaction_type'   => 'referral',
                'reference_id'       => (string) $lockedReferralCode->id,
                // 'remarks'            => "Referral bonus: User #{$referredId} (" . ($referredUser->name ?? 'User') . ") completed first quiz",
                  'remarks' => "Referral bonus : " . ($referredUser->name ?? 'User'),
                
            ]);

            // 3. Mark referral_codes record as used and point provided
            $lockedReferralCode->update([
                'used'                   => 'used',
                'used_date'              => $lockedReferralCode->used_date ?? now(),
                'referral_point_provide' => 'yes',
                'points'                 => $reward,
                'status'                 => 'active',
            ]);

            // 4. ActionTrail Logging
            // Trail for users table
            $this->logActionTrail(
                $lockedReferrer->tenant_id,
                'users',
                $lockedReferrer->id,
                'update wallet referral reward',
                $lockedReferrer->toArray(),
                $oldUserSnapshot,
                $firstQuizPlayed->quiz_id ?? null,
                $lockedReferrer->id
            );

            // Trail for wallet_history table
            $this->logActionTrail(
                $lockedReferrer->tenant_id,
                'wallet_history',
                $walletHistory->id,
                'add wallet_history referral reward',
                $walletHistory->toArray(),
                null,
                $firstQuizPlayed->quiz_id ?? null,
                $lockedReferrer->id
            );

            // Trail for referral_codes table
            $this->logActionTrail(
                $lockedReferralCode->tenant_id,
                'referral_codes',
                $lockedReferralCode->id,
                'update referral_codes reward completed',
                $lockedReferralCode->toArray(),
                $oldReferralCodeSnapshot,
                $firstQuizPlayed->quiz_id ?? null,
                $lockedReferrer->id
            );

            Log::info('Referral reward credited via referral_codes successfully', [
                'referral_code_id'   => $lockedReferralCode->id,
                'referrer_user_id'   => $lockedReferrer->id,
                'referred_user_id'   => $referredId,
                'quiz_id'            => $firstQuizPlayed->quiz_id ?? null,
                'reward_amount'      => $reward,
                'old_wallet_balance' => $oldBalance,
                'new_wallet_balance' => $newBalance,
            ]);

            $log('info', "Rewarded {$reward} coins to Referrer ID {$lockedReferrer->id} for Referred User ID {$referredId}.");

            return true;
        });
    }

    /**
     * Process referral for a user who has referral_user_id set on their profile but no referral_codes table record.
     */
    public function processUserDirectReferral(User $newUser, bool $isDryRun = false, ?\Closure $outputCallback = null): bool
    {
        $log = function (string $type, string $message) use ($outputCallback) {
            if ($outputCallback) {
                $outputCallback($type, $message);
            }
        };

        $referrerId = (int) $newUser->referral_user_id;
        $referredId = (int) $newUser->id;

        // Prevent self-referral
        if ($referrerId === $referredId) {
            return false;
        }

        $referrerUser = User::find($referrerId);
        if (!$referrerUser) {
            return false;
        }

        // Check if already rewarded in wallet_history
        $alreadyRewarded = WalletHistory::where('user_id', $referrerId)
            ->where('referral_user_id', $referredId)
            ->where('transaction_type', 'referral')
            ->exists();

        if ($alreadyRewarded) {
            return false;
        }

        // Check if referred user has played a quiz (quiz_winners.total_question > 0)
        $firstQuizPlayed = QuizWinner::where('user_id', $referredId)
            ->where('total_question', '>', 0)
            ->orderBy('id', 'asc')
            ->first();

        if (!$firstQuizPlayed) {
            return false;
        }

        if ($isDryRun) {
            $log('info', "[DRY-RUN] Would reward Referrer ID {$referrerId} for User ID {$referredId} (Direct referral).");
            return true;
        }

        return DB::transaction(function () use ($newUser, $referrerUser, $firstQuizPlayed, $referrerId, $referredId, $log) {
            $lockedReferrer = User::where('id', $referrerId)->lockForUpdate()->first();
            if (!$lockedReferrer) {
                return false;
            }

            // Check if already rewarded in wallet_history inside transaction
            $alreadyRewarded = WalletHistory::where('user_id', $referrerId)
                ->where('referral_user_id', $referredId)
                ->where('transaction_type', 'referral')
                ->exists();

            if ($alreadyRewarded) {
                return false;
            }

            $oldUserSnapshot = $lockedReferrer->toArray();

            // Create record in referral_codes table
            $referralCodeRecord = ReferralCode::create([
                'tenant_id'              => $lockedReferrer->tenant_id ?? 1,
                'user_id'                => $referrerId,
                'referral_user_id'       => $referredId,
                'code'                   => $newUser->referral_code_to ?? $lockedReferrer->referral_code ?? 'DIRECT',
                'status'                 => 'active',
                'used'                   => 'used',
                'used_date'              => now(),
                'referral_point_provide' => 'yes',
                'points'                 => (int) $this->rewardAmount,
            ]);

            $oldBalance = (float) ($lockedReferrer->wallet_balance ?? 0);
            $newBalance = $oldBalance + $this->rewardAmount;

            // 1. Update Referrer's wallet balance
            $lockedReferrer->wallet_balance = $newBalance;
            $lockedReferrer->save();

            // 2. Insert into wallet_history
            $walletHistory = WalletHistory::create([
                'tenant_id'          => $lockedReferrer->tenant_id ?? 1,
                'user_id'            => $lockedReferrer->id,
                'quiz_id'            => null,
                'referral_user_id'   => $referredId,
                'survey_id'          => null,
                'old_wallet_balance' => $oldBalance,
                'amount'             => $this->rewardAmount,
                'new_wallet_balance' => $newBalance,
                'type'               => 'credit',
                'transaction_type'   => 'referral',
                'reference_id'       => (string) $referralCodeRecord->id,
                'remarks'            => "Referral bonus: User #{$referredId} (" . ($newUser->name ?? 'User') . ") completed first quiz",
            ]);

            // 3. ActionTrail Logging
            $this->logActionTrail(
                $lockedReferrer->tenant_id,
                'users',
                $lockedReferrer->id,
                'update wallet referral reward',
                $lockedReferrer->toArray(),
                $oldUserSnapshot,
                $firstQuizPlayed->quiz_id ?? null,
                $lockedReferrer->id
            );

            $this->logActionTrail(
                $lockedReferrer->tenant_id,
                'wallet_history',
                $walletHistory->id,
                'add wallet_history referral reward',
                $walletHistory->toArray(),
                null,
                $firstQuizPlayed->quiz_id ?? null,
                $lockedReferrer->id
            );

            $this->logActionTrail(
                $referralCodeRecord->tenant_id,
                'referral_codes',
                $referralCodeRecord->id,
                'add referral_codes reward completed',
                $referralCodeRecord->toArray(),
                null,
                $firstQuizPlayed->quiz_id ?? null,
                $lockedReferrer->id
            );

            Log::info('Direct Referral reward credited via referral_codes successfully', [
                'referral_code_id'   => $referralCodeRecord->id,
                'referrer_user_id'   => $lockedReferrer->id,
                'referred_user_id'   => $referredId,
                'quiz_id'            => $firstQuizPlayed->quiz_id ?? null,
                'reward_amount'      => $this->rewardAmount,
                'old_wallet_balance' => $oldBalance,
                'new_wallet_balance' => $newBalance,
            ]);

            $log('info', "Rewarded {$this->rewardAmount} coins to Referrer ID {$lockedReferrer->id} for User ID {$referredId}.");

            return true;
        });
    }

    /**
     * Log change to action_trails table.
     */
    public function logActionTrail(
        ?int $tenantId,
        string $tableName,
        $referenceId,
        string $action,
        $details = null,
        $oldDetails = null,
        ?int $quizId = null,
        ?int $userId = null
    ): void {
        try {
            ActionTrail::create([
                'tenant_id'          => $tenantId ?? 1,
                'quiz_id'            => $quizId,
                'user_id'            => $userId,
                'survey_id'          => null,
                'table_name'         => $tableName,
                'reference_id'       => (string) $referenceId,
                'action'             => $action,
                'updated_by'         => $userId ?? 1,
                'updated_by_role_id' => 3,
                'old_details'        => $oldDetails ? (is_array($oldDetails) ? $oldDetails : (array) $oldDetails) : null,
                'details'            => $details ? (is_array($details) ? $details : (array) $details) : null,
                'status'             => 'success',
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to record ActionTrail for {$tableName} (ID: {$referenceId}): " . $e->getMessage());
        }
    }
}
