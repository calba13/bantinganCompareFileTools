<?php
namespace Modules\Common\Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail 
{
    public static function mailer($addresses=null,$subject=null, $body=null)
    {
        $mail = new PHPMailer;
        
        $mail->XMailer = ' ';
        $mail->CharSet = MAIL_SETTINGS["CharSet"];
        $mail->isHTML(MAIL_SETTINGS["IsHTML"]);
        $mail->setFrom(MAIL_SETTINGS["MailDefaultFromEmail"], MAIL_SETTINGS["MailDefaultFromName"]);
        $mail->addReplyTo(MAIL_SETTINGS["MailDefaultReplyEmail"], MAIL_SETTINGS["MailDefaultReplyName"]);

        if (MAIL_SETTINGS["IsSMTP"])
        {
            $mail->IsSMTP(MAIL_SETTINGS["IsSMTP"]);
            $mail->SMTPDebug = MAIL_SETTINGS["SMTPDebug"];
            $mail->Host = MAIL_SETTINGS["SMTPHost"];
            $mail->SMTPAuth = MAIL_SETTINGS["SMTPAuth"];        
            $mail->Username = MAIL_SETTINGS["SMTPUsername"]; 
            $mail->Password = MAIL_SETTINGS["SMTPPassword"]; 
            $mail->SMTPSecure = MAIL_SETTINGS["SMTPSecure"];
            $mail->Port = MAIL_SETTINGS["SMTPPort"];
        } else {
            $mail->isMail(true);
        }

        if ($addresses != null) {
            foreach($addresses as $key => $address) {
                if (is_string($key)) {
                    $mail->AddAddress($address, $key);               
                } else {
                    $mail->AddAddress($address);
                }            
            }   
        }

        if ($subject != null) {
            $mail->Subject = $subject;
        }

        if ($body != null) {            
            $mail->Body = $body;
        }

        return $mail;
    }

    public static function send($addresses, $from_email, $from_name, $subject, $body)
    {
        $mail = new PHPMailer;
        
        $mail->XMailer = ' ';
        $mail->CharSet = MAIL_SETTINGS["CharSet"];

        if (MAIL_SETTINGS["IsSMTP"])
        {
            $mail->IsSMTP(MAIL_SETTINGS["IsSMTP"]);
            $mail->SMTPDebug = MAIL_SETTINGS["SMTPDebug"];
            $mail->Host = MAIL_SETTINGS["SMTPHost"];
            $mail->SMTPAuth = MAIL_SETTINGS["SMTPAuth"];        
            $mail->Username = MAIL_SETTINGS["SMTPUsername"]; 
            $mail->Password = MAIL_SETTINGS["SMTPPassword"]; 
            $mail->SMTPSecure = MAIL_SETTINGS["SMTPSecure"];
            $mail->Port = MAIL_SETTINGS["SMTPPort"];
        } else {
            $mail->isMail(true);
        }
        
        // fill mail with data
        $mail->From = $from_email;
        $mail->FromName = $from_name;
        foreach($addresses as $key => $address) {
            if (is_string($key)) {
                $mail->AddAddress($address, $key);               
            } else {
                $mail->AddAddress($address);
            }          
        }        
        $mail->Subject = $subject;

        $mail->isHTML(MAIL_SETTINGS["IsHTML"]);
        $mail->Body = $body;

        if($mail->send()){
			return ['success' => true,'messages' => 'Email Sent!'];
		} else {
            return ['success' => false, 'messages' => $mail->ErrorInfo];
		}
    }
}