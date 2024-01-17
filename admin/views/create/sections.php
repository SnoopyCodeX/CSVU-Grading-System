<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

// check if request is an ajax request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // fetch students
    if (isset($_GET['year_level'])) {
        $yearLevel = $dbCon->real_escape_string($_GET['year_level']);

        $query = "SELECT * FROM ap_userdetails WHERE year_level='$yearLevel' AND roles='student'";
        $result = $dbCon->query($query);

        if ($result->num_rows > 0) {
            $students = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($students);
        } else {
            echo json_encode([]);
        }
    }

    exit();
}

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

// Create section
if (isset($_POST['create_section'])) {
    $sectionName = $dbCon->real_escape_string($_POST['section_name']);
    $subject = $dbCon->real_escape_string($_POST['subject']);
    $schoolYear = $dbCon->real_escape_string($_POST['school_year']);
    $term = $dbCon->real_escape_string($_POST['term']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $instructor = $dbCon->real_escape_string($_POST['instructor']);
    $students = json_decode($_POST['students']);

    // Check if section name already exists
    $checkQuery = "SELECT * FROM ap_sections WHERE name='$sectionName'";
    $checkResult = $dbCon->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        $hasError = true;
        $message = "Section name already exists";
    } else {
        // Create section
        $query = "INSERT INTO ap_sections (name, subject, school_year, term, year_level, course, instructor) VALUES ('$sectionName', '$subject', '$schoolYear', '$term', '$yearLevel', '$course', '$instructor')";
        $result = $dbCon->query($query);

        if ($result) {
            $sectionId = $dbCon->insert_id;

            // Create section students
            foreach ($students as $student) {
                $query = "INSERT INTO ap_section_students (section_id, student_id) VALUES ('$sectionId', '$student')";
                $result = $dbCon->query($query);
            }

            $hasSuccess = true;
            $message = "Section created successfully";
        } else {
            $hasError = true;
            $message = "Failed to create section";
        }
    }
}

// Prefetch all students query
$studentsQuery = "SELECT * FROM ap_userdetails WHERE roles='student'";

// Prefetch all instructors query
$instructorsQuery = "SELECT * FROM ap_userdetails WHERE roles='instructor'";

// Prefetch all subjects query
$subjectsQuery = "SELECT * FROM ap_subjects";

// Prefetch all school year query
$schoolYearQuery = "SELECT * FROM ap_school_year";

// Prefetch all course query
$courseQuery = "SELECT * FROM ap_courses";
?>

