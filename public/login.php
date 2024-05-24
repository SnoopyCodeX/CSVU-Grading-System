<?php
session_start();

// Global variables for error handling
$hasError = false;
$message = "";

require "../configuration/config.php";
require("../auth/controller/verify-login.php");
require("../auth/controller/auth.controller.php");

$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];

if (AuthController::isAuthenticated()) {
    header("Location: {$rootFolder}/" . AuthController::user()->roles);
    exit();
}

require_once("../components/header.php");
?>

<main class="w-full h-[100vh] grid grid-cols-1 lg:grid-cols-2">
    <div class="relative hidden lg:block">
        <div class="absolute top-0 p-4 gap-2 items-center z-20">
            <img src="../assets/images/logo.png" alt="" class="w-[100px]">
        </div>

        <div class="absolute top-0 w-full h-full bg-[#00000055] z-10"></div>
        <img src="../assets/images/background.png" alt="" class="h-full w-full object-cover">
    </div>

    <div class="flex justify-center items-center px-[64px]">
        <!-- Form -->
        <form class="flex flex-col gap-4 max-w-[600px]" action="" method="POST">
            <!-- Header -->
            <div class="flex flex-col justify-center gap-2 mb-4 w-full">
                <span class="italic">Welcome to</span>
                <h1 class="text-[38px] font-bold">Web-based Grading System</h1>
            </div>

            <?php if ($hasError) { ?>
                <div role="alert" class="w-[300px] absolute top-0 right-0 m-4 alert alert-error mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $message ?></span>
                </div>
            <?php } ?>

            <!-- Email -->
            <label class="flex flex-col gap-2">
                <span>Email</span>
                <input type="email" name="email" class="input input-bordered input-md" placeholder="Enter your Email" required />
            </label>

            <!-- Password -->
            <label class="flex flex-col gap-2" x-data="{show: true}">
                <span>Password</span>
                <div class="relative">
                    <input type="password" name="password" class="input input-bordered input-md w-full" placeholder="Enter your Password" required x-bind:type="show ? 'password' : 'text'">
                    <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
                        <i x-show="!show" class='bx bx-hide'></i>
                        <i x-show="show" class='bx bx-show'></i>
                    </button>
                </div>
            </label>

            <!-- Break -->
            <span class="border border-black my-2"></span>
            <p class="w-full flex flex-col md:flex-row md:justify-between">
                <span>Have you forgotten your password?</span>
                <a class="link text-sky-500" href="./forgot-password.php">Reset Password</a>
            </p>
            <button type="submit" name="login" class="btn bg-[#1b651b] text-base text-white ">Login</button>
        </form>
    </div>
</main>


<?php
require_once("../components/footer.php");
?>