<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\api\CmsRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CmsController extends Controller
{
    protected CmsRepositoryInterface $cmsRepository;

    public function __construct(CmsRepositoryInterface $cmsRepository)
    {
        $this->cmsRepository = $cmsRepository;
    }

    /** GET /api/cms/{slug} */
    public function pageBySlug(string $slug)
    {
        return response()->json($this->cmsRepository->pageBySlug($slug));
    }

    /** POST /api/admin/cms/create (also handles update via slug upsert) */
    public function createOrUpdatePage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:191',
            'slug'              => 'required|string|max:191',
            'content'           => 'required|string',
            'meta_title'        => 'nullable|string|max:191',
            'meta_description'  => 'nullable|string|max:191',
            'status'            => 'nullable|in:draft,published',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->cmsRepository->createOrUpdatePage($validator->validated()));
    }

    /** POST /api/admin/campaign/create */
    public function createCampaign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:191',
            'channels'       => 'required|array',
            'segment'        => 'nullable|string|max:191',
            'schedule_type'  => 'required|in:now,later',
            'scheduled_at'   => 'nullable|date',
            'title'          => 'required|string|max:191',
            'message'        => 'required|string',
            'deep_link'      => 'nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data           = $validator->validated();
        $data['status'] = 'scheduled';

        return response()->json($this->cmsRepository->createCampaign($data));
    }

    /** POST /api/admin/campaign/{id}/send */
    public function sendCampaign(int $id)
    {
        return response()->json($this->cmsRepository->sendCampaign($id));
    }

    /** POST /api/enquiry/submit-step */
    public function submitEnquiryStep(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'          => 'required|digits_between:10,15',
            'current_step'   => 'required|integer',
            'business_name'  => 'nullable|string|max:191',
            'email'          => 'nullable|email|max:191',
            'business_type'  => 'nullable|string|max:191',
            'state'          => 'nullable|string|max:191',
            'city'           => 'nullable|string|max:191',
            'address'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->cmsRepository->submitEnquiryStep($validator->validated()));
    }

    /** POST /api/enquiry/verify-otp */
    public function verifyEnquiryOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits_between:10,15',
            'otp'   => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->cmsRepository->verifyEnquiryOtp($validator->validated()));
    }

    /** GET /api/admin/enquiries */
    public function enquiryList(Request $request)
    {
        return response()->json($this->cmsRepository->enquiryList($request->only(['inquiry_status'])));
    }

    /** POST /api/report */
    public function reportContent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_type'        => 'required|string|max:50',
            'item_id'          => 'required|integer',
            'reason'           => 'required|string|max:191',
            'reporter_comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json(
            $this->cmsRepository->reportContent($request->user(), $validator->validated())
        );
    }

    /** GET /api/admin/moderation-reports */
    public function moderationList(Request $request)
    {
        return response()->json($this->cmsRepository->moderationList($request->only(['status'])));
    }

    /** POST /api/admin/moderation-reports/{id}/action */
    public function moderationAction(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'decision'       => 'required|in:approve,edit_republish,remove,dismiss',
            'notify_creator' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->cmsRepository->moderationAction($id, $validator->validated()));
    }

    /** GET /api/admin/whatsapp-templates */
    public function whatsappTemplates()
    {
        return response()->json($this->cmsRepository->whatsappTemplates());
    }

    /** POST /api/admin/whatsapp-templates */
    public function saveWhatsappTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_name'  => 'required|string|max:191',
            'language_code'  => 'required|string|max:10',
            'data'           => 'nullable|array',
            'status'         => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->cmsRepository->saveWhatsappTemplate($validator->validated()));
    }

    /** GET /api/admin/settings */
    public function settingsList()
    {
        return response()->json($this->cmsRepository->settingsList());
    }

    /** POST /api/admin/settings */
    public function saveSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key'   => 'required|string|max:191',
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return response()->json($this->cmsRepository->saveSetting($validator->validated()));
    }
}
