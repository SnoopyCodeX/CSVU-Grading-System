<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
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
    $school_year = $dbCon->real_escape_string($_POST['school_year']);
    $semester = strtolower($dbCon->real_escape_string($_POST['semester']));
    $school_year_copy = str_replace(" ", "", $school_year);
    $school_year_fragments = explode("-", $school_year_copy);

    if (count($school_year_fragments) != 2) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Invalid school year format. Please use the format 'YYYY - YYYY'";
    } else if ($school_year_fragments[0] >= $school_year_fragments[1]) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Invalid school year format. First year should be less than the second year. Please use the format 'YYYY - YYYY'";
    } else if (($school_year_fragments[1] - $school_year_fragments[0]) > 1) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Invalid school year format. Range should only be 1 year. Please use the format 'YYYY - YYYY'";
    } else if ($dbCon->query("SELECT * FROM school_year WHERE school_year = '$school_year' AND semester='$semester'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "School year with the same semester already exists";
    } else {
        $sql = "INSERT INTO school_year (school_year, semester, status) VALUES ('$school_year', '$semester', 'inactive')";
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

<main class="w-screen h-screen overflow-x-auto flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center h-[70%] w-full">
            <div class="flex justify-center items-center flex-col gap-4 w-full">
                <h2 class="text-[38px] font-bold mb-8">Create School year</h2>
                <form class="flex flex-col gap-4  px-[32px]  w-full mx-[80px]" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

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
                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-bold text-[18px]">School Year</span>
                        <input x-mask="9999 - 9999" placeholder="<?= date('Y') ?> - <?= date('Y', strtotime('+ 1 year')) ?>" name="school_year" class="input input-bordered" required>
                    </label>

                    <label class="flex flex-col gap-2 mb-2">
                        <span class="font-bold text-[18px]">Semester</span>
                        <select class="select select-bordered" name="semester" required>
                            <option disabled selected>Select an option</option>
                            <option value="1st Sem">1st Semester</option>
                            <option value="2nd Sem">2nd Semester</option>
                            <option value="Midyear">Midyear</option>
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