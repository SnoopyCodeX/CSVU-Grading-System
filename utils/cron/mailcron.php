<?php
/**
 * This php file is responsible for sending
 * emails to all imported students.
 */

require_once('../../configuration/config.php');
require_once('../mailer.php');

// First, get all emails from the database
$userEmailsQuery = $dbCon->query("SELECT * FROM pending_account_mails");
$userEmails = $userEmailsQuery->fetch_all(MYSQLI_ASSOC);

// Loop through each pending account emails
foreach ($userEmails as $userEmail) {
    $email = $userEmail['email'];
    $password = $userEmail['raw_password'];

    $checkIfEmailExists = $dbCon->query("SELECT * FROM userdetails WHERE email = '$email'");

    if ($checkIfEmailExists->num_rows > 0) {
        $userData = $checkIfEmailExists->fetch_assoc();

        $firstName = $userData['firstName'] ?? "Test";
        $middleName = $userData['middleName'] ?? "";
        $lastName = $userData['lastName'] ?? "User";
    
        // get the email template
        $template = getNewAccountMailTemplate(
            $email, 
            "$firstName $middleName $lastName", 
            $password, 
            "Welcome to CvSU Grading System", 
            constant('APP_URL'), 
            "We've sent you this email to notify you that we have created your account and you may login using this email address and this generated password. Under no circumstances are you to share this password to anyone. You may change your password once you've logged in.", 
            date('Y')
        );
    
        $sent = sendMail($email, 'CvSU Grading System', $template);
    
        // If the email was successfully sent, we will delete it from the table
        if ($sent) {
            $dbCon->query("DELETE FROM pending_account_mails WHERE id='$userEmail[id]'");

            echo "Sent mail to: $email" . PHP_EOL;
        }
    } else {
        // Delete from the table if the email does not exist in the userdetails table
        $dbCon->query("DELETE FROM pending_account_mails WHERE email = '$email' AND id = '$userEmail[id]'");
    }
}
?>