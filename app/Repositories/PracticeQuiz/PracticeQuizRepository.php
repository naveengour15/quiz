<?php

namespace App\Repositories\PracticeQuiz;

use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\PracticeQuiz;
use App\Models\PracticeQuizQuestion;
use App\Models\PracticeQuizUser;
use App\Models\PracticeQuizUserAnswer;
use App\Models\QuizWinner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PracticeQuizRepository implements PracticeQuizRepositoryInterface
{
    protected function findQuiz(int $quizId)
    {
        $pq = PracticeQuiz::find($quizId);
        if ($pq) return $pq;
        return Quiz::withoutGlobalScopes()->find($quizId);
    }

    public function availableList($user): array
    {
        $tenantId = $user->tenant_id ?? 1;
        $pQuizzes = PracticeQuiz::with(['quizType'])
            ->whereIn('tenant_id', [$tenantId, 1])
            ->where('approve_status', 'approved')
            ->orderBy('id', 'asc')
            ->get();

        if ($pQuizzes->isEmpty()) {
            $pQuizzes = PracticeQuiz::with(['quizType'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->orderBy('id', 'asc')
                ->get();
        }

        if ($pQuizzes->isEmpty()) {
            $pQuizzes = Quiz::with(['quizType'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where('type', 'Practice')
                ->where('approve_status', 'approved')
                ->orderBy('id', 'asc')
                ->get();
        }

        if ($pQuizzes->isEmpty()) {
            $pQuizzes = Quiz::with(['quizType'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where(function ($q) {
                    $q->where('visibility', 'practice')
                      ->orWhereNull('visibility')
                      ->orWhere('visibility', 'public');
                })
                ->orderBy('id', 'asc')
                ->get();
        }

        $completedQuizIds = [];
        if ($user) {
            $completedQuizIds = PracticeQuizUser::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('status', 'completed')
                        ->orWhere('total_correct_answer', '>', 0)
                        ->orWhere('total_time', '>', 0);
                })
                ->pluck('quiz_id')
                ->toArray();

            $answeredQuizIds = PracticeQuizUserAnswer::where('user_id', $user->id)
                ->pluck('quiz_id')
                ->toArray();

            $completedQuizIds = array_unique(array_merge($completedQuizIds, $answeredQuizIds));
        }

        $formattedQuizzes = [];
        $previousCompleted = true; // First quiz is always unlocked
        foreach ($pQuizzes as $index => $quiz) {
            $isCompleted = in_array($quiz->id, $completedQuizIds);
            $isUnlocked = $previousCompleted;
            $previousCompleted = $isCompleted;

            $quiz->serial_number = $index + 1;
            $quiz->is_completed = $isCompleted;
            $quiz->is_unlocked = $isUnlocked;

            $formattedQuizzes[] = [
                'id'              => $quiz->id,
                'title'           => $quiz->title ?? ($quiz->name ?? 'Practice Module ' . ($index + 1)),
                'name'            => $quiz->name ?? ($quiz->title ?? 'Practice Module ' . ($index + 1)),
                'serial_number'   => $index + 1,
                'is_completed'    => $isCompleted,
                'is_unlocked'     => $isUnlocked,
                'total_questions' => (int) ($quiz->total_questions ?: ($quiz->question_limit ?: 10)),
                'banner_image'    => $quiz->banner_image ?? null,
                'created_at'      => $quiz->created_at,
            ];
        }

        $completedCount = count(array_intersect($pQuizzes->pluck('id')->toArray(), $completedQuizIds));
        $totalCount = $pQuizzes->count();

        return [
            'success'          => true,
            'practice_quizzes' => $formattedQuizzes,
            'completed_count'  => $completedCount,
            'total_count'      => $totalCount,
            'data'             => $formattedQuizzes,
        ];
    }

    public function detail($user, int $quizId): array
    {
        $quiz = $this->findQuiz($quizId);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $practiceUser = null;
        $hasPlayed = false;
        if ($user) {
            $practiceUser = PracticeQuizUser::where('quiz_id', $quizId)
                ->where('user_id', $user->id)
                ->first();

            if ($practiceUser && ($practiceUser->status === 'completed' || $practiceUser->total_correct_answer > 0 || $practiceUser->total_time > 0)) {
                $hasPlayed = true;
            } else {
                $hasPlayed = PracticeQuizUserAnswer::where('quiz_id', $quizId)
                    ->where('user_id', $user->id)
                    ->exists();
            }
        }

        return [
            'success'       => true,
            'quiz'          => $quiz,
            'practice_user' => $practiceUser,
            'has_played'    => $hasPlayed,
            'is_completed'  => $hasPlayed,
        ];
    }

    public function start($user, int $quizId): array
    {
        $quiz = $this->findQuiz($quizId);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $existing = PracticeQuizUser::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->first();

        $hasAnswers = PracticeQuizUserAnswer::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->exists();

        if (($existing && ($existing->status === 'completed' || $existing->total_correct_answer > 0 || $existing->total_time > 0)) || $hasAnswers) {
            return [
                'success'        => false,
                'message'        => 'You have already played this practice quiz and it is now locked.',
                'already_played' => true,
            ];
        }

        $standardQuestions = PracticeQuizQuestion::where(function($q) use ($quizId) {
                $q->where('practice_quiz_id', $quizId)->orWhere('question_id', $quizId);
            })
            ->where(function ($q) {
                $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
            })
            ->orderBy('order')
            ->get();

        if ($standardQuestions->count() === 0) {
            $standardQuestions = QuizQuestion::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
                })
                ->orderBy('order')
                ->get();
        }

        $totalQuestions = ($quiz->question_limit > 0) ? (int)$quiz->question_limit : $standardQuestions->count();
        if ($totalQuestions <= 0) {
            $totalQuestions = 10;
        }

        // Reset or create fresh practice session
        $practiceUser = PracticeQuizUser::updateOrCreate(
            [
                'quiz_id' => $quizId,
                'user_id' => $user->id,
            ],
            [
                'tenant_id'            => $quiz->tenant_id ?? 1,
                'status'               => 'active',
                'fifty_fifty'          => false,
                'switch_option'        => false,
                'question_id'          => null,
                'total_question'       => $totalQuestions,
                'total_correct_answer' => 0,
                'total_time'           => 0,
                'per_ans_average_time' => 0,
            ]
        );

        // Clear previous answers for this user & quiz practice session
        PracticeQuizUserAnswer::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->delete();

        return [
            'success'         => true,
            'quiz_id'         => $quizId,
            'total_questions' => $totalQuestions,
            'practice_user'   => $practiceUser,
            'lifelines'       => [
                'fifty_fifty'    => (bool)($quiz->fifty_fifty ?? true),
                'switch_option'  => (bool)($quiz->switch_option ?? true),
                'used_fifty'     => false,
                'used_switch'    => false,
                'used_lifeline'  => false,
            ],
        ];
    }

    public function getQuestion($user, int $quizId, array $payload): array
    {
        $quiz = $this->findQuiz($quizId);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $index = (int) ($payload['index'] ?? 0);
        $practiceUser = PracticeQuizUser::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->first();

        $hasSwitched = (bool) ($practiceUser->switch_option ?? false);
        $switchedReplacedQId = $hasSwitched ? ($practiceUser->question_id ?? null) : null;

        $standardQuestions = PracticeQuizQuestion::where(function($q) use ($quizId) {
                $q->where('practice_quiz_id', $quizId);
            })
            ->where(function ($q) {
                $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
            })
            ->orderBy('order')
            ->get();

        $switchQuestion = PracticeQuizQuestion::where(function($q) use ($quizId) {
                $q->where('practice_quiz_id', $quizId);
            })
            ->where('is_switch_question', 1)
            ->first();

        if ($standardQuestions->count() === 0) {
            $standardQuestions = QuizQuestion::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
                })
                ->orderBy('order')
                ->get();

            $switchQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('is_switch_question', 1)
                ->first();
        }

        $questions = [];
        foreach ($standardQuestions as $sq) {
            if ($hasSwitched && $switchedReplacedQId && $sq->id == $switchedReplacedQId && $switchQuestion) {
                $questions[] = $switchQuestion;
            } else {
                $questions[] = $sq;
            }
        }

        if (empty($questions) || !isset($questions[$index])) {
            return ['success' => false, 'message' => 'Question not found'];
        }

        $question = $questions[$index];
        $rawOpts = is_array($question->options) ? $question->options : (json_decode($question->options, true) ?: []);
        $hindiOpts = is_array($question->hindi_options) ? $question->hindi_options : (json_decode($question->hindi_options, true) ?: []);

        if (empty($hindiOpts) && is_object($question->question ?? null)) {
            $hRel = $question->question->hindi_options ?? null;
            $hindiOpts = is_array($hRel) ? $hRel : (json_decode($hRel, true) ?: []);
        }

        $mergedOpts = [];
        foreach ($rawOpts as $optIdx => $opt) {
            $optText = is_array($opt) ? ($opt['option_text'] ?? $opt['text'] ?? $opt['label'] ?? '') : (string) $opt;
            $optText = trim($optText);

            $hOptText = '';
            if (is_array($hindiOpts) && isset($hindiOpts[$optIdx])) {
                $hOpt = $hindiOpts[$optIdx];
                $hOptText = is_array($hOpt) ? ($hOpt['option_text'] ?? $hOpt['text'] ?? $hOpt['label'] ?? '') : (string) $hOpt;
                $hOptText = trim($hOptText);
            }

            if ($hOptText !== '' && strcasecmp($optText, $hOptText) !== 0) {
                $mergedOpts[] = $hOptText . ' (' . $optText . ')';
            } elseif ($hOptText !== '') {
                $mergedOpts[] = $hOptText;
            } else {
                $mergedOpts[] = $optText;
            }
        }
        $options = $this->shuffleOptions(!empty($mergedOpts) ? $mergedOpts : $rawOpts, ($user->id ?? 0) . '_' . ($question->id ?? 0));

        return [
            'success'         => true,
            'quiz_id'         => $quizId,
            'index'           => $index,
            'total_questions' => count($questions),
            'question'        => [
                'id'            => $question->id,
                'question_text' => $question->question_text,
                'hindi_question' => $question->hindi_question ?? ($question->question->hindi_question ?? null),
                'options'       => $options,
                'media'         => $question->media ?? $question->media_url ?? null,
                'question_audio' => $question->question_audio ?? ($question->question->question_audio ?? ($question->audio_file ?? null)),
            ],
            'lifelines'       => [
                'fifty_fifty'    => (bool)($quiz->fifty_fifty ?? true),
                'switch_option'  => (bool)($quiz->switch_option ?? true),
                'used_fifty'     => (bool)($practiceUser->fifty_fifty ?? false),
                'used_switch'    => (bool)($practiceUser->switch_option ?? false),
                'used_lifeline'  => (bool)($practiceUser->fifty_fifty || $practiceUser->switch_option),
            ],
        ];
    }

    public function useLifeline($user, int $quizId, string $lifeline, int $questionId): array
    {
        $quiz = $this->findQuiz($quizId);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz not found'];
        }

        $practiceUser = PracticeQuizUser::firstOrCreate([
            'quiz_id' => $quizId,
            'user_id' => $user->id,
        ], [
            'tenant_id' => $quiz->tenant_id ?? 1,
            'status'    => 'active',
        ]);

        if ($practiceUser->fifty_fifty || $practiceUser->switch_option) {
            return [
                'success' => false,
                'message' => 'You have already used a lifeline in this practice quiz.'
            ];
        }

        if ($lifeline === 'fifty_fifty') {
            $practiceUser->update(['fifty_fifty' => true]);
            $question = PracticeQuizQuestion::find($questionId) ?: QuizQuestion::find($questionId);
            if (!$question) {
                return ['success' => false, 'message' => 'Question not found'];
            }

            $rawOptions = is_array($question->options) ? $question->options : json_decode($question->options, true);
            $correct = trim((string)$question->correct_answer);

            $correctOpt = null;
            $wrongOpts = [];

            foreach ($rawOptions as $optIdx => $opt) {
                $val = is_array($opt) ? ($opt['value'] ?? $opt['text'] ?? '') : $opt;
                $valStr = trim((string)$val);

                $isMatch = false;
                if (strcasecmp($valStr, $correct) === 0) {
                    $isMatch = true;
                } else {
                    $cBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $correct));
                    $vBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $valStr));
                    preg_match('/\((.*?)\)/u', $correct, $cMatch);
                    preg_match('/\((.*?)\)/u', $valStr, $vMatch);
                    $cInner = isset($cMatch[1]) ? trim($cMatch[1]) : '';
                    $vInner = isset($vMatch[1]) ? trim($vMatch[1]) : '';

                    if (strcasecmp($cBase, $vBase) === 0 ||
                        ($cInner !== '' && strcasecmp($cInner, $valStr) === 0) ||
                        ($vInner !== '' && strcasecmp($vInner, $correct) === 0) ||
                        ($cInner !== '' && strcasecmp($cInner, $vBase) === 0) ||
                        ($vInner !== '' && strcasecmp($vInner, $cBase) === 0) ||
                        ($cInner !== '' && $vInner !== '' && strcasecmp($cInner, $vInner) === 0)
                    ) {
                        $isMatch = true;
                    }
                }

                if ($isMatch && $correctOpt === null) {
                    $correctOpt = $opt;
                } else {
                    $wrongOpts[] = $opt;
                }
            }

            if ($correctOpt === null && !empty($rawOptions)) {
                $correctOpt = $rawOptions[0];
                $wrongOpts = array_slice($rawOptions, 1);
            }

            shuffle($wrongOpts);
            $keptWrong = array_slice($wrongOpts, 0, 1);
            $removedOpts = array_slice($wrongOpts, 1);
            $remaining = array_merge([$correctOpt], $keptWrong);
            shuffle($remaining);

            $removedValues = [];
            foreach ($removedOpts as $ro) {
                $removedValues[] = is_array($ro) ? ($ro['value'] ?? $ro['text'] ?? '') : $ro;
            }

            return [
                'success'           => true,
                'lifeline'          => 'fifty_fifty',
                'remaining_options' => $remaining,
                'removed_options'   => $removedValues,
            ];
        }

        if ($lifeline === 'switch_option') {
            $switchQuestion = PracticeQuizQuestion::where(function($q) use ($quizId) {
                    $q->where('practice_quiz_id', $quizId);
                })
                ->where('is_switch_question', 1)
                ->first();

            if (!$switchQuestion) {
                $switchQuestion = QuizQuestion::where('quiz_id', $quizId)
                    ->where('is_switch_question', 1)
                    ->first();
            }

            if (!$switchQuestion) {
                return ['success' => false, 'message' => 'No switch question available for this quiz.'];
            }

            $practiceUser->update([
                'switch_option' => true,
                'question_id'   => $questionId,
            ]);

            $options = is_array($switchQuestion->options) ? $switchQuestion->options : json_decode($switchQuestion->options, true);
            $options = $this->shuffleOptions($options, ($user->id ?? 0) . '_' . ($switchQuestion->id ?? 0));

            return [
                'success'  => true,
                'lifeline' => 'switch_option',
                'question' => [
                    'id'            => $switchQuestion->id,
                    'question_text' => $switchQuestion->question_text,
                    'hindi_question' => $switchQuestion->hindi_question ?? ($switchQuestion->question->hindi_question ?? null),
                    'options'       => $options,
                    'media'         => $switchQuestion->media ?? $switchQuestion->media_url ?? null,
                    'question_audio' => $switchQuestion->question_audio ?? ($switchQuestion->question->question_audio ?? ($switchQuestion->audio_file ?? null)),
                ],
            ];
        }

        return ['success' => false, 'message' => 'Invalid lifeline selected'];
    }

    public function submitAnswer($user, int $quizId, array $payload): array
    {
        $questionId = (int) ($payload['question_id'] ?? 0);
        $answerVal  = $payload['answer'] ?? null;
        $totalTime  = (int) round((float) ($payload['total_time'] ?? 10));

        $showTime = !empty($payload['show_time']) ? Carbon::parse($payload['show_time']) : null;
        $ansTime  = !empty($payload['ans_time']) ? Carbon::parse($payload['ans_time']) : null;

        if ($showTime && $ansTime) {
            $diffInSeconds = (float) $showTime->diffInSeconds($ansTime, true);
            if ($diffInSeconds > 0) {
                $totalTime = (int) round($diffInSeconds);
            }
        } elseif (!$showTime && !$ansTime && $totalTime > 0) {
            $ansTime = Carbon::now();
            $showTime = (clone $ansTime)->subSeconds($totalTime);
        }

        $question = PracticeQuizQuestion::find($questionId) ?: QuizQuestion::find($questionId);
        if (!$question) {
            return ['success' => false, 'message' => 'Question not found'];
        }

        $isCorrect = false;
        if ($answerVal !== null) {
            $correct = trim((string)$question->correct_answer);
            $submitted = trim((string)$answerVal);
            if ($correct !== '' && $submitted !== '') {
                if (strcasecmp($correct, $submitted) === 0) {
                    $isCorrect = true;
                } else {
                    $cBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $correct));
                    $sBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $submitted));
                    preg_match('/\((.*?)\)/u', $correct, $cMatch);
                    preg_match('/\((.*?)\)/u', $submitted, $sMatch);
                    $cInner = isset($cMatch[1]) ? trim($cMatch[1]) : '';
                    $sInner = isset($sMatch[1]) ? trim($sMatch[1]) : '';

                    if (strcasecmp($cBase, $sBase) === 0 ||
                        ($cInner !== '' && strcasecmp($cInner, $submitted) === 0) ||
                        ($sInner !== '' && strcasecmp($sInner, $correct) === 0) ||
                        ($cInner !== '' && strcasecmp($cInner, $sBase) === 0) ||
                        ($sInner !== '' && strcasecmp($sInner, $cBase) === 0) ||
                        ($cInner !== '' && $sInner !== '' && strcasecmp($cInner, $sInner) === 0)
                    ) {
                        $isCorrect = true;
                    }
                }
            }
        }

        PracticeQuizUserAnswer::updateOrCreate(
            [
                'quiz_id'     => $quizId,
                'question_id' => $questionId,
                'user_id'     => $user->id,
            ],
            [
                'tenant_id'  => $question->tenant_id ?? 1,
                'answer'     => $answerVal,
                'status'     => $isCorrect ? 1 : 0,
                'show_time'  => $showTime,
                'ans_time'   => $ansTime,
                'total_time' => $totalTime,
            ]
        );

        // Update practice user progress DIRECTLY
        $userAnswers = PracticeQuizUserAnswer::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->get();

        $totalCorrect = $userAnswers->where('status', 1)->count();
        $sumTime = $userAnswers->sum('total_time');
        $avgTime = $userAnswers->count() > 0 ? intdiv($sumTime, $userAnswers->count()) : 0;

        $practiceUser = PracticeQuizUser::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->first();

        if ($practiceUser) {
            $practiceUser->update([
                'total_correct_answer' => $totalCorrect,
                'total_time'           => $sumTime,
                'per_ans_average_time' => $avgTime,
            ]);
        }

        return [
            'success'     => true,
            'is_correct'  => $isCorrect,
            'correct_ans' => $question->correct_answer,
            'explanation' => $question->explanation,
            'total_time'  => $totalTime,
            'show_time'   => $showTime ? $showTime->toDateTimeString() : null,
            'ans_time'    => $ansTime ? $ansTime->toDateTimeString() : null,
        ];
    }

    public function myResult($user, int $quizId): array
    {
        $practiceUser = PracticeQuizUser::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->first();

        if (!$practiceUser) {
            return ['success' => false, 'message' => 'No practice result found'];
        }

        $practiceUser->update(['status' => 'completed']);

        return [
            'success'              => true,
            'quiz_id'              => $quizId,
            'total_questions'      => $practiceUser->total_question,
            'total_correct_answer' => $practiceUser->total_correct_answer,
            'total_time'           => $practiceUser->total_time,
            'per_ans_average_time' => $practiceUser->per_ans_average_time,
        ];
    }

    public function answerSheet($user, int $quizId): array
    {
        $practiceUser = PracticeQuizUser::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->first();

        $hasSwitched = (bool) ($practiceUser->switch_option ?? false);
        $switchedReplacedQId = $hasSwitched ? ($practiceUser->question_id ?? null) : null;

        $standardQuestions = PracticeQuizQuestion::where(function($q) use ($quizId) {
                $q->where('practice_quiz_id', $quizId);
            })
            ->where(function ($q) {
                $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
            })
            ->orderBy('order')
            ->get();

        $switchQuestion = PracticeQuizQuestion::where(function($q) use ($quizId) {
                $q->where('practice_quiz_id', $quizId);
            })
            ->where('is_switch_question', 1)
            ->first();

        if ($standardQuestions->count() === 0) {
            $standardQuestions = QuizQuestion::where('quiz_id', $quizId)
                ->where(function ($q) {
                    $q->where('is_switch_question', 0)->orWhereNull('is_switch_question');
                })
                ->orderBy('order')
                ->get();

            $switchQuestion = QuizQuestion::where('quiz_id', $quizId)
                ->where('is_switch_question', 1)
                ->first();
        }

        $questionsToUse = [];
        foreach ($standardQuestions as $sq) {
            if ($hasSwitched && $switchedReplacedQId && $sq->id == $switchedReplacedQId && $switchQuestion) {
                $questionsToUse[] = $switchQuestion;
            } else {
                $questionsToUse[] = $sq;
            }
        }

        $userAnswers = PracticeQuizUserAnswer::where([
            'quiz_id' => $quizId,
            'user_id' => $user->id,
        ])->get()->keyBy('question_id');

        $data = [];
        $correct = 0;
        $totalTimeSum = 0;

        foreach ($questionsToUse as $question) {
            $rawOpts = is_array($question->options) ? $question->options : (json_decode($question->options, true) ?: []);
            $hindiOpts = is_array($question->hindi_options) ? $question->hindi_options : (json_decode($question->hindi_options, true) ?: []);

            if (empty($hindiOpts) && is_object($question->question ?? null)) {
                $hRel = $question->question->hindi_options ?? null;
                $hindiOpts = is_array($hRel) ? $hRel : (json_decode($hRel, true) ?: []);
            }

            $mergedOpts = [];
            foreach ($rawOpts as $optIdx => $opt) {
                $optText = is_array($opt) ? ($opt['option_text'] ?? $opt['text'] ?? $opt['label'] ?? '') : (string) $opt;
                $optText = trim($optText);

                $hOptText = '';
                if (is_array($hindiOpts) && isset($hindiOpts[$optIdx])) {
                    $hOpt = $hindiOpts[$optIdx];
                    $hOptText = is_array($hOpt) ? ($hOpt['option_text'] ?? $hOpt['text'] ?? $hOpt['label'] ?? '') : (string) $hOpt;
                    $hOptText = trim($hOptText);
                }

                if ($hOptText !== '' && strcasecmp($optText, $hOptText) !== 0) {
                    $mergedOpts[] = $hOptText . ' (' . $optText . ')';
                } elseif ($hOptText !== '') {
                    $mergedOpts[] = $hOptText;
                } else {
                    $mergedOpts[] = $optText;
                }
            }
            $finalOptions = !empty($mergedOpts) ? $mergedOpts : $rawOpts;

            $userAnswer = $userAnswers->get($question->id);
            $selectedAnswer = $userAnswer ? $userAnswer->answer : null;
            $qTime = $userAnswer ? (float) ($userAnswer->total_time ?? 0) : 0;
            $totalTimeSum += $qTime;

            $isCorrect = false;
            if ($userAnswer && $selectedAnswer !== null) {
                if ((int)$userAnswer->status === 1) {
                    $isCorrect = true;
                } else {
                    $correctStr = trim((string)$question->correct_answer);
                    $submittedStr = trim((string)$selectedAnswer);
                    if ($correctStr !== '' && $submittedStr !== '') {
                        if (strcasecmp($correctStr, $submittedStr) === 0) {
                            $isCorrect = true;
                        } else {
                            $cBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $correctStr));
                            $sBase = trim(preg_replace('/\s*\(.*?\)\s*$/u', '', $submittedStr));
                            preg_match('/\((.*?)\)/u', $correctStr, $cMatch);
                            preg_match('/\((.*?)\)/u', $submittedStr, $sMatch);
                            $cInner = isset($cMatch[1]) ? trim($cMatch[1]) : '';
                            $sInner = isset($sMatch[1]) ? trim($sMatch[1]) : '';

                            if (strcasecmp($cBase, $sBase) === 0 ||
                                ($cInner !== '' && strcasecmp($cInner, $submittedStr) === 0) ||
                                ($sInner !== '' && strcasecmp($sInner, $correctStr) === 0) ||
                                ($cInner !== '' && strcasecmp($cInner, $sBase) === 0) ||
                                ($sInner !== '' && strcasecmp($sInner, $cBase) === 0) ||
                                ($cInner !== '' && $sInner !== '' && strcasecmp($cInner, $sInner) === 0)
                            ) {
                                $isCorrect = true;
                            }
                        }
                    }
                }
            }

            if ($isCorrect) {
                $correct++;
            }

            $mediaUrl = $question->media_url ?? ($question->media ?? null);
            if (!empty($mediaUrl)) {
                $mediaUrl = trim((string) $mediaUrl);
                $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $mediaUrl);
                $cleanPath = ltrim($cleanPath, '/');
                $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
                if (!empty($awsUrl)) {
                    $mediaUrl = rtrim($awsUrl, '/') . '/' . $cleanPath;
                } else {
                    $mediaUrl = asset($mediaUrl);
                }
            } else {
                $mediaUrl = null;
            }

            $questionAudio = $question->question_audio ?: ($question->audio_file ?? null);
            if (!empty($questionAudio)) {
                $questionAudio = trim((string) $questionAudio);
                $cleanPath = preg_replace('/^https?:\/\/[^\/]+\//i', '', $questionAudio);
                $cleanPath = ltrim($cleanPath, '/');
                $awsUrl = env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com');
                if (!empty($awsUrl)) {
                    $questionAudio = rtrim($awsUrl, '/') . '/' . $cleanPath;
                } else {
                    $questionAudio = asset($questionAudio);
                }
            } else {
                $questionAudio = null;
            }

            $data[] = [
                'question_id'     => $question->id,
                'question'        => $question->question_text,
                'hindi_question'  => $question->hindi_question ?? ($question->question->hindi_question ?? null),
                'question_audio'  => $questionAudio,
                'media_url'       => $mediaUrl,
                'options'         => $finalOptions,
                'selected_answer' => $selectedAnswer,
                'correct_answer'  => $question->correct_answer,
                'is_correct'      => $isCorrect,
                'explanation'     => $question->explanation,
                'show_time'       => $userAnswer && $userAnswer->show_time ? Carbon::parse($userAnswer->show_time)->toDateTimeString() : null,
                'ans_time'        => $userAnswer && $userAnswer->ans_time ? Carbon::parse($userAnswer->ans_time)->toDateTimeString() : null,
                'total_time'      => $qTime,
            ];
        }

        $totalSheetCount = count($data);
        $userTotalTime = round($totalTimeSum, 1);

        if ($practiceUser) {
            $practiceUser->update([
                'status'               => 'completed',
                'total_correct_answer' => $correct,
                'total_time'           => $userTotalTime,
            ]);
        }

        // Fetch Connected Quiz & Rank #1 Winner from QuizWinner table
        $pq = PracticeQuiz::find($quizId);
        $connectedQuizId = $pq ? ($pq->quiz_id ?: $quizId) : $quizId;

        $firstRankWinner = QuizWinner::with('user')
            ->where('quiz_id', $connectedQuizId)
            ->where('rank', 1)
            ->first();

        if (!$firstRankWinner) {
            $firstRankWinner = QuizWinner::with('user')
                ->where('quiz_id', $connectedQuizId)
                ->orderByDesc('total_correct_answer')
                ->orderBy('total_time')
                ->first();
        }

        $winnerComparison = null;
        if ($firstRankWinner) {
            $winnerCorrect = (int) ($firstRankWinner->total_correct_answer ?? 0);
            $winnerTime = round((float) ($firstRankWinner->total_time ?? 0), 1);
            $winnerName = $firstRankWinner->user ? ($firstRankWinner->user->name ?? $firstRankWinner->user->user_name ?? 'Rank 1 Champion') : 'Rank 1 Champion';

            $isBetter = false;
            $isEqual = false;

            if ($correct > $winnerCorrect) {
                $isBetter = true;
            } elseif ($correct === $winnerCorrect) {
                if ($userTotalTime < $winnerTime) {
                    $isBetter = true;
                } elseif ($userTotalTime == $winnerTime) {
                    $isEqual = true;
                }
            }

            $isEqualOrBetter = ($isBetter || $isEqual);

            if ($isBetter) {
                $status = 'better';
                $message = "🔥 Outstanding! You beat the Rank #1 Winner's benchmark ({$correct} correct in {$userTotalTime}s vs {$winnerCorrect} correct in {$winnerTime}s)!";
            } elseif ($isEqual) {
                $status = 'equal';
                $message = "🏆 Incredible! You matched the Rank #1 Winner's exact score and time ({$correct} correct in {$userTotalTime}s)!";
            } else {
                $status = 'needs_improvement';
                if ($correct === $winnerCorrect) {
                    $diff = round($userTotalTime - $winnerTime, 1);
                    $message = "⚡ Very Close! Same score ({$correct} correct), but Rank #1 was {$diff}s faster. Keep practicing!";
                } else {
                    $diff = $winnerCorrect - $correct;
                    $message = "⚡ Keep Practicing! Rank #1 scored {$winnerCorrect} correct in {$winnerTime}s (Your: {$correct} correct in {$userTotalTime}s).";
                }
            }

            $winnerComparison = [
                'has_winner'          => true,
                'connected_quiz_id'   => $connectedQuizId,
                'winner_id'           => $firstRankWinner->id,
                'winner_name'         => $winnerName,
                'winner_rank'         => $firstRankWinner->rank ?? 1,
                'winner_correct_ans'  => $winnerCorrect,
                'winner_total_time'   => $winnerTime,
                'user_correct_ans'    => $correct,
                'user_total_time'     => $userTotalTime,
                'is_better'           => $isBetter,
                'is_equal'            => $isEqual,
                'is_equal_or_better'  => $isEqualOrBetter,
                'status'              => $status,
                'message'             => $message,
            ];
        }

        return [
            'success' => true,
            'result'  => [
                'total_questions'   => $totalSheetCount,
                'correct_answers'   => $correct,
                'wrong_answers'     => $totalSheetCount - $correct,
                'total_time'        => $userTotalTime,
                'winner_comparison' => $winnerComparison,
                'answers'           => $data,
            ]
        ];
    }

    protected function shuffleOptions($options, $seed = null)
    {
        if (!is_array($options)) {
            $options = json_decode($options, true) ?: [];
        }
        if (empty($options) || count($options) <= 1) {
            return $options;
        }

        if ($seed !== null) {
            mt_srand((int) crc32((string) $seed));
            $shuffled = array_values($options);
            for ($i = count($shuffled) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                $tmp = $shuffled[$i];
                $shuffled[$i] = $shuffled[$j];
                $shuffled[$j] = $tmp;
            }
            mt_srand();
            return $shuffled;
        }

        $shuffled = array_values($options);
        shuffle($shuffled);
        return $shuffled;
    }
}
