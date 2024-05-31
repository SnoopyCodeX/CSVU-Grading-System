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

if (isset($_POST['search-admin'])) {
    $search = $dbCon->real_escape_string($_POST['search-admin']);
    $hasSearch = true;
}

// update admin in userdetails table
if (isset($_POST['update-admin'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $firstName = $dbCon->real_escape_string($_POST['firstName']);
    $middleName = $dbCon->real_escape_string($_POST['middleName']);
    $lastName = $dbCon->real_escape_string($_POST['lastName']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = str_replace("-", "", $dbCon->real_escape_string($_POST['contact']));
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address";
    } else if (!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address. It should end with <strong>@cvsu.edu.ph</strong>";
    } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
    } else if ($dbCon->query("SELECT * FROM userdetails WHERE email='$email' AND id != '$id' AND roles = 'admin'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "An admin with that email address already exists!";
    } else {
        $updateAdminQuery = "UPDATE userdetails SET
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
                $updateAdminQuery .= ", password='" . $newPasswordHashed . "'";
            }
        } */

        if (!$hasError) {
            $updateAdminQuery .= " WHERE id = '$id'";
            $updateAdminResult = $dbCon->query($updateAdminQuery);

            if ($updateAdminResult) {
                $hasError = false;
                $hasSuccess = true;
                $message = "Admin successfully updated!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Something went wrong. Please try again!";
            }
        }
    }
}

