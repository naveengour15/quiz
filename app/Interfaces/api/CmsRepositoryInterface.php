<?php

namespace App\Interfaces\api;

interface CmsRepositoryInterface
{
    // CMS
    public function pageBySlug(string $slug): array;
    public function createOrUpdatePage(array $data): array;

    // Campaign
    public function createCampaign(array $data): array;
    public function sendCampaign(int $campaignId): array;

    // Web Enquiry
    public function submitEnquiryStep(array $data): array;
    public function verifyEnquiryOtp(array $data): array;
    public function enquiryList(array $filters): array;

    // Moderation
    public function reportContent($user, array $data): array;
    public function moderationList(array $filters): array;
    public function moderationAction(int $reportId, array $data): array;

    // WhatsApp templates & Settings
    public function whatsappTemplates(): array;
    public function saveWhatsappTemplate(array $data): array;
    public function settingsList(): array;
    public function saveSetting(array $data): array;
}
