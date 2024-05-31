<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// fetch course
$courseId = $dbCon->real_escape_string($_GET['id']);
$courseQuery = $dbCon->query("SELECT 
    courses.*, 
    CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as adviserFullname
    FROM courses 
    LEFT JOIN userdetails ON courses.adviser = userdetails.id 
    WHERE courses.id = '$courseId'
");

// if no course was found, return back to manage-sections.php
if ($courseQuery->num_rows == 0) {
    header('location: ../manage-sections.php');
    exit;
}

$course = $courseQuery->fetch_assoc();

// Section counts
$firstYearSectionCountQuery = $dbCon->query("SELECT COUNT(*) as count FROM sections WHERE year_level='1st Year' AND course='$courseId'");
$firstYearSectionCount = $firstYearSectionCountQuery->fetch_assoc()['count'];

$secondYearSectionCountQuery = $dbCon->query("SELECT COUNT(*) as count FROM sections WHERE year_level='2nd Year' AND course='$courseId'");
$secondYearSectionCount = $secondYearSectionCountQuery->fetch_assoc()['count'];

$thirdYearSectionCountQuery = $dbCon->query("SELECT COUNT(*) as count FROM sections WHERE year_level='3rd Year' AND course='$courseId'");
$thirdYearSectionCount = $thirdYearSectionCountQuery->fetch_assoc()['count'];

$fourthYearSectionCountQuery = $dbCon->query("SELECT COUNT(*) as count FROM sections WHERE year_level='4th Year' AND course='$courseId'");
$fourthYearSectionCount = $fourthYearSectionCountQuery->fetch_assoc()['count'];

$fifthYearSectionCountQuery = $dbCon->query("SELECT COUNT(*) as count FROM sections WHERE year_level='5th Year' AND course='$courseId'");
$fifthYearSectionCount = $fifthYearSectionCountQuery->fetch_assoc()['count'];
?>


<main class="overflow-x-auto flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-screen">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

            <!-- Table Header -->
            <div class="flex justify-between">
                <!-- Table Header -->
                <div class="flex flex-col justify-between">
                    <div class="flex justify-between items-center">
                        <h1 class="text-[32px] font-bold"><?= $course['course_code'] ?> > Year Levels</h1>
                    </div>

                    <div class="flex gap-2 items-center">
                        <h1 class="text-[18px]">Course Adviser: </h1>
                        <span><?= $course['adviserFullname'] ?? 'Not Assigned' ?></span>
                    </div>
                </div>

                <div class="flex gap-4 px-4">
                    <a class="btn bg-[#276bae] text-white" href="../manage-sections.php"><i class="bx bxs-chevron-left"></i> Go Back</a>
                </div>
            </div>

            <div class='overflow-auto sm:pr-[48px] sm:grid sm:grid-cols-2 gap-4 md:grid-cols-2 lg:grid-cols-3 p-4 mt-8'>
                <a href="./course_section.php?id=<?= $course['id'] ?>&yearLevel=1st Year" class="">
                    <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4 hover:bg-[#27ae60] hover:text-white'>
                        <h1 class='text-[32px] font-semibold text-center cursor-pointer'>1st Year</h1>
                        <span class="text-[18px]"><?= $firstYearSectionCount ?> section<?= $firstYearSectionCount > 1 || $firstYearSectionCount == 0 ? 's' : '' ?></span>
                    </div>
                </a>

                <a href="./course_section.php?id=<?= $course['id'] ?>&yearLevel=2nd Year" class="">
                    <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4 hover:bg-[#27ae60] hover:text-white'>
                        <h1 class='text-[32px] font-semibold text-center cursor-pointer'>2nd Year</h1>
                        <span class="text-[18px]"><?= $secondYearSectionCount ?> section<?= $secondYearSectionCount > 1 || $secondYearSectionCount == 0 ? 's' : '' ?></span>
                    </div>
                </a>

                <a href="./course_section.php?id=<?= $course['id'] ?>&yearLevel=3rd Year" class="">
                    <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4 hover:bg-[#27ae60] hover:text-white'>
                        <h1 class='text-[32px] font-semibold text-center cursor-pointer'>3rd Year</h1>
                        <span class="text-[18px]"><?= $thirdYearSectionCount ?> section<?= $thirdYearSectionCount > 1 || $thirdYearSectionCount == 0 ? 's' : '' ?></span>
                    </div>
                </a>

                <a href="./course_section.php?id=<?= $course['id'] ?>&yearLevel=4th Year" class="">
                    <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4 hover:bg-[#27ae60] hover:text-white'>
                        <h1 class='text-[32px] font-semibold text-center cursor-pointer'>4th Year</h1>
                        <span class="text-[18px]"><?= $fourthYearSectionCount ?> section<?= $fourthYearSectionCount > 1 || $fourthYearSectionCount == 0 ? 's' : '' ?></span>
                    </div>
                </a>

                <a href="./course_section.php?id=<?= $course['id'] ?>&yearLevel=5th Year" class="">
                    <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4 hover:bg-[#27ae60] hover:text-white'>
                        <h1 class='text-[32px] font-semibold text-center cursor-pointer'>5th Year</h1>
                        <span class="text-[18px]"><?= $fifthYearSectionCount ?> section<?= $fifthYearSectionCount > 1 || $fifthYearSectionCount == 0 ? 's' : '' ?></span>
                    </div>
                </a>
            </div>
        </div>
    </section>
</main>