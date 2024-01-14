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
        <h2 class="text-[38px] font-bold mb-8">Create Course</h2>
            <form class="flex flex-col gap-4  px-[32px]  w-[1000px] mb-auto">
                
                <!-- Name -->
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Course</span>
                    <input type="text" class="border border-gray-400 input input-bordered" placeholder="Course Name">
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Course Code</span>
                    <input type="text" class="border border-gray-400 input input-bordered" placeholder="Course Name">
                </label>

                 <!-- Actions -->
                <div class="grid grid-cols-2 gap-4">
                    <button class="btn text-base">Cancel</button>
                    <button class="btn text-base">Create</button>
                </div>
            </form>
        </div>
        </div>

</main>