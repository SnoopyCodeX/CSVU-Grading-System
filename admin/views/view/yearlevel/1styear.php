<?php
session_start();

require_once("../../../../configuration/config.php");
require_once("../../../../auth/controller/auth.controller.php");

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}
require_once("../../../../components/header.php");

$hasError = false;
$hasSuccess = false;
$message = "";

// Edit course
if (isset($_POST['edit_course'])) {
    $course = $dbCon->real_escape_string($_POST['course']);
    $courseCode = $dbCon->real_escape_string($_POST['course_code']);
    $id = $dbCon->real_escape_string($_POST['id']);

    $courseCodeExistQuery = $dbCon->query("SELECT * FROM ap_courses WHERE id = '$id'");

    if ($courseCodeExistQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Course does not exist!";
    } else {
        $query = "UPDATE ap_courses SET course = '$course', course_code = '$courseCode' WHERE id = '$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Course updated successfully!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Course update failed!";
        }
    }
}

// Delete course
if (isset($_POST['delete-course'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $courseCodeExistQuery = $dbCon->query("SELECT * FROM ap_courses WHERE id = '$id'");

    if ($courseCodeExistQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Course does not exist!";
    } else {
        $query = "DELETE FROM ap_courses WHERE id = '$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Course deleted successfully!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Course deletion failed!";
        }
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// count total pages
$courseCount = $dbCon->query("SELECT COUNT(*) AS count FROM ap_courses")->fetch_assoc();
$total = $courseCount['count'];
$pages = ceil($total / $limit);

// prefetch all courses
$courses = $dbCon->query("SELECT * FROM ap_courses LIMIT $start, $limit");
?>

<main class="h-screen overflow-hidden">
    <div class="h-screen grid md:grid md:grid-cols-[320px_auto] gap-4 border border-gray-400 w-full">
        <?php require_once("../../../layout/sidebar.php")  ?>
       <div class="overflow-y-scroll">
        <?php require_once("../../../layout/topbar.php") ?>
        <div class='py-4 flex justify-end pr-4'>
            <a class='btn' href="../../create/course.php">Create</a>
        </div>
       <div class=' overflow-hidden sm:pr-[48px] sm:grid sm:grid-cols-2 gap-4 md:grid-cols-2 lg:grid-cols-3 p-4 mt-8'>
           <a href="../students/1styear.php" class="">
           <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4'>
                <h1 class='text-[32px] font-semibold text-center cursor-pointer'>DSA - 101</h1>
                <span>32 students</span>
                <span>Mr John roy pogi123</span>
            </div>
           </a>
           <a href="../students/1styear.php" class="">
           <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4'>
                <h1 class='text-[32px] font-semibold text-center cursor-pointer'>DSA - 101</h1>
                <span>32 students</span>
                <span>Mr John roy pogi123</span>
            </div>
           </a>
           <a href="../students/1styear.php" class="">
           <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4'>
                <h1 class='text-[32px] font-semibold text-center cursor-pointer'>DSA - 101</h1>
                <span>32 students</span>
                <span>Mr John roy pogi123</span>
            </div>
           </a>
        
        </div>
       </div>
    </div>

</main>