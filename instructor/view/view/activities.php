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

// get all activities
$query = $dbCon->query("SELECT 
    ap_activities.*,
    ap_subjects.id AS subject_id,
    ap_subjects.name AS subject_name,
    ap_courses.course AS course_name,
    ap_sections.name AS section_name
    FROM ap_activities 
    INNER JOIN ap_subjects ON ap_activities.subject = ap_subjects.id
    INNER JOIN ap_courses ON ap_activities.course = ap_courses.id
    INNER JOIN ap_school_year ON ap_activities.school_year = ap_school_year.id
    INNER JOIN ap_sections ON ap_activities.section = ap_sections.id
    WHERE ap_activities.instructor = '" . AuthController::user()->id . "' AND ap_activities.id = '$id'");
$activity = $query->fetch_assoc();

// save student grade
if (isset($_GET['update_student']) && isset($_POST['save-grade'])) {
    $studentID = $dbCon->real_escape_string($_POST["student_id_{$_GET['update_student']}"]);
    $grade = $dbCon->real_escape_string($_POST["grade_{$_GET['update_student']}"]);

    // check if student is enrolled in the activity
    $query = $dbCon->query("SELECT * FROM ap_section_students WHERE student_id = '$studentID' AND section_id='{$activity['section']}'");
    if ($query->num_rows == 0) {
        $hasError = true;
        $message = "Student is not enrolled in this activity";
    } else {
        // check if student grade already exists
        $gradecheckquery1 = $dbCon->query("SELECT * FROM ap_student_grades WHERE student_id = '$studentID' AND activity_id = '{$activity['id']}'");

        if ($gradecheckquery1->num_rows == 0) {

            // insert new student grade
            $insertnewgrade = $dbCon->query("INSERT INTO ap_student_grades (
                student_id, 
                activity_id, 
                instructor_id, 
                section_id,
                grade,
                term,
                year_level
            ) VALUES (
                '$studentID', 
                '{$activity['id']}', 
                '" . AuthController::user()->id . "', 
                '{$activity['section']}',
                '$grade',
                '{$activity['term']}',
                '{$activity['year_level']}'
            )");

            if ($insertnewgrade) {
                $hasSuccess = true;
                $message = "Student grade has been saved";
            } else {
                $hasError = true;
                $message = "Failed to save student grade";
            }
        } else {
            // update student grade
            $updatenewgrade = $dbCon->query("UPDATE ap_student_grades SET grade = '$grade' WHERE student_id = '$studentID' AND activity_id = '{$activity['id']}'");

            if ($updatenewgrade) {
                $hasSuccess = true;
                $message = "Student grade has been updated";
            } else {
                $hasError = true;
                $message = "Failed to update student grade";
            }
        }
    }
}

// reset student grade
if (isset($_GET['update_student']) && isset($_POST['reset-grade'])) {
    $studentID = $dbCon->real_escape_string($_POST["student_id_{$_GET['update_student']}"]);
    $grade = $dbCon->real_escape_string($_POST["grade_{$_GET['update_student']}"]);

    // check if student is enrolled in the activity
    $query = $dbCon->query("SELECT * FROM ap_section_students WHERE student_id = '$studentID' AND section_id='{$activity['section']}'");
    if ($query->num_rows == 0) {
        $hasError = true;
        $message = "Student is not enrolled in this activity";
    } else {
        // check if student has a grade
        $checkGrade = $dbCon->query("SELECT * FROM ap_student_grades WHERE student_id = '$studentID' AND activity_id = '{$activity['id']}' ");

        if ($checkGrade->num_rows == 0) {
            $hasError = true;
            $message = "Student has no grade to reset";
        } else {
            $updateStudentGrade = $dbCon->query("UPDATE ap_student_grades SET grade = '0' WHERE student_id = '$studentID' AND activity_id = '{$activity['id']}'");

            if ($updateStudentGrade) {
                $hasSuccess = true;
                $message = "Student's grade has been reset successfully";
            } else {
                $hasError = true;
                $message = "Failed to reset student's grade. {$dbCon->error}";
            }
        }
    }
}

// Fetch all subjects
$subjectQuery = $dbCon->query("SELECT * FROM ap_subjects");

// Fetch school years
$schoolYearQuery = $dbCon->query("SELECT * FROM  ap_school_year");

// Fetch all courses
$courseQuery = $dbCon->query("SELECT * FROM  ap_courses");

// get all students handled by the instructor
$studentsQuery = $dbCon->query(
    "SELECT 
    ap_sections.*,
    ap_userdetails.id as studentID,
    ap_userdetails.firstName as studentFN,
    ap_userdetails.middleName as studentMN,
    ap_userdetails.lastName as studentLN
    FROM ap_sections 
    INNER JOIN ap_section_students ON ap_section_students.section_id = ap_sections.id 
    INNER JOIN ap_userdetails ON ap_section_students.student_id = ap_userdetails.id
    WHERE ap_sections.instructor = '" . AuthController::user()->id . "' AND ap_sections.year_level = '{$activity['year_level']}' AND ap_sections.id='{$activity['section']}'"
);

// get all students grades
$gradesQuery = $dbCon->query("SELECT * FROM ap_student_grades WHERE activity_id = '$id'");
$gradesQueryResult = $gradesQuery->fetch_all(MYSQLI_ASSOC);

// store all student grades in an array with the student's id as the key
$grades = [];
foreach ($gradesQueryResult as $grade) {
    $grades[$grade['student_id']] = $grade['grade'];
}
?>

<main class="w-screen h-screen overflow-x-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-full">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full">
            <div class="flex justify-center items-center flex-col p-8">
                <h2 class="text-[38px] font-bold mb-4">View Activity</h2>
                <div class="flex flex-col gap-[24px]  px-[32px]  w-[1000px] mb-auto flex">

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
                        <input class="input input-bordered" name="activity_name" value="<?= $activity['name'] ?>" disabled />
                    </label>

                    <!-- Main Grid -->
                    <div class="grid grid-cols-2 gap-4">


                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject</span>
                            <select class="select select-bordered" name="subject" disabled>
                                <!--Display all the subjects here-->
                                <option value="" selected disabled>Select Subject</option>

                                <?php while ($row = $subjectQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>" <?php if ($activity['subject_id'] == $row['id']) { ?> selected <?php } ?>><?= $row['name'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Year</span>
                            <select class="select select-bordered" name="school_year" disabled>
                                <!--Display all the School Year here-->
                                <option value="" selected disabled>Select School Year</option>

                                <?php while ($row = $schoolYearQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>" <?php if ($activity['school_year'] == $row['id']) { ?> selected <?php } ?>><?= $row['school_year'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Term</span>
                            <select class="select select-bordered" name="term" disabled>
                                <!--Display all the Semister here-->
                                <option value="" selected disabled>Select Semester</option>
                                <option value="1st Sem" <?php if (strtolower($activity['term']) == '1st sem') { ?> selected <?php } ?>>1st Sem</option>
                                <option value="2nd Sem" <?php if (strtolower($activity['term']) == '2nd sem') { ?> selected <?php } ?>>2nd Sem</option>
                                <option value="3rd Sem" <?php if (strtolower($activity['term']) == '3rd sem') { ?> selected <?php } ?>>3rd Sem</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" name="year_level" disabled>
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
                            <select class="select select-bordered" name="course" disabled>
                                <!--Display all the Course here-->
                                <option value="">Select Course</option>
                                <?php while ($row = $courseQuery->fetch_assoc()) { ?>
                                    <option value="<?= $row['id'] ?>" <?php if ($activity['course'] == $row['id']) { ?> selected <?php } ?>><?= $row['course'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col col-span gap-2">
                            <span class="font-bold text-[18px]">Section</span>
                            <select class="select select-bordered" name="section" disabled>
                                <!--Display all the Course here-->
                                <option value="" selected disabled><?= $activity['section_name'] ?></option>
                            </select>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Passing Rate</span>
                            <select class="select select-bordered" name="passing_rate" disabled>
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
                            <input type="number" class="input input-bordered" name="max_score" value="<?= $activity['max_score'] ?>" onchange="(e) => parseInt(e.target.value) < 0 ? e.target.value='1' : ''" value="0" pattern="[0-9]+" disabled />
                        </label>
                    </div>

                    <div class="divider">Student Grades</div>

                    <label class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[18px]">Student Grades</span>
                        </div>
                        <div class="border border-black rounded-[5px] w-full h-[400px] grid grid-cols-2 gap-4 p-4 overflow-y-scroll " method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

                            <?php while ($row = $studentsQuery->fetch_assoc()) { ?>
                                <form class="h-[72px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]" method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $id ?>&update_student=<?= $row['studentID'] ?>">
                                    <input type="hidden" name="student_id_<?= $row['studentID'] ?>" value="<?= $row['studentID'] ?>" />
                                    <input type="text" class="input input-sm w-[48px] h-[38px] input-bordered text-center" name="grade_<?= $row['studentID'] ?>" value="<?= $grades[$row['studentID']] ?? 0 ?>" />
                                    <span><?= $row['studentFN'] ?> <?= $row['studentMN'] ?> <?= $row['studentLN'] ?></span>

                                    <div class="flex gap-4">
                                        <button class="btn btn-success btn-sm" name="save-grade">Save</button>
                                        <button class="btn btn-error btn-sm" name="reset-grade">Reset</button>
                                    </div>
                                </form>
                            <?php } ?>

                        </div>
                    </label>

                    <!-- Actions -->
                    <div class="">
                        <a class="btn btn-error text-base w-full" href="../manage-activity.php">Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>