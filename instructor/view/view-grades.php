<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";

if(isset($_POST['search-subject'])) {
    $hasSearch = true;
    $search = $_POST['search-subject'];
}

// Get all subjects that the instructor is handling
if($hasSearch) {
    $subjectsQuery = "SELECT
        subject_instructors.*,
        subjects.name as name,
        subjects.year_level as year_level,
        subjects.code as code,
        courses.course AS course,
        courses.course_code AS course_code
        FROM subject_instructors
        LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
        LEFT JOIN courses ON subjects.course = courses.id
        WHERE subject_instructors.instructor_id='" . AuthController::user()->id . "' AND subjects.name LIKE '%$search%'
    ";
} else {
    $subjectsQuery = "SELECT
        subject_instructors.*,
        subjects.name as name,
        subjects.year_level as year_level,
        subjects.code as code,
        courses.course AS course,
        courses.course_code AS course_code
        FROM subject_instructors
        LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
        LEFT JOIN courses ON subjects.course = courses.id
        WHERE subject_instructors.instructor_id='" . AuthController::user()->id . "'";
}

$subjectsResult = $dbCon->query($subjectsQuery);
$subjects = $subjectsResult->fetch_all(MYSQLI_ASSOC);

?>


<main class="h-screen overflow-x-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">View Grades</h1>
                </div>

                <div class="flex gap-4 px-4">
                    <!-- Search bar -->
                    <form class="w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" autocomplete="off">   
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" name="search-subject" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search subject" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subjects that the instructor is handling -->
            <div class=' overflow-hidden sm:pr-[48px] sm:grid sm:grid-cols-2 gap-4 md:grid-cols-2 lg:grid-cols-3 p-4 mt-8'>
                <?php if($subjectsResult->num_rows > 0): ?>
                    <?php foreach($subjects as $key => $subject): ?>
                        <a href="./view/subject_grade.php?subject=<?= $subject['subject_id'] ?>" class="">
                            <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4'>
                                <h1 class='text-[32px] font-semibold text-center cursor-pointer'><?= $subject['name'] ?></h1> <!-- Section name -->
                                <span><?= $subject['course_code'] ?> (<?= $subject['year_level'] ?>)</span> <!-- Course code -->
                                <span><?= $subject['code'] ?></span> <!-- Subject code -->
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="flex justify-center items-center h-[300px] rounded-[5px] border border-gray-400 p-4 flex-col gap-2 mb-4">
                        <h1 class="text-[32px] font-semibold text-center">No subjects found</h1>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>