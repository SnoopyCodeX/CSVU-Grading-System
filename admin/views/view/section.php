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

// Get id from url
$id = $dbCon->real_escape_string($_GET['id']) ? $dbCon->real_escape_string($_GET['id']) : header("Location: ../manage-sections.php");

// Fetch section details query joining userdetails, sections, subjects, schoolyear and courses tables
$sectionQuery = "SELECT 
    sections.id, 
    sections.name AS sectionName,
    sections.term AS term,
    sections.year_level AS yearLevel,
    subjects.name AS subjectName, 
    school_year.school_year AS schoolYear, 
    courses.course AS courseName,
    courses.course_code AS courseCode,
    CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS instructorName
    FROM sections 
    INNER JOIN subjects ON sections.subject = subjects.id 
    INNER JOIN school_year ON sections.school_year = school_year.id 
    INNER JOIN courses ON sections.course = courses.id
    INNER JOIN userdetails ON sections.instructor = userdetails.id
    WHERE sections.id = $id";

// Fetch all students query joining userdetails and section_students tables
$studentsQuery = "SELECT
    section_students.id,
    section_students.student_id AS studentId,
    CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS studentName
    FROM
    section_students
    INNER JOIN userdetails ON section_students.student_id = userdetails.id
    WHERE section_students.section_id = $id
";

// Prefetch section query
$sectionResult = $dbCon->query($sectionQuery);

// If section does not exist, redirect to manage-sections.php
if ($sectionResult->num_rows === 0) {
    header("Location: ../manage-sections.php");
    exit();
}

// Prefetch section result
$sectionResult = $sectionResult->fetch_assoc();

// Prefetch all students query
$studentsResult = $dbCon->query($studentsQuery);
?>

<main class="w-screen h-screen overflow-x-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-full">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full">
            <div class="flex justify-center items-center flex-col p-8">
                <h2 class="text-[38px] font-bold mb-4">View Section</h2>
                <form class="flex flex-col gap-[24px]  px-[32px]  w-[1000px] mb-auto flex">

                    <!-- Details -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Section Name</span>
                        <input class="input input-bordered" value="<?= $sectionResult['sectionName'] ?>" disabled />
                    </label>

                    <!-- Main Grid -->
                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject</span>
                            <select class="select select-bordered" disabled>
                                <!--Display all the subjects here-->
                                <option value="<?= $sectionResult['subjectName'] ?>" selected><?= $sectionResult['subjectName'] ?></option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Year</span>
                            <select class="select select-bordered" disabled>
                                <!--Display all the School Year here-->
                                <option value="<?= $sectionResult['schoolYear'] ?>" selected><?= $sectionResult['schoolYear'] ?></option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Term</span>
                            <select class="select select-bordered" disabled>
                                <!--Display all the Semister here-->
                                <option value="<?= $sectionResult['term'] ?>" selected><?= $sectionResult['term'] ?></option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" disabled>
                                <!--Display all the Year here-->
                                <option value="<?= $sectionResult['yearLevel'] ?>" selected><?= $sectionResult['yearLevel'] ?></option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Course</span>
                            <select class="select select-bordered" disabled>
                                <!--Display all the Course here-->
                                <option value="<?= $sectionResult['courseName'] ?>" selected><?= $sectionResult['courseName'] ?></option>
                            </select>
                        </label>
                    </div>


                    <!-- Student Selections -->
                    <div class="divider">People</div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Instructor</span>
                        <select class="select select-bordered" disabled>
                            <!--Display all the subjects here-->
                            <option value="<?= $sectionResult['instructorName'] ?>"><?= $sectionResult['instructorName'] ?></option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[18px]">Students</span>

                            <label class="flex flex-col gap-2">
                                <select class="select select-bordered select-sm" disabled>
                                    <!--Display all the Year level here-->
                                    <option value="" selected>Select Year level</option>
                                </select>
                            </label>
                        </div>


                        <div class="border border-black rounded-[5px] w-full h-[300px] grid grid-cols-3 gap-4 p-4 overflow-y-scroll ">

                            <!-- Students -->
                            <?php while ($student = $studentsResult->fetch_assoc()) { ?>
                                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                                    <input type="checkbox" class="checkbox checkbox-sm" checked disabled />
                                    <span><?= $student['studentName'] ?></span>
                                </div>
                            <?php } ?>

                        </div>
                    </label>
                </form>
            </div>
        </div>
    </section>
</main>