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

if (isset($_POST['search-course'])) {
    $search = $dbCon->real_escape_string($_POST['search-course']);
    $hasSearch = true;
}

// Edit course
if (isset($_POST['edit_course'])) {
    $course = $dbCon->real_escape_string($_POST['course']);
    $courseCode = $dbCon->real_escape_string($_POST['course_code']);
    $id = $dbCon->real_escape_string($_POST['id']);

    $courseCodeExistQuery = $dbCon->query("SELECT * FROM ap_courses WHERE id = '$id'");

    if ($courseCodeExistQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Course does not exist!";
    } else {
        $query = "UPDATE ap_courses SET course = '$course', course_code = '$courseCode' WHERE id = '$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Course updated successfully!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Course update failed!";
        }
    }
}

// Delete course
if (isset($_POST['delete-course'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $courseCodeExistQuery = $dbCon->query("SELECT * FROM ap_courses WHERE id = '$id'");

    if ($courseCodeExistQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Course does not exist!";
    } else {
        $query = "DELETE FROM ap_courses WHERE id = '$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Course deleted successfully!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Course deletion failed!";
        }
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// count total pages
if ($hasSearch) {
    $searchQuery = "SELECT COUNT(*) AS count FROM ap_courses WHERE course LIKE '%$search%' OR course_code LIKE '%$search%'";
    $courseCount = $dbCon->query($searchQuery)->fetch_assoc();
} else {
    $courseCount = $dbCon->query("SELECT COUNT(*) AS count FROM ap_courses")->fetch_assoc();
}
$total = $courseCount['count'];
$pages = ceil($total / $limit);

// prefetch all courses
if ($hasSearch) {
    $courses = $dbCon->query("SELECT * FROM ap_courses WHERE course LIKE '%$search%' OR course_code LIKE '%$search%' LIMIT $start, $limit");
} else {
    $courses = $dbCon->query("SELECT * FROM ap_courses LIMIT $start, $limit");
}
?>

<main class="overflow-hidden flex h-screen">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

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

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[28px] font-bold">Course</h1>
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
                            <input type="search" name="search-course" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search course" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Create button -->
                    <a href="./create/course.php" class="btn">Create</a>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead class="">
                        <tr>
                            <th class="bg-slate-500 text-white">ID</th>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Code</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($courses->num_rows == 0) { ?>
                            <tr>
                                <td colspan="4" class="text-center">No records found</td>
                            </tr>
                        <?php } else { ?>
                            <?php while ($course = $courses->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= $course['id'] ?></td>
                                    <td class="capitalize text-[18px]"><?= $course['course'] ?></td>
                                    <td>
                                        <span class="badge bg-yellow-200 font-bold p-4 text-black">
                                            <?= $course['course_code'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex justify-center gap-2">
                                            <label for="view-course-<?= $course['id'] ?>" class="btn btn-sm bg-blue-300 text-white">View</label>
                                            <label for="edit-course-<?= $course['id'] ?>" class="btn btn-sm bg-gray-300 text-white">Edit</label>
                                            <label for="delete-modal-<?= $course['id'] ?>" class="btn btn-sm bg-red-500 text-white">Delete</label>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
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

    <!-- Fetch all courses again -->
    <?php $courses = $dbCon->query("SELECT * FROM ap_courses LIMIT $start, $limit"); ?>

    <!-- Modals -->
    <?php while ($course = $courses->fetch_assoc()) { ?>

        <!-- View Course Modal -->
        <input type="checkbox" id="view-course-<?= $course['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <h3 class="text-lg font-bold">Course ID #<?= $course['id'] ?></h3>
                <p class="py-4">Course name: <?= $course['course'] ?></p>
                <p class="py-1">Course code: <?= $course['course_code'] ?></p>
            </div>
            <label class="modal-backdrop" for="view-course-<?= $course['id'] ?>">Close</label>
        </div>

        <!-- Edit Course Modal -->
        <input type="checkbox" id="edit-course-<?= $course['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <form class="flex flex-col gap-4  px-[32px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <!-- Name -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-base">Course</span>
                        <input type="text" class="border border-gray-400 input input-bordere capitalize" placeholder="Course Name" name="course" value="<?= $course['course'] ?>">
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-base">Course Code</span>
                        <input type="text" class="border border-gray-400 input input-bordered" placeholder="123456" name="course_code" value="<?= $course['course_code'] ?>">
                    </label>

                    <input type="hidden" name="id" value="<?= $course['id'] ?>">

                    <!-- Actions -->
                    <div class="flex flex-col gap-2">
                        <button class="btn btn-success text-base text-white" name="edit_course">Edit</button>
                        <label class="btn btn-error text-base text-white" for="edit-course-<?= $course['id'] ?>">Cancel</label>
                    </div>
                </form>
            </div>
            <label class="modal-backdrop" for="edit-course-<?= $course['id'] ?>">Close</label>
        </div>

        <!-- Delete Course Modal -->
        <input type="checkbox" id="delete-modal-<?= $course['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="id" value="<?= $course['id'] ?>">
                    <label class="btn" for="delete-modal-<?= $course['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete-course">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-modal-<?= $course['id'] ?>">Close</label>
        </div>

    <?php } ?>
</main>