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
    } else if(!str_ends_with($email, "@cvsu.edu.ph")) {
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
if($hasSearch) {
    $result1 = $dbCon->query("SELECT count(*) AS id FROM userdetails WHERE roles = 'admin' AND (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%' OR contact LIKE '%$search%')");
} else {
    $result1 = $dbCon->query("SELECT count(*) AS id FROM userdetails WHERE roles = 'admin'");
}
$adminCount = $result1->fetch_all(MYSQLI_ASSOC);
$total = $adminCount[0]['id'];
$pages = ceil($total / $limit);

// query to get admin
if($hasSearch) {
    $adminsQuery = "SELECT * FROM userdetails WHERE roles = 'admin' AND (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%' OR contact LIKE '%$search%') LIMIT $start, $limit";
} else {   
    $adminsQuery = "SELECT * FROM userdetails WHERE roles = 'admin' LIMIT $start, $limit";
}
?>


<main class="overflow-x-auto flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
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
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" name="search-admin" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search name" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Create button -->
                    <a href="./create/admin.php" class="btn btn-success"><i class="bx bx-plus-circle"></i> Create</a>
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
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 230px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-slate-500 text-white text-center">Name</td>
                            <td class="bg-slate-500 text-white text-center">Email</td>
                            <td class="bg-slate-500 text-white text-center">Sex</td>
                            <td class="bg-slate-500 text-white text-center">Contact</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $adminsResult = $dbCon->query($adminsQuery); ?>
                        <?php if ($adminsResult->num_rows == 0) { ?>
                            <tr>
                                <td colspan="5" class="text-center">No records found</td>
                            </tr>
                        <?php } else { ?>
                            <?php while ($admin = $adminsResult->fetch_assoc()) { ?>
                                <tr>
                                    <!-- <th class="font-normal"><?= $admin['id'] ?></th> -->
                                    <td class="font-normal text-center"><?= $admin['firstName'] ?> <?= $admin['middleName'] ?> <?= $admin['lastName'] ?></td>
                                    <th class="font-normal text-center"><?= $admin['email'] ?></th>
                                    <th class="font-normal text-center">
                                        <?= ucfirst($admin['gender']) ?>
                                    </th>
                                    <th class="font-normal text-center"><?= $admin['contact'] ?></th>
                                    <td>
                                        <div class="flex gap-2 justify-center items-center">
                                            <label for="view-admin-<?= $admin['id'] ?>" class="bg-blue-400 btn btn-sm text-white">View</label>
                                            <label for="edit-admin-<?= $admin['id'] ?>" class="bg-gray-400 btn btn-sm text-white">Edit</label>
                                            <label for="delete-admin-<?= $admin['id'] ?>" class="bg-red-400 btn btn-sm text-white">Delete</label>
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
                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn btn-sm" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
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
                            <input class="input input-bordered" name="firstName" value="<?= $admin['firstName'] ?>" required disabled />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[16px]">Middle Name</span>
                            <input class="input input-bordered" name="middleName" value="<?= $admin['middleName'] ?>" disabled />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[16px]">Last Name</span>
                            <input class="input input-bordered" name="lastName" value="<?= $admin['lastName'] ?>" required disabled />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Sex</span>
                            <select class="select select-bordered" name="gender" required disabled>
                                <option value="" selected disabled>Select Sex</option>
                                <option value="male" <?php if ($admin['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                <option value="female" <?php if ($admin['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="<?= $admin['birthday'] ?? "2001-01-01" ?>" required disabled />
                        </label>
                    </div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Contact</span>
                        <input class="input input-bordered" name="contact" value="<?= $admin['contact'] ?>" required disabled />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Email</span>
                        <input class="input input-bordered" type="email" name="email" value="<?= $admin['email'] ?>" required disabled />
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
                            <input class="input input-bordered" name="firstName" value="<?= $admin['firstName'] ?>" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middleName" value="<?= $admin['middleName'] ?>" />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="lastName" value="<?= $admin['lastName'] ?>" required />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Sex</span>
                            <select class="select select-bordered" name="gender" required>
                                <option value="" selected disabled>Select Sex</option>
                                <option value="male" <?php if ($admin['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                <option value="female" <?php if ($admin['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="<?= $admin['birthday'] ?? "2000-01-01" ?>" required />
                        </label>
                    </div>

                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-bold text-[18px]">Contact</span>
                        <input x-mask="9999-999-9999" @input="enforcePrefix" type="tel" class="input input-bordered" name="contact" placeholder="0912-345-6789" value="<?= $admin['contact'] ?>" required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Email</span>
                        <input class="input input-bordered" type="email" name="email" value="<?= $admin['email'] ?>" required />
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
                        <button class="btn btn-success text-base" name="update-admin">Update</button>
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
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

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