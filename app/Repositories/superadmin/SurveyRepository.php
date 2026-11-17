<?php

namespace App\Repositories\superadmin;

use App\Repositories\superadmin\SurveyRepositoryInterface;
use App\Models\ActionTrail;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyReward;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyRepository implements SurveyRepositoryInterface
{
    private function writeActionTrail($survey, string $action, array $details = [], array $oldDetails = [], string $status = 'success'): void
    {
        ActionTrail::create([
            'tenant_id'          => $survey->tenant_id ?? null,
            'survey_id'          => $survey->id,
            'user_id'            => auth()->id(),
            'table_name'         => 'surveys',
            'reference_id'       => $survey->id,
            'action'             => $action,
            'updated_by'         => auth()->id(),
            'updated_by_role_id' => auth()->user()?->role_id ?? null,
            'old_details'        => $oldDetails,
            'details'            => $details,
            'status'             => $status,
        ]);
    }

    public function searchTenant($search)
    {
        $query = trim((string) $search);

        $tenants = Tenant::query()
            ->select('id', 'name', 'phone')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return $tenants->map(function ($tenant) {
            return [
                'id'   => $tenant->id,
                'text' => $tenant->name . ' (' . $tenant->phone . ')',
            ];
        })->values();
    }

     public function index($request)
    {
        $editing = null;

        if ($request->filled('edit')) {
            $editing = Survey::with(['questions', 'rewards'])
                ->findOrFail($request->edit);
        }

        // $surveys = Survey::with(['questions', 'rewards'])
        //     ->latest()
        //     ->get();
        $perPage = $request->get('per_page', 10);
        $surveys = Survey::with(['questions', 'rewards'])
                ->withCount('questions') // Question Count
                ->addSelect([
                        'completed_count' => DB::table('survey_user_progress')
                            ->selectRaw('COUNT(*)')
                            ->whereColumn('survey_user_progress.survey_id', 'surveys.id')
                            ->where('survey_user_progress.status', 'completed')
                    ])
                ->withCount([
                    'answers as yes_count' => function ($q) {
                        $q->where('answer', 'yes');
                    },
                    'answers as no_count' => function ($q) {
                        $q->where('answer', 'no');
                    }
                      
                ])
            ->when($request->filled('search'), function ($query) use ($request) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        })
        ->when($request->filled('tenant_id'), function ($query) use ($request) {
            $query->where('tenant_id', $request->tenant_id);
        })
        ->when($request->filled('type'), function ($query) use ($request) {
            $query->where(
                'type',
                'like',
                '%' . trim($request->type) . '%'
            );
        })
        ->when($request->filled('reward_type'), function ($query) use ($request) {
            $query->where(
                'reward_type',
                $request->reward_type
            );
        })
        ->when($request->filled('status'), function ($query) use ($request) {
            $query->where(
                'status',
                $request->status
            );
        })->latest()->paginate($perPage);
        $tenants = Tenant::select('id','name','phone')->orderBy('name')->get();
            return compact('surveys', 'editing','tenants');
    }


        public function store(array $data)
    {
        DB::beginTransaction();

        try {

            // Survey Save
            $quizValue = !empty($data['quiz'])
                ? (int) $data['quiz']
                : null;

            $survey = Survey::create([
                'tenant_id'   => $data['tenant_id'] ?? 1,
                'name'        => $data['name'],
                'type'        => $data['type'],
                'reward_type' => $data['reward_type'],
                'point'       => $data['reward_type'] == 'point'
                                    ? $data['point']
                                    : null,
                'quiz'        => $quizValue,
                'status'      => 'active',
            ]);

            /* Questions */

            if (!empty($data['questions'])) {

               foreach ($data['questions'] as $index => $question) {

                if (trim($question) === '') {
                    continue;
                }

                SurveyQuestion::create([
                    'tenant_id'   => $data['tenant_id'] ?? 1,
                    'survey_id'   => $survey->id,
                    'question'    => $question,

                    // User entered values
                    // Default Yes / No
                    'yes'         => !empty($data['yes'][$index])
                                        ? trim($data['yes'][$index])
                                        : 'Yes',

                    'no'          => !empty($data['no'][$index])
                                        ? trim($data['no'][$index])
                                        : 'No',

                    // Internal answer key remains yes/no
                    'correct_ans' => 'yes',

                    'status'      => 'active',
                ]);
             }
            }

            $this->writeActionTrail($survey, 'create', $data, [], 'success');

            DB::commit();

            return [
                'status' => true,
                'message' => 'Survey created successfully.'
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Survey Create Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to create survey.'
            ];
        }
    }

   
        public function update($survey, array $data)
    {
        DB::beginTransaction();

        try {

            // Update Survey
            $quizValue = !empty($data['quiz'])
                ? (int) $data['quiz']
                : null;

            $oldDetails = $survey->getAttributes();

            $survey->update([
                'tenant_id'   => $data['tenant_id'] ?? 1,
                'name'        => $data['name'],
                'type'        => $data['type'],
                'reward_type' => $data['reward_type'],
                'point'       => $data['reward_type'] == 'point'
                                    ? $data['point']
                                    : null,
                'quiz'        => $quizValue,
            ]);

            $existingQuestions = SurveyQuestion::where('survey_id', $survey->id)
                ->orderBy('id')
                ->get();

            $questionIndex = 0;

            foreach ($data['questions'] ?? [] as $questionIndex => $questionText) {

                if (trim($questionText) === '') {
                    continue;
                }

                $existingQuestion = $existingQuestions[$questionIndex] ?? null;

                $yesText = !empty($data['yes'][$questionIndex])
                    ? trim($data['yes'][$questionIndex])
                    : 'Yes';

                $noText = !empty($data['no'][$questionIndex])
                    ? trim($data['no'][$questionIndex])
                    : 'No';

                if ($existingQuestion) {

                    $existingQuestion->update([
                        'tenant_id'   => $data['tenant_id'] ?? 1,
                        'question'    => $questionText,
                        'yes'         => $yesText,
                        'no'          => $noText,

                        // Keep internal answer key unchanged
                        'correct_ans' => 'yes',

                        'status'      => 'active',
                    ]);

                } else {

                    SurveyQuestion::create([
                        'tenant_id'   => $data['tenant_id'] ?? 1,
                        'survey_id'   => $survey->id,
                        'question'    => $questionText,
                        'yes'         => $yesText,
                        'no'          => $noText,
                        'correct_ans' => 'yes',
                        'status'      => 'active',
                    ]);
                }

                $questionIndex++;
            }

            if ($existingQuestions->count() > $questionIndex) {
                $existingQuestions->slice($questionIndex)->each(function ($question) {
                    $question->delete();
                });
            }

            $this->writeActionTrail($survey, 'update', $data, $oldDetails, 'success');

            DB::commit();

            return [
                'status'  => true,
                'message' => 'Survey updated successfully.'
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Survey Update Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return [
                'status'  => false,
                'message' => 'Unable to update survey.'
            ];
        }
    }

    public function destroy($survey)
    {
        DB::beginTransaction();

        try {

            $oldDetails = $survey->getAttributes();

            SurveyQuestion::where('survey_id', $survey->id)->delete();
            SurveyAnswer::where('survey_id', $survey->id)->delete();

            $survey->delete();

            $this->writeActionTrail($survey, 'delete', [], $oldDetails, 'success');

            DB::commit();

            return [
                'status'  => true,
                'message' => 'Survey deleted successfully.'
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Survey Delete Error', [
                'message' => $e->getMessage()
            ]);

            return [
                'status'  => false,
                'message' => 'Unable to delete survey.'
            ];
        }
    }

    public function report($survey)
    {
        try {
            $survey->loadMissing(['questions']);

            $questions = $survey->questions()->orderBy('id')->get();

            $report = $questions->map(function ($question) use ($survey) {
                $yesCount = SurveyAnswer::query()
                    ->where('survey_id', $survey->id)
                    ->where('question_id', $question->id)
                    ->where('answer', 'yes')
                    ->count();

                $noCount = SurveyAnswer::query()
                    ->where('survey_id', $survey->id)
                    ->where('question_id', $question->id)
                    ->where('answer', 'no')
                    ->count();

                return [
                    'question_id' => $question->id,
                    'question' => $question->question,
                    // 'yes_count' => (int) $yesCount,
                    // 'no_count' => (int) $noCount,

                     // Frontend display labels
                    'yes_label'   => $question->yes ?: 'Yes',
                    'no_label'    => $question->no ?: 'No',

                    // Internal counts remain yes/no
                    'yes_count'   => (int) $yesCount,
                    'no_count'    => (int) $noCount,
                ];
            })->values();

            return [
                'success' => true,
                'data' => [
                    'survey' => $survey,
                    'questions' => $report,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Survey Report Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to load survey report.',
            ];
        }
    }

    public function reportUsers($survey, int $questionId, string $answer)
    {
        
        try {
            $question = SurveyQuestion::query()
                ->where('survey_id', $survey->id)
                ->where('id', $questionId)
                ->first();

            if (!$question) {
                return [
                    'success' => false,
                    'message' => 'Question not found for this survey.',
                ];
            }

            $users = SurveyAnswer::query()
                ->with('user:id,name,email,mobile')
                ->where('survey_id', $survey->id)
                ->where('question_id', $questionId)
                ->where('answer', $answer)
                ->get()
                ->map(function ($answerRow) {
                    $user = $answerRow->user;

                    return [
                        'id' => $user?->id,
                        'name' => $user?->name ?? 'Unknown User',
                        'email' => $user?->email ?? null,
                        'mobile' => $user?->mobile ?? null,
                    ];
                })
                ->values();

            return [
                'success' => true,
                'data' => [
                    'question' => $question->question,
                    'answer' => $answer,
                    'answer_label' => $answer === 'yes'
                                    ? ($question->yes ?: 'Yes')
                                    : ($question->no ?: 'No'),
                    'users' => $users,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Survey Report Users Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to load user list.',
            ];
        }
    }

    public function changeStatus($survey)
    {
        try {

            $oldDetails = $survey->getAttributes();

            $survey->status = $survey->status == 'active'
                ? 'inactive'
                : 'active';

            $survey->save();

            $this->writeActionTrail(
                $survey,
                'status',
                ['status' => $survey->status],
                $oldDetails,
                'success'
            );

            return [
                'success' => true,
                'status'  => $survey->status,
            ];

        } catch (\Exception $e) {

            Log::error('Survey Status Error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
            ];
        }
    }


   

}