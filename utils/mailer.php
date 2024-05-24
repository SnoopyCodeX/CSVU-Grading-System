<?php 
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = $FirstDir['0'];

require_once "{$_SERVER['DOCUMENT_ROOT']}/{$rootFolder}/configuration/constants.php";
require_once "{$_SERVER['DOCUMENT_ROOT']}/{$rootFolder}/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email to the specified recipient
 *
 * @param string $to email address of the recipient
 * @param string $subject email subject
 * @param string $body email body (can be HTML)
 * @return boolean
 */
function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = constant('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = constant('SMTP_USER');
        $mail->Password = constant('SMTP_PASS');
        $mail->SMTPSecure = constant('SMTP_SECURE');
        $mail->Port = constant('SMTP_PORT');

        $mail->setFrom(constant('MAIL_FROM'), constant('MAIL_NAME'));
        $mail->addAddress($to);
        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo $e->errorMessage();
        return false;
    }
}

/**
 * Encrypts a string using AES-256-CBC
 *
 * @param string $data - data to be encrypted
 * @param string $key - encryption key, must be 32 characters long
 * @return string|boolean - encrypted string or false if key is not 32 characters long
 */
function encrypt($data, $key) {
    if(strlen($key) !== 32) {
        return false;
    }

    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    $result = base64_encode($iv . $encrypted);

    return $result;
}

/**
 * Get email template for new account
 *
 * @param string $email The email address of the recipient
 * @param string $name The name of the recipient
 * @param string $password The generated password
 * @param string $title The title of the email
 * @param string $siteUrl The URL of the site
 * @param string $message The message to be included in the email
 * @param int $year The current year
 * @return string The email template
 */
function getNewAccountMailTemplate($email, $name, $password, $title, $siteUrl, $message, $year) {
    global $rootFolder;

    $template = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/{$rootFolder}/utils/templates/new-account.mail.html");
    $template = str_replace('{{ password }}', $password, $template);
    $template = str_replace('{{ siteUrl }}', $siteUrl, $template);
    $template = str_replace('{{ name }}', $name, $template);
    $template = str_replace('{{ message }}', $message, $template);
    $template = str_replace('{{ title }}', $title, $template);
    $template = str_replace('{{ email }}', $email, $template);
    $template = str_replace('{{ year }}', $year, $template);

    return $template;
}

/**
 * Get email template for reset password
 *
 * @param string $email The email address of the recipient
 * @param string $name The name of the recipient
 * @param string $siteUrl The URL of the site
 * @param int $year The current year
 * @return string The email template
 */
function getResetPasswordMailTemplate($email, $name, $siteUrl, $year) {
    global $rootFolder;

    $template = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/{$rootFolder}/utils/templates/reset-password.mail.html");
    $template = str_replace('{{ siteUrl }}', $siteUrl, $template);
    $template = str_replace('{{ name }}', $name, $template);
    $template = str_replace('{{ email }}', $email, $template);
    $template = str_replace('{{ year }}', $year, $template);

    return $template;
}



/**
 * --------------------------------[ TESTING ]--------------------------------
 * Everything below this line is for testing purposes only
 */

// Auto generate password using uuid to prevent collision and with at least 8 characters    
// $password = substr(md5(uniqid()), 0, 8);

// randomly insert at least 1-3 special character to the password
// $specialChars = ['!', '@', '#', '$', '&', '_', '?'];
// $specialCharCount = rand(1, 3);
// for($i = 0; $i < $specialCharCount; $i++) {
//     $password = substr_replace($password, $specialChars[rand(0, count($specialChars) - 1)], rand(0, strlen($password) - 1), 0);
// }

// $template = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/{$rootFolder}/admin/utils/templates/new-account.mail.html");
// $template = str_replace('{{ password }}', $password, $template);
// $template = str_replace('{{ siteUrl }}', "http://localhost/csvu-grading-sys", $template);
// $template = str_replace('{{ name }}', "John Roy Lapida", $template);
// $template = str_replace('{{ message }}', "We've sent you this email to notify you that we have created your account and you may login using this email address and this generated password. Under no circumstances are you to share this password to anyone. You may change your password once you've logged in.", $template);
// $template = str_replace('{{ title }}', "New Account", $template);
// $template = str_replace('{{ email }}', "johnroy062102calimlim@gmail.com", $template);
// $template = str_replace('{{ year }}', date('Y'), $template);

// echo $template;

// $result = sendMail('johnroy062102calimlim@gmail.com', 'Random Password Again, Again', $template);
// echo $result ? 'Email sent' : 'Email not sent';
?>