<main class="w-screen h-screen overflow-x-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-full">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full">
            <div class="flex justify-center items-center flex-col p-8">
                <h2 class="text-[38px] font-bold mb-4">Create Sections</h2>

                <form class="flex flex-col gap-[24px]  px-[32px]  w-[1000px] mb-auto flex" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" id="create-section-form">

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

                    <!-- Details -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Section Name</span>
                        <input class="input input-bordered" name="section_name" placeholder="Enter section name" required />
                    </label>


                    <!-- Main Grid -->
                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject</span>
                            <select class="select select-bordered" name="subject" required>
                                <!--Display all the subjects here-->
                                <option value="" selected disabled>Select Subject</option>

                                <?php $subjects = $dbCon->query($subjectsQuery); ?>
                                <?php while ($subject = $subjects->fetch_assoc()) { ?>
                                    <option value="<?php echo $subject['id'] ?>"><?php echo $subject['name'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Year</span>
                            <select class="select select-bordered" name="school_year" required>
                                <!--Display all the School Year here-->
                                <option value="" selected disabled>Select school year</option>

                                <?php $schoolYears = $dbCon->query($schoolYearQuery); ?>
                                <?php while ($schoolYear = $schoolYears->fetch_assoc()) { ?>
                                    <option value="<?php echo $schoolYear['id'] ?>"><?php echo $schoolYear['school_year'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Term</span>
                            <select class="select select-bordered" name="term" required>
                                <!--Display all the Semister here-->
                                <option value="" selected disabled>Select Semester</option>

                                <option value="1st Sem">First Semester</option>
                                <option value="2nd Sem">Second Semester</option>
                                <option value="3rd Sem">Third Semester</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" name="year_level" required>
                                <!--Display all the Year here-->
                                <option value="" selected disabled>Select year level</option>

                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </label>

                        <label class="flex flex-col col-span-2 gap-2">
                            <span class="font-bold text-[18px]">Course</span>
                            <select class="select select-bordered" name="course" required>
                                <!--Display all the Course here-->
                                <option value="" selected disabled>Select Course</option>

                                <?php $courses = $dbCon->query($courseQuery); ?>
                                <?php while ($course = $courses->fetch_assoc()) { ?>
                                    <option value="<?php echo $course['id'] ?>"><?php echo $course['course'] ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    </div>


                    <!-- Student Selections -->
                    <div class="divider">People</div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Instructor</span>
                        <select class="select select-bordered" name="instructor" required>
                            <!--Display all the subjects here-->
                            <option value="" selected disabled>Select instructor</option>

                            <?php $instructors = $dbCon->query($instructorsQuery); ?>
                            <?php while ($instructor = $instructors->fetch_assoc()) { ?>
                                <option value="<?php echo $instructor['id'] ?>"><?php echo "{$instructor['firstName']} {$instructor['middleName']} {$instructor['lastName']}" ?></option>
                            <?php } ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[18px]">Students</span>

                            <label class="flex flex-col gap-2">
                                <select class="select select-bordered select-sm" id="section-students-filter">
                                    <!--Display all the Year here-->
                                    <option value="" selected disabled>Select year level</option>

                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </label>
                        </div>

                        <div class="border border-black rounded-[5px] w-full h-[300px] grid grid-cols-3 gap-4 p-4 overflow-y-scroll" id="section-students-body">
                            <?php $students = $dbCon->query($studentsQuery); ?>
                            <?php while ($student = $students->fetch_assoc()) { ?>
                                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                                    <input type="checkbox" class="checkbox checkbox-sm" />
                                    <span data-studentId="<?= $student['id'] ?>"><?= $student['firstName'] ?> <?= $student['middleName'] ?> <?= $student['lastName'] ?></span>
                                </div>
                            <?php } ?>
                        </div>
                    </label>

                    <input type="hidden" name="students" id="selected-students" />

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-sections.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success text-base" name="create_section">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const yearLevelSelect = document.querySelector("#section-students-filter");
        const studentContainer = document.querySelector("#section-students-body");

        // Year level filter for student selection
        yearLevelSelect.addEventListener("change", (e) => {
            const yearLevel = e.target.value;

            fetch(`<?= $_SERVER['PHP_SELF'] ?>?year_level=${yearLevel}`, {
                    method: "GET",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "content-type": "application/json"
                    }
                })
                .then(res => res.json())
                .then(students => {
                    studentContainer.innerHTML = "";

                    students.forEach(student => {
                        const studentDiv = document.createElement("div");
                        studentDiv.classList.add("h-[48px]", "flex", "gap-4", "justify-start", "px-4", "items-center", "gap-4", "border", "border-gray-400", "rounded-[5px]");
                        studentDiv.innerHTML = `
                        <input type="checkbox" class="checkbox checkbox-sm" />
                        <span data-studentId="${student.id}">${student.firstName} ${student.middleName} ${student.lastName}</span>
                    `;

                        studentContainer.appendChild(studentDiv);
                    })
                })
        })

        // Form submission
        document.querySelector("#create-section-form").addEventListener("submit", (e) => {
            // Get all the selected students
            const students = Array.from(document.querySelectorAll("#section-students-body input[type='checkbox']:checked"));
            const studentIds = students.map(student => student.nextElementSibling.dataset.studentid);

            // Set the value of the hidden input
            document.querySelector("#selected-students").value = JSON.stringify(studentIds);
        });
    });
</script>