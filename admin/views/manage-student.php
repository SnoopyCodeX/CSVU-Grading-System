<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require('../../auth/controller/auth.controller.php');

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

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result1 = $dbCon->query("SELECT count(id) AS id FROM ap_userdetails WHERE roles='student'");
$students = $result1->fetch_all(MYSQLI_ASSOC);
$total = $students[0]['id'];
$pages = ceil($total / $limit);

// update student
if (isset($_POST['update_student'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $studentId = $dbCon->real_escape_string($_POST['student_id']);
    $firstName = $dbCon->real_escape_string($_POST['first_name']);
    $middleName = $dbCon->real_escape_string($_POST['middle_name']);
    $lastName = $dbCon->real_escape_string($_POST['last_name']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = $dbCon->real_escape_string($_POST['contact']);
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $dbCon->real_escape_string($_POST['password']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address";
    } else if ($dbCon->query("SELECT * FROM ap_userdetails WHERE id='$id' AND roles = 'student'")->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Student does not exist!";
    } else {
        // update student query 
        $query = "UPDATE ap_userdetails SET 
            sid='$studentId',
            firstName='$firstName',
            middleName='$middleName',
            lastName='$lastName',
            email='$email',
            gender='$gender',
            birthday='$birthday',
            contact='$contact',
            year_level='$yearLevel'
        ";

        if ($password) {
            $query .= ", password='" . crypt($password, '$6$Crypt$') . "'";
        }

        $query .= " WHERE id='$id'";

        $update = $dbCon->query($query);

        if ($update) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Successfully updated student!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Failed to update student!";
        }
    }
}

// delete student
if (isset($_POST['delete-student'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    if ($dbCon->query("SELECT * FROM ap_userdetails WHERE id='$id' AND roles = 'student'")->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Student does not exist!";
    } else {
        $query = "DELETE FROM ap_userdetails WHERE id='$id'";

        $delete = $dbCon->query($query);

        if ($delete) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Successfully deleted student!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Failed to delete student!";
        }
    }
}

// Prefetch all students query
$query = "SELECT * FROM ap_userdetails WHERE roles='student' LIMIT $start, $limit";
?>

<main class="w-screen overflow-x-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="h-screen w-full px-4">
        <?php require_once("../layout/topbar.php") ?>


        <div class="px-4 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Student</h1>
                </div>
                <a href="./create/student.php" class="btn">Create</a>
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
                            <th class="bg-slate-500 text-white">ID</th>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Email</td>
                            <td class="bg-slate-500 text-white">Gender</td>
                            <td class="bg-slate-500 text-white">Contact</td>
                            <td class="bg-slate-500 text-white">Student ID</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $dbCon->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "
                                    <tr>
                                        <td>{$row['id']}</td>
                                        <td>{$row['firstName']} {$row['middleName']} {$row['lastName']}</th>
                                        <td>{$row['email']}</td>
                                        <td>" . ucfirst($row['gender']) . "</td>
                                        <td>{$row['contact']}</td>
                                        <td>{$row['sid']}</td>
                                        <td>
                                            <div class='flex gap-2 justify-center items-center'>
                                                <label for='view-student-{$row['id']}' class='btn btn-sm bg-blue-400 text-white'>View</label>
                                                <label for='edit-student-{$row['id']}' class='btn btn-sm bg-gray-400 text-white'>Edit</label>
                                                <label for='delete-student-{$row['id']}' class='btn btn-sm bg-red-400 text-white'>Delete</label>
                                            </div>
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "
                                <tr>
                                    <td colspan='7'>No records found</td>
                                </tr>
                            ";
                        }

                        mysqli_free_result($result);
                        ?>
                        <tr>
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
    <?php $result = $dbCon->query($query); ?>
    <?php if ($result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>

            <!-- View modal -->
            <input type="checkbox" id="view-student-<?= $row['id'] ?>" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">
                    <div class="flex flex-col gap-4  px-[32px] mb-auto">

                        <!-- Student ID -->
                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Student ID</span>
                            <input class="input input-bordered" name="student_id" value="<?= $row['sid'] ?>" required disabled />
                        </label>

                        <!-- Name -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">First Name</span>
                                <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Middle Name</span>
                                <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" required disabled />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Last Name</span>
                                <input class="input input-bordered" name="last_name" value="<?= $row['lastName'] ?>" required disabled />
                            </label>
                        </div>

                        <!-- Details -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Gender</span>
                                <select class="select select-bordered" name="gender" required disabled>
                                    <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                    <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Contact</span>
                                <input class="input input-bordered" name="contact" value="<?= $row['contact'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Birthdate</span>
                                <input class="input input-bordered" type="date" name="birthday" value="<?= $row['birthday'] ?? "1900-01-01" ?>" required disabled />
                            </label>
                        </div>



                        <!-- Account -->
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Email</span>
                                <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Password</span>
                                <input class="input input-bordered" name="password" value="" required disabled />
                            </label>
                        </div>

                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Year level</span>
                            <select class="select select-bordered" name="year_level" required disabled>
                                <option value="1st year" <?php if ($row['year_level'] == '1st year') { ?> selected <?php } ?>>1st year</option>
                                <option value="2nd year" <?php if ($row['year_level'] == '2nd year') { ?> selected <?php } ?>>2nd year</option>
                                <option value="3rd year" <?php if ($row['year_level'] == '3rd year') { ?> selected <?php } ?>>3rd year</option>
                                <option value="4th year" <?php if ($row['year_level'] == '4th year') { ?> selected <?php } ?>>4th year</option>
                            </select>
                        </label>
                    </div>
                </div>
                <label class="modal-backdrop" for="view-student-<?= $row['id'] ?>">Close</label>
            </div>

            <!-- Edit modal -->
            <input type="checkbox" id="edit-student-<?= $row['id'] ?>" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">
                    <form class="flex flex-col gap-4  px-[32px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>" />

                        <!-- Student ID -->
                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Student ID</span>
                            <input class="input input-bordered" name="student_id" value="<?= $row['sid'] ?>" required />
                        </label>

                        <!-- Name -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">First Name</span>
                                <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" required />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Middle Name</span>
                                <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" required />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Last Name</span>
                                <input class="input input-bordered" name="last_name" value="<?= $row['lastName'] ?>" required />
                            </label>
                        </div>

                        <!-- Details -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Gender</span>
                                <select class="select select-bordered" name="gender" required>
                                    <option value="" selected disabled>Select Gender</option>
                                    <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                    <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Contact</span>
                                <input class="input input-bordered" name="contact" value="<?= $row['contact'] ?>" required />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Birthdate</span>
                                <input class="input input-bordered" type="date" name="birthday" value="<?= $row['birthday'] ?? "1900-01-01" ?>" required />
                            </label>
                        </div>



                        <!-- Account -->
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Email</span>
                                <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" required />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Password</span>
                                <input class="input input-bordered" name="password" />
                            </label>
                        </div>

                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Year level</span>
                            <select class="select select-bordered" name="year_level" required>
                                <option value="" selected disabled>Select year level</option>
                                <option value="1st year" <?php if ($row['year_level'] == '1st year') { ?> selected <?php } ?>>1st year</option>
                                <option value="2nd year" <?php if ($row['year_level'] == '2nd year') { ?> selected <?php } ?>>2nd year</option>
                                <option value="3rd year" <?php if ($row['year_level'] == '3rd year') { ?> selected <?php } ?>>3rd year</option>
                                <option value="4th year" <?php if ($row['year_level'] == '4th year') { ?> selected <?php } ?>>4th year</option>
                            </select>
                        </label>

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-4">
                            <label for="edit-student-<?= $row['id'] ?>" class="btn btn-error text-base">Cancel</label>
                            <button class="btn btn-success text-base" name="update_student">Update</button>
                        </div>
                    </form>
                </div>
                <label class="modal-backdrop" for="edit-student-<?= $row['id'] ?>">Close</label>
            </div>

            <!-- Delete modal -->
            <input type="checkbox" id="delete-student-<?= $row['id'] ?>" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">
                    <h3 class="text-lg font-bold">Notice!</h3>
                    <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                    <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>" />

                        <label class="btn" for="delete-student-<?= $row['id'] ?>">Cancel</label>
                        <button class="btn btn-error" name="delete-student">Delete</button>
                    </form>
                </div>
                <label class="modal-backdrop" for="delete-student-<?= $row['id'] ?>">Close</label>
            </div>

        <?php } ?>
        <?php mysqli_free_result($result); ?>
    <?php } ?>
</main>