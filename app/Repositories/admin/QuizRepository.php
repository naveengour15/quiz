<?php

namespace App\Repositories\admin;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\State;
use App\Models\City;
use App\Repositories\admin\QuizRepositoryInterface;
  use Illuminate\Support\Collection;

class QuizRepository implements QuizRepositoryInterface
{
    protected $model;

    public function __construct(Quiz $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = [])
    {
        $query = $this->model->query();

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->latest()->paginate(10);
    }

    public function find(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $quiz = $this->find($id);
        $quiz->update($data);

        return $quiz;
    }

    public function delete(int $id)
    {
        $quiz = $this->find($id);

        return $quiz->delete();
    }

  

    // public function getQuestionBank_old(?Quiz $quiz = null)
    // {
    //     $tenantId = getTenantId();

    //     // 1. Query ko $query variable me ASSIGN karein (return NAHI karna hai abhi)
    //     $query = Question::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
    //         ->whereIn('status', ['published', 'active'])
    //         ->whereNotNull('correct_answer')
    //         ->when(auth('superadmin')->check(), function ($q) {
    //             $q->where('tenant_id', auth('superadmin')->user()->tenant_id);
    //         })
    //         ->withCount('quizzes')
    //         ->with(['quizzes' => function ($q) {
    //             $q->select('quizzes.id', 'quizzes.title') 
    //             ->orderBy('quizzes.id', 'desc')        
    //             ->limit(2);
    //         }]);
    //     if ($quiz !== null) {
    //         if (isset($quiz->visibility) && $quiz->visibility === 'fastest_finger_first') {
    //             $query->where('type', 'fastest_finger_first');
    //         } else {
    //             $query->where('type', '!=', 'fastest_finger_first');
    //         }
    //     }
    //     return $query->orderBy('id', 'desc')->get();
    // }

    public function getQuestionBank(?Quiz $quiz = null)
    {
        $tenantId = getTenantId();

        // 1. Query ko $query variable me ASSIGN karein
        $query = Question::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('status', ['published', 'active'])
            
            // ✅ STRICT LOGIC: Ya toh Pure English ho, Ya Fully Valid Hindi ho
            ->where(function ($q) {
                
                // Scenario 1: Pure English Question (Hindi khali hai, English valid hai)
                $q->where(function ($sub) {
                    $sub->where(function ($h) {
                        $h->whereNull('hindi_question')->orWhere('hindi_question', '');
                    })->whereNotNull('correct_answer')
                    ->where('correct_answer', '!=', '');
                })
                
                // Scenario 2: Fully Valid Hindi Question (Hindi Question AND Hindi Answer DONO bhare hue hain)
                ->orWhere(function ($sub) {
                    $sub->whereNotNull('hindi_question')
                        ->where('hindi_question', '!=', '')
                        ->whereNotNull('hindi_correct_answer')
                        ->where('hindi_correct_answer', '!=', '');
                });
                
            })
            
            ->when(auth('superadmin')->check(), function ($q) {
                $q->where('tenant_id', auth('superadmin')->user()->tenant_id);
            })
            ->withCount('quizzes')
            ->with(['quizzes' => function ($q) {
                $q->select('quizzes.id', 'quizzes.title') 
                ->orderBy('quizzes.id', 'desc')        
                ->limit(2);
            }]);

        // 2. Quiz specific filter
        if ($quiz !== null) {
            if (isset($quiz->visibility) && $quiz->visibility === 'fastest_finger_first') {
                $query->where('type', 'fastest_finger_first');
            } else {
                $query->where('type', '!=', 'fastest_finger_first');
            }
        }

        return $query->orderBy('id', 'desc')->get();
    }


    public function getSelectedQuestions(?Quiz $quiz): Collection
    {
            if (!$quiz) {
                return collect();
            }

            // return $quiz->questions()->get()->keyBy('id');
            if ($quiz->visibility === 'fastest_finger_first') {
            
            return $quiz->fffQuestions()->get()->keyBy('id');
            } else {
                
                return $quiz->questions()->get()->keyBy('id');
            }
    }

   
    // public function syncQuestions(
    //     Quiz $quiz,
    //     array $questionIds,
    //     array $orders = [],
    //     array $botAnswers = [],
    //     array $isSwitchQuestion = []
    // )
    // {
    //     $sync = [];

