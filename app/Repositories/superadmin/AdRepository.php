<?php

namespace App\Repositories\superadmin;
use App\Models\Quiz;
use App\Models\ActionTrail;
use App\Models\Ad;
use App\Models\City;
use App\Models\State;
use App\Models\Tenant;
use App\Models\AdMedia;
use App\Models\AdViewHistory;
use App\Models\AdClickHistory;
use App\Models\AdAnalytic;
use App\Models\Banner;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;


class AdRepository implements AdRepositoryInterface
{

    private function validateMediaDimensions(Request $request)
    {
        $images = [
            'banner' => [320, 50],
            'large_banner' => [320, 100],
            'medium_banner' => [300, 250],
            'full_screen' => [1080, 1920],
        ];

        foreach ($images as $field => $size) {

            if ($request->hasFile($field)) {

                [$width, $height] = getimagesize($request->file($field)->getRealPath());

                if ($width != $size[0] || $height != $size[1]) {

                    return back()
                        ->withErrors([
                            $field => "Image size must be {$size[0]} x {$size[1]} pixels."
                        ])
                        ->withInput();

                }
            }
        }
    }
    

    private function writeActionTrail(
        $ad,
        string $action,
        array $details = [],
        array $oldDetails = [],
        string $status = 'success'
    ): void {

        ActionTrail::create([
            'tenant_id' => $ad->tenant_id ?? null,
            'table_name' => 'ads',
            'reference_id' => $ad->id,
            'action' => $action,
            'updated_by' => auth()->id(),
            'updated_by_role_id' => auth()->user()?->role_id ?? null,
            'old_details' => $oldDetails,
            'details' => $details,
            'status' => $status,
        ]);
    }

    public function index($request)
    {
        $editing = null;

        $ads = Ad::with(['user', 'stateData', 'cityData', 'mediaFile'])
            ->latest()
            ->paginate(10);
           

        if ($request->filled('edit')) {
            $editing = Ad::with(['stateData', 'cityData', 'mediaFile'])
                ->findOrFail($request->edit);
                //   dd($editing->media);die;
        }
       //dd($editing);die;
        $tenants = Tenant::orderBy('name')->get();

        $states = State::where('status', 'active')
            ->orderBy('name')
            ->get();

        $bannerPerPage = max(1, (int) $request->input('per_page', 10));

        $banners = Banner::with([
            'tenant',
            'user'
        ])
        ->withCount([
            'viewHistories as total_views',
            'clickHistories as total_clicks',

            'viewHistories as unique_views' => function ($query) {
                $query->where('is_unique', true);
            },
        ])
        ->latest()
        ->paginate($bannerPerPage, ['*'], 'banner_page')
        ->appends(['section' => 'banners']);

        return compact(
            'ads',
            'editing',
            'tenants',
            'states',
            'banners'
    
        );
    }


