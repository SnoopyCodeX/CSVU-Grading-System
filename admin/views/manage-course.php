<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../components/header.php");

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
    $adviser = $dbCon->real_escape_string($_POST['adviser'] ?? 'NULL');
    $id = $dbCon->real_escape_string($_POST['id']);

    if (strlen($courseCode) > 12) {
        $hasError = true;
        $message = "Course code must be 12 characters long";
    } else {
        $courseCodeExistQuery = $dbCon->query("SELECT * FROM courses WHERE id = '$id'");

        if ($courseCodeExistQuery->num_rows == 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Course does not exist!";
        } else {
            $query = strtolower($adviser) == 'none'
                ? "UPDATE courses SET course = '$course', course_code = '$courseCode', adviser=NULL WHERE id = '$id'"
                : "UPDATE courses SET course = '$course', course_code = '$courseCode', adviser=$adviser WHERE id = '$id'";

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
}

// Delete course
if (isset($_POST['delete-course'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $courseCodeExistQuery = $dbCon->query("SELECT * FROM courses WHERE id = '$id'");

    if ($courseCodeExistQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Course does not exist!";
    } else {
        $query = "DELETE FROM courses WHERE id = '$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            // Get all subjects in the course
            $subjectsQuery = $dbCon->query("SELECT * FROM subjects WHERE course=$id");

            // Get all sections in the course
            $sectionsQuery = $dbCon->query("SELECT * FROM sections WHERE course=$id");

            if ($subjectsQuery->num_rows > 0) {
                $subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

                // Loop through each subjects
                foreach ($subjects as $subject) {
                    // Get all activities for the current subject
                    $activitiesQuery = $dbCon->query("SELECT * FROM activities WHERE subject='{$subject['id']}'");

                    if ($activitiesQuery->num_rows > 0) {
                        $activities = $activitiesQuery->fetch_all(MYSQLI_ASSOC);

                        // Loop through each activities
                        foreach ($activities as $activity) {
                            // Delete all activity scores for the current activity
                            $dbCon->query("DELETE FROM activity_scores WHERE activity_id = {$activity['id']}");
                        }

                        // Delete all activities under the current subject
                        $dbCon->query("DELETE FROM activities WHERE subject={$subject['id']}");
                    }

                    // Delete all activities for the current subject
                    $dbCon->query("DELETE FROM activities WHERE subject = {$subject['id']}");

                    // Delete all grade release request for the current subject
                    $dbCon->query("DELETE FROM instructor_grade_release_requests WHERE subject_id={$subject['id']}");

                    // Delete all enrolled subject from the student
                    $dbCon->query("DELETE FROM student_enrolled_subjects WHERE subject_id = {$subject['id']}");

                    // Delete all irregular subject from the student
                    $dbCon->query("DELETE FROM section_students WHERE irregular_subject_id = {$subject['id']}");

                    // Delete all student final grades
                    $dbCon->query("DELETE FROM student_final_grades WHERE subject = {$subject['id']}");

                    // Delete subject from instructor's assigned subjects
                    $dbCon->query("DELETE FROM subject_instructors WHERE subject_id = {$subject['id']}");

                    // Delete subject from instructor's assigned sections
                    $dbCon->query("DELETE FROM subject_instructor_sections WHERE subject_id = {$subject['id']}");
                }

                // Delete all subjects under the current course
                $dbCon->query("DELETE FROM subjects WHERE course=$id");
            }

            if ($sectionsQuery->num_rows > 0) {
                $sections = $sectionsQuery->fetch_all(MYSQLI_ASSOC);

                // Loop through each sections
                foreach ($sections as $section) {
                    // Delete students in the current section
                    $dbCon->query("DELETE FROM section_students WHERE section_id = {$section['id']}");

                    // Delete section from instructor's assigned sections
                    $dbCon->query("DELETE FROM subject_instructor_sections WHERE section_id = {$section['id']}");
                }

                // Delete all the sections under the course
                $dbCon->query("DELETE FROM sections WHERE course=$id");
            }

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
    $searchQuery = "SELECT COUNT(*) AS count FROM courses WHERE course LIKE '%$search%' OR course_code LIKE '%$search%'";
    $courseCount = $dbCon->query($searchQuery)->fetch_assoc();
} else {
    $courseCount = $dbCon->query("SELECT COUNT(*) AS count FROM courses")->fetch_assoc();
}
$total = $courseCount['count'];
$pages = ceil($total / $limit);

// prefetch all courses
if ($hasSearch) {
    $courses = $dbCon->query("SELECT 
        courses.*,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as fullName
        FROM 
        courses 
        LEFT JOIN userdetails ON courses.adviser = userdetails.id
        WHERE courses.course LIKE '%$search%' 
        OR courses.course_code LIKE '%$search%' 
        LIMIT $start, $limit
    ");
} else {
    $courses = $dbCon->query("SELECT 
        courses.*,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as fullName
        FROM courses 
        LEFT JOIN userdetails ON courses.adviser = userdetails.id
        LIMIT $start, $limit
    ");
}

// Fetch all instructors
$instructorsQuery = "SELECT 
    *, 
    CONCAT(firstName, ' ', middleName, ' ', lastName) as fullName 
    FROM userdetails 
    WHERE roles='instructor'
    ORDER BY fullName ASC
";
$instructorsQueryResult = $dbCon->query($instructorsQuery);
$instructors = $instructorsQueryResult->fetch_all(MYSQLI_ASSOC);

// Fetch all instructors that have an assigned course
// $selectedInstructorsQuery = "SELECT 
//     id
//     FROM userdetails 
//     WHERE roles='instructor' 
//     AND id IN (SELECT adviser FROM courses)
// ";
// $selectedInstructorsQueryResult = $dbCon->query($selectedInstructorsQuery);
// $selectedInstructorsAssoc = $selectedInstructorsQueryResult->fetch_all(MYSQLI_ASSOC);
// $selectedInstructorIds = array_map(fn ($instructor) => $instructor['id'], $selectedInstructorsAssoc);

// Filter the `$instructors` array to get the unassigned instructors.
// I had to use `splat operator` here to re-index the array after filtering
// because `array_filter()` preserves the index after filtering.
//
// However, keep in mind that using `splat operator` to re-index
// an array only works if the array has a numerical index. Using
// `array_values()` is much more preferred if you have an array with
// a non-numerical index.
// $unselectedInstructorIds = [...array_filter(
//     array_map(
//         fn ($instructor) => $instructor['id'], $instructors), 
//         fn ($instructorId) => !in_array($instructorId, $selectedInstructorIds)
//     )
// ];
?>

<main class="overflow-x-auto flex h-screen">
    <?php require_once ("../layout/sidebar.php") ?>
    <section class="w-full px-4">
        <?php require_once ("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

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

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage Courses</h1>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-4 px-4">
                    <!-- Search bar -->
                    <form class="w-[280px] md:w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>"
                        autocomplete="off">
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
                            <input type="search" name="search-course" id="default-search"
                                class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Search course" value="<?= $hasSearch ? $search : '' ?>" required>
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

                    <!-- Create button -->
                    <a href="./create/course.php" class="btn text-white bg-[#276bae]">
                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>add_circle_fill</title>
                            <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                            </g>
                        </svg>
                        <span class="text-white">
                            Create
                        </span>
                    </a>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead class="">
                        <tr class="hover">
                            <!-- <th class="bg-slate-500 text-white">ID</th> -->
                            <td class="bg-[#276bae] text-white text-center">Course</td>
                            <td class="bg-[#276bae] text-white text-center">Code</td>
                            <td class="bg-[#276bae] text-white text-center">Adviser</td>
                            <td class="bg-[#276bae] text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($courses->num_rows == 0) { ?>
                        <tr class="hover">
                            <td colspan="4" class="text-center">No records found</td>
                        </tr>
                        <?php } else { ?>
                        <?php while ($course = $courses->fetch_assoc()) { ?>
                        <tr class="hover">
                            <!-- <td><?= $course['id'] ?></td> -->
                            <td class="capitalize text-center"><?= $course['course'] ?></td>
                            <td class="text-center">
                                <?= $course['course_code'] ?>
                            </td>
                            <td class="capitalize text-center"><?= $course['fullName'] ?? 'Not Assigned' ?></td>
                            <td>
                                <div class="flex justify-center gap-2">
                                    <label for="view-course-<?= $course['id'] ?>"
                                        class="btn btn-sm bg-[#276bae] text-white">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>View</title>
                                            <g id="eye_2_fill" fill='none' fill-rule='nonzero'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M12 5c3.679 0 8.162 2.417 9.73 5.901.146.328.27.71.27 1.099 0 .388-.123.771-.27 1.099C20.161 16.583 15.678 19 12 19c-3.679 0-8.162-2.417-9.73-5.901C2.124 12.77 2 12.389 2 12c0-.388.123-.771.27-1.099C3.839 7.417 8.322 5 12 5Zm0 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z' />
                                            </g>
                                        </svg>
                                        <span>View</span>
                                    </label>
                                    <label for="edit-course-<?= $course['id'] ?>"
                                        class="btn btn-sm bg-gray-500 text-white">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>Edit</title>
                                            <g id="edit_line" fill='none' fill-rule='nonzero'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M13 3a1 1 0 0 1 .117 1.993L13 5H5v14h14v-8a1 1 0 0 1 1.993-.117L21 11v8a2 2 0 0 1-1.85 1.995L19 21H5a2 2 0 0 1-1.995-1.85L3 19V5a2 2 0 0 1 1.85-1.995L5 3h8Zm6.243.343a1 1 0 0 1 1.497 1.32l-.083.095-9.9 9.899a1 1 0 0 1-1.497-1.32l.083-.094 9.9-9.9Z' />
                                            </g>
                                        </svg>
                                        <span>Edit</span>
                                    </label>
                                    </label>
                                    <label for="delete-modal-<?= $course['id'] ?>"
                                        class="btn btn-sm bg-red-500 text-white">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>Delete</title>
                                            <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                            </g>
                                        </svg>
                                        <span>Delete</span>
                                    </label>
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
                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>"
                    <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>"
                    <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Fetch all courses again -->
    <?php $courses = $dbCon->query("SELECT * FROM courses LIMIT $start, $limit"); ?>

    <!-- Modals -->
    <?php while ($course = $courses->fetch_assoc()) { ?>

    <!-- View Course Modal -->
    <input type="checkbox" id="view-course-<?= $course['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">

            <div class="flex flex-col gap-4  px-[32px] mb-auto">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-white">Course</span>
                    <input type="text" class="border border-gray-400 input input-bordere capitalize"
                        placeholder="Course Name" name="course" value="<?= $course['course'] ?>" readonly>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-white">Course Code</span>
                    <input type="text" class="border border-gray-400 input input-bordered" placeholder="123456"
                        name="course_code" value="<?= $course['course_code'] ?>" readonly>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Assigned Adviser</span>
                    <?php if ($instructorsQueryResult->num_rows > 0 || isset($course['adviser'])): ?>
                    <select class="select select-bordered" name="adviser" required disabled>
                        <?php if ($course['adviser'] != null): ?>
                        <option value="" disabled selected>Select Adviser</option>

                        <?php foreach ($instructors as $instructor) { ?>
                        <option value="<?php echo $instructor['id'] ?>"
                            <?php if ($instructor['id'] == $course['adviser']): ?> selected <?php endif; ?>>
                            <?= $instructor['fullName'] ?></option>
                        <?php } ?>
                        <?php else: ?>
                        <option value="" disabled selected>Not Assigned</option>
                        <?php endif; ?>
                    </select>
                    <?php else: ?>
                    <div role="alert" class="alert alert-error mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="flex space-between items-center gap-4">
                            <span>No advisers available.</span>
                            <a class="btn" href="./manage-instructor.php">
                                <span class="bx bx-plus"></span>
                                Add Instructor
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                </label>
            </div>
        </div>
        <label class="modal-backdrop" for="view-course-<?= $course['id'] ?>">Close</label>
    </div>

    <!-- Edit Course Modal -->
    <input type="checkbox" id="edit-course-<?= $course['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <form class="flex flex-col gap-4  px-[32px] mb-auto" method="post">
                <!-- Name -->
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-white">Course</span>
                    <input type="text" class="border border-gray-400 input input-bordere capitalize"
                        placeholder="Course Name" name="course" value="<?= $course['course'] ?>">
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-white">Course Code</span>
                    <input type="text" class="border border-gray-400 input input-bordered" placeholder="123456"
                        name="course_code" value="<?= $course['course_code'] ?>">
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Assign Adviser</span>

                    <select class="select select-bordered" name="adviser" required>
                        <option value="" disabled selected>Select Adviser</option>
                        <!-- <option value="none">None</option> -->

                        <?php foreach ($instructors as $instructor) { ?>
                        <option value="<?php echo $instructor['id'] ?>"
                            <?php if ($instructor['id'] == $course['adviser']): ?> selected <?php endif; ?>>
                            <?= $instructor['fullName'] ?></option>
                        <?php } ?>
                    </select>

                    <!-- <?php // if(count($unselectedInstructorIds) > 0 || $course['adviser'] != null): ?>
                            <select class="select select-bordered" name="adviser" required>
                                <option value="" disabled selected>Select Adviser</option>
                                <option value="none">None</option>
                                
                                <?php // foreach($instructors as $instructor) { ?>
                                    <?php // if(in_array($instructor['id'], $selectedInstructorIds) && $instructor['id'] != $course['adviser']) continue; ?>

                                    <option value="<?= "" // $instructor['id'] ?>" <?php // if($instructor['id'] == $course['adviser']): ?> selected <?php // endif; ?>><?= "" // $instructor['fullName'] ?></option>
                                <?php // } ?>
                            </select>
                        <?php // else: ?>
                            <div role="alert" class="alert alert-error mb-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="flex space-between items-center gap-4">
                                    <span>No advisers available.</span> 
                                    <a class="btn" href="./manage-instructor.php">
                                        <span class="bx bx-plus"></span> 
                                        Add Instructor
                                    </a>
                                </span>
                            </div>
                        <?php // endif; ?> -->
                </label>

                <input type="hidden" name="id" value="<?= $course['id'] ?>">

                <!-- Actions -->
                <div class="flex flex-col gap-2">
                    <button class="btn btn-success text-white text-white" name="edit_course">Edit</button>
                    <label class="btn btn-error text-white text-white"
                        for="edit-course-<?= $course['id'] ?>">Cancel</label>
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
            <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this course will
                also delete all subjects, activities, sections, and others under this course. Would you still like to to
                proceed?</p>

            <form class="flex justify-end gap-4 items-center" method="post">
                <input type="hidden" name="id" value="<?= $course['id'] ?>">
                <label class="btn" for="delete-modal-<?= $course['id'] ?>">Cancel</label>
                <button class="btn btn-error" name="delete-course">Yes, Delete</button>
            </form>
        </div>
        <label class="modal-backdrop" for="delete-modal-<?= $course['id'] ?>">Close</label>
    </div>

    <?php } ?>
</main>