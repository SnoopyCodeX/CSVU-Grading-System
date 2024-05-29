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

// Year level
$yearLevel = $dbCon->real_escape_string($_GET['yearLevel']);

// fetch course
$courseId = $dbCon->real_escape_string($_GET['id']);
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

if (isset($_POST['search-section'])) {
    $search = $dbCon->real_escape_string($_POST['search-section']);
    $hasSearch = true;
}

// Delete section from sections table and from section_students table
if (isset($_POST['delete-section'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $deleteSectionQuery = "DELETE FROM sections WHERE id = $id";
    $deleteSectionStudentsQuery = "DELETE FROM section_students WHERE section_id = $id";

    if ($dbCon->query($deleteSectionStudentsQuery) && $dbCon->query($deleteSectionQuery)) {
        $hasSuccess = true;
        $message = "Section deleted successfully!";
    } else {
        $hasError = true;
        $message = "Error deleting section!";
    }
}

// Create section
if (isset($_POST['create-section'])) {
    $sectionName = $dbCon->real_escape_string($_POST['name']);

    // Get active school year
    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");
    $schoolYearData = $schoolYearQuery->fetch_all(MYSQLI_ASSOC);

    if (count($schoolYearData) > 0) {
        $schoolYearId = $schoolYearData[0]['id'];
        $semester = $schoolYearData[0]['semester'];

        $checkSectionExistsQuery = $dbCon->query("SELECT 
            * 
            FROM sections 
            WHERE name='$sectionName' AND school_year='$schoolYearId' AND year_level='$yearLevel' AND course='$courseId'
        ");

        if ($checkSectionExistsQuery->num_rows == 0) {
            $createSectionQuery = $dbCon->query("INSERT INTO sections(name, school_year, year_level, course) VALUES(
                '$sectionName',
                '$schoolYearId',
                '$yearLevel',
                '$courseId'
            )");

            if ($createSectionQuery) {
                $hasSuccess = true;
                $hasError = false;
                $message = "Successfully created a new section!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "An error occured while creating a new section";
            }
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Section name already exists! Please pick a different section name";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "There is no active school year. Please set or create an active school year <a href='../manage-schoolyear.php'>here.</a>";
    }
}

// Update section
if (isset($_POST['edit-section'])) {
    $sectionId = $dbCon->real_escape_string($_POST['id']);
    $sectionName = $dbCon->real_escape_string($_POST['name']);

    // Get active school year
    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");
    $schoolYearData = $schoolYearQuery->fetch_all(MYSQLI_ASSOC);

    if (count($schoolYearData) > 0) {
        $schoolYearId = $schoolYearData[0]['id'];
        $semester = $schoolYearData[0]['semester'];

        // Check if section id exists
        $checkSectionIdQuery = $dbCon->query("SELECT * FROM sections WHERE id = $sectionId");

        if ($checkSectionIdQuery->num_rows > 0) {
            $checkSectionExistsQuery = $dbCon->query("SELECT 
                * 
                FROM sections 
                WHERE name='$sectionName' AND school_year='$schoolYearId' AND year_level='$yearLevel' AND course='$courseId'
            ");

            if ($checkSectionExistsQuery->num_rows == 0) {
                $updateSectionQuery = $dbCon->query("UPDATE sections SET name='$sectionName', school_year='$schoolYearId', year_level='$yearLevel', course='$courseId' WHERE id = '$sectionId'");

                if ($updateSectionQuery) {
                    $hasSuccess = true;
                    $hasError = false;
                    $message = "Section has been updated successfully!";
                } else {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "An error occured while updating section";
                }
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Section name already exists! Please pick a different section name";
            }
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "The section you're trying to update does not exist!";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "There is no active school year. Please set or create an active school year <a href='../manage-schoolyear.php'>here.</a>";
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if ($hasSearch) {
    $result2 = mysqli_query($dbCon, "SELECT COUNT(*) AS id FROM sections WHERE name LIKE '%$search%' AND year_level='$yearLevel' AND course='$courseId'");
} else {
    $result2 = mysqli_query($dbCon, "SELECT COUNT(*) AS id FROM sections WHERE year_level='$yearLevel' AND course='$courseId'");
}
$sectionsCount = mysqli_fetch_array($result2);
$total = $sectionsCount['id'];
$pages = ceil($total / $limit);


// fetch all sections
if ($hasSearch) {
    $sectionsQuery = "SELECT 
        sections.id,
        sections.name AS name,
        courses.course_code AS course,
        courses.course_code AS courseName,
        school_year.school_year AS school_year,
        school_year.semester AS term,
        sections.year_level AS year_level
        FROM sections 
        LEFT JOIN courses ON sections.course = courses.id
        LEFT JOIN school_year ON sections.school_year = school_year.id
        WHERE sections.name LIKE '%$search%' AND sections.year_level='$yearLevel' AND sections.course='$courseId'
        LIMIT $start, $limit
    ";
} else {
    $sectionsQuery = "SELECT 
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
        WHERE sections.year_level='$yearLevel' AND sections.course='$courseId'
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
                            <?= $yearLevel ?> > Sections</h1>
                    </div>

                    <div class="flex gap-2 items-center">
                        <h1 class="text-[18px]">Course Adviser: </h1>
                        <span><?= $course['adviserFullname'] ?? 'Not Assigned' ?></span>
                    </div>
                </div>

                <div class="flex items-center gap-4 md:px-4">
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
                            <input type="search" name="search-section" id="default-search"
                                class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Search section" value="<?= $hasSearch ? $search : '' ?>" required>
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

                    <!-- Back button -->
                    <a href="./course.php?id=<?= $courseId ?>" class="btn hover:bg-[#276bae] hover:text-white">
                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>left_fill</title>
                            <g id="left_fill" fill='none' fill-rule='evenodd'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M7.94 13.06a1.5 1.5 0 0 1 0-2.12l5.656-5.658a1.5 1.5 0 1 1 2.121 2.122L11.122 12l4.596 4.596a1.5 1.5 0 1 1-2.12 2.122L7.938 13.06Z' />
                            </g>
                        </svg>
                        Go Back</a>

                    <!-- Create button -->
                    <label for="create-section" class="btn bg-[#276bae] text-white">
                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>add_circle_fill</title>
                            <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                            </g>
                        </svg>

                        <eate>Create</eate>
                    </label>
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
                            <td class="bg-[#276bae] text-white text-center">Course</td>
                            <td class="bg-[#276bae] text-white text-center">Section Name</td>
                            <td class="bg-[#276bae] text-white text-center">School Year</td>
                            <td class="bg-[#276bae] text-white text-center">Term</td>
                            <td class="bg-[#276bae] text-white text-center">Year Level</td>
                            <td class="bg-[#276bae] text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sections = $dbCon->query($sectionsQuery); ?>
                        <?php if ($sections->num_rows > 0) { ?>
                        <?php while ($section = $sections->fetch_assoc()) { ?>
                        <tr class="hover">
                            <!-- <td><?= $section['id'] ?></td> -->
                            <td class="text-center"><?= $section['courseName'] ?></td>
                            <td class="text-center"><?= $section['name'] ?></td>
                            <td class="text-center"><?= $section['school_year'] ?></td>
                            <td class="text-center"><?= $section['term'] ?></td>
                            <td class="text-center"><?= $section['year_level'] ?></td>
                            <td class="flex justify-center gap-4">
                                <a href="./section_students.php?courseId=<?= $courseId ?>&sectionId=<?= $section['id'] ?>&yearLevel=<?= $yearLevel ?>"
                                    class="btn bg-[#276bae] text-white btn-sm">
                                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                        <title>user_3_fill</title>
                                        <g id="user_3_fill" fill='none' fill-rule='nonzero'>
                                            <path
                                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                            <path fill='currentColor'
                                                d='M12 13c2.396 0 4.575.694 6.178 1.672.8.488 1.484 1.064 1.978 1.69.486.615.844 1.351.844 2.138 0 .845-.411 1.511-1.003 1.986-.56.45-1.299.748-2.084.956-1.578.417-3.684.558-5.913.558s-4.335-.14-5.913-.558c-.785-.208-1.524-.506-2.084-.956C3.41 20.01 3 19.345 3 18.5c0-.787.358-1.523.844-2.139.494-.625 1.177-1.2 1.978-1.69C7.425 13.695 9.605 13 12 13Zm0-11a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z' />
                                        </g>
                                    </svg>

                                    <span>Students</span>

                                </a>
                                <label for="edit-section-<?= $section['id'] ?>"
                                    class="btn bg-gray-500 text-white btn-sm">
                                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                        <title>edit_line</title>
                                        <g id="edit_line" fill='none' fill-rule='nonzero'>
                                            <path
                                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                            <path fill='currentColor'
                                                d='M13 3a1 1 0 0 1 .117 1.993L13 5H5v14h14v-8a1 1 0 0 1 1.993-.117L21 11v8a2 2 0 0 1-1.85 1.995L19 21H5a2 2 0 0 1-1.995-1.85L3 19V5a2 2 0 0 1 1.85-1.995L5 3h8Zm6.243.343a1 1 0 0 1 1.497 1.32l-.083.095-9.9 9.899a1 1 0 0 1-1.497-1.32l.083-.094 9.9-9.9Z' />
                                        </g>
                                    </svg>
                                    <span>
                                        Edit
                                    </span>
                                </label>
                                <Delete for="delete-section-<?= $section['id'] ?>"
                                    class="btn btn-sm text-white bg-red-500">
                                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                        <title>delete_2_fill</title>
                                        <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                            <path
                                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                            <path fill='currentColor'
                                                d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                        </g>
                                    </svg>
                                    <span>
                                        Delete
                                    </span>
                                </Delete>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php } else { ?>
                        <tr class="hover">
                            <td colspan="6" class="text-center">No sections found!</td>
                        </tr>
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

    <!-- Modals -->

    <!-- Create Modal -->
    <input type="checkbox" id="create-section" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <h3 class="text-lg text-center font-bold">Create section</h3>

            <form class="flex flex-col gap-4 mt-4" method="post">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Section</span>
                    <input class="input input-bordered" name="name" placeholder="Enter section name" required />
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <label class="btn" for="create-section">Close</label>
                    <button class="btn bg-[#276bae] text-white" name="create-section">

                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>add_circle_fill</title>
                            <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                            </g>
                        </svg>
                        <span>Create</span>
                    </button>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="create-section">Close</label>
    </div>

    <?php $sections = $dbCon->query($sectionsQuery); ?>
    <?php while ($section = $sections->fetch_assoc()) { ?>

    <!-- Edit Modal -->
    <input type="checkbox" id="edit-section-<?= $section['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <h3 class="text-lg text-center font-bold">Edit section</h3>

            <form class="flex flex-col gap-4 mt-4" method="post">
                <input type="hidden" name="id" value="<?= $section['id'] ?>">

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Section</span>
                    <input class="input input-bordered" name="name" placeholder="Enter section name"
                        value="<?= $section['name'] ?>" required />
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <label class="btn" for="edit-section-<?= $section['id'] ?>">Close</label>
                    <button class="btn btn-success" name="edit-section">Update</button>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="edit-section-<?= $section['id'] ?>">Close</label>
    </div>

    <!-- Delete Modal -->
    <input type="checkbox" id="delete-section-<?= $section['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <h3 class="text-lg font-bold text-error">Notice!</h3>
            <p class="py-4">Are you sure you want to delete this section? This action cannot be undone!</p>

            <form class="flex justify-end gap-4 items-center" method="post">
                <input type="hidden" name="id" value="<?= $section['id'] ?>">

                <label class="btn" for="delete-section-<?= $section['id'] ?>">Close</label>
                <button class="btn btn-error" name="delete-section">Delete</button>
            </form>
        </div>
        <label class="modal-backdrop" for="delete-section-<?= $section['id'] ?>">Close</label>
    </div>

    <?php } ?>
</main>