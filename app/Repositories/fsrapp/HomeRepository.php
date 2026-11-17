<?php

namespace App\Repositories\fsrapp;

use App\Interfaces\fsrapp\HomeRepositoryInterface;
use App\Models\Banner;
use App\Models\Leaderboard;
use App\Models\Quiz;
use App\Models\QuizWinner;
use App\Models\User;
use App\Models\Category;
use App\Models\Survey;
use App\Models\PracticeQuiz;
use App\Models\PracticeQuizUser;
use App\Models\PracticeQuizUserAnswer;
use App\Models\QuizNotification;
use App\Models\Announcement;
use App\Models\AnnouncementHistory;
use App\Models\AppUpdation;
use App\Models\AppUpdationHistory;
use App\Models\UserWhatsappSubscribe;
use App\Http\Controllers\fsrapp\QuizesController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HomeRepository implements HomeRepositoryInterface
{
    /**
     * Cache helper with safety fallback against corrupted objects.
     */
    private function safeCache(string $key, int $ttl, callable $callback)
    {
        try {
            $value = Cache::get($key);
            if ($value !== null) {
                if ($this->hasIncompleteClass($value)) {
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

    /**
     * Check if a value or any nested item is an incomplete class.
     */
    private function hasIncompleteClass($value): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->hasIncompleteClass($item)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Clear active quiz session and user-level cache entries.
     */
    public function clearActiveQuizSessionAndCache(): void
    {
        $activeQuizId = Session::get('active_quiz_id');
        if ($activeQuizId) {
            $this->clearUserQuizSession((int) $activeQuizId);
        }

        $activeFffQuizId = Session::get('active_fff_quiz_id');
        if ($activeFffQuizId) {
            $this->clearUserQuizSession((int) $activeFffQuizId);
        }

        // Only scan session keys if active quiz was set
        if ($activeQuizId || $activeFffQuizId) {
            foreach (Session::all() as $key => $val) {
                if (is_string($key) && preg_match('/^(quiz|fff_quiz)_(\d+)_/', $key, $matches)) {
                    $qId = (int) $matches[2];
                    $this->clearUserQuizSession($qId);
                }
            }
        }
    }

    /**
     * Clear only current user's session data for a quiz without destroying shared questions cache.
     */
    private function clearUserQuizSession(int $id): void
    {
        $userId = Auth::id() ?: (session('fsr_user_id') ?? null);
        if ($userId) {
            Session::forget("quiz_{$id}_user_{$userId}_anssheet");
            Session::forget("quiz_{$id}_user_{$userId}_user_answers");
            Cache::forget("quiz_{$id}_user_{$userId}_anssheet");
            Cache::forget("quiz_{$id}_user_{$userId}_user_answers");
        }
        Session::forget("quiz_{$id}_questions");
        Session::forget("quiz_{$id}_switch_questions");
        Session::forget("quiz_{$id}_counter_start_ms");
        Session::forget("quiz_{$id}_status");
        Session::forget("quiz_{$id}_anssheet");
        Session::forget("quiz_{$id}_user_answers");
        Session::forget("quiz_{$id}_answers");
        Session::forget("quiz_{$id}_out_status");
        Session::forget("quiz_{$id}_out_remark");
        if (Session::get('active_quiz_id') == $id) {
            Session::forget('active_quiz_id');
        }
    }

    /**
     * Get all aggregated home screen data by orchestrating modular repository methods.
     */
    public function getHomeData(?User $user): array
    {
        $tenantId = $user ? ($user->tenant_id ?? 1) : 1;

        // 1. User stats & Leaderboard rank
        $userStats = $this->getUserAccountStats($user);
        $walletBalance = $userStats['walletBalance'];
        $leaderboard = $userStats['leaderboard'];
        $rank = $userStats['rank'];
        $streak = $userStats['streak'];
        $totalWins = $userStats['totalWins'];

        // 2. Tenant static / cached collections
        $banners = $this->getBanners($tenantId);
        $todayQuizzes = $this->getTodayQuizzes($tenantId);
        $megaTournaments = $this->getMegaTournaments($tenantId);
        $categories = $this->getCategories($tenantId);
        $practiceQuizzes = $this->getPracticeQuizzes($tenantId, $user);
        $surveys = $this->getSurveys($tenantId);
        $topPlayers = $this->getTopPlayers($tenantId);
        $topQuizPlayers = $this->getTopQuizPlayers($tenantId);

        // 3. Collect all unique Quiz IDs
        $allQuizIds = collect()
            ->merge($todayQuizzes ? $todayQuizzes->pluck('id') : [])
            ->merge($practiceQuizzes ? $practiceQuizzes->pluck('id') : [])
            ->merge($megaTournaments ? $megaTournaments->pluck('id') : [])
            ->unique()
            ->toArray();

        // 4. User engagement (surveys done, joined quiz IDs, etc.)
        $engagement = $this->getUserEngagementData($user, $tenantId, $allQuizIds);
        $surveysDone = $engagement['surveysDone'];
        $surveyPoints = $engagement['surveyPoints'];
        $quizzesPlayed = $engagement['quizzesPlayed'];
        $completedSurveyIds = $engagement['completedSurveyIds'];
        $startedSurveyIds = $engagement['startedSurveyIds'];
        $joinedQuizIds = $engagement['joinedQuizIds'];
        $inactiveQuizIds = $engagement['inactiveQuizIds'];
        $notifiedQuizIds = $engagement['notifiedQuizIds'];

        // Do not filter out quizzes if limit is 0 so the quiz card shows on home with red alert
        // if ($user && ($user->available_quiz_limit ?? 0) <= 0) {
        //     $todayQuizzes = $this->filterLimitExceededQuizzes($todayQuizzes, $joinedQuizIds);
        //     $megaTournaments = $this->filterLimitExceededQuizzes($megaTournaments, $joinedQuizIds);
        // }

        // 5. Build $allQuizzesArray lookup for Blade
        $allQuizzesArray = $this->buildQuizzesArray($joinedQuizIds, $todayQuizzes, $practiceQuizzes, $megaTournaments);

        // 6. Recent activity & Quiz histories
        $activityData = $this->getUserRecentActivity($user);
        $recentActivity = $activityData['recentActivity'];
        $histories_quiz = $activityData['histories_quiz'];

        // 7. Announcements & Automatic App Updates
        $announcements = $this->getUserAnnouncements($user, $tenantId);
        $appUpdations = $this->processAutomaticAppUpdation($user, $tenantId);

        // 8. Real-time last quiz data
        $lastQuizData = $this->getLastQuizData($tenantId);

        // 9. WhatsApp Channel subscription status
        $isWhatsappSubscribed = $this->isWhatsappSubscribed($user);

        return compact(
            'user',
            'walletBalance',
            'rank',
            'streak',
            'totalWins',
            'banners',
            'todayQuizzes',
            'megaTournaments',
            'categories',
            'practiceQuizzes',
            'surveys',
            'topPlayers',
            'recentActivity',
            'surveysDone',
            'surveyPoints',
            'quizzesPlayed',
            'completedSurveyIds',
            'startedSurveyIds',
            'joinedQuizIds',
            'inactiveQuizIds',
            'notifiedQuizIds',
            'allQuizzesArray',
            'histories_quiz',
            'announcements',
            'appUpdations',
            'leaderboard',
            'lastQuizData',
            'topQuizPlayers',
            'isWhatsappSubscribed'
        );
    }

    /**
     * Get user account statistics (wallet balance, rank, streak, total wins, leaderboard).
     */
    public function getUserAccountStats(?User $user): array
    {
        $userId = $user ? $user->id : 0;
        if (!$userId) {
            return [
                'walletBalance' => 0,
                'leaderboard' => null,
                'rank' => 0,
                'streak' => 0,
                'totalWins' => 0,
            ];
        }

        return $this->safeCache("fsr_user_stats_{$userId}", 60, function () use ($user, $userId) {
            $walletBalance = $user->wallet_balance ?? 0;
            $leaderboard = Leaderboard::where('user_id', $userId)->where('status', 'active')->first();
            $rank = $leaderboard?->rank ?? 0;
            $streak = $user->streak_count ?? 0;
            $totalWins = QuizWinner::where('user_id', $userId)->count();

            return [
                'walletBalance' => $walletBalance,
                'leaderboard' => null,
                'rank' => $rank,
                'streak' => $streak,
                'totalWins' => $totalWins,
            ];
        });
    }

    /**
     * Get cached active banners for tenant.
     */
    public function getBanners(int $tenantId)
    {
        return $this->safeCache("fsr_banners_{$tenantId}", 300, function () {
            return Banner::where('status', 'active')->latest()->get();
        });
    }

    /**
     * Get today's scheduled or approved quizzes.
     */
    public function getTodayQuizzes(int $tenantId)
    {
        return $this->safeCache("fsr_today_quizzes_{$tenantId}_" . today()->toDateString(), 45, function () use ($tenantId) {
            $tq = Quiz::with(['rankRewards', 'quizType'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where('approve_status', 'approved')
                ->where('complete_status', 'pending')
                ->where('scheduled_at', '>=', now()->subMinutes(15))
                ->orderBy('scheduled_at', 'asc')
                ->take(10)
                ->get();

            if ($tq->isEmpty()) {
                $tq = Quiz::with(['rankRewards', 'quizType'])
                    ->whereIn('tenant_id', [$tenantId, 1])
                    ->where('approve_status', 'approved')
                    ->where('complete_status', 'pending')
                    ->latest('id')
                    ->take(10)
                    ->get();
            }

            if ($tq->isEmpty()) {
                $tq = Quiz::with(['rankRewards', 'quizType'])
                    ->whereIn('tenant_id', [$tenantId, 1])
                    ->latest('id')
                    ->take(10)
                    ->get();
            }

            return $tq;
        });
    }

    /**
     * Get mega tournaments for leaderboard quizzes.
     */
    public function getMegaTournaments(int $tenantId)
    {
        return $this->safeCache("fsr_mega_tournaments_{$tenantId}", 120, function () use ($tenantId) {
            return Quiz::with(['rankRewards', 'quizType'])
                ->whereIn('tenant_id', [$tenantId, 1])
                ->where('type', 'Leaderboard')
                ->where('approve_status', 'approved')
                ->where('complete_status', 'pending')
                ->latest('id')
                ->get();
        });
    }

    /**
     * Get active categories with quizzes count.
     */
    public function getCategories(int $tenantId)
    {
        return $this->safeCache("fsr_categories_{$tenantId}", 600, function () use ($tenantId) {
            return Category::whereIn('tenant_id', [$tenantId, 1])
                ->where('status', 'active')
                ->withCount('quizzes')
                ->orderBy('quizzes_count', 'desc')
                ->get();
        });
    }

    /**
     * Get practice quizzes with sequential unlock status.
     */
    public function getPracticeQuizzes(int $tenantId, ?User $user)
    {
        $practiceQuizzes = $this->safeCache("fsr_practice_quizzes_{$tenantId}", 300, function () use ($tenantId) {
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

        // Calculate sequential lock status for practice quizzes with per-user caching
        $completedPracticeQuizIds = [];
        if ($user) {
            $completedPracticeQuizIds = $this->safeCache("fsr_user_practice_done_{$user->id}", 60, function () use ($user) {
                $completed = PracticeQuizUser::where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->where('status', 'completed')
                            ->orWhere('total_correct_answer', '>', 0)
                            ->orWhere('total_time', '>', 0);
                    })
                    ->pluck('quiz_id')
                    ->toArray();

                $answered = PracticeQuizUserAnswer::where('user_id', $user->id)
                    ->pluck('quiz_id')
                    ->toArray();

                return array_unique(array_merge($completed, $answered));
            });
        }

        if ($practiceQuizzes) {
            $previousCompleted = true;
            foreach ($practiceQuizzes as $index => $quiz) {
                $quiz->serial_number = $index + 1;
                $quiz->is_completed = in_array($quiz->id, $completedPracticeQuizIds);
                $quiz->is_unlocked = $previousCompleted;
                $previousCompleted = $quiz->is_completed;
            }
        }

        return $practiceQuizzes;
    }

    /**
     * Get active surveys.
     */
    public function getSurveys(int $tenantId)
    {
        return $this->safeCache("fsr_surveys_{$tenantId}", 180, function () use ($tenantId) {
            return Survey::whereIn('tenant_id', [$tenantId, 1])
                ->where('status', 'active')
                ->latest('id')
                ->get();
        });
    }

    /**
     * Get top players from leaderboard podium.
     */
    public function getTopPlayers(int $tenantId)
    {
        return $this->safeCache("fsr_top_players_{$tenantId}", 60, function () use ($tenantId) {
            return Leaderboard::whereIn('tenant_id', [$tenantId, 1])
                ->where('status', 'active')
                ->orderBy('points', 'desc')
                ->take(5)
                ->get();
        });
    }

    /**
     * Get user survey stats and joined/notified quiz IDs.
     */
    public function getUserEngagementData(?User $user, int $tenantId, array $allQuizIds = []): array
    {
        $userId = $user ? $user->id : 0;
        if (!$userId) {
            return [
                'surveysDone' => 0,
                'surveyPoints' => 0,
                'quizzesPlayed' => 0,
                'completedSurveyIds' => [],
                'startedSurveyIds' => [],
                'joinedQuizIds' => [],
                'inactiveQuizIds' => [],
                'notifiedQuizIds' => []
            ];
        }

        $allQuizHash = empty($allQuizIds) ? 'none' : md5(implode(',', $allQuizIds));
        $cacheKey = "fsr_user_engagement_{$userId}_{$tenantId}_{$allQuizHash}";

        return $this->safeCache($cacheKey, 45, function () use ($userId, $tenantId, $allQuizIds) {
            $userSurveyStats = DB::table('survey_user_progress')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->select('survey_id', 'status')
                ->get();

            $completedSurveyIds = $userSurveyStats->where('status', 'completed')->pluck('survey_id')->toArray();
            $startedSurveyIds = $userSurveyStats->where('status', 'pending')->pluck('survey_id')->toArray();
            $surveysDone = count($completedSurveyIds);

            $surveyTotals = DB::table('survey_rewards')
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->selectRaw('COALESCE(SUM(point), 0) as total_points, COALESCE(SUM(quiz), 0) as total_quizzes')
                ->first();

            $surveyPoints = (int) ($surveyTotals?->total_points ?? 0);
            $quizzesPlayed = (int) ($surveyTotals?->total_quizzes ?? 0);

            $joinedQuizIds = [];
            $inactiveQuizIds = [];
            $notifiedQuizIds = [];

            if (!empty($allQuizIds)) {
                $quizUsers = DB::table('quiz_users')
                    ->where('user_id', $userId)
                    ->whereIn('quiz_id', $allQuizIds)
                    ->select('quiz_id', 'status')
                    ->get();

                $joinedQuizIds = $quizUsers->where('status', 'active')->pluck('quiz_id')->unique()->values()->toArray();
                $inactiveQuizIds = $quizUsers->where('status', 'inactive')->pluck('quiz_id')->unique()->values()->toArray();

                $notifiedQuizIds = QuizNotification::where('user_id', $userId)
                    ->whereIn('quiz_id', $allQuizIds)
                    ->pluck('quiz_id')
                    ->toArray();
            }

            return compact(
                'surveysDone',
                'surveyPoints',
                'quizzesPlayed',
                'completedSurveyIds',
                'startedSurveyIds',
                'joinedQuizIds',
                'inactiveQuizIds',
                'notifiedQuizIds'
            );
        });
    }

    /**
     * Filter quiz list if user quiz limit is exceeded.
     */
    private function filterLimitExceededQuizzes($quizzes, array $joinedQuizIds)
    {
        if ($quizzes) {
            $filtered = $quizzes->filter(function ($q) use ($joinedQuizIds) {
                return in_array($q->id, $joinedQuizIds);
            });
            if ($filtered->isNotEmpty()) {
                return $filtered;
            }
        }
        return $quizzes;
    }

    /**
     * Build indexed quizzes lookup array for Blade view.
     */
    public function buildQuizzesArray(array $joinedQuizIds, ...$quizLists): array
    {
        $allQuizzesArray = [];

        foreach ($quizLists as $list) {
            if ($list) {
                foreach ($list as $q) {
                    $isFinished = $q->complete_status == 'completed' || ($q->availability_end && Carbon::parse($q->availability_end)->isPast());
                    $hasJoined = in_array($q->id, $joinedQuizIds);

                    $maxPoints = ($q->rankRewards && $q->rankRewards->count() > 0)
                        ? $q->rankRewards->max('reward')
                        : ($q->no_of_points ?? '500');

                    $allQuizzesArray[$q->id] = [
                        'id' => $q->id,
                        'title' => $q->title ?? 'Quiz',
                        'desc' => $q->description ?? '',
                        'prize' => $maxPoints,
                        'fee' => $q->entry_fee ?? 0,
                        'joined' => $q->joined_count ?? 0,
                        'needsPassword' => (bool) $q->is_private,
                        'alreadyPlayed' => $isFinished,
                        'isJoined' => $hasJoined,
                        'startTimeStr' => $q->scheduled_at ?? $q->availability_start,
                    ];
                }
            }
        }

        return $allQuizzesArray;
    }

    /**
     * Get user recent winners activity and completed quiz history.
     */
    public function getUserRecentActivity(?User $user): array
    {
        $userId = $user ? $user->id : 0;
        if (!$userId) {
            return ['recentActivity' => collect(), 'histories_quiz' => collect()];
        }

        $recentActivity = $this->safeCache("fsr_user_recent_activity_{$userId}", 120, function () use ($userId) {
            $recentWinners = QuizWinner::where('user_id', $userId)
                ->select('id', 'quiz_id', 'user_id', 'reward', 'created_at')
                ->with('quiz:id,title')
                ->latest()
                ->take(5)
                ->get();

            return $recentWinners->map(function ($w) {
                return [
                    'quiz_title' => $w->quiz?->title ?? 'Quiz',
                    'status' => 'Completed',
                    'coins' => $w->reward ?? '0',
                    'created_at' => $w->created_at ? $w->created_at->diffForHumans() : 'Recently',
                ];
            });
        });

        // Do not cache histories_quiz in file cache to prevent __PHP_Incomplete_Class serialization issues
        $histories_quiz = Quiz::query()
            ->where('complete_status', 'completed')
            ->where(function ($query) use ($userId) {
                $query->whereHas('quizUsers', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId);
                })->orWhereHas('tempQuizUsers', function ($sub) use ($userId) {
                    $sub->where('user_id', $userId);
                });
            })
            ->select('id', 'title', 'category_id', 'complete_status', 'scheduled_at', 'availability_start', 'updated_at')
            ->with([
                'category', 
                'rankRewards', 
                'quizUsers' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'tempQuizUsers' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->withCount(['quizUsers', 'tempQuizUsers'])
            ->latest('updated_at')
            ->take(3)
            ->get();

        return compact('recentActivity', 'histories_quiz');
    }

    /**
     * Get active targeted announcements for user.
     */
    public function getUserAnnouncements(?User $user, int $tenantId)
    {
        $userId = $user ? $user->id : 0;
        if (!$userId) {
            return collect();
        }

        $cacheKey = "fsr_user_announcements_{$userId}_{$tenantId}";
        return $this->safeCache($cacheKey, 60, function () use ($userId, $tenantId) {
            $closedAnnouncementIds = AnnouncementHistory::where('user_id', $userId)
                ->pluck('announcement_id')
                ->toArray();

            return Announcement::query()
                ->whereNotIn('id', $closedAnnouncementIds)
                ->where('created_at', '>=', now()->subHours(24))
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
        });
    }

    /**
     * Get targeted app update popup data for user.
     */
    public function getUserAppUpdation(?User $user, int $tenantId)
    {
        $userId = $user ? $user->id : 0;
        if (!$userId || !$user) {
            return null;
        }

        $cacheKey = "fsr_user_app_updation_{$userId}_{$tenantId}";
        return $this->safeCache($cacheKey, 60, function () use ($user, $userId, $tenantId) {
            $closedAppUpdationIds = AppUpdationHistory::where('user_id', $userId)
                ->pluck('app_updation_id')
                ->toArray();

            // First time login user or user created recently should not see app update popups
            $isFirstTimeUser = ($user->created_at && $user->created_at->diffInMinutes(now()) < 10) || session('is_first_login', false);

            if ($isFirstTimeUser) {
                return null;
            }

            return AppUpdation::query()
                ->whereNotIn('id', $closedAppUpdationIds)
                ->when($user->created_at, function ($q) use ($user) {
                    // Only show updates published AFTER the user registered
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
                ->first();
        });
    }

    /**
     * Automatically process pending app updates by clearing quiz cache/session and acknowledging history.
     */
    public function processAutomaticAppUpdation(?User $user, int $tenantId)
    {
        $appUpdation = $this->getUserAppUpdation($user, $tenantId);

        if ($appUpdation && $user) {
            // 1. Automatically clear active user quiz cache & session
            $this->clearActiveQuizSessionAndCache();

            // 2. Automatically record acknowledgement in AppUpdationHistory
            $this->closeAppUpdation($user->id, $tenantId, $appUpdation->id);

            Log::info('Automatic App Updation processed for user', [
                'user_id' => $user->id,
                'app_updation_id' => $appUpdation->id,
                'tenant_id' => $tenantId,
            ]);
        }

        return $appUpdation;
    }

    /**
     * Get real-time last completed quiz data with winners (cached for 15s to prevent thundering herd).
     */
    public function getLastQuizData(int $tenantId): array
    {
        return $this->safeCache("fsr_last_quiz_data_{$tenantId}", 15, function () use ($tenantId) {
            $lastQuiz = Quiz::whereIn('tenant_id', [$tenantId, 1])
                ->whereIn('complete_status', ['completed', 'processing'])
                ->latest('updated_at')
                ->first();

            $winners = [];
            $title = 'No recent quiz';
            $quizId = null;
            $completedAt = null;
            $completedAtMs = null;
            $completeStatus = null;

            if ($lastQuiz) {
                $completeStatus = $lastQuiz->complete_status;

                if ($completeStatus === 'processing' || $completeStatus === 'completed') {
                    $title = $lastQuiz->title;
                    $quizId = $lastQuiz->id;
                    $completedAt = $lastQuiz->updated_at ? $lastQuiz->updated_at->toDateTimeString() : null;
                    $completedAtMs = $lastQuiz->updated_at ? $lastQuiz->updated_at->timestamp * 1000 : null;

                    if ($completeStatus === 'completed') {
                        $winners = QuizWinner::with('user:id,name,profile_photo')
                            ->where('quiz_id', $lastQuiz->id)
                            ->orderBy('rank', 'asc')
                            ->take(5)
                            ->get()
                            ->map(function ($w) {
                                return [
                                    'rank' => $w->rank,
                                    'user_name' => $w->user->name ?? 'User',
                                    'profile_photo' => $w->user->profile_photo ?? null,
                                    'total_correct_answer' => $w->total_correct_answer,
                                    'total_question' => $w->total_question,
                                    'total_time' => $w->total_time,
                                    'reward' => $w->reward,
                                    'reward_type' => $w->reward_type,
                                ];
                            })->toArray();
                    }
                } else {
                    $completeStatus = null;
                }
            }

            return [
                'id' => $quizId,
                'title' => $title,
                'completed_at' => $completedAt,
                'completed_at_ms' => $completedAtMs,
                'complete_status' => $completeStatus,
                'winners' => $winners
            ];
        });
    }

    /**
     * Get top quiz players ranked by correct answer count.
     */
    public function getTopQuizPlayers(int $tenantId): array
    {
        // Cache for 30 minutes to eliminate heavy table scan across quiz answers under 50k users
        return $this->safeCache("fsr_web_top_quiz_players_{$tenantId}", 1800, function () use ($tenantId) {
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
    }

    /**
     * Close announcement for user.
     */
    public function closeAnnouncement(int $userId, int $tenantId, int $announcementId): array
    {
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

        Cache::forget("fsr_user_announcements_{$userId}_{$tenantId}");

        return ['success' => true, 'message' => 'Announcement closed successfully.'];
    }

    /**
     * Close app updation for user.
     */
    public function closeAppUpdation(int $userId, int $tenantId, int $appUpdationId): array
    {
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

        Cache::forget("fsr_user_app_updation_{$userId}_{$tenantId}");

        return ['success' => true, 'message' => 'App updation closed successfully.'];
    }

    /**
     * Handle banner click recording and resolve target redirect URL.
     */
    public function handleBannerClick(int $bannerId, string $type): ?string
    {
        $bannerData = Banner::find($bannerId);

        if (!$bannerData) {
            return null;
        }

        if (function_exists('recordBannerClick')) {
            recordBannerClick(
                $bannerData->id,
                $type,
                $bannerData->url
            );
        }

        return $bannerData->url ?: null;
    }

    /**
     * Fetch status of last completed or processing quiz.
     */
    public function getLastQuizStatus(int $tenantId, ?int $targetQuizId = null): array
    {
        $cacheKey = $targetQuizId ? "last_quiz_status_t{$tenantId}_q{$targetQuizId}" : "last_quiz_status_t{$tenantId}_latest";
        
        $cached = Cache::get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        if ($targetQuizId) {
            $lastQuiz = Quiz::select('id', 'complete_status', 'updated_at')->find($targetQuizId);
        } else {
            $lastQuiz = Quiz::select('id', 'complete_status', 'updated_at')
                ->whereIn('tenant_id', [$tenantId, 1])
                ->whereIn('complete_status', ['completed', 'processing'])
                ->latest('updated_at')
                ->first();
        }

        if ($lastQuiz && $lastQuiz->complete_status === 'completed') {
            $completedTime = $lastQuiz->updated_at ? $lastQuiz->updated_at->timestamp : 0;
            if (!$targetQuizId && (time() - $completedTime) > (30 * 60)) {
                $res = [
                    'id' => null,
                    'complete_status' => null,
                ];
                Cache::put($cacheKey, $res, 10);
                return $res;
            }
        }

        $res = [
            'id' => $lastQuiz ? $lastQuiz->id : null,
            'complete_status' => $lastQuiz ? ($lastQuiz->complete_status ?? 'processing') : null,
        ];

        // Cache for 8 seconds when processing (reduces database thrashing by 60% under 50k users)
        $ttl = ($lastQuiz && $lastQuiz->complete_status === 'completed') ? 3600 : 8;
        Cache::put($cacheKey, $res, $ttl);

        return $res;
    }

    /**
     * Check if user has subscribed to WhatsApp channel.
     */
    public function isWhatsappSubscribed(?User $user): bool
    {
        $userId = $user ? $user->id : (Auth::id() ?: session('fsr_user_id'));
        if (!$userId) {
            return false;
        }

        return $this->safeCache("fsr_user_whatsapp_sub_{$userId}", 300, function () use ($userId) {
            return UserWhatsappSubscribe::where('user_id', $userId)
                ->where('status', 'subscribed')
                ->exists();
        });
    }

    /**
     * Subscribe user to WhatsApp channel.
     */
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

        Cache::forget("fsr_user_whatsapp_sub_{$userId}");

        return ['success' => true, 'message' => 'Successfully subscribed to WhatsApp channel.'];
    }
}
