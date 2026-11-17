<?php

namespace App\Interfaces\api;

interface PlanRepositoryInterface
{
    public function list(): array;
    public function applyCoupon(array $data): array;
    public function initiatePayment($user, int $planId, array $data): array;
    public function paymentCallback(array $data): array;
    public function mySubscription($user): array;
    public function billingHistory($user): array;
    public function cancelSubscription($user): array;
    public function createCoupon(array $data): array;
}
