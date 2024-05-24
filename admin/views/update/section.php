<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

// check if request is an ajax request
/* if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $data = json_decode(file_get_contents('php://input'), true);

    // fetch students
    if (isset($data['id']) && isset($data['year_level']) && isset($data['selectedStudentIds'])) {
        $id = $dbCon->real_escape_string($data['id']);
        $yearLevel = $dbCon->real_escape_string($data['year_level']);
        $selectedStudentIds = $data['selectedStudentIds'];

        if ($yearLevel === "All") {
            $studentQuery = "SELECT * FROM userdetails WHERE roles='student' AND id NOT IN (SELECT student_id FROM section_students WHERE section_id='$id')";
        } else {
            $studentQuery = "SELECT * FROM userdetails WHERE year_level='$yearLevel' AND roles='student' AND id NOT IN (SELECT student_id FROM section_students WHERE section_id='$id')";
        }

        if(count($selectedStudentIds) > 0)
            $sectionStudentsQuery = "SELECT * FROM section_students WHERE section_id='$id' AND student_id NOT IN (" . implode(",", $selectedStudentIds) . ")";
        else
            $sectionStudentsQuery = "SELECT * FROM section_students WHERE section_id='$id'";

        $result = $dbCon->query($studentQuery);

        if ($result->num_rows > 0) {
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $sectionStudents = $dbCon->query($sectionStudentsQuery);

            $sectionStudentIds = [];
            while ($sectionStudent = $sectionStudents->fetch_assoc()) {
                array_push($sectionStudentIds, $sectionStudent['student_id']);
            }

            // filter students using foreach loop
            $filteredStudents = [];
            foreach ($students as $student) {
                if (!in_array($student['id'], $sectionStudentIds) || !in_array($student['id'], $selectedStudentIds)) {
                    array_push($filteredStudents, $student);
                }
            }

            echo json_encode($filteredStudents);
        } else {
            echo json_encode([]);
        }
    }

    exit();
} */

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
$id = $dbCon->real_escape_string($_GET['id']) 
    ? $dbCon->real_escape_string($_GET['id']) 
    : header("Location: ../manage-sections.php");

