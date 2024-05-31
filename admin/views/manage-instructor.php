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

// search instructor
if (isset($_POST['search-instructor'])) {
    $search = $dbCon->real_escape_string($_POST['search-instructor']);
    $hasSearch = true;
}

// update instructor
if (isset($_POST['update_instructor'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $firstName = $dbCon->real_escape_string($_POST['first_name']);
    $middleName = $dbCon->real_escape_string($_POST['middle_name']);
    $lastName = $dbCon->real_escape_string($_POST['lastname_name']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = str_replace("-", "", $dbCon->real_escape_string($_POST['contact']));
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Invalid email address";
    } else if (!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address. It should end with <strong>@cvsu.edu.ph</strong>";
    } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
    } else {
        // check if id exists in userdetails and has a role of 'instructor' query
        $checkIdQuery = "SELECT * FROM userdetails WHERE id = '$id' AND roles = 'instructor'";
        $checkIdResult = $dbCon->query($checkIdQuery);

        if ($checkIdResult->num_rows <= 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Instructor does not exist";
        } else {
            $updateQuery = "UPDATE userdetails SET 
                firstName = '$firstName',
                middleName = '$middleName',
                lastName = '$lastName',
                gender = '$gender',
                contact = '$contact',
                birthday = '$birthday',
                email = '$email'
            ";

            /* if ($newPassword) {
                // check if new password matches with the confirm password
                $newPasswordHashed = crypt($newPassword, '$6$Crypt$');
                $confirmPasswordHashed = crypt($confirmPassword, '$6$Crypt$');

                if ($newPasswordHashed != $confirmPasswordHashed) {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "The given passwords doesn't match!";
                } else {
                    $updateQuery .= ", password='" . $newPasswordHashed . "'";
                }
            } */

            if (!$hasError) {
                $updateQuery .= "WHERE id = '$id'";
                $result = $dbCon->query($updateQuery);

                if ($result) {
                    $hasError = false;
                    $hasSuccess = true;
                    $message = "Instructor updated successfully";
                } else {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "Something went wrong";
                }
            }
        }
    }
}

// delete instructor
if (isset($_POST['delete_instructor'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    // check if id exists in userdetails and has a role of 'instructor' query
    $checkIdQuery = "SELECT * FROM userdetails WHERE id = '$id' AND roles = 'instructor'";
    $checkIdResult = $dbCon->query($checkIdQuery);

    if ($checkIdResult->num_rows <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Instructor does not exist";
    } else {
        $deleteQuery = "DELETE FROM userdetails WHERE id = '$id'";
        $result = $dbCon->query($deleteQuery);

        if ($result) {
            // Get all activities for the current instructor
            $activitiesQuery = $dbCon->query("SELECT * FROM activities WHERE instructor = $id");

            if ($activitiesQuery->num_rows > 0) {
                $activities = $activitiesQuery->fetch_all(MYSQLI_ASSOC);

                // Loop through each activities
                foreach ($activities as $activity) {
                    // Delete all activity scores for the current activity
                    $dbCon->query("DELETE FROM activity_scores WHERE activity_id = {$activity['id']}");
                }

                // Delete all activities under the current instructor
                $dbCon->query("DELETE FROM activities WHERE instructor = $id");
            }

            // Delete all activities for the current instructor
            $dbCon->query("DELETE FROM activities WHERE instructor = $id");

            // Delete all grade release request for the current instructor
            $dbCon->query("DELETE FROM instructor_grade_release_requests WHERE instructor_id = $id");

            // Delete all grading criterias for the current instructor
            $dbCon->query("DELETE FROM grading_criterias WHERE instructor = $id");

            // Delete all assigned subjects for the current instructor
            $dbCon->query("DELETE FROM subject_instructors WHERE instructor_id = $id");

            // Delete all assigned sections for the current instructor
            $dbCon->query("DELETE FROM subject_instructor_sections WHERE instructor_id = $id");

            $hasError = false;
            $hasSuccess = true;
            $message = "Instructor deleted successfully";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong";
        }
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// get total records
if ($hasSearch) {
    $queryCount = "SELECT count(*) as total FROM userdetails WHERE roles = 'instructor' AND (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%')";
} else {
    $queryCount = "SELECT count(*) as total FROM userdetails WHERE roles = 'instructor'";
}
$resultCount = $dbCon->query($queryCount);
$rowCount = $resultCount->fetch_assoc();
$total = $rowCount['total'];
$pages = ceil($total / $limit);

// get all instructors
if ($hasSearch) {
    $query = "SELECT * FROM userdetails WHERE roles = 'instructor' AND (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%') LIMIT $start, $limit";
} else {
    $query = "SELECT * FROM userdetails WHERE roles = 'instructor' LIMIT $start, $limit";
}
?>


<main class="overflow-y-auto h-screen flex">
    <?php require_once ("../layout/sidebar.php") ?>
    <section class="w-full px-4 pb-8">
        <?php require_once ("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage Instructors</h1>
                </div>
                <div class="flex items-center gap-4 px-4">
                    <!-- Search bar -->
                    <form class="w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" autocomplete="off">
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
                            <input type="search" name="search-instructor" id="default-search"
                                class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Search name" value="<?= $hasSearch ? $search : '' ?>" required>
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
                    <a href="./create/instructor.php" class="btn bg-[#276bae] text-white">
                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>add_circle_fill</title>
                            <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                            </g>
                        </svg>
                        Create</a>
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
                        <tr class="hover cursor-default">
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-[#276bae] text-white text-center">Name</td>
                            <td class="bg-[#276bae] text-white text-center">Sex</td>
                            <td class="bg-[#276bae] text-white text-center">Contact</td>
                            <td class="bg-[#276bae] text-white text-center">Email</td>
                            <td class="bg-[#276bae] text-white text-center">Birthday</td>
                            <td class="bg-[#276bae] text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $instructors = $dbCon->query($query); ?>
                        <?php if ($instructors->num_rows == 0) { ?>
                        <tr class="hover">
                            <td colspan="6" class="text-center">No records found</td>
                        </tr>
                        <?php } else { ?>
                        <?php while ($instructor = $instructors->fetch_assoc()) { ?>
                        <tr class="hover">
                            <!-- <td><?= $instructor['id'] ?></td> -->
                            <td class="text-center"><?= $instructor['firstName'] ?> <?= $instructor['middleName'] ?>
                                <?= $instructor['lastName'] ?></td>
                            <td class="text-center"><?= ucfirst($instructor['gender']) ?></td>
                            <td class="text-center"><?= $instructor['contact'] ?></td>
                            <td class="text-center"><?= $instructor['email'] ?></td>
                            <td class="text-center"><?= $instructor['birthday'] ?></td>
                            <td>
                                <div class="flex gap-2 justify-center items-center">
                                    <label for="view-instructor-<?= $instructor['id'] ?>"
                                        class="bg-[#276bae] btn btn-sm text-white">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>eye_2_fill</title>
                                            <g id="eye_2_fill" fill='none' fill-rule='nonzero'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M12 5c3.679 0 8.162 2.417 9.73 5.901.146.328.27.71.27 1.099 0 .388-.123.771-.27 1.099C20.161 16.583 15.678 19 12 19c-3.679 0-8.162-2.417-9.73-5.901C2.124 12.77 2 12.389 2 12c0-.388.123-.771.27-1.099C3.839 7.417 8.322 5 12 5Zm0 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z' />
                                            </g>
                                        </svg>
                                        View</label>
                                    <label for="edit-instructor-<?= $instructor['id'] ?>"
                                        class="bg-gray-500 btn btn-sm text-white">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>edit_line</title>
                                            <g id="edit_line" fill='none' fill-rule='nonzero'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M13 3a1 1 0 0 1 .117 1.993L13 5H5v14h14v-8a1 1 0 0 1 1.993-.117L21 11v8a2 2 0 0 1-1.85 1.995L19 21H5a2 2 0 0 1-1.995-1.85L3 19V5a2 2 0 0 1 1.85-1.995L5 3h8Zm6.243.343a1 1 0 0 1 1.497 1.32l-.083.095-9.9 9.899a1 1 0 0 1-1.497-1.32l.083-.094 9.9-9.9Z' />
                                            </g>
                                        </svg>

                                        Edit</label>
                                    <label for="delete-instructor-<?= $instructor['id'] ?>"
                                        class="bg-red-500 btn btn-sm text-white">

                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>delete_2_fill</title>
                                            <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                            </g>
                                        </svg>
                                        Delete</label>
                                </div>
                            </td>
                        </tr>
                        <?php } ?>
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
    <?php $instructors = $dbCon->query($query); ?>
    <?php while ($row = $instructors->fetch_assoc()) { ?>

    <!-- View modal -->
    <input type="checkbox" id="view-instructor-<?= $row['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <div class="flex flex-col gap-4 px-[32px] mb-auto">

                <!-- Name -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">First Name</span>
                        <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" disabled
                            required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Middle Name</span>
                        <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>"
                            disabled />
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Last Name</span>
                        <input class="input input-bordered" name="lastname_name" value="<?= $row['lastName'] ?>"
                            disabled required />
                    </label>
                </div>

                <!-- Details -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Sex</span>
                        <select class="select select-bordered" name="gender" disabled required>
                            <option value="male" <?php if ($row['gender'] == 'male') { ?> disabled <?php } ?>>Male
                            </option>
                            <option value="female" <?php if ($row['gender'] == 'female') { ?> disabled <?php } ?>>Female
                            </option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-bold text-[18px]">Contact</span>
                        <input x-mask="9999-999-9999" type="tel" class="input input-bordered" name="contact"
                            placeholder="0912-345-6789" class="input input-bordered" value="<?= $row['contact'] ?>"
                            name="contact" disabled required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Birthdate</span>
                        <input class="input input-bordered" type="date" value="<?= $row['birthday'] ?? "2001-01-01" ?>"
                            name="birthday" disabled required />
                    </label>
                </div>



                <!-- Account -->
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Email</span>
                    <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" disabled
                        required />
                </label>
            </div>
        </div>
        <label class="modal-backdrop" for="view-instructor-<?= $row['id'] ?>">Close</label>
    </div>

    <!-- Edit modal -->
    <input type="checkbox" id="edit-instructor-<?= $row['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <form class="flex flex-col gap-4 px-[32px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

                <input type="hidden" name="id" value="<?= $row['id'] ?>" />

                <!-- Name -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">First Name</span>
                        <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>"
                            required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Middle Name</span>
                        <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" />
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Last Name</span>
                        <input class="input input-bordered" name="lastname_name" value="<?= $row['lastName'] ?>"
                            required />
                    </label>
                </div>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Sex</span>
                    <select class="select select-bordered" name="gender" required>
                        <option value="" selected disabled>Select Sex</option>
                        <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                        <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female
                        </option>
                    </select>
                </label>

                <!-- Details -->
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-bold text-[18px]">Contact</span>
                        <input x-mask="9999-999-9999" @input="enforcePrefix" type="tel" class="input input-bordered"
                            name="contact" placeholder="0912-345-6789" class="input input-bordered"
                            value="<?= $row['contact'] ?>" name="contact" required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Birthdate</span>
                        <input class="input input-bordered" type="date" value="<?= $row['birthday'] ?? "2000-01-01" ?>"
                            name="birthday" required />
                    </label>
                </div>



                <!-- Account -->
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Email</span>
                    <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>"
                        required />
                </label>

                <!-- <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2" x-data="{show: true}">
                            <span class="font-semibold text-base">New Password</span>
                            <div class="relative">
                                <input class="input input-bordered w-full" name="new-password" placeholder="New password" x-bind:type="show ? 'password' : 'text'" />
                                <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
                                    <i x-show="!show" class='bx bx-hide'></i>
                                    <i x-show="show" class='bx bx-show'></i>
                                </button>
                            </div>
                        </label>

                        <label class="flex flex-col gap-2" x-data="{show: true}">
                            <span class="font-semibold text-base">Confirm Password</span>
                            <div class="relative">
                                <input class="input input-bordered w-full" name="confirm-password" placeholder="Confirm password" x-bind:type="show ? 'password' : 'text'" />
                                <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
                                    <i x-show="!show" class='bx bx-hide'></i>
                                    <i x-show="show" class='bx bx-show'></i>
                                </button>
                            </div>
                        </label>
                    </div> -->

                <!-- Actions -->
                <div class="grid grid-cols-2 gap-4">
                    <label for="edit-instructor-<?= $row['id'] ?>" class="btn btn-error text-base">Cancel</label>
                    <button class="btn bg-[#276bae] text-white text-base" name="update_instructor">Update</button>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="edit-instructor-<?= $row['id'] ?>">Close</label>
    </div>

    <!-- Delete modal -->
    <input type="checkbox" id="delete-instructor-<?= $row['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <h3 class="text-lg font-bold text-error">Notice!</h3>
            <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information
                will permanently remove it from the system. Ensure that you have backed up any essential data before
                confirming.</p>

            <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <input type="hidden" name="id" value="<?= $row['id'] ?>" />

                <label class="btn" for="delete-instructor-<?= $row['id'] ?>">Close</label>
                <button class="btn btn-error" name="delete_instructor">Delete</button>
            </form>
        </div>
        <label class="modal-backdrop" for="delete-instructor-<?= $row['id'] ?>">Close</label>
    </div>
    <?php } ?>
</main>

<script>
function enforcePrefix(e) {
    let currentValue = e.target.value;

    if (!currentValue.startsWith("09")) {
        e.target.value = "09" + currentValue.substring(2);
    }

    console.log("HELO")
}
</script>