    //     $usedOrders = [];

    //     foreach ($questionIds as $questionId) {

    //         $question = Question::find($questionId);

    //         if (!$question) {
    //             continue;
    //         }

    //         $order = $orders[$questionId] ?? null;

    //         // Blank ya 0 hai to next available order
    //         if (empty($order) || $order <= 0) {

    //             $order = 1;

    //             while (in_array($order, $usedOrders)) {
    //                 $order++;
    //             }
    //         }

    //         // Duplicate order hai to next available order
    //         while (in_array($order, $usedOrders)) {
    //             $order++;
    //         }

    //         $usedOrders[] = $order;

    //         // $sync[$questionId] = [

    //         //     'order'              => $order,
    //         //     'bot_correct_answer' => $botAnswers[$questionId] ?? 'no',
    //         //     'is_switch_question' => isset($isSwitchQuestion[$questionId]) && (int)$isSwitchQuestion[$questionId] === 1 ? 1 : 0,

    //         //     'tenant_id'          => $question->tenant_id,
    //         //     'question_text'      => $question->question_text,
    //         //     'question_audio'         => $question->question_audio,
    //         //     'type'               => $question->type,
    //         //     'media_url'          => $question->media_url,
    //         //     'options'            => json_encode($question->options),
    //         //     'correct_answer'     => $question->correct_answer,
    //         //     'hindi_question'       => $question->hindi_question,
    //         //     'hindi_options' => json_encode($question->hindi_options),
    //         //     'hindi_correct_answer' => $question->hindi_correct_answer,

    //         //     'media_url'            => $question->media_url,
    //         //     'audio_file'           => $question->audio_file,
    //         //     'explanation'        => $question->explanation,
    //         //     'category_id'        => $question->category_id,
    //         //     'sub_category'       => $question->sub_category,
    //         //     'difficulty'         => $question->difficulty,
    //         //     'language'           => $question->language,
    //         //     'time_limit_seconds' => $question->time_limit_seconds,
    //         //     'xp_value'           => $question->xp_value,
    //         //     'negative_marking'   => $question->negative_marking,
    //         //     'status'             => $question->status,
    //         //     'tags'               => $question->tags,
    //         //     'source'             => $question->source,
    //         //     'moderation_status'  => $question->moderation_status,
    //         // ];

    //         // 1. ✅ BASE ARRAY: Ye fields DONO (Normal aur FFF) ke liye common hain
    //         $syncData = [
    //             // Order zaroori hai sorting ke liye
    //             'tenant_id'      => $question->tenant_id,
    //             'question_text'  => $question->question_text,
    //             'question_audio' => $question->question_audio,
    //             'type'           => $question->type,
    //             'media_url'      => $question->media_url,
    //             'options'        => json_encode($question->options),
    //             'correct_answer' => $question->correct_answer,
    //             'language'       => $question->language,
    //         ];

    //         // 2. ✅ EXTRA FIELDS: Ye sirf Normal Quiz ke liye add honge (FFF ke liye nahi)
    //         if ($quiz->visibility !== 'fastest_finger_first') {
    //              $syncData['order']         =                  $order;
    //             $syncData['bot_correct_answer']   = $botAnswers[$questionId] ?? 'no';
    //             $syncData['is_switch_question']   = isset($isSwitchQuestion[$questionId]) && (int)$isSwitchQuestion[$questionId] === 1 ? 1 : 0;
                
    //             $syncData['hindi_question']       = $question->hindi_question;
    //             $syncData['hindi_options']        = json_encode($question->hindi_options);
    //             $syncData['hindi_correct_answer'] = $question->hindi_correct_answer;
                
