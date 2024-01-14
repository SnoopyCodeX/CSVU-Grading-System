<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
    exit();
}
    
// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// Prefetch all students query
$query = "SELECT id, firstName, middleName, lastName, email, gender, contact, sid FROM ap_userdetails WHERE roles='student'";
?>

<main class="w-screen h-screen overflow-hidden flex" >
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center h-[70%]">
        <div class="flex justify-center items-center flex-col gap-4">
        <h2 class="text-[38px] font-bold mb-8">Create Subject</h2>
            <form class="flex flex-col gap-4  px-[32px]  w-[1000px] mb-auto">
                
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Course</span>
                    <select class="select select-bordered">
                        <option value="">Select Course</option>
                    </select>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Year level</span>
                    <select class="select select-bordered">
                        <option value="">Select Year level</option>
                    </select>
                </label>
            
                <!-- Name -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Subject Name</span>
                        <input class="input input-bordered" placeholder="Enter Subject Name" />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Units</span>
                        <input class="input input-bordered"  placeholder="Enter Subject Units" />
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Credits Units</span>
                        <input class="input input-bordered"  placeholder="Enter Subject Credits" />
                    </label>
                    <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Term</span>
                    <select class="select select-bordered">
                        <option value="">Select Course</option>
                        <option value="">1st Sem</option>
                        <option value="">2nd Sem</option>
                        <option value="">3rd Sem</option>
                    </select>
                </label>
                </div>


                 <!-- Actions -->
                <div class="grid grid-cols-2 gap-4">
                    <a href="../manage-subjects.php" class="btn text-base">Cancel</a>
                    <button class="btn text-base">Create</button>
                </div>
            </form>
        </div>
        </div>

</main>