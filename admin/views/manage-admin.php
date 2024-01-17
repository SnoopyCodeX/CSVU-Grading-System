<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//

require_once("../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// update admin in ap_userdetails table
if (isset($_POST['update-admin'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $firstName = $dbCon->real_escape_string($_POST['firstName']);
    $middleName = $dbCon->real_escape_string($_POST['middleName']);
    $lastName = $dbCon->real_escape_string($_POST['lastName']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = $dbCon->real_escape_string($_POST['contact']);
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $dbCon->real_escape_string($_POST['password']);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address";
    } else if ($dbCon->query("SELECT * FROM ap_userdetails WHERE email='$email' AND id != '$id' AND roles = 'admin'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "An admin with that email address already exists!";
    } else {
        $updateAdminQuery = "UPDATE ap_userdetails SET
            firstName = '$firstName',
            middleName = '$middleName',
            lastName = '$lastName', 
            gender = '$gender',
            contact = '$contact',
            birthday = '$birthday',
            email = '$email'
        ";

        if ($password) {
            $updateAdminQuery .= ", password = '" . crypt($password, '$6$Crypt$') . "'";
        }

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

// delete admin in ap_userdetails table
if (isset($_POST['delete-admin'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    // check if admin exists
    if ($dbCon->query("SELECT * FROM ap_userdetails WHERE id = '$id' AND roles = 'admin'")->num_rows > 0) {
        $deleteAdminQuery = "DELETE FROM ap_userdetails WHERE id = '$id'";
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
$result1 = $dbCon->query("SELECT count(*) AS id FROM ap_userdetails WHERE roles = 'admin'");
$adminCount = $result1->fetch_all(MYSQLI_ASSOC);
$total = $adminCount[0]['id'];
$pages = ceil($total / $limit);

// query to get admin
$adminsQuery = "SELECT * FROM ap_userdetails WHERE roles = 'admin' LIMIT $start, $limit";
?>


<main class="overflow-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Admin</h1>
                </div>
                <a href="./create/admin.php" class="btn">Create</a>
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
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 230px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Email</td>
                            <td class="bg-slate-500 text-white">Gender</td>
                            <td class="bg-slate-500 text-white">Contact</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $adminsResult = $dbCon->query($adminsQuery); ?>
                        <?php while ($admin = $adminsResult->fetch_assoc()) { ?>
                            <tr>
                                <th class="font-normal"><?= $admin['id'] ?></th>
                                <th class="font-normal"><?= $admin['firstName'] ?> <?= $admin['middleName'] ?> <?= $admin['lastName'] ?></th>
                                <th class="font-normal"><?= $admin['email'] ?></th>
                                <th class="font-normal">
                                    <div class="badge p-3 bg-blue-200 text-black">
                                        <?= ucfirst($admin['gender']) ?>
                                    </div>
                                </th>
                                <th class="font-normal"><?= $admin['contact'] ?></th>
                                <td>
                                    <div class="flex gap-2">
                                        <label for="view-admin-<?= $admin['id'] ?>" class="bg-blue-400 btn btn-sm text-white">View</label>
                                        <label for="edit-admin-<?= $admin['id'] ?>" class="bg-gray-400 btn btn-sm text-white">Edit</label>
                                        <label for="delete-admin-<?= $admin['id'] ?>" class="bg-red-400 btn btn-sm text-white">Delete</label>
                                    </div>
                                </td>
                            </tr>
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

                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 >= $pages) { ?> disabled <?php } ?>>
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
                    <div class="grid grid-cols-2 gap-4">
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
                            <span class="font-bold text-[18px]">Gender</span>
                            <select class="select select-bordered" name="gender" required disabled>
                                <option value="" selected disabled>Select Gender</option>
                                <option value="male" <?php if ($admin['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                <option value="female" <?php if ($admin['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="<?= $admin['birthday'] ?? "1990-01-01" ?>" required disabled />
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

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Password</span>
                        <input class="input input-bordered" name="password" value="<?= $admin['password'] ?>" required disabled />
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
                    <div class="grid grid-cols-2 gap-4">
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
                            <span class="font-bold text-[18px]">Gender</span>
                            <select class="select select-bordered" name="gender" required>
                                <option value="" selected disabled>Select Gender</option>
                                <option value="male" <?php if ($admin['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                <option value="female" <?php if ($admin['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="<?= $admin['birthday'] ?? "1990-01-01" ?>" required />
                        </label>
                    </div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Contact</span>
                        <input class="input input-bordered" name="contact" value="<?= $admin['contact'] ?>" required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Email</span>
                        <input class="input input-bordered" type="email" name="email" value="<?= $admin['email'] ?>" required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Password</span>
                        <input class="input input-bordered" name="password" value="" />
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-admin.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success text-base" name="update-admin">Update</button>
                    </div>
                </form>
            </div>
            <label class="modal-backdrop" for="edit-admin-<?= $admin['id'] ?>">Close</label>
        </div>

        <!-- Delete Students Modal -->
        <input type="checkbox" id="delete-admin-<?= $admin['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <h3 class="text-lg font-bold">Notice!</h3>
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