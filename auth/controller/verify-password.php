<?php
require_once("./components/header.php");
?>
<main class="w-screen h-screen border border-black p-4 overflow-hidden">
<?php require_once("./auth-header.php") ?>
    <div class="h-full flex justify-center items-center flex-col h-full">
        <div class="w-[500px] h-[500px]">
                <!-- Header -->
            <div class="flex flex-col justify-center items-center gap-4 mb-8">
                <h1 class="text-[32px] font-bold">Verification</h1>
                <span class="text-base text-center">"Ready for a fresh start? Enter your new password below to complete the change. Keep it secure, and you're good to go!"</span>
            </div>

            <!-- Form -->
            <form class="mt-[4rem]">
                <div class="flex flex-col gap-4 w-full">
                    <!-- Email -->
                    <label class="flex flex-col gap-2">
                        <span>New Password</span>
                        <input type="email" class="input input-bordered input-md" placeholder="Enter your Email" />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span>Confirm Password</span>
                        <input type="email" class="input input-bordered input-md" placeholder="Enter your Email" />
                    </label>
                    
                    <!-- Break -->
                    <span class="border border-black my-2"></span>
                    
                    <!-- Button -->
                    <button class="btn w-full">Submit</button>
                </div>
            </form>
        </div>
    </div>
</main>