// delete admin in userdetails table
if (isset($_POST['delete-admin'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    // check if admin exists
    if ($dbCon->query("SELECT * FROM userdetails WHERE id = '$id' AND roles = 'admin'")->num_rows > 0) {
        $deleteAdminQuery = "DELETE FROM userdetails WHERE id = '$id'";
        $deleteAdminResult = $dbCon->query($deleteAdminQuery);

        if ($deleteAdminResult) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Admin successfully deleted!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong. Please try again!";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Admin does not exist!";
    }
}

// pagination 
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if ($hasSearch) {
    $result1 = $dbCon->query("SELECT count(*) AS id FROM userdetails WHERE roles = 'admin' AND (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%' OR contact LIKE '%$search%')");
} else {
    $result1 = $dbCon->query("SELECT count(*) AS id FROM userdetails WHERE roles = 'admin'");
}
$adminCount = $result1->fetch_all(MYSQLI_ASSOC);
$total = $adminCount[0]['id'];
$pages = ceil($total / $limit);

// query to get admin
if ($hasSearch) {
    $adminsQuery = "SELECT * FROM userdetails WHERE roles = 'admin' AND (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%' OR contact LIKE '%$search%') LIMIT $start, $limit";
} else {
    $adminsQuery = "SELECT * FROM userdetails WHERE roles = 'admin' LIMIT $start, $limit";
}
?>


<main class="overflow-x-auto flex">
    <?php require_once ("../layout/sidebar.php") ?>
    <section class="w-full px-4">
        <?php require_once ("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage Admins</h1>
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
                            <input type="search" name="search-admin" id="default-search"
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
                    <a href="./create/admin.php" class="btn bg-[#276bae] text-white"><i class="bx bx-plus-circle"></i> Create</a>
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
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 230px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-[#276bae] text-white text-center">Name</td>
                            <td class="bg-[#276bae] text-white text-center">Email</td>
                            <td class="bg-[#276bae] text-white text-center">Sex</td>
                            <td class="bg-[#276bae] text-white text-center">Contact</td>
                            <td class="bg-[#276bae] text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $adminsResult = $dbCon->query($adminsQuery); ?>
                        <?php if ($adminsResult->num_rows == 0) { ?>
                        <tr class="hover">
                            <td colspan="5" class="text-center">No records found</td>
                        </tr>
                        <?php } else { ?>
                        <?php while ($admin = $adminsResult->fetch_assoc()) { ?>
                        <tr class="hover">
                            <!-- <th class="font-normal"><?= $admin['id'] ?></th> -->
                            <td class="font-normal text-center"><?= $admin['firstName'] ?> <?= $admin['middleName'] ?>
                                <?= $admin['lastName'] ?></td>
                            <td class="font-normal text-center"><?= $admin['email'] ?></td>
                            <td class="font-normal text-center">
                                <?= ucfirst($admin['gender']) ?>
                            </td>
                            <td class="font-normal text-center"><?= $admin['contact'] ?></td>
                            <td>
                                <div class="flex gap-2 justify-center items-center">
                                    <label for="view-admin-<?= $admin['id'] ?>"
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
                                    <label for="edit-admin-<?= $admin['id'] ?>"
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
                                    <label for="delete-admin-<?= $admin['id'] ?>"
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

                <button class="btn bg-[#276bae] text-white type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>"
                    <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <?php $adminsResult = $dbCon->query($adminsQuery); ?>
    <?php while ($admin = $adminsResult->fetch_assoc()) { ?>

    <!-- View Admin Modal -->
    <input type="checkbox" id="view-admin-<?= $admin['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <form class="flex flex-col gap-4  px-[32px] mb-auto" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">

                <!-- Name -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[16px]">First Name</span>
                        <input class="input input-bordered" name="firstName" value="<?= $admin['firstName'] ?>" required
                            disabled />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[16px]">Middle Name</span>
                        <input class="input input-bordered" name="middleName" value="<?= $admin['middleName'] ?>"
                            disabled />
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[16px]">Last Name</span>
                        <input class="input input-bordered" name="lastName" value="<?= $admin['lastName'] ?>" required
                            disabled />
                    </label>
                </div>

                <!-- Details -->
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Sex</span>
                        <select class="select select-bordered" name="gender" required disabled>
                            <option value="" selected disabled>Select Sex</option>
                            <option value="male" <?php if ($admin['gender'] == 'male') { ?> selected <?php } ?>>Male
                            </option>
                            <option value="female" <?php if ($admin['gender'] == 'female') { ?> selected <?php } ?>>
                                Female
                            </option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Birthdate</span>
                        <input class="input input-bordered" type="date" name="birthday"
                            value="<?= $admin['birthday'] ?? "2001-01-01" ?>" required disabled />
                    </label>
                </div>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Contact</span>
                    <input class="input input-bordered" name="contact" value="<?= $admin['contact'] ?>" required
                        disabled />
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Email</span>
                    <input class="input input-bordered" type="email" name="email" value="<?= $admin['email'] ?>"
                        required disabled />
                </label>
            </form>
        </div>
        <label class="modal-backdrop" for="view-admin-<?= $admin['id'] ?>">Close</label>
    </div>

    <!-- Edit Admin Modal -->
    <input type="checkbox" id="edit-admin-<?= $admin['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <form class="flex flex-col gap-4  px-[32px] mb-auto" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                <input type="hidden" name="id" value="<?= $admin['id'] ?>" />

                <!-- Name -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">First Name</span>
                        <input class="input input-bordered" name="firstName" value="<?= $admin['firstName'] ?>"
                            required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Middle Name</span>
                        <input class="input input-bordered" name="middleName" value="<?= $admin['middleName'] ?>" />
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Last Name</span>
                        <input class="input input-bordered" name="lastName" value="<?= $admin['lastName'] ?>"
                            required />
                    </label>
                </div>

                <!-- Details -->
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Sex</span>
                        <select class="select select-bordered" name="gender" required>
                            <option value="" selected disabled>Select Sex</option>
                            <option value="male" <?php if ($admin['gender'] == 'male') { ?> selected <?php } ?>>Male
                            </option>
                            <option value="female" <?php if ($admin['gender'] == 'female') { ?> selected <?php } ?>>
                                Female
                            </option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Birthdate</span>
                        <input class="input input-bordered" type="date" name="birthday"
                            value="<?= $admin['birthday'] ?? "2000-01-01" ?>" required />
                    </label>
                </div>

                <label class="flex flex-col gap-2" x-data>
                    <span class="font-bold text-[18px]">Contact</span>
                    <input x-mask="9999-999-9999" @input="enforcePrefix" type="tel" class="input input-bordered"
                        name="contact" placeholder="0912-345-6789" value="<?= $admin['contact'] ?>" required />
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Email</span>
                    <input class="input input-bordered" type="email" name="email" value="<?= $admin['email'] ?>"
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
                    <label for="edit-admin-<?= $admin['id'] ?>" class="btn btn-error text-base">Cancel</label>
                    <button class="btn bg-[#276bae] text-white text-base" name="update-admin">Update</button>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="edit-admin-<?= $admin['id'] ?>">Close</label>
    </div>

    <!-- Delete Students Modal -->
    <input type="checkbox" id="delete-admin-<?= $admin['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <h3 class="text-lg font-bold text-error">Notice!</h3>
            <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information
                will permanently remove it from the system. Ensure that you have backed up any essential data before
                confirming.</p>

            <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <input type="hidden" name="id" value="<?= $admin['id'] ?>" />

                <label class="btn" for="delete-admin-<?= $admin['id'] ?>">Close</label>
                <button class="btn btn-error" name="delete-admin">Delete</button>
            </form>
        </div>
        <label class="modal-backdrop" for="delete-admin-<?= $admin['id'] ?>">Close</label>
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