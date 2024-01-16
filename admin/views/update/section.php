<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

// check if request is an ajax request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $data = json_decode(file_get_contents('php://input'), true);

    // fetch students
    if (isset($data['id']) && isset($data['year_level']) && isset($data['selectedStudentIds'])) {
        $id = $dbCon->real_escape_string($data['id']);
        $yearLevel = $dbCon->real_escape_string($data['year_level']);
        $selectedStudentIds = $data['selectedStudentIds'];

        $studentQuery = "SELECT * FROM ap_userdetails WHERE year_level='$yearLevel' AND roles='student'";
        $sectionStudentsQuery = "SELECT * FROM ap_section_students WHERE section_id='$id'";

        $result = $dbCon->query($studentQuery);

        if ($result->num_rows > 0) {
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $sectionStudents = $dbCon->query($sectionStudentsQuery);

            $sectionStudentIds = [];
            while ($sectionStudent = $sectionStudents->fetch_assoc()) {
                array_push($sectionStudentIds, $sectionStudent['student_id']);
            }

            $filteredStudents = array_filter($students, function ($student) use ($sectionStudentIds, $selectedStudentIds) {
                return !in_array($student['id'], $sectionStudentIds) || in_array($student['id'], $selectedStudentIds);
            });

            echo json_encode($filteredStudents);
        } else {
            echo json_encode([]);
        }
    }

    exit();
}

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

// update section
if (isset($_POST['update_section'])) {
    $sectionName = $dbCon->real_escape_string($_POST['section_name']);
    $subject = $dbCon->real_escape_string($_POST['subject']);
    $schoolYear = $dbCon->real_escape_string($_POST['school_year']);
    $term = $dbCon->real_escape_string($_POST['term']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $instructor = $dbCon->real_escape_string($_POST['instructor']);

    // Update section query
    $updateSectionQuery = "UPDATE ap_sections SET 
        name = '$sectionName',
        subject = '$subject',
        school_year = '$schoolYear',
        term = '$term',
        year_level = '$yearLevel',
        course = '$course',
        instructor = '$instructor'
        WHERE id = $id
    ";

    // Update section
    if ($dbCon->query($updateSectionQuery)) {
        $hasSuccess = true;
        $message = "Section updated successfully!";
    } else {
        $hasError = true;
        $message = "There was an error updating the section!";
    }
}

// Get id from url
$id = $dbCon->real_escape_string($_GET['id']) ? $dbCon->real_escape_string($_GET['id']) : header("Location: ../manage-sections.php");

// Fetch section details query joining ap_userdetails, ap_sections, ap_subjects, ap_schoolyear and ap_courses tables
$sectionQuery = "SELECT 
    ap_sections.id, 
    ap_sections.name AS sectionName,
    ap_sections.term AS term,
    ap_sections.year_level AS yearLevel,
    ap_subjects.id AS subjectId, 
    ap_subjects.name AS subjectName, 
    ap_school_year.school_year AS schoolYear, 
    ap_school_year.id AS schoolYearId, 
    ap_courses.id AS courseId,
    ap_courses.course AS courseName,
    ap_courses.course_code AS courseCode,
    ap_userdetails.id AS instructorId,
    CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) AS instructorName
    FROM ap_sections 
    INNER JOIN ap_subjects ON ap_sections.subject = ap_subjects.id 
    INNER JOIN ap_school_year ON ap_sections.school_year = ap_school_year.id 
    INNER JOIN ap_courses ON ap_sections.course = ap_courses.id
    INNER JOIN ap_userdetails ON ap_sections.instructor = ap_userdetails.id
    WHERE ap_sections.id = $id";

// Fetch all students query joining ap_userdetails and ap_section_students tables
$studentsQuery = "SELECT
    ap_section_students.id,
    ap_section_students.student_id AS studentId,
    CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) AS studentName
    FROM
    ap_section_students
    INNER JOIN ap_userdetails ON ap_section_students.student_id = ap_userdetails.id
    WHERE ap_section_students.section_id = $id
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

// Prefetch all subjects
$subjectsQuery = "SELECT * FROM ap_subjects WHERE id != '{$sectionResult['subjectId']}'";

// Prefetch all school years
$schoolYearsQuery = "SELECT * FROM ap_school_year WHERE school_year != '{$sectionResult['schoolYear']}'";

// Prefetch all courses
$coursesQuery = "SELECT * FROM ap_courses WHERE id != '{$sectionResult['courseId']}'";

// Prefetch all instructors
$instructorsQuery = "SELECT * FROM ap_userdetails WHERE id != '{$sectionResult['instructorId']}' AND roles = 'instructor'";
?>

