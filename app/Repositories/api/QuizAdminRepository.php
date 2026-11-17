<?php

namespace App\Repositories\api;

use App\Interfaces\api\QuizAdminRepositoryInterface;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizFilter;
use App\Models\QuizQuestion;
use App\Models\QuizRankReward;
use App\Models\QuizStatus;
use Illuminate\Support\Facades\DB;

class QuizAdminRepository implements QuizAdminRepositoryInterface
{
    public function list(array $filters): array
    {
        $query = Quiz::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return ['success' => true, 'data' => $query->latest('id')->paginate(20)];
    }

    public function detail(int $quizId): array
    {
        $quiz = Quiz::find($quizId);

        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        return [
            'success'      => true,
            'quiz'         => $quiz,
            'questions'    => QuizQuestion::where('quiz_id', $quizId)->orderBy('order')->get(),
            'rank_rewards' => QuizRankReward::where('quiz_id', $quizId)->get(),
            'filters'      => QuizFilter::where('quiz_id', $quizId)->first(),
        ];
    }

    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $quiz = Quiz::create([
                'tenant_id'              => $data['tenant_id'] ?? 1,
                'title'                  => $data['title'],
                'category_id'            => $data['category_id'] ?? null,
                'sub_category_id'        => $data['sub_category_id'] ?? null,
                'level_id'               => $data['level_id'] ?? null,
                'type'                   => $data['type'] ?? null,
                'visibility'             => $data['visibility'] ?? 'public',
                'password'               => $data['password'] ?? null,
                'retake_allowed'         => $data['retake_allowed'] ?? 1,
                'max_attempts'           => $data['max_attempts'] ?? null,
                'pass_percentage'        => $data['pass_percentage'] ?? 60,
                'time_limit_minutes'     => $data['time_limit_minutes'] ?? null,
                'per_que_time_limit_sec' => $data['per_que_time_limit_sec'] ?? null,
                'question_limit'         => $data['question_limit'] ?? 0,
                'no_of_players'          => $data['no_of_players'] ?? 0,
                'total_reward_amount'    => $data['total_reward_amount'] ?? 0,
                'negative_marking'       => $data['negative_marking'] ?? 0,
                'fifty_fifty'            => $data['fifty_fifty'] ?? 0,
                'switch_option'          => $data['switch_option'] ?? 0,
                'availability_start'     => $data['availability_start'] ?? null,
                'availability_end'       => $data['availability_end'] ?? null,
                'scheduled_at'           => $data['scheduled_at'] ?? null,
                'status'                 => 'draft',
            ]);

            if (! empty($data['question_ids'])) {
                $this->attachQuestions($quiz->id, $data['question_ids'], $data['tenant_id'] ?? 1);
            }

            if (! empty($data['rank_rewards'])) {
                foreach ($data['rank_rewards'] as $reward) {
                    QuizRankReward::create(array_merge($reward, [
                        'tenant_id' => $data['tenant_id'] ?? 1,
                        'quiz_id'   => $quiz->id,
                    ]));
                }
            }

            QuizFilter::create(array_merge($data['filters'] ?? [], [
                'tenant_id' => $data['tenant_id'] ?? 1,
                'quiz_id'   => $quiz->id,
                'gender'    => $data['filters']['gender'] ?? 'all',
            ]));

            QuizStatus::create([
                'tenant_id' => $data['tenant_id'] ?? 1,
                'quiz_id'   => $quiz->id,
            ]);

            return [
                'success' => true,
                'message' => 'Quiz created successfully',
                'quiz'    => $quiz,
            ];
        });
    }

    public function update(int $quizId, array $data): array
    {
        $quiz = Quiz::find($quizId);

        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $quiz->fill($data);
        $quiz->save();

        return ['success' => true, 'message' => 'Quiz updated successfully', 'quiz' => $quiz];
    }

    public function delete(int $quizId): array
    {
        $quiz = Quiz::find($quizId);

        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $quiz->delete();

        return ['success' => true, 'message' => 'Quiz deleted successfully'];
    }

    public function setQuestions(int $quizId, array $questionIds): array
    {
        $quiz = Quiz::find($quizId);

        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $this->attachQuestions($quizId, $questionIds, $quiz->tenant_id ?? 1);

        return ['success' => true, 'message' => 'Questions added to quiz successfully'];
    }

    public function removeQuestion(int $quizId, int $questionId): array
    {
        QuizQuestion::where('quiz_id', $quizId)->where('question_id', $questionId)->delete();

        return ['success' => true, 'message' => 'Question removed from quiz'];
    }

    public function setRankRewards(int $quizId, array $reward): array
    {
        $quiz = Quiz::find($quizId);

        $rankReward = QuizRankReward::create(array_merge($reward, [
            'tenant_id' => $quiz->tenant_id ?? 1,
            'quiz_id'   => $quizId,
        ]));

        return ['success' => true, 'message' => 'Rank reward added', 'rank_reward' => $rankReward];
    }

    public function setFilters(int $quizId, array $filters): array
    {
        $quiz = Quiz::find($quizId);

        $quizFilter = QuizFilter::updateOrCreate(
            ['quiz_id' => $quizId],
            array_merge($filters, ['tenant_id' => $quiz->tenant_id ?? 1])
        );

        return ['success' => true, 'message' => 'Quiz filters updated', 'filters' => $quizFilter];
    }

    public function publish(int $quizId, array $data): array
    {
        $quiz = Quiz::find($quizId);

        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $quiz->status       = $data['status'] ?? 'published';
        $quiz->scheduled_at = $data['scheduled_at'] ?? $quiz->scheduled_at;
        $quiz->save();

        return ['success' => true, 'message' => 'Quiz status updated', 'quiz' => $quiz];
    }

    public function approve(int $quizId, string $status): array
    {
        $quiz = Quiz::find($quizId);

        if (! $quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $quiz->approve_status = $status;
        $quiz->save();

        return ['success' => true, 'message' => 'Quiz approval status updated'];
    }

    public function stats(int $quizId): array
    {
        $status = QuizStatus::where('quiz_id', $quizId)->first();

        return ['success' => true, 'stats' => $status];
    }

    /**
     * Copy master questions into the quiz_question pivot/snapshot table.
     */
    protected function attachQuestions(int $quizId, array $questionIds, int $tenantId): void
    {
        $existingMax = QuizQuestion::where('quiz_id', $quizId)->max('order') ?? 0;

        foreach ($questionIds as $index => $questionId) {
            $question = Question::find($questionId);

            if (! $question) {
                continue;
            }

            QuizQuestion::create([
                'tenant_id'          => $tenantId,
                'quiz_id'            => $quizId,
                'question_id'        => $question->id,
                'order'              => $existingMax + $index + 1,
                'question_text'      => $question->question_text,
                'type'               => $question->type,
                'media_url'          => $question->media_url,
                'options'            => $question->options,
                'correct_answer'     => $question->correct_answer,
                'bot_correct_answer' => $question->bot_correct_answer,
                'explanation'        => $question->explanation,
                'category_id'        => $question->category_id,
                'sub_category'       => $question->sub_category,
                'difficulty'         => $question->difficulty,
                'language'           => $question->language,
                'time_limit_seconds' => $question->time_limit_seconds,
                'xp_value'           => $question->xp_value,
                'negative_marking'   => $question->negative_marking,
                'status'             => 'published',
                'tags'               => $question->tags,
                'source'             => $question->source,
                'moderation_status'  => $question->moderation_status,
            ]);
        }
    }
}
