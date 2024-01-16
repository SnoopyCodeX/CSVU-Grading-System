<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
    exit();
}
    
// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Create new subject
if(isset($_POST['create_subject'])) {
    $course = $dbCon->real_escape_string($_POST['course']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $subjectName = $dbCon->real_escape_string($_POST['subject_name']);
    $units = $dbCon->real_escape_string($_POST['units']);
    $creditsUnits = $dbCon->real_escape_string($_POST['credits_units']);
    $term = $dbCon->real_escape_string($_POST['term']);

    $subjectNameExistQuery = $dbCon->query("SELECT * FROM ap_subjects WHERE name = '$subjectName'");

    if($subjectNameExistQuery->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject name already exists!";
    } else {
        $query = "INSERT INTO ap_subjects (course, year_level, name, units, credits_units, term) VALUES ('$course', '$yearLevel', '$subjectName', '$units', '$creditsUnits', '$term')";
        $result = mysqli_query($dbCon, $query);

        if($result) {
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
$courses = $dbCon->query("SELECT * FROM ap_courses");
?>

<main class="w-screen h-screen overflow-hidden flex" >
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center h-[70%]">
            <div class="flex justify-center items-center flex-col gap-4">
                <h2 class="text-[38px] font-bold mb-8">Create Subject</h2>
                <form class="flex flex-col gap-4  px-[32px]  w-[1000px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

                    <?php if($hasError)  { ?>
                        <div role="alert" class="alert alert-error mb-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span><?= $message ?></span>
                        </div>
                    <?php } ?>

                    <?php if($hasSuccess)  { ?>
                        <div role="alert" class="alert alert-success mb-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span><?= $message ?></span>
                        </div>
                    <?php } ?>
                    
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <select class="select select-bordered" name="course" required>
                            <option value="" disabled selected>Select Course</option>
                            <?php while($course = $courses->fetch_assoc()) { ?>
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
                    <div class="grid grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject Name</span>
                            <input class="input input-bordered" placeholder="Enter Subject Name" name="subject_name" required/>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Units</span>
                            <input class="input input-bordered"  placeholder="Enter Subject Units" name="units" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Credits Units</span>
                            <input class="input input-bordered"  placeholder="Enter Subject Credits" name="credits_units" required />
                        </label>

                        <label class="flex flex-col gap-2 col-span-3">
                            <span class="font-bold text-[18px]">Term</span>
                            <select class="select select-bordered" name="term">
                                <option value="" selected disabled>Select Term</option>
                                <option value="1st Sem">1st Sem</option>
                                <option value="2nd Sem">2nd Sem</option>
                                <option value="3rd Sem">3rd Sem</option>
                            </select>
                        </label>
                    </div>


                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-subjects.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success" name="create_subject">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>