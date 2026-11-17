<?php

namespace App\Repositories;

use App\Models\WhatsAppTemplateSetting;
use App\Models\User;
use App\Models\NotificationHistory;
use Illuminate\Support\Facades\Log;

class WhatsAppRepository implements WhatsAppRepositoryInterface
{
    public function sendWhatsApp(array $data, $tenantId = null)
    {
        try {

            $ch = curl_init();

            // Tenant
            //$tenantId = getTenantId();

            // $whatsapp = getCommunicationConfigs('whatsapp');
           
            // if (empty($whatsapp) || $whatsapp->is_active==0) {
            //    Log::error('WhatsApp send failed: WhatsApp template setting is not configured');
            //     return false;
            // }

            // $whatsapp_api_url = $whatsapp->config['whatsapp_api_url'];
            // $whatsapp_api_token = $whatsapp->config['token'];

            

            $whatsapp_api_url = config('services.whatsapp.url') ?: env('WHATSAPP_API_URL');
            $whatsapp_api_token = config('services.whatsapp.token') ?: env('WHATSAPP_TOKEN');
            
            // Validate URL
            
            if (empty($whatsapp_api_url)) {
                Log::error('WhatsApp CURL execution failed', [
                    'error' => 'API URL is empty or not configured',
                ]);
                curl_close($ch);
                return false;
            }

            curl_setopt($ch, CURLOPT_URL, $whatsapp_api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    
            $token = $whatsapp_api_token;

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $token,
                "Content-Type: application/json"
            ]);
    
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                Log::error('WhatsApp CURL execution failed', [
                    'error' => $error_msg,
                    'url' => $whatsapp_api_url,
                ]);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            $responseData = json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('WhatsApp CURL execution failed', [
                    'error' => 'Invalid JSON response',
                    'raw_response' => substr($response, 0, 500),
                ]);
                return false;
            }

            $status = 'failed';
            if (isset($responseData->messages[0]->message_status)) {
                $status = $responseData->messages[0]->message_status == 'accepted' ? 'sent' : $responseData->messages[0]->message_status;
            } elseif (isset($responseData->error)) {
                $status = 'failed';
            }

            $mobileNumber = $data['to'] ?? '';
            $templateName = $data['template']['name'] ?? 'whatsapp';
            
            // Try to find user by phone (query only id to reduce memory)
            $userMobile = substr($mobileNumber, 2);
            $user = User::where('mobile', $userMobile)->where('tenant_id', $tenantId)->select(['id'])->first();

            NotificationHistory::create([
                'tenant_id' => $tenantId,
                'user_id' => $user ? $user->id : null,
                'notification_type' => $templateName,
                'type' => 'Whatsapp',
                'send_to' => $mobileNumber,
                'title' => $templateName,
                'status' => $status,
            ]);

