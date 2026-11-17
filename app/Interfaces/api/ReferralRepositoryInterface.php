<?php

namespace App\Interfaces\api;

interface ReferralRepositoryInterface
{
    public function myCode($user): array;
    public function applyCode($user, string $code): array;
    public function myReferrals($user): array;
    public function settle(int $referralId, array $data): array;
}
