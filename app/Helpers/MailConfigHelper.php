<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

class MailConfigHelper
{

    public static function setMailConfig()
    {

        $emailConfig = getCommunicationConfigs('email');


        if(!$emailConfig || !$emailConfig->is_active){
            return false;
        }


        $config = $emailConfig->config;


       Config::set([
            'mail.mailers.smtp.host' => $config['mail_host'],
            'mail.mailers.smtp.port' => $config['mail_port'],
            'mail.mailers.smtp.username' => $config['mail_user_name'],
            'mail.mailers.smtp.password' => $config['mail_password'],
            'mail.mailers.smtp.encryption' => $config['mail_encription_type'],
             'mail.from.address' => $config['mail_user_name'],
            'mail.from.name' => $config['mail_from_name'],
        ]);

          app()->forgetInstance('mail.manager');


        return true;
    }

}