<?php
session_start();
// // kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (isset($_GET['fetch']) && isset($_GET['instructor']) && isset($_GET['course']) && isset($_GET['yearLevel'])) {
        $fetch = $dbCon->real_escape_string($_GET['fetch']);
        $course = $dbCon->real_escape_string($_GET['course']);
        $yearLevel = $dbCon->real_escape_string($_GET['yearLevel']);
        $instructor = $dbCon->real_escape_string($_GET['instructor']);
        $result = [];


        if ($fetch == 'subjects') {
            // Get all subjects handled by the instructor in a specific course and year level
            // $subjectsQuery = $dbCon->query("SELECT * FROM subjects WHERE course='$course' AND year_level='$yearLevel' AND instructor='$instructor'");
            // $result = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

            $subjectsQuery = $dbCon->query(
                "SELECT 
                subject_instructors.*,
                subjects.year_level as year_level,
                subjects.name as name,
                subjects.code as code,
                subjects.units as units,
                subjects.credits_units as credits_units,
                subjects.term as term,
                courses.course_code as course,
                courses.course_code as course_code
                FROM subject_instructors
                LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
                LEFT JOIN courses ON subjects.course = courses.id
                WHERE subject_instructors.instructor_id = '" . AuthController::user()->id . "' AND subjects.course = '$course' AND subjects.year_level = '$yearLevel'"
            );

            $result = $subjectsQuery->fetch_all(MYSQLI_ASSOC);
        } else if ($fetch == 'sections') {
            // Get active school year
            $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");

            if ($schoolYearQuery->num_rows > 0) {
                $schoolYear = $schoolYearQuery->fetch_assoc();

                // Get section
                $sectionsQuery = $dbCon->query("SELECT * FROM sections WHERE course='$course' AND year_level='$yearLevel' AND school_year='{$schoolYear['id']}'");
                $result = $sectionsQuery->fetch_all(MYSQLI_ASSOC);
            }
        }

        header('Content-type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    exit;
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
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
    $year_level = $dbCon->real_escape_string($_POST['year_level']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $passing_rate = $dbCon->real_escape_string($_POST['passing_rate']);
    $max_score = $dbCon->real_escape_string($_POST['max_score']);
    $type = $dbCon->real_escape_string($_POST['type']);

    // Get active school year
    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");

    if ($schoolYearQuery->num_rows > 0) {
        $schoolYear = $schoolYearQuery->fetch_assoc();

        $query = $dbCon->query("UPDATE activities SET 
            name = '$activity_name', 
            subject = '$subject', 
            school_year = '{$schoolYear['id']}', 
            term = '{$schoolYear['semester']}', 
            year_level = '$year_level', 
            course = '$course', 
            passing_rate = '" . intval($passing_rate) / 100 . "', 
            max_score = '$max_score',
            type='$type'
            WHERE id = '$id'
        ");

        if ($query) {
            $hasSuccess = true;
            $message = "Activity updated successfully!";
        } else {
            $hasError = true;
            $message = "Something went wrong. Please try again!";
        }
    } else {
        $hasError = true;
        $message = "Failed to update activity. There is no currently active school year. Contact your admin to create a new school year.";
    }
}

// get all activities
$query = $dbCon->query("SELECT 
    activities.*,
    subjects.id AS subject_id,
    subjects.name AS subject_name,
    courses.course AS course_name,
    grading_criterias.criteria_name AS criteria_name,
    grading_criterias.id AS criteria_id
    FROM activities 
    INNER JOIN subjects ON activities.subject = subjects.id
    INNER JOIN courses ON activities.course = courses.id
    INNER JOIN school_year ON activities.school_year = school_year.id
    LEFT JOIN grading_criterias ON activities.type = grading_criterias.id
    WHERE activities.instructor = '" . AuthController::user()->id . "' AND activities.id = '$id'");
$activity = $query->fetch_assoc();

// Fetch all subjects
// $subjectQuery = $dbCon->query("SELECT * FROM subjects");

// Fetch school years
$schoolYearQuery = $dbCon->query("SELECT * FROM  school_year");

// Fetch all courses
$courseQuery = $dbCon->query("SELECT * FROM  courses");

// Fetch all grading criterias
$gradingCriteriasQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE instructor=" . AuthController::user()->id);

// Count all subjects that the instructor is handling
$subjectsQuery = $dbCon->query(
    "SELECT 
    subject_instructors.*,
    subjects.year_level as year_level,
    subjects.name as name,
    subjects.code as code,
    subjects.units as units,
    subjects.credits_units as credits_units,
    subjects.term as term,
    courses.course_code as course,
    courses.course_code as course_code
    FROM subject_instructors
    LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
    LEFT JOIN courses ON subjects.course = courses.id
    WHERE subject_instructors.instructor_id = " . AuthController::user()->id
);
$subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);
$subjectsCount = count($subjects);
?>
<style>
    /* Style to hide number input arrows */
    /* Chrome, Safari, Edge, Opera */
    input.passing-rate::-webkit-outer-spin-button,
    input.passing-rate::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input.passing-rate[type=number] {
        -moz-appearance: textfield;
    }
</style>
<main class="w-screen overflow-y-auto h-[100vh] flex">
    <?php require_once("../../layout/sidebar.php")  ?>

    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex justify-center items-center flex-col p-8 ">

            <h2 class="text-[38px] font-bold mb-4">Edit Activity</h2>
            <form class="flex flex-col gap-[24px] md:px-[64px] w-full flex" method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $id ?>">

                <?php if ($gradingCriteriasQuery->num_rows == 0) { ?>
                    <div role="alert" class="alert alert-error mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="grid grid-cols-1 md:grid-cols-2 gap-2 w-full">
                            <span class='flex items-center'>Before you can edit an activity, you must first create your grading criteria.</span>
                            <div class='flex w-full justify-end items-center'>
                                <a href='../manage-grading-criteria.php' class='btn btn'>
                                    <i class='bx bx-plus-circle'></i> Create
                                </a>
                            </div>
                        </span>
                    </div>
                <?php } ?>

                <?php if ($hasError) { ?>
                    <div role="alert" class="alert alert-error mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="grid grid-cols-1 md:grid-cols-2 gap-2 w-full">
                            <?= $message ?>
                        </span>
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
                    <input class="input input-bordered" placeholder="Activity name" name="activity_name" value="<?= $activity['name'] ?>" required <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?> />
                </label>


                <!-- Main Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex flex-col col-span gap-2">
                        <span class="font-bold text-[18px]">Type of Activity</span>
                        <select class="select select-bordered" name="type" required <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?>>
                            <!--Display all the Course here-->
                            <option value="" selected disabled>Select Type</option>

                            <?php while ($row = $gradingCriteriasQuery->fetch_assoc()) { ?>
                                <option value="<?= $row['id'] ?>" <?php if ($row['id'] == $activity['type']) : ?> selected <?php endif; ?>><?= $row['criteria_name'] ?></option>
                            <?php } ?>
                        </select>
                    </label>

                    <label class="flex flex-col col-span gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <select class="select select-bordered" name="course" required <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?>>
                            <!--Display all the Course here-->
                            <option value="" selected disabled>Select Course</option>

                            <?php while ($row = $courseQuery->fetch_assoc()) { ?>
                                <option value="<?= $row['id'] ?>" <?php if ($row['id'] == $activity['course']) : ?> selected <?php endif; ?>><?= $row['course'] ?></option>
                            <?php } ?>
                        </select>
                    </label>
                </div>

                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?>>
                            <!--Display all the Year here-->
                            <option value="" selected disabled>Select Year level</option>
                            <option value="1st Year" <?php if ("1st Year" == $activity['year_level']) : ?> selected <?php endif; ?>>1st Year</option>
                            <option value="2nd Year" <?php if ("2nd Year" == $activity['year_level']) : ?> selected <?php endif; ?>>2nd Year</option>
                            <option value="3rd Year" <?php if ("3rd Year" == $activity['year_level']) : ?> selected <?php endif; ?>>3rd Year</option>
                            <option value="4th Year" <?php if ("4th Year" == $activity['year_level']) : ?> selected <?php endif; ?>>4th Year</option>
                            <option value="5th Year" <?php if ("5th Year" == $activity['year_level']) : ?> selected <?php endif; ?>>5th Year</option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Subject</span>
                        <select class="select select-bordered" name="subject" required <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?>>
                            <!--Display all the subjects here-->
                            <option value="" selected disabled>Select Subject</option>

                            <?php foreach($subjects as $row) { ?>
                                <option value="<?= $row['subject_id'] ?>" <?php if ($row['subject_id'] == $activity['subject']) : ?> selected <?php endif; ?>>(<?= $row['code'] ?>) <?= $row['name'] ?></option>
                            <?php } ?>
                        </select>
                    </label>
                </div>

                <div class="grid md:grid-cols-2 gap-4">

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Passing Rate</span>
                        <input type="number" class="input input-bordered passing-rate" placeholder="EG: 10%" value="<?= $activity['passing_rate'] * 100 ?>" name="passing_rate" min="1" max="100" required <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?> />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Max Score</span>
                        <input type="number" class="input input-bordered" value="<?= $activity['max_score'] ?>" name="max_score" min="0" onchange="(e) => parseInt(e.target.value) <= 0 ? e.target.value='1' : ''" value="1" pattern="[0-9]+" required <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?> />
                    </label>
                </div>

                <!-- Actions -->
                <div class="grid grid-cols-2 gap-4">
                    <a type="button" href="../manage-activity.php<?= isset($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" class="btn text-base btn-error">Go Back</a>
                    <button class="btn text-base btn-success" name="update-activity" <?php if ($gradingCriteriasQuery->num_rows == 0) : ?> disabled <?php endif; ?>>Update</button>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
    const course = document.querySelector("select[name='course']");
    const yearLevel = document.querySelector("select[name='year_level']");
    const section = document.querySelector("select[name='section']");
    const subject = document.querySelector("select[name='subject']");

    course.addEventListener('change', function(e) {
        if (document.querySelector('select[name="year_level"]:disabled')) {
            yearLevel.removeAttribute('disabled');
        } else {
            const selectedCourse = e.target.value.trim();
            const selectedYearLevel = yearLevel.value.trim();

            if (!!selectedYearLevel) {
                // Load all subjects for the selected course and year level
                fetchSubjects(selectedCourse, selectedYearLevel);

                // Load all sections for the selected course and year level
                // fetchSections(selectedCourse, selectedYearLevel);
            }
        }
    });

    yearLevel.addEventListener('change', function(e) {
        const selectedCourse = course.value.trim();
        const selectedYearLevel = e.target.value.trim();

        if (!!selectedCourse && !!selectedYearLevel) {
            // Load all subjects for the selected course and year level
            fetchSubjects(selectedCourse, selectedYearLevel);

            // Load all sections for the selected course and year level
            // fetchSections(selectedCourse, selectedYearLevel);
        }
    });

    async function fetchSubjects(course, yearLevel) {
        subject.setAttribute('disabled', '');
        subject.innerHTML = "<option selected disabled>Loading subjects</option>";

        const subjects = await fetch(`<?= $_SERVER['PHP_SELF'] ?>?fetch=subjects&instructor=<?= AuthController::user()->id ?>&course=${course}&yearLevel=${yearLevel}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/json"
                }
            })
            .then(res => res.json());

        if (subjects.length > 0) {
            subject.removeAttribute('disabled');
            subject.innerHTML = "<option value='' selected disabled>Select subject</option>";

            subjects.forEach(__subject__ => {
                const option = document.createElement('option');
                option.setAttribute('value', __subject__.subject_id);

                const textNode = document.createTextNode(`(${__subject__.code}) ${__subject__.name}`);
                option.appendChild(textNode);
                subject.appendChild(option);
            });
        } else {
            subject.setAttribute('disabled', '');

            const option = document.createElement('option');
            option.setAttribute('value', '');
            option.setAttribute('selected', '');
            option.setAttribute('disabled', '');

            const textNode = document.createTextNode('You have no subjects handled here');
            option.appendChild(textNode);
            subject.appendChild(option);
        }
    }

    /* async function fetchSections(course, yearLevel) {
        section.setAttribute('disabled', '');
        section.innerHTML = "<option selected disabled>Loading sections</option>";

        const sections = await fetch(`<?= "" // $_SERVER['PHP_SELF'] ?>?fetch=sections&instructor=<?= "" // AuthController::user()->id ?>&course=${course}&yearLevel=${yearLevel}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/json"
                }
            })
            .then(res => res.json());

        if (sections.length > 0) {
            section.removeAttribute('disabled');
            section.innerHTML = "<option value='' selected disabled>Select section</option>";

            sections.forEach(__section__ => {
                const option = document.createElement('option');
                option.setAttribute('value', __section__.id);

                const textNode = document.createTextNode(__section__.name);
                option.appendChild(textNode);
                section.appendChild(option);
            });
        } else {
            section.setAttribute('disabled', '');

            const option = document.createElement('option');
            option.setAttribute('value', '');
            option.setAttribute('selected', '');
            option.setAttribute('disabled', '');

            const textNode = document.createTextNode('No available sections');
            option.appendChild(textNode);
            section.appendChild(option);
        }
    } */
</script>