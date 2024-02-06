<?php 

session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";

if(!isset($_GET['section'])) {
    header("Location: ../");
    exit();
}

if(isset($_POST['search-student'])) {
    $hasSearch = true;
    $search = $_POST['search-student'];
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$studentGradesCountQuery = $dbCon->query("SELECT COUNT(*) AS total FROM ap_student_final_grades WHERE section = '" . $dbCon->real_escape_string($_GET['section']) . "'");
$studentGradesCount = $studentGradesCountQuery->fetch_all(MYSQLI_ASSOC);
$total = $studentGradesCount[0]['total'];
$pages = ceil($total / $limit);


$section_id = $dbCon->real_escape_string($_GET['section']);

// get section details
$sectionQuery = "SELECT 
    ap_sections.*,
    ap_courses.course_code AS course_code,
    ap_school_year.school_year AS school_year
    FROM ap_sections 
    JOIN ap_courses ON ap_sections.course = ap_courses.id
    JOIN ap_school_year ON ap_sections.school_year = ap_school_year.id
    WHERE ap_sections.id = '$section_id'";

$sectionResult = $dbCon->query($sectionQuery);
$section = $sectionResult->fetch_assoc();

if($hasSearch) {
    $studentGradesQuery = "SELECT 
        ap_student_final_grades.*,
        ap_sections.name as section_name,
        ap_subjects.name AS subject_name,
        ap_courses.course_code AS course_code,
        ap_school_year.school_year AS school_year,
        CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) AS student_name
        FROM ap_student_final_grades
        JOIN ap_userdetails ON ap_student_final_grades.student = ap_userdetails.id
        JOIN ap_subjects ON ap_student_final_grades.subject = ap_subjects.id
        JOIN ap_school_year ON ap_student_final_grades.school_year = ap_school_year.id
        JOIN ap_sections ON ap_student_final_grades.section = ap_sections.id
        JOIN ap_courses ON ap_sections.course = ap_courses.id
        WHERE ap_student_final_grades.section = '$section_id' AND CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) LIKE '%$search%' LIMIT $start, $limit";
} else {    
    $studentGradesQuery = "SELECT 
        ap_student_final_grades.*,
        ap_sections.name as section_name,
        ap_subjects.name AS subject_name,
        ap_courses.course_code AS course_code,
        ap_school_year.school_year AS school_year,
        CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) AS student_name
        FROM ap_student_final_grades
        JOIN ap_userdetails ON ap_student_final_grades.student = ap_userdetails.id
        JOIN ap_subjects ON ap_student_final_grades.subject = ap_subjects.id
        JOIN ap_school_year ON ap_student_final_grades.school_year = ap_school_year.id
        JOIN ap_sections ON ap_student_final_grades.section = ap_sections.id
        JOIN ap_courses ON ap_sections.course = ap_courses.id
        WHERE ap_student_final_grades.section = '$section_id' LIMIT $start, $limit";
}
$studentGradesResult = $dbCon->query($studentGradesQuery);

// fetch all result in associative array format
$studentGrades = $studentGradesResult->fetch_all(MYSQLI_ASSOC);
?>

<main class="h-[95%] overflow-x-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex flex-col justify-between">
                    <h2 class="text-[26px] font-bold">Section: <?= $section['name'] ?></h2>
                    <span class="text-[18px]">Course: <?= $section['course_code'] ?></span>
                </div>

                <div class="flex gap-4">
                    <!-- Search bar -->
                    <form class="w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" autocomplete="off">   
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" name="search-student" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search student" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Back button -->
                    <a href="../view-grades" class="btn">Go Back</a>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white">ID</th>
                            <th class="bg-slate-500 text-white">Student</th>
                            <th class="bg-slate-500 text-white">School Year</th>
                            <th class="bg-slate-500 text-white">Semester</th>
                            <th class="bg-slate-500 text-white">Course</th>
                            <th class="bg-slate-500 text-white">Total Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($studentGradesResult->num_rows > 0) : ?>
                            <?php foreach ($studentGrades as $studentGrade) : ?>
                                <tr>
                                    <td><?= $studentGrade['id'] ?></td>
                                    <td><?= $studentGrade['student_name'] ?></td>
                                    <td><?= $studentGrade['school_year'] ?></td>
                                    <td><?= $studentGrade['term'] ?></td>
                                    <td><?= $studentGrade['course_code'] ?></td>
                                    <td><?= $studentGrade['grade'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-center">No grades to show</td>
                            </tr>
                        <?php endif; ?>
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
</main>