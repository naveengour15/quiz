<?php

namespace App\Repositories;


interface WhatsAppRepositoryInterface
{
    public function sendWhatsAppMessage(string $mobileNumber, string $templateName, array $bodyParameters = [], array $buttonParameters = [], $tenant = null);

    /**
     * Send multiple WhatsApp messages in parallel batches.
     *
     * @param array $messages Array of message items ['id', 'mobileNumber', 'templateCode', 'bodyParameters', 'buttonParameters', 'userId', 'tenantId']
     * @param mixed $tenant
     * @return array Results keyed by message ID ['success' => bool, 'status' => string, 'msg' => string, 'response' => mixed]
     */
    public function sendBatchWhatsAppMessages(array $messages, $tenant = null): array;
}