    //             $syncData['audio_file']           = $question->audio_file;
    //             $syncData['explanation']          = $question->explanation;
    //             $syncData['category_id']          = $question->category_id;
    //             $syncData['sub_category']         = $question->sub_category;
    //             $syncData['difficulty']           = $question->difficulty;
    //             $syncData['time_limit_seconds']   = $question->time_limit_seconds;
    //             $syncData['xp_value']             = $question->xp_value;
    //             $syncData['negative_marking']     = $question->negative_marking;
    //             $syncData['status']               = $question->status;
    //             $syncData['tags']                 = $question->tags;
    //             $syncData['source']               = $question->source;
    //             $syncData['moderation_status']    = $question->moderation_status;
    //         }
    //     }

    //     // $quiz->questions()->sync($sync);
    //     // 3. ✅ VISIBILITY CHECK: Sahi table me sync karo
    //     if ($quiz->visibility === 'fastest_finger_first') {
    //         // FFF table me sync karega (Sirf base fields ke sath)
    //         $quiz->fffQuestions()->sync($sync);
    //     } else {
    //         // Normal quiz table me sync karega (Base + Extra fields ke sath)
    //         $quiz->questions()->sync($sync);
    //     }
    // }

    public function syncQuestions(
        Quiz $quiz,
        array $questionIds,
        array $orders = [],
        array $botAnswers = [],
        array $isSwitchQuestion = []
    ) {
        $sync = [];
        $usedOrders = [];

        foreach ($questionIds as $questionId) {
            $question = Question::find($questionId);

            if (!$question) {
                continue;
            }

            $order = $orders[$questionId] ?? null;

            // Blank ya 0 hai to next available order
            if (empty($order) || $order <= 0) {
                $order = 1;
                while (in_array($order, $usedOrders)) {
                    $order++;
                }
            }

            // Duplicate order hai to next available order
            while (in_array($order, $usedOrders)) {
                $order++;
            }

            $usedOrders[] = $order;

            $syncData = [
                'tenant_id'            => $question->tenant_id,
                'order'                => $order,
                'bot_correct_answer'   => $botAnswers[$questionId] ?? 'no',
                'is_switch_question'   => isset($isSwitchQuestion[$questionId]) && (int)$isSwitchQuestion[$questionId] === 1 ? 1 : 0,
                'question_text'        => $question->question_text,
                'question_audio'       => $question->question_audio ?: $question->audio_file,
                'type'                 => $question->type,
                'media_url'            => $question->media_url,
                'options'              => is_array($question->options) ? json_encode($question->options) : $question->options,
                'correct_answer'       => $question->correct_answer,
                'hindi_question'       => $question->hindi_question,
                'hindi_options'        => is_array($question->hindi_options) ? json_encode($question->hindi_options) : $question->hindi_options,
                'hindi_correct_answer' => $question->hindi_correct_answer,
                'audio_file'           => $question->audio_file,
                'explanation'          => $question->explanation,
                'category_id'          => $question->category_id,
                'sub_category'         => $question->sub_category,
                'difficulty'           => $question->difficulty,
                'language'             => $question->language,
                'time_limit_seconds'   => $question->time_limit_seconds,
                'xp_value'             => $question->xp_value,
                'negative_marking'     => $question->negative_marking,
                'status'               => $question->status ?? 'active',
                'tags'                 => $question->tags,
                'source'               => $question->source,
                'moderation_status'    => $question->moderation_status,
            ];

            $sync[$questionId] = $syncData;
        }

        // 1. Sync into main quiz_question table
        $quiz->questions()->sync($sync);

        // Ensure QuizStatus is created in main quiz_status table for all quizzes
        \App\Models\QuizStatus::firstOrCreate(
            [
                'quiz_id'   => $quiz->id,
                'tenant_id' => $quiz->tenant_id ?? 1,
            ],
            [
                'status' => $quiz->complete_status ?? 'pending',
            ]
        );
    }


    public function getStates(): Collection
    {
        return State::where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function getCitiesByState(int $stateId): Collection
    {
        return City::where('state_id', $stateId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
    
}