<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Quiz;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected string $projectId;
    protected string $credentialsPath;
    protected ?string $serverKey;

    public function __construct()
    {
        $this->projectId = config('firebase.project_id', env('FIREBASE_PROJECT_ID', 'fsrquiz'));
        $this->credentialsPath = config('firebase.credentials_path', storage_path('app/firebase/firebase_credentials.json'));
        $this->serverKey = config('firebase.server_key', env('FIREBASE_SERVER_KEY', null));
    }

    /**
     * Generate Google OAuth2 Access Token for Firebase Cloud Messaging v1 API
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember('fcm_v1_access_token', 3300, function () {
            $clientEmail = config('firebase.client_email');
            $privateKey = config('firebase.private_key');

            if ($clientEmail && $privateKey) {
                $privateKey = str_replace('\n', "\n", $privateKey);
            } else {
                $path = $this->credentialsPath;
                if (!file_exists($path)) {
                    $path = base_path($this->credentialsPath);
                }

                if (file_exists($path)) {
                    $jsonContent = file_get_contents($path);
                    $serviceAccount = json_decode($jsonContent, true);

                    if ($serviceAccount && !empty($serviceAccount['client_email']) && !empty($serviceAccount['private_key'])) {
                        $clientEmail = $serviceAccount['client_email'];
                        $privateKey = $serviceAccount['private_key'];
                    }
                }
            }

            if (empty($clientEmail) || empty($privateKey)) {
                Log::warning('Firebase service account credentials missing in .env and JSON file.');
                return null;
            }

            $now = time();
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $claim = json_encode([
                'iss'   => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]);

            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Claim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));
            $signatureInput = $base64Header . '.' . $base64Claim;

            $signature = '';
            $pkey = openssl_pkey_get_private($privateKey);
            if (!$pkey) {
                Log::error('Firebase invalid private key format.');
                return null;
            }

            if (!openssl_sign($signatureInput, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
                Log::error('Firebase failed to sign JWT.');
                return null;
            }

            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            $jwt = $signatureInput . '.' . $base64Signature;

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('Firebase OAuth2 token request failed: ' . $response->body());
            return null;
        });
    }

    /**
     * Send Push Notification to a single device token using FCM v1 or legacy API
     */
    public function sendToToken(string $token, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $accessToken = $this->getAccessToken();

        // 1. Send via FCM v1 API (Standard)
        if ($accessToken) {
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            // Format all custom data fields as strings (FCM v1 requirement)
            $stringData = [];
            foreach ($data as $k => $v) {
                $stringData[(string) $k] = is_array($v) ? json_encode($v) : (string) $v;
            }

            $message = [
                'token'        => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data'         => $stringData,
                'android'      => [
                    'priority'     => 'HIGH',
                    'notification' => [
                        'sound'        => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'channel_id'   => 'quiz_alerts',
                        'icon'         => 'ic_notification',
                        'color'        => '#A14FF0',
                        'tag'          => !empty($data['quiz_id']) ? ('quiz_' . $data['quiz_id']) : 'quiz_reminder',
                    ],
                ],
                'webpush'      => [
                    'headers'      => [
                        'Urgency' => 'high',
                    ],
                    'notification' => [
                        'title'   => $title,
                        'body'    => $body,
                        'icon'    => asset('assets/images/logo.png'),
                        'actions' => [
                            [
                                'action' => 'join_now',
                                'title'  => '🚀 Join Now',
                            ],
                        ],
                    ],
                    'fcm_options'  => [
                        'link' => $data['action_url'] ?? (!empty($data['quiz_id']) ? $this->getQuizJoinUrl($data['quiz_id']) : url('/fsrapp/home')),
                    ],
                ],
            ];

            if ($imageUrl) {
                $message['notification']['image'] = $imageUrl;
                $message['android']['notification']['image'] = $imageUrl;
            }

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(10)
                    ->post($url, ['message' => $message]);

                if ($response->successful()) {
                    return ['success' => true, 'response' => $response->json()];
                }

                $respBody = $response->json();
                $errorCode = $respBody['error']['details'][0]['errorCode'] ?? $respBody['error']['status'] ?? '';

                // If token is invalid or unregistered, deactivate in DB
                if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'])) {
                    DeviceToken::where('token', $token)->update(['is_active' => false]);
                }

                return ['success' => false, 'error' => $respBody, 'status' => $response->status()];
            } catch (\Throwable $e) {
                Log::error('FCM v1 push send error: ' . $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // 2. Fallback to Legacy FCM Server Key if provided
        if (!empty($this->serverKey)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type'  => 'application/json',
                ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', [
                    'to'           => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                        'sound' => 'default',
                        'image' => $imageUrl,
                    ],
                    'data'         => $data,
                    'priority'     => 'high',
                    'tag'          => !empty($data['quiz_id']) ? ('quiz_' . $data['quiz_id']) : 'quiz_reminder',
                ]);

                if ($response->successful()) {
                    return ['success' => true, 'response' => $response->json()];
                }
                return ['success' => false, 'error' => $response->body()];
            } catch (\Throwable $e) {
                Log::error('FCM legacy push send error: ' . $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => 'No valid Firebase credentials available.'];
    }

    /**
     * Send Push Notification in parallel batches to multiple device tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $uniqueTokens = array_unique(array_filter($tokens));
        $sentCount = 0;
        $failedCount = 0;

        // Process in chunks of 50
        $chunks = array_chunk($uniqueTokens, 50);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $t) {
                $res = $this->sendToToken($t, $title, $body, $data, $imageUrl);
                if (!empty($res['success'])) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        return [
            'sent'   => $sentCount,
            'failed' => $failedCount,
            'total'  => count($uniqueTokens),
        ];
    }

    /**
     * Send DATA-ONLY push (no FCM "notification" block) to many app tokens in parallel.
     * The Flutter app renders these itself so it can show action buttons (e.g. "Join Quiz").
     */
    public function sendDataToTokens(array $tokens, array $data, int $ttlSeconds = 900): array
    {
        $uniqueTokens = array_values(array_unique(array_filter($tokens)));
        if (empty($uniqueTokens)) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['sent' => 0, 'failed' => count($uniqueTokens), 'total' => count($uniqueTokens), 'error' => 'No valid Firebase credentials available.'];
        }

        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_array($v) ? json_encode($v) : (string) $v;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $sent = 0;
        $failed = 0;
        $invalid = [];

        foreach (array_chunk($uniqueTokens, 100) as $chunk) {
            try {
                $responses = Http::pool(function ($pool) use ($chunk, $accessToken, $url, $stringData, $ttlSeconds) {
                    $reqs = [];
                    foreach ($chunk as $i => $t) {
                        $reqs[] = $pool->as((string) $i)->withToken($accessToken)->timeout(10)->post($url, [
                            'message' => [
                                'token'   => $t,
                                'data'    => $stringData,
                                'android' => [
                                    'priority' => 'HIGH',
                                    'ttl'      => max(60, $ttlSeconds) . 's',
                                ],
                            ],
                        ]);
                    }
                    return $reqs;
                });
            } catch (\Throwable $e) {
                Log::error('FCM data pool error: ' . $e->getMessage());
                $failed += count($chunk);
                continue;
            }

            foreach ($chunk as $i => $t) {
                $res = $responses[(string) $i] ?? null;
                if ($res instanceof \Illuminate\Http\Client\Response && $res->successful()) {
                    $sent++;
                    continue;
                }
                $failed++;
                if ($res instanceof \Illuminate\Http\Client\Response) {
                    $body = $res->json();
                    $code = $body['error']['details'][0]['errorCode'] ?? $body['error']['status'] ?? '';
                    if (in_array($code, ['UNREGISTERED', 'NOT_FOUND'], true)) {
                        $invalid[] = $t;
                    }
                }
            }
        }

        if (!empty($invalid)) {
            DeviceToken::whereIn('token', $invalid)->update(['is_active' => false]);
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($uniqueTokens)];
    }

    /**
     * Send 5-minute quiz reminder notification
     */
    public function sendQuiz5MinuteReminder(Quiz $quiz): array
    {
        $quizId = $quiz->id;
        $quizTitle = $quiz->title ?? 'Live Quiz';
        $quizType = $quiz->visibility === 'fastest_finger_first' ? 'Fastest Finger First' : 'Quiz Challenge';

        // Title and Body for 5-minute alert
        $title = "⚡ {$quizTitle} - 5 मिनट में शुरू हो रहा है!";
        $body = "{$quizType} '{$quizTitle}' सिर्फ 5 मिनट में शुरू होने वाला है! अभी ऐप खोलें और तैयार रहें।";

        // Target Click URL: Dynamic Login with auto_join to Quiz
        $actionUrl = $this->getQuizJoinUrl($quizId);

        $payloadData = [
            'quiz_id'           => (string) $quizId,
            'quiz_type'         => (string) $quiz->visibility,
            'notification_type' => 'quiz_5m_reminder',
            'action_button'     => 'Join Now',
            'action_url'        => $actionUrl,
            'click_action'      => $actionUrl,
            'scheduled_at'      => (string) $quiz->scheduled_at,
        ];

        // Fetch all active device tokens
        $tokens = DeviceToken::where('is_active', true)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info("No active device tokens found for 5-min Firebase notification (Quiz #{$quizId})");
            return [
                'success' => true,
                'message' => 'No active device tokens registered.',
                'sent'    => 0,
                'total'   => 0
            ];
        }

        $result = $this->sendToTokens($tokens, $title, $body, $payloadData, $quiz->image_url ?? null);

        Log::info("Sent 5-min Firebase quiz notification for Quiz #{$quizId}", [
            'quiz_id' => $quizId,
            'sent'    => $result['sent'],
            'failed'  => $result['failed'],
            'total'   => $result['total'],
        ]);

        return array_merge(['success' => true, 'quiz_id' => $quizId], $result);
    }

    /**
     * Generate dynamic Quiz Join URL based on active domain / APP_URL
     */
    public function getQuizJoinUrl($quizId): string
    {
        $baseUrl = rtrim(config('app.url') ?: env('APP_URL') ?: url('/'), '/');

        // Clean trailing slashes or subpaths
        if (empty($baseUrl) || $baseUrl === 'http://localhost') {
            $baseUrl = app()->environment('local') ? url('/') : 'https://fsrquiz.com';
        }
        $baseUrl = rtrim($baseUrl, '/');

        return "{$baseUrl}/fsrapp/login/{$quizId}?auto_join=1";
    }

    /**
     * Send Push Notification to an FCM Topic using FCM v1 or legacy API
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], ?string $imageUrl = null): array
    {
        $cleanTopic = str_replace('/topics/', '', trim($topic));
        if (empty($cleanTopic)) {
            $cleanTopic = config('firebase.topic', env('FIREBASE_TOPIC', 'quiz_updates'));
        }

        $accessToken = $this->getAccessToken();

        // 1. Send via FCM v1 API (Standard)
        if ($accessToken) {
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            // Format all custom data fields as strings (FCM v1 requirement)
            $stringData = [];
            foreach ($data as $k => $v) {
                $stringData[(string) $k] = is_array($v) ? json_encode($v) : (string) $v;
            }

            $message = [
                'topic'        => $cleanTopic,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data'         => $stringData,
                'android'      => [
                    'priority'     => 'HIGH',
                    'notification' => [
                        'sound'        => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'channel_id'   => 'quiz_alerts',
                        'tag'          => !empty($data['quiz_id']) ? ('quiz_' . $data['quiz_id']) : 'quiz_reminder',
                    ],
                ],
                'webpush'      => [
                    'headers'      => [
                        'Urgency' => 'high',
                    ],
                    'notification' => [
                        'title'   => $title,
                        'body'    => $body,
                        'icon'    => asset('assets/images/logo.png'),
                        'actions' => [
                            [
                                'action' => 'join_now',
                                'title'  => '🚀 Join Now',
                            ],
                        ],
                    ],
                    'fcm_options'  => [
                        'link' => $data['action_url'] ?? (!empty($data['quiz_id']) ? $this->getQuizJoinUrl($data['quiz_id']) : url('/fsrapp/home')),
                    ],
                ],
            ];

            if ($imageUrl) {
                $message['notification']['image'] = $imageUrl;
                $message['android']['notification']['image'] = $imageUrl;
            }

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(15)
                    ->post($url, ['message' => $message]);

                if ($response->successful()) {
                    Log::info("FCM v1 push sent successfully to topic: {$cleanTopic}", [
                        'topic' => $cleanTopic,
                        'response' => $response->json(),
                    ]);
                    return ['success' => true, 'topic' => $cleanTopic, 'response' => $response->json()];
                }

                $respBody = $response->json();
                Log::error("FCM v1 topic push failed for topic {$cleanTopic}: " . json_encode($respBody));
                return ['success' => false, 'topic' => $cleanTopic, 'error' => $respBody, 'status' => $response->status()];
            } catch (\Throwable $e) {
                Log::error("FCM v1 push send error for topic {$cleanTopic}: " . $e->getMessage());
                return ['success' => false, 'topic' => $cleanTopic, 'error' => $e->getMessage()];
            }
        }

        // 2. Fallback to Legacy FCM Server Key if provided
        if (!empty($this->serverKey)) {
            try {
                $toTopic = '/topics/' . $cleanTopic;
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type'  => 'application/json',
                ])->timeout(15)->post('https://fcm.googleapis.com/fcm/send', [
                    'to'           => $toTopic,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                        'sound' => 'default',
                        'image' => $imageUrl,
                    ],
                    'data'         => $data,
                    'priority'     => 'high',
                    'tag'          => !empty($data['quiz_id']) ? ('quiz_' . $data['quiz_id']) : 'quiz_reminder',
                ]);

                if ($response->successful()) {
                    Log::info("Legacy FCM push sent successfully to topic: {$cleanTopic}");
                    return ['success' => true, 'topic' => $cleanTopic, 'response' => $response->json()];
                }
                return ['success' => false, 'topic' => $cleanTopic, 'error' => $response->body()];
            } catch (\Throwable $e) {
                Log::error("Legacy FCM push send error for topic {$cleanTopic}: " . $e->getMessage());
                return ['success' => false, 'topic' => $cleanTopic, 'error' => $e->getMessage()];
            }
        }

        return ['success' => false, 'topic' => $cleanTopic, 'error' => 'No valid Firebase credentials available.'];
    }

    /**
     * Send 5-minute quiz reminder notification to an FCM Topic
     */
    public function sendQuizTopicReminder(Quiz $quiz, ?string $topic = null): array
    {
        $quizId = $quiz->id;
        $quizTitle = $quiz->title ?? 'Live Quiz';
        $quizType = $quiz->visibility === 'fastest_finger_first' ? 'Fastest Finger First' : 'Quiz Challenge';
        $targetTopic = $topic ?: config('firebase.topic', env('FIREBASE_TOPIC', 'quiz_updates'));

        // Title and Body for 5-minute alert
        $title = "⚡ {$quizTitle} - 5 मिनट में शुरू हो रहा है!";
        $body = "{$quizType} '{$quizTitle}' सिर्फ 5 मिनट में शुरू होने वाला है! अभी ऐप खोलें और तैयार रहें।";

        // Target Click URL: Dynamic Login with auto_join to Quiz
        $actionUrl = $this->getQuizJoinUrl($quizId);

        $payloadData = [
            'quiz_id'           => (string) $quizId,
            'quiz_type'         => (string) $quiz->visibility,
            'notification_type' => 'quiz_5m_reminder',
            'action_button'     => 'Join Now',
            'action_url'        => $actionUrl,
            'click_action'      => $actionUrl,
            'scheduled_at'      => (string) $quiz->scheduled_at,
            'topic'             => (string) $targetTopic,
        ];

        $result = $this->sendToTopic($targetTopic, $title, $body, $payloadData, $quiz->image_url ?? null);

        Log::info("Sent 5-min Firebase quiz notification to topic '{$targetTopic}' for Quiz #{$quizId}", [
            'quiz_id' => $quizId,
            'topic'   => $targetTopic,
            'success' => $result['success'] ?? false,
        ]);

        return array_merge(['quiz_id' => $quizId, 'topic' => $targetTopic], $result);
    }

    /**
     * Subscribe one or more FCM device tokens to a topic using Google IID / FCM API
     */
    public function subscribeToTopic($tokens, ?string $topic = null): array
    {
        $targetTopic = $topic ?: config('firebase.topic', env('FIREBASE_TOPIC', 'quiz_updates'));
        $cleanTopic = str_replace('/topics/', '', trim($targetTopic));

        $tokensArray = is_array($tokens) ? $tokens : [$tokens];
        $tokensArray = array_values(array_filter(array_unique($tokensArray)));

        if (empty($tokensArray)) {
            return ['success' => false, 'message' => 'No tokens provided'];
        }

        $accessToken = $this->getAccessToken();
        $serverKey = $this->serverKey;

        $url = 'https://iid.googleapis.com/iid/v1:batchAdd';
        $payload = [
            'to' => '/topics/' . $cleanTopic,
            'registration_tokens' => $tokensArray,
        ];

        try {
            if ($accessToken) {
                $response = Http::withHeaders([
                    'Authorization'     => 'Bearer ' . $accessToken,
                    'access_token_auth' => 'true',
                    'Content-Type'      => 'application/json',
                ])->timeout(10)->post($url, $payload);
            } elseif ($serverKey) {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type'  => 'application/json',
                ])->timeout(10)->post($url, $payload);
            } else {
                Log::warning("FCM topic subscription skipped: No Firebase credentials found.");
                return ['success' => false, 'message' => 'No Firebase credentials available'];
            }

            if ($response->successful()) {
                Log::info("Subscribed " . count($tokensArray) . " token(s) to FCM topic '{$cleanTopic}' successfully", [
                    'topic' => $cleanTopic,
                    'response' => $response->json(),
                ]);
                return ['success' => true, 'topic' => $cleanTopic, 'response' => $response->json()];
            }

            Log::error("FCM topic subscription failed for topic '{$cleanTopic}': " . $response->body());
            return ['success' => false, 'topic' => $cleanTopic, 'error' => $response->body()];
        } catch (\Throwable $e) {
            Log::error("FCM topic subscription error: " . $e->getMessage());
            return ['success' => false, 'topic' => $cleanTopic, 'error' => $e->getMessage()];
        }
    }

    /**
     * Backward-compatible alias
     */
    public function sendQuiz15MinuteReminder(Quiz $quiz): array
    {
        return $this->sendQuiz5MinuteReminder($quiz);
    }
}
