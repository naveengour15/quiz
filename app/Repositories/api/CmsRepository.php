<?php

namespace App\Repositories\api;

use App\Interfaces\api\CmsRepositoryInterface;
use App\Models\Campaign;
use App\Models\CmsPage;
use App\Models\ModerationReport;
use App\Models\Setting;
use App\Models\WebEnquiry;
use App\Models\WhatsAppTemplateSetting;
use Illuminate\Support\Str;

class CmsRepository implements CmsRepositoryInterface
{
    // ---------------------------------------------------------------
    // CMS
    // ---------------------------------------------------------------
    public function pageBySlug(string $slug): array
    {
        $page = CmsPage::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $page) {
            $page = CmsPage::withoutGlobalScope('tenant')
                ->where('slug', $slug)
                ->first();
        }

        if (! $page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        return ['success' => true, 'page' => $page];
    }

    public function createOrUpdatePage(array $data): array
    {
        $page = CmsPage::updateOrCreate(['slug' => $data['slug']], $data);

        return ['success' => true, 'message' => 'CMS page saved successfully', 'page' => $page];
    }

    // ---------------------------------------------------------------
    // Campaign
    // ---------------------------------------------------------------
    public function createCampaign(array $data): array
    {
        $campaign = Campaign::create($data);

        return ['success' => true, 'message' => 'Campaign created successfully', 'campaign' => $campaign];
    }

    public function sendCampaign(int $campaignId): array
    {
        $campaign = Campaign::find($campaignId);

        if (! $campaign) {
            return ['success' => false, 'message' => 'Campaign not found'];
        }

        // NOTE: dispatch a job here that pushes to SMS/Push/Email providers.
        $campaign->sent_at = now();
        $campaign->status  = 'sent';
        $campaign->save();

        return ['success' => true, 'message' => 'Campaign sent successfully', 'campaign' => $campaign];
    }

    // ---------------------------------------------------------------
    // Web Enquiry
    // ---------------------------------------------------------------
    public function submitEnquiryStep(array $data): array
    {
        $enquiry = WebEnquiry::firstOrNew(['phone' => $data['phone']]);
        $enquiry->fill($data);

        if (! $enquiry->exists) {
            $enquiry->otp             = (string) random_int(1000, 9999);
            $enquiry->otp_verify      = 0;
            $enquiry->inquiry_status  = 'in_progress';
        }

        $enquiry->save();

        // TODO: send OTP via SMS gateway when current_step indicates verification stage.

        return ['success' => true, 'message' => 'Enquiry step saved successfully', 'enquiry' => $enquiry];
    }

    public function verifyEnquiryOtp(array $data): array
    {
        $enquiry = WebEnquiry::where('phone', $data['phone'])->first();

        if (! $enquiry || $enquiry->otp !== $data['otp']) {
            return ['success' => false, 'message' => 'Invalid OTP'];
        }

        $enquiry->otp_verify = 1;
        $enquiry->save();

        return ['success' => true, 'message' => 'OTP verified successfully'];
    }

    public function enquiryList(array $filters): array
    {
        $query = WebEnquiry::query();

        if (! empty($filters['inquiry_status'])) {
            $query->where('inquiry_status', $filters['inquiry_status']);
        }

        return ['success' => true, 'data' => $query->latest('id')->paginate(20)];
    }

    // ---------------------------------------------------------------
    // Moderation
    // ---------------------------------------------------------------
    public function reportContent($user, array $data): array
    {
        $report = ModerationReport::create([
            'tenant_id'        => $user->tenant_id ?? 1,
            'item_type'        => $data['item_type'],
            'item_id'          => $data['item_id'],
            'reason'           => $data['reason'],
            'reporter_comment' => $data['reporter_comment'] ?? null,
            'flagged_count'    => 1,
            'status'           => 'pending',
        ]);

        return ['success' => true, 'message' => 'Content reported successfully', 'report' => $report];
    }

    public function moderationList(array $filters): array
    {
        $query = ModerationReport::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return ['success' => true, 'data' => $query->latest('id')->paginate(20)];
    }

    public function moderationAction(int $reportId, array $data): array
    {
        $report = ModerationReport::find($reportId);

        if (! $report) {
            return ['success' => false, 'message' => 'Report not found'];
        }

        $report->decision       = $data['decision'];
        $report->status         = 'resolved';
        $report->notify_creator = $data['notify_creator'] ?? 0;
        $report->save();

        return ['success' => true, 'message' => 'Moderation action applied', 'report' => $report];
    }

    // ---------------------------------------------------------------
    // WhatsApp templates & Settings
    // ---------------------------------------------------------------
    public function whatsappTemplates(): array
    {
        return ['success' => true, 'data' => WhatsAppTemplateSetting::all()];
    }

    public function saveWhatsappTemplate(array $data): array
    {
        $template = WhatsAppTemplateSetting::updateOrCreate(
            ['template_name' => $data['template_name']],
            $data
        );

        return ['success' => true, 'message' => 'Template saved successfully', 'template' => $template];
    }

    public function settingsList(): array
    {
        return ['success' => true, 'data' => Setting::all()->pluck('value', 'key')];
    }

    public function saveSetting(array $data): array
    {
        $setting = Setting::updateOrCreate(
            ['key' => $data['key']],
            ['value' => $data['value']]
        );

        return ['success' => true, 'message' => 'Setting saved successfully', 'setting' => $setting];
    }
}
