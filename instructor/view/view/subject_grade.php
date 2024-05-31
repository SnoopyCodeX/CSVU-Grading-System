<?php

session_start();
// kung walang session mag reredirect sa login //

require ("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';
require ("../../../utils/grades.php");

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$hasFilter = false;
$message = "";

if (!isset($_GET['subject'])) {
    header("Location: ../");
    exit();
}

$subject_id = $dbCon->real_escape_string($_GET['subject']);
$students = [];

// get active school year
$schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
$schoolYear = $schoolYearQuery->fetch_assoc();

// get subject details
$subjectQuery = $dbCon->query("SELECT 
    subject_instructors.*,
    subjects.year_level as year_level,
    subjects.name as name,
    subjects.code as code,
    subjects.units as units,
    subjects.credits_units as credits_units,
    subjects.term as term,
    courses.course as course_name,
    courses.id as course_id
    FROM subject_instructors
    LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
    LEFT JOIN courses ON subjects.course = courses.id
    WHERE subject_instructors.instructor_id = " . AuthController::user()->id . " AND subject_instructors.subject_id = $subject_id"
);

if ($subjectQuery->num_rows > 0) {
    $subject = $subjectQuery->fetch_assoc();

    // Fetch assigned sections for the selected subject
    $sectionsQuery = "SELECT
        sections.*,
        courses.course as courseName
        FROM subject_instructor_sections
        LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
        LEFT JOIN courses ON sections.course = courses.id
        WHERE subject_instructor_sections.instructor_id = " . AuthController::user()->id . " AND subject_instructor_sections.subject_id = $subject[subject_id]";
    $sectionsQueryResult = $dbCon->query($sectionsQuery);
    $sections = $sectionsQueryResult->fetch_all(MYSQLI_ASSOC);

    $students = [];

    // get all students handled by the instructor
    foreach ($sections as $section) {
        $studentsQuery = $dbCon->query("SELECT 
            section_students.student_id,
            section_students.section_id,
            userdetails.sid as studentID,
            CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as studentName,
            userdetails.firstName as studentFN,
            userdetails.middleName as studentMN,
            userdetails.lastName as studentLN
            FROM section_students 
            INNER JOIN userdetails ON section_students.student_id = userdetails.id
            WHERE section_students.section_id = " . $section['id'] . " GROUP BY section_students.student_id"
        );

        while ($row = $studentsQuery->fetch_assoc()) {
            // ONly show students that are enrolled to the subject
            $enrolledSubjectQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE subject_id = $subject[subject_id] AND student_id = $row[student_id]");

            if ($enrolledSubjectQuery->num_rows > 0) {
                $row['subject_id'] = $subject['subject_id'];
                $students[] = $row;
            }
        }
    }
}
?>

<main class="h-screen overflow-x-auto md:overflow-x-hidden flex">
    <?php require_once ("../../layout/sidebar.php") ?>
    <section class="w-full px-4">
        <?php require_once ("../../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center">
                <!-- Table Header -->
                <div class="flex flex-col justify-between">
                    <h2 class="text-[26px] font-bold">Subject: <?= $subject['name'] ?? "" ?></h2>
                    <span class="text-[18px]">Course: <?= $subject['course_name'] ?? "" ?></span>
                    <span class="text-[18px]">Year Level: <?= $subject['year_level'] ?? "" ?></span>
                </div>

                <a class="btn bg-[#276bae] text-white" href="../view-grades.php">
                    <i class="bx bx-chevron-left"></i>
                    Go Back
                </a>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto md:overflow-x-hidden border border-gray-300 rounded-md"
                style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <th class="bg-[#276bae] text-white text-center">Student ID</th>
                            <th class="bg-[#276bae] text-white text-center">Student</th>
                            <th class="bg-[#276bae] text-white text-center">Course</th>
                            <th class="bg-[#276bae] text-white text-center">Year Level</th>
                            <th class="bg-[#276bae] text-white text-center">Semester</th>
                            <th class="bg-[#276bae] text-white text-center">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                        <tr class="hover">
                            <td class="text-center"><?= $student['studentID'] ?></td>
                            <td class="text-center"><?= $student['studentName'] ?></td>
                            <td class="text-center"><?= $subject['course_name'] ?></td>
                            <td class="text-center"><?= $subject['year_level'] ?></td>
                            <td class="text-center"><?= $subject['term'] ?></td>
                            <td class="text-center">
                                <?php
                                    $computedGrade = number_format(
                                        computeStudentGradesFromSubject(
                                            $dbCon,
                                            $student['subject_id'],
                                            $subject['course_id'],
                                            $student['student_id'],
                                            AuthController::user()->id,
                                            $schoolYear['id'],
                                            $subject['term']
                                        ),
                                        2
                                    );
                                ?>
                                <?= $computedGrade == "-1.00" ? 'No activity scores' : $computedGrade ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No students enrolled in this subject.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<script>
document.querySelector("select[name='filter-section']").addEventListener('change', function() {
    document.querySelector("form#filter-section-form").submit();
});
</script>