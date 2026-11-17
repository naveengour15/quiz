<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; 

use App\Models\NotificationHistory;

class BuildSmsHelper
{ 
    /**
     * Send SMS (Common for all types)
     */
    public static function send(
        string $number,
        string $message,
        ?string $templateId = null
    ): array {
        try {
            $url = config('services.sms.base_url');

            if (empty($url)) {
                throw new \Exception('SMS_BASE_URL is not configured');
            }

            $response = Http::get($url, [
                'APIKey'        => config('services.sms.api_key'),
                'senderid'      => config('services.sms.sender_id'),
                'channel'       => 2,
                'DCS'           => 0,
                'flashsms'      => 0,
                'number'        => $number,
                'text'          => $message,
                'route'         => config('services.sms.route'),
                'EntityId'      => config('services.sms.entity_id'),
                'dlttemplateid' => $templateId,
                //'dlttemplateid' => $templateId ?? config('services.sms.template_id'),
            ]);

            Log::info('SMS Sent', [
                'number'  => $number,
                'status'  => $response->status(),
                'message' => $message,
                'body'    => $response->body(),
            ]);

            if ($response->successful() && str_contains($response->body(), 'Success')) {
                return [
                    'status'  => true,
                    'message' => 'SMS sent successfully',
                    'message_body'    => $message,
                ];
            }

            return [
                'status'   => false,
                'message'  => 'SMS gateway error',
                'response' => $response->body(),
                'message_body'    => $message,
            ];

        } catch (\Exception $e) {

            Log::error('SMS Failed', [
                'number' => $number,
                'error'  => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Exception occurred',
                'error'   => $e->getMessage(),
                'message_body'    => $message,
            ];
        }
    }

    public static function createNotificationHistory($id, $employeeId, $tenantId, $sendTo, $title, $message, $messageType, $status, $type='SMS')
    {
        // $setStatus = 'pending';
         $setStatus = 'failed';
        if($status == true || $status == 'sent'){
            $setStatus = 'sent';
        }
          $data = array(
            'employee_id' => $employeeId,
            'send_to' => $sendTo,
            'notification_type' => $messageType,
            'notification_type_id' => $id,
            'type' => $type,
            'title' => $title,
            'body' => $message,
            'status' => $setStatus,
        );

        NotificationHistory::create( $data); 
    }

    /********************** Calling Message **********************/ 
    public static function sendOtp($title, $notificationId=null, string $number,string $otp, string $appName = 'ChaloOnline')
    {
        try {
            $message = "Your OTP for {$otp} is {$appName}. Do not share this OTP with anyone. - Team Chaloonline";
  
             //$templateId = 1107176880340485624;
            $res = self::send(
                $number,
                $message,             
            );

            self::createNotificationHistory(
                $notificationId,
                null,
                null,
                $number,
                $title,
                $message,
                'sent otp',
                $res['status'],
            );
            return $res;

        } catch (\Throwable $e) {

            Log::error('OTP SMS sending failed', [
                'number' => $number,
                'app' => $appName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false; // or throw custom exception
        }
    }

    public static function sendStoreCreated($customerId, $tenantId, string $number,string $appName = '- Team Chalo Online')
    {
        try {
            $message = "Your business has been successfully created on Chalo Online. You can now start managing your business online.
       {$appName}";
            //$templateId = 1107176854826135954;

            $res = self::send(
                $number,
                $message
            );

            self::createNotificationHistory(
                $customerId,
                $tenantId,
                $customerId,
                $number,
                'store created',
                $message,
                'store created',
                $res['status'],
            );
            return $res;
        } catch (\Throwable $e) {
            // Log the error
            Log::error('Failed to send business creation message', [
                'number' => $number ?? null,
                'template_id' => $templateId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Optional: rethrow or return false
            return false;
        }

    }

    public static function sendStoreFormSubmitted($notificationId, string $number, string $appName = '- Team Chalo Online')
    {
        try {
            $message = "Your business details have been submitted successfully on Chalo Online. Our team will review and update you shortly.
{$appName}";
            $templateId = 1107176882428987638;

            $res = self::send(
                $number,
                $message
            );
            
            self::createNotificationHistory(
                $notificationId,
                null,
                null,
                $number,
                'store form submitted',
                $message,
                'store form submitted',
                $res['status'],
            );
            return $res;
        } catch (\Throwable $e) {
            error_log('Business Details Form SMS Send Failed: ' . $e->getMessage());
            error_log($e->getTraceAsString());

            return false; // or handle as needed
        }
    }

    
    public static function sendPasswordChangedSMS($id, $tenant, $customerId, $number)
    {
        $result = self::sendPasswordChanged($number, $tenant->name);

        $message = $result['message_body'] ?? '';
        $status = $result['status'] ? 'sent' : 'failed';

        self::createNotificationHistory(
            $id,
            $customerId,
            $tenant->id,
            $number,
            'Update Password Successful',
            $message,
            'change password',
            $status
        );

        return $result;
    }

    public static function sendPasswordChanged(
        string $number,
        string $retailerName,
        string $appName = '- Team Chalo Online'
    ): array {

        try {
            $message = "Dear {$retailerName},
Your account password has been changed successfully. If this was not done by you, please contact support immediately.
{$appName}
";
            return self::send($number, $message);

        } catch (\Throwable $e) {

            Log::error('Password Change SMS Failed', [
                'number' => $number,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'SMS sending failed',
                'error' => $e->getMessage(),
                'message_body' => '',
            ];
        }
    }

    
    public static function sendPlanActivation(
        $notificationId,
        $tenant,
        string $planName,
        string $planPrice,
        string $appName = '- Team Chalo Online')
    {

        try {
            $message = "Dear {$tenant->name},
Your {$planName} plan Rs {$planPrice} has been activated successfully on Chalo Online. You can now access all the features included in your plan.
{$appName}";

            //$templateId = 1107176882627722632;

            $res = self::send($tenant->phone, $message);

            self::createNotificationHistory(
                $notificationId,
                $tenant->id,
                $tenant->user_id,
                $tenant->phone,
                'store created',
                $message,
                'store created',
                $res['status'],
            );
            return $res;

        } catch (\Throwable $e) {
            // Log the error
            error_log(
                '[SMS_SEND_ERROR] ' .
                'Number: ' . $tenant->phone . ' | ' .
                'Error: ' . $e->getMessage() . ' | ' .
                'File: ' . $e->getFile() . ':' . $e->getLine()
            );

            // Optional: return false or throw custom exception
            return false;
        }
    }

    public static function sendPlanExpireSoon($data, string $appName = '- Team Chalo Online')
    {
        try {
       
        $number = $data['mobile'];
        $retailerName = $data['customer_name'];
        $expireDate = $data['plan_end_date'];

            $message = "Dear {$retailerName},
Your Chalo Online plan will expire on {$expireDate}. Please renew to avoid service interruption.
{$appName}";
            Log::info('Sending plan expiry soon SMS', [
                'number' => $number,
                'retailer' => $retailerName,
                'expire_date' => $expireDate,
            ]);
            
            $res = self::send($number, $message);

            self::createNotificationHistory(
                $data['notification_type_id'],
                $data['tenant_id'],
                $data['customer_id'],
                $number,
                'expire plan soon',
                $message,
                'expire plan soon',
                $res['status'],
            );
            return $res;

        } catch (\Throwable $e) {
            Log::error('Failed to send plan expiry soon SMS', [
                'number' => $number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false; // or throw $e; if you want to propagate
        }
    }

    public static function sendPlanExpired($data,string $appName = '- Team Chalo Online')
    {
        try {
            $number = $data['mobile'];
            $retailerName = $data['customer_name'];
            $expireDate = $data['plan_end_date'];

            $message = "Dear {$retailerName},
Your Chalo Online plan expired on {$expireDate}. Your website is now deactivated. Please renew to continue services.
{$appName}";
            Log::info('Sending plan expired SMS', [
                'number' => $number,
                'retailer' => $retailerName,
                'expire_date' => $expireDate,
            ]);
            
            $res = self::send($number, $message);
            
            self::createNotificationHistory(
                $data['notification_type_id'],
                $data['tenant_id'],
                $data['customer_id'],
                $number,
                'expired plan',
                $message,
                'expired plan',
                $res['status'],
            );
            return $res;

        } catch (\Throwable $e) {
            Log::error('Failed to send plan expired SMS', [
                'number' => $number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false; // or throw $e; if you want to propagate
        }
    }

    public static function sendStoreActivation($tenant, $customer, string $appName = '- Team Chalo Online')
    {
       try {
            $number = $tenant->phone;
            $retailerName = $customer->name;
            $message = "Dear {$retailerName},
        Your business store has been activated successfully on Chalo Online. Your website is now live for customers.
        {$appName}";
           
            Log::info('Sending store activation SMS', [
                'number' => $number,
                'retailer_name' => $retailerName,
            ]);

            $res = self::send(
                $number,
                $message
            );

            Log::info('Store activation SMS sent successfully', [
                'number' => $number,
                'response' => $res,
            ]);

            self::createNotificationHistory(
                $tenant->id,
                $tenant->id,
                $customer->id,
                $tenant->phone,
                'store activated',
                $message,
                'store activated',
                $res['status'],
            );
            return $res;

        } catch (\Throwable $e) {
            Log::error('Failed to send store activation SMS', [
                'number' => $number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false; // or throw $e; depending on your flow
        }
    }

    public static function sendStoreDeactivation($tenant, $customer, string $appName = '- Team Chalo Online')
    {
        try {
            $number = $tenant->phone;
            $retailerName = $customer->name;
            $message = "Dear {$retailerName},
        Your business website has been temporarily deactivated on Chalo Online.
        Please contact support for more information.
        {$appName}";

            $res = self::send(
                $number,
                $message
            );

            Log::info('SMS sent successfully', [
                'number' => $number,
                'template_id' => config('services.sms.otp_template_id'),
                'response' => $res,
            ]);

            self::createNotificationHistory(
                $tenant->id,
                $tenant->id,
                $customer->id,
                $tenant->phone,
                'store deactivated',
                $message,
                'store deactivated',
                $res['status'],
            );
            return $res;

        } catch (\Throwable $e) {

            Log::error('Failed to send SMS', [
                'number' => $number ?? null,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Option 1: rethrow exception
            throw $e;

            // Option 2: return false / custom response
            // return false;
        }
    }


    public static function sendServicesActivation(string $number, string $service,string $appName = '- Team Chalo Online')
    {
        try {
            $message = "Your {$service} service has been activated successfully. You can now start using this service.
        {$appName}";

            return self::send(
                $number,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('SMS sending failed', [
                'number' => $number,
                'service' => $service ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Option 1: return false
            return false;

            // Option 2: rethrow (use if you want upstream handling)
            // throw $e;
        }
    }

    public static function sendServicesDeactivation(string $number, string $service, string $appName = '- Team Chalo Online')
    {
        try {
            $message = "Service deactivated SMS:
        Your {$service} service has been deactivated. Please renew or contact support to continue using it.
        {$appName}";

            return self::send(
                $number,
                $message
            );
        } catch (\Throwable $e) {
            error_log('SMS send failed: ' . $e->getMessage());

            // optional: rethrow or return false
            return false;
        }

    }

    public static function sendServicesReminders(string $number,string $service,string $days,string $appName = '- Team Chalo Online')
    {    
        try {
            $message = "Expiry Reminder SMS:
        Alert: Your {$service} service will expire on {$days} day(s).
        Please renew or contact support to continue using it.
        {$appName}";

            return self::send(
                $number,
                $message
            );

        } catch (\Throwable $e) {
            error_log(
                '[SMS ERROR] ' . $e->getMessage() .
                ' | Number: ' . $number .
                ' | Service: ' . $service
            );

            return false;
        }
    }

    

    public static function sendLowBalance(string $number, string $remaining, string $service, string $appName = '- Team Chalo Online')
    {       
        $message = "Low Balance SMS:
        Alert: Only {$remaining} {$service} remaining in your account.
	    Please recharge to continue services.
        {$appName} ";

        return self::send(
            $number,
            $message,
            config('services.sms.otp_template_id')
        );
    }


    public static function sendOrderSuccessSMS($tenantId, $tenantName, $phone, $order, $customerId)
    {
 
        $result = self::sendOrderSuccess($phone, $order->order_number, '- '.$tenantName);
        if (!empty($result['response'])) {
            $message = $result['message_body'];
            $status = 'sent';
            $title = 'Order Successful';
            if($result['status'] == false) {
                $status = 'failed';
            }
            self::createNotificationHistory(
                $order->id,
                $customerId,
                $tenantId,
                $phone,
                $title,
                $message,
                'order',
                $status,
            );
        }
    }
    
    public static function sendOrderSuccess($number, $orderNo, string $appName = '- Team Chalo Online')
    {
        try {
            $message = "Your order has been placed successfully.
            Your Order Number : {$orderNo}.
            Thank you for shopping with us!
            {$appName}";

                $response = self::send(
                    $number,
                    $message,
                );

                Log::info('Order SMS sent successfully', [
                    'order_no' => $orderNo,
                    'mobile'   => $number,
                    'response' => $response,
                ]);

                return $response;

            } catch (\Throwable $e) {

                Log::error('Failed to send Order SMS', [
                    'order_no' => $orderNo ?? null,
                    'mobile'   => $number ?? null,
                    'error'    => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);

                // Optionally rethrow or return false
                throw $e;
                // return false;
            }
    }


    // ************** SMS ORDER TEMPLATE 25 feb **************

    public static function sendOrderStatusSMS(array $data, $notificationId = null)
    {
        try {

            // 1️⃣ Status-wise template lao
            $template = self::getOrderTemplateByStatus($data['status']);

            if (!$template) {
                Log::warning('Order SMS template not found', [
                    'status'   => $data['status'] ?? null,
                    'order_no' => $data['order_no'] ?? null,
                ]);
                return false;
            }

            // 2️⃣ Final message banao
            $message = self::bindTemplate($template, $data);

            // 3️⃣ SMS send (same as OTP)
            $res = self::send(
                $data['mobile'],
                $message
            );

            // 4️⃣ ✅ TABLE INSERT (SAME AS OTP)


            self::createNotificationHistory(
                $data['notification_type_id'],
                $data['customer_id'],
                $data['tenant_id'],
                $data['mobile'],
                self::formatStatusTitle($data['status'] ?? ''),
                $message,
                self::formatStatusTitle($data['status'] ?? ''),
                self::formatStatusTitle($data['status'] ?? ''),
            );



            return $res;
        } catch (\Throwable $e) {

            Log::error('Order SMS sending failed', [
                'mobile'   => $data['mobile'] ?? null,
                'order_no' => $data['order_no'] ?? null,
                'status'   => $data['status'] ?? null,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return false;
        }
    }



    private static function getOrderTemplateByStatus(string $status): ?string
    {
        $templates = [

            'out_for_delivery' =>
            "Dear {customer_name},
	Good news! Your order {order_no} with {shop_name} is out for delivery and will reach you soon. For any assistance, contact our support team.
	- Team Chaloonline",

            'delivered' =>
            "Dear {customer_name},
Your order {order_no} with {shop_name} has been successfully delivered. We hope you enjoy your purchase! For any assistance, please feel free to reach out to our support team.
- Team Chaloonline",

            'refunded' =>
            "Dear {customer_name},
Your refund for order {order_no} with {shop_name} has been successfully processed. The amount will reflect in your original payment method shortly. For any queries, please contact our support team.
- Team Chaloonline",

            'returned' =>
            "Dear {customer_name},
Your order {order_no} with {shop_name} has been successfully returned. If you need any further assistance, please feel free to contact our support team.
- Team Chaloonline",

            'cancelled' =>
            "Dear {customer_name},
Your order {order_no} with {shop_name} has been successfully cancelled. For any assistance, please feel free to reach out to our support team.
- Team Chaloonline",

            'order_tracking' =>
            "Dear {customer_name},
Exciting update! Your order {order_no} from {shop_name} is on the way with {courier_company}. Tracking ID: {tracking_id}.
- Team Chaloonline",
        ];

        return $templates[$status] ?? null;
    }

    private static function bindTemplate(string $template, array $data)
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
    private static function formatStatusTitle(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    /************** END Of CLASS **************/
}
