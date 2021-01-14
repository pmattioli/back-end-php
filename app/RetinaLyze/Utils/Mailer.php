<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Utils;

use RetinaLyze\Utils\Config;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Description of Mailer
 *
 * @author mom
 */
class Mailer {

    private $mail;

    function __construct() {
        
    }

    function send($to, $subject, $body) {
        $config = Config::getConfig();
        
        $this->mail = new PHPMailer(); // create a new object
        $this->mail->CharSet = 'UTF-8';
        $this->mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
        if($config['useSMTP'] === true){
            $this->mail->IsSMTP(); // enable SMTP
            $this->mail->SMTPAuth = true;
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Host = $config['smtpHost'];
            $this->mail->Port = 587;
            $this->mail->Username = $config['smtpUsername'];
            $this->mail->Password = $config['smtpPassword'];
        }
        
        $this->mail->IsHTML(true);
        $this->mail->SetFrom($config['mailFromAddress']);
        $this->mail->Subject = $subject;
        $this->mail->Body = $body;
        $this->mail->AddAddress($to);
        if (!$this->mail->Send()) {
            error_log('Could not sent message:' . $this->mail->ErrorInfo);
            return false;
            
        } else {
            return true;
        }
    }

}
