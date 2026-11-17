<?php

namespace App\Repositories;

use App\Models\Quiz;
use App\Models\QuizNotification;
use App\Models\User;
use App\Models\AdminUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizNotificationRepository implements QuizNotificationRepositoryInterface
{
    protected WhatsAppRepositoryInterface $whatsAppRepository;

    public function __construct(WhatsAppRepositoryInterface $whatsAppRepository)
    {
        $this->whatsAppRepository = $whatsAppRepository;
    }

    public function prepareWhatsappQuizEntries(int $quizId): array
    {
        try {
            $now = now();

            // 1. Get specific quiz
            $quiz = Quiz::where('id', $quizId)
                ->where('status', 'scheduled')
                ->where('complete_status', 'pending')
                ->where('scheduled_at', '>', now()->addMinutes(20))
                ->first();

            if (!$quiz) {
                Log::warning('WhatsApp quiz preparation skipped: Quiz not found or not eligible.', [
                    'quiz_id' => $quizId,
                ]);

                return [
                    'inserted' => 0,
                    'updated' => 0,
                    'quiz' => null,
                ];
            }

            // 2. Activation time = quiz time - 20 minutes
            $activTime = Carbon::parse($quiz->scheduled_at)->subMinutes(20);

            $totalInserted = 0;
            $totalUpdated = 0;

            // 3. Process active users in chunks
            User::where('is_active', 1)
                ->whereNotNull('mobile')
                ->where('mobile', '!=', '')
                ->select(['id', 'name', 'tenant_id'])
                ->chunkById(500, function ($users) use (
                    $quiz,
                    $activTime,
                    $now,
                    &$totalInserted,
                    &$totalUpdated
                ) {
                    $userIds = $users->pluck('id');

                    // Existing notifications for this batch
                    $existingRecords = QuizNotification::withTrashed()
                        ->where('quiz_id', $quiz->id)
                        ->whereIn('user_id', $userIds)
                        ->get([
                            'id',
                            'user_id',
                            'status',
                            'quiz_schedule_time',
                            'sent_date_time',
                            'deleted_at',
                        ])
                        ->keyBy('user_id');

                    $insertData = [];
                    $updateUserIds = [];
                    $restoreIds = [];

                    foreach ($users as $user) {

                        // Existing notification
                        if ($existingRecords->has($user->id)) {

                            $record = $existingRecords->get($user->id);

                            // Already sent/failed => don't touch
                            if (
                                in_array($record->status, ['Sent', 'failed']) ||
                                !empty($record->sent_date_time)
                            ) {
                                continue;
                            }

                            // Restore soft deleted pending notification
                            if (
                                $record->deleted_at !== null &&
                                $record->status === 'pending'
                            ) {
                                $restoreIds[] = $record->id;
                            }

                            // Update schedule time if changed
                            if (
                                $record->status === 'pending' &&
                                $record->quiz_schedule_time != $quiz->scheduled_at
                            ) {
                                $updateUserIds[] = $user->id;
                            }

                            continue;
                        }

                        // New notification
                        $insertData[] = [
                            'quiz_id' => $quiz->id,
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'quiz_name' => $quiz->title ?? 'Quiz',
                            'quiz_schedule_time' => $quiz->scheduled_at,
                            'notificationactiv_date_time' => $activTime,
                            'status' => 'pending',
                            'msg' => null,
                            'sent_date_time' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    // Restore deleted records
                    if (!empty($restoreIds)) {
                        QuizNotification::withTrashed()
                            ->whereIn('id', $restoreIds)
                            ->restore();
                    }

                    // Insert new records
                    if (!empty($insertData)) {
                        $uniqueInsertData = collect($insertData)->unique('user_id')->values()->all();
                        QuizNotification::insertOrIgnore($uniqueInsertData);

                        $totalInserted += count($uniqueInsertData);
                    }

                    // Update pending records
                    if (!empty($updateUserIds)) {
                        QuizNotification::where('quiz_id', $quiz->id)
                            ->whereIn('user_id', $updateUserIds)
                            ->where('status', 'pending')
                            ->update([
                                'quiz_schedule_time' => $quiz->scheduled_at,
                                'notificationactiv_date_time' => $activTime,
                                'updated_at' => $now,
                            ]);

                        $totalUpdated += count($updateUserIds);
                    }

                    unset($userIds, $existingRecords, $insertData, $updateUserIds, $restoreIds, $uniqueInsertData);
                    gc_collect_cycles();
                });

            // Success log
            Log::info('WhatsApp quiz notification preparation completed.', [
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'scheduled_at' => $quiz->scheduled_at,
                'activation_time' => $activTime->toDateTimeString(),
                'inserted' => $totalInserted,
                'updated' => $totalUpdated,
            ]);

            return [
                'inserted' => $totalInserted,
                'updated' => $totalUpdated,
                'quiz' => $quiz,
            ];

        } catch (\Throwable $e) {

            Log::error('WhatsApp quiz notification preparation failed.', [
                'quiz_id' => $quizId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'inserted' => 0,
                'updated' => 0,
                'quiz' => null,
                'error' => $e->getMessage(),
            ];
        }
    } 

    /**
     * Prepare QuizNotification database entries for upcoming scheduled quizzes.
     *
     * @param callable|null $output Optional callback for logging/console output
     * @return array Summary of preparation ['inserted' => int, 'updated' => int, 'quiz' => mixed]
     */
    public function prepareWhatsappEntries(?callable $output = null): array
    {
        // 1. Next Scheduled Quiz nikalo jiska schedule time abhi se 20 minute bada (aage) ho
        $quiz = Quiz::where('status', 'scheduled')
            ->where('approve_status', 'approved')
            ->where('complete_status', 'pending')
            ->where('scheduled_at', '>', now()->addMinutes(20))
            ->orderBy('scheduled_at', 'asc')
            ->first();

        if (!$quiz) {
            if ($output) {
                $output('Preparation Skipped: Koi upcoming scheduled quiz (scheduled_at > 20 mins) nahi mila.');
            }
            return ['inserted' => 0, 'updated' => 0, 'quiz' => null];
        }

        // Check if entries for this scheduled quiz already exist and schedule time has not changed
        $totalActiveUsersCount = User::where('is_active', 1)
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->count();

        $alreadyPreparedCount = QuizNotification::where('quiz_id', $quiz->id)->count();

        // Agar saare users ki entry pehle se ban chuki hai aur quiz time same hai toh heavy chunk scanning skip karein
        if ($alreadyPreparedCount >= $totalActiveUsersCount && $totalActiveUsersCount > 0) {
            $needsUpdate = QuizNotification::where('quiz_id', $quiz->id)
                ->where('status', 'pending')
                ->where('quiz_schedule_time', '!=', $quiz->scheduled_at)
                ->exists();

            if (!$needsUpdate) {
                return ['inserted' => 0, 'updated' => 0, 'quiz' => $quiz];
            }
        }

        // 2. Schedule time se 20 minute pehle ka activation time calculate karo
        $activTime = Carbon::parse($quiz->scheduled_at)->subMinutes(20);
        $now = now();
        $totalInserted = 0;
        $totalUpdated = 0;

        // 3. Process active users in memory-efficient chunks using chunkById()
        User::where('is_active', 1)
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->select(['id', 'name', 'tenant_id'])
            ->chunkById(500, function ($users) use ($quiz, $activTime, $now, &$totalInserted, &$totalUpdated) {
                $userIds = $users->pluck('id')->toArray();

                // 4. Fetch existing records for only this chunk's users (including soft-deleted)
                $existingRecords = QuizNotification::withTrashed()
                    ->where('quiz_id', $quiz->id)
                    ->whereIn('user_id', $userIds)
                    ->select(['id', 'user_id', 'status', 'quiz_schedule_time', 'sent_date_time', 'deleted_at'])
                    ->get()
                    ->keyBy('user_id');

                $insertData = [];
                $updateUserIds = [];
                $restoreIds = [];

                // 5. Har user ko check karo
                foreach ($users as $user) {
                    if ($existingRecords->has($user->id)) {
                        $record = $existingRecords->get($user->id);

                        // RULE 1: Agar message pehle se 'Sent' ho chuka hai ya 'failed' hai ya sent_date_time set hai -> KUCH MAT KARO (Strict Skip)
                        if (in_array($record->status, ['Sent', 'failed']) || !empty($record->sent_date_time)) {
                            continue; 
                        }

                        // Agar soft deleted hai aur pending tha, tabhi restore karo
                        if ($record->deleted_at !== null && $record->status === 'pending') {
                            $restoreIds[] = $record->id;
                        }

                        // RULE 2: Agar status 'pending' hai aur quiz ka time change hua hai, toh UPDATE list mein daalo
                        if ($record->status === 'pending' && $record->quiz_schedule_time != $quiz->scheduled_at) {
                            $updateUserIds[] = $user->id;
                        }

                    } else {
                        // RULE 3: Agar is user ki is quiz ke liye koi entry hi nahi hai, toh INSERT list mein daalo
                        $insertData[] = [
                            'quiz_id' => $quiz->id,
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'quiz_name' => $quiz->title ?? 'Quiz',
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

                // Soft-deleted records restore karo
                if (!empty($restoreIds)) {
                    QuizNotification::withTrashed()->whereIn('id', $restoreIds)->restore();
                }

                // 6. INSERT: Naye users ki bulk entry karo (insertOrIgnore + unique user_id to prevent duplicates)
                if (!empty($insertData)) {
                    $uniqueInsertData = collect($insertData)->unique('user_id')->values()->all();
                    QuizNotification::insertOrIgnore($uniqueInsertData);
                    $totalInserted += count($uniqueInsertData);
                }

                // 7. UPDATE: Sirf un 'pending' users ko update karo jinka time change hua hai
                if (!empty($updateUserIds)) {
                    QuizNotification::where('quiz_id', $quiz->id)
                        ->whereIn('user_id', $updateUserIds)
                        ->where('status', 'pending')
                        ->update([
                            'quiz_schedule_time' => $quiz->scheduled_at,
                            'notificationactiv_date_time' => $activTime,
                            'updated_at' => $now,
                        ]);
                    $totalUpdated += count($updateUserIds);
                }

                unset($userIds, $existingRecords, $insertData, $updateUserIds, $restoreIds, $uniqueInsertData);
                gc_collect_cycles();
            });

        // 8. Console / Output Callback
        if ($output && ($totalInserted > 0 || $totalUpdated > 0)) {
            $output("✅ Preparation Complete: {$totalInserted} Nayi entries, {$totalUpdated} Purani entries update.");
            $output("📌 Quiz: {$quiz->title} | Schedule: {$quiz->scheduled_at} | Activate at: {$activTime->toDateTimeString()}");
        }

        return ['inserted' => $totalInserted, 'updated' => $totalUpdated, 'quiz' => $quiz];
    }

    /**
     * Process pending quiz notifications in parallel batches.
     *
     * @param callable|null $output Optional callback for logging/console output
     * @return array Summary of processed notifications ['sent' => int, 'failed' => int]
     */

    public function processPendingQuizNotifications(?callable $output = null): array
    {
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        DB::disableQueryLog();

        $sent = 0;
        $failed = 0;
        $now = now();

        try {
            Log::info('Pending quiz notification processing started', [
                'started_at' => $now->toDateTimeString(),
            ]);

            /*
            * 1. Expire past quiz notifications
            */
            try {
                $expiredCount = QuizNotification::where('status', 'pending')
                    ->whereNotNull('quiz_schedule_time')
                    ->where('quiz_schedule_time', '<', $now)
                    ->update([
                        'status' => 'failed',
                        'msg' => 'Quiz scheduled time passed',
                        'updated_at' => $now,
                    ]);

                Log::info('Past quiz notifications expired', [
                    'expired_count' => $expiredCount,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to expire past quiz notifications', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }

            /*
            * 2. Fetch pending notifications ready for dispatch
            */
            $query = QuizNotification::where('status', 'pending')
                ->whereNull('sent_date_time')
                ->where(function ($q) use ($now) {
                    $q->whereNull('notificationactiv_date_time')
                        ->orWhere('notificationactiv_date_time', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('quiz_schedule_time')
                        ->orWhere('quiz_schedule_time', '>=', $now);
                })
                ->with([
                    'user:id,name,mobile,tenant_id,is_active',
                    'quiz:id,title,tenant_id,scheduled_at',
                ]);

            /*
            * 3. Process notifications in batches
            */
            $query->chunkById(100, function ($notifications) use (
                &$sent,
                &$failed,
                $now
            ) {
                $messages = [];
                $invalidUserNotifIds = [];
                $ids = [];
                $results = [];
                $successIds = [];
                $failedIds = [];

                try {
                    Log::info('Processing quiz notification chunk', [
                        'chunk_count' => $notifications->count(),
                        'sent_so_far' => $sent,
                        'failed_so_far' => $failed,
                    ]);

                    foreach ($notifications as $notification) {
                        try {
                            $user = $notification->user;

                            if (
                                !$user ||
                                !$user->is_active ||
                                empty($user->mobile)
                            ) {
                                $invalidUserNotifIds[] = $notification->id;
                                $failed++;

                                Log::warning('Quiz notification skipped: invalid user', [
                                    'notification_id' => $notification->id,
                                    'user_id' => $notification->user_id,
                                    'has_user' => (bool) $user,
                                    'is_active' => $user?->is_active,
                                    'has_mobile' => !empty($user?->mobile),
                                ]);

                                continue;
                            }

                            $scheduleTime = $notification->quiz_schedule_time
                                ?? $notification->quiz?->scheduled_at
                                ?? $now;

                            $messages[] = [
                                'id' => $notification->id,
                                'mobileNumber' => $user->mobile,
                                'templateCode' => 'Quiz_Joined_Alert',
                                'bodyParameters' => [
                                    [
                                        'name' => 'user_name',
                                        'value' => $notification->user_name
                                            ?: ($user->name ?? 'User'),
                                    ],
                                    [
                                        'name' => 'quiz_name',
                                        'value' => $notification->quiz_name
                                            ?: ($notification->quiz?->title ?? 'Quiz'),
                                    ],
                                    [
                                        'name' => 'quiz_time',
                                        'value' => Carbon::parse($scheduleTime)
                                            ->format('g:i A'),
                                    ],
                                    [
                                        'name' => 'quiz_link',
                                        'value' => $notification->quiz_id
                                            . '?auto_join=1',
                                    ],
                                ],
                                'buttonParameters' => [],
                                'userId' => $user->id,
                                'tenantId' => $notification->quiz?->tenant_id
                                    ?? $user->tenant_id
                                    ?? 1,
                            ];
                        } catch (\Throwable $e) {
                            $failed++;

                            Log::error('Quiz notification message preparation failed', [
                                'notification_id' => $notification->id ?? null,
                                'user_id' => $notification->user_id ?? null,
                                'quiz_id' => $notification->quiz_id ?? null,
                                'error' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                            ]);
                        }
                    }

                    /*
                    * Mark invalid users as failed
                    */
                    if (!empty($invalidUserNotifIds)) {
                        try {
                            QuizNotification::whereIn(
                                'id',
                                $invalidUserNotifIds
                            )->update([
                                'status' => 'failed',
                                'msg' => 'User inactive or missing mobile number',
                                'updated_at' => $now,
                            ]);

                            Log::info('Invalid user notifications marked failed', [
                                'count' => count($invalidUserNotifIds),
                            ]);
                        } catch (\Throwable $e) {
                            Log::error(
                                'Failed to update invalid user notifications',
                                [
                                    'notification_ids' => $invalidUserNotifIds,
                                    'error' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line' => $e->getLine(),
                                ]
                            );
                        }
                    }

                    if (empty($messages)) {
                        Log::info('No valid WhatsApp messages in current chunk');
                        return;
                    }

                    $ids = collect($messages)->pluck('id')->all();

                    /*
                    * Claim batch
                    */
                    try {
                        $claimedCount = QuizNotification::whereIn('id', $ids)
                            ->where('status', 'pending')
                            ->whereNull('sent_date_time')
                            ->update([
                                'sent_date_time' => $now,
                                'updated_at' => $now,
                            ]);

                        Log::info('Quiz notification batch claimed', [
                            'requested_count' => count($ids),
                            'claimed_count' => $claimedCount,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Failed to claim quiz notification batch', [
                            'notification_ids' => $ids,
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);

                        $failed += count($ids);
                        return;
                    }

                    /*
                    * Send WhatsApp batch
                    */
                    try {
                        Log::info('Sending WhatsApp notification batch', [
                            'count' => count($messages),
                            'notification_ids' => $ids,
                        ]);

                        $results = $this->whatsAppRepository
                            ->sendBatchWhatsAppMessages($messages);

                        Log::info('WhatsApp notification batch response received', [
                            'count' => count($results),
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('WhatsApp batch send failed', [
                            'notification_ids' => $ids,
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        try {
                            QuizNotification::whereIn('id', $ids)->update([
                                'status' => 'failed',
                                'msg' => 'WhatsApp API exception: '
                                    . $e->getMessage(),
                                'updated_at' => now(),
                            ]);
                        } catch (\Throwable $dbException) {
                            Log::critical(
                                'Failed to mark WhatsApp exception notifications as failed',
                                [
                                    'notification_ids' => $ids,
                                    'error' => $dbException->getMessage(),
                                    'file' => $dbException->getFile(),
                                    'line' => $dbException->getLine(),
                                ]
                            );
                        }

                        $failed += count($ids);
                        return;
                    }

                    foreach ($ids as $id) {
                        $result = $results[$id] ?? null;

                        if ($result && ($result['success'] ?? false)) {
                            $successIds[] = $id;
                        } else {
                            $failedIds[] = $id;

                            Log::warning('WhatsApp notification failed', [
                                'notification_id' => $id,
                                'result' => $result,
                            ]);
                        }
                    }

                    /*
                    * Mark successful notifications
                    */
                    if (!empty($successIds)) {
                        try {
                            QuizNotification::whereIn('id', $successIds)
                                ->update([
                                    'status' => 'Sent',
                                    'msg' => 'WhatsApp message sent successfully',
                                    'updated_at' => now(),
                                ]);

                            $sent += count($successIds);

                            Log::info('WhatsApp notifications marked as sent', [
                                'count' => count($successIds),
                                'notification_ids' => $successIds,
                            ]);
                        } catch (\Throwable $e) {
                            Log::error(
                                'Failed to update successful quiz notifications',
                                [
                                    'notification_ids' => $successIds,
                                    'error' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line' => $e->getLine(),
                                ]
                            );
                        }
                    }

                    /*
                    * Mark failed notifications
                    */
                    if (!empty($failedIds)) {
                        try {
                            QuizNotification::whereIn('id', $failedIds)
                                ->update([
                                    'status' => 'failed',
                                    'msg' => 'WhatsApp API error',
                                    'updated_at' => now(),
                                ]);

                            $failed += count($failedIds);

                            Log::info('Failed WhatsApp notifications updated', [
                                'count' => count($failedIds),
                                'notification_ids' => $failedIds,
                            ]);
                        } catch (\Throwable $e) {
                            Log::error(
                                'Failed to update failed quiz notifications',
                                [
                                    'notification_ids' => $failedIds,
                                    'error' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line' => $e->getLine(),
                                ]
                            );
                        }
                    }

                } catch (\Throwable $e) {
                    Log::critical('Quiz notification chunk processing crashed', [
                        'chunk_count' => $notifications->count(),
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $failed += $notifications->count();
                } finally {
                    unset($messages, $invalidUserNotifIds, $ids, $results, $successIds, $failedIds, $notifications);
                    gc_collect_cycles();
                }
            });

            if ($output) {
                try {
                    $output(
                        "WhatsApp notifications: {$sent} sent, {$failed} failed."
                    );
                } catch (\Throwable $e) {
                    Log::warning('Notification output callback failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Pending quiz notification processing completed', [
                'sent' => $sent,
                'failed' => $failed,
                'completed_at' => now()->toDateTimeString(),
            ]);

            return [
                'sent' => $sent,
                'failed' => $failed,
            ];

        } catch (\Throwable $e) {
            Log::critical('Pending quiz notification processing crashed', [
                'sent' => $sent,
                'failed' => $failed,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($output) {
                try {
                    $output(
                        "WhatsApp notification process failed. Sent: {$sent}, Failed: {$failed}."
                    );
                } catch (\Throwable $outputException) {
                    Log::warning('Failure output callback failed', [
                        'error' => $outputException->getMessage(),
                    ]);
                }
            }

            return [
                'sent' => $sent,
                'failed' => $failed,
                'error' => $e->getMessage(),
            ];
        } finally {
            if ($originalMemoryLimit !== false && $originalMemoryLimit !== '') {
                ini_set('memory_limit', $originalMemoryLimit);
            }
            unset($query);
            gc_collect_cycles();
            if (function_exists('gc_mem_caches')) {
                gc_mem_caches();
            }
        }
    }



    public function sendQuizNotifications(?callable $output = null): array
    {
        $sent = 0;
        $failed = 0;
        $quiz = null;
        $user = null;
        $notification = null;

        try {
            $now = now();
            $minutes1 = $now->copy()->addMinutes(30);
            $minutes2 = $now->copy()->addMinutes(15);

            // 1. Consider upcoming scheduled quiz
            $quiz = Quiz::where('status', 'scheduled')
                ->where('approve_status', 'approved')
                ->where('complete_status', 'pending')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', $minutes1)
                ->where('scheduled_at', '>=', $minutes2)
                ->latest('scheduled_at')
                ->first();

            if (!$quiz) {
                $msg = 'No upcoming scheduled quiz found in 20-30 minute window.';
                if ($output) {
                    $output("⚠️ " . $msg);
                }
                Log::info($msg);

                return [
                    'sent' => 0,
                    'failed' => 0,
                    'quiz' => null,
                    'message' => $msg,
                ];
            }

            // 2. Find target user (AdminUser ID 1, User ID 1, or first active user with valid mobile)
            $user = AdminUser::where('id', 1)->whereNotNull('mobile')->where('mobile', '!=', '')->first()
                ?? User::where('id', 1)->where('is_active', 1)->whereNotNull('mobile')->where('mobile', '!=', '')->first()
                ?? User::where('is_active', 1)->whereNotNull('mobile')->where('mobile', '!=', '')->first();

            if (!$user) {
                $msg = 'No valid target user with mobile number found.';
                if ($output) {
                    $output("⚠️ " . $msg);
                }
                Log::warning($msg, ['quiz_id' => $quiz->id]);

                return [
                    'sent' => 0,
                    'failed' => 0,
                    'quiz' => $quiz,
                    'message' => $msg,
                ];
            }

            // 3. Find or Create QuizNotification for this user and quiz
            $notification = QuizNotification::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->first();

            if ($notification && $notification->status === 'Sent' && !empty($notification->sent_date_time)) {
                $msg = "Already sent to user {$user->name} (ID: {$user->id}) for Quiz #{$quiz->id}";
                if ($output) {
                    $output("⚠️ " . $msg);
                }

                Log::warning('Notification skipped (already sent)', [
                    'id' => $notification->id,
                    'quiz_id' => $quiz->id,
                    'user_id' => $user->id,
                    'message' => $msg,
                ]);

                return [
                    'sent' => 0,
                    'failed' => 0,
                    'quiz' => $quiz,
                    'message' => $msg,
                ];
            }

            if (!$notification) {
                $notification = QuizNotification::create([
                    'quiz_id' => $quiz->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name ?: 'Fsr Quiz Family',
                    'quiz_name' => $quiz->title ?? 'Quiz',
                    'quiz_schedule_time' => $quiz->scheduled_at,
                    'notificationactiv_date_time' => $now,
                    'status' => 'pending',
                    'msg' => null,
                    'sent_date_time' => null,
                ]);
            }

            // 4. Send WhatsApp Notification
            $scheduleTime = $notification->quiz_schedule_time ?? $quiz->scheduled_at ?? $now;
            $quizTimeFormatted = Carbon::parse($scheduleTime)->format('g:i A');
            $quizLink = $quiz->id . '?auto_join=1';
            $tenantId = $quiz->tenant_id ?? $user->tenant_id ?? 1;

            $bodyParameters = [
                [
                    'name' => 'user_name',
                    'value' => 'Fsr Quiz Family',
                ],
                [
                    'name' => 'quiz_name',
                    'value' => $notification->quiz_name ?: ($quiz->title ?? 'Quiz'),
                ],
                [
                    'name' => 'quiz_time',
                    'value' => $quizTimeFormatted,
                ],
                [
                    'name' => 'quiz_link',
                    'value' => $quizLink,
                ],
            ];

            $response = $this->whatsAppRepository->sendWhatsAppMessage(
                $user->mobile,
                'Quiz_Joined_Alert',
                $bodyParameters,
                [],
                (object) ['id' => $tenantId]
            );
            $response0 = $this->whatsAppRepository->sendWhatsAppMessage(
                '9555876110',
                'Quiz_Joined_Alert',
                $bodyParameters,
                [],
                (object) ['id' => $tenantId]
            );
            
            $response1 = $this->whatsAppRepository->sendWhatsAppMessage(
                '7097341395',
                'Quiz_Joined_Alert',
                $bodyParameters,
                [],
                (object) ['id' => $tenantId]
            );
            
            $response2 = $this->whatsAppRepository->sendWhatsAppMessage(
                '9981534320',
                'Quiz_Joined_Alert',
                $bodyParameters,
                [],
                (object) ['id' => $tenantId]
            );

            $response3 = $this->whatsAppRepository->sendWhatsAppMessage(
                '8962291913',
                'Quiz_Joined_Alert',
                $bodyParameters,
                [],
                (object) ['id' => $tenantId]
            );

            if ($response !== false) {
                $notification->update([
                    'status' => 'Sent',
                    'msg' => 'WhatsApp message sent successfully',
                    'sent_date_time' => now(),
                ]);
                $sent = 1;

                if ($output) {
                    $output("✅ Notification successfully sent to user: {$user->name} ({$user->mobile}) for Quiz: {$quiz->title}");
                }

                Log::info('Quiz notification sent to single user successfully', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'mobile' => $user->mobile,
                    'quiz_id' => $quiz->id,
                ]);
            } else {
                $notification->update([
                    'status' => 'failed',
                    'msg' => 'WhatsApp API error / send failed',
                    'updated_at' => now(),
                ]);
                $failed = 1;

                if ($output) {
                    $output("❌ WhatsApp notification failed for user {$user->name} ({$user->mobile}) for Quiz: {$quiz->title}");
                }

                Log::warning('WhatsApp notification failed for user', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'mobile' => $user->mobile,
                    'quiz_id' => $quiz->id,
                ]);
            }

        } catch (\Throwable $e) {
            $failed++;

            if (isset($notification) && $notification instanceof QuizNotification) {
                $notification->update([
                    'status' => 'failed',
                    'msg' => $e->getMessage(),
                    'updated_at' => now(),
                ]);
            }

            Log::error('Quiz notification send failed', [
                'notification_id' => (isset($notification) && $notification instanceof QuizNotification) ? $notification->id : null,
                'quiz_id' => $quiz?->id,
                'user_id' => $user?->id,
                'mobile' => $user?->mobile,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($output) {
                $output("❌ Error: " . $e->getMessage());
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'quiz' => $quiz,
        ];
    }
}
