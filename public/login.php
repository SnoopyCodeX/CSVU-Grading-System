<?php
session_start();

// Global variables for error handling
$hasError = false;
$message = "";

require "../configuration/config.php";
require("../auth/controller/verify-login.php");
require("../auth/controller/auth.controller.php");
require_once("../components/header.php");

$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];

if (AuthController::isAuthenticated()) {
    header("Location: {$rootFolder}/" . AuthController::user()->roles);
    exit();
}

?>
<main class="h-screen ">


    <div class="w-full h-full grid grid-cols-2 gap-4">
        <div class="px-[120px] flex justify-center  gap-4 flex-col">
            <!-- Header -->
            <div class="flex flex-col justify-center items-center gap-4 mb-4 ">
                <h1 class="text-[48px] font-bold">Login</h1>
                <span class="text-base text-center">Welcome to the LogIn portal! Navigate your academic journey with the web-based grading system of Cavite State University - General Triasl City, Campus, Lets get started</span>
            </div>

            <?php if ($hasError) { ?>
                <div role="alert" class="w-[300px] absolute top-0 right-0 m-4 alert alert-error mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $message ?></span>
                </div>
            <?php } ?>

            <!-- Form -->
            <form class="" action="" method="POST">
                <div class="flex flex-col gap-4 w-full">
                    <!-- Email -->
                    <label class="flex flex-col gap-2">
                        <span>Email</span>
                        <input type="email" name="email" class="input input-bordered input-md" placeholder="Enter your Email" required />
                    </label>

                    <!-- Password -->
                    <label class="flex flex-col gap-2">
                        <span>Password</span>
                        <input type="password" name="password" class="input input-bordered input-md" placeholder="Enter your password" required />
                    </label>

                    <!-- Break -->
                    <span class="border border-black my-2"></span>
                    <!-- <p><a href="./forgot-password.php">Forgot Password</a></p> -->
                    <!-- Button -->
                    <button type="submit" name="login" class="btn bg-[#1b651b] text-base text-white ">Login</button>
                </div>
            </form>
        </div>

        <div  style='background: linear-gradient(0deg, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url(../assets/images/background.jpg); background-size: cover;'></div>
    </div>

</main>


<?php
require_once("../components/footer.php");
?>