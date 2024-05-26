<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// fetch all courses
$coursesQuery = $dbCon->query("SELECT * FROM courses");
?>

<main class="overflow-x-auto flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-screen">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Select Course</h1>
                </div>

                <div class="flex gap-4 px-4">
                </div>
            </div>

            <div class='overflow-auto sm:pr-[48px] sm:grid sm:grid-cols-2 gap-4 md:grid-cols-2 lg:grid-cols-3 p-4 mt-8'>
                <?php if($coursesQuery->num_rows > 0): ?>
                    <?php while($course = $coursesQuery->fetch_assoc()): ?>
                        <a href="./view/course.php?id=<?= $course['id'] ?>" class="">
                            <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4'>
                                <h1 class='text-[32px] font-semibold text-center cursor-pointer'><?= $course['course'] ?></h1>
                                <span class="text-[24px]"><?= $course['course_code'] ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4'>
                        <h1 class='text-[32px] font-semibold text-center cursor-pointer'>No courses found</h1>
                        <span><a href="./manage-course.php" class="btn btn-primary"><i class="bx bx-plus"></i> Add Course</a></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>