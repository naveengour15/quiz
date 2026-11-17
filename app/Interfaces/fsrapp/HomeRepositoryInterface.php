<?php

namespace App\Interfaces\fsrapp;

use App\Models\User;

interface HomeRepositoryInterface
{
    /**
     * Clear active quiz cache and session variables.
     */
    public function clearActiveQuizSessionAndCache(): void;

    /**
     * Get all aggregated data required for the Home screen view.
     */
    public function getHomeData(?User $user): array;

    /**
     * Get user account statistics (wallet balance, rank, streak, total wins, leaderboard).
     */
    public function getUserAccountStats(?User $user): array;

    /**
     * Get cached active banners for tenant.
     */
    public function getBanners(int $tenantId);

    /**
     * Get today's scheduled or approved quizzes.
     */
    public function getTodayQuizzes(int $tenantId);

    /**
     * Get mega tournaments for leaderboard quizzes.
     */
    public function getMegaTournaments(int $tenantId);

    /**
     * Get active categories with quizzes count.
     */
    public function getCategories(int $tenantId);

    /**
     * Get practice quizzes with sequential unlock status.
     */
    public function getPracticeQuizzes(int $tenantId, ?User $user);

    /**
     * Get active surveys.
     */
    public function getSurveys(int $tenantId);

    /**
     * Get top players from leaderboard podium.
     */
    public function getTopPlayers(int $tenantId);

    /**
     * Get user survey stats and joined/notified quiz IDs.
     */
    public function getUserEngagementData(?User $user, int $tenantId, array $allQuizIds = []): array;

    /**
     * Build indexed quizzes lookup array for Blade view.
     */
    public function buildQuizzesArray(array $joinedQuizIds, ...$quizLists): array;

    /**
     * Get user recent winners activity and completed quiz history.
     */
    public function getUserRecentActivity(?User $user): array;

    /**
     * Get active targeted announcements for user.
     */
    public function getUserAnnouncements(?User $user, int $tenantId);

    /**
     * Get targeted app update popup data for user.
     */
    public function getUserAppUpdation(?User $user, int $tenantId);

    /**
     * Automatically process pending app updates by clearing quiz cache/session and acknowledging history.
     */
    public function processAutomaticAppUpdation(?User $user, int $tenantId);

    /**
     * Get real-time last completed quiz data with winners.
     */
    public function getLastQuizData(int $tenantId): array;

    /**
     * Get top quiz players ranked by correct answer count.
     */
    public function getTopQuizPlayers(int $tenantId): array;

    /**
     * Close/acknowledge an announcement for the user.
     */
    public function closeAnnouncement(int $userId, int $tenantId, int $announcementId): array;

    /**
     * Close/acknowledge an app updation popup for the user.
     */
    public function closeAppUpdation(int $userId, int $tenantId, int $appUpdationId): array;

    /**
     * Record banner click and return redirect URL.
     */
    public function handleBannerClick(int $bannerId, string $type): ?string;

    /**
     * Get the status of the last completed or processing quiz.
     */
    public function getLastQuizStatus(int $tenantId, ?int $targetQuizId = null): array;

    /**
     * Check if user has subscribed to WhatsApp channel.
     */
    public function isWhatsappSubscribed(?User $user): bool;

    /**
     * Subscribe user to WhatsApp channel.
     */
    public function subscribeWhatsappChannel(int $userId, int $tenantId): array;
}
