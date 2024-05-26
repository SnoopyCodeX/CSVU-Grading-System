<?php
session_start();
// kung walang session mag reredirect sa login //

require("../configuration/config.php");
require '../auth/controller/auth.controller.php';
require '../utils/humanizer.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
    exit();
}

require_once("../components/header.php");

$hasError = false;
$hasSuccess = false;
$message = "";

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


$sectionsQuery = "SELECT
    sections.*,
    courses.course as courseName
    FROM subject_instructor_sections
    LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
    LEFT JOIN courses ON sections.course = courses.id
    WHERE subject_instructor_sections.instructor_id = " . AuthController::user()->id . " GROUP BY subject_instructor_sections.section_id";
$sectionsQueryResult = $dbCon->query($sectionsQuery);
$sections = $sectionsQueryResult->fetch_all(MYSQLI_ASSOC);

// Count all students in each section that the instructor is handling
$studentsCount = 0;
foreach ($sections as $section) {
    // Only count students that are enrolled to the instructor's subjects
    $studentsQuery = $dbCon->query("SELECT * FROM section_students WHERE section_id = {$section['id']}");
    $students = $studentsQuery->fetch_all(MYSQLI_ASSOC);
    $filteredStudents = [];

    foreach ($subjects as $subject) {
        foreach ($students as $student) {
            $studentEnrolledSubjectsQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE student_id = " . $student['student_id'] . " AND subject_id = '$subject[subject_id]'");
            $studentEnrolledSubjects = $studentEnrolledSubjectsQuery->fetch_all(MYSQLI_ASSOC);

            if ($studentEnrolledSubjectsQuery->num_rows > 0)            
                $filteredStudents[] = $student;
        }
    }

    $filteredStudents = removeDuplicates($filteredStudents, 'student_id');
    $studentsCount += count($filteredStudents);
}

// Count all sections that the instructor is handling
$sectionsCount = count($sections);
?>

<main class="h-screen flex overflow-x-auto md:overflow-hidden">
    <?php require_once("layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("layout/topbar.php") ?>

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

        <div class="stats shadow w-full mb-8">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-title">My Students</div>
                <div class="stat-value"><?= $studentsCount ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <div class="stat-title">My Subjects</div>
                <div class="stat-value"><?= $subjectsCount ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">My Sections</div>
                <div class="stat-value"><?= $sectionsCount ?></div>
            </div>

        </div>

        <div class="px-4 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-bold">Assigned Subjects</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto md:overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 330px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="text-center">Subject Code</td>
                            <td class="text-center">Subject Name</td>
                            <td class="text-center">Course</td>
                            <td class="text-center">Year Level</td>
                            <td class="text-center">Term</td>
                            <td class="text-center">Units</td>
                            <td class="text-center">Credits Units</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($subjects) > 0) : ?>
                            <?php foreach ($subjects as $subject) : ?>
                                <tr>
                                    <td class="text-center"><?= $subject['code'] ?></td>
                                    <td class="text-center"><?= $subject['name'] ?></td>
                                    <td class="text-center"><?= $subject['course'] ?></td>
                                    <td class="text-center"><?= $subject['year_level'] ?></td>
                                    <td class="text-center"><?= $subject['term'] ?></td>
                                    <td class="text-center"><?= $subject['units'] ?></td>
                                    <td class="text-center"><?= $subject['credits_units'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">There are no subjects assigned to you</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</main>