<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Submit and compute final grades
if (isset($_POST['submit-grades'])) {
    $subject = $dbCon->real_escape_string($_POST['subject']);
    $section = $dbCon->real_escape_string($_POST['section']);
    $term = $dbCon->real_escape_string($_POST['term']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $schoolYear = $dbCon->real_escape_string($_POST['school_year']);

    // Get all students in the section
    $studentsQuery = "SELECT * FROM ap_section_students WHERE section_id = $section";
    $studentsQueryResult = $dbCon->query($studentsQuery);
    $students = $studentsQueryResult->fetch_all(MYSQLI_ASSOC);

    // Get all activities in the section
    $activitiesQuery = "SELECT 
        ap_activities.*,
        ap_subjects.name as subjectName,
        ap_subjects.id as subjectId,
        ap_student_grades.grade as studentGrade, 
        ap_student_grades.student_id as studentId
        FROM ap_activities 
        INNER JOIN ap_student_grades ON ap_student_grades.activity_id = ap_activities.id
        INNER JOIN ap_subjects ON ap_activities.subject = ap_subjects.id
        WHERE ap_activities.section=$section AND ap_activities.school_year=$schoolYear AND ap_activities.term='$term' AND ap_activities.year_level='$yearLevel' AND ap_activities.subject=$subject AND ap_activities.instructor = " . AuthController::user()->id;

    $activitiesQueryResult = $dbCon->query($activitiesQuery);
    $activities = $activitiesQueryResult->fetch_all(MYSQLI_ASSOC);

    if (count($activities) == 0) {
        $hasError = true;
        $message = "No activities found!";
    } else {
        // compute average score of each studen for each activity in a subject
        foreach ($students as $key => $student) {
            $studentId = $student['student_id'];
            $studentScore = 0;
            $totalScore = 0;

            foreach ($activities as $key => $activity) {
                if ($studentId == $activity['studentId']) {
                    $studentScore += $activity['studentGrade'];
                    $totalScore += $activity['max_score'];
                }
            }

            if ($totalScore == 0) {
                $finalGrade = 0;
            } else {
                $averageScore = $studentScore / $totalScore;
                $finalGrade = $averageScore * 100;
            }

            // check if grade with the same school_year, student, subject and year_level already exist. If so, update it, otherwise insert new grade
            $checkGradeQuery = "SELECT * FROM ap_student_final_grades WHERE school_year = $schoolYear AND student = $studentId AND subject = $subject AND year_level = '$yearLevel' AND term='$term'";
            $checkGradeQueryResult = $dbCon->query($checkGradeQuery);
            $checkGrade = $checkGradeQueryResult->fetch_assoc();

            if ($checkGrade) {
                $updateGradeQuery = "UPDATE ap_student_final_grades SET grade = $finalGrade WHERE school_year = $schoolYear AND student = $studentId AND subject = $subject AND year_level = '$yearLevel', term='$term'";
                $updateGradeQueryResult = $dbCon->query($updateGradeQuery);

                if ($updateGradeQueryResult) {
                    $hasSuccess = true;
                    $message = "Grades submitted successfully!";
                } else {
                    $hasError = true;
                    $message = "Something went wrong. Please try again!";
                }
            } else {
                $insertGradeQuery = "INSERT INTO ap_student_final_grades (subject, term, year_level, section, student, school_year, grade) VALUES (
                    $subject,
                    '$term',
                    '$yearLevel',
                    $section,
                    $studentId,
                    $schoolYear,
                    $finalGrade
                )";
                $insertGradeQueryResult = $dbCon->query($insertGradeQuery);

                if ($insertGradeQueryResult) {
                    $hasSuccess = true;
                    $message = "Grades submitted successfully!";
                } else {
                    $hasError = true;
                    $message = "Something went wrong. Please try again!";
                }
            }
        }
    }
}

