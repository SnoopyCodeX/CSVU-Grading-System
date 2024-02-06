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

// Create new admin
if (isset($_POST['create-admin'])) {
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
    } else if(!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address. It should end with <strong>@cvsu.edu.ph</strong>";
    } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
    } else if ($dbCon->query("SELECT * FROM ap_userdetails WHERE email = '$email'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "A user with that email address already exists!";
    } else {
        $insertAdminQuery = "INSERT INTO ap_userdetails(firstName, middleName, lastName, gender, contact,  birthday, email, password, roles) VALUES(
            '$firstName',
            '$middleName',
            '$lastName',
            '$gender',
            '$contact',
            '$birthday',
            '$email',
            '" . crypt($password, '$6$Crypt$') . "',
            'admin'
        )";

        $insertAdminResult = $dbCon->query($insertAdminQuery);

        if ($insertAdminResult) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Admin successfully created!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong. Please try again later.";
        }
    }
}
?>

<main class="w-screen h-screen overflow-scroll flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center md:w-[700px] mx-auto">
            <div class="flex justify-center items-center flex-col w-full gap-4">
                <h2 class="text-[38px] font-bold mb-8">Create Admin</h2>
                <form class="flex flex-col gap-4  px-[32px]  w-full mb-auto" action="<?= $_SERVER['PHP_SELF'] ?>" method="post">

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
                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">First Name</span>
                            <input class="input input-bordered" name="firstName" placeholder="Enter First name" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middleName" placeholder="Enter Middle name" />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="lastName" required placeholder="Enter Last name" />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid md:grid-cols-3 gap-4">
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
                            <input typ="tel" class="input input-bordered" name="contact" placeholder="Enter Contact name" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="1900-01-01" required />
                        </label>
                    </div>



                    <!-- Account -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Email</span>
                            <input type="email" placeholder="Enter email" class="input input-bordered w-full" type="email" name="email" required />
                        </label>

                        <label class="flex flex-col gap-2" x-data="{show: true}">
                            <span class="font-bold text-[18px]">Password</span>
                            <div class="relative">
                                <input type="password" placeholder="Enter Password" class="input input-bordered w-full" name="password" x-bind:type="show ? 'password' : 'text'" required />
                                <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
                                    <i x-show="!show" class='bx bx-hide'></i>
                                    <i x-show="show" class='bx bx-show'></i>
                                </button>
                            </div>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-admin.php" class="btn btn-error text-lg text-semibold text-white">Cancel</a>
                        <button class="btn btn-success text-lg text-semibold text-white" name="create-admin">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>