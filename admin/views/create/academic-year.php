<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../../configuration/config.php");
require ('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../../components/header.php");

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
    <?php require_once ("../../layout/sidebar.php") ?>
    <section class="border w-full px-4">
        <?php require_once ("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center  h-[70%] w-full">
            <div class="flex justify-center items-center flex-col gap-4 max-w-[600px]">
                <h2 class="text-[38px] font-bold mb-8">Create School year</h2>
                <form class="flex flex-col gap-4  px-[32px]  w-full mx-[80px]" method="post"
                    action="<?= $_SERVER['PHP_SELF'] ?>">

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

                    <!-- Name -->
                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-bold text-[18px]">School Year</span>
                        <input x-mask="9999 - 9999"
                            placeholder="<?= date('Y') ?> - <?= date('Y', strtotime('+ 1 year')) ?>" name="school_year"
                            class="input input-bordered" required>
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
                        <a class="btn text-base bg-red-500 text-white" href="../manage-schoolyear.php">
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                <title>delete_2_fill</title>
                                <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                    <path
                                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                    <path fill='currentColor'
                                        d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                </g>
                            </svg>
                            <span>
                                Cancel
                            </span>
                        </a>
                        <button class="btn text-base bg-[#276bae] text-white" name="create_school_year">
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                <title>add_circle_fill</title>
                                <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                    <path
                                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                    <path fill='currentColor'
                                        d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                                </g>
                            </svg>

                            Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>