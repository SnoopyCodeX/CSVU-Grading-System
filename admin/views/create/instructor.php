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

// create instructor
if (isset($_POST['create_instructor'])) {
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
        // check if email already exists in ap_userdetails table
        $checkEmailQuery = "SELECT * FROM ap_userdetails WHERE email = '$email'";
        $checkEmailResult = $dbCon->query($checkEmailQuery);

        if ($checkEmailResult->num_rows > 0) {
            $hasError = true;
            $hhasSuccess = false;
            $message = "Email already exists";
        } else {
            // insert to ap_userdetails table
            $insertQuery = "INSERT INTO ap_userdetails (firstName, middleName, lastName, gender, contact, birthday, email, password, roles) VALUES (
                '$firstName', 
                '$middleName', 
                '$lastName', 
                '$gender', 
                '$contact', 
                '$birthday', 
                '$email', 
                '" . crypt($password, '$6$Crypt$') . "', 
                'instructor'
            )";

            $result = $dbCon->query($insertQuery);

            if ($result) {
                $hasError = false;
                $hasSuccess = true;
                $message = "Instructor successfully created!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Error creating instructor";
            }
        }
    }
}
?>

<main class="w-screen h-screen overflow-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class=" w-full px-4 lg:w-[700px] mx-auto">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center h-[70%]">
            <div class="flex justify-center items-center flex-col gap-4">
                <h2 class="text-[38px] font-bold mb-8">Create Instructor</h2>
                <form class="flex flex-col gap-4  mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

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

                    <!-- Name -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">First Name</span>
                            <input class="input input-bordered" name="first_name" placeholder='Enter first name' required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middle_name"  placeholder='Enter middle name' />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="lastname_name"  placeholder='Enter last name' required />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Gender</span>
                            <select class="select select-bordered" name="gender"  required>
                                <option value="" selected disabled>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Contact</span>
                            <input class="input input-bordered" name="contact"  placeholder='Enter Contact'  required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" value="1900-01-01" name="birthday" required />
                        </label>
                    </div>



                    <!-- Account -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Email</span>
                            <input class="input input-bordered" type="email"  placeholder='Email Password'  name="email" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Password</span>
                            <input class="input input-bordered" placeholder='Enter Password'  name="password" required />
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-instructor.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success text-base" name="create_instructor">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>