<?php
session_start();

require "../configuration/config.php";
require_once("../auth/controller/auth.controller.php");
require_once("../utils/mailer.php");

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
$tokenUsedOrExpired = false;
$message = "";

// If token is not set, redirect to login page
if (!isset($_GET['token'])) {
    header("Location: ./login");
    exit();
}

// Get token from the URL
$token = $dbCon->real_escape_string($_GET['token']);

// Check if token exists
$tokenExists = $dbCon->query("SELECT * FROM password_reset_tokens WHERE token = '$token'");

if ($tokenExists->num_rows == 0) {
    header("Location: ./login");

    exit();
} else {
    $tokenData = $tokenExists->fetch_assoc();

    // Check if token is expired or used
    if (in_array($tokenData['status'], ['expired', 'used'])) {
        $tokenUsedOrExpired = true;
        $hasError = true;
        $message = "Password reset token has expired or already used. You may request a new one.";

        // Redirect to login page after 3 seconds
        header('Refresh: 3; URL=./login.php');
    } else {
        if (isset($_POST['reset-password'])) {
            $newPassword = $dbCon->real_escape_string($_POST['new-password']);
            $confirmPassword = $dbCon->real_escape_string($_POST['confirm-password']);

            if (empty($newPassword) || empty($confirmPassword)) {
                $hasError = true;
                $message = "Password is required!";
            } else if ($newPassword !== $confirmPassword) {
                $hasError = true;
                $message = "Passwords do not match!";
            } else {
                // Hash the password
                $hashedPassword = crypt($newPassword, '$6$Crypt$');

                // Update user's password
                $updatePassword = $dbCon->query("UPDATE userdetails SET password = '$hashedPassword' WHERE email = '{$tokenData['email']}'");

                if ($updatePassword) {
                    // Update token status
                    $updateToken = $dbCon->query("UPDATE password_reset_tokens SET status = 'used' WHERE token = '$token'");

                    if ($updateToken) {
                        $hasSuccess = true;
                        $message = "Password reset successful! You can now login with your new password.";

                        // Redirect to login page after 3 seconds
                        header('Refresh: 3; URL=./login.php');
                    } else {
                        $hasError = true;
                        $message = "An error occurred while updating your password. Please try again!";
                    }
                } else {
                    $hasError = true;
                    $message = "An error occurred while updating your password. Please try again!";
                }
            }
        }
    }
}

require_once("../components/header.php");
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
        
        <div class="px-[30px] lg:px-[90px] flex justify-center gap-4 flex-col">
            <div class="flex flex-col gap-4 w-full">
                <!-- Header -->
                <div class="flex flex-col justify-center items-center gap-4 mb-4">
                    <h1 class="text-[32px] font-bold">Reset Password</h1>
                    <span class="text-base text-center">Please enter your new password credentials below.</span>
                </div>

                <?php if ($hasError) { ?>
                    <div role="alert" class="alert alert-error mb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= $message ?></span>
                    </div>
                <?php } ?>

                <?php if ($hasSuccess) { ?>
                    <div role="alert" class="alert alert-success mb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= $message ?></span>
                    </div>
                <?php } ?>

                <!-- Form -->
                <form class="mt-[2rem]" action="" method="post">
                    <div class="flex flex-col gap-4 w-full">
                        <!-- New Password -->
                        <label class="flex flex-col gap-2" x-data="{show: true}">
                            <span class="font-semibold text-base">New Password</span>
                            <div class="relative">
                                <input class="input input-bordered w-full" name="new-password" placeholder="New password" x-bind:type="show ? 'password' : 'text'" required <?php if ($tokenUsedOrExpired) : ?> disabled <?php endif; ?> />
                                <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show" <?php if ($tokenUsedOrExpired) : ?> disabled <?php endif; ?>>
                                    <i x-show="!show" class='bx bx-hide'></i>
                                    <i x-show="show" class='bx bx-show'></i>
                                </button>
                            </div>
                        </label>

                        <!-- Confirm Password -->
                        <label class="flex flex-col gap-2" x-data="{show: true}">
                            <span class="font-semibold text-base">Confirm Password</span>
                            <div class="relative">
                                <input class="input input-bordered w-full" name="confirm-password" placeholder="Confirm password" x-bind:type="show ? 'password' : 'text'" required <?php if ($tokenUsedOrExpired) : ?> disabled <?php endif; ?> />
                                <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show" <?php if ($tokenUsedOrExpired) : ?> disabled <?php endif; ?>>
                                    <i x-show="!show" class='bx bx-hide'></i>
                                    <i x-show="show" class='bx bx-show'></i>
                                </button>
                            </div>
                        </label>

                        <!-- Break -->
                        <span class="border border-black my-2"></span>

                        <!-- Button -->
                        <button class="btn bg-[#1b651b] text-base text-white w-full" name="reset-password" <?php if ($tokenUsedOrExpired) : ?> disabled <?php endif; ?>>Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>