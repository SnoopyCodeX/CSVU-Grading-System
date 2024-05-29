<?php
session_start();

require "../configuration/config.php";
require_once ("../auth/controller/auth.controller.php");
require_once ("../utils/mailer.php");

$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));

// If user is already authenticated, redirect to their respective dashboard
if (AuthController::isAuthenticated()) {
    header("Location: " . str_repeat("../", count($FirstDir) - 1) . AuthController::user()->roles);
    exit();
}

// Error handlers
$hasError = false;
$hasSuccess = false;
$message = "";

if (isset($_POST['verify-email'])) {
    $email = $dbCon->real_escape_string($_POST['email']);

    if (empty($email)) {
        $hasError = true;
        $message = "Email is required!";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hasError = true;
        $message = "Invalid email address!";
    } else if (!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $message = "Invalid email address, email address must end with <strong>@cvsu.edu.ph</strong>!";
    } else {
        // Check if email exists
        $user = $dbCon->query("SELECT * FROM userdetails WHERE email = '$email'");

        if ($user->num_rows > 0) {
            // Check if user has an active password reset request (not expired or used)
            $hasPendingReset = $dbCon->query("SELECT * FROM password_reset_tokens WHERE email = '$email' AND status='active'");

            if ($hasPendingReset->num_rows > 0) {
                $hasError = true;
                $message = "We have already sent a password reset link to your email. Please check your inbox!";
            } else {
                $user = $user->fetch_assoc();

                // Generate a random token that has a low chance of collision
                $token = bin2hex(random_bytes(32));

                // Template for the email
                $template = getResetPasswordMailTemplate(
                    $email,
                    "{$user['firstName']} {$user['middleName']} {$user['lastName']}",
                    constant('APP_URL') . "/server/public/reset-password.php?token=$token",
                    date('Y')
                );

                // Send email
                $mail = sendMail($email, "Reset Password Confirmation", $template);

                // If email is sent successfully
                if ($mail) {
                    // Insert token to the database
                    $dbCon->query("INSERT INTO password_reset_tokens (email, token, status) VALUES ('$email', '$token', 'active')");

                    $hasSuccess = true;
                    $message = "We've sent a password reset link to your email. Please check your inbox.";
                } else {
                    $hasError = true;
                    $message = "Failed to send email!";
                }
            }
        } else {
            $hasError = true;
            $message = "Email address does not exist in our database";
        }
    }
}

require_once ("../components/header.php");
?>

<main class="h-screen">
    <div class="h-full grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="relative hidden lg:block">
            <div class="absolute top-0 p-4 gap-2 items-center z-20">
                <img src="../assets/images/logo.png" alt="" class="w-[100px]">
            </div>

            <div class="absolute top-0 w-full h-full bg-[#00000055] z-10"></div>
            <img src="../assets/images/background.png" alt="" class="h-full w-full object-cover">
        </div>

        <div class="px-[30px] lg:px-[90px] flex justify-center items-center gap-4 flex-col">
            <div class="flex flex-col gap-4 w-full max-w-[600px]">
                <!-- Header -->
                <div class="flex flex-col justify-center items-center gap-4 mb-4">
                    <h1 class="text-[32px] font-bold">Reset Password</h1>
                </div>

                <?php if ($hasError) { ?>
                <div role="alert" class="alert alert-error mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $message ?></span>
                </div>
                <?php } ?>

                <?php if ($hasSuccess) { ?>
                <div role="alert" class="alert alert-success mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $message ?></span>
                </div>
                <?php } ?>

                <!-- Form -->
                <form class="mt-[2rem]" action="" method="post">
                    <div class="flex flex-col gap-4 w-full">
                        <!-- Email -->
                        <label class="flex flex-col gap-2">
                            <span>Email</span>
                            <input type="email" class="input input-bordered input-md"
                                placeholder="Enter your email address" name="email" required />
                        </label>

                        <!-- Break -->
                        <span class="border border-black my-2"></span>

                        <!-- Verify button -->
                        <button class="btn bg-[#1b651b] text-base text-white w-full" name="verify-email">Verify
                            Email</button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</main>