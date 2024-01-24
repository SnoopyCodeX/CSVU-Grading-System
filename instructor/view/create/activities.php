<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");
require_once("../../../configuration/config.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Create Activity
if (isset($_POST['create-activity'])) {
    $activity_name = $dbCon->real_escape_string($_POST['activity_name']);
    $subject = $dbCon->real_escape_string($_POST['subject']);
    $school_year = $dbCon->real_escape_string($_POST['school_year']);
    $term = $dbCon->real_escape_string($_POST['term']);
    $year_level = $dbCon->real_escape_string($_POST['year_level']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $section = $dbCon->real_escape_string($_POST['section']);
    $passing_rate = $dbCon->real_escape_string($_POST['passing_rate']);
    $max_score = $dbCon->real_escape_string($_POST['max_score']);

    $query = $dbCon->query("INSERT INTO ap_activities (
        name, 
        subject, 
        school_year, 
        term, 
        year_level, 
        course, 
        section,
        passing_rate, 
        max_score, 
        instructor
    ) VALUES (
        '$activity_name', 
        '$subject', 
        '$school_year', 
        '$term', 
        '$year_level', 
        '$course', 
        '$section',
        '$passing_rate', 
        '$max_score',
        '" . AuthController::user()->id . "'
    )");

    if ($query) {
        $hasSuccess = true;
        $message = "Activity created successfully!";
    } else {
        $hasError = true;
        $message = "Something went wrong. Please try again! {$dbCon->error}";
    }
}

// Fetch all subjects
$subjectQuery = $dbCon->query("SELECT * FROM  ap_subjects");

// Fetch school years
$schoolYearQuery = $dbCon->query("SELECT * FROM  ap_school_year");

// Fetch all courses
$courseQuery = $dbCon->query("SELECT * FROM  ap_courses");

// Fetch all sections that the instructor is assigned to
$sectionQuery = $dbCon->query("SELECT * FROM  ap_sections WHERE instructor = '" . AuthController::user()->id . "'");
?>

<main class="w-screen flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full ">
            <div class="flex justify-center items-center flex-col p-8 ">
                <h2 class="text-[38px] font-bold mb-4">Create Activity</h2>
                <form class="flex flex-col gap-[24px]  px-[32px]  w-[800px] mb-auto flex" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <?php if ($hasError) { ?>
                        <div role="alert" class="alert alert-error mb-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><?= $message ?></span>
                        </div>
                    <?php } ?>

                    <?php if ($hasSuccess) { ?>
                        <div role="alert" class="alert alert-success mb-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><?= $message ?></span>
                        </div>
                    <?php } ?>

                    <!-- Details -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Activity Name</span>
                        <input class="input input-bordered" name="activity_name" required />
                    </label>


                    <!-- Main Grid -->
                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject</span>
                            <select class="select select-bordered" name="subject" required>
                                <!--Display all the subjects here-->
                                <option value="" selected disabled>Select Subject</option>

                                <?php while ($row = $subjectQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Year</span>
                            <select class="select select-bordered" name="school_year" required>
                                <option value="" selected disabled>Select School Year</option>

                                <?php while ($row = $schoolYearQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['school_year'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Term</span>
                            <select class="select select-bordered" name="term" required>
                                <!--Display all the Semister here-->
                                <option value="" selected disabled>Select Semester</option>
                                <option value="1st Sem">1st Sem</option>
                                <option value="2nd Sem">2nd Sem</option>
                                <option value="3rd Sem">3rd Sem</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" name="year_level" required>
                                <!--Display all the Year here-->
                                <option value="" selected disabled>Select Year level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </label>

                        <label class="flex flex-col col-span gap-2">
                            <span class="font-bold text-[18px]">Course</span>
                            <select class="select select-bordered" name="course" required>
                                <!--Display all the Course here-->
                                <option value="" selected disabled>Select Course</option>

                                <?php while ($row = $courseQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['course'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col col-span gap-2">
                            <span class="font-bold text-[18px]">Section</span>
                            <select class="select select-bordered" name="section" required>
                                <!--Display all the Course here-->
                                <option value="" selected disabled>Select Section</option>

                                <?php while ($row = $sectionQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Passing Rate</span>
                            <select class="select select-bordered" name="passing_rate" required>
                                <!--Display all the Course here-->
                                <option value="" selected disabled>Select Passing Rate</option>
                                <option value="0.25">25%</option>
                                <option value="0.50">50%</option>
                                <option value="0.75">75%</option>
                                <option value="1.00">100%</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Max Score</span>
                            <input type="number" class="input input-bordered" name="max_score" min="0" onchange="(e) => parseInt(e.target.value) < 0 ? e.target.value='1' : ''" value="0" pattern="[0-9]+" required />
                        </label>

                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-activity.php" class="btn text-base btn-error">Cancel</a>
                        <button class="btn text-base btn-success" name="create-activity">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>