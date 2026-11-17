<?php

namespace App\Interfaces\fsrapp;

interface EGiftvoucherRepositoryInterface
{
    /**
     * Get all active and available e-gift vouchers
     *
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableVouchers(int $tenantId = 1);

    /**
     * Get user's e-gift voucher requests
     *
     * @param int $userId
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserVoucherRequests(int $userId, int $tenantId = 1);

    /**
     * Create a new voucher redeem request for a user
     *
     * @param int $userId
     * @param int $voucherId
     * @param int $tenantId
     * @return array
     */
    public function createVoucherRequest(int $userId, int $voucherId, int $tenantId = 1);
}