    public function store($request)
    {
        DB::beginTransaction();

        try {

            $data = $this->validation($request);

            $tenantId = $request->tenant_id ?? getSuperAdminTenantId();

            $tenant = Tenant::find($tenantId);

            $data['tenant_id'] = $tenant?->id ?? getSuperAdminTenantId();
            // $data['user_id'] = $tenant?->user_id ?? getSuperAdminTenantId();

            
            $data['interests'] = $request->filled('interests')
                ? implode(', ', array_map('trim', explode(',', $request->interests)))
                : null;

            $data['hobby'] = $request->filled('hobby')
                ? implode(', ', array_map('trim', explode(',', $request->hobby)))
                : null;


                if ($request->filled('age_range')) {

                    $age = trim($request->age_range);

                    if (str_contains($age, '-')) {

                        [$from, $to] = preg_split('/\s*-\s*/', $age);

                        $data['age_from'] = (int) $from;
                        $data['age_to']   = (int) $to;

                    } else {

                        $data['age_from'] = (int) $age;
                        $data['age_to']   = (int) $age;
                    }

                } else {

                    $data['age_from'] = null;
                    $data['age_to']   = null;
                }

            if ($response = $this->validateMediaDimensions($request)) {
                return $response;
            }

            $ad = Ad::create($data);

            $media = new AdMedia();
            $media->tenant_id = $ad->tenant_id;
            $media->ad_id = $ad->id;

            $imageFields = [
                    'banner',
                    'large_banner',
                    'medium_banner',
                    'full_screen',
                ];

                foreach ($imageFields as $field) {

                    if ($request->hasFile($field)) {

                        $file = $request->file($field);

                        $fileName = time().'_'.$field.'.'.$file->getClientOriginalExtension();

                        $path = "ads/{$field}/".$fileName;

                        Storage::disk('s3')->put(
                            $path,
                            file_get_contents($file),
                            'public'
                        );

                        $media->$field = $path;
                    }
                }


                $videoFields = [
                    'video_portrait',
                    'video_landscape',
                ];

                foreach ($videoFields as $field) {

                    if ($request->hasFile($field)) {

                        $file = $request->file($field);

                        $fileName = time().'_'.$field.'.'.$file->getClientOriginalExtension();

                        $path = "ads/{$field}/".$fileName;

                        Storage::disk('s3')->put(
                            $path,
                            file_get_contents($file),
                            'public'
                        );

                        $media->$field = $path;
                    }
                }

            $media->save();

            $this->writeActionTrail(
                $ad,
                'create',
                $data,
                [],
                'success'
            );

            DB::commit();

            return redirect()
                ->route('superadmin.ad.index')
                ->with('success', 'Ad created successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withErrors([
                    'error' => $e->getMessage(),
                ])
                ->withInput();
        }
    }

   
    public function update($request, $id)
    {
        DB::beginTransaction();

        try {

            $ad = Ad::findOrFail($id);

            $data = $this->validation($request);

            $tenantId = $request->tenant_id ?? getSuperAdminTenantId();

            $tenant = Tenant::find($tenantId);

            $data['tenant_id'] = $tenant?->id ?? getSuperAdminTenantId();
            // $data['user_id'] = $tenant?->user_id ?? getSuperAdminTenantId();

            

            $data['interests'] = $request->filled('interests')
            ? implode(', ', array_map('trim', explode(',', $request->interests)))
            : null;

            $data['hobby'] = $request->filled('hobby')
            ? implode(', ', array_map('trim', explode(',', $request->hobby)))
            : null;

            if ($request->filled('age_range')) {

                $age = trim($request->age_range);

                if (str_contains($age, '-')) {

                    [$from, $to] = preg_split('/\s*-\s*/', $age);

                    $data['age_from'] = (int) $from;
                    $data['age_to']   = (int) $to;

                } else {

                    $data['age_from'] = (int) $age;
                    $data['age_to']   = (int) $age;
                }

            } else {

                $data['age_from'] = null;
                $data['age_to']   = null;
            }

            

            if ($response = $this->validateMediaDimensions($request)) {
                return $response;
            }

            $oldDetails = $ad->getAttributes();

            $ad->update($data);

            $media = AdMedia::firstOrNew([
                'ad_id' => $ad->id,
            ]);

            $media->tenant_id = $ad->tenant_id;

            $imageFields = [
                'banner',
                'large_banner',
                'medium_banner',
                'full_screen',
            ];

            foreach ($imageFields as $field) {

                if ($request->hasFile($field)) {

                    if (!empty($media->$field)) {
                        Storage::disk('s3')->delete($media->$field);
                    }

                    $file = $request->file($field);

                    $fileName = time().'_'.$field.'.'.$file->getClientOriginalExtension();

                    $path = "ads/{$field}/".$fileName;

                    Storage::disk('s3')->put(
                        $path,
                        file_get_contents($file),
                        'public'
                    );

                    $media->$field = $path;
                }
            }

            $videoFields = [
                'video_portrait',
                'video_landscape',
            ];

            foreach ($videoFields as $field) {

                if ($request->hasFile($field)) {

                    if (!empty($media->$field)) {
                        Storage::disk('s3')->delete($media->$field);
                    }

                    $file = $request->file($field);

                    $fileName = time().'_'.$field.'.'.$file->getClientOriginalExtension();

                    $path = "ads/{$field}/".$fileName;

                    Storage::disk('s3')->put(
                        $path,
                        file_get_contents($file),
                        'public'
                    );

                    $media->$field = $path;
                }
            }

            $media->save();

            $this->writeActionTrail(
                $ad,
                'update',
                $data,
                $oldDetails,
                'success'
            );

            DB::commit();

            return redirect()
                ->route('superadmin.ad.index')
                ->with('success', 'Ad updated successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withErrors([
                    'error' => $e->getMessage(),
                ])
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $ad = Ad::findOrFail($id);

        $media = AdMedia::where('ad_id', $id)->first();

        $oldDetails = $ad->getAttributes();

        $this->writeActionTrail(
            $ad,
            'delete',
            [],
            $oldDetails,
            'success'
        );

        if ($media) {

            $files = [
                'banner',
                'large_banner',
                'medium_banner',
                'full_screen',
                'video_portrait',
                'video_landscape',
            ];

            foreach ($files as $file) {

                if (!empty($media->$file)) {
                    Storage::disk('s3')->delete($media->$file);
                }
            }

            $media->delete();   // <-- yahi delete hoga
        }

        $ad->delete();

        return back()->with('success', 'Ad deleted successfully.');
    }

    public function show($id)
    {

        $ad = Ad::with([ 'mediaFile','tenant', 'stateData', 'cityData'])->findOrFail($id);

        $media = $ad->mediaFile;

        if ($media) {

            $media->banner_url = $media->banner
                ? Storage::disk('s3')->url($media->banner)
                : null;

            $media->large_banner_url = $media->large_banner
                ? Storage::disk('s3')->url($media->large_banner)
                : null;

            $media->medium_banner_url = $media->medium_banner
                ? Storage::disk('s3')->url($media->medium_banner)
                : null;

            $media->full_screen_url = $media->full_screen
                ? Storage::disk('s3')->url($media->full_screen)
                : null;

            $media->video_portrait_url = $media->video_portrait
                ? Storage::disk('s3')->url($media->video_portrait)
                : null;

            $media->video_landscape_url = $media->video_landscape
                ? Storage::disk('s3')->url($media->video_landscape)
                : null;
        }

        return response()->json([
            'success' => true,
            'data' => $ad,
        ]);
    }

    public function changeStatus($id, $request)
    {
        $oldDetails = $id->getAttributes();
        $id->status = $request->status;
        $id->save();
        $this->writeActionTrail(
            $id,
            'status',
            ['status' => $id->status],
            $oldDetails,
            'success'
        );

        return response()->json([
            'success' => true,
            'status' => $id->status,
        ]);
    }

    public function getCities($stateId)
    {
        return City::where('state_id', $stateId)
            ->orderBy('name')
            ->get();
    }

    private function validation(Request $request)
    {
        return $request->validate([
            'campaign_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ad_type' => 'required|in:image,video',
            // 'media' => $request->isMethod('post')
            //                     ? 'required|image'
            //                     : 'nullable|image',
            'budget' => 'required|numeric',
            'state' => 'nullable',
            'city' => 'nullable',
            // 'age_from'       => 'nullable|integer',
            // 'age_to'         => 'nullable|integer',
            'age_range' => [
                'nullable',
                 'regex:/^\d{1,2}(\s*-\s*\d{1,2})?$/',
            ],
            'gender' => 'required|in:all,male,female',
           'language' => 'nullable|in:Hindi,English',
            'status' => 'required|in:pending,draft,active,paused,completed,rejected',
            'interests' => 'nullable|string',
            'hobby' => 'nullable|string',

            'banner'            => $request->isMethod('post') ? 'nullable|image|mimes:jpg,jpeg,png,webp' : 'nullable|image|mimes:jpg,jpeg,png,webp',
            'large_banner'      => $request->isMethod('post') ? 'nullable|image|mimes:jpg,jpeg,png,webp' : 'nullable|image|mimes:jpg,jpeg,png,webp',

            'medium_banner'     => $request->isMethod('post') ? 'nullable|image|mimes:jpg,jpeg,png,webp' : 'nullable|image|mimes:jpg,jpeg,png,webp',

            'full_screen'       => $request->isMethod('post') ? 'nullable|image|mimes:jpg,jpeg,png,webp' : 'nullable|image|mimes:jpg,jpeg,png,webp',

            'video_portrait'    => $request->isMethod('post') ? 'nullable|mimes:mp4,mov,avi' : 'nullable|mimes:mp4,mov,avi',

            'video_landscape'   => $request->isMethod('post') ? 'nullable|mimes:mp4,mov,avi' : 'nullable|mimes:mp4,mov,avi',

        ]);
    }

    public function getCitiesByState(int $stateId): Collection
    {
        return City::where('state_id', $stateId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /*public function assignQuiz(Ad $ad)
    {
        $quizzes = Quiz::latest()->get();

        $selectedQuizIds = $ad->quiz_id ?? [];

        return [
            'ad' => $ad,
            'quizzes' => $quizzes,
            'selectedQuizIds' => is_array($selectedQuizIds)
                ? $selectedQuizIds
                : json_decode($selectedQuizIds, true),
        ];
    }*/

        public function assignQuiz(Ad $ad)
        {
            $quizzes = Quiz::query()
                ->with('quizType')
                ->latest()
                ->get();

            $selectedQuizIds = $ad->quiz_id ?? [];

            $selectedQuizIds = array_map(
                'intval',
                (array) $selectedQuizIds
            );

            $selectedQuizTotalPlayers = $quizzes
                ->whereIn('id', $selectedQuizIds)
                ->sum(function ($quiz) {
                    return (int) ($quiz->no_of_players ?? 0);
                });

            return [
                'ad' => $ad,
                'quizzes' => $quizzes,
                'selectedQuizIds' => $selectedQuizIds,
                'selectedQuizTotalPlayers' => $selectedQuizTotalPlayers,
            ];
        }
            

    /*public function saveAssignedQuizzes($request, Ad $ad)
    {
        $request->validate([
            'quiz_ids' => 'nullable|array',
            'quiz_ids.*' => 'integer|exists:quizzes,id',
        ]);

        DB::beginTransaction();

        try {
            $oldDetails = $ad->getAttributes();
            $quizIds = $request->input('quiz_ids', []);

            /* * Remove duplicate IDs*
            $quizIds = array_values(array_unique(array_map(
                'intval',
                $quizIds
            )));

            /** Save JSON through model cast*
            $ad->quiz_id = $quizIds;
            $ad->save();

            $this->writeActionTrail(
                $ad,
                'assign_quiz',
                [
                    'quiz_ids' => $quizIds,
                ],
                $oldDetails,
                'success'
            );

            DB::commit();

            return redirect()->route('superadmin.ad.assign.quiz', $ad->id)->with('success', 'Quiz assigned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([ 'error' => $e->getMessage(),])->withInput();
        }
    }*/


    public function saveAssignedQuizzes($request, Ad $ad)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'selected' => 'required|boolean',
        ]);

        DB::beginTransaction();

        try {

            $oldDetails = $ad->getAttributes();

            // Existing quiz IDs
            $quizIds = $ad->quiz_id ?? [];

            $quizIds = array_map(
                'intval',
                (array) $quizIds
            );

            $quizId = (int) $request->quiz_id;

            // SELECT
            if ($request->boolean('selected')) {

                if (!in_array($quizId, $quizIds)) {
                    $quizIds[] = $quizId;
                }

            } 
            // UNSELECT
            else {

                $quizIds = array_values(
                    array_filter(
                        $quizIds,
                        fn ($id) => $id !== $quizId
                    )
                );
            }

            // Remove duplicate IDs
            $quizIds = array_values(
                array_unique($quizIds)
            );

            // Save
            $ad->quiz_id = $quizIds;
            $ad->save();

            $this->writeActionTrail(
                $ad,
                'assign_quiz',
                [
                    'quiz_ids' => $quizIds,
                ],
                $oldDetails,
                'success'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'quiz_ids' => $quizIds,
                'message' => 'Quiz selection updated successfully.',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


     public function analysis($id)
    {
        $tenantId = getSuperAdminTenantId();

        $ad = Ad::query()->where('id', $id)->where('tenant_id', $tenantId)->with([
                'user:id,name',
            ])->first();

        if (!$ad) {
            return response()->json([
                'success' => false,
                'message' => 'Ad not found.',
            ], 404);
        }

        $analytics = AdAnalytic::query()->where('ad_id', $id)->where('tenant_id', $tenantId)->first();

        $viewHistory = AdViewHistory::query()->where('ad_id', $id)->where('tenant_id', $tenantId)->with([
                'viewer:id,name',
            ])->latest('id')
            ->get([
                'id',
                'viewer_id',
                'ip_address',
                'device',
                'browser',
                'os',
                'country',
                'state',
                'city',
                'referrer',
                'watch_time',
                'is_unique',
                'created_at',
            ])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'viewer_id' => $item->viewer_id,
                    'user_name' => $item->viewer
                        ? $item->viewer->name
                        : '-',

                    'ip_address' => $item->ip_address ?? '-',
                    'device' => $item->device ?? '-',
                    'browser' => $item->browser ?? '-',
                    'os' => $item->os ?? '-',
                    'country' => $item->country ?? '-',
                    'state' => $item->state ?? '-',
                    'city' => $item->city ?? '-',
                    'referrer' => $item->referrer ?? '-',
                    'watch_time' => $item->watch_time ?? '-',
                    'is_unique' => $item->is_unique ? 'Yes' : 'No',
                    'created_at' => $item->created_at
                        ? $item->created_at->format('d M Y, h:i A')
                        : '-',
                ];
            });


        $clickHistory = AdClickHistory::query()->where('ad_id', $id)->where('tenant_id', $tenantId)->with([
                'user:id,name',
            ])->latest('id')
            ->get([
                'id',
                'user_id',
                'ip_address',
                'device',
                'browser',
                'os',
                'country',
                'state',
                'city',
                'destination_url',
                'clicked_at',
                'created_at',
            ])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user
                        ? $item->user->name
                        : '-',
                    'ip_address' => $item->ip_address ?? '-',
                    'device' => $item->device ?? '-',
                    'browser' => $item->browser ?? '-',
                    'os' => $item->os ?? '-',
                    'country' => $item->country ?? '-',
                    'state' => $item->state ?? '-',
                    'city' => $item->city ?? '-',
                    'destination_url' => $item->destination_url ?? '-',
                    'clicked_at' => $item->clicked_at
                        ? $item->clicked_at->format('d M Y, h:i A')
                        : (
                            $item->created_at
                                ? $item->created_at->format('d M Y, h:i A')
                                : '-'
                        ),
                ];
            });


        $summary = [
            'impressions' => $analytics
                ? ($analytics->impressions ?? 0)
                : 0,

            'views' => $analytics
                ? ($analytics->views ?? 0)
                : 0,

            'unique_views' => $analytics
                ? ($analytics->unique_views ?? 0)
                : 0,

            'clicks' => $analytics
                ? ($analytics->clicks ?? 0)
                : 0,

            'favorites' => $analytics
                ? ($analytics->favorites ?? 0)
                : 0,

            'shares' => $analytics
                ? ($analytics->shares ?? 0)
                : 0,

            'conversions' => $analytics
                ? ($analytics->conversions ?? 0)
                : 0,

            'ctr' => $analytics
                ? ($analytics->ctr ?? 0)
                : 0,

            'spent_amount' => $analytics
                ? ($analytics->spent_amount ?? 0)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'ad' => [
                'id' => $ad->id,
                'campaign_name'=> $ad->campaign_name,
                'title' => $ad->title ?? '-',
                'user_id' => $ad->user_id,
                'user_name' => $ad->user
                    ? $ad->user->name
                    : '-',
            ],

            'summary' => $summary,
            'view_history' => $viewHistory,
            'click_history' => $clickHistory,
        ]);
    }

    public function bannerAnalysis($id)
    {
        $tenantId = getSuperAdminTenantId();
        $perPage = max(1, (int) request('per_page', 10));
        $paginationQuery = request()->except(['view_page', 'click_page', 'page', 'tab']);

        $banner = Banner::with(['tenant'])
            ->where('id', $id)
            ->first();

        if (!$banner) {
            abort(404, 'Banner not found.');
        }

        $totalViews = AdViewHistory::query()
            ->where('banner_id', $id)
            ->where('tenant_id', $tenantId)
            ->count();

        $totalClicks = AdClickHistory::query()
            ->where('banner_id', $id)
            ->where('tenant_id', $tenantId)
            ->count();

        $viewHistory = AdViewHistory::query()
            ->where('banner_id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['viewer:id,name'])
            ->latest('id')
            ->paginate($perPage, [
                'id',
                'viewer_id',
                'page_type',
                'ip_address',
                'device',
                'browser',
                'os',
                'country',
                'state',
                'city',
                'referrer',
                'is_unique',
                'viewed_at',
                'created_at',
            ], 'view_page')
            ->appends(array_merge($paginationQuery, ['tab' => 'views']));

        $clickHistory = AdClickHistory::query()
            ->where('banner_id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['user:id,name'])
            ->latest('id')
            ->paginate($perPage, [
                'id',
                'user_id',
                'page_type',
                'ip_address',
                'device',
                'browser',
                'os',
                'country',
                'state',
                'city',
                'destination_url',
                'clicked_at',
                'created_at',
            ], 'click_page')
            ->appends(array_merge($paginationQuery, ['tab' => 'clicks']));

        $uniqueViews = $viewHistory
            ->where('is_unique', true)
            ->count();
       
        // Banner Type Wise View & Click Count
                     $typeStats = [];
                    $bannerTypes = (array) $banner->type;

                    if (in_array('all', $bannerTypes, true)) {
                        // ALL banner ke liye FSR ke saare pages
                        $allPageTypes = [
                                'join' ,
                                'home' ,
                                'wallet' ,
                                'redeem_request' ,
                                'latest_quiz',
                                'yearly_leaderboard',

                                'question' ,
                                'quiz_leaderboard',
                                'quiz_answer_sheet',
                                'before_quiz_start_count',
                                'quiz_start_10s_count',
                                'before_answer_count',  
                                
                                'practices_list',
                                'practices_start',
                                'before_practice_count',
                                'practice_answer_sheet',
                                'practice_before_answer_sheet',
                                
                                'quiz_history_leaderboard',
                                'quiz_history_answer_sheet',
                                'quiz_history_list' ,
            
                                'fff_question',
                                'fff_quiz_start_10s_count',
                                'fff_before_quiz_start_count',
                                'fff_before_answer_count',
                                'fff_quiz_answer_sheet',
                                'fff_quiz_leaderboard',
                        ];

                        foreach ($allPageTypes as $type) {
                            $typeStats[$type] = [
                                'views' => AdViewHistory::where('banner_id', $banner->id)
                                    ->where('tenant_id', $tenantId)
                                    ->where('page_type', $type)
                                    ->count(),

                                'clicks' => AdClickHistory::where('banner_id', $banner->id)
                                    ->where('tenant_id', $tenantId)
                                    ->where('page_type', $type)
                                    ->count(),
                            ];
                        }

                    } else {
                        // Single / Multiple selected type (same as current)
                        foreach ($bannerTypes as $type) {

                            $typeStats[$type] = [
                                'views' => AdViewHistory::where('banner_id', $banner->id)
                                    ->where('tenant_id', $tenantId)
                                    ->where('page_type', $type)
                                    ->count(),

                                'clicks' => AdClickHistory::where('banner_id', $banner->id)
                                    ->where('tenant_id', $tenantId)
                                    ->where('page_type', $type)
                                    ->count(),
                            ];
                        }
                    }

        $activeTab = in_array(request('tab'), ['views', 'clicks'], true)
            ? request('tab')
            : (request()->has('click_page') ? 'clicks' : 'views');
            
            
        return view('superadmin.ad.detail', compact(
            'banner',
            'viewHistory',
            'clickHistory',
            'totalViews',
            'uniqueViews',
            'totalClicks',
            'activeTab',
             'typeStats'
        ));
    }

}
