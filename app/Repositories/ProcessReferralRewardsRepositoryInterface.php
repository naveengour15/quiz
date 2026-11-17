<?php

namespace App\Repositories;

use App\Models\ReferralCode;
use App\Models\User;

interface ProcessReferralRewardsRepositoryInterface
{
    /**
     * Process all eligible referral rewards.
     *
     * @param bool $isDryRun
     * @param \Closure|null $outputCallback
     * @return array
     */
    public function processRewards(bool $isDryRun = false, ?\Closure $outputCallback = null): array;

    /**
     * Process a single referral record from the referral_codes table.
     *
     * @param ReferralCode $referralRecord
     * @param bool $isDryRun
     * @param \Closure|null $outputCallback
     * @return bool
     */
    public function processReferralCodeRecord(ReferralCode $referralRecord, bool $isDryRun = false, ?\Closure $outputCallback = null): bool;

    /**
     * Process referral for a user who has referral_user_id set on their profile but no referral_codes table record.
     *
     * @param User $newUser
     * @param bool $isDryRun
     * @param \Closure|null $outputCallback
     * @return bool
     */
    public function processUserDirectReferral(User $newUser, bool $isDryRun = false, ?\Closure $outputCallback = null): bool;

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
    ): void;
}