// update section
if (isset($_POST['update_section'])) {
    $sectionName = $dbCon->real_escape_string($_POST['section_name']);
    $schoolYear = $dbCon->real_escape_string($_POST['school_year']);
    $term = $dbCon->real_escape_string($_POST['term']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $course = $dbCon->real_escape_string($_POST['course']);
    // $selectedStudents = json_decode($_POST['selected_students']);
    $subjects = $_POST['subjects'];

    // Get instructor from course
    $courseQuery = $dbCon->query("SELECT * FROM courses WHERE id='$course'");
    $courseInstructor = $courseQuery->fetch_assoc()['adviser'];

    // Update section query
    $updateSectionQuery = "UPDATE sections SET 
        name = '$sectionName',
        school_year = '$schoolYear',
        term = '$term',
        year_level = '$yearLevel',
        course = '$course',
        instructor = '$courseInstructor'
        WHERE id = '$id'
    ";

    // Delete all students from section_students table
    // $deleteSectionStudentsQuery = "DELETE FROM section_students WHERE section_id = $id";

    // Execute delete query
    // $dbCon->query($deleteSectionStudentsQuery);

    // check if there are selected subjects
    if (count($subjects) > 0) {
        // delete all subjects assigned to the section
        $deleteSectionSubjectsQuery = "DELETE FROM section_subjects WHERE section_id = $id";
        $dbCon->query($deleteSectionSubjectsQuery);

        // Update section subjects query
        $updateSectionSubjectsQuery = "INSERT INTO section_subjects(section_id, subject_id) VALUES";

        // loop though all selected subjects
        foreach ($subjects as $subjectId) {
            $updateSectionSubjectsQuery .= "($id, $subjectId),";
        }

        // Remove last comma
        $updateSectionSubjectsQuery = substr($updateSectionSubjectsQuery, 0, -1);

        // Execute insert query
        $dbCon->query($updateSectionSubjectsQuery);

        // check if there are selected students
        /* if (count($selectedStudents) > 0) {
            // Insert all selected students to section_students table
            $insertSectionStudentsQuery = "INSERT INTO section_students (section_id, student_id) VALUES ";

            foreach ($selectedStudents as $studentId) {
                $insertSectionStudentsQuery .= "($id, $studentId),";
            }

            // Remove last comma
            $insertSectionStudentsQuery = substr($insertSectionStudentsQuery, 0, -1);

            // Execute insert query
            $dbCon->query($insertSectionStudentsQuery);
        } */

        // Update section
        if ($dbCon->query($updateSectionQuery)) {
            $hasSuccess = true;
            $message = "Section updated successfully!";
        } else {
            $hasError = true;
            $message = "There was an error updating the section! {$dbCon->error}";
        }
    } else {
        // If no subjects are selected, return an error
        $hasError = true;
        $message = "Please select at least one subject!";
    }
}

// Fetch section details query joining userdetails, sections, subjects, schoolyear and courses tables
$sectionQuery = "SELECT 
    sections.id, 
    sections.name AS sectionName,
    sections.term AS term,
    sections.year_level AS yearLevel,
    school_year.school_year AS schoolYear, 
    school_year.id AS schoolYearId, 
    courses.id AS courseId,
    courses.course AS courseName,
    courses.course_code AS courseCode,
    userdetails.id AS instructorId,
    CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS instructorName
    FROM sections 
    INNER JOIN school_year ON sections.school_year = school_year.id 
    INNER JOIN courses ON sections.course = courses.id
    INNER JOIN userdetails ON sections.instructor = userdetails.id
    WHERE sections.id = $id";

// Fetch all students query joining userdetails and section_students tables
// $studentsQuery = "SELECT
//     section_students.id,
//     section_students.student_id AS studentId,
//     CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS studentName,
//     userdetails.year_level as year_level
//     FROM
//     section_students
//     INNER JOIN userdetails ON section_students.student_id = userdetails.id
//     WHERE section_students.section_id = $id
// ";


// Prefetch section query
$sectionResult = $dbCon->query($sectionQuery);

// If section does not exist, redirect to manage-sections.php
if ($sectionResult->num_rows === 0) {
    header("Location: ../manage-sections.php");
    exit();
}

// Fetch all subjects assigned to the section
$sectionSubjectsQuery = "SELECT 
    subjects.* 
    FROM section_subjects
    JOIN subjects ON section_subjects.subject_id = subjects.id
    WHERE section_id = $id
";

// Prefetch section subjects
$sectionSubjects = $dbCon->query($sectionSubjectsQuery);

// store section subject ids in an array
$sectionSubjectIds = [];
while ($sectionSubject = $sectionSubjects->fetch_assoc()) {
    array_push($sectionSubjectIds, $sectionSubject['id']);
}

// Prefetch section result
$sectionResult = $sectionResult->fetch_assoc();

// Prefetch all students query
// $studentsResult = $dbCon->query($studentsQuery);

// Prefetch all subjects
if(count($sectionSubjectIds) > 0)
    $subjectsQuery = "SELECT * FROM subjects WHERE id NOT IN (" . implode(",", $sectionSubjectIds) . ")";
else
    $subjectsQuery = "SELECT * FROM subjects";

// Prefetch all school years
$schoolYearsQuery = "SELECT * FROM school_year WHERE school_year != '{$sectionResult['schoolYear']}'";

// Prefetch all courses
$coursesQuery = "SELECT * FROM courses WHERE id != '{$sectionResult['courseId']}'";
?>
<style>
    .ts-wrapper .ts-control {
        background-color: transparent;
        border-color: var(--fallback-bc,oklch(var(--bc)/0.2));
        height: 3rem;
        padding-left: 1rem;
        padding-right: 2.5rem;
        line-height: 2;
    }

    .ts-wrapper .ts-control input {
        color: white;
    }
</style>
<main class="w-screen h-screen overflow-x-auto flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-full">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full">
            <div class="flex justify-center items-center flex-col p-8">
                <h2 class="text-[38px] font-bold mb-4">Update Section</h2>
                <form class="flex flex-col gap-[24px]  px-[32px]  w-[1000px] mb-auto flex" id="update-section-form" method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $id ?>">

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
                        <input class="input input-bordered" value="<?= $sectionResult['sectionName'] ?>" name="section_name" required />
                    </label>

                    <!-- Subjects -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Subjects</span>
                        <div class="relative flex w-full">
                            <select class="block w-full rounded-sm cursor-pointer focus:outline-none w-full" name="subjects[]" id="subjects" multiple required>
                                <option value="" disabled>Select Subject</option>

                                <?php $sectionSubjectsResult = $dbCon->query($sectionSubjectsQuery); ?>
                                <?php while ($sectionSubject = $sectionSubjectsResult->fetch_assoc()) { ?>
                                    <option value="<?= $sectionSubject['id'] ?>" selected><?= $sectionSubject['name'] ?></option>
                                <?php } ?>

                                <?php $subjects = $dbCon->query($subjectsQuery); ?>
                                <?php while ($subject = $subjects->fetch_assoc()) { ?>
                                    <option value="<?= $subject['id'] ?>"><?= $subject['name'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </label>

                    <!-- School Year -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">School Year</span>
                        <select class="select select-bordered" name="school_year" required>
                            <!--Display all the subjects here-->
                            <option value="" disabled>Select School Year</option>
                            <option value="<?= $sectionResult['schoolYearId'] ?>" selected><?= $sectionResult['schoolYear'] ?></option>

                            <?php $schoolYears = $dbCon->query($schoolYearsQuery); ?>
                            <?php while ($schoolYear = $schoolYears->fetch_assoc()) { ?>
                                <option value="<?= $schoolYear['id'] ?>"><?= $schoolYear['school_year'] ?></option>
                            <?php } ?>
                        </select>
                    </label>

                    <!-- Main Grid -->
                    <div class="grid grid-cols-2 gap-4">

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">School Term</span>
                            <select class="select select-bordered" name="term" required>
                                <!--Display all the Semister here-->
                                <option value="" disabled>Select School Term</option>

                                <option value="1st Sem" <?php if ($sectionResult['term'] == '1st Sem') { ?> selected <?php } ?>>1st Sem</option>
                                <option value="2nd Sem" <?php if ($sectionResult['term'] == '2nd Sem') { ?> selected <?php } ?>>2nd Sem</option>
                                <option value="Midyear" <?php if ($sectionResult['term'] == 'Midyear') { ?> selected <?php } ?>>Midyear</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" name="year_level" required>
                                <!--Display all the Year here-->
                                <option value="" selected>Select Year Level</option>

                                <option value="1st Year" <?php if ($sectionResult['yearLevel'] == '1st Year') { ?> selected <?php } ?>>1st Year</option>
                                <option value="2nd Year" <?php if ($sectionResult['yearLevel'] == '2nd Year') { ?> selected <?php } ?>>2nd Year</option>
                                <option value="3rd Year" <?php if ($sectionResult['yearLevel'] == '3rd Year') { ?> selected <?php } ?>>3rd Year</option>
                                <option value="4th Year" <?php if ($sectionResult['yearLevel'] == '4th Year') { ?> selected <?php } ?>>4th Year</option>
                            </select>
                        </label>

                        <label class="flex flex-col col-span-2 gap-2">
                            <span class="font-bold text-[18px]">Course</span>
                            <select class="select select-bordered" name="course" required>
                                <!--Display all the Course here-->
                                <option value="" disabled>Select Course</option>
                                <option value="<?= $sectionResult['courseId'] ?>" selected><?= $sectionResult['courseName'] ?></option>

                                <?php $courses = $dbCon->query($coursesQuery); ?>
                                <?php while ($course = $courses->fetch_assoc()) { ?>
                                    <option value="<?= $course['id'] ?>"><?= $course['course'] ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    </div>


                    <!-- Student Selections -->
                    <!-- <div class="divider">People</div> -->

                    <!-- <label class="flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[18px]">Students</span>

                            <label class="flex flex-col gap-2">
                                <select class="select select-bordered select-sm" id="section-students-filter">
                                    <option value="" selected disabled>Select Year level</option>

                                    <option value="All">All</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                    <option value="5th Year">5th Year</option>
                                </select>
                            </label>
                        </div>


                        <div class="border border-black rounded-[5px] w-full h-[300px] flex flex-col p-4 overflow-y-scroll " id="section-students-body">

                            <?php // while ($student = $studentsResult->fetch_assoc()) { ?>
                                <div class="h-[56px] border border-gray-400 rounded-[5px] mb-2">
                                    <div class="flex gap-4 justify-start px-4 items-center">
                                        <input type="checkbox" class="checkbox checkbox-sm" checked />
                                        <div class="flex flex-col gap-1">
                                            <span data-studentId="<?= "" // $student['studentId'] ?>"><?= "" // $student['studentName'] ?></span>
                                            <span class="badge badge-info"><?= "" // $student['year_level'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php // } ?>

                        </div>
                    </label> -->

                    <!-- <input type="hidden" id="selected-students" name="selected_students" /> -->

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a class="btn btn-error" href="../view/course_section.php?id=<?= $sectionResult['courseId'] ?>&yearLevel=<?= $sectionResult['yearLevel'] ?>">Cancel</a>
                        <button class="btn btn-success text-base" name="update_section">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            new TomSelect("#subjects", {});
    
            /* const yearLevelSelect = document.querySelector("#section-students-filter");
            const studentContainer = document.querySelector("#section-students-body");
    
            // Year level filter for student selection
            yearLevelSelect.addEventListener("change", (e) => {
                // Get all students that has been checked
                const selectedStudents = Array.from(studentContainer.querySelectorAll("input[type='checkbox']:checked"));
                const selectedStudentIds = selectedStudents.map(student => student.parentElement.querySelector('span').dataset.studentid);
    
                console.log("selected students: ", selectedStudentIds);
    
                const yearLevel = e.target.value;
                const data = {
                    year_level: yearLevel,
                    id: "<?= "" // $id ?>",
                    selectedStudentIds: selectedStudentIds
                };
    
                fetch(`<?= "" // $_SERVER['PHP_SELF'] ?>`, {
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
                        unselectedStudents.forEach(student => student.parentElement.parentElement.remove());
    
                        console.log("fetched students: " ,students);
    
                        students.forEach(student => {
                            const studentDiv = document.createElement("div");
                            studentDiv.classList.add("h-[56px]", "border", "border-gray-400", "rounded-[5px]", "mb-2");
                            studentDiv.innerHTML = `
                                <div class="flex gap-4 justify-start px-4 items-center">
                                    <input type="checkbox" class="checkbox checkbox-sm" />
                                    <div class="flex flex-col gap-1">
                                        <span data-studentId="${student.id}">${student.firstName} ${student.middleName} ${student.lastName}</span>
                                        <span class="badge badge-info">${student.year_level}</span>
                                    </div>
                                </div>
                            `;
    
                            studentContainer.appendChild(studentDiv);
                        })
                    })
            })
    
            // Form submission
            document.querySelector("#update-section-form").addEventListener("submit", (e) => {
                // e.preventDefault();
                // Get all the selected students
                const students = Array.from(document.querySelectorAll("#section-students-body input[type='checkbox']:checked"));
                const studentIds = students.map(student => student.nextElementSibling.firstElementChild.dataset.studentid);
    
    
                console.log(studentIds);
    
                // Set the value of the hidden input
                document.querySelector("#selected-students").value = JSON.stringify(studentIds);
            }); */
        });
    </script>
</main>
