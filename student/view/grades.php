<?php
session_start();
// kung walang session mag reredirect sa login //

require('../../vendor/autoload.php');
require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';
require('../../utils/grades.php');

use Fpdf\Fpdf;

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Check if the received request is an ajax request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $data = json_decode(file_get_contents('php://input'), true);
    $semester = $data['semester'];
    $id = $data['id'];

    // Get active school year
    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
    $schoolYear = $schoolYearQuery->fetch_assoc();

    // Get the user id from the database using the user email that is saved in the session
    $userQuery = $dbCon->query("SELECT * FROM userdetails WHERE id='$id' AND roles='student'");
    $user = $userQuery->fetch_object();

    // Get student section
    $sectionQuery = $dbCon->query("SELECT 
        section_students.*,
        sections.school_year AS school_year,
        school_year.semester AS semester,
        sections.year_level AS year_level,
        sections.course AS course,
        courses.course_code AS course_code
        FROM section_students
        LEFT JOIN sections ON section_students.section_id = sections.id
        LEFT JOIN courses ON sections.course = courses.id
        LEFT JOIN school_year ON sections.school_year = school_year.id
        WHERE section_students.student_id = '$id'
    ");
    $section = $sectionQuery->fetch_assoc();

    // Fetch all subjects from the student's course and year level
    $subjectsQuery = $dbCon->query("SELECT
        student_enrolled_subjects.*,
        subjects.*
        FROM student_enrolled_subjects
        LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
        WHERE student_enrolled_subjects.student_id = " . AuthController::user()->id
    );
    $subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

    // Fetch the final grades from the database
    $finalGradesQuery = $dbCon->query("SELECT
        *
        FROM student_final_grades
        WHERE student='{$user->id}' AND school_year='{$schoolYear['id']}' AND term='$semester'
    ");
    $finalGrades = $finalGradesQuery->fetch_all(MYSQLI_ASSOC);

    // Get final grades of each subjects
    $grades = [...array_filter(array_map(function ($subject) use ($finalGrades) {
        $subjectId = $subject['subject_id'];
        $subjectGrade = null;

        foreach ($finalGrades as $finalGrade) {
            if ($finalGrade['subject'] == $subjectId)
                $subjectGrade = $finalGrade['grade'];
        }

        return $subjectGrade == null ? null : array(
            $subject['subject_id'],
            $subject['code'],
            $subject['name'],
            $subjectGrade ?? 'NG',
            '',
            $subject['units'],
            $subject['credits_units']
        );
    }, $subjects), fn($grade) => $grade != null)];

    // Get grade request from grade_requests table
    // $gradeRequestsResults = $dbCon->query("SELECT * FROM grade_requests WHERE student_id = $id AND term = '$semester'");
    // $gradeRequest = $gradeRequestsResults->fetch_assoc();

    // $grades = array_merge(['grades' => $gradeRequest == null ? [] : $grades], ['gradeRequest' => $gradeRequest]);
    $grades = array_merge(['grades' => $grades]);

    header('Content-type: application/json');
    echo json_encode($grades);
    exit();
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// print grades
if (isset($_POST['print-grades'])) {
    $semester = $dbCon->real_escape_string($_POST['semester-hidden']);
    $user = AuthController::user();
    $studentName = "{$user->firstName} {$user->middleName} {$user->lastName}";
    $studentID = $user->sid;

    // Get active school year
    $_schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
    $_schoolYear = $_schoolYearQuery->fetch_assoc();

    // Fetch all subjects from the student's course and year level
    $_subjectsQuery = $dbCon->query("SELECT
        student_enrolled_subjects.*,
        subjects.*
        FROM student_enrolled_subjects
        LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
        WHERE student_enrolled_subjects.student_id='" . AuthController::user()->id . "'
    ");
    $_subjects = $_subjectsQuery->fetch_all(MYSQLI_ASSOC);

    // Fetch the final grades from the database
    $_finalGradesQuery = $dbCon->query("SELECT
        *
        FROM student_final_grades
        WHERE student='" . AuthController::user()->id . "' AND school_year='{$_schoolYear['id']}' AND term='$semester'
    ");
    $_finalGrades = $_finalGradesQuery->fetch_all(MYSQLI_ASSOC);

    // Get all grades
    $_grades = array_map(function($subject) use ($_finalGrades) {
        $subjectGrade = null;
        
        foreach ($_finalGrades as $finalGrade) {
            if ($finalGrade['subject'] == $subject['subject_id'])
                $subjectGrade = $finalGrade['grade'];
        }

        return $subjectGrade;
    }, $_subjects);

    // Filter null grades
    $_grades = array_filter($_grades, fn ($grade) => $grade != null);

    $areAllGradesFromSubjectsReleased = count($_grades) == count($_subjects);

    if ($areAllGradesFromSubjectsReleased) {
        /* $gradeRequestQuery = $dbCon->query("SELECT * FROM grade_requests WHERE student_id = {$user->id} AND term='$semester' AND status='approved'");

        if ($gradeRequestQuery->num_rows > 0) {
            
        } else {
            $hasError = true;
            $message = "You do not have permission to print/download this grades!";
        } */

        // Get section of the student
        $studentSectionQuery = $dbCon->query("SELECT
            sections.*,
            courses.course AS course_name,
            courses.course_code AS course_code
            FROM section_students
            LEFT JOIN sections ON section_students.section_id = sections.id
            LEFT JOIN courses ON sections.course = courses.id
            WHERE section_students.student_id = '{$user->id}'
        ");
        $studentSection = $studentSectionQuery->fetch_assoc();

        // Get active school year
        $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
        $schoolYear = $schoolYearQuery->fetch_assoc();

        class PDF extends FPDF
        {
            // Header
            function Header()
            {
                global $studentName, $studentID, $studentSection;

                // Logo
                $this->Image('../../assets/images/logo.png', 10, 6, 22, 22);
                // School Name
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, 'Cavite State University -', 0, 1, 'C');
                $this->Cell(0, 0, 'General Trias City, Campus (CvSU)', 0, 1, 'C');
                $this->Ln(16); // Line break

                $this->SetFont('Arial', '', 11);
                $this->Cell(0, 0, 'Student Name: ' . $studentName, 0, 1);
                $this->Cell(0, 10, 'Student ID: ' . $studentID, 0, 0);

                $this->Cell(0, 0, 'Year Level: ' . $studentSection['year_level'], 0, 0, 'R');
                $this->Cell(0, 10, 'Course: ' . $studentSection['course_code'], 0, 0, 'R');

                $this->Ln(16); // Line break
            }

            /* // Footer
            function Footer() {
                // Position at 1.5 cm from bottom
                $this->SetY(-15);
                // Arial italic 8
                $this->SetFont('Arial','I',8);
                // Page number
                $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
            } */
        }

        // Create PDF object
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage('P', 'Letter');
        $pdf->SetTitle("$studentName - {$studentSection['course_code']} {$studentSection['year_level']} - Grade Sheet");

        // Set font
        $pdf->SetFont('Arial', '', 11);

        // Fetch all subjects from the student's course and year level
        $subjectsQuery = $dbCon->query("SELECT
            student_enrolled_subjects.*,
            subjects.*
            FROM student_enrolled_subjects
            LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
            WHERE student_enrolled_subjects.student_id = {$user->id}"
        );
        $subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

        // Fetch the final grades from the database
        $finalGradesQuery = $dbCon->query("SELECT
            *
            FROM student_final_grades
            WHERE student='{$user->id}' AND school_year='{$schoolYear['id']}' AND term='$semester'
        ");

        if ($finalGradesQuery->num_rows > 0) {
            $finalGrades = $finalGradesQuery->fetch_all(MYSQLI_ASSOC);
            $generalWeightedAverage = 0;
            $sumUnits = 0;
            $sumUnitsXgrades = 0;

            // Get final grades of each subjects
            $grades = array_map(function ($subject) use ($finalGrades) {
                $subjectId = $subject['subject_id'];
                $subjectGrade = null;

                foreach ($finalGrades as $finalGrade) {
                    if ($finalGrade['subject'] == $subjectId)
                        $subjectGrade = $finalGrade['grade'];
                }

                return array(
                    $subject['code'],
                    $subject['name'],
                    $subjectGrade ?? 'NG',
                    '',
                    intval($subject['units']),
                    $subject['credits_units']
                );
            }, $subjects);

            // Exempted subjects from GWA computation
            $exemptedSubjectsFromGWA = array(
                'cvsu 101',
                'nstp 1',
                'nstp 2'
            );

            // Sum all units and Unit*Grade
            foreach ($grades as $grade) {
                if (!in_array(strtolower($grade[0]), $exemptedSubjectsFromGWA)) {
                    $sumUnits += $grade[4];
                    $sumUnitsXgrades += ((!is_numeric($grade[2]) ? 0 : floatval($grade[2])) * $grade[4]);
                }
            }

            // Compute GWA
            $generalWeightedAverage = $sumUnitsXgrades / ($sumUnits > 0 ? $sumUnits : 1);

            // Output grade sheet as table
            $pdf->SetFont('Arial', 'B', 10);

            // Output column headers
            $columnWidths = array(35, 82, 20, 15, 20, 24); // Widths of each column
            $columnHeaders = array('Course Code', 'Subject', 'Grade', 'Comp', 'Units', 'Credit Units'); // Column headers
            for ($i = 0; $i < count($columnHeaders); $i++) {
                $pdf->Cell($columnWidths[$i], 10, $columnHeaders[$i], 1, 0, 'C');
            }
            $pdf->Ln();

            // Output grade data
            $pdf->SetFont('Arial', '', 9);
            for ($row = 0; $row < count($grades); $row++) {
                for ($col = 0; $col < count($grades[$row]); $col++) {
                    $pdf->Cell($columnWidths[$col], 10, $grades[$row][$col], 1, 0, 'C');
                }
                $pdf->Ln();
            }

            // Output average
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 10, 'GWA: ' . number_format($generalWeightedAverage, 2), 0, 1);

            // Output PDF
            $pdf->Output('', "$studentName - {$studentSection['course_code']} {$studentSection['year_level']} - Grade Sheet.pdf");
        } else {
            $hasError = true;
            $message = "Your grades hasn't been released yet by your respective subject instructors";
        }
    } else {
        $hasError = true;
        $message = "You instructor(s) haven't released all of your grades yet!";
    }
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");


// request grade 
/* if (isset($_POST['request-grade'])) {
    $student_id = $dbCon->real_escape_string($_POST['student_id']);
    $term = $dbCon->real_escape_string($_POST['term']);

    // Get active school year
    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
    $schoolYear = $schoolYearQuery->fetch_assoc();

    // Fetch all subjects from the student's course and year level
    $subjectsQuery = $dbCon->query("SELECT
        student_enrolled_subjects.*,
        subjects.*
        FROM student_enrolled_subjects
        LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
        WHERE student_enrolled_subjects.student_id='" . AuthController::user()->id . "'
    ");
    $subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

    // Fetch the final grades from the database
    $finalGradesQuery = $dbCon->query("SELECT
        *
        FROM student_final_grades
        WHERE student='" . AuthController::user()->id . "' AND school_year='{$schoolYear['id']}' AND term='$term'
    ");
    $finalGrades = $finalGradesQuery->fetch_all(MYSQLI_ASSOC);

    // Get all grades
    $_grades = array_map(function($subject) use ($finalGrades) {
        $subjectGrade = null;
        
        foreach ($finalGrades as $finalGrade) {
            if ($finalGrade['subject'] == $subject['subject_id'])
                $subjectGrade = $finalGrade['grade'];
        }

        return $subjectGrade;
    }, $subjects);

    // Filter null grades
    $_grades = array_filter($_grades, fn ($grade) => $grade != null);

    $areAllGradesFromSubjectsReleased = count($_grades) == count($subjects);

    if ($areAllGradesFromSubjectsReleased) {
        // get section id from section_students table using student's id
        $sectionResults = $dbCon->query("SELECT section_id FROM section_students WHERE student_id = $student_id");
        $section = $sectionResults->fetch_assoc();

        // check if student_id, term and section_id already exists with a status of 'rejected'
        $gradeRequestResults = $dbCon->query("SELECT * FROM grade_requests WHERE student_id = $student_id AND term = '$term' AND section_id = {$section['section_id']} AND status = 'rejected'");

        // If a request with the same semester, section and term already exists and the status is rejected, then only update its status back to pending
        if ($gradeRequestResults->num_rows > 0) {
            $result = $dbCon->query("UPDATE grade_requests SET status = 'pending' WHERE student_id = $student_id AND term = '$term' AND section_id = {$section['section_id']}");

            if ($result) {
                $hasSuccess = true;
                $message = "Your request for the release of your grade has been sent to the admin.";
            } else {
                $hasError = true;
                $message = "There was an error sending your request, please try again.";
            }
        } else {
            // Only create new request if the student does not have an existing approved or pending request
            if ($dbCon->query("SELECT * FROM grade_requests WHERE student_id = $student_id AND term = '$term' AND section_id = {$section['section_id']} AND status IN ('approved', 'pending')")->num_rows == 0) {
                $result = $dbCon->query("INSERT INTO grade_requests (student_id, section_id, term, status) VALUES ($student_id, {$section['section_id']}, '$term', 'pending')");

                if ($result) {
                    $hasSuccess = true;
                    $message = "Your request for the release of your grade has been sent to the admin.";
                } else {
                    $hasError = true;
                    $message = "There was an error sending your request, please try again.";
                }
            }
        }
    } else {
        $hasError = true;
        $message = "You cannot request for you grades yet because your instructor(s) haven't released all of your grades yet";
    }
} */

// Get active school year
$schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
$schoolYear = $schoolYearQuery->fetch_assoc();

// Get the user id from the database using the user email that is saved in the session
$user = AuthController::user();
$id = $user->id;

// Get student section
$sectionQuery = $dbCon->query("SELECT 
    section_students.*,
    sections.school_year AS school_year,
    school_year.semester AS semester,
    sections.year_level AS year_level,
    sections.course AS course,
    courses.course_code AS course_code
    FROM section_students
    LEFT JOIN sections ON section_students.section_id = sections.id
    LEFT JOIN courses ON sections.course = courses.id
    LEFT JOIN school_year ON sections.school_year = school_year.id
    WHERE section_students.student_id = '$id'
");
$section = $sectionQuery->fetch_assoc();

// Fetch all subjects from the student's course and year level
$subjectsQuery = $dbCon->query("SELECT
    student_enrolled_subjects.*,
    subjects.*
    FROM student_enrolled_subjects
    LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
    WHERE student_enrolled_subjects.student_id = " . AuthController::user()->id
);
$subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

// Fetch the final grades from the database
$finalGradesQuery = $dbCon->query("SELECT
    *
    FROM student_final_grades
    WHERE student='{$user->id}' AND school_year='{$schoolYear['id']}' AND term='{$section['semester']}'
");
$finalGrades = $finalGradesQuery->fetch_all(MYSQLI_ASSOC);

// Get final grades of each subjects
$grades = [...array_filter(array_map(function ($subject) use ($finalGrades) {
    $subjectId = $subject['id'];
    $subjectGrade = null;

    foreach ($finalGrades as $finalGrade) {
        if ($finalGrade['subject'] == $subjectId)
            $subjectGrade = $finalGrade['grade'];
    }

    return $subjectGrade == null ? null : array(
        $subject['id'],
        $subject['code'],
        $subject['name'],
        $subjectGrade,
        '',
        $subject['units'],
        $subject['credits_units']
    );
}, $subjects), fn($grade) => $grade != null)];

// Get grade request from grade_requests table
$gradeRequestsResults = $dbCon->query("SELECT * FROM grade_requests WHERE student_id = $id AND term='1st Sem'");
$gradeRequest = $gradeRequestsResults->fetch_assoc();

/**
 * THIS SECTION BELOW IS RESPONSIBLE FOR DISABLING THE
 * PRINT AND REQUEST BUTTON IF THE STUDENT'S GRADES FROM
 * ALL THE SUBJECTS THAT HE/SHE IS ENROLLED HAS NOT YET 
 * BEEN RELEASED
 */

// Get active school year
/* $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
$schoolYear = $schoolYearQuery->fetch_assoc();

// Fetch all subjects from the student's course and year level
$subjectsQuery = $dbCon->query("SELECT
    student_enrolled_subjects.*,
    subjects.*
    FROM student_enrolled_subjects
    LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
    WHERE student_enrolled_subjects.student_id='" . AuthController::user()->id . "'
");
$subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

// Fetch the final grades from the database
$finalGradesQuery = $dbCon->query("SELECT
    *
    FROM student_final_grades
    WHERE student='" . AuthController::user()->id . "' AND school_year='{$schoolYear['id']}' AND term='1st Sem'
");
$finalGrades = $finalGradesQuery->fetch_all(MYSQLI_ASSOC);

// Get all grades
$_grades = array_map(function($subject) use ($finalGrades) {
    $subjectGrade = null;
    
    foreach ($finalGrades as $finalGrade) {
        if ($finalGrade['subject'] == $subject['subject_id'])
            $subjectGrade = $finalGrade['grade'];
    }

    return $subjectGrade;
}, $subjects);

// Filter null grades
$_grades = array_filter($_grades, fn ($grade) => $grade != null);

$areAllGradesFromSubjectsReleased = count($_grades) == count($subjects); */
?>


<main class="h-[95%] overflow-x-auto md:overflow-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 overflow-x-auto md:overflow-hidden">

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

            <!-- <div id="alert-dialogs">
                <?php // if ($gradeRequestsResults->num_rows > 0) : ?>
                    <?php // if ($gradeRequest['status'] == 'pending') : ?>
                        <div role="alert" class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Your request for the release of your grade is currently pending.</span>
                        </div>
                    <?php // endif; ?>

                    <?php // if ($gradeRequest['status'] == 'rejected') : ?>
                        <div role="alert" class="alert alert-error">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Your request for the release of your grade has been rejected by the admin, you may send another request to the admin.</span>
                        </div>
                    <?php // endif; ?>

                    <?php // if ($gradeRequest['status'] == 'approved') : ?>
                        <div role="alert" class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Your request for the release of your grade has been approved by the admin, you may now be able to view and print your grade.</span>
                        </div>
                    <?php // endif; ?>
                <?php // else : ?>
                    <div role="alert" class="alert alert-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>You currently don't have permission to view and print out your grades. Please send a grade request and wait for the admin's approval.</span>
                    </div>
                <?php // endif; ?>
            </div> -->

            <!-- Table Header -->
            <div class="grid grid-cols-1 gap-2 md:flex md:justify-between md:items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">My Grades</h1>
                </div>

                <div class="grid grid-cols-1 gap-2 md:flex md:gap-4">
                    <label class="flex flex-col gap-2">
                        <select class="select select-bordered" id="semester-filter">
                            <option value="" disabled>Select Semester</option>
                            <option value="1st Sem" selected>1st Sem</option>
                            <option value="2nd Sem">2nd Sem</option>
                            <option value="Midyear">Midyear</option>
                        </select>
                    </label>

                    <button class="btn btn-info" id="print-button"
                        <?php if (count($grades) > 0 && count($grades) == count($subjects)): ?>
                        onclick="print_modal.showModal()" <?php endif; ?>
                        <?php if (count($grades) == 0 || count($grades) != count($subjects)): ?> disabled
                        <?php endif; ?>>
                        <i class="bx bxs-printer"></i> Print
                    </button>

                    <!-- <button class="btn btn-success" 
                        id="request-button" 
                        onclick="request_modal.showModal()" 
                        <?php // if ($gradeRequestsResults->num_rows > 0 && ($gradeRequest['status'] == 'approved' || $gradeRequest['status'] == 'pending')) { ?> disabled <?php // } ?>
                    >
                        <i class="bx bxs-paper-plane "></i> Request for Grade
                    </button> -->
                </div>

            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto md:overflow-x-hidden border border-gray-300 rounded-md"
                style="height: calc(100vh - 250px)" id="printable-table">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <!-- <th class="bg-slate-500 text-white">ID</th> -->
                            <th class="bg-[#276bae] text-white text-center">Course Code</th>
                            <th class="bg-[#276bae] text-white text-center">Title</th>
                            <th class="bg-[#276bae] text-white text-center">Grade</th>
                            <th class="bg-[#276bae] text-white text-center">Comp</th>
                            <th class="bg-[#276bae] text-white text-center">Units</th>
                            <th class="bg-[#276bae] text-white text-center">Credit Units</th>
                        </tr>
                    </thead>
                    <tbody id="grades-body">
                        <?php
                        if (count($grades) > 0 && count($grades) == count($subjects)) {
                            foreach ($grades as $grade) {
                        ?>
                        <tr>
                            <!-- <td><?= $grade[0] ?></td> -->
                            <td class="text-center"><?= $grade[1] ?></td>
                            <td class="text-center"><?= $grade[2] ?></td>
                            <td class="text-center">
                                <?= is_string($grade[3]) ? $grade[3] : number_format($grade[3], 2) ?></td>
                            <td class="text-center"></td>
                            <td class="text-center"><?= $grade[5] ?></td>
                            <td class="text-center"><?= $grade[6] ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo "<tr class='text-center'><td colspan='6'>Your grades from all your enrolled subjects hasn't been released yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Grading system -->
            <div class="card bg-base-100 shadow-xl border border-1 rounded-md mb-8">
                <div class="card-body">
                    <h2 class="card-title">Grading System:</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div class="flex flex-col gap-2">
                            <p><span class="font-bold">1.00:&nbsp;&nbsp;</span> 96.7 - 100</p>
                            <p><span class="font-bold">1.25:&nbsp;&nbsp;</span> 93.4 - 96.6</p>
                            <p><span class="font-bold">1.50:&nbsp;&nbsp;</span> 90.1 - 93.3</p>
                            <p><span class="font-bold">1.75:&nbsp;&nbsp;</span> 86.7 - 90.0</p>
                            <p><span class="font-bold">2.00:&nbsp;&nbsp;</span> 83.4 - 86.6</p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <p><span class="font-bold">2.25:&nbsp;&nbsp;</span> 80.1 - 83.3</p>
                            <p><span class="font-bold">2.50:&nbsp;&nbsp;</span> 76.7 - 80.0</p>
                            <p><span class="font-bold">2.75:&nbsp;&nbsp;</span> 73.4 - 76.6</p>
                            <p><span class="font-bold">3.00:&nbsp;&nbsp;</span> 70.0 - 73.3</p>
                            <p><span class="font-bold">4.00:&nbsp;&nbsp;</span> 50.0 - 69.9</p>
                            <p><span class="font-bold">5.00:&nbsp;&nbsp;</span> &le; 49.9 &Rarr; Failed</p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <p><span class="font-bold">INC:&nbsp;&nbsp;</span> Lack of requirements / Incomplete</p>
                            <p><span class="font-bold">DRP:&nbsp;&nbsp;</span> Dropped</p>
                            <p><span class="font-bold">NG:&nbsp;&nbsp;</span> No Grade</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Print modal -->
<dialog id="print_modal" class="modal">
    <div class="modal-box border border-info">
        <h3 class="font-bold text-lg text-info">Print Grade</h3>
        <p class="py-4">Are you really sure you want to print your grade?</p>

        <form class="flex justify-end gap-4" id="print-form" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
            <input type="hidden" name="semester-hidden" value="1st Sem">
            <button class="btn btn-error" onclick="print_modal.close()">Cancel</button>
            <button class="btn btn-success" type="submit" name="print-grades">Yes</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- Request modal -->
<!-- <dialog id="request_modal" class="modal">
    <div class="modal-box border border-success">
        <h3 class="font-bold text-lg text-success">Request for Grade</h3>
        <p class="py-4">Are you really sure you want to request for your grade?</p>

        <form class="flex justify-end gap-4" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" id="request-form-modal">
            <input type="hidden" name="student_id" value="<?= $id ?>">
            <input type="hidden" name="term" value="1st Sem">

            <button class="btn btn-error" onclick="request_modal.close()">Cancel</button>
            <button class="btn btn-success" name="request-grade" onclick="request_modal.close()">Yes</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog> -->

<script>
const semesterFilter = document.getElementById('semester-filter');
const alertDialogs = document.getElementById('alert-dialogs');
const printButton = document.getElementById('print-button');
const requestButton = document.getElementById('request-button');

semesterFilter.addEventListener('change', (e) => {
    const selectedSemester = e.target.value;
    const gradesBody = document.getElementById('grades-body');

    // Update value of hidden input in the print dialog
    if (document.querySelector('#print-form'))
        document.querySelector('#print-form').querySelector('input[name="semester-hidden"]').value =
        selectedSemester;

    // update values of input[name="term"] in the request form modal
    if (document.getElementById('request-form-modal'))
        document.getElementById('request-form-modal').querySelector('input[name="term"]').value =
        selectedSemester;

    fetch(`<?= $_SERVER['PHP_SELF'] ?>`, {
            method: 'POST',
            body: JSON.stringify({
                semester: selectedSemester,
                id: <?= $id ?>
            }),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            let html = '';

            if (data.grades.length > 0 && data.grades.length == <?= count($subjects) ?>) {
                data.grades.forEach(grade => {
                    html += `
                            <tr>
                                <td>${grade[1]}</td>
                                <td>${grade[2]}</td>
                                <td>${grade[3]}</td>
                                <td>${grade[4]}</td>
                                <td>${grade[5]}</td>
                                <td>${grade[6]}</td>
                            </tr>
                        `;
                });

                printButton.removeAttribute('disabled');
            } else {
                html = `
                        <tr class="text-center">
                            <td colspan="6">Your grades from all your enrolled subjects hasn't been released yet.</td>
                        </tr>
                    `;

                printButton.setAttribute('disabled', true);
            }

            // Disable print button if there is no grade request or the grade request is not approved
            /* if (data.gradeRequest) {
                if (data.gradeRequest.status == 'approved') {
                    printButton.removeAttribute('disabled');
                } else {
                    printButton.setAttribute('disabled', true);
                }
            } else {
                printButton.setAttribute('disabled', true);
            } */

            // Display alert dialogs based on the grade request status
            /* if (data.gradeRequest) {
                // clear the alert dialogs
                alertDialogs.innerHTML = '';

                if (data.gradeRequest.status == 'pending') {
                    alertDialogs.innerHTML = `
                        <div role="alert" class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span>Your request for the release of your grade is currently pending.</span>
                        </div>
                    `;

                    requestButton.setAttribute('disabled', true);
                    printButton.setAttribute('disabled', true);
                }

                if (data.gradeRequest.status == 'rejected') {
                    alertDialogs.innerHTML = `
                        <div role="alert" class="alert alert-error">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Your request for the release of your grade is has been rejected by the admin, you may send another request to the admin.</span>
                        </div>
                    `;

                    requestButton.removeAttribute('disabled');
                    printButton.setAttribute('disabled', true);
                }

                if (data.gradeRequest.status == 'approved') {
                    alertDialogs.innerHTML = `
                        <div role="alert" class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Your request for the release of your grade has been approved by the admin, you may now be able to view and print your grade.</span>
                        </div>
                    `;

                    printButton.removeAttribute('disabled');
                    requestButton.setAttribute('disabled', true);
                }
            } else {
                alertDialogs.innerHTML = `
                    <div role="alert" class="alert alert-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>You currently don't have permission to view and print out your grades. Please send a grade request and wait for the admin's approval.</span>
                    </div>
                `;

                printButton.setAttribute('disabled', true);
                requestButton.removeAttribute('disabled');

                html = "<tr class='text-center'><td colspan='6'>No grades to show</td></tr>";
            } */

            gradesBody.innerHTML = html;
        });
});
</script>