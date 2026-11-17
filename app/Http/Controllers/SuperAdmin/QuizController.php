<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\State;
use App\Models\QuizFilter;
use App\Models\Level;
use App\Models\QuizType;
use App\Models\QuizRankReward;
use App\Models\VoucherHistory;
use App\Models\Voucher;
use App\Models\Ad;
use Illuminate\Http\Request;
use App\Repositories\admin\QuizRepositoryInterface;
use App\Repositories\QuizNotificationRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Interfaces\ActionTrailRepositoryInterface;

use App\Models\User;
use App\Models\QuizNotification;
use App\Repositories\WhatsAppRepositoryInterface;

use App\Models\QuizQuestionUserAnswer;
use App\Models\QuizWinner; 
use App\Models\WalletHistory; 
use App\Models\LeaderPointHistory;
use App\Models\QuizStatus;
use Illuminate\Support\Facades\Auth;
use Exception;

class QuizController extends Controller
{
    public function __construct(protected QuizRepositoryInterface $quizRepository ,  protected ActionTrailRepositoryInterface $actionTrail, protected QuizNotificationRepositoryInterface $quizNotification)
    {
    }
    
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $quizzes = Quiz::where('tenant_id', getSuperAdminTenantId())->with([
                    'category',
                    'level',
                    'quizType',
                    'quizStatus',
                    'tempQuizStatus'
                ])->withCount('questions','fffQuestions','quizUsers','tempQuizUsers','adViewHistory','adClickHistory')
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->visibility, fn ($q) => $q->where('visibility', $request->visibility))
           ->when(
                    $request->date,
                    fn ($q) => $q->whereDate('scheduled_at', $request->date)
                )
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.quizzes.index', compact('quizzes'));
    }
    public function show(Quiz $quiz)
    {
        try {
            
            $quiz->load([
                'category',
                'subCategory',      
                'level',
                'quizType',         
                'questions',        
                'rankRewards.voucher', 
                'filter.adCampaign',
                'filter.state',
                'filter.city',
            ]);

            // 2. Format data exactly as expected by the JavaScript frontend
            $data = [
                'title'                   => $quiz->title,
                'category'                => $quiz->category ? ['name' => $quiz->category->name] : null,
                'sub_category'            => $quiz->subCategory ? ['name' => $quiz->subCategory->name] : null,
                'level'                   => $quiz->level ? ['name' => $quiz->level->name] : null,
                'quiz_type'               => $quiz->quizType ? ['name' => $quiz->quizType->name] : null,
                'visibility'              => $quiz->visibility,
                'certificate_on_pass'     => $quiz->certificate_on_pass,
                'question_limit'          => $quiz->question_limit,
                'per_que_time_limit_sec'  => $quiz->per_que_time_limit_sec,
                'no_of_players'           => $quiz->no_of_players,
                'negative_marking'        => $quiz->negative_marking,
                'status'                  => $quiz->status,
                'scheduled_at'            => $quiz->scheduled_at,
                'availability_start'      => $quiz->availability_start,
                'availability_end'        => $quiz->availability_end,
                'before_minute_entry'     => $quiz->before_minute_entry,
                'fifty_fifty'             => $quiz->fifty_fifty,
                'switch_option'           => $quiz->switch_option,

                // Questions with Pivot Data
                'questions' => $quiz->questions->map(function ($q) {
                    return [
                        'question_text'  => $q->question_text ?? 'N/A',
                        'options'        => $q->options, // JS isko khud JSON parse karega
                        'correct_answer' => $q->correct_answer ?? 'N/A',

                        'hindi_question'         => $q->hindi_question ?? null,
                        'hindi_options'          => $q->hindi_options ?? null,
                        'hindi_correct_answer'   => $q->hindi_correct_answer ?? null,
                        'pivot'          => [
                            'order'              => $q->pivot->order ?? null,
                            'bot_correct_answer' => $q->pivot->bot_correct_answer ?? null,
                        ]
                    ];
                }),

                // Rank Rewards with Voucher details
               'rank_rewards' => $quiz->rankRewards->map(function ($r) {

                    $histories = VoucherHistory::whereIn('id',$r->voucher_histories_id ?? [])->get(['id', 'code']);

                    return [
                        'start_rank' => $r->start_rank,
                        'end_rank'   => $r->end_rank,
                        'rank_range' => $r->rank_range,
                        'type'       => $r->type,
                        'reward'     => $r->reward,
                        'voucher_id' => $r->voucher_id,

                        'voucher' => $r->voucher ? [
                            'title' => $r->voucher->name,

                            'voucher_histories' => $histories->map(function ($history) {
                                return [
                                    'id'   => $history->id,
                                    'code' => $history->code,
                                ];
                            })->values(),
                        ] : null,
                    ];
                }),
                // Filter Details
               'filter' => $quiz->filter ? [
                    'ads_campaign' => $quiz->filter->adCampaign instanceof \App\Models\Ad ? [
                        'campaign_name' => $quiz->filter->adCampaign->campaign_name,
                    ] : null,

                    'state' => $quiz->filter->getRelation('state') instanceof \App\Models\State ? [
                            'name' => $quiz->filter->getRelation('state')->name,
                        ] : [
                            // 'name' => $quiz->filter->state,
                            'name' => "-",
                        ],

                        'city' => $quiz->filter->getRelation('city') instanceof \App\Models\City ? [
                            'name' => $quiz->filter->getRelation('city')->name,
                        ] : [
                            // 'name' => $quiz->filter->city,
                            'name' => "-",
                        ],

                    'area'      => $quiz->filter->area,
                    'age_range' => $quiz->filter->age_range,
                    'gender'    => $quiz->filter->gender,
                    'hobby'     => $quiz->filter->hobby,
                    'status'    => $quiz->filter->status,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
            
        } catch (\Exception $e) {
            // Error logging for debugging
            Log::error('Quiz Show Modal Error: ' . $e->getMessage(), [
                'quiz_id' => $quiz->id,
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quiz details. ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        $subcategories = Category::where('tenant_id', getSuperAdminTenantId())->whereNotNull('parent_id')->orderBy('name')->get();
        $categories = Category::where('tenant_id', getSuperAdminTenantId())->whereNull('parent_id')->orderBy('name')->get();
        $Level = Level::where('tenant_id', getSuperAdminTenantId())->orderBy('name')->get();
        $QuizType = QuizType::where('tenant_id', getSuperAdminTenantId())->orderBy('name')->get();
        $AdsCampaigns = Ad::where('tenant_id', getSuperAdminTenantId())->orderBy('campaign_name')->get();
        $editing = null;
        $questionBank = $this->quizRepository->getQuestionBank();
        $selectedQuestions = collect();
        $states = $this->quizRepository->getStates();
        $cities = collect();
        return view('superadmin.quizzes.create-steps', compact('categories', 'subcategories','Level','QuizType','questionBank', 'editing','selectedQuestions','states', 'cities','AdsCampaigns'));
    }

    public function edit(Quiz $quiz)
    {
        $quiz->load('questions');
        $subcategories = Category::where('tenant_id', getSuperAdminTenantId())->whereNotNull('parent_id')->orderBy('name')->get();
        $categories = Category::where('tenant_id', getSuperAdminTenantId())->whereNull('parent_id')->orderBy('name')->get();
        $Level = Level::where('tenant_id', getSuperAdminTenantId())->orderBy('name')->get();
        $QuizType = QuizType::where('tenant_id', getSuperAdminTenantId())->orderBy('name')->get();
        $AdsCampaigns = Ad::where('tenant_id', getSuperAdminTenantId())->orderBy('campaign_name')->get();
        $questionBank = $this->quizRepository->getQuestionBank($quiz);
        $editing = $quiz;
           
        $quizFilter = QuizFilter::where('quiz_id', $quiz->id)->first();
        $states = $this->quizRepository->getStates();
        $cities = collect();

        if ($quizFilter && $quizFilter->state) {
            $cities = $this->quizRepository->getCitiesByState($quizFilter->state);
        }
        
        $selectedQuestions = $this->quizRepository->getSelectedQuestions($quiz);

        return view('superadmin.quizzes.create-steps', compact('categories', 'subcategories','Level','QuizType', 'questionBank', 'editing','selectedQuestions','states', 'cities', 'quizFilter', 'AdsCampaigns'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($data['visibility'] !== 'practice') {
            $helper = getQuestionAvailableTime($data['scheduled_at'], $data['question_limit'], $quizId = null,$data['visibility']);
            
            if($helper){
                $data['availability_start']=$helper['available_start_time'];
                $data['availability_end']=$helper['available_end_time'];
            }
        }

        if ($data['visibility'] == 'fastest_finger_first') {
            $data['fifty_fifty'] = 0;
            $data['switch_option'] = 0;
        }
        $data['tenant_id'] = getSuperAdminTenantId();
        $data['approve_status'] = 'pending';
        $quiz = Quiz::create($data);
        $this->syncQuestions($quiz, $request->input('question_ids', []));
        AuditLog::log('Created quiz', 'Content', $data['title']);
        $this->actionTrail->create([
                'tenant_id'    => $quiz->tenant_id,
                'table_name'   => 'quizzes',
                'reference_id' => $quiz->id,
                'action'       => 'created',
                'details'      => $quiz
             
            ]);
        $quiz->load('questions');
        
        if (!in_array($data['visibility'], ['practice', 'leaderboard','fastest_finger_first'])) {
            $this->insertDefaultRewards($quiz->id);
        }
       
        return redirect()->route('superadmin.quizzes.edit', $quiz)
            ->with('success', 'Quiz details saved successfully! Proceed to select questions.')
            ->with('active_tab', 'questions');
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $this->validated($request);
        
        if ($data['visibility'] !== 'practice') {
            $helper = getQuestionAvailableTime($data['scheduled_at'], $data['question_limit'], $quizId = null,$data['visibility']);
            
            
            if($helper){
                $data['availability_start']=$helper['available_start_time'];
                $data['availability_end']=$helper['available_end_time'];
            }
        }
        
        $data['tenant_id'] = getSuperAdminTenantId();
        $oldDetails = $quiz->toArray();
        $oldTitle = $quiz->title;
        $newTitle = $data['title'] ?? $oldTitle;
        $quiz->update($data);
        AuditLog::log('Updated quiz', 'Content', $quiz->title);
        $this->actionTrail->create([
            'tenant_id'    => $quiz->tenant_id,
            'table_name'   => 'quizzes',
            'reference_id' => $quiz->id,
            'action'       => 'updated',
            'old_details'  => json_encode($oldDetails),
            'details'      => json_encode($quiz->fresh()->toArray()),
        ]);
      
        
        //  Question times allocate karein (ab service method hai)
        if (!in_array($quiz->visibility, ['practice', 'fastest_finger_first'])) {
            $allocationResult = $this->allocateQuestionTimes($quiz);
        }

        return redirect()->route('superadmin.quizzes.edit', $quiz)->with('success', 'Quiz updated.')->with('active_tab', 'questions');
    }

    public function destroy(Quiz $quiz)
    {
        // 2. ✅ Is Quiz se judi saari Notifications delete kar dein
         \App\Models\QuizNotification::where('quiz_id', $quiz->id)->forceDelete();
        $oldDetails = $quiz->toArray();
        $quiz->delete();
        AuditLog::log('Deleted quiz', 'Content', $quiz->title);
        $this->actionTrail->create([
            'tenant_id'    => $quiz->tenant_id,
            'table_name'   => 'quizzes',
            'reference_id' => $quiz->id,
            'action'       => 'deleted',
            'old_details'  => json_encode($oldDetails),
            'details'      =>   $quiz,
            'status'       => 'success',
        ]);

        return back()->with('success', 'Quiz deleted.');
    }

    private function syncQuestions(Quiz $quiz, array $questionIds): void
    {
        $sync = [];
        foreach (array_values($questionIds) as $i => $qid) {
            $sync[$qid] = ['order' => $i + 1];
        }
        $quiz->questions()->sync($sync);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:191',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:categories,id',
            'level_id' => 'nullable|exists:levels,id',
            'type' => 'required|exists:quiz_types,id',
            // 'visibility' => 'required|in:public,private,leaderboard,practice,mahaquiz,fastest_finger_first',
              'visibility' => 'required|in:public,leaderboard,fastest_finger_first',
            'password' => 'nullable|string',
            'retake_allowed' => 'required|boolean',
            'max_attempts' => 'nullable|integer|min:1',
            'pass_percentage' => 'nullable|integer|min:0|max:100',
            'certificate_on_pass' => 'required|boolean',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'question_limit' => 'required|integer|min:0',
            'per_que_time_limit_sec' => 'nullable|integer|min:0',
            'no_of_points' => 'nullable|numeric|min:0',
            'no_of_players' => 'required|numeric|min:0',
            'total_reward_amount' => 'nullable|numeric|min:0',
            'negative_marking' => 'nullable|boolean',
            'approve_status' => 'nullable|in:pending,approved,rejected',
            'complete_status' => 'nullable|in:pending,completed',
            'status' => 'required|in:draft,scheduled,published,archived',
            // 'scheduled_at' => 'required|date',
            'scheduled_at' => 'nullable|date|required_unless:visibility,practice',
            'before_minute_entry' => 'nullable|numeric|min:0',
            'fifty_fifty' => 'nullable|boolean',
            'switch_option' => 'nullable|boolean',
            'login_time_period' => 'nullable|date',
        ]);
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:quizzes,id',
            'status' => 'required|in:draft,scheduled,published,archived',
        ]);

        $quiz = Quiz::findOrFail($request->id);
       $oldStatus = $quiz->status;
        $quiz->update([
            'status' => $request->status
        ]);

        $this->actionTrail->create([
            'tenant_id'    => $quiz->tenant_id,
            'table_name'   => 'quizzes',
            'reference_id' => $quiz->id,
            'action'       => 'status_updated',
            'old_details'  => json_encode([
                'status' => $oldStatus,
            ]),
            'details'      => json_encode([
                'status' => $quiz->status,
            ]),
            'status'       => 'success',
        ]);

        AuditLog::log(
            'Updated quiz status',
            'Content',
            $quiz->title
        );

        return response()->json([
            'success' => true,
            'message' => 'Quiz status updated successfully.'
        ]);
    }


  

    public function saveQuestions(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_ids'   => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        // Quiz ki question limit
        $maxLimit = !empty($quiz->question_limit) ? (int)$quiz->question_limit : 0;
        if ($quiz->switch_option && $maxLimit > 0) {
            $maxLimit += 1;
        }

        if ($maxLimit > 0) {
            $selectedCount = count($request->question_ids);

            if ($selectedCount > $maxLimit) {
                $limitMsg = $quiz->switch_option ? "You can select maximum {$maxLimit} questions ({$quiz->question_limit} standard + 1 switch question)." : "You can select maximum {$maxLimit} questions.";
                return back()->withErrors([
                    'question_ids' => $limitMsg
                ])->withInput();
            }
        }

        $orders = [];

        foreach ($request->question_ids as $index => $qid) {

            $order = $request->order[$qid] ?? null;

            // Blank order ko skip karo
            if ($order === '' || $order === null) {
                continue;
            }

            // 0 allow nahi
            if ($order <= 0) {
                return back()->withErrors([
                    'order' => 'Order number must be greater than 0.'
                ])->withInput();
            }

            // Duplicate allow nahi
            if (in_array($order, $orders)) {

                return back()->withErrors([
                    'order' => "Duplicate order number ($order) is not allowed."
                ])->withInput();
            }

            $orders[] = $order;
        }

        $questionIds = (array) $request->input('question_ids', []);
        $isSwitchQuestions = (array) $request->input('is_switch_question', []);

        if ($quiz->switch_option && !empty($questionIds)) {
            $hasSelectedSwitch = false;
            foreach ($isSwitchQuestions as $val) {
                if ((int)$val === 1) {
                    $hasSelectedSwitch = true;
                    break;
                }
            }

            if (!$hasSelectedSwitch) {
                $lastQid = end($questionIds);
                foreach ($questionIds as $qid) {
                    $isSwitchQuestions[$qid] = ($qid == $lastQid) ? 1 : 0;
                }
            }
        }

        $this->quizRepository->syncQuestions(
            $quiz,
            $questionIds,
            (array) $request->input('order', []),
            (array) $request->input('bot_correct_answer', []),
            $isSwitchQuestions
        );

        if (!empty($questionIds)) {
            $quiz->update([
                'approve_status' => 'approved',
            ]);
        }

        $this->actionTrail->create([
            'tenant_id'    => $quiz->tenant_id,
            'table_name'   => 'quiz_questions',
            'reference_id' => $quiz->id,
            'action'       => 'questions_updated',
            'details'      => json_encode([
                'question_ids' => $questionIds,
                'total_questions' => count($questionIds),
            ]),
            'status'       => 'success',
        ]);

         //  Question times allocate karein (ab service method hai)
        if (!in_array($quiz->visibility, ['practice', 'fastest_finger_first'])) {
            $allocationResult = $this->allocateQuestionTimes($quiz);

            if (!$allocationResult['success']) {
                return back()->withErrors([
                    'questions' => $allocationResult['message']
                ])->withInput();
            }
        }

         $active_tab ='rewards';
        if ($quiz->visibility === 'fastest_finger_first') {
            
            return redirect()->route('superadmin.quizzes.index')->with('success', 'Questions added successfully.');
        }


        return back()->with('success', 'Questions added successfully.')->with('active_tab', $active_tab);
    }

  

    //**************rank reward save***************//
    

    public function saveRewards(Request $request)
    {
        $validated = $request->validate([
            'quiz_id'                => 'required|exists:quizzes,id',
            'rewards'                => 'required|array|min:1',
            'rewards.*.id'           => 'nullable|integer|exists:quiz_rank_rewards,id',
            'rewards.*.start_rank'   => 'required|integer|min:1',
            'rewards.*.end_rank'     => 'required|integer|min:1',
            'rewards.*.reward_type'  => 'required|in:point,voucher',
            'rewards.*.reward_value' => 'required_if:rewards.*.reward_type,point|nullable|numeric',
            'rewards.*.voucher_id'   => 'required_if:rewards.*.reward_type,voucher|nullable|exists:vouchers,id',
        ]);

        // Backend Validation: Start > End and Overlap check
        $ranges = [];
        foreach ($validated['rewards'] as $index => $rewardData) {
            if ($rewardData['start_rank'] > $rewardData['end_rank']) {
                return back()->withErrors(['rewards' => "Row " . ($index + 1) . ": Start Rank cannot be greater than End Rank."])->withInput();
            }
            $ranges[] = [
                'start' => $rewardData['start_rank'], 
                'end'   => $rewardData['end_rank'], 
                'index' => $index
            ];
        }

        for ($i = 0; $i < count($ranges); $i++) {
            for ($j = $i + 1; $j < count($ranges); $j++) {
                $r1 = $ranges[$i];
                $r2 = $ranges[$j];
                
                if ($r1['start'] <= $r2['end'] && $r1['end'] >= $r2['start']) {
                    return back()->withErrors(['rewards' => "Rank range {$r1['start']}-{$r1['end']} overlaps with {$r2['start']}-{$r2['end']}."])->withInput();
                }
            }
        }

        $tenantId = getSuperAdminTenantId();

        // 🔒 START TRANSACTION: Taaki agar koi step fail ho, toh pura process rollback ho jaye
        DB::beginTransaction();

        try {
            // Existing rewards ke IDs nikalo
            $existingRewards = QuizRankReward::where('quiz_id', $validated['quiz_id'])
                ->get()
                ->keyBy('id');

            $submittedIds = [];

            // Update ya Create logic
            foreach ($validated['rewards'] as $rewardData) {
                $rewardPayload = [
                    'start_rank'  => $rewardData['start_rank'],
                    'end_rank'    => $rewardData['end_rank'],
                    'type'        => $rewardData['reward_type'],
                    'reward'      => $rewardData['reward_type'] === 'point' ? $rewardData['reward_value'] : null,
                    'voucher_id'  => $rewardData['reward_type'] === 'voucher' ? $rewardData['voucher_id'] : null,
                    'status'      => 'active',
                ];

                if (!empty($rewardData['id']) && $existingRewards->has($rewardData['id'])) {
                    $reward = $existingRewards->get($rewardData['id']);
                    $oldType = $reward->type;
                    $oldVoucherHistoriesRaw = $reward->voucher_histories_id ?? [];

                    // 🚨 SCENARIO: Pehle Voucher tha, ab Point (ya kuch aur) kar diya
                    if ($oldType === 'voucher' && $rewardData['reward_type'] !== 'voucher') {
                        // Safe JSON Decode
                        $historyIds = is_string($oldVoucherHistoriesRaw) 
                            ? json_decode($oldVoucherHistoriesRaw, true) 
                            : (array) $oldVoucherHistoriesRaw;
                        $historyIds = array_filter($historyIds);

                        if (!empty($historyIds)) {
                            // Voucher histories ko wapas pending karo aur user details clear karo
                            VoucherHistory::whereIn('id', $historyIds)
                                ->where('voucher_id', $reward->voucher_id) // Safety check
                                ->update([
                                    'status'            => 'pending',
                                    'assign_to_user_id' => null,
                                    'assign_date'       => null,
                                    'expire_date'       => null,
                                    'used_date'         => null,
                                    'used_user_id'      => null,
                                    'updated_at'        => now(),
                                ]);
                        }
                        // Payload se voucher fields clear kar do taaki DB mein null save ho
                        $rewardPayload['voucher_id'] = null;
                        $rewardPayload['voucher_histories_id'] = null; 
                    }

                    // Record update karo
                    $reward->update($rewardPayload);
                    
                    // Agar ab Voucher hai (naya select kiya ya pehle se tha), toh histories assign karo
                    if ($rewardData['reward_type'] === 'voucher') {
                        $this->assignVoucherHistories($reward);
                    }
                    
                    $submittedIds[] = $rewardData['id'];
                } else {
                    // Naya reward → Create
                    $newReward = QuizRankReward::create(array_merge($rewardPayload, [
                        'quiz_id'   => $validated['quiz_id'],
                        'tenant_id' => $tenantId,
                        'voucher_histories_id' => null, 
                    ]));
                    
                    if ($newReward->type === 'voucher') {
                        $this->assignVoucherHistories($newReward);
                    }
                    
                    $submittedIds[] = $newReward->id;
                }
            }

            // Removed rewards ko Soft Delete karo aur unke vouchers release karo
            $removedIds = array_diff($existingRewards->keys()->toArray(), $submittedIds);
            if (!empty($removedIds)) {
                foreach ($removedIds as $removedId) {
                    $removedReward = $existingRewards->get($removedId);
                    
                    if ($removedReward->type === 'voucher' && !empty($removedReward->voucher_histories_id)) {
                        $historyIds = is_string($removedReward->voucher_histories_id) 
                            ? json_decode($removedReward->voucher_histories_id, true) 
                            : (array) $removedReward->voucher_histories_id;
                        $historyIds = array_filter($historyIds);

                        if (!empty($historyIds)) {
                            VoucherHistory::whereIn('id', $historyIds)
                                ->where('voucher_id', $removedReward->voucher_id)
                                ->update([
                                    'status'            => 'pending',
                                    'assign_to_user_id' => null,
                                    'assign_date'       => null,
                                    'expire_date'       => null,
                                    'used_date'         => null,
                                    'used_user_id'      => null,
                                    'updated_at'        => now(),
                                ]);
                        }
                    }
                    
                    $removedReward->delete();
                }
            }

            // Action Trail Entry
            $this->actionTrail->create([
                'tenant_id'    => $tenantId,
                'quiz_id'      => $validated['quiz_id'],
                'table_name'   => 'quiz_rank_rewards',
                'reference_id' => $validated['quiz_id'],
                'action'       => 'rewards_updated',
                'details'      => json_encode([
                    'total_rewards' => count($validated['rewards']),
                    'rewards'       => $validated['rewards'],
                    'removed_ids'   => array_values($removedIds),
                ]),
                'status'       => 'success',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // ✅ Sab kuch successful raha to commit karo
            DB::commit();

            return redirect()->route('superadmin.quizzes.index')->with('success', 'Quiz Rank Rewards saved successfully!'); 

        } catch (\Exception $e) {
            // ❌ Koi error aaya to pura process undo (rollback) karo
            DB::rollBack();
            
            Log::error("Failed to save quiz rewards: " . $e->getMessage(), [
                'quiz_id' => $validated['quiz_id'],
                'trace'   => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->withErrors(['error' => 'Failed to save rewards. Please try again.'])->withInput();
        }
    }

    /** Naya Function: Voucher Histories ko assign/release karna */
  
 
    public function assignVoucherHistories($reward)
    {
        try {
            // 1. Calculate required vouchers for this reward (e.g., rank 1-3 = 3 vouchers)
            $requiredCount = ($reward->end_rank - $reward->start_rank) + 1;
            
            // 2. Safely decode current IDs (Handle both JSON string "[10,11]" and Array [10,11])
            $currentIdsRaw = $reward->voucher_histories_id ?? [];
            $currentIds = is_string($currentIdsRaw) 
                ? json_decode($currentIdsRaw, true) 
                : (array) $currentIdsRaw;
            
            // Remove any null or empty values from array
            $currentIds = array_filter($currentIds); 

            if ($requiredCount > count($currentIds)) {
                // ✅ SCENARIO 1: NEED MORE Vouchers (e.g., had 2, now need 3)
                $additionalNeeded = $requiredCount - count($currentIds);
                
                $newHistories = VoucherHistory::where('voucher_id', $reward->voucher_id)
                    ->where('tenant_id', $reward->tenant_id) // 🔒 Safety: Same tenant only
                    ->where('status', 'pending') // Jo abhi tak kisi ko assign nahi hue
                    ->whereNull('assign_to_user_id')
                    ->limit($additionalNeeded)
                    ->get();
                
                $newIds = $newHistories->pluck('id')->toArray();
                
                if (!empty($newIds)) {
                    // Newly fetched vouchers ka status update karo
                    VoucherHistory::whereIn('id', $newIds)->update([
                        'status'     => 'assigned_to_quiz', // Base status
                        'updated_at' => now(),
                    ]);
                }
                
                // Existing + New IDs ko merge karke save karo
                $reward->update([
                    'voucher_histories_id' => array_merge($currentIds, $newIds)
                ]);
                
            } elseif ($requiredCount < count($currentIds)) {
                // ✅ SCENARIO 2: NEED FEWER Vouchers (e.g., had 3, now need 2)
                
                // Step A: Sirf required count ke hisaab se shuru ke IDs ko "Keep" karo
                $keptIds = array_slice($currentIds, 0, $requiredCount);
                
                // Step B: Baaki bach gayi IDs ko "Excess" maan kar nikal lo (e.g., [10,11,12] - [10,11] = [12])
                $excessIds = array_diff($currentIds, $keptIds);
                
                if (!empty($excessIds)) {
                    // Step C: 🔒 COLUMN CHECK: Database mein verify karo ki ye IDs issi voucher ki hain aur inhe hi reset karo
                    VoucherHistory::whereIn('id', $excessIds)
                        ->where('voucher_id', $reward->voucher_id)
                        ->whereIn('status', ['assigned_to_quiz', 'pending']) // Safety check
                        ->update([
                            'status'            => 'pending',          // Wapas pending kar do
                            'assign_to_user_id' => null,               // User assignment clear
                            'assign_date'       => null,
                            'expire_date'       => null,
                            'used_date'         => null,
                            'used_user_id'      => null,
                            'updated_at'        => now(),
                        ]);
                        
                    Log::info("✅ Released excess vouchers to pending", [
                        'reward_id'  => $reward->id,
                        'excess_ids' => array_values($excessIds),
                    ]);
                }
                
                // Step D: Reward table mein sirf "kept" IDs save karo
                $reward->update([
                    'voucher_histories_id' => array_values($keptIds) // Clean indexed array
                ]);
                
            }
            // ✅ SCENARIO 3: If requiredCount == count($currentIds), no change needed (Do nothing)

        } catch (Exception $e) {
            // ❌ Koi bhi unexpected error aaya toh yahan catch hoga
            Log::error('Failed to assign voucher histories', [
                'reward_id'    => $reward->id ?? 'unknown',
                'voucher_id'   => $reward->voucher_id ?? 'unknown',
                'tenant_id'    => $reward->tenant_id ?? 'unknown',
                'error_message'=> $e->getMessage(),
                'trace'        => $e->getTraceAsString()
            ]);

            // 🚨 IMPORTANT: Exception ko wapas upar (parent function) phenk do
            // Taaki parent function ka 'DB::rollBack()' trigger ho sake aur data corrupt na ho.
            throw $e; 
        }
    }

    // ************************ */
   
    public function getCities($stateId)
    {
        return response()->json(
            $this->quizRepository->getCitiesByState($stateId)
        );
    }


    // ********slot selction********
   
    /*public function checkSlot(Request $request)
    {
        try {

            $request->validate([
                'scheduled_at'   => 'required|date',
                'question_limit' => 'required|integer|min:1',
                'quiz_id'        => 'nullable|integer',
            ]);

            $time = getQuestionAvailableTime(
                $request->scheduled_at,
                $request->question_limit,
                $request->quiz_id
            );

            $slot = checkQuizTimeSlot(
                $time['available_start_time'],
                $time['available_end_time'],
                $request->quiz_id
            );

            if ($slot) {

                $start = Carbon::parse($slot->availability_start);
                $end   = Carbon::parse($slot->availability_end);

                return response()->json([
                    'status' => false,
                    'message' => sprintf(
                        'A quiz is already scheduled on %s, %s – %s. Please choose another time.',
                        $start->format('d M Y'),
                        $start->format('h:i A'),
                        $end->format('h:i A')
                    ),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => '',
                'available_start' => $time['available_start_time'],
                'available_end'   => $time['available_end_time'],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }*/

    public function checkSlot(Request $request)
    {
        try {

            $request->validate([
                'scheduled_at'   => 'required|date',
                'question_limit' => 'required|integer|min:1',
                'quiz_id'        => 'nullable|integer',
                'quiz_id'        => 'nullable|integer',
                 'visibility'     => 'required|string|in:public,leaderboard,fastest_finger_first,practice,public', // ✅ Validation add ki
            ]);

            $scheduledAt = Carbon::parse($request->scheduled_at);
            $questionLimit = (int) $request->question_limit;
            $quizId = $request->quiz_id;
            $visibility = $request->visibility;

            // Check current time first
            $currentTime = getQuestionAvailableTime(
                $scheduledAt->toDateTimeString(),
                $questionLimit,
                $quizId,
                $visibility
            );

            $currentSlot = checkQuizTimeSlot(
                $currentTime['available_start_time'],
                $currentTime['available_end_time'],
                $quizId
            );

            //  No conflict at selected time
            if (!$currentSlot) {
                return response()->json([
                    'status' => true,
                    'message' => 'Slot is available',
                    'available_start' => $currentTime['available_start_time'],
                    'available_end'   => $currentTime['available_end_time'],
                ], 200);
            }

            //  Conflict detected at selected time
            $conflictStart =Carbon::parse($currentSlot->availability_start);
            $conflictEnd   = Carbon::parse($currentSlot->availability_end);

            $conflictMessage = sprintf(
                '⚠️ Conflict! A quiz is already scheduled on %s, %s – %s',
                $conflictStart->format('d M Y'),
                $conflictStart->format('h:i A'),
                $conflictEnd->format('h:i A')
            );

            //  Find next available slot (search up to 7 days ahead)
            $searchTime = $conflictEnd->copy()->addMinutes(2); // Start search after conflict ends
            $maxAttempts = 336; // 7 days / 30 min intervals
            $attempt = 0;
            $nextAvailable = null;

            while ($attempt < $maxAttempts) {
                
                $checkTime = getQuestionAvailableTime(
                    $searchTime->toDateTimeString(),
                    $questionLimit,
                    $quizId,
                    $visibility
                );

                $conflictSlot = checkQuizTimeSlot(
                    $checkTime['available_start_time'],
                    $checkTime['available_end_time'],
                    $quizId
                );

                if (!$conflictSlot) {
                    //  Found next available slot
                    $nextAvailable = [
                        'start' => Carbon::parse($checkTime['available_start_time']),
                        'end'   => Carbon::parse($checkTime['available_end_time']),
                        'input_value' => $searchTime->format('Y-m-d\TH:i'),
                    ];
                    break;
                }

                // Jump past this conflict
                $jumpEnd = Carbon::parse($conflictSlot->availability_end);
                $searchTime = $jumpEnd->copy()->addMinutes(2);
                $attempt++;
            }

            if (!$nextAvailable) {
                return response()->json([
                    'status' => false,
                    'message' => $conflictMessage . '. No available slot found in the next 7 days.',
                    'has_next' => false,
                ], 409);
            }

            //  Return conflict + next available suggestion
            return response()->json([
                'status' => false,
                'message' => $conflictMessage,
                'has_next' => true,
                'next_available' => [
                    'datetime' => $nextAvailable['start']->format('d M Y, h:i A'),
                    'start' => $nextAvailable['start']->format('Y-m-d H:i:s'),
                    'end'   => $nextAvailable['end']->format('Y-m-d H:i:s'),
                    'input_value' => $nextAvailable['input_value'],
                ]
            ], 409);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }


    // **********Other Quizzes**********//
    public function otherQuizzes(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $quizzes = Quiz::where('tenant_id',"!=", getSuperAdminTenantId())->with([
                    'category',
                    'level',
                    'quizType',
                    'tenant'  
                ])->withCount('questions')
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->visibility, fn ($q) => $q->where('visibility', $request->visibility))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('superadmin.quizzes.other-quiz', compact('quizzes'));
    }

    public function aproveChangeStatus(Request $request)
    { 
        $request->validate([
            'id' => 'required|exists:quizzes,id',
            'approve_status' => 'required|in:approved,rejected,cancelled',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $quiz = Quiz::findOrFail($request->id);

            // Sirf pending quiz hi approve/reject ho sakti hai
           /* if ($quiz->approve_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending quizzes can be approved or rejected or cancelled.'
                ], 403);
            }*/

            $updateData = [
                'approve_status' => $request->approve_status,
            ];

            if ($request->filled('note')) {
                $updateData['notes'] = $request->note;
            }
           $oldApproveStatus = $quiz->approve_status;
            $quiz->update($updateData);

            $this->actionTrail->create([
                'tenant_id'    => $quiz->tenant_id,
                'table_name'   => 'quizzes',
                'reference_id' => $quiz->id,
                'action'       => 'approval_status_updated',
                'old_details'  => json_encode([
                    'approve_status' => $oldApproveStatus,
                ]),
                'details'      => json_encode([
                    'approve_status' => $quiz->approve_status,
                    'note'           => $request->note,
                ]),
                'status'       => 'success',
            ]);
            AuditLog::log(
                'Updated quiz approve status to ' . $request->approve_status,
                'Content',
                $quiz->title . ' | Note: ' . ($request->note ?? 'None')
            );

            return response()->json([
                'success' => true,
                'message' => 'Quiz approval status updated successfully.'
            ]);

        } catch (\Exception $e) {

            Log::error('Quiz approval status update failed', [
                'quiz_id' => $request->id,
                'approve_status' => $request->approve_status,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating the quiz approval status.',
            ], 500);
        }
    }


    // *******time allwocation for each question********//
 
   
    // *******time allwocation for each question********//
    public function allocateQuestionTimes(Quiz $quiz): array
    {
        try {
            // Check scheduled_at
            if (empty($quiz->scheduled_at)) {
                return [
                    'success' => false,
                    'message' => 'Please schedule the quiz first before allocating question times.',
                    'data'    => null,
                ];
            }

            // Questions order ke hisaab se fetch
            $questions = $quiz->questions()
                ->orderBy('quiz_question.order')
                ->get();

            if ($questions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No questions found for this quiz.',
                    'data'    => null,
                ];
            }

            DB::beginTransaction();

            $baseTime = Carbon::parse($quiz->scheduled_at);
            
            // ⏱ NEW TIMING CONFIG
            $initialGap = 10;        // First question se pehle 20 sec ka gap
            $timePerQuestion = 17;   // Har question 15 sec ka duration
            
            // Current offset start with initial gap
            $currentOffset = $initialGap;

            foreach ($questions as $index => $question) {
                // Start time = scheduled_at + current offset
                $startTime = $baseTime->copy()->addSeconds($currentOffset);
                
                // End time = start time + 15 seconds
                $endTime = $startTime->copy()->addSeconds($timePerQuestion);

                // Pivot table update karein
                $quiz->questions()->updateExistingPivot($question->id, [
                    'start_time' => $startTime->format('Y-m-d H:i:s'),
                    'end_time'   => $endTime->format('Y-m-d H:i:s'),
                ]);

                // Next question ke liye offset increment
                $currentOffset += $timePerQuestion;
            }

            DB::commit();

            // Total duration calculate karein (initial gap + all questions)
            $totalDurationSeconds = $currentOffset;
            $totalDuration = gmdate('H:i:s', $totalDurationSeconds);
            $quizEndTime = $baseTime->copy()->addSeconds($totalDurationSeconds);

            AuditLog::log(
                'Allocated question times for quiz',
                'Quiz',
                $quiz->title . ' | Total Questions: ' . $questions->count() . 
                ' | Start: ' . $baseTime->format('Y-m-d H:i:s') . 
                ' | End: ' . $quizEndTime->format('Y-m-d H:i:s') .
                ' | Initial Gap: ' . $initialGap . 's | Per Question: ' . $timePerQuestion . 's'
            );

            $this->actionTrail->create([
                'tenant_id'    => $quiz->tenant_id,
                'table_name'   => 'quiz_questions',
                'reference_id' => $quiz->id,
                'action'       => 'question_times_allocated',
                'details'      => json_encode([
                    'total_questions'   => $questions->count(),
                    'scheduled_at'      => $baseTime->format('Y-m-d H:i:s'),
                    'first_question_at' => $baseTime->copy()->addSeconds($initialGap)->format('Y-m-d H:i:s'),
                    'quiz_end'          => $quizEndTime->format('Y-m-d H:i:s'),
                    'initial_gap'       => $initialGap,
                    'time_per_question' => $timePerQuestion,
                    'total_duration'    => $totalDuration,
                ]),
                'status' => 'success',
            ]);

            return [
                'success' => true,
                'message' => 'Question times allocated successfully.',
                'data'    => [
                    'total_questions'    => $questions->count(),
                    'scheduled_at'       => $baseTime->format('Y-m-d H:i:s'),
                    'quiz_start'         => $baseTime->format('Y-m-d H:i:s'),
                    'first_question_at'  => $baseTime->copy()->addSeconds($initialGap)->format('Y-m-d H:i:s'),
                    'quiz_end'           => $quizEndTime->format('Y-m-d H:i:s'),
                    'initial_gap'        => $initialGap,
                    'time_per_question'  => $timePerQuestion,
                    'total_duration'     => $totalDuration,
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Question time allocation failed', [
                'quiz_id' => $quiz->id,
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return [
                'success' => false,
                'message' => 'Something went wrong while allocating question times: ' . $e->getMessage(),
                'data'    => null,
            ];
        }
    }

    // *************defuault entry reward************
  
    public function insertDefaultRewards(int $quizId): void
    {
        //  Quiz fetch karein
        $quiz = Quiz::findOrFail($quizId);
        
        $maxPlayers = $quiz->no_of_players > 0 ? $quiz->no_of_players : 400;

        $defaultRewards = [
            ['start_rank' => 1,   'end_rank' => 3,   'reward_type' => 'point', 'reward_value' => 25.00],
            ['start_rank' => 4,   'end_rank' => 10,  'reward_type' => 'point', 'reward_value' => 10.00],
            ['start_rank' => 11,  'end_rank' => 11,  'reward_type' => 'point', 'reward_value' => 11.00],
            ['start_rank' => 12,  'end_rank' => 20,  'reward_type' => 'point', 'reward_value' => 5.00],
            ['start_rank' => 21,  'end_rank' => 21,  'reward_type' => 'point', 'reward_value' => 21.00],
            ['start_rank' => 22,  'end_rank' => 35,  'reward_type' => 'point', 'reward_value' => 5.00],
            ['start_rank' => 36,  'end_rank' => 50,  'reward_type' => 'point', 'reward_value' => 3.00],
            ['start_rank' => 51,  'end_rank' => 51,  'reward_type' => 'point', 'reward_value' => 11.00],
            ['start_rank' => 52,  'end_rank' => 125, 'reward_type' => 'point', 'reward_value' => 3.00],
            ['start_rank' => 126, 'end_rank' => 250, 'reward_type' => 'point', 'reward_value' => 2.00],
            ['start_rank' => 251, 'end_rank' => 400, 'reward_type' => 'point', 'reward_value' => 1.00],
        ];

     
        $quiz->rankRewards()->delete();

        $insertedCount = 0;

        foreach ($defaultRewards as $reward) {
            
            if ($reward['start_rank'] > $maxPlayers) {
                break;
            }

            $adjustedEndRank = min($reward['end_rank'], $maxPlayers);

            QuizRankReward::create([
                'quiz_id'    => $quizId,
                'tenant_id'  => $quiz->tenant_id,
                'start_rank' => $reward['start_rank'],
                'end_rank'   => $adjustedEndRank, // ✅ Adjusted
                'type'       => $reward['reward_type'],
                'reward'     => $reward['reward_value'],
                'voucher_id' => null,
            ]);


            $insertedCount++;

            if ($adjustedEndRank >= $maxPlayers) {
                break;
            }
        }

        Log::info("✅ Default rewards inserted for quiz ID: {$quizId} | Max Players: {$maxPlayers} | Entries: {$insertedCount}");
   
        $this->actionTrail->create([
                'tenant_id'    => $quiz->tenant_id,
                'table_name'   => 'quiz_rank_rewards',
                'reference_id' => $quizId,
                'action'       => 'default_rewards_created',
                'details'      => json_encode([
                    'max_players'   => $maxPlayers,
                    'inserted_count' => $insertedCount,
                ]),
                'status' => 'success',
            ]);
   
    }
    public function addDefaultRewards($quizId)
    {
        // Aapka existing function call hoga
        $this->insertDefaultRewards((int) $quizId);
        

        $this->actionTrail->create([
            'tenant_id'    => getSuperAdminTenantId(),
            'table_name'   => 'quiz_rank_rewards',
            'reference_id' => $quizId,
            'action'       => 'default_rewards_added',
            'details'      => json_encode([
                'quiz_id' => $quizId,
            ]),
            'status'       => 'success',
        ]);
        // Optional: Audit log save karne ke liye (agar aapke paas AuditLog model hai)
        AuditLog::log('Added default rewards for Quiz ID: ' . $quizId, 'QuizRewards');

        return back()->with('success', 'Default rewards added successfully!')->with('active_tab', 'rewards');;
    }
    // **********clear reward***************
 
 
    public function clearRewards($quizId)
    {
        try {
            // 1. Quiz fetch karo (ModelNotFoundException handle ho jayega catch block mein)
            $quiz = Quiz::findOrFail($quizId);
            $tenantId = $quiz->tenant_id;

            // 🔒 Transaction start karo taaki data consistent rahe
            DB::beginTransaction();

            // 2. Pehle existing rewards fetch karo taaki voucher check kar saken
            $existingRewards = QuizRankReward::where('quiz_id', $quizId)->get();

            // 3. Voucher Rewards ki histories ko reset (pending) karo
            foreach ($existingRewards as $reward) {
                if ($reward->type === 'voucher' && !empty($reward->voucher_histories_id)) {
                    
                    // ✅ SAFE JSON DECODE: String "[12,13]" ya Array [12,13] dono handle karega
                    $historyIds = is_string($reward->voucher_histories_id) 
                        ? json_decode($reward->voucher_histories_id, true) 
                        : (is_array($reward->voucher_histories_id) ? $reward->voucher_histories_id : []);

                    if (!empty($historyIds) && is_array($historyIds)) {
                        // Voucher histories ko wapas 'pending' status mein daal do aur user details clear kar do
                        $resetCount = VoucherHistory::whereIn('id', $historyIds)
                            ->where('tenant_id', $tenantId) // Safety: Sirf same tenant ke vouchers reset ho
                            ->update([
                                'status'            => 'pending', // ⚠️ Note: Agar aapka initial status 'assigned_to_quiz' hai, toh yahan 'assigned_to_quiz' likh dein
                                'assign_to_user_id' => null,
                                'assign_date'       => null,
                                'expire_date'       => null,
                                'used_date'         => null,
                                'used_user_id'      => null,
                                'updated_at'        => now(),
                            ]);

                        Log::info("✅ Reset {$resetCount} voucher histories for cleared reward ID: {$reward->id}", [
                            'quiz_id' => $quizId,
                            'history_ids' => $historyIds
                        ]);
                    }
                }
            }

            // 4. Ab safely QuizRankReward records delete karo
            $deletedCount = QuizRankReward::where('quiz_id', $quizId)->delete();

            // 5. Action Trail Entry
            $this->actionTrail->create([
                'tenant_id'    => $tenantId,
                'quiz_id'      => $quizId,
                'table_name'   => 'quiz_rank_rewards',
                'reference_id' => $quizId,
                'action'       => 'rewards_cleared_and_vouchers_reset',
                'details'      => json_encode([
                    'deleted_rewards_count' => $deletedCount,
                    'message'               => 'Associated voucher histories reset to pending'
                ]),
                'status'       => 'success',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            Log::info("✅ All rewards cleared for quiz ID: {$quizId} | Deleted: {$deletedCount} records");
            
            // Audit Log (Agar aapke paas custom AuditLog class hai)
            if (class_exists('AuditLog')) {
                AuditLog::log('Clear default rewards for Quiz ID: ' . $quizId, 'QuizRewards');
            }

            // ✅ Sab kuch successful raha to commit karo
            DB::commit();

            return redirect()
                ->back()
                ->with('success', "All {$deletedCount} rewards deleted successfully! Associated vouchers have been reset to pending. You can now add new rewards.")
                ->with('active_tab', 'rewards');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Quiz not found.']);
                
        } catch (\Exception $e) {
            // ❌ Koi error aaya to pura process undo (rollback) karo
            DB::rollBack();
            
            Log::error("❌ Failed to clear rewards for quiz {$quizId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to clear rewards. Please try again.']);
        }
    }

    // ************Whatsup entry direct*************

    public function prepareWhatsappEntries()
    {
        // 1. Next Scheduled Quiz nikalo
        $quiz = Quiz::where('status', 'scheduled') 
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->first();

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Koi upcoming scheduled quiz nahi mila.'
            ]);
        }

        // 2. Schedule time se 15 minute pehle ka time calculate karo
        $activTime = Carbon::parse($quiz->scheduled_at)->subMinutes(15);
        $now = now();

        // 3. Active users jinke paas mobile number hai
        $users = User::where('is_active', 1)
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->get(['id', 'name']);

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Koi active user mobile number ke sath nahi mila.'
            ]);
        }

        // 4. ✅ SMART CHECK: Is quiz ki SARI entries nikalo (pending, Sent, failed sab)
        $existingRecords = QuizNotification::withTrashed()
            ->where('quiz_id', $quiz->id)
            ->get()
            ->keyBy('user_id');

        $insertData = [];
        $updateUserIds = [];
        $restoreIds = [];

        // 5. Har user ko check karo
        foreach ($users as $user) {
            if ($existingRecords->has($user->id)) {
                $record = $existingRecords->get($user->id);

                // ✅ RULE 1: Agar status 'Sent', 'failed' hai ya sent_date_time set hai -> Strict Skip!
                if (in_array($record->status, ['Sent', 'failed']) || !empty($record->sent_date_time)) {
                    continue; 
                }

                if ($record->deleted_at !== null && $record->status === 'pending') {
                    $restoreIds[] = $record->id;
                }

                // ✅ RULE 2: Agar status 'pending' hai aur quiz ka time change hua hai, toh UPDATE list mein daalo
                if ($record->status === 'pending' && $record->quiz_schedule_time != $quiz->scheduled_at) {
                    $updateUserIds[] = $user->id;
                }

            } else {
                // ✅ RULE 3: Agar is user ki is quiz ke liye koi entry hi nahi hai, toh INSERT list mein daalo
                $insertData[] = [
                    'quiz_id' => $quiz->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'quiz_name' => $quiz->title ?? $quiz->name,
                    'quiz_schedule_time' => $quiz->scheduled_at,
                    'notificationactiv_date_time' => $activTime, 
                    'status' => 'pending',
                    'msg' => null,
                    'sent_date_time' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($restoreIds)) {
            QuizNotification::withTrashed()->whereIn('id', $restoreIds)->restore();
        }

        $totalInserted = 0;
        $totalUpdated = 0;

        // 6. ✅ INSERT: Naye users ki bulk entry karo (insertOrIgnore + unique user_id)
        if (!empty($insertData)) {
            $uniqueInsertData = collect($insertData)->unique('user_id')->values()->all();
            foreach (array_chunk($uniqueInsertData, 500) as $chunk) {
                QuizNotification::insertOrIgnore($chunk);
            }
            $totalInserted = count($uniqueInsertData);
        }

        // 7. ✅ UPDATE: Sirf un 'pending' users ko update karo jinka time change hua hai
        if (!empty($updateUserIds)) {
            QuizNotification::where('quiz_id', $quiz->id)
                ->whereIn('user_id', $updateUserIds)
                ->where('status', 'pending') // Extra safety check
                ->update([
                    'quiz_schedule_time' => $quiz->scheduled_at,
                    'notificationactiv_date_time' => $activTime,
                    'updated_at' => $now,
                ]);
            $totalUpdated = count($updateUserIds);
        }

        // 8. Success response
        return response()->json([
            'success' => true,
            'message' => "Process Complete: {$totalInserted} Nayi entries, {$totalUpdated} Purani entries update. (Sent/Failed users ko safely skip kar diya gaya).",
            'quiz_name' => $quiz->title ?? $quiz->name,
            'quiz_schedule_time' => $quiz->scheduled_at,
            'notification_will_activate_at' => $activTime->toDateTimeString()
        ]);
    }

    // *****************************************************************************************
    // *****************************************************************************************
    // *****************************************************************************************
    // ***********************udpate play quizes data start*************************************

    public function quizUpdatePlayData(Request $request, Quiz $quiz)
    {
        $result = app(\App\Interfaces\api\QuizWinnerRepositoryInterface::class)->quizUpdatePlayData($quiz);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
    //************udpate play quizes data end */

    /**
     * Send / Activate WhatsApp notifications for a specific quiz immediately.
     */
    public function sendQuizNotifications(Request $request, Quiz $quiz, WhatsAppRepositoryInterface $whatsAppRepository)
    {
        try {
            $now = now();

            // 1. If no entries exist yet for this quiz, auto-prepare them for active users with mobile
            $existingCount = QuizNotification::withTrashed()->where('quiz_id', $quiz->id)->count();
            if ($existingCount === 0) {
                $users = User::where('is_active', 1)
                    ->whereNotNull('mobile')
                    ->where('mobile', '!=', '')
                    ->get(['id', 'name', 'tenant_id']);

                if ($users->isNotEmpty()) {
                    $insertData = [];
                    foreach ($users as $user) {
                        $insertData[] = [
                            'quiz_id' => $quiz->id,
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'quiz_name' => $quiz->title ?? 'Quiz',
                            'quiz_schedule_time' => $quiz->scheduled_at,
                            'notificationactiv_date_time' => $now,
                            'status' => 'pending',
                            'msg' => null,
                            'sent_date_time' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    $uniqueInsertData = collect($insertData)->unique('user_id')->values()->all();
                    foreach (array_chunk($uniqueInsertData, 500) as $chunk) {
                        QuizNotification::insertOrIgnore($chunk);
                    }
                }
            }

            // 2. Update notificationactiv_date_time and updated_at for all pending records of this quiz
            QuizNotification::where('quiz_id', $quiz->id)
                ->where('status', 'pending')
                ->update([
                    'notificationactiv_date_time' => $now,
                    'updated_at' => $now,
                ]);

            // 3. Fetch all pending records with user and quiz relations
            $pendingNotifications = QuizNotification::where('quiz_id', $quiz->id)
                ->where('status', 'pending')
                ->whereNull('sent_date_time')
                ->with([
                    'user:id,name,mobile,tenant_id',
                    'quiz:id,title,tenant_id'
                ])
                ->get();

            if ($pendingNotifications->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Is quiz ke liye koi pending notification nahi mili (sab pehle se bhej diye gaye hain).'
                ]);
            }

            $pendingIds = $pendingNotifications->pluck('id')->toArray();

            // ATOMIC CLAIM: Mark sent_date_time immediately so background cron or another click cannot double-fetch
            QuizNotification::whereIn('id', $pendingIds)
                ->where('status', 'pending')
                ->whereNull('sent_date_time')
                ->update([
                    'sent_date_time' => $now,
                    'updated_at' => $now,
                ]);

            $batchMessages = [];
            $missingMobileIds = [];

            foreach ($pendingNotifications as $notification) {
                $user = $notification->user;
                $mobileNumber = $user ? $user->mobile : null;

                if (empty($mobileNumber)) {
                    $missingMobileIds[] = $notification->id;
                    continue;
                }

                $userName = $notification->user_name 
                    ?: ($user->name ?? $user->mobile ?? 'User');

                $quizName = $notification->quiz_name 
                    ?: ($notification->quiz->title ?? 'Quiz');

                $scheduleTime = $notification->quiz_schedule_time ?? $notification->quiz->scheduled_at ?? $quiz->scheduled_at ?? now();
                $quizTime = Carbon::parse($scheduleTime)->format('g:i A');

                $tenantId = $notification->quiz->tenant_id ?? $user->tenant_id ?? 1;

                $quizId = $notification->quiz_id ?: $quiz->id;
                $quizLinkSuffix = $quizId . '?auto_join=1';

                $batchMessages[] = [
                    'id' => $notification->id,
                    'mobileNumber' => $mobileNumber,
                    'templateCode' => 'Quiz_Joined_Alert',
                    'bodyParameters' => [
                        ['name' => 'user_name', 'value' => $userName],
                        ['name' => 'quiz_name', 'value' => $quizName],
                        ['name' => 'quiz_time', 'value' => $quizTime],
                        ['name' => 'quiz_link', 'value' => $quizLinkSuffix],
                    ],
                    'buttonParameters' => [],
                    'userId' => $user->id,
                    'tenantId' => $tenantId,
                ];
            }

            // Mark missing mobile numbers as failed
            if (!empty($missingMobileIds)) {
                QuizNotification::whereIn('id', $missingMobileIds)->update([
                    'status' => 'failed',
                    'msg' => 'User mobile number missing',
                    'sent_date_time' => $now,
                    'updated_at' => $now,
                ]);
            }

            $totalSent = 0;
            $totalFailed = count($missingMobileIds);

            // Send in parallel batch
            if (!empty($batchMessages)) {
                $results = $whatsAppRepository->sendBatchWhatsAppMessages($batchMessages);

                $successIds = [];
                $failedIds = [];

                foreach ($results as $notificationId => $result) {
                    if ($result['success'] ?? false) {
                        $successIds[] = $notificationId;
                    } else {
                        $failedIds[] = $notificationId;
                    }
                }

                if (!empty($successIds)) {
                    QuizNotification::whereIn('id', $successIds)->update([
                        'status' => 'Sent',
                        'msg' => 'WhatsApp message sent successfully',
                        'sent_date_time' => $now,
                        'updated_at' => $now,
                    ]);
                    $totalSent = count($successIds);
                }

                if (!empty($failedIds)) {
                    QuizNotification::whereIn('id', $failedIds)->update([
                        'status' => 'failed',
                        'msg' => 'WhatsApp API error / failed response',
                        'sent_date_time' => $now,
                        'updated_at' => $now,
                    ]);
                    $totalFailed += count($failedIds);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "WhatsApp notifications successfully process ho gaye! Total: " . ($totalSent + $totalFailed) . " (Sent: {$totalSent}, Failed: {$totalFailed})",
                'data' => [
                    'quiz_id' => $quiz->id,
                    'quiz_title' => $quiz->title,
                    'total' => $totalSent + $totalFailed,
                    'sent' => $totalSent,
                    'failed' => $totalFailed,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('sendQuizNotifications failed: ' . $e->getMessage(), [
                'quiz_id' => $quiz->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notification bhejne me error: ' . $e->getMessage()
            ], 500);
        }
    }


    // **********duplicate controller**************

    public function duplicateQuiz(Request $request, Quiz $quiz)
    {
        $request->validate([
            'new_scheduled_at' => 'required|date|after:now',
        ]);

        $newScheduledAt = Carbon::parse($request->new_scheduled_at);
        
        // Original quiz ka question limit lein (fallback 10)
        $questionLimit = (int) ($quiz->question_limit ?? 10);

        // ==========================================
        // 1. ROBUST Time Slot Conflict Check 
        // (Hum existing checkSlot method ko reuse kar rahe hain)
        // ==========================================
        $fakeRequest = new Request();
        $fakeRequest->merge([
            'scheduled_at'   => $newScheduledAt->toDateTimeString(),
            'question_limit' => $questionLimit,
            'quiz_id'        => null, // ❗ IMPORTANT: Null pass karein taaki ye ALL quizzes ke against check kare (kyunki ye ek naya quiz hai)
        ]);

        // checkSlot method ko call karein
        $slotCheckResponse = $this->checkSlot($fakeRequest);
        $slotCheckData = json_decode($slotCheckResponse->getContent(), true);

        // Agar status false hai, matlab slot conflict hai (409)
        if ($slotCheckData['status'] === false) {
            return response()->json([
                'success' => false,
                'message' => $slotCheckData['message'] ?? '⚠️ Schedule Conflict: This time slot overlaps with another quiz.'
            ], 409);
        }

        // ==========================================
        // 2. Start Duplication Process (Transaction)
        // ==========================================
        DB::beginTransaction();
        try {
            // A. Duplicate Quiz Main Record
            $newQuiz = $quiz->replicate();
            $newQuiz->title = 'Copy of ' . $quiz->title;
            $newQuiz->scheduled_at = $newScheduledAt;
            $newQuiz->status = 'scheduled'; // Naya quiz draft/scheduled me rahega
            $newQuiz->complete_status = 'pending';

            if ($newQuiz->visibility !== 'practice') {
                $helper = getQuestionAvailableTime($newScheduledAt, $questionLimit, null,$newQuiz->visibility);
                if ($helper) {
                    $newQuiz->availability_start = $helper['available_start_time'];
                    $newQuiz->availability_end   = $helper['available_end_time'];
                }
            }

            $newQuiz->save();

            // Status table entry
            \App\Models\QuizStatus::firstOrCreate(
                [
                    'quiz_id'   => $newQuiz->id,
                    'tenant_id' => $newQuiz->tenant_id ?? 1,
                ],
                [
                    'total_enroll_user'           => 0,
                    'total_played_user'           => 0,
                    'total_complete_quiz_user'    => 0,
                    'total_correct_ans'           => 0,
                    'total_intrested_user'        => 0,
                    'correct_answer_average_time' => 0,
                    'status'                      => $newQuiz->complete_status ?? 'pending',
                ]
            );

            // B. Duplicate Questions with complete snapshot (question_text, options, correct_answer, etc.)
            $sourceQuestions = \App\Models\QuizQuestion::where('quiz_id', $quiz->id)->orderBy('order', 'asc')->get();
            if ($sourceQuestions->isEmpty()) {
                $sourceQuestions = $quiz->questions()->get();
            }

            foreach ($sourceQuestions as $sq) {
                $questionId = $sq->question_id ?? $sq->id;
                $masterQ = \App\Models\Question::find($questionId);

                // Options parsing
                $options = !empty($sq->options) ? $sq->options : ($masterQ ? $masterQ->options : []);
                if (is_string($options)) {
                    $options = json_decode($options, true) ?: [];
                }

                // Hindi options parsing
                $hindiOptions = !empty($sq->hindi_options) ? $sq->hindi_options : ($masterQ ? $masterQ->hindi_options : []);
                if (is_string($hindiOptions)) {
                    $hindiOptions = json_decode($hindiOptions, true) ?: [];
                }

                $order = $sq->order ?? ($sq->pivot->order ?? 1);
                $isSwitchQuestion = !empty($sq->is_switch_question) || !empty($sq->pivot->is_switch_question) ? 1 : 0;
                $botCorrectAnswer = $sq->bot_correct_answer ?? ($sq->pivot->bot_correct_answer ?? ($masterQ ? $masterQ->bot_correct_answer : 'no'));

                $questionText = $sq->question_text ?: ($masterQ ? $masterQ->question_text : null);
                $questionAudio = $sq->question_audio ?: ($sq->audio_file ?: ($masterQ ? ($masterQ->question_audio ?: $masterQ->audio_file) : null));
                $type = $sq->type ?: ($masterQ ? $masterQ->type : null);
                $mediaUrl = $sq->media_url ?: ($masterQ ? $masterQ->media_url : null);
                $correctAnswer = ($sq->correct_answer !== null && $sq->correct_answer !== '') ? $sq->correct_answer : ($masterQ ? $masterQ->correct_answer : null);
                $hindiQuestion = $sq->hindi_question ?: ($masterQ ? $masterQ->hindi_question : null);
                $hindiCorrectAnswer = ($sq->hindi_correct_answer !== null && $sq->hindi_correct_answer !== '') ? $sq->hindi_correct_answer : ($masterQ ? $masterQ->hindi_correct_answer : null);
                $audioFile = $sq->audio_file ?: ($masterQ ? $masterQ->audio_file : null);
                $explanation = $sq->explanation ?: ($masterQ ? $masterQ->explanation : null);
                $categoryId = $sq->category_id ?: ($masterQ ? $masterQ->category_id : null);
                $subCategory = $sq->sub_category ?: ($masterQ ? $masterQ->sub_category : null);
                $difficulty = $sq->difficulty ?: ($masterQ ? $masterQ->difficulty : null);
                $language = $sq->language ?: ($masterQ ? $masterQ->language : null);
                $timeLimitSeconds = $sq->time_limit_seconds ?: ($masterQ ? $masterQ->time_limit_seconds : null);
                $xpValue = $sq->xp_value ?: ($masterQ ? $masterQ->xp_value : null);
                $negativeMarking = $sq->negative_marking !== null ? $sq->negative_marking : ($masterQ ? $masterQ->negative_marking : null);
                $status = $sq->status ?: ($masterQ ? $masterQ->status : 'active');
                $tags = $sq->tags ?: ($masterQ ? $masterQ->tags : null);
                $source = $sq->source ?: ($masterQ ? $masterQ->source : null);
                $moderationStatus = $sq->moderation_status ?: ($masterQ ? $masterQ->moderation_status : null);

                $questionData = [
                    'tenant_id'            => $newQuiz->tenant_id ?? 1,
                    'quiz_id'              => $newQuiz->id,
                    'question_id'          => $questionId,
                    'order'                => $order,
                    'is_switch_question'   => $isSwitchQuestion,
                    'bot_correct_answer'   => $botCorrectAnswer,
                    'question_text'        => $questionText,
                    'question_audio'       => $questionAudio,
                    'type'                 => $type,
                    'media_url'            => $mediaUrl,
                    'options'              => $options,
                    'correct_answer'       => $correctAnswer,
                    'hindi_question'       => $hindiQuestion,
                    'hindi_options'        => $hindiOptions,
                    'hindi_correct_answer' => $hindiCorrectAnswer,
                    'audio_file'           => $audioFile,
                    'explanation'          => $explanation,
                    'category_id'          => $categoryId,
                    'sub_category'         => $subCategory,
                    'difficulty'           => $difficulty,
                    'language'             => $language,
                    'time_limit_seconds'   => $timeLimitSeconds,
                    'xp_value'             => $xpValue,
                    'negative_marking'     => $negativeMarking,
                    'status'               => $status,
                    'tags'                 => $tags,
                    'source'               => $source,
                    'moderation_status'    => $moderationStatus,
                ];

                // 1. Create in quiz_question
                \App\Models\QuizQuestion::create($questionData);
            }

            // Allocate question start and end times for the new quiz
            $this->allocateQuestionTimes($newQuiz);

            // C. Duplicate Rank Rewards
            if ($quiz->rankRewards && $quiz->rankRewards->count() > 0) {
                foreach ($quiz->rankRewards as $reward) {
                    $newReward = $reward->replicate();
                    $newReward->quiz_id = $newQuiz->id;
                    $newReward->save();
                }
            } else {
                $this->insertDefaultRewards($newQuiz->id);
            }

            // D. Duplicate Quiz Filter (Agar exist karta hai)
            if ($quiz->filter) {
                $newFilter = $quiz->filter->replicate();
                $newFilter->quiz_id = $newQuiz->id;
                $newFilter->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quiz duplicated successfully!',
                'redirect' => route('superadmin.quizzes.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quiz Duplication Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'quiz_id' => $quiz->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate quiz: ' . $e->getMessage()
            ], 500);
        }
    }

    // ***************whatsup before minute update code start*********
    public function updateWhatsAppBefore(Request $request, Quiz $quiz)
    {
        try {
            $request->validate([
                'whatsapp_message_sent_before_minutes' => 'required|integer|min:0|max:1440',
            ]);

            // Check if quiz has already started
            if ($quiz->scheduled_at && \Carbon\Carbon::parse($quiz->scheduled_at)->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update WhatsApp timing for a quiz that has already started.',
                ], 403);
            }

            $quiz->update([
                'whatsapp_message_sent_before_minutes' => $request->whatsapp_message_sent_before_minutes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp reminder time updated successfully!',
                'data' => [
                    'quiz_id' => $quiz->id,
                    'minutes' => $quiz->whatsapp_message_sent_before_minutes,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating WhatsApp timing: ' . $e->getMessage(),
            ], 500);
        }
    }
    // ***************whatsup before minute update code end***********
}
