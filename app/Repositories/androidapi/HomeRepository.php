<?php

namespace App\Repositories\androidapi;

use App\Interfaces\androidapi\HomeRepositoryInterface;
use App\Models\Banner;
use App\Models\Leaderboard;
use App\Models\Quiz;
use App\Models\QuizWinner;
use App\Models\QuizUser;
use App\Models\User;
use App\Models\Category;
use App\Models\Survey;
use App\Models\Announcement;
use App\Models\AnnouncementHistory;
use App\Models\AppUpdation;
use App\Models\AppUpdationHistory;
use App\Models\PracticeQuiz;
use App\Models\PracticeQuizUser;
use App\Models\PracticeQuizUserAnswer;
use App\Models\UserWhatsappSubscribe;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeRepository implements HomeRepositoryInterface
{
    private function safeCache(string $key, int $ttl, callable $callback)
    {
        try {
            $value = Cache::get($key);
            if ($value !== null) {
                if ($value instanceof \__PHP_Incomplete_Class || (is_object($value) && !($value instanceof \Illuminate\Support\Collection))) {
                    Cache::forget($key);
                    $value = null;
                } else {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            Cache::forget($key);
        }

        $value = $callback();
        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable $e) {}
        return $value;
    }

    public function getHomeData(?User $user): array
    {
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;
        $userId = $user ? $user->id : 0;

        // User Account Stats
        $walletBalance = $user ? (float) ($user->wallet_balance ?? 0) : 0;
        $leaderboard = $userId ? Leaderboard::where('user_id', $userId)->where('status', 'active')->first() : null;
        $rank = $leaderboard?->rank ?? 0;
        $streak = $user ? (int) ($user->streak_count ?? 0) : 0;
        $totalWins = $userId ? QuizWinner::where('user_id', $userId)->count() : 0;
        $quizLimit = $user ? (int) ($user->available_quiz_limit ?? 0) : 0;
        $createQuizLimit = $user ? (int) ($user->create_quiz_limit ?? 0) : 0;
        $quizLeaves = $user ? (int) ($user->quiz_leave ?? 0) : 0;
        $lastQuizReward = 0;
        $lastSurveyPoints = 0;
        $lastSurveyQuizLimit = 0;

        if ($userId) {
            $lastQuizReward = (int) (QuizWinner::where('user_id', $userId)
                ->latest('id')
                ->value('reward') ?? 0);

            $lastSurveyReward = DB::table('survey_rewards')
                ->where('user_id', $userId)
                ->latest('id')
                ->first(['point', 'quiz']);
            $lastSurveyPoints = (int) ($lastSurveyReward?->point ?? 0);
            $lastSurveyQuizLimit = (int) ($lastSurveyReward?->quiz ?? 0);
        }

        // WhatsApp Subscribed Check
        $isWhatsappSubscribed = false;
        if ($userId) {
            $isWhatsappSubscribed = UserWhatsappSubscribe::where('user_id', $userId)
                ->where('status', 'subscribed')
                ->exists();
        }

        // Banners
        $rawBanners = $this->safeCache("fsr_banners_{$tenantId}", 60, function () {
            return Banner::where('status', 'active')->latest()->get();
        });

        $topQuizPlayers = $this->safeCache("fsr_top_quiz_players_{$tenantId}", 1800, function () use ($tenantId) {
            $topStats = DB::table('quiz_question_user_answers')
                ->where('status', 1)
                ->whereIn('tenant_id', [$tenantId, 1])
                ->select('user_id', DB::raw('COUNT(*) as correct_answers_count'))
                ->groupBy('user_id')
                ->orderByDesc('correct_answers_count')
                ->take(5)
                ->get();

            if ($topStats->isEmpty()) {
                return [];
            }

            $userIds = $topStats->pluck('user_id')->toArray();
            $users = User::whereIn('id', $userIds)
                ->withCount('quizUsers as quiz_count')
                ->get()
                ->keyBy('id');

            return $topStats->map(function ($stat) use ($users) {
                $u = $users->get($stat->user_id);
                return [
                    'name' => $u?->name ?? 'User',
                    'correct_answers_count' => (int) $stat->correct_answers_count,
                    'quiz_count' => (int) ($u?->quiz_count ?? 0),
                ];
            })->toArray();
        });

        $homeHistories = collect();
        if ($userId) {
            $homeHistories = Quiz::query()
                ->where('complete_status', 'completed')
                ->where(function ($query) use ($userId) {
                    $query->whereHas('quizUsers', fn ($sub) => $sub->where('user_id', $userId))
                          ->orWhereHas('tempQuizUsers', fn ($sub) => $sub->where('user_id', $userId));
                })
                ->with([
                    'category:id,name,icon',
                    'quizUsers' => fn ($q) => $q->where('user_id', $userId),
                    'tempQuizUsers' => fn ($q) => $q->where('user_id', $userId),
                ])
                ->withCount(['quizUsers', 'tempQuizUsers'])
                ->latest('updated_at')
                ->take(3)
                ->get()
                ->map(function ($quiz) {
                    $userResult = $quiz->quizUsers->first() ?? $quiz->tempQuizUsers->first();
                    $totalQuestion = (int) ($userResult->total_question ?? 0);
                    $correctAnswer = (int) ($userResult->total_correct_answer ?? 0);
                    $percentage = $totalQuestion > 0
                        ? (int) round(($correctAnswer / $totalQuestion) * 100)
                        : (int) ($userResult->total_score ?? 0);

                    $playedCount = max(1, (int) (($quiz->quiz_users_count ?? 0) + ($quiz->temp_quiz_users_count ?? 0)));

                    return [
                        'id' => $quiz->id,
                        'title' => $quiz->title ?? 'Quiz',
                        'category' => [
                            'name' => $quiz->category?->name ?? 'Category',
                            'icon' => $quiz->category?->icon ?? 'quiz',
                        ],
                        'category_name' => $quiz->category?->name ?? 'Category',
                        'category_icon' => $quiz->category?->icon ?? 'quiz',
                        'played_count' => $playedCount,
                        'quiz_users_count' => $playedCount,
                        'total_question' => $totalQuestion,
                        'total_correct_answer' => $correctAnswer,
                        'percentage' => $percentage,
                        'updated_at' => $quiz->updated_at?->toIso8601String(),
                    ];
                });
        }

        $lastQuizData = [
            'id' => null,
            'title' => 'No recent quiz',
            'complete_status' => null,
            'completed_at' => null,
            'completed_at_ms' => null,
            'winners' => [],
        ];
        $lastQuiz = Quiz::whereIn('tenant_id', [$tenantId, 1])
            ->whereIn('complete_status', ['completed', 'processing'])
            ->latest('updated_at')
            ->first();
        if ($lastQuiz && in_array($lastQuiz->complete_status, ['completed', 'processing'], true)) {
            $lastQuizData = [
                'id' => $lastQuiz->id,
                'title' => $lastQuiz->title ?? 'Quiz',
                'complete_status' => $lastQuiz->complete_status,
                'completed_at' => $lastQuiz->updated_at?->toIso8601String(),
                'completed_at_ms' => $lastQuiz->updated_at ? $lastQuiz->updated_at->timestamp * 1000 : null,
                'winners' => $lastQuiz->complete_status === 'completed'
                    ? QuizWinner::with('user:id,name,profile_photo')
                        ->where('quiz_id', $lastQuiz->id)
                        ->orderBy('rank')
                        ->take(5)
                        ->get()
                        ->map(fn ($winner) => [
                            'rank' => $winner->rank,
                            'user_name' => $winner->user?->name ?? 'User',
                            'profile_photo' => $winner->user?->profile_photo,
                            'total_correct_answer' => $winner->total_correct_answer,
                            'total_question' => $winner->total_question,
                            'total_time' => $winner->total_time,
                            'reward' => $winner->reward,
                            'reward_type' => $winner->reward_type,
                        ])->values()
                    : [],
            ];
        }

        $banners = $rawBanners->map(function ($b) {
            $imagePath = $b->banner_url ?: ($b->banner_file
                ? (str_starts_with($b->banner_file, 'http') ? $b->banner_file : rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') . '/' . ltrim($b->banner_file, '/'))
                : ($b->image ? (str_starts_with($b->image, 'http') ? $b->image : rtrim(env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'), '/') . '/' . ltrim($b->image, '/')) : null));

            $types = is_array($b->type) ? $b->type : (is_string($b->type) ? explode(',', $b->type) : []);

            return [
                'id' => $b->id,
                'title' => $b->title ?? $b->name ?? '',
                'name' => $b->name ?? $b->title ?? '',
                'image' => $imagePath,
                'banner_file' => $imagePath,
                'banner_url' => $imagePath,
                'link' => $b->link ?? $b->url ?? '',
                'url' => $b->url ?? $b->link ?? '',
                'type' => $types,
                'status' => $b->status ?? 'active',
            ];
        });

        // Today's Quizzes
        $todayQuizzesRaw = $this->safeCache("fsr_today_quizzes_{$tenantId}_" . today()->toDateString(), 30, function () use ($tenantId) {
            $tq = Quiz::with(['rankRewards.voucher', 'quizType', 'category'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where('approve_status', 'approved')
                ->where('complete_status', 'pending')
                ->where('scheduled_at', '>=', now()->subMinutes(15))
                ->orderBy('scheduled_at', 'asc')
                ->take(10)
                ->get();

            if ($tq->isEmpty()) {
                $tq = Quiz::with(['rankRewards.voucher', 'quizType', 'category'])
                    ->whereIn('tenant_id', [$tenantId, 1])
                    ->where('approve_status', 'approved')
                    ->where('complete_status', 'pending')
                    ->latest('id')
                    ->take(10)
                    ->get();
            }

            if ($tq->isEmpty()) {
                $tq = Quiz::with(['rankRewards.voucher', 'quizType', 'category'])
                    ->whereIn('tenant_id', [$tenantId, 1])
                    ->where('approve_status', 'approved')
                    ->latest('id')
                    ->take(10)
                    ->get();
            }

            return $tq;
        });

        // Categories
        $categoriesRaw = $this->safeCache("fsr_categories_{$tenantId}", 120, function () use ($tenantId) {
            return Category::whereIn('tenant_id', [$tenantId, 1])
                ->where('status', 'active')
                ->withCount('quizzes')
                ->orderBy('quizzes_count', 'desc')
                ->get();
        });

        $categories = $categoriesRaw->map(function ($c) {
            $icon = $c->image ? (str_starts_with($c->image, 'http') ? $c->image : asset($c->image)) : null;
            return [
                'id' => $c->id,
                'name' => $c->name ?? '',
                'icon' => $icon,
                'quizzes_count' => $c->quizzes_count ?? 0,
            ];
        });

        // Practice Quizzes
        $practiceQuizzesRaw = $this->safeCache("fsr_practice_quizzes_{$tenantId}", 60, function () use ($tenantId) {
            $pq = PracticeQuiz::with(['quizType'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where('approve_status', 'approved')
                ->orderBy('id', 'asc')
                ->get();

            if ($pq->isEmpty()) {
                $pq = PracticeQuiz::with(['quizType'])
                    ->whereIn('tenant_id', [$tenantId, 1])
                    ->orderBy('id', 'asc')
                    ->get();
            }

            return $pq;
        });

        // Surveys
        $surveysRaw = $this->safeCache("fsr_surveys_{$tenantId}", 60, function () use ($tenantId) {
            return Survey::whereIn('tenant_id', [$tenantId, 1])
                ->where('status', 'active')
                ->latest('id')
                ->get();
        });

        // User Joined & Survey Stats
        $joinedQuizIds = [];
        $notifiedQuizIds = [];
        $inactiveQuizIds = [];
        $surveysDone = 0;
        $surveyPoints = 0;
        $quizzesPlayed = 0;
        $completedSurveyIds = [];
        $startedSurveyIds = [];

        if ($userId) {
            $userSurveyStats = DB::table('survey_user_progress')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->select('survey_id', 'status')
                ->get();

            $completedSurveyIds = $userSurveyStats->where('status', 'completed')->pluck('survey_id')->toArray();
            $startedSurveyIds = $userSurveyStats->where('status', 'started')->pluck('survey_id')->toArray();
            $surveysDone = count($completedSurveyIds);

            $surveyPoints = (int) DB::table('survey_rewards')->where('user_id', $userId)->where('tenant_id', $tenantId)->sum('point');
            $quizzesPlayed = (int) DB::table('survey_rewards')->where('user_id', $userId)->where('tenant_id', $tenantId)->sum('quiz');

            $allQuizIds = collect()
                ->merge($todayQuizzesRaw ? $todayQuizzesRaw->pluck('id') : [])
                ->merge($practiceQuizzesRaw ? $practiceQuizzesRaw->pluck('id') : [])
                ->unique()
                ->toArray();

            if (!empty($allQuizIds)) {
                $joinedQuizIds = DB::table('quiz_users')
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereIn('quiz_id', $allQuizIds)
                    ->pluck('quiz_id')
                    ->toArray();

                $notifiedQuizIds = \App\Models\QuizNotification::where('user_id', $userId)
                    ->whereIn('quiz_id', $allQuizIds)
                    ->pluck('quiz_id')
                    ->toArray();
            }

            // Do not filter out quizzes when available_quiz_limit <= 0 so quiz cards remain visible with alert
            // if ($quizLimit <= 0) {
            //     $todayQuizzesRaw = $todayQuizzesRaw->filter(
            //         fn ($quiz) => in_array($quiz->id, $joinedQuizIds)
            //     )->values();
            // }
        }

        // Check for inactive quizzes (player limit reached)
        if ($todayQuizzesRaw) {
            foreach ($todayQuizzesRaw as $q) {
                if (!empty($q->no_of_players) && $q->no_of_players > 0) {
                    $joinedCount = DB::table('quiz_users')->where('quiz_id', $q->id)->where('status', 'active')->count();
                    if ($joinedCount >= $q->no_of_players) {
                        $inactiveQuizIds[] = $q->id;
                    }
                }
            }
        }

        // Sequential Lock Status for Practice Quizzes
        $completedPracticeQuizIds = [];
        if ($userId) {
            $completedPracticeQuizIds = PracticeQuizUser::where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('status', 'completed')
                        ->orWhere('total_correct_answer', '>', 0)
                        ->orWhere('total_time', '>', 0);
                })
                ->pluck('quiz_id')
                ->toArray();

            $answeredPracticeQuizIds = PracticeQuizUserAnswer::where('user_id', $userId)
                ->pluck('quiz_id')
                ->toArray();

            $completedPracticeQuizIds = array_unique(array_merge($completedPracticeQuizIds, $answeredPracticeQuizIds));
        }

        $practiceQuizzes = [];
        if ($practiceQuizzesRaw) {
            $previousCompleted = true;
            foreach ($practiceQuizzesRaw as $index => $q) {
                $isCompleted = in_array($q->id, $completedPracticeQuizIds);
                $isUnlocked = $previousCompleted;
                $previousCompleted = $isCompleted;

                $practiceQuizzes[] = [
                    'id' => $q->id,
                    'title' => $q->title ?? ($q->name ?? 'Practice Quiz'),
                    'serial_number' => $index + 1,
                    'is_completed' => $isCompleted,
                    'is_unlocked' => $isUnlocked,
                    'questions_count' => $q->total_questions ?? 10,
                ];
            }
        }

        // Format Quiz Card Mapper
        $formatQuizCard = function ($q) use ($joinedQuizIds, $notifiedQuizIds, $inactiveQuizIds) {
            $maxPoints = ($q->rankRewards && $q->rankRewards->count() > 0) 
                ? $q->rankRewards->max('reward') 
                : ($q->no_of_points ?? 500);

            $bannerImage = $q->banner_image ? (str_starts_with($q->banner_image, 'http') ? $q->banner_image : asset($q->banner_image)) : null;

            $rankRewards = ($q->rankRewards && $q->rankRewards->count() > 0)
                ? $q->rankRewards->sortBy('start_rank')->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'start_rank' => (int) $r->start_rank,
                        'end_rank' => (int) ($r->end_rank ?: $r->start_rank),
                        'reward' => (float) $r->reward,
                        'voucher_id' => $r->voucher_id,
                        'voucher_name' => $r->voucher?->name ?? null,
                    ];
                })->values()
                : [];

            $startTime = $q->scheduled_at ? Carbon::parse($q->scheduled_at)->timestamp : 0;
            $endTime = $q->availability_end ? Carbon::parse($q->availability_end)->timestamp : ($startTime ? $startTime + 15 * 60 : 0);

            return [
                'id' => $q->id,
                'title' => $q->title ?? 'Quiz',
                'description' => $q->description ?? '',
                'category_name' => $q->category?->name ?? 'General',
                'quiz_type_name' => $q->quizType?->name ?? 'Quiz',
                'banner_image' => $bannerImage,
                'visibility' => $q->visibility ?? 'public',
                'entry_fee' => (float) ($q->entry_fee ?? 0),
                'prize_points' => (int) $maxPoints,
                'no_of_players' => (int) ($q->no_of_players ?? 0),
                'joined_count' => (int) ($q->joined_count ?? 0),
                'scheduled_at' => $q->scheduled_at ? Carbon::parse($q->scheduled_at)->toIso8601String() : null,
                'scheduled_timestamp' => $startTime,
                'availability_end' => $q->availability_end ? Carbon::parse($q->availability_end)->toIso8601String() : null,
                'availability_end_timestamp' => $endTime,
                'end_time' => $endTime,
                'time_str' => $q->scheduled_at ? Carbon::parse($q->scheduled_at)->format('h:i A') : null,
                'is_joined' => in_array($q->id, $joinedQuizIds),
                'is_inactive' => in_array($q->id, $inactiveQuizIds),
                'is_notified' => in_array($q->id, $notifiedQuizIds),
                'is_private' => (bool) $q->is_private,
                'complete_status' => $q->complete_status ?? 'pending',
                'status' => $q->status ?? 'pending',
                'rank_rewards' => $rankRewards,
            ];
        };

        $todayQuizzes = $todayQuizzesRaw->filter(function ($q) {
            $isFinished = $q->complete_status === 'completed' ||
                ($q->availability_end && Carbon::parse($q->availability_end)->isPast()) ||
                (!$q->availability_end && $q->scheduled_at && Carbon::parse($q->scheduled_at)->addMinutes(15)->isPast());
            return !$isFinished;
        })->values()->map($formatQuizCard);

        // Surveys
        $surveys = $surveysRaw->reject(function ($survey) use ($completedSurveyIds) {
            return in_array($survey->id, $completedSurveyIds);
        })->map(function ($s) use ($startedSurveyIds) {
            $rawRewardType = strtolower($s->reward_type ?? '');
            $isQuiz = ($rawRewardType === 'quiz') || ($s->quiz && (int)$s->quiz > 0 && $rawRewardType !== 'point');
            $rewardType = $isQuiz ? 'quiz' : 'point';

            return [
                'id' => $s->id,
                'title' => $s->title ?? $s->name ?? 'Survey',
                'name' => $s->name ?? $s->title ?? 'Survey',
                'description' => $s->description ?? '',
                'point' => $s->point !== null ? (int) $s->point : null,
                'quiz' => $s->quiz !== null ? (int) $s->quiz : null,
                'reward_type' => $rewardType,
                'reward_points' => (int) ($s->points ?? $s->point ?? 50),
                'is_started' => in_array($s->id, $startedSurveyIds),
            ];
        });

            // Announcements
            $announcements = $this->safeCache("fsr_announcements_user_{$userId}", 300, function () use ($userId, $tenantId) {
                $closedAnnouncementIds = AnnouncementHistory::where('user_id', $userId)
                    ->pluck('announcement_id')
                    ->toArray();

                $announcementsRaw = Announcement::query()
                    ->whereNotIn('id', $closedAnnouncementIds)
                    ->where(function ($q) {
                        $q->whereNull('status')
                          ->orWhereIn('status', ['send', 'active', 'published', '1']);
                    })
                    ->where(function ($q) use ($userId, $tenantId) {
                        $q->where('recipient_type', 'all_user')
                          ->orWhere('recipient_type', 'all_retailer')
                          ->orWhereNull('recipient_type')
                          ->orWhere(function ($sub) use ($userId) {
                              $sub->where('recipient_type', 'individual_user')
                                  ->where(function ($uQuery) use ($userId) {
                                      $uQuery->whereJsonContains('user_id', (int) $userId)
                                             ->orWhereJsonContains('user_id', (string) $userId);
                                  });
                          })
                          ->orWhere(function ($sub) use ($tenantId) {
                              $sub->where('recipient_type', 'individual_retailer')
                                  ->where(function ($tQuery) use ($tenantId) {
                                      $tQuery->whereJsonContains('tenant_id', (int) $tenantId)
                                             ->orWhereJsonContains('tenant_id', (string) $tenantId);
                                  });
                          });
                    })
                    ->latest('id')
                    ->get();

                return $announcementsRaw->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'title' => $a->subject ?? $a->title ?? 'Announcement',
                        'subject' => $a->subject ?? $a->title ?? 'Announcement',
                        'message' => $a->message ?? $a->content ?? '',
                        'created_at' => $a->created_at ? $a->created_at->diffForHumans() : 'Just now',
                    ];
                });
            });

            // App Updations
            $appUpdations = $this->safeCache("fsr_appupdations_user_{$userId}", 300, function () use ($userId, $tenantId, $user) {
                $closedAppUpdationIds = AppUpdationHistory::where('user_id', $userId)
                    ->pluck('app_updation_id')
                    ->toArray();

                $isFirstTimeUser = ($user && $user->created_at && $user->created_at->diffInMinutes(now()) < 10);

                if ($isFirstTimeUser) {
                    $appUpdationsRaw = collect();
                } else {
                    $appUpdationsRaw = AppUpdation::query()
                        ->whereNotIn('id', $closedAppUpdationIds)
                        ->when($user && $user->created_at, function ($q) use ($user) {
                            $q->where('created_at', '>', $user->created_at);
                        })
                        ->where(function ($q) {
                            $q->whereNull('status')
                              ->orWhereIn('status', ['send', 'active', 'published', '1']);
                        })
                        ->where(function ($q) use ($userId, $tenantId) {
                            $q->where('recipient_type', 'all_user')
                              ->orWhere('recipient_type', 'all_retailer')
                              ->orWhereNull('recipient_type')
                              ->orWhere(function ($sub) use ($userId) {
                                  $sub->where('recipient_type', 'individual_user')
                                      ->where(function ($uQuery) use ($userId) {
                                          $uQuery->whereJsonContains('user_id', (int) $userId)
                                                 ->orWhereJsonContains('user_id', (string) $userId);
                                      });
                              })
                              ->orWhere(function ($sub) use ($tenantId) {
                                  $sub->where('recipient_type', 'individual_retailer')
                                      ->where(function ($tQuery) use ($tenantId) {
                                          $tQuery->whereJsonContains('tenant_id', (int) $tenantId)
                                                 ->orWhereJsonContains('tenant_id', (string) $tenantId);
                                      });
                              });
                        })
                        ->latest('id')
                        ->take(1)
                        ->get();
                }

                return $appUpdationsRaw->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'title' => $a->subject ?? 'App Update',
                        'subject' => $a->subject ?? 'App Update',
                        'message' => $a->message ?? '',
                        'created_at' => $a->created_at ? $a->created_at->diffForHumans() : 'Just now',
                    ];
                });
            });

        $profileUpdatePoint = (int) $this->safeCache("fsr_profile_update_point_{$tenantId}", 300, function () {
            return (int) (\App\Models\QuizSetting::value('profile_update_point') ?? 0);
        });

        return [
            'user_stats' => [
                'name' => $user?->name ?? '',
                'email' => $user?->email ?? '',
                'mobile' => $user?->mobile ?? '',
                'referral_code' => $user?->referral_code ?? '',
                'profile_status' => $user?->profile_status ?? 'pending',
                'profile_update_point' => $profileUpdatePoint,
                'show_profile_update_prompt' => (bool) ($user && !in_array(strtolower($user->profile_status ?? ''), ['complete', 'completed']) && $profileUpdatePoint > 0),
                'wallet_balance' => $walletBalance,
                'rank' => $rank,
                'streak' => $streak,
                'total_wins' => $totalWins,
                'available_quiz_limit' => $quizLimit,
                'surveys_done' => $surveysDone,
                'survey_points' => $surveyPoints,
                'quizzes_played' => $quizzesPlayed,
                'leader_board_point' => $user ? (float) ($user->leader_board_point ?? 0) : 0,
                'create_quiz_limit' => $createQuizLimit,
                'quiz_leave' => $quizLeaves,
                'remaining_quiz_limit' => $quizLimit,
                'quiz_quits' => $quizLeaves,
                'last_quiz_reward' => $lastQuizReward,
                'last_survey_points' => $lastSurveyPoints,
                'last_survey_quiz_limit' => $lastSurveyQuizLimit,
                'limits' => [
                    'available_quiz' => $quizLimit,
                    'create_quiz' => $createQuizLimit,
                    'quiz_leave' => $quizLeaves,
                ],
            ],
            'is_whatsapp_subscribed' => $isWhatsappSubscribed,
            'banners' => $banners,
            'today_quizzes' => $todayQuizzes,
            'practice_quizzes' => $practiceQuizzes,
            'joinedQuizIds' => $joinedQuizIds,
            'inactiveQuizIds' => $inactiveQuizIds,
            'notifiedQuizIds' => $notifiedQuizIds,
            'joined_quiz_ids' => $joinedQuizIds,
            'inactive_quiz_ids' => $inactiveQuizIds,
            'notified_quiz_ids' => $notifiedQuizIds,
            'categories' => $categories,
            'surveys' => $surveys,
            'top_quiz_players' => $topQuizPlayers,
            'histories' => $homeHistories,
            'histories_quiz' => $homeHistories,
            'last_quiz' => $lastQuizData,
            'announcements' => $announcements,
            'app_updations' => $appUpdations ?? [],
            'profile_update_point' => $profileUpdatePoint,
            'app_version_info' => [
                'latest_version' => \App\Models\Setting::where('key', 'android_app_version')->value('value') ?? '1.0.0',
                'latest_version_code' => (int) (\App\Models\Setting::where('key', 'android_app_version_code')->value('value') ?? 1),
                'update_url' => \App\Models\Setting::where('key', 'android_app_update_url')->value('value') ?? 'https://play.google.com/store/apps/details?id=com.chaloonline.fsrquiz',
                'play_store_url' => 'https://play.google.com/store/apps/details?id=com.chaloonline.fsrquiz',
                'apk_url' => 'https://fsrquiz.com/downloads/fsrquiz.apk',
                'force_update' => (bool) (\App\Models\Setting::where('key', 'android_app_force_update')->value('value') ?? false),
            ],
            'server_timestamp' => time(),
        ];
    }

    public function closeAnnouncement(int $userId, int $announcementId): array
    {
        $user = User::find($userId);
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;

        AnnouncementHistory::firstOrCreate(
            [
                'announcement_id' => $announcementId,
                'user_id' => $userId,
            ],
            [
                'tenant_id' => $tenantId,
                'status' => 'accept',
                'read_at' => now(),
            ]
        );

        Cache::forget("fsr_announcements_user_{$userId}");

        return [
            'status' => true,
            'message' => 'Announcement closed successfully.'
        ];
    }

    public function closeAppUpdation(int $userId, int $appUpdationId): array
    {
        $user = User::find($userId);
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;

        AppUpdationHistory::firstOrCreate(
            [
                'app_updation_id' => $appUpdationId,
                'user_id' => $userId,
            ],
            [
                'tenant_id' => $tenantId,
                'status' => 'accept',
                'read_at' => now(),
            ]
        );

        Cache::forget("fsr_appupdations_user_{$userId}");

        return [
            'status' => true,
            'message' => 'App updation closed successfully.'
        ];
    }

    public function subscribeWhatsappChannel(int $userId, int $tenantId): array
    {
        UserWhatsappSubscribe::updateOrCreate(
            [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ],
            [
                'status' => 'subscribed',
            ]
        );

        return [
            'status' => true,
            'success' => true,
            'message' => 'Successfully subscribed to WhatsApp channel.'
        ];
    }

    public function getQuizTimes(array $quizIds, int $tenantId): array
    {
        $cacheKey = "fsr_quiz_times_{$tenantId}_" . (empty($quizIds) ? 'all' : md5(implode(',', $quizIds)));

        $times = Cache::remember($cacheKey, 10, function () use ($tenantId, $quizIds) {
            if (empty($quizIds)) {
                $quizzes = Quiz::whereIn('tenant_id', [$tenantId, 1])
                    ->where('approve_status', 'approved')
                    ->where('complete_status', 'pending')
                    ->where('scheduled_at', '>=', now()->subMinutes(15))
                    ->orderBy('scheduled_at', 'asc')
                    ->take(20)
                    ->get(['id', 'scheduled_at', 'availability_end', 'status', 'complete_status']);
            } else {
                $quizzes = Quiz::whereIn('id', $quizIds)->get(['id', 'scheduled_at', 'availability_end', 'status', 'complete_status']);
            }

            $list = [];
            foreach ($quizzes as $quiz) {
                $startTime = $quiz->scheduled_at ? Carbon::parse($quiz->scheduled_at)->timestamp : 0;
                $endTime = $quiz->availability_end ? Carbon::parse($quiz->availability_end)->timestamp : ($startTime ? $startTime + 15 * 60 : 0);
                $list[$quiz->id] = [
                    'id' => $quiz->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'availability_end' => $quiz->availability_end ? Carbon::parse($quiz->availability_end)->toIso8601String() : null,
                    'status' => $quiz->status,
                    'complete_status' => $quiz->complete_status,
                ];
            }
            return $list;
        });

        $now = Carbon::now()->timestamp;

        foreach ($times as $id => $val) {
            $times[$id]['server_time'] = $now;
        }

        return [
            'status' => true,
            'success' => true,
            'data' => $times,
            'server_time' => $now
        ];
    }

    public function getLastQuizStatus(int $tenantId, ?int $quizId = null): array
    {
        $cacheKey = $quizId ? "android_last_quiz_status_t{$tenantId}_q{$quizId}" : "android_last_quiz_status_t{$tenantId}_latest";

        $cached = Cache::get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        if ($quizId) {
            $lastQuiz = Quiz::whereIn('tenant_id', [$tenantId, 1])->find($quizId);
        } else {
            $lastQuiz = Quiz::whereIn('tenant_id', [$tenantId, 1])
                ->whereIn('complete_status', ['completed', 'processing'])
                ->latest('updated_at')
                ->first();
        }

        if ($lastQuiz && $lastQuiz->complete_status === 'completed') {
            $completedTime = $lastQuiz->updated_at ? $lastQuiz->updated_at->timestamp : 0;
            if (!$quizId && (time() - $completedTime) > (30 * 60)) {
                $res = [
                    'id' => null,
                    'complete_status' => null,
                    'winners' => [],
                ];
                Cache::put($cacheKey, $res, 10);
                return $res;
            }
        }

        $winners = [];
        if ($lastQuiz && $lastQuiz->complete_status === 'completed') {
            $winners = QuizWinner::with('user:id,name,profile_photo')
                ->where('quiz_id', $lastQuiz->id)
                ->orderBy('rank')
                ->take(5)
                ->get()
                ->map(fn ($winner) => [
                    'rank' => $winner->rank,
                    'user_name' => $winner->user?->name ?? 'User',
                    'profile_photo' => $winner->user?->profile_photo,
                    'total_correct_answer' => $winner->total_correct_answer,
                    'total_question' => $winner->total_question,
                    'total_time' => $winner->total_time,
                    'reward' => $winner->reward,
                    'reward_type' => $winner->reward_type,
                ])->values()->toArray();
        }

        $res = [
            'id' => $lastQuiz ? $lastQuiz->id : null,
            'title' => $lastQuiz ? ($lastQuiz->title ?? 'Quiz') : null,
            'complete_status' => $lastQuiz ? ($lastQuiz->complete_status ?? 'processing') : null,
            'completed_at' => $lastQuiz?->updated_at?->toIso8601String(),
            'completed_at_ms' => $lastQuiz?->updated_at ? $lastQuiz->updated_at->timestamp * 1000 : null,
            'winners' => $winners,
        ];

        $ttl = ($lastQuiz && $lastQuiz->complete_status === 'completed') ? 3600 : 3;
        Cache::put($cacheKey, $res, $ttl);

        return $res;
    }

    /**
     * Handle banner click recording and resolve target redirect URL.
     */
    public function handleBannerClick(int $bannerId, string $type, ?User $user = null): ?string
    {
        $bannerData = Banner::find($bannerId);

        if (!$bannerData) {
            return null;
        }

        $userId = $user ? $user->id : null;
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;

        if (function_exists('recordBannerClick')) {
            recordBannerClick(
                $bannerData->id,
                $type,
                $bannerData->url,
                $userId,
                $tenantId
            );
        }

        return $bannerData->url ?: null;
    }

    /**
     * Track a banner view.
     */
    public function recordBannerView(int $bannerId, string $type, ?User $user = null): bool
    {
        $bannerData = Banner::where('status', 'active')->find($bannerId);

        if (!$bannerData) {
            return false;
        }

        $userId = $user ? $user->id : null;
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;

        if (function_exists('recordBannerView')) {
            recordBannerView(
                $bannerData->id,
                (string) $type,
                $userId,
                $tenantId
            );
        }

        return true;
    }
}
