<?php

namespace App\Interfaces\api;

interface RewardRepositoryInterface
{
    public function catalog(): array;
    public function redeem($user, array $data): array;
    public function myRedemptions($user): array;
    public function walletBalance($user): array;
    public function walletHistory($user): array;
    public function actionOnRedemption(int $redemptionId, array $data): array;
}
