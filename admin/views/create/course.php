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

if (isset($_POST['create_course'])) {
    $course = $dbCon->real_escape_string($_POST['course']);
    $courseCode = $dbCon->real_escape_string($_POST['course_code']);
    $adviser = $dbCon->real_escape_string($_POST['adviser']);

    if(strlen(trim($course)) < 6) {
        $hasError = true;
        $message = "Course name must be 6 characters long";
    } else if (empty($adviser)) {
        $hasError = true;
        $message = "Please select a course adviser!";
    } else {
        $courseCodeExistQuery = $dbCon->query("SELECT * FROM courses WHERE course_code = '$courseCode'");

        if ($courseCodeExistQuery->num_rows > 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Course code already exists!";
        } else {
            $query = "INSERT INTO courses (course, course_code, adviser) VALUES ('$course', '$courseCode', '$adviser')";
            $result = mysqli_query($dbCon, $query);

            if ($result) {
                $hasError = false;
                $hasSuccess = true;
                $message = "Course created successfully!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Course creation failed!";
            }
        }
    }
}

// $instructorsQuery = "SELECT 
//     *, 
//     CONCAT(firstName, ' ', middleName, ' ', lastName) as fullName 
//     FROM userdetails 
//     WHERE roles='instructor' 
//     AND id NOT IN (SELECT adviser FROM courses) 
//     ORDER BY fullName ASC
// ";

$instructorsQuery = "SELECT 
    *, 
    CONCAT(firstName, ' ', middleName, ' ', lastName) as fullName 
    FROM userdetails 
    WHERE roles='instructor' 
    ORDER BY fullName ASC
";
$instructorsQueryResult = $dbCon->query($instructorsQuery);
?>

<main class="w-screen h-screen overflow-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center w-full h-[70%]">
            <div class="w-full flex justify-center items-center flex-col gap-4">
                <h2 class="text-[28px] md:text-[38px] font-bold mb-8">Create Course</h2>

                <form class="flex flex-col gap-4  px-auto md:px-[32px] w-full mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
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
                        <span class="font-bold text-[18px]">Course</span>
                        <input type="text" class="border border-gray-400 input input-bordered" placeholder="Course Name" name="course" <?php if($instructorsQueryResult->num_rows == 0): ?> disabled <?php endif; ?>>
                    </label>

                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-bold text-[18px]">Course Code</span>
                        <input type="text" class="border border-gray-400 input input-bordered" placeholder="Course Code" name="course_code" x-mask="************" <?php if($instructorsQueryResult->num_rows == 0): ?> disabled <?php endif; ?>>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Assign Adviser</span>
                        <?php if($instructorsQueryResult->num_rows > 0): ?>
                            <select class="select select-bordered" name="adviser" required>
                                <option value="" disabled selected>Select Adviser</option>
                                <?php while ($instructor = $instructorsQueryResult->fetch_assoc()) { ?>
                                    <option value="<?php echo $instructor['id'] ?>"><?= $instructor['fullName'] ?></option>
                                <?php } ?>
                            </select>
                        <?php else: ?>
                            <div role="alert" class="alert alert-error mb-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="flex space-between items-center gap-4">
                                    <span>No advisers available.</span> 
                                    <a class="btn" href="../manage-instructor.php">
                                        <span class="bx bx-plus"></span> 
                                        Add Instructor
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a class="btn btn-error text-base" href="../manage-course.php">Cancel</a>
                        <button class="btn btn-success text-base" name="create_course">Create</button>
                    </div>
                </form>
            </div>
        </div>

</main>