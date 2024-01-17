<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Create new student
if (isset($_POST['create_student'])) {
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
    } else if ($dbCon->query("SELECT * FROM ap_userdetails WHERE sid = '$studentId' OR email = '$email' AND roles = 'student'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "A student with the same Student ID or email address already exists!";
    } else {
        $query = "INSERT INTO ap_userdetails(firstName, middleName, lastName, email, password, gender, contact,  birthday, year_level, roles, sid) VALUES(
            '$firstName',
            '$middleName',
            '$lastName',
            '$email',
            '" . crypt($password, '$6$Crypt$') . "',
            '$gender',
            '$contact',
            '$birthday',
            '$yearLevel',
            'student',
            '$studentId'
        )";
        $result = $dbCon->query($query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Successfully added a new student!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Failed to add a new student!";
        }
    }
}
?>

<main class="w-screen h-screen overflow-x-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center">
            <div class="flex justify-center items-center flex-col gap-4">
                <h2 class="text-[38px] font-bold">Create Student</h2>
                <form class="flex flex-col gap-4  px-[32px]  w-[1000px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

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

                    <!-- Student ID -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Student ID</span>
                        <input class="input input-bordered" name="student_id" placeholder="Enter Student ID" required />
                    </label>

                    <!-- Name -->
                    <div class="grid grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">First Name</span>
                            <input class="input input-bordered" name="first_name" placeholder="Enter First name" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middle_name" placeholder="Enter Middle Name" required />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="last_name" placeholder="Enter Last name" required />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Gender</span>
                            <select class="select select-bordered" name="gender" required>
                                <option value="" selected disabled>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Contact</span>
                            <input class="input input-bordered" name="contact" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="1900-01-01" required />
                        </label>
                    </div>



                    <!-- Account -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Email</span>
                            <input type="email" placeholder="Enter email" class="input input-bordered" type="email" name="email" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Password</span>
                            <input type="password" placeholder="Enter Password" class="input input-bordered" name="password" required />
                        </label>
                    </div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required>
                            <option value="" selected disabled>Select year level</option>
                            <option value="1st year">1st year</option>
                            <option value="2nd year">2nd year</option>
                            <option value="3rd year">3rd year</option>
                            <option value="4th year">4th year</option>
                        </select>
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-student.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success text-base" name="create_student">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>