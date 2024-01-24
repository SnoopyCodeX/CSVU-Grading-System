<?php
session_start();
// // kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// get activity id
$id = $dbCon->real_escape_string($_GET['id']) ? $dbCon->real_escape_string($_GET['id']) : header("Location: ../manage-activity.php");

// Update Activity
if (isset($_POST['update-activity'])) {
    $activity_name = $dbCon->real_escape_string($_POST['activity_name']);
    $subject = $dbCon->real_escape_string($_POST['subject']);
    $school_year = $dbCon->real_escape_string($_POST['school_year']);
    $term = $dbCon->real_escape_string($_POST['term']);
    $year_level = $dbCon->real_escape_string($_POST['year_level']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $section = $dbCon->real_escape_string($_POST['section']);
    $passing_rate = $dbCon->real_escape_string($_POST['passing_rate']);
    $max_score = $dbCon->real_escape_string($_POST['max_score']);

    $query = $dbCon->query("UPDATE ap_activities SET 
        name = '$activity_name', 
        subject = '$subject', 
        school_year = '$school_year', 
        term = '$term', 
        year_level = '$year_level', 
        course = '$course', 
        section = '$section',
        passing_rate = '$passing_rate', 
        max_score = '$max_score'
        WHERE id = '$id'
    ");

    if ($query) {
        $hasSuccess = true;
        $message = "Activity updated successfully!";
    } else {
        $hasError = true;
        $message = "Something went wrong. Please try again!";
    }
}

// get all activities
$query = $dbCon->query("SELECT 
    ap_activities.*,
    ap_subjects.id AS subject_id,
    ap_subjects.name AS subject_name,
    ap_courses.course AS course_name
    FROM ap_activities 
    INNER JOIN ap_subjects ON ap_activities.subject = ap_subjects.id
    INNER JOIN ap_courses ON ap_activities.course = ap_courses.id
    INNER JOIN ap_school_year ON ap_activities.school_year = ap_school_year.id
    WHERE instructor = '" . AuthController::user()->id . "' AND ap_activities.id = '$id'");
$activity = $query->fetch_assoc();

// Fetch all subjects
$subjectQuery = $dbCon->query("SELECT * FROM ap_subjects");

// Fetch school years
$schoolYearQuery = $dbCon->query("SELECT * FROM  ap_school_year");

// Fetch all courses
$courseQuery = $dbCon->query("SELECT * FROM  ap_courses");

// Fetch all sections that the instructor is assigned to
$sectionQuery = $dbCon->query("SELECT * FROM  ap_sections WHERE instructor = '" . AuthController::user()->id . "'");
?>

<main class="w-screen h-screen overflow-x-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-full">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full">
            <div class="flex justify-center items-center flex-col p-8">
                <h2 class="text-[38px] font-bold mb-4">Update Activity</h2>
                <form class="flex flex-col gap-[24px]  px-[32px]  w-[1000px] mb-auto flex" method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $id ?>">

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
                        <input class="input input-bordered" name="activity_name" value="<?= $activity['name'] ?>" required />
                    </label>

                    <!-- Main Grid -->
                    <div class="grid grid-cols-2 gap-4">


                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject</span>
                            <select class="select select-bordered" name="subject" required>
                                <!--Display all the subjects here-->
                                <option value="" selected disabled>Select Subject</option>

                                <?php while ($row = $subjectQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>" <?php if ($activity['subject_id'] == $row['id']) { ?> selected <?php } ?>><?= $row['name'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Year</span>
                            <select class="select select-bordered" name="school_year" required>
                                <!--Display all the School Year here-->
                                <option value="" selected disabled>Select School Year</option>

                                <?php while ($row = $schoolYearQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>" <?php if ($activity['school_year'] == $row['id']) { ?> selected <?php } ?>><?= $row['school_year'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Term</span>
                            <select class="select select-bordered" name="term" required>
                                <!--Display all the Semister here-->
                                <option value="" selected disabled>Select Semester</option>
                                <option value="1st Sem" <?php if (strtolower($activity['term']) == '1st sem') { ?> selected <?php } ?>>1st Sem</option>
                                <option value="2nd Sem" <?php if (strtolower($activity['term']) == '2nd sem') { ?> selected <?php } ?>>2nd Sem</option>
                                <option value="3rd Sem" <?php if (strtolower($activity['term']) == '3rd sem') { ?> selected <?php } ?>>3rd Sem</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" name="year_level" required>
                                <!--Display all the Year here-->
                                <option value="" selected disabled>Select Year Level</option>
                                <option value="1st Year" <?php if (strtolower($activity['year_level']) == '1st year') { ?> selected <?php } ?>>1st Year</option>
                                <option value="2nd Year" <?php if (strtolower($activity['year_level']) == '2nd year') { ?> selected <?php } ?>>2nd Year</option>
                                <option value="3rd Year" <?php if (strtolower($activity['year_level']) == '3rd year') { ?> selected <?php } ?>>3rd Year</option>
                                <option value="4th Year" <?php if (strtolower($activity['year_level']) == '4th year') { ?> selected <?php } ?>>4th Year</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Course</span>
                            <select class="select select-bordered" name="course" required>
                                <!--Display all the Course here-->
                                <option value="">Select Course</option>
                                <?php while ($row = $courseQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>" <?php if ($activity['course'] == $row['id']) { ?> selected <?php } ?>><?= $row['course'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col col-span gap-2">
                            <span class="font-bold text-[18px]">Section</span>
                            <select class="select select-bordered" name="section" required>
                                <!--Display all the Course here-->
                                <option value="" selected disabled>Select Section</option>

                                <?php while ($row = $sectionQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>" <?php if ($activity['section'] == $row['id']) { ?> selected <?php } ?>><?= $row['name'] ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Passing Rate</span>
                            <select class="select select-bordered" name="passing_rate" required>
                                <!--Display all the Course here-->
                                <option value="">Select Passing Rate</option>
                                <option value="0.25" <?php if ($activity['passing_rate'] == 0.25) { ?> selected <?php } ?>>25%</option>
                                <option value="0.50" <?php if ($activity['passing_rate'] == 0.50) { ?> selected <?php } ?>>50%</option>
                                <option value="0.75" <?php if ($activity['passing_rate'] == 0.75) { ?> selected <?php } ?>>75%</option>
                                <option value="1.00" <?php if ($activity['passing_rate'] == 1.00) { ?> selected <?php } ?>>100%</option>
                            </select>
                        </label>


                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Max Score</span>
                            <input type="number" class="input input-bordered" name="max_score" value="<?= $activity['max_score'] ?>" onchange="(e) => parseInt(e.target.value) < 0 ? e.target.value='1' : ''" value="0" pattern="[0-9]+" required />
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-activity.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success text-base" name="update-activity">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>