// Delete activity
if (isset($_POST['delete-activity'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $deleteQuery = "DELETE FROM ap_activities WHERE id = $id";
    $result = $dbCon->query($deleteQuery);

    if ($result) {
        $hasSuccess = true;
        $message = "Activity deleted successfully!";
    } else {
        $hasError = true;
        $message = "Something went wrong. Please try again!";
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result = $dbCon->query("SELECT COUNT(*) AS id FROM ap_activities WHERE instructor = '" . AuthController::user()->id . "'");
$activitiesCount = $result->fetch_all(MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// get all activities
$query = "SELECT 
    ap_activities.*,
    ap_subjects.name AS subject_name,
    ap_courses.course_code AS course_code,
    ap_sections.name AS section_name,
    ap_sections.id AS section_id
    FROM ap_activities 
    INNER JOIN ap_subjects ON ap_activities.subject = ap_subjects.id
    INNER JOIN ap_courses ON ap_activities.course = ap_courses.id
    INNER JOIN ap_school_year ON ap_activities.school_year = ap_school_year.id
    INNER JOIN ap_sections ON ap_activities.section = ap_sections.id
    WHERE ap_activities.instructor = '" . AuthController::user()->id . "' LIMIT $start, $limit";

$sectionsQuery = "SELECT
    ap_sections.id as sectionId,
    ap_sections.name as sectionName,
    ap_courses.course_code as courseCode
    FROM ap_sections 
    JOIN ap_courses ON ap_sections.course = ap_courses.id
    WHERE ap_sections.instructor = " . AuthController::user()->id;

$sectionsQueryResult = $dbCon->query($sectionsQuery);
$sections = $sectionsQueryResult->fetch_all(MYSQLI_ASSOC);

// Count all students in each section that the instructor is handling
$studentsCount = [];
foreach ($sections as $key => $section) {
    $countStudentsQuery = "SELECT COUNT(*) as count FROM ap_section_students WHERE section_id = " . $section['sectionId'];
    $countStudentsQueryResult = $dbCon->query($countStudentsQuery);
    $countStudents = $countStudentsQueryResult->fetch_assoc();

    if(!isset($studentsCount["{$section['sectionId']}"]))
        $studentsCount["{$section['sectionId']}"] = $countStudents['count'];
    else
        $studentsCount["{$section['sectionId']}"] += $countStudents['count'];
}

// fetch all subjects
$subjectsQuery = "SELECT * FROM ap_subjects";

// fetch all school years
$schoolYearsQuery = "SELECT * FROM ap_school_year";
?>


<main class="h-[95%] overflow-x-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Activities</h1>
                </div>

                <div class="flex gap-4">
                    <label for="submit-modal" class="btn">Submit</label>
                    <a href="./create/activities.php" class="btn">Create</a>
                </div>
            </div>

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

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Term</td>
                            <td class="bg-slate-500 text-white">Students</td>
                            <td class="bg-slate-500 text-white">Subject</td>
                            <td class="bg-slate-500 text-white">Course</td>
                            <td class="bg-slate-500 text-white">Section</td>
                            <td class="bg-slate-500 text-white">Passing Rate</td>
                            <td class="bg-slate-500 text-white">Max Score</td>
                            <td class="bg-slate-500 text-white">Status</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $activityResult = $dbCon->query($query); ?>
                        <?php while ($row = $activityResult->fetch_assoc()) : ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= $row['name'] ?></td>
                                <td><?= $row['term'] ?></td>
                                <td><?= $studentsCount["{$row['section_id']}"] ?></td>
                                <td><?= $row['subject_name'] ?></td>
                                <td><?= $row['course_code'] ?></td>
                                <td><?= $row['section_name'] ?></td>
                                <td><?= $row['passing_rate'] * 100 ?>%</td>
                                <td><?= $row['max_score'] ?></td>
                                <td>
                                    <div class="badge p-4 bg-blue-300 text-black">
                                        On going
                                    </div>
                                </td>
                                <td>
                                    <div class="flex justify-center items-center gap-2">
                                        <a class="btn btn-sm" href="./view/activities.php?id=<?= $row['id'] ?>">View</a>
                                        <a class="btn btn-sm" href="./update/activities.php?id=<?= $row['id'] ?>">Edit</a>
                                        <label for="delete-activity-<?= $row['id'] ?>" class="btn btn-sm">Delete</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center">
                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn btn-sm" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 >= $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <?php $activityResult = $dbCon->query($query); ?>
    <?php while ($row = $activityResult->fetch_assoc()) : ?>
        <!-- Delete Modal -->
        <input type="checkbox" id="delete-activity-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <label class="btn" for="delete-activity-<?= $row['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete-activity">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-activity-<?= $row['id'] ?>">Close</label>
        </div>
    <?php endwhile; ?>

    <input type="checkbox" id="submit-modal" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Subject</span>
                    <select class="select select-bordered" name="subject" required>
                        <!-- Display all the subject related to the instructor -->
                        <option value="" selected disabled>Select Subject </option>

                        <?php $subjectsQueryResult = $dbCon->query($subjectsQuery); ?>
                        <?php while ($subject = $subjectsQueryResult->fetch_assoc()) : ?>
                            <option value="<?= $subject['id'] ?>"><?= $subject['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </label>

                <label class="flex flex-col gap-2 my-4">
                    <span class="font-bold text-[18px]">Section</span>
                    <select class="select select-bordered" name="section" required>
                        <!-- Display all the subject related to the instructor -->
                        <option value="" selected disabled>Select Section </option>

                        <?php $sectionsQueryResult = $dbCon->query($sectionsQuery); ?>
                        <?php while ($section = $sectionsQueryResult->fetch_assoc()) : ?>
                            <option value="<?= $section['sectionId'] ?>"><?= $section['sectionName'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </label>

                <label class="flex flex-col gap-2 my-4">
                    <span class="font-bold text-[18px]">Term</span>
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

                <label class="flex flex-col gap-2 my-4">
                    <span class="font-bold text-[18px]">School Year</span>
                    <select class="select select-bordered" name="school_year" required>
                        <!-- Display all the subject related to the instructor -->
                        <option value="" selected disabled>Select School Year </option>

                        <?php $schoolYearsQueryResult = $dbCon->query($schoolYearsQuery); ?>
                        <?php while ($schoolYear = $schoolYearsQueryResult->fetch_assoc()) : ?>
                            <option value="<?= $schoolYear['id'] ?>"><?= $schoolYear['school_year'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </label>

                <div class="flex justify-end gap-4 items-center my-4">
                    <label class="btn btn-error" for="submit-modal">Close</label>
                    <button class="btn btn-success" name="submit-grades">Submit Grades</button>
                </div>
            </form>

        </div>
        <label class="modal-backdrop" for="submit-modal">Close</label>
    </div>
</main>