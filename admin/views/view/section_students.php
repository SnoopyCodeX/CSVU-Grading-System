<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";

// If there is a search
if (isset($_POST['search-student'])) {
    $search = $dbCon->real_escape_string($_POST['search-student']);
    $hasSearch = true;
}

// Remove student
if (isset($_POST['remove-student'])) {
    $sectionId = $dbCon->real_escape_string($_POST['section_id']);
    $studentId = $dbCon->real_escape_string($_POST['student_id']);

    $delete = $dbCon->query("DELETE FROM section_students WHERE student_id='$studentId' AND section_id='$sectionId'");

    $hasError = !$delete;
    $hasSuccess = $delete;
    $message = $delete
        ? 'Student has been successfully removed from this section.'
        : 'Failed to remove student from this section.';
}

// Page from previous panel
$prevPanelPage = $dbCon->real_escape_string($_GET['currentPage'] ?? '');

// Year level
$yearLevel = $dbCon->real_escape_string($_GET['yearLevel']);

// fetch course
$courseId = $dbCon->real_escape_string($_GET['courseId']);
$courseQuery = $dbCon->query("SELECT 
    courses.*, 
    CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as adviserFullname 
    FROM courses 
    LEFT JOIN userdetails ON courses.adviser = userdetails.id 
    WHERE courses.id = '$courseId'
");

// if no course was found, return back to manage-sections.php
if ($courseQuery->num_rows == 0) {
    header('location: ../manage-sections.php');
    exit;
}

$course = $courseQuery->fetch_assoc();

// Fetch section
$sectionId = $dbCon->real_escape_string($_GET['sectionId']);
$sectionQuery = "SELECT 
    sections.id,
    sections.name AS name,
    courses.course,
    courses.course_code AS courseName,
    school_year.school_year AS school_year,
    school_year.semester AS term,
    sections.year_level AS year_level
    FROM sections 
    LEFT JOIN courses ON sections.course = courses.id
    LEFT JOIN school_year ON sections.school_year = school_year.id
    WHERE sections.id='$sectionId' 
        AND sections.year_level='$yearLevel' 
        AND sections.course='$courseId' 
";
$sectionQueryResult = $dbCon->query($sectionQuery);

if ($sectionQueryResult->num_rows == 0) {
    header('location: ../manage-sections.php');
    exit;
}

$section = $sectionQueryResult->fetch_assoc();

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if ($hasSearch) {
    $result2 = mysqli_query($dbCon, "SELECT 
        COUNT(*) as count
        FROM 
        section_students 
        JOIN userdetails ON section_students.student_id = userdetails.id 
        WHERE section_students.section_id = {$section['id']} AND CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%'
    ");
} else {
    $result2 = mysqli_query($dbCon, "SELECT 
        COUNT(*) as count
        FROM 
        section_students 
        JOIN userdetails ON section_students.student_id = userdetails.id 
        WHERE section_students.section_id = {$section['id']}
    ");
}

$sectionsCount = mysqli_fetch_array($result2);
$total = $sectionsCount['count'];
$pages = ceil($total / $limit);

// Fetch all students in the specific section
if ($hasSearch) {
    $studentsQuery = "SELECT 
        section_students.is_irregular as is_irregular,
        userdetails.*,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as fullName
        FROM 
        section_students 
        JOIN userdetails ON section_students.student_id = userdetails.id 
        WHERE section_students.section_id = {$section['id']}
        AND CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%'
        LIMIT $start, $limit
    ";
} else {
    $studentsQuery = "SELECT 
        section_students.is_irregular as is_irregular,
        userdetails.*,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as fullName
        FROM 
        section_students 
        JOIN userdetails ON section_students.student_id = userdetails.id 
        WHERE section_students.section_id = {$section['id']}
        LIMIT $start, $limit
    ";
}
?>


<main class="overflow-x-auto flex">
    <?php require_once ("../../layout/sidebar.php") ?>
    <section class="w-full px-4 h-screen">
        <?php require_once ("../../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between">
                <!-- Table Header -->
                <div class="flex flex-col justify-between">
                    <div class="flex justify-between items-center">
                        <h1 class="text-[18px] md:text-[32px] font-bold"><?= $course['course_code'] ?> >
                            <?= $yearLevel ?> > Section <?= $section['name'] ?> > Students</h1>
                    </div>

                    <div class="flex gap-2 items-center">
                        <h1 class="text-[18px]">Course Adviser: </h1>
                        <span><?= $course['adviserFullname'] ?? 'Not Assigned' ?></span>
                    </div>
                </div>

                <div class="flex gap-4 md:px-4">
                     <!-- Back button -->
                     <a href="./course_section.php?id=<?= $courseId ?>&yearLevel=<?= $yearLevel ?><?= !empty($prevPanelPage) ? '&page=' . $prevPanelPage : '' ?>" class="btn bg-[#276bae] text-white">
                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>left_fill</title>
                            <g id="left_fill" fill='none' fill-rule='evenodd'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M7.94 13.06a1.5 1.5 0 0 1 0-2.12l5.656-5.658a1.5 1.5 0 1 1 2.121 2.122L11.122 12l4.596 4.596a1.5 1.5 0 1 1-2.12 2.122L7.938 13.06Z' />
                            </g>
                        </svg>
                        Go Back
                    </a>

                    <!-- Search bar -->
                    <form class="w-full md:w-[300px]" method="POST" autocomplete="off">
                        <label for="default-search"
                            class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="search" name="search-student" id="default-search"
                                class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Search student" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit"
                                class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($hasError) { ?>
            <div role="alert" class="alert alert-error mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?= $message ?></span>
            </div>
            <?php } ?>

            <?php if ($hasSuccess) { ?>
            <div role="alert" class="alert alert-success mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?= $message ?></span>
            </div>
            <?php } ?>

            <!-- Table Content -->
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-[#276bae] text-white text-center">Student ID</td>
                            <td class="bg-[#276bae] text-white text-center">Full Name</td>
                            <td class="bg-[#276bae] text-white text-center">Contact Number</td>
                            <td class="bg-[#276bae] text-white text-center">Email Address</td>
                            <td class="bg-[#276bae] text-white text-center">Birthday</td>
                            <td class="bg-[#276bae] text-white text-center">Sex</td>
                            <td class="bg-[#276bae] text-white text-center">Year Level</td>
                            <td class="bg-[#276bae] text-white text-center">Status</td>
                            <td class="bg-[#276bae] text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $students = $dbCon->query($studentsQuery); ?>
                        <?php if ($students->num_rows > 0) { ?>
                        <?php while ($student = $students->fetch_assoc()) { ?>
                        <tr class="hover">
                            <td class="text-center"><?= $student['sid'] ?></td>
                            <td class="text-center"><?= $student['fullName'] ?></td>
                            <td class="text-center"><?= $student['contact'] ?></td>
                            <td class="text-center"><?= $student['email'] ?></td>
                            <td class="text-center"><?= date('F j, Y', strtotime($student['birthday'])) ?></td>
                            <td class="text-center"><?= ucfirst($student['gender']) ?></td>
                            <td class="text-center"><?= ucwords($student['year_level']) ?></td>
                            <td class="text-center">
                                <span
                                    class="badge <?= $student['is_irregular'] == '1' ? 'badge-warning' : 'badge-success' ?>">
                                    <?= $student['is_irregular'] == 1 ? 'Irregular' : 'Regular' ?>
                                </span>
                            </td>
                            <td class="flex justify-center items-center gap-4">
                                <label for="remove-student-<?= $student['id'] ?>"
                                    class="btn btn-sm btn-error <?= $student['is_irregular'] == 1 ? 'btn-disabled' : '' ?>">Remove</label>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php } else { ?>
                        <tr>
                            <td colspan="9" class="text-center">No students found!</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end items-center gap-4">
                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>"
                    <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn bg-[#276bae] text-white" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>"
                    <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <!-- <?php $students = $dbCon->query($studentsQuery); ?> -->
    <?php while ($student = $students->fetch_assoc()) { ?>

    <!-- Delete Modal -->
    <input type="checkbox" id="remove-student-<?= $student['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <h3 class="text-lg font-bold text-error">Remove Student</h3>
            <p class="py-4">Are you sure you want to remove this student from this section? This action cannot be
                undone.</p>

            <form class="flex justify-end gap-4 items-center" method="post">
                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                <input type="hidden" name="section_id" value="<?= $sectionId ?>">

                <label class="btn" for="remove-student-<?= $student['id'] ?>">Close</label>
                <button class="btn btn-error" name="remove-student">Remove</button>
            </form>
        </div>
        <label class="modal-backdrop" for="remove-student-<?= $student['id'] ?>">Close</label>
    </div>

    <?php } ?>
</main>