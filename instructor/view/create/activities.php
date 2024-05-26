<?php
session_start();
// kung walang session mag reredirect sa login //

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

            $subjectsQuery = $dbCon->query("SELECT 
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
require_once("../../../configuration/config.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Create Activity
if (isset($_POST['create-activity'])) {
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
        // Get all grading criterias
        $gradingCriteriasQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE instructor=" . AuthController::user()->id);

        if ($gradingCriteriasQuery->num_rows > 0) {
            $schoolYear = $schoolYearQuery->fetch_assoc();

            if ($max_score > 0) {
                $query = $dbCon->query("INSERT INTO activities (
                    name, 
                    subject, 
                    school_year, 
                    term, 
                    year_level, 
                    course, 
                    passing_rate, 
                    max_score, 
                    instructor,
                    type
                ) VALUES (
                    '$activity_name', 
                    '$subject', 
                    '" . $schoolYear['id'] . "', 
                    '" . $schoolYear['semester'] . "', 
                    '$year_level', 
                    '$course', 
                    '" . intval($passing_rate) / 100 . "', 
                    '$max_score',
                    '" . AuthController::user()->id . "',
                    '$type'
                )");

                if ($query) {
                    $hasSuccess = true;
                    $message = "Activity created successfully!";
                } else {
                    $hasError = true;
                    $message = "Something went wrong. Please try again! {$dbCon->error}";
                }
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Failed to create new activity. Max activity score must be greater than 0!";
            }
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "<span class='flex items-center'>Failed to create new activity. You must first create your grading criteria.</span> <div class='flex w-full justify-end items-center'><a href='../manage-grading-criteria.php' class='btn btn'><i class='bx bx-plus-circle'></i> Create</a></div>";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Failed to create new activity. There is no currently active school year. Contact your admin to create a new school year.";
    }
}

// Fetch school years
$schoolYearQuery = $dbCon->query("SELECT * FROM  school_year");

// Fetch all courses
$courseQuery = $dbCon->query("SELECT * FROM  courses");

// Fetch all grading criterias
$gradingCriteriasQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE instructor=" . AuthController::user()->id);

// Count all subjects that the instructor is handling
$subjectsQuery = $dbCon->query("SELECT 
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

            <h2 class="text-[24px] font-semibold mb-4">Create Activity</h2>
            <form class="flex flex-col gap-[24px] md:px-[64px] w-full flex" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
            
                <?php if ($gradingCriteriasQuery->num_rows == 0) { ?>
                    <div role="alert" class="alert alert-error mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="grid grid-cols-1 md:grid-cols-2 gap-2 w-full">
                            <span class='flex items-center'>Before you can create a new activity, you must first create your grading criteria.</span> 
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
                    <input class="input input-bordered" placeholder="Activity name" name="activity_name" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                </label>


                <!-- Main Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex flex-col col-span gap-2">
                        <span class="font-bold text-[18px]">Type of Activity</span>
                        <select class="select select-bordered" name="type" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?>>
                            <!--Display all the Course here-->
                            <option value="" selected disabled>Select Type</option>

                            <?php while ($row = $gradingCriteriasQuery->fetch_assoc()) { ?>
                                <option value="<?= $row['id'] ?>"><?= $row['criteria_name'] ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    
                    <label class="flex flex-col col-span gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <select class="select select-bordered" name="course" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?>>
                            <!--Display all the Course here-->
                            <option value="" selected disabled>Select Course</option>

                            <?php while ($row = $courseQuery->fetch_assoc()) { ?>
                                <option value="<?= $row['id'] ?>"><?= $row['course'] ?></option>
                            <?php } ?>
                        </select>
                    </label>
                </div>

                <div class="flex flex-col md:grid md:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required disabled <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?>>
                            <!--Display all the Year here-->
                            <option value="" selected disabled>Select Year level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Subject</span>
                        <select class="select select-bordered" name="subject" required disabled <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?>>
                            <!--Display all the subjects here-->
                            <option value="" selected disabled>Select Subject</option>
                        </select>
                    </label>

                    <!-- <label class="flex flex-col col-span gap-2">
                        <span class="font-bold text-[18px]">Section</span>
                        <select class="select select-bordered" name="section" required disabled <?php // if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php // endif; ?>>
                            <option value="" selected disabled>Select Section</option>

                            <?php // while ($row = $sectionQuery->fetch_assoc()) { ?>
                                <option value="<?= "" // $row['id'] ?>"><?= "" // $row['name'] ?></option>
                            <?php // } ?>
                        </select>
                    </label> -->
                </div>

                <div class="grid md:grid-cols-2 gap-4">

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Passing Rate</span>
                        <input type="number" class="input input-bordered passing-rate" placeholder="EG: 10%" name="passing_rate" min="1" max="100" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Max Score</span>
                        <input type="number" class="input input-bordered" name="max_score" min="0" value="1" pattern="[0-9]+" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                    </label>
                </div>

                <!-- Actions -->
                <div class="grid grid-cols-2 gap-4">
                    <a type="button" href="../manage-activity.php<?= isset($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" class="btn text-base btn-error">Go Back</a>
                    <button class="btn text-base btn-success" name="create-activity" <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> >Create</button>
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

    // async function fetchSections(course, yearLevel) {
    //     section.setAttribute('disabled', '');
    //     section.innerHTML = "<option selected disabled>Loading sections</option>";

    //     const sections = await fetch(`<?= $_SERVER['PHP_SELF'] ?>?fetch=sections&instructor=<?= AuthController::user()->id ?>&course=${course}&yearLevel=${yearLevel}`, {
    //             headers: {
    //                 "X-Requested-With": "XMLHttpRequest",
    //                 "Content-Type": "application/json"
    //             }
    //         })
    //         .then(res => res.json());

    //     if (sections.length > 0) {
    //         section.removeAttribute('disabled');
    //         section.innerHTML = "<option value='' selected disabled>Select section</option>";

    //         sections.forEach(__section__ => {
    //             const option = document.createElement('option');
    //             option.setAttribute('value', __section__.id);

    //             const textNode = document.createTextNode(`${__section__.name}`);
    //             option.appendChild(textNode);
    //             section.appendChild(option);
    //         });
    //     } else {
    //         section.setAttribute('disabled', '');

    //         const option = document.createElement('option');
    //         option.setAttribute('value', '');
    //         option.setAttribute('selected', '');
    //         option.setAttribute('disabled', '');

    //         const textNode = document.createTextNode('No available sections');
    //         option.appendChild(textNode);
    //         section.appendChild(option);
    //     }
    // }

    document.querySelector("input[name='passing_rate']").addEventListener("input", function(e) {
        if (parseInt(e.target.value) > 100) {
            e.target.value = "100";
        } else if (parseInt(e.target.value) < 1) {
            e.target.value = "1";
        }
    })

    document.querySelector("input[name='max_score']").addEventListener("input", function(e) {
        if (parseInt(e.target.value) < 1) {
            e.target.value = "1";
        }
    })
</script>