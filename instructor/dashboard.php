<?php
session_start();
// kung walang session mag reredirect sa login //

require("../configuration/config.php");
require '../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
    exit();
}

require_once("../components/header.php");

$sectionsQuery = "SELECT
    ap_subjects.*,
    ap_sections.id as sectionId,
    ap_courses.course as courseName
    FROM ap_sections 
    JOIN ap_subjects ON ap_sections.subject = ap_subjects.id 
    JOIN ap_section_students ON ap_sections.id = ap_section_students.section_id
    JOIN ap_courses ON ap_subjects.course = ap_courses.id
    WHERE ap_sections.instructor = " . AuthController::user()->id . " GROUP BY ap_sections.subject";

$sectionsQueryResult = $dbCon->query($sectionsQuery);
$sections = $sectionsQueryResult->fetch_all(MYSQLI_ASSOC);

// Count all students in each section that the instructor is handling
$studentsCount = 0;
foreach ($sections as $key => $section) {
    $countStudentsQuery = "SELECT COUNT(*) as count FROM ap_section_students WHERE section_id = " . $section['sectionId'];
    $countStudentsQueryResult = $dbCon->query($countStudentsQuery);
    $countStudents = $countStudentsQueryResult->fetch_assoc();
    $studentsCount += $countStudents['count'];
}

// Count all sections that the instructor is handling
$sectionsCount = count($sections);
?>

<main class="h-screen flex overflow-hidden">
    <?php require_once("layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("layout/topbar.php") ?>

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
                <div class="stat-value"><?= count($sections) ?></div>
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
                    <h1 class="text-[24px] font-bold">Recent Subjects</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 330px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td>ID</td>
                            <td>Name</td>
                            <td>Course</td>
                            <td>Year Level</td>
                            <td>Units</td>
                            <td>Credits Units</td>
                            <td>Term</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($sections as $key => $section) {
                        ?>
                            <tr>
                                <th><?= $section['id'] ?></th>
                                <td><?= $section['name'] ?></td>
                                <td><?= $section['courseName'] ?></td>
                                <td><?= $section['year_level'] ?></td>
                                <td><?= $section['units'] ?></td>
                                <td><?= $section['credits_units'] ?></td>
                                <td><?= $section['term'] ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</main>