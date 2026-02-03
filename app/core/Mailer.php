<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    public static function send($to, $subject, $body) {

        $mail = new PHPMailer(true);

        try {
            // SMTP mód
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->Port       = MAIL_PORT;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;


            $mail->CharSet = 'UTF-8';

            $mail->setFrom(MAIL_USER, 'Inventory System');

            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            return $mail->send();

        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
}
