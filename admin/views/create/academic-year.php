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

// error and success message handling //
$hasError = false;
$hasSuccess = false;
$message = "";

// create new school year
if (isset($_POST['create_school_year'])) {
    $school_year = $_POST['school_year'];

    if ($dbCon->query("SELECT * FROM ap_school_year WHERE school_year = '$school_year'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "School year already exists";
    } else {
        $sql = "INSERT INTO ap_school_year (school_year) VALUES ('$school_year')";
        $result = mysqli_query($dbCon, $sql);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "School year created successfully";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Error creating school year";
        }
    }
}
?>

<main class="w-screen h-screen overflow-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center h-[70%] w-full">
            <div class="flex justify-center items-center flex-col gap-4 w-full">
                <h2 class="text-[38px] font-bold mb-8">Create School year</h2>
                <form class="flex flex-col gap-4  px-[32px]  w-full" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

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
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">School Year</span>
                        <select class="select select-bordered" name="school_year" required>
                            <option disabled="disabled" selected="selected">Select an option</option>
                            // school year options 2022 - 2023 using item //
                            <?php
                            $earlyYear = 2022;
                            $lateYear = 2030;
                            for ($i = $earlyYear; $i <= $lateYear; $i++) {
                                echo "<option value='$i - " . ($i + 1) . "'>$i - " . ($i + 1) . "</option>";
                            }
                            ?>
                        </select>
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a class="btn text-base btn-error" href="../manage-schoolyear.php">Cancel</a>
                        <button class="btn text-base btn-success" name="create_school_year">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>