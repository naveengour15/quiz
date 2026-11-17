<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\InboxNotification;
use App\Models\Quiz;
use App\Models\QuizCronJobHistory;
use App\Models\QuizUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QuizStartReminderService
{
    public const TOPIC = 'quiz_updates';

    public function __construct(protected FirebaseNotificationService $firebase)
    {
    }

    public function getStages(): array
    {
        $stages = [];
        try {
            $setting = \App\Models\QuizSetting::first();
            if ($setting) {
                $stages = $setting->getReminderIntervals();
            }
        } catch (\Throwable $e) {}

        if (empty($stages)) {
            $stages = [
                ['value' => 15, 'unit' => 'minute'],
                ['value' => 10, 'unit' => 'minute'],
                ['value' => 5,  'unit' => 'minute'],
                ['value' => 3,  'unit' => 'minute'],
                ['value' => 1,  'unit' => 'minute'],
                ['value' => 30, 'unit' => 'second'],
                ['value' => 10, 'unit' => 'second'],
            ];
        }

        // Always guarantee 10-second stage is included even if DB has older intervals list
        $has10s = false;
        foreach ($stages as $s) {
            $val = (int) ($s['value'] ?? 0);
            $u = strtolower((string) ($s['unit'] ?? ''));
            if ($val === 10 && in_array($u, ['second', 'seconds', 'sec', 's'])) {
                $has10s = true;
                break;
            }
        }
        if (!$has10s) {
            $stages[] = ['value' => 10, 'unit' => 'second'];
        }

        return $stages;
    }

    /**
     * Run reminder checks for configured notification stages (e.g. 15m, 10m, 5m, 3m, 1m, 30s).
     *
     * @return array{stages: array<string, array{stage: string, sent: array, skipped: array}>}
     */
    public function run(?int $targetQuizId = null): array
    {
        $this->assertFirebaseConfigured();

        $stageResults = [];
        $stages = $this->getStages();

        foreach ($stages as $stageConfig) {
            $value = (int) ($stageConfig['value'] ?? 0);
            $unit = strtolower((string) ($stageConfig['unit'] ?? 'minute'));
            if ($value <= 0) continue;

            $isSeconds = in_array($unit, ['second', 'seconds', 'sec', 's']);
            $stageKey = $isSeconds ? "{$value}s" : "{$value}m";
            $cronType = "quiz_reminder_{$stageKey}";
            $leadSeconds = $isSeconds ? $value : ($value * 60);

            $quizzes = $this->dueQuizzes($leadSeconds, $isSeconds, $targetQuizId);

            $sent = [];
            $skipped = [];

            foreach ($quizzes as $quiz) {
                if (!$this->claim($quiz, $cronType)) {
                    $skipped[] = [
                        'quiz_id' => (int) $quiz->id,
                        'stage' => $stageKey,
                        'reason' => 'already_sent_or_locked',
                    ];
                    continue;
                }

                try {
                    $copy = $this->reminderCopy($quiz, $value, $unit);
                    $this->saveInbox($quiz, $copy, $stageKey);

                    // Instant single-request broadcast to all devices via FCM Topic (Takes ~0.2s for all users)
                    $pushResult = $this->firebase->sendToTopic(
                        self::TOPIC,
                        $copy['title'],
                        $copy['body'],
                        $copy['data'],
                        $copy['image'] ?? null
                    );

                    QuizCronJobHistory::where('quiz_id', $quiz->id)
                        ->where('cron_type', $cronType)
                        ->update(['status' => 'success', 'updated_at' => now()]);

                    $sent[] = [
                        'quiz_id' => (int) $quiz->id,
                        'stage' => $stageKey,
                        'title' => $copy['title'],
                        'body' => $copy['body'],
                        'sent_count' => 1,
                        'failed_count' => !empty($pushResult['success']) ? 0 : 1,
                        'total_tokens' => 1,
                        'topic' => self::TOPIC,
                    ];

                    Log::info("Quiz {$quiz->id} [{$stageKey} reminder]: Broadcasted to FCM topic '" . self::TOPIC . "' successfully");
                } catch (\Throwable $e) {
                    QuizCronJobHistory::where('quiz_id', $quiz->id)
                        ->where('cron_type', $cronType)
                        ->where('status', '!=', 'success')
                        ->update(['status' => 'failed', 'updated_at' => now()]);

                    $skipped[] = [
                        'quiz_id' => (int) $quiz->id,
                        'stage' => $stageKey,
                        'reason' => 'send_failed',
                        'error' => $e->getMessage(),
                    ];

                    Log::error("Quiz {$quiz->id} [{$stageKey} reminder] failed: " . $e->getMessage());
                }
            }

            $stageResults[$stageKey] = [
                'stage' => $stageKey,
                'sent' => $sent,
                'skipped' => $skipped,
            ];
        }

        return ['stages' => $stageResults];
    }

    public function message(array $result): string
    {
        $messages = [];
        foreach ($result['stages'] ?? [] as $stageKey => $stage) {
            $sentCount = count($stage['sent'] ?? []);
            if ($sentCount > 0) {
                $messages[] = "{$stageKey} reminder: sent for {$sentCount} quiz(es)";
            }
        }

        if (!empty($messages)) {
            return implode('; ', $messages);
        }

        return 'No upcoming quiz reminders due at this time.';
    }

    /**
     * Quizzes scheduled around the target leadSeconds mark with precise, non-overlapping windows.
     *
     * @return Collection<int, Quiz>
     */
    private function dueQuizzes(int $leadSeconds, bool $isSeconds, ?int $targetQuizId = null): Collection
    {
        // Calculate non-overlapping precision windows for each stage so stages never fire early or out of order
        if ($leadSeconds <= 15) {
            // 10-second stage: window [2s, 16s] (centered at ~9-10s)
            $from = now()->addSeconds(max(1, $leadSeconds - 8));
            $to = now()->addSeconds($leadSeconds + 6);
        } elseif ($leadSeconds <= 45) {
            // 30-second stage: window [22s, 38s] (centered at 30s)
            $from = now()->addSeconds($leadSeconds - 8);
            $to = now()->addSeconds($leadSeconds + 8);
        } elseif ($leadSeconds <= 90) {
            // 1-minute stage: window [45s, 75s] (centered at 60s)
            $from = now()->addSeconds($leadSeconds - 15);
            $to = now()->addSeconds($leadSeconds + 15);
        } elseif ($leadSeconds <= 240) {
            // 3-minute stage: window [155s, 205s] (centered at 180s)
            $from = now()->addSeconds($leadSeconds - 25);
            $to = now()->addSeconds($leadSeconds + 25);
        } else {
            // 5-minute, 10-minute, 15-minute stages: window [lead - 30s, lead + 30s]
            $from = now()->addSeconds($leadSeconds - 30);
            $to = now()->addSeconds($leadSeconds + 30);
        }

        $query = Quiz::query()
            ->where('approve_status', 'approved')
            ->where(function ($q) {
                $q->whereNull('complete_status')
                  ->orWhere('complete_status', 'pending');
            })
            ->where(function ($q) {
                $q->whereNull('visibility')
                  ->orWhere('visibility', '!=', 'practice');
            })
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now()->subSeconds(5)); // Ensure quiz hasn't already begun

        if ($targetQuizId) {
            $query->where('id', $targetQuizId);
        } else {
            $query->whereBetween('scheduled_at', [$from, $to]);
        }

        return $query->orderBy('scheduled_at')->orderBy('id')->get();
    }

    private function assertFirebaseConfigured(): void
    {
        $token = $this->firebase->getAccessToken();
        $serverKey = config('firebase.server_key');
        if (!$token && empty($serverKey)) {
            throw new HttpException(503, 'Firebase push notifications are not configured');
        }
    }

    private function claim(Quiz $quiz, string $cronType): bool
    {
        return DB::transaction(function () use ($quiz, $cronType) {
            $exists = QuizCronJobHistory::query()
                ->where('quiz_id', $quiz->id)
                ->where('cron_type', $cronType)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return false;
            }

            QuizCronJobHistory::create([
                'tenant_id' => $quiz->tenant_id,
                'quiz_id' => $quiz->id,
                'cron_type' => $cronType,
                'scheduled_at' => $quiz->scheduled_at,
                'status' => 'pending',
            ]);

            return true;
        });
    }

    /**
     * Copy tailored with dynamic time (min / sec) and quiz name with Join Quiz action
     */
    private function reminderCopy(Quiz $quiz, int $value, string $unit): array
    {
        $title = trim((string) $quiz->title) ?: 'Daily Quiz #' . $quiz->id;
        $joinUrl = $this->firebase->getQuizJoinUrl($quiz->id);

        $isSeconds = in_array(strtolower($unit), ['second', 'seconds', 'sec', 's']);
        $timeStr = $isSeconds ? "{$value} सेकंड" : "{$value} मिनट";

        if ($isSeconds) {
            $notificationTitle = "🔥 {$title} — सिर्फ {$timeStr} बाकी!";
            $notificationBody = "{$title} सिर्फ {$timeStr} में शुरू होने वाला है! 'Join Quiz' दबाकर अभी लाइव जुड़ें।";
        } elseif ($value >= 15) {
            $notificationTitle = "🎯 {$title} — {$timeStr} में शुरू होगा!";
            $notificationBody = "{$title} {$timeStr} में शुरू हो रहा है! 'Join Quiz' दबाकर अभी शामिल हों और समय पर तैयार रहें।";
        } elseif ($value >= 10) {
            $notificationTitle = "🎯 {$title} — {$timeStr} बाकी!";
            $notificationBody = "{$title} {$timeStr} में शुरू होने वाला है! 'Join Quiz' पर टैप करके अपनी सीट पक्की करें।";
        } elseif ($value >= 5) {
            $notificationTitle = "⏰ {$title} — सिर्फ {$timeStr} बाकी!";
            $notificationBody = "सिर्फ {$timeStr} बाकी! {$title} शुरू होने वाला है, तुरंत Join Quiz करें।";
        } elseif ($value >= 3) {
            $notificationTitle = "🚨 {$title} — अंतिम {$timeStr}!";
            $notificationBody = "अंतिम {$timeStr}! {$title} में शामिल होने के लिए अभी Join Quiz पर टैप करें।";
        } else {
            $notificationTitle = "⚡ {$title} — सिर्फ {$timeStr} बाकी!";
            $notificationBody = "{$title} सिर्फ {$timeStr} में शुरू होने वाला है! तुरंत Join Quiz पर टैप करें।";
        }

        return [
            'title' => $notificationTitle,
            'body'  => $notificationBody,
            'image' => asset('assets/images/logo.png'),
            'data'  => [
                'type'         => 'quiz_reminder',
                'quiz_id'      => (string) $quiz->id,
                'quiz_title'   => (string) $title,
                'lead_time'    => (string) $value,
                'lead_unit'    => $unit,
                'lead_minutes' => (string) ($isSeconds ? round($value / 60, 2) : $value),
                'action'       => 'join_quiz',
                'action_title' => 'Join Quiz',
                'action_url'   => $joinUrl,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }

    private function saveInbox(Quiz $quiz, array $copy, string $stageKey): void
    {
        try {
            InboxNotification::create([
                'tenant_id' => $quiz->tenant_id,
                'user_id' => null,
                'audience' => 'all',
                'type' => "quiz_reminder_{$stageKey}",
                'title' => $copy['title'],
                'body' => $copy['body'],
                'action' => $copy['data']['action'],
                'deep_link' => $copy['data']['action_url'] ?? null,
                'data' => $copy['data'],
                'related_type' => 'quiz',
                'related_id' => $quiz->id,
                'topic' => self::TOPIC,
                'status' => 'published',
                'published_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Inbox save ' . $e->getMessage());
        }
    }
}