<main class="w-screen h-screen overflow-x-hidden flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-full">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full">
            <div class="flex justify-center items-center flex-col p-8">
                <h2 class="text-[38px] font-bold mb-4">Update Section</h2>
                <form class="flex flex-col gap-[24px]  px-[32px]  w-[1000px] mb-auto flex" id="update-section-form">

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
                        <input class="input input-bordered" value="<?= $sectionResult['sectionName'] ?>" required />
                    </label>

                    <!-- Main Grid -->
                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject</span>
                            <select class="select select-bordered" required>
                                <!--Display all the subjects here-->
                                <option value="" disabled>Select Subject</option>
                                <option value="<?= $sectionResult['subjectId'] ?>" selected><?= $sectionResult['subjectName'] ?></option>

                                <?php $subjects = $dbCon->query($subjectsQuery); ?>
                                <?php while ($subject = $subjects->fetch_assoc()) { ?>
                                    <option value="<?= $subject['id'] ?>"><?= $subject['name'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Year</span>
                            <select class="select select-bordered" required>
                                <!--Display all the subjects here-->
                                <option value="" disabled>Select School Year</option>
                                <option value="<?= $sectionResult['schoolYearId'] ?>" selected><?= $sectionResult['schoolYear'] ?></option>

                                <?php $schoolYears = $dbCon->query($schoolYearsQuery); ?>
                                <?php while ($schoolYear = $schoolYears->fetch_assoc()) { ?>
                                    <option value="<?= $schoolYear['id'] ?>"><?= $schoolYear['school_year'] ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Term</span>
                            <select class="select select-bordered" required>
                                <!--Display all the Semister here-->
                                <option value="" disabled>Select School Term</option>

                                <option value="1st Sem" <?php if ($sectionResult['term'] == '1st Sem') { ?> selected <?php } ?>>1st Sem</option>
                                <option value="2nd Sem" <?php if ($sectionResult['term'] == '2nd Sem') { ?> selected <?php } ?>>2nd Sem</option>
                                <option value="3rd Sem" <?php if ($sectionResult['term'] == '3rd Sem') { ?> selected <?php } ?>>3rd Sem</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" required>
                                <!--Display all the Year here-->
                                <option value="" selected>Select Year Level</option>

                                <option value="1st Year" <?php if ($sectionResult['yearLevel'] == '1st Year') { ?> selected <?php } ?>>1st Year</option>
                                <option value="2nd Year" <?php if ($sectionResult['yearLevel'] == '2nd Year') { ?> selected <?php } ?>>2nd Year</option>
                                <option value="3rd Year" <?php if ($sectionResult['yearLevel'] == '3rd Year') { ?> selected <?php } ?>>3rd Year</option>
                                <option value="4th Year" <?php if ($sectionResult['yearLevel'] == '4th Year') { ?> selected <?php } ?>>4th Year</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Course</span>
                            <select class="select select-bordered" required>
                                <!--Display all the Course here-->
                                <option value="" disabled>Select Course</option>
                                <option value="<?= $sectionResult['courseName'] ?>" selected><?= $sectionResult['courseName'] ?></option>

                                <?php $courses = $dbCon->query($coursesQuery); ?>
                                <?php while ($course = $courses->fetch_assoc()) { ?>
                                    <option value="<?= $course['id'] ?>"><?= $course['course'] ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    </div>


                    <!-- Student Selections -->
                    <div class="divider">People</div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Instructor</span>
                        <select class="select select-bordered" required>
                            <!--Display all the subjects here-->
                            <option value="" disabled>Select Instructor</option>
                            <option value="<?= $sectionResult['instructorName'] ?>"><?= $sectionResult['instructorName'] ?></option>

                            <?php $instructors = $dbCon->query($instructorsQuery); ?>
                            <?php while ($instructor = $instructors->fetch_assoc()) { ?>
                                <option value="<?= $instructor['id'] ?>"><?= $instructor['firstName'] . ' ' . $instructor['middleName'] . ' ' . $instructor['lastName'] ?></option>
                            <?php } ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[18px]">Students</span>

                            <label class="flex flex-col gap-2">
                                <select class="select select-bordered select-sm" id="section-students-filter">
                                    <!--Display all the Year level here-->
                                    <option value="" selected disabled>Select Year level</option>

                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                </select>
                            </label>
                        </div>


                        <div class="border border-black rounded-[5px] w-full h-[300px] grid grid-cols-3 gap-4 p-4 overflow-y-scroll " id="section-students-body">

                            <!-- Students -->
                            <?php while ($student = $studentsResult->fetch_assoc()) { ?>
                                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                                    <input type="checkbox" class="checkbox checkbox-sm" checked />
                                    <span data-studentId="<?= $student['studentId'] ?>" data-default="yes"><?= $student['studentName'] ?></span>
                                </div>
                            <?php } ?>

                        </div>
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a class="btn btn-error" href="../manage-sections.php">Cancel</a>
                        <button class="btn btn-success text-base" name="update_section">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
    // TODO: fix bug in student selection
    document.addEventListener("DOMContentLoaded", () => {
        const yearLevelSelect = document.querySelector("#section-students-filter");
        const studentContainer = document.querySelector("#section-students-body");

        // Year level filter for student selection
        yearLevelSelect.addEventListener("change", (e) => {
            // Get all students that has an attribute data-default="yes"
            const selectedStudents = Array.from(studentContainer.querySelectorAll("span[data-default='yes']"));
            const selectedStudentIds = selectedStudents.map(student => student.dataset.studentid);

            const yearLevel = e.target.value;
            const data = {
                year_level: yearLevel,
                id: "<?= $id ?>",
                selectedStudentIds: selectedStudentIds
            };

            fetch(`<?= $_SERVER['PHP_SELF'] ?>`, {
                    method: "POST",
                    body: JSON.stringify(data),
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "content-type": "application/json"
                    }
                })
                .then(res => res.json())
                .then(students => {
                    // get all unselected students from studentContainer
                    const unselectedStudents = Array.from(studentContainer.querySelectorAll("input[type='checkbox']:not(:checked)"));

                    // remove all unselected students from studentContainer
                    unselectedStudents.forEach(student => student.parentElement.remove());

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
        document.querySelector("#update-section-form").addEventListener("submit", (e) => {
            // Get all the selected students
            const students = Array.from(document.querySelectorAll("#section-students-body input[type='checkbox']:checked"));
            const studentIds = students.map(student => student.nextElementSibling.dataset.studentid);

            // Set the value of the hidden input
            document.querySelector("#selected-students").value = JSON.stringify(studentIds);
        });
    });
</script>