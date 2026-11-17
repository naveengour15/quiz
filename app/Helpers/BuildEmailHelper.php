<?php

namespace App\Helpers;

use App\Mail\TestMail;
use App\Models\NotificationHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BuildEmailHelper
{
   
    public static function sendOtpMail($email, $otp)
    {
        $html = "
        <p style='font-size:15px; color:#555;'>
        Thank you for registering on <strong>FSR Quiz</strong>.
        </p>

        <p style='font-size:15px; color:#555; margin-top:10px;'>
        Your Email Verification OTP is:
        </p>

        <h2 style='letter-spacing:3px; color:#263e9e;'>{$otp}</h2>

        <p style='font-size:14px; color:#777; margin-top:10px;'>
        This OTP is valid for 5 minutes. Please do not share it with anyone.
        </p>

        <p style='margin-top:20px; font-size:14px; color:#777;'>
        Regards,<br>
        <strong>FSR Quiz Team</strong>
        </p>
     ";
     
        $data = [
            'name' => 'FSR Quiz User',
            'email' => $email,
        ];

        $res = self::sendMailOtp(
            [
                'name' => $data['name'],
                'message' => $html,
            ],
            'Your FSR Quiz Email OTP',
            $data['email']
        );

        self::createNotification(
            $data,
            'FSR Quiz otp',
            'Your FSR Quiz Email OTP',
            $html,
            $res
        );

    }
    

    /*************************** Send Mail ***************************/
    public static function sendMail($data, $subject, $email = 'support@chaloonline.in')
    {
        try {
            // Mail::to($email)->queue(new TestMail($data, $subject));
            // Mail::to($email)->send(new TestMail($data, $subject));
            // Log::info('Mail sent successfully', [
            //     'email' => $email,
            //     'subject' => $subject,
            // ]);

            // return [
            //     'status' => 'sent',
            //     'message' => 'Mail added to queue successfully',
            // ];

            
            return [
                'status' => 'failed',
                'message' => 'Mail added to queue successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Mail sending failed', [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public static function sendQueueMail($data, $subject, $email = 'support@chaloonline.in')
    {
        try {
            Mail::to($email)->queue(new TestMail($data, $subject));
            Log::info('Mail sent successfully', [
                'email' => $email,
                'subject' => $subject,
            ]);

            return [
                'status' => 'sent',
                'message' => 'Mail added to queue successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Mail sending failed', [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public static function createNotification($data,
        $notification_type,
        $title,
        $html,
        $res)
    {
        if (! empty($data['notification_type_id'])) {

            $createData = [
                'tenant_id' => $data['tenant_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'notification_type_id' => $data['notification_type_id'],
                'notification_type' => $notification_type,
                'send_to' => $data['email'] ?? null,
                'type' => 'EMAIL',
                'title' => $title,
                'body' => $html,
                'status' => $res['status'] ?? null,
            ];

            NotificationHistory::create($createData);
        }
    }
  

    public static function sendMailOtp($data, $subject, $email = 'support@chaloonline.in')
    {
        try {
            //Mail::to($email)->queue(new TestMail($data, $subject));
            Mail::to($email)->send(new TestMail($data, $subject));
            Log::info('Mail sent successfully', [
                'email' => $email,
                'subject' => $subject,
            ]);

            return [
                'status' => 'sent',
                'message' => 'Mail added to queue successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Mail sending failed', [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /******************** END ********************/
}