            return $response;       

        } catch (\Throwable $th) {
            Log::error('WhatsApp Send Failed', [
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
            ]);
        }   
    }

    public function sendWhatsAppMessage(
        string $mobileNumber,
        string $templateCode,
        array $bodyParameters = [],
        array $buttonParameters = [],
        $tenant = null
    ) {
        $templateName = '';

        try {   
            
            if ($tenant === null ){
                $tenantId = getTenantId();
                
            } if ($tenant && !empty($tenant->id)) {
                $tenantId = $tenant->id;
            }
           
            // Guard: ensure we have a tenant instance before accessing its id
            if (is_null($tenantId) || !isset($tenantId)) {
                Log::error('WhatsApp send failed: tenant not resolved', [
                    'mobile' => $mobileNumber,
                    'templateCode' => $templateCode,
                ]);
                return false;
            }

            $template = WhatsAppTemplateSetting::where('template_code', $templateCode)
                ->where('status','active')
                ->first();
            // if (empty($template)) {
            //     Log::error('WhatsApp send failed: whatsapp template not enabled', [
            //         'mobile' => $mobileNumber,
            //         'templateCode' => $templateCode,
            //     ]);
            //     return false;
            // }
            
           
            $templateName = '';
            if ($template && $template->status == 'active') {
                $languageCode = $template->language_code;
                $templateName = $template->template_name;
            } else{
                Log::error('WhatsApp Template Not Active', [
                    'template' => $templateName,
                ]);
                return false;
            }
            // Remove non numeric characters
            $mobileNumber = preg_replace('/\D/', '', $mobileNumber);

            // Add India country code
            if (!str_starts_with($mobileNumber, '91')) {
                $mobileNumber = '91' . $mobileNumber;
            }

            $templateBodyParameters = [];

            foreach ($bodyParameters as $param) {

                // Named parameter
                if (is_array($param)) {

                    $templateBodyParameters[] = [
                        "type" => "text",
                        "parameter_name" => $param['name'],
                        "text" => (string) $param['value']
                    ];

                } else {

                    // Normal numeric parameter
                    $templateBodyParameters[] = [
                        "type" => "text",
                        "text" => (string) $param
                    ];
                }
            }

            $components = [];

            if (!empty($templateBodyParameters)) {

                $components[] = [
                    "type" => "body",
                    "parameters" => $templateBodyParameters
                ];
            }
            /*
            |--------------------------------------------------------------------------
            | Button Parameters
            |--------------------------------------------------------------------------
            */

            if (!empty($buttonParameters)) {

                foreach ($buttonParameters as $button) {

                    $components[] = [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => (string) ($button['index'] ?? 0),
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => (string) ($button['value'] ?? '')
                            ]
                        ]
                    ];
                }
            }
            $data = [
                "messaging_product" => "whatsapp",
                "to" => $mobileNumber,
                "type" => "template",
                "template" => [
                    "name" => $templateName,
                    "language" => [
                        "code" => $languageCode
                    ],
                    "components" => $components
                ]
            ];
            if (config('app.debug')) {
                Log::debug('WhatsApp Template Payload', [
                    'template' => $templateName,
                    'payload' => $data
                ]);
            }

            return $this->sendWhatsApp($data, $tenantId);

        } catch (\Throwable $e) {

            Log::error('WhatsApp Template Failed', [
                'mobile' => $mobileNumber,
                'template' => $templateName,
                'body_parameters' => $bodyParameters,
                'button_parameters' => $buttonParameters,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return false;
        }
    }

    /**
     * Send multiple WhatsApp messages in high-speed parallel batches using curl_multi.
     *
     * @param array $messages Array of message items ['id', 'mobileNumber', 'templateCode', 'bodyParameters', 'buttonParameters', 'userId', 'tenantId']
     * @param mixed $tenant
     * @return array Results keyed by message ID
     */
    public function sendBatchWhatsAppMessages(array $messages, $tenant = null): array
    {
        try {
            if (empty($messages)) {
                Log::info('WhatsApp batch skipped: no messages provided');
                return [];
            }

            // De-duplicate incoming batch by message id
            $messages = collect($messages)
                ->unique('id')
                ->values()
                ->all();

            Log::info('WhatsApp batch processing started', [
                'total_messages' => count($messages),
            ]);

            $defaultTenantId = null;

            if ($tenant && !empty($tenant->id)) {
                $defaultTenantId = $tenant->id;
            } elseif (function_exists('getTenantId')) {
                $defaultTenantId = getTenantId();
            }

            $defaultTenantId = $defaultTenantId ?: 1;

            // Pre-fetch all required active templates in 1 query
            $templateCodes = collect($messages)
                ->pluck('templateCode')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            $templates = WhatsAppTemplateSetting::whereIn('template_code', $templateCodes)
                ->where('status', 'active')
                ->get()
                ->keyBy('template_code');

            Log::info('WhatsApp templates loaded', [
                'requested_templates' => count($templateCodes),
                'active_templates' => $templates->count(),
                'tenant_id' => $defaultTenantId,
            ]);

            $preparedRequests = [];
            $results = [];
            $historyBatch = [];
            $now = now();

            // Build WhatsApp API payloads
            foreach ($messages as $msg) {
                try {
                    $msgId = $msg['id'] ?? uniqid('msg_', true);
                    $mobileNumber = $msg['mobileNumber'] ?? '';
                    $templateCode = $msg['templateCode'] ?? '';
                    $bodyParameters = $msg['bodyParameters'] ?? [];
                    $buttonParameters = $msg['buttonParameters'] ?? [];
                    $userId = $msg['userId'] ?? null;
                    $tenantId = $msg['tenantId'] ?? $defaultTenantId;

                    $template = $templates->get($templateCode);

                    if (!$template) {
                        Log::warning('WhatsApp template not found/inactive', [
                            'message_id' => $msgId,
                            'template_code' => $templateCode,
                            'user_id' => $userId,
                        ]);

                        $results[$msgId] = [
                            'success' => false,
                            'status' => 'failed',
                            'msg' => "WhatsApp Template '{$templateCode}' not active or not found",
                            'response' => null,
                        ];

                        continue;
                    }

                    // Clean and format mobile number
                    $cleanMobile = preg_replace('/\D/', '', (string) $mobileNumber);

                    if (empty($cleanMobile)) {
                        Log::warning('Invalid WhatsApp mobile number', [
                            'message_id' => $msgId,
                            'mobile' => $mobileNumber,
                            'user_id' => $userId,
                        ]);

                        $results[$msgId] = [
                            'success' => false,
                            'status' => 'failed',
                            'msg' => 'Invalid mobile number',
                            'response' => null,
                        ];

                        continue;
                    }

                    if (!str_starts_with($cleanMobile, '91')) {
                        $cleanMobile = '91' . $cleanMobile;
                    }

                    // Anti-duplicate firewall
                    $quizId = null;

                    foreach ($bodyParameters as $p) {
                        if (
                            is_array($p) &&
                            ($p['name'] ?? '') === 'quiz_link'
                        ) {
                            $quizId = explode(
                                '?',
                                (string) ($p['value'] ?? '')
                            )[0] ?? null;
                        }
                    }

                    if (
                        $templateCode === 'Quiz_Joined_Alert' &&
                        $quizId &&
                        $userId
                    ) {
                        $alreadySent = \App\Models\QuizNotification::where('quiz_id', $quizId)
                            ->where('user_id', $userId)
                            ->where('status', 'Sent')
                            ->whereNotNull('sent_date_time')
                            ->exists();

                        if ($alreadySent) {
                            Log::info('WhatsApp duplicate prevented', [
                                'message_id' => $msgId,
                                'quiz_id' => $quizId,
                                'user_id' => $userId,
                                'mobile' => $cleanMobile,
                            ]);

                            $results[$msgId] = [
                                'success' => true,
                                'status' => 'sent',
                                'msg' => 'WhatsApp message already sent for this quiz (duplicate prevented)',
                                'response' => null,
                            ];

                            continue;
                        }
                    }

                    // Format body parameters
                    $templateBodyParameters = [];

                    foreach ($bodyParameters as $param) {
                        if (is_array($param)) {
                            $templateBodyParameters[] = [
                                'type' => 'text',
                                'parameter_name' => (string) ($param['name'] ?? ''),
                                'text' => (string) ($param['value'] ?? ''),
                            ];
                        } else {
                            $templateBodyParameters[] = [
                                'type' => 'text',
                                'text' => (string) $param,
                            ];
                        }
                    }

                    $components = [];

                    if (!empty($templateBodyParameters)) {
                        $components[] = [
                            'type' => 'body',
                            'parameters' => $templateBodyParameters,
                        ];
                    }

                    if (!empty($buttonParameters)) {
                        foreach ($buttonParameters as $button) {
                            $components[] = [
                                'type' => 'button',
                                'sub_type' => 'url',
                                'index' => (string) ($button['index'] ?? 0),
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => (string) ($button['value'] ?? ''),
                                    ],
                                ],
                            ];
                        }
                    }

                    $payload = [
                        'messaging_product' => 'whatsapp',
                        'to' => $cleanMobile,
                        'type' => 'template',
                        'template' => [
                            'name' => $template->template_name,
                            'language' => [
                                'code' => $template->language_code ?? 'en',
                            ],
                            'components' => $components,
                        ],
                    ];

                    $preparedRequests[$msgId] = [
                        'payload' => $payload,
                        'cleanMobile' => $cleanMobile,
                        'templateName' => $template->template_name,
                        'userId' => $userId,
                        'tenantId' => $tenantId,
                    ];

                } catch (\Throwable $e) {
                    Log::error('WhatsApp message preparation failed', [
                        'message_id' => $msg['id'] ?? null,
                        'template_code' => $msg['templateCode'] ?? null,
                        'user_id' => $msg['userId'] ?? null,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $msgId = $msg['id'] ?? uniqid('msg_', true);

                    $results[$msgId] = [
                        'success' => false,
                        'status' => 'failed',
                        'msg' => 'Message preparation failed',
                        'response' => null,
                    ];
                }
            }

            // Execute HTTP requests
            if (!empty($preparedRequests)) {
                Log::info('WhatsApp concurrent requests started', [
                    'total_requests' => count($preparedRequests),
                ]);

                try {
                    $concurrentResults = $this->sendConcurrentWhatsAppRequests(
                        $preparedRequests
                    );

                    Log::info('WhatsApp concurrent requests completed', [
                        'total_results' => count($concurrentResults),
                    ]);

                } catch (\Throwable $e) {
                    Log::error('WhatsApp concurrent request batch failed', [
                        'total_requests' => count($preparedRequests),
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Mark all prepared requests as failed
                    $concurrentResults = [];

                    foreach ($preparedRequests as $msgId => $reqMeta) {
                        $concurrentResults[$msgId] = [
                            'success' => false,
                            'status' => 'failed',
                            'msg' => 'WhatsApp API batch request failed',
                            'response' => null,
                        ];
                    }
                }

                foreach ($concurrentResults as $msgId => $res) {
                    $results[$msgId] = $res;

                    $reqMeta = $preparedRequests[$msgId] ?? null;

                    if (!$reqMeta) {
                        continue;
                    }

                    $historyBatch[] = [
                        'tenant_id' => $reqMeta['tenantId'],
                        'user_id' => $reqMeta['userId'],
                        'notification_type' => $reqMeta['templateName'],
                        'type' => 'Whatsapp',
                        'send_to' => $reqMeta['cleanMobile'],
                        'title' => $reqMeta['templateName'],
                        'status' => $res['status'] ?? 'failed',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Bulk insert NotificationHistory
                if (!empty($historyBatch)) {
                    try {
                        foreach (array_chunk($historyBatch, 500) as $chunk) {
                            NotificationHistory::insert($chunk);
                        }

                        Log::info('WhatsApp notification history inserted', [
                            'total_history' => count($historyBatch),
                        ]);

                    } catch (\Throwable $e) {
                        Log::error('Bulk NotificationHistory insert failed', [
                            'total_history' => count($historyBatch),
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);
                    }
                }
            }

            $successCount = collect($results)
                ->where('success', true)
                ->count();

            $failedCount = count($results) - $successCount;

            Log::info('WhatsApp batch processing completed', [
                'total' => count($results),
                'success' => $successCount,
                'failed' => $failedCount,
            ]);

            return $results;

        } catch (\Throwable $e) {

            Log::critical('WhatsApp batch processing crashed', [
                'total_messages' => count($messages),
                'tenant_id' => $tenant->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'batch_error' => [
                    'success' => false,
                    'status' => 'failed',
                    'msg' => 'WhatsApp batch processing failed',
                    'response' => null,
                ],
            ];
        }
    }

    /**
     * Send array of payloads using persistent HTTP connection with 0% CPU consumption.
     */
    protected function sendConcurrentWhatsAppRequests(array $requests): array
    {
        $apiUrl = config('services.whatsapp.url') ?: env('WHATSAPP_API_URL');
        $token = config('services.whatsapp.token') ?: env('WHATSAPP_TOKEN');

        if (empty($apiUrl) || empty($token)) {
            Log::error('WhatsApp Batch Send: API URL or Token is empty/unconfigured');
            $results = [];
            foreach ($requests as $key => $req) {
                $results[$key] = [
                    'success' => false,
                    'status' => 'failed',
                    'msg' => 'WhatsApp API URL or Token not configured',
                    'response' => null,
                ];
            }
            return $results;
        }

        $results = [];

        // Single persistent cURL handle with Keep-Alive (Zero CPU usage, instant reuse)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Connection: keep-alive',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        foreach ($requests as $key => $req) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req['payload']));
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $isSuccess = false;
            $msgStatus = 'failed';
            $errorMsg = null;

            if ($curlError) {
                $errorMsg = 'cURL Error: ' . $curlError;
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                $responseData = json_decode($response);
                if (json_last_error() === JSON_ERROR_NONE && !isset($responseData->error)) {
                    $isSuccess = true;
                    $msgStatus = 'sent';
                    if (isset($responseData->messages[0]->message_status)) {
                        $msgStatus = $responseData->messages[0]->message_status == 'accepted' ? 'sent' : $responseData->messages[0]->message_status;
                    }
                } else {
                    $errorMsg = $responseData->error->message ?? 'API returned error response';
                }
            } else {
                $errorMsg = "HTTP error code {$httpCode}";
            }

            $results[$key] = [
                'success' => $isSuccess,
                'status' => $msgStatus,
                'msg' => $isSuccess ? 'WhatsApp message sent successfully' : ($errorMsg ?? 'Failed'),
                'response' => $response,
            ];
        }

        curl_close($ch);

        return $results;
    }
    
    /******************************* END OF CLASS ********************************/ 
}