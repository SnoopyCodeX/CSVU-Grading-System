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

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Create new subject
if (isset($_POST['create_subject'])) {
    $course = $dbCon->real_escape_string($_POST['course']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $subjectName = $dbCon->real_escape_string($_POST['subject_name']);
    $subjectCode = $dbCon->real_escape_string($_POST['subject_code']);
    $units = $dbCon->real_escape_string($_POST['units']);
    $creditsUnits = $dbCon->real_escape_string($_POST['credits_units']);
    $term = $dbCon->real_escape_string($_POST['term']);

    $subjectCodeExistQuery = $dbCon->query("SELECT * FROM subjects WHERE code = '$subjectCode' AND course='$course'");

    if ($subjectCodeExistQuery->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject code already exists!";
    } else if (!is_numeric($units) || intval($units) <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject units must be a numeric value and must be a positive integer greater than 0!";
    } else if (!is_numeric($creditsUnits) || intval($creditsUnits) <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject credit units must be a numeric value and must be a positive integer greater than 0!";
    } else {
        $query = "INSERT INTO subjects (course, year_level, name, code, units, credits_units, term) VALUES ('$course', '$yearLevel', '$subjectName', '$subjectCode', '$units', '$creditsUnits', '$term')";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Subject created successfully!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Subject creation failed!";
        }
    }
}

// Prefetch all courses
$courses = $dbCon->query("SELECT * FROM courses");

// Prefetch all instructors
$instructors = $dbCon->query("SELECT * FROM userdetails WHERE roles='instructor'");
?>

<main class="w-screen h-screen overflow-scroll overflow-scroll flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center md:w-[700px] mx-auto">
            <div class="flex justify-center items-center flex-col gap-4 w-full">
                <h2 class="text-[38px] font-bold mb-8">Create Subject</h2>
                <form class="flex flex-col gap-4 w-full" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

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

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <select class="select select-bordered" name="course" required>
                            <option value="" disabled selected>Select Course</option>
                            <?php while ($course = $courses->fetch_assoc()) { ?>
                                <option value="<?php echo $course['id'] ?>"><?php echo $course['course'] . " - #" . $course['course_code'] ?></option>
                            <?php } ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required>
                            <option value="" disabled selected>Select Year level</option>
                            <option value="1st year">1st year</option>
                            <option value="2nd year">2nd year</option>
                            <option value="3rd year">3rd year</option>
                            <option value="4th year">4th year</option>
                        </select>
                    </label>

                    <!-- Name -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject Name</span>
                            <input class="input input-bordered" placeholder="Enter Subject Name" name="subject_name" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Units</span>
                            <input class="input input-bordered" placeholder="Enter Subject Units" name="units" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Credits Units</span>
                            <input class="input input-bordered" placeholder="Enter Subject Credits" name="credits_units" required />
                        </label>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject Code</span>
                            <input class="input input-bordered" placeholder="Enter Subject Code" name="subject_code" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Term</span>
                            <select class="select select-bordered" name="term" required>
                                <option value="" selected disabled>Select Term</option>
                                <option value="1st Sem">1st Sem</option>
                                <option value="2nd Sem">2nd Sem</option>
                                <option value="Midyear">Midyear</option>
                            </select>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-subjects.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn bg-[#276bae] text-white" name="create_subject">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>