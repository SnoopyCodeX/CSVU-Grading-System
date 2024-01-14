<?php
require_once("./components/header.php");
?>
<main class="w-screen h-screen border border-black p-4 overflow-hidden">
    <?php
        require_once("./components/auth-header.php")
    ?>
    <div class="h-full flex justify-center items-center flex-col h-full">
        <div class="w-[500px] h-[500px]">
                <!-- Header -->
            <div class="flex flex-col justify-center items-center gap-4 mb-8">
                <h1 class="text-[32px] font-bold">Forgot Password</h1>
                <span class="text-base text-center">Oops, locked out? No worries! If you've forgotten your password, Enter your email' below. We'll guide you through a quick verification process to ensure account security.</span>
            </div>

            <!-- Form -->
            <form class="mt-[4rem]">
                <div class="flex flex-col gap-4 w-full">
                    <!-- Email -->
                    <label class="flex flex-col gap-2">
                        <span>Email</span>
                        <input type="email" class="input input-bordered input-md" placeholder="Enter your Email" />
                    </label>
                    
                    <!-- Break -->
                    <span class="border border-black my-2"></span>
                    
                    <!-- Button -->
                    <button class="btn w-full">Verfiy</button>
                </div>
            </form>
        </div>
    </div>
</main>