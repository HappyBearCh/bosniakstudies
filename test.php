

<?php
require_once('/home/fhkip2carjh3/public_html/bosniakstudies.org/lists/admin/PHPMailer6/src/PHPMailer.php');
require_once('/home/fhkip2carjh3/public_html/bosniakstudies.org/lists/admin/PHPMailer6/src/SMTP.php');
require_once('/home/fhkip2carjh3/public_html/bosniakstudies.org/lists/admin/PHPMailer6/src/Exception.php');

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->Host = 'localhost';
    $mail->SMTPAuth = false;
    $mail->SMTPSecure = '';
    $mail->SMTPAutoTLS = false;
    $mail->Port = 25;

    $mail->Username = 'info@bosniakstudiees.org';  // change this
    $mail->addAddress('esad.sirbegovic@gmail.com');
    $mail->Subject = 'SMTP Test';
    $mail->Body = 'This is a test email.';

    $mail->send();
    echo 'Message sent!';
} catch (Exception $e) {
    echo "Error: {$mail->ErrorInfo}";
}
?>