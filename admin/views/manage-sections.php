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

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";

if(isset($_POST['search-section'])) {
    $search = $dbCon->real_escape_string($_POST['search-section']);
    $hasSearch = true;
}

// Delete section from ap_sections table and from ap_section_students table
if (isset($_POST['delete-section'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $deleteSectionQuery = "DELETE FROM ap_sections WHERE id = $id";
    $deleteSectionStudentsQuery = "DELETE FROM ap_section_students WHERE section_id = $id";

    if ($dbCon->query($deleteSectionStudentsQuery) && $dbCon->query($deleteSectionQuery)) {
        $hasSuccess = true;
        $message = "Section deleted successfully!";
    } else {
        $hasError = true;
        $message = "Error deleting section!";
    }
}


// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if($hasSearch) {
    $result2 = mysqli_query($dbCon, "SELECT COUNT(*) AS id FROM ap_sections WHERE name LIKE '%$search%'");
} else {
    $result2 = mysqli_query($dbCon, "SELECT COUNT(*) AS id FROM ap_sections");
}
$sectionsCount = mysqli_fetch_array($result2);
$total = $sectionsCount['id'];
$pages = ceil($total / $limit);

// fetch all sections
if($hasSearch) {
    $sectionsQuery = "SELECT 
        ap_sections.id,
        ap_sections.name AS name,
        ap_courses.course_code AS course,
        ap_school_year.school_year AS school_year,
        ap_sections.term AS term,
        ap_sections.year_level AS year_level,
        CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) AS instructor
        FROM ap_sections 
        LEFT JOIN ap_courses ON ap_sections.course = ap_courses.id
        LEFT JOIN ap_school_year ON ap_sections.school_year = ap_school_year.id
        LEFT JOIN ap_userdetails ON ap_sections.instructor = ap_userdetails.id
        WHERE ap_sections.name LIKE '%$search%'
        LIMIT $start, $limit
    ";
} else {
    $sectionsQuery = "SELECT 
        ap_sections.id,
        ap_sections.name AS name,
        ap_courses.course_code AS course,
        ap_school_year.school_year AS school_year,
        ap_sections.term AS term,
        ap_sections.year_level AS year_level,
        CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) AS instructor
        FROM ap_sections 
        LEFT JOIN ap_courses ON ap_sections.course = ap_courses.id
        LEFT JOIN ap_school_year ON ap_sections.school_year = ap_school_year.id
        LEFT JOIN ap_userdetails ON ap_sections.instructor = ap_userdetails.id
        LIMIT $start, $limit
    ";
}
?>


<main class="overflow-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-screen">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Sections</h1>
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
                            <input type="search" name="search-section" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search section" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Create button -->
                    <a href="./create/sections.php" class="btn">Create</a>
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
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Course</td>
                            <td class="bg-slate-500 text-white">School Year</td>
                            <td class="bg-slate-500 text-white">Term</td>
                            <td class="bg-slate-500 text-white">Year Level</td>
                            <td class="bg-slate-500 text-white">Instructor</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sections = $dbCon->query($sectionsQuery); ?>
                        <?php if($sections->num_rows > 0) { ?>
                            <?php while ($section = $sections->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= $section['id'] ?></td>
                                    <td><?= $section['name'] ?></td>
                                    <td><?= $section['course'] ?></td>
                                    <td><?= $section['school_year'] ?></td>
                                    <td><?= $section['term'] ?></td>
                                    <td><?= $section['year_level'] ?></td>
                                    <td><?= $section['instructor'] ?></td>
                                    <td class="flex justify-center gap-4">
                                        <a href="./update/section.php?id=<?= $section['id'] ?>" class="btn btn-sm">Edit</a>
                                        <label for="delete-section-<?= $section['id'] ?>" class="btn btn-sm btn-error">Delete</label>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="8" class="text-center">No sections found!</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center">
                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 >= $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <?php $sections = $dbCon->query($sectionsQuery); ?>
    <?php while ($section = $sections->fetch_assoc()) { ?>

        <!-- Delete Modal -->
        <input type="checkbox" id="delete-section-<?= $section['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="id" value="<?= $section['id'] ?>">

                    <label class="btn" for="delete-section-<?= $section['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete-section">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-section-<?= $section['id'] ?>">Close</label>
        </div>

    <?php } ?>
</main>