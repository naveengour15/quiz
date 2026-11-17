<?php

namespace App\Interfaces\api;

interface RedeemRequestRepositoryInterface
{
    public function createRedeemRequest($user, array $data): array;

    public function getRedeemRequests($user, array $filters = []): array;
}