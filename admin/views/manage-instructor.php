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
$message = "";

// update instructor
if (isset($_POST['update_instructor'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $firstName = $dbCon->real_escape_string($_POST['first_name']);
    $middleName = $dbCon->real_escape_string($_POST['middle_name']);
    $lastName = $dbCon->real_escape_string($_POST['lastname_name']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = $dbCon->real_escape_string($_POST['contact']);
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $dbCon->real_escape_string($_POST['password']);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Invalid email address";
    } else {
        // check if id exists in ap_userdetails and has a role of 'instructor' query
        $checkIdQuery = "SELECT * FROM ap_userdetails WHERE id = '$id' AND roles = 'instructor'";
        $checkIdResult = $dbCon->query($checkIdQuery);

        if ($checkIdResult->num_rows <= 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Instructor does not exist";
        } else {
            $updateQuery = "UPDATE ap_userdetails SET 
                firstName = '$firstName',
                middleName = '$middleName',
                lastName = '$lastName',
                gender = '$gender',
                contact = '$contact',
                birthday = '$birthday',
                email = '$email'
            ";

            if ($password) {
                $updateQuery .= ", password = '" . crypt($password, '$6$Crypt$') . "' ";
            }

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

// delete instructor
if (isset($_POST['delete_instructor'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    // check if id exists in ap_userdetails and has a role of 'instructor' query
    $checkIdQuery = "SELECT * FROM ap_userdetails WHERE id = '$id' AND roles = 'instructor'";
    $checkIdResult = $dbCon->query($checkIdQuery);

    if ($checkIdResult->num_rows <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Instructor does not exist";
    } else {
        $deleteQuery = "DELETE FROM ap_userdetails WHERE id = '$id'";
        $result = $dbCon->query($deleteQuery);

        if ($result) {
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
$queryCount = "SELECT count(*) as total FROM ap_userdetails WHERE roles = 'instructor'";
$resultCount = $dbCon->query($queryCount);
$rowCount = $resultCount->fetch_assoc();
$total = $rowCount['total'];
$pages = ceil($total / $limit);

// get all instructors
$query = "SELECT * FROM ap_userdetails WHERE roles = 'instructor' LIMIT $start, $limit";
?>


<main class=" overflow-hidden h-screen flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Instructor</h1>
                </div>
                <a href="./create/instructor.php" class="btn">Create</a>
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
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Gender</td>
                            <td class="bg-slate-500 text-white">Contact</td>
                            <td class="bg-slate-500 text-white">Email</td>
                            <td class="bg-slate-500 text-white">Birthday</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $instructors = $dbCon->query($query); ?>
                        <?php while ($instructor = $instructors->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $instructor['id'] ?></td>
                                <td><?= $instructor['firstName'] ?> <?= $instructor['middleName'] ?> <?= $instructor['lastName'] ?></td>
                                <td><?= ucfirst($instructor['gender']) ?></td>
                                <td><?= $instructor['contact'] ?></td>
                                <td><?= $instructor['email'] ?></td>
                                <td><?= $instructor['birthday'] ?></td>
                                <td>
                                    <div class="flex gap-2">
                                        <label for="view-instructor-<?= $instructor['id'] ?>" class="bg-blue-400 btn btn-sm text-white">View</label>
                                        <label for="edit-instructor-<?= $instructor['id'] ?>" class="bg-gray-400 btn btn-sm text-white">Edit</label>
                                        <label for="delete-instructor-<?= $instructor['id'] ?>" class="bg-red-400 btn btn-sm text-white">Delete</label>
                                    </div>
                                </td>
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
                            <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" disabled required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" disabled />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="lastname_name" value="<?= $row['lastName'] ?>" disabled required />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Gender</span>
                            <select class="select select-bordered" name="gender" disabled required>
                                <option value="male" <?php if ($row['gender'] == 'male') { ?> disabled <?php } ?>>Male</option>
                                <option value="female" <?php if ($row['gender'] == 'female') { ?> disabled <?php } ?>>Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Contact</span>
                            <input class="input input-bordered" value="<?= $row['contact'] ?>" name="contact" disabled required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" value="<?= $row['birthday'] ?? "1900-01-01" ?>" name="birthday" disabled required />
                        </label>
                    </div>



                    <!-- Account -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Email</span>
                            <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" disabled required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Password</span>
                            <input class="input input-bordered" name="password" value="<?= $row['password'] ?>" disabled required />
                        </label>
                    </div>
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
                            <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="lastname_name" value="<?= $row['lastName'] ?>" required />
                        </label>
                    </div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Gender</span>
                        <select class="select select-bordered" name="gender" required>
                            <option value="" selected disabled>Select Gender</option>
                            <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                            <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                        </select>
                    </label>

                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Contact</span>
                            <input class="input input-bordered" value="<?= $row['contact'] ?>" name="contact" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" value="<?= $row['birthday'] ?? "1900-01-01" ?>" name="birthday" required />
                        </label>
                    </div>



                    <!-- Account -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Email</span>
                        <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Password</span>
                        <input class="input input-bordered" name="password" />
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <label for="edit-instructor-<?= $row['id'] ?>" class="btn btn-error text-base">Cancel</label>
                        <button class="btn btn-success text-base" name="update_instructor">Update</button>
                    </div>
                </form>
            </div>
            <label class="modal-backdrop" for="edit-instructor-<?= $row['id'] ?>">Close</label>
        </div>

        <!-- Delete modal -->
        <input type="checkbox" id="delete-instructor-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <h3 class="text-lg font-bold">Notice!</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

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