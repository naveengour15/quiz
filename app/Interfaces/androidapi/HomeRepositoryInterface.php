<?php

namespace App\Interfaces\androidapi;

use App\Models\User;

interface HomeRepositoryInterface
{
    /**
     * Get aggregated home screen data for Android App
     */
    public function getHomeData(?User $user): array;

    /**
     * Close announcement for specified user
     */
    public function closeAnnouncement(int $userId, int $announcementId): array;

    /**
     * Close app updation for specified user
     */
    public function closeAppUpdation(int $userId, int $appUpdationId): array;

    /**
     * Subscribe user to WhatsApp channel
     */
    public function subscribeWhatsappChannel(int $userId, int $tenantId): array;

    /**
     * Get live countdown start times and statuses for quizzes
     */
    public function getQuizTimes(array $quizIds, int $tenantId): array;

    /**
     * Get last quiz completion status
     */
    public function getLastQuizStatus(int $tenantId, ?int $quizId = null): array;

    /**
     * Handle banner click recording and resolve target redirect URL
     */
    public function handleBannerClick(int $bannerId, string $type, ?\App\Models\User $user = null): ?string;

    /**
     * Record banner view when visible
     */
    public function recordBannerView(int $bannerId, string $type, ?\App\Models\User $user = null): bool;
}
