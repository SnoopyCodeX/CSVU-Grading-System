<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../vendor/autoload.php");
require("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';
require('../../../utils/grades.php');
require('../../../utils/files.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

$subjectId = $dbCon->real_escape_string($_GET['subjectId'] ?? '');

if (empty($subjectId) || $dbCon->query("SELECT * FROM subjects WHERE id = $subjectId")->num_rows == 0) {
    header("../manage-activity.php");
    exit;
}

// error and success handlers
$hasError = false;
$hasSuccess = false;
$hasFilter = false;
$hasWarning = false;
$warning = "";
$message = "";

if (isset($_POST['apply-filter'])) {
    $filterCriteria = $dbCon->real_escape_string($_POST['filter-criteria'] ?? '');
    $filterCourse = $dbCon->real_escape_string($_POST['filter-course'] ?? '');
    $filterYearLevel = $dbCon->real_escape_string($_POST['filter-yearlevel'] ?? '');
    $filterSemester = $dbCon->real_escape_string($_POST['filter-semester'] ?? '');

    $hasFilter = true;
}

if (isset($_POST['clear-filter'])) {
    $hasFilter = false;

    unset($filterCriteria);
    unset($filterCourse);
    unset($filterYearLevel);
    unset($filterSemester);
}

// Submit grade release request
if (isset($_POST['submit-release-grade-request'])) {
    $subject = $dbCon->real_escape_string($_POST['subject']);
    $term = $dbCon->real_escape_string($_POST['term']);
    $file = $_FILES['file'];

    // Get active school year
    $schoolYearsQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");

    if ($schoolYearsQuery->num_rows > 0) {
        $schoolYear = $schoolYearsQuery->fetch_assoc();

        // Get file details
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileType = $file['type'];

        // Get file extension
        $fileExt = explode('.', $fileName);
        $fileActualExt = strtolower(end($fileExt));
        $allowed = ['pdf'];

        // Check if the extension and the mimetype is of a PDF File
        if (in_array($fileActualExt, $allowed) && $fileType === 'application/pdf') {
            $maxFileSize = (1024 * 1024) * 10; // 10 MB
            $actualFileSize = $fileSize / (1024 * 1024);

            // Check if file size is equal or less than 10 MB
            if ($actualFileSize <= $maxFileSize && $actualFileSize > 0) {
                
                // Check if the uploaded pdf file is valid
                if (validatePDFFile($fileTmpName)) {
                    // Get current directory
                    $currentDir = dirname($_SERVER['PHP_SELF']);
                    $firstDir = explode('/', trim($currentDir, '/'));

                    // Create new file name and assign new file path
                    $instructor = AuthController::user();
                    $newFileName = "Instructor Grade Sheet " . date("Y-m-d-h-i-s") . ".pdf";
                    $newFilePath = str_repeat("../", count($firstDir) - 1) . "uploads/$newFileName";

                    // Upload file
                    if (@move_uploaded_file($fileTmpName, $newFilePath)) {
                        // Get subject details
                        $subjectDataQuery = $dbCon->query("SELECT * FROM subjects WHERE id = '$subject'");
                        $subjectData = $subjectDataQuery->fetch_assoc();
                        
                        // Check if instructor already has an existing request (pending or approved or grade-released) with the same subject, term and school year
                        $checkRequestQuery = $dbCon->query("SELECT * FROM instructor_grade_release_requests WHERE 
                            instructor_id='{$instructor->id}' AND 
                            subject_id='$subject' AND 
                            term='$term' AND 
                            school_year='$schoolYear[id]' AND
                            status IN ('approved', 'pending', 'grade-released')
                        ");

                        if ($checkRequestQuery->num_rows == 0) {

                            $checkRejectedRequestQuery = $dbCon->query("SELECT * FROM instructor_grade_release_requests WHERE 
                                instructor_id='{$instructor->id}' AND 
                                subject_id='$subject' AND 
                                term='$term' AND 
                                school_year='$schoolYear[id]' AND
                                status = 'rejected'
                                ORDER BY updated_at DESC
                            ");

                            // Check if instructor already have a rejected request, if so, update its status and the pdf file
                            if ($checkRejectedRequestQuery->num_rows > 0) {
                                $rejectedRequest = $checkRejectedRequestQuery->fetch_assoc();
                                $rejectedID = $rejectedRequest['id'];
                                $rejectedPDFFile = str_repeat("../", count($firstDir) - 1) . "uploads/$rejectedRequest[grade_sheet_file]";

                                // Delete the rejected PDF file
                                @unlink($rejectedPDFFile);

                                // Update the status and pdf file of the rejected request
                                $updateRequestQuery = $dbCon->query("UPDATE 
                                    instructor_grade_release_requests 
                                    SET grade_sheet_file = '$newFileName', status='pending' 
                                    WHERE id = '$rejectedID'
                                ");

                                if ($updateRequestQuery) {
                                    $hasSuccess = true;
                                    $message = "Grade release request for <strong>($subjectData[code]) $subjectData[name]</strong>@<strong>$term</strong> has been sent to the admin successfully and is currently pending for approval.";
                                } else {
                                    $hasError = true;
                                    $message = "Failed to send grade release request for <strong>($subjectData[code]) $subjectData[name]</strong>@<strong>$term</strong> to the admin.";
                                    
                                    // Delete the uploaded pdf file
                                    @unlink($newFilePath);
                                }
                            } else {
                                // Create a new release request
                                $newRequest = $dbCon->query("INSERT INTO instructor_grade_release_requests(instructor_id, subject_id, grade_sheet_file, file_uid, school_year, term) VALUES (
                                    '{$instructor->id}',
                                    '$subject',
                                    '$newFileName',
                                    '" . md5(uniqid()) . "',
                                    '$schoolYear[id]',
                                    '$term'
                                )");

                                if ($newRequest) {
                                    $hasSuccess = true;
                                    $message = "Grade release request for <strong>($subjectData[code]) $subjectData[name]</strong>@<strong>$term</strong> has been sent to the admin successfully and is currently pending for approval.";
                                } else {
                                    $hasError = true;
                                    $message = "Failed to send grade release request for <strong>($subjectData[code]) $subjectData[name]</strong>@<strong>$term</strong> to the admin.";

                                    // Delete the uploaded pdf file
                                    @unlink($newFilePath);
                                }
                            }

                        } else {
                            $hasError = true;
                            $message = "Request aborted, you already have a <strong>pending/approved</strong> request or you have already <strong>released</strong> the grades with the same subject, semester and school year!";

                            // Delete the uploaded pdf file
                            @unlink($newFilePath);
                        }
                    } else {
                        $hasError = true;
                        $message = "Upload failed! Failed to upload PDF file to the server!";
                    }
                } else {
                    $hasError = true;
                    $message = "Invalid PDF file uploaded! Please upload a valid PDF file.";
                }

            } else {
                $hasError = true;
                $message = "Invalid file size! The size of the PDF file must not exceed <strong>10 MB</strong> and the file must not be <strong>EMPTY</strong>!";
            }
        } else {
            $hasError = true;
            $message = "Invalid file type! Only PDF file is allowed to be uploaded";
        }
    } else {
        $hasError = true;
        $message = "Failed to send request to admin. There's no active school year and semester. Contact your admin to create new active school year and semester.";
    }
}

// Create Activity
if (isset($_POST['create-activity'])) {
    $activity_name = $dbCon->real_escape_string($_POST['activity_name']);
    $passing_rate = $dbCon->real_escape_string($_POST['passing_rate']);
    $max_score = $dbCon->real_escape_string($_POST['max_score']);
    $type = $dbCon->real_escape_string($_POST['type']);

    // Get active school year
    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");

    if ($schoolYearQuery->num_rows > 0) {
        // Get all grading criterias
        $gradingCriteriasQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE instructor=" . AuthController::user()->id);

        if ($gradingCriteriasQuery->num_rows > 0) {
            $schoolYear = $schoolYearQuery->fetch_assoc();

            if (intval($max_score) > 0) {
                if (intval($passing_rate) >= 1 && intval($passing_rate) <= 100) {
                    $subjectDataQuery = $dbCon->query("SELECT * FROM subjects WHERE id = $subjectId");
                    $subjectData = $subjectDataQuery->fetch_assoc();

                    $query = $dbCon->query("INSERT INTO activities (
                        name, 
                        subject, 
                        school_year, 
                        term, 
                        year_level, 
                        course, 
                        passing_rate, 
                        max_score, 
                        instructor,
                        type
                    ) VALUES (
                        '$activity_name', 
                        '$subjectId', 
                        '" . $schoolYear['id'] . "', 
                        '" . $schoolYear['semester'] . "', 
                        '" . ucwords($subjectData['year_level']) . "', 
                        '$subjectData[course]', 
                        '" . intval($passing_rate) / 100 . "', 
                        '$max_score',
                        '" . AuthController::user()->id . "',
                        '$type'
                    )");

                    if ($query) {
                        $hasSuccess = true;
                        $message = "Activity created successfully!";
                    } else {
                        $hasError = true;
                        $message = "Something went wrong. Please try again! {$dbCon->error}";
                    }
                } else {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "Failed to create new activity. Activity passing rate must be in the range of <strong>1% - 100%</strong>!";
                }
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Failed to create new activity. Max activity score must be greater than 0!";
            }
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "<span class='flex items-center'>Failed to create new activity. You must first create your grading criteria.</span> <div class='flex w-full justify-end items-center'><a href='../manage-grading-criteria.php' class='btn btn'><i class='bx bx-plus-circle'></i> Create</a></div>";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Failed to create new activity. There is no currently active school year. Contact your admin to create a new school year.";
    }
}

// Update Activity
if (isset($_POST['update-activity'])) {
    $id = $dbCon->real_escape_string($_POST['activity_id']);
    $activity_name = $dbCon->real_escape_string($_POST['activity_name']);
    $passing_rate = $dbCon->real_escape_string($_POST['passing_rate']);
    $max_score = $dbCon->real_escape_string($_POST['max_score']);
    $type = $dbCon->real_escape_string($_POST['type']);

    // Get active school year
    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");

    if ($schoolYearQuery->num_rows > 0) {
        if (intval($max_score) > 0) {
            if (intval($passing_rate) >= 1 && intval($passing_rate) <= 100) {
                $schoolYear = $schoolYearQuery->fetch_assoc();

                $subjectDataQuery = $dbCon->query("SELECT * FROM subjects WHERE id = $subjectId");
                $subjectData = $subjectDataQuery->fetch_assoc();

                $query = $dbCon->query("UPDATE activities SET 
                    name = '$activity_name', 
                    subject = '$subjectId', 
                    school_year = '{$schoolYear['id']}', 
                    term = '{$schoolYear['semester']}', 
                    year_level = '" . ucwords($subjectData['year_level']) . "', 
                    course = '$subjectData[course]', 
                    passing_rate = '" . intval($passing_rate) / 100 . "', 
                    max_score = '$max_score',
                    type='$type'
                    WHERE id = '$id'
                ");

                if ($query) {
                    $hasSuccess = true;
                    $message = "Activity updated successfully!";
                } else {
                    $hasError = true;
                    $message = "Something went wrong. Please try again!";
                }
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Failed to create new activity. Activity passing rate must be in the range of <strong>1% - 100%</strong>!";
            }
        } else {
            $hasError = true;
            $message = "Failed to create new activity. Max activity score must be greater than 0!";
        }
    } else {
        $hasError = true;
        $message = "Failed to update activity. There is no currently active school year. Contact your admin to create a new school year.";
    }
}

// Delete activity
if (isset($_POST['delete-activity'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $deleteQuery = "DELETE FROM activities WHERE id = $id";
    $result = $dbCon->query($deleteQuery);

    if ($result) {
        $dbCon->query("DELETE FROM activity_scores WHERE activity_id = $id");

        $hasSuccess = true;
        $message = "Activity deleted successfully!";
    } else {
        $hasError = true;
        $message = "Something went wrong. Please try again!";
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if (!$hasFilter) {
    $paginationQuery = "SELECT COUNT(*) AS id FROM activities WHERE instructor = '" . AuthController::user()->id . "' AND subject=$subjectId";
} else {
    $paginationQuery = "SELECT COUNT(*) AS id FROM activities WHERE instructor = '" . AuthController::user()->id . "' AND subject=$subjectId";

    if (!empty($filterCourse) && $dbCon->query("SELECT * FROM courses WHERE id = '$filterCourse'")->num_rows > 0) {
        $paginationQuery .= " AND activities.course = '$filterCourse'";
    }

    if (!empty($filterYearLevel)) {
        $paginationQuery .= " AND activities.year_level = '$filterYearLevel'";
    }

    if (!empty($filterSemester)) {
        $paginationQuery .= " AND activities.term = '$filterSemester'";
    }

    if (!empty($filterCriteria)) {
        $paginationQuery .= " AND activities.type = '$filterCriteria'";
    }
}
$result = $dbCon->query($paginationQuery);
$activitiesCount = $result->fetch_all(MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// get all activities
if (!$hasFilter) {
    $query = "SELECT 
        activities.*,
        subjects.name AS subject_name,
        subjects.code AS subject_code,
        courses.course_code AS course_code,
        grading_criterias.criteria_name AS criteria_name
        FROM activities 
        INNER JOIN subjects ON activities.subject = subjects.id
        INNER JOIN courses ON activities.course = courses.id
        INNER JOIN school_year ON activities.school_year = school_year.id
        LEFT JOIN grading_criterias ON activities.type = grading_criterias.id
        WHERE activities.instructor = '" . AuthController::user()->id . "' AND activities.subject=$subjectId LIMIT $start, $limit";
} else {
    $query = "SELECT 
        activities.*,
        subjects.name AS subject_name,
        subjects.code AS subject_code,
        courses.course_code AS course_code,
        grading_criterias.criteria_name AS criteria_name
        FROM activities 
        INNER JOIN subjects ON activities.subject = subjects.id
        INNER JOIN courses ON activities.course = courses.id
        INNER JOIN school_year ON activities.school_year = school_year.id
        LEFT JOIN grading_criterias ON activities.type = grading_criterias.id
        WHERE activities.instructor = '" . AuthController::user()->id . "' AND activities.subject=$subjectId";

    if (!empty($filterCourse) && $dbCon->query("SELECT * FROM courses WHERE id = '$filterCourse'")->num_rows > 0) {
        $query .= " AND activities.course = '$filterCourse'";
    }

    if (!empty($filterYearLevel)) {
        $query .= " AND activities.year_level = '$filterYearLevel'";
    }

    if (!empty($filterSemester)) {
        $query .= " AND activities.term = '$filterSemester'";
    }

    if (!empty($filterCriteria)) {
        $query .= " AND activities.type = '$filterCriteria'";
    }

    $query .= " LIMIT $start, $limit";
}

// Count all subjects that the instructor is handling
$subjectsQuery = $dbCon->query("SELECT 
    subject_instructors.*,
    subjects.year_level as year_level,
    subjects.name as name,
    subjects.code as code,
    subjects.units as units,
    subjects.credits_units as credits_units,
    subjects.term as term,
    courses.course_code as course,
    courses.course_code as course_code
    FROM subject_instructors
    LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
    LEFT JOIN courses ON subjects.course = courses.id
    WHERE subject_instructors.instructor_id = " . AuthController::user()->id
);
$subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);
$subjectsCount = count($subjects);

if (count($subjects) == 0) {
    $hasWarning = true;
    $warning = "There are no subjects assigned to you. Contact your admins to have them assign you a subject.";
}

// fetch all grading criterias
$gradingCriteriasQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE instructor = " . AuthController::user()->id);
$gradingCriterias = $gradingCriteriasQuery->fetch_all(MYSQLI_ASSOC);

// fetch all school years
$schoolYearsQuery = "SELECT * FROM school_year";

// Fetch all of courses
$coursesQuery = $dbCon->query("SELECT * FROM courses");

// Fetch current subject
$currentSubjectQuery = $dbCon->query("SELECT subjects.*, courses.course_code AS course_code, courses.course AS course_name FROM subjects LEFT JOIN courses ON subjects.course = courses.id WHERE subjects.id = $subjectId");
$currentSubject = $currentSubjectQuery->fetch_assoc();
?>

<style>
    /* Style to hide number input arrows */
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<main class="h-screen overflow-x-auto flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class=" w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>
        
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center">
                <!-- Table Header -->
                <div class="flex flex-col justify-start md:justify-center gap-2">
                    <h1 class="text-[24px] font-semibold">Manage Activities</h1>
                    <h1 class="text-md font-semibold">Subject: (<?= $currentSubject['code'] ?>) <?= $currentSubject['name'] ?></h1>
                    <h1 class="text-md font-semibold">Course: (<?= $currentSubject['course_code'] ?>) <?= $currentSubject['course_name'] ?></h1>
                </div>

                <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                    <button class="btn" onclick="filters.showModal()"><i class="<?= $hasFilter ? 'bx bxs-filter-alt' : 'bx bx-filter-alt' ?>"></i> Filters</button>
                    <label for="submit-modal" class="btn" <?php if ($gradingCriteriasQuery->num_rows == 0 || count($subjects) == 0): ?> disabled <?php endif; ?>>Release Grades</label>
                    <a href="../manage-activity.php" class="btn btn-info"><i class="bx bxs-chevron-left"></i> Go Back</a>
                    <a onclick="create_activity_modal.showModal()" class="btn btn-success" <?php if ($gradingCriteriasQuery->num_rows == 0 || count($subjects) == 0): ?> disabled <?php endif; ?>><i class="bx bx-plus-circle"></i> Create</a>
                </div>
            </div>

            <?php if ($gradingCriteriasQuery->num_rows == 0) { ?>
                <div role="alert" class="alert alert-error mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="grid grid-cols-1 md:grid-cols-2 gap-2 w-full">
                        <span class='flex items-center'>Before you can create a new activity, you must first create your grading criteria.</span> 
                        <div class='flex w-full justify-end items-center'>
                            <a href='../manage-grading-criteria.php' class='btn btn'>
                                <i class='bx bx-plus-circle'></i> Create
                            </a>
                        </div>
                    </span>
                </div>
            <?php } ?>

            <?php if ($hasWarning) { ?>
                <div role="alert" class="alert alert-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span><?= $warning ?></span>
                </div>
            <?php } ?>

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

            <!-- Table Content -->
            <div class="overflow-x-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white text-center">Activity Name</td>
                            <td class="bg-slate-500 text-white text-center">Type of Activity</td>
                            <td class="bg-slate-500 text-white text-center">Year Level</td>
                            <td class="bg-slate-500 text-white text-center">Semester</td>
                            <td class="bg-slate-500 text-white text-center">Passing Rate</td>
                            <td class="bg-slate-500 text-white text-center">Max Score</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $activityResult = $dbCon->query($query); ?>
                        <?php if ($activityResult->num_rows > 0) : ?>
                            <?php while ($row = $activityResult->fetch_assoc()) : ?>
                                <tr>
                                    <td class="text-center"><?= $row['name'] ?></td>
                                    <td class="text-center"><?= $row['criteria_name'] ?></td>
                                    <td class="text-center"><?= $row['year_level'] ?></td>
                                    <td class="text-center"><?= $row['term'] ?></td>
                                    <td class="text-center"><?= $row['passing_rate'] * 100 ?>%</td>
                                    <td class="text-center"><?= $row['max_score'] ?></td>
                                    <td>
                                        <div class="flex justify-center items-center gap-2">
                                            <a class="btn btn-sm" href="./activity_scores.php?id=<?= $row['id'] ?>&subjectId=<?= $subjectId ?><?= $page > 1 ? '&page=' . $page : '' ?>">Scores</a>
                                            <label for="update-activity-<?= $row['id'] ?>" class="btn btn-info btn-sm">Edit</label>
                                            <label for="delete-activity-<?= $row['id'] ?>" class="btn btn-error btn-sm">Delete</label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td class="text-center" colspan="11">No activities to show</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center">
                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <?php $activityResult = $dbCon->query($query); ?>
    <?php while ($row = $activityResult->fetch_assoc()) : ?>
        <!-- Update Modal -->
        <input type="checkbox" id="update-activity-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <h3 class="text-lg font-semibold text-center">Update Activity</h3>

                <form class="flex flex-col gap-4 mt-4" method="post">
                    <input type="hidden" name="activity_id" value="<?= $row['id'] ?>">

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Activity Name</span>
                        <input class="input input-bordered" placeholder="Activity name" name="activity_name" value="<?= $row['name'] ?>" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                    </label>

                    <label class="flex flex-col col-span gap-2">
                        <span class="font-bold text-[18px]">Type of Activity</span>
                        <select class="select select-bordered" name="type" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?>>
                            <!--Display all the Course here-->
                            <option value="" selected disabled>Select Type</option>

                            <?php foreach ($gradingCriterias as $criteria) { ?>
                                <option value="<?= $criteria['id'] ?>" <?php if ($criteria['id'] == $row['type']): ?> selected <?php endif; ?>><?= $criteria['criteria_name'] ?></option>
                            <?php } ?>
                        </select>
                    </label>

                    <div class="grid md:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Passing Rate</span>
                            <input type="number" class="input input-bordered passing-rate" placeholder="EG: 10%" name="passing_rate" min="1" max="100" value="<?= ($row['passing_rate'] * 100) ?>" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Max Score</span>
                            <input type="number" class="input input-bordered" name="max_score" min="0" pattern="[0-9]+" value="<?= $row['max_score'] ?>" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                        </label>
                    </div>

                    <div class="flex justify-end items-center gap-4 mt-4">
                        <label class="btn" for="update-activity-<?= $row['id'] ?>">Cancel</label>
                        <button class="btn btn-info" name="update-activity">Update</button>
                    </div>
                </form>
            </div>
            <label class="modal-backdrop" for="update-activity-<?= $row['id'] ?>">Close</label>
        </div>
        
        <!-- Delete Modal -->
        <input type="checkbox" id="delete-activity-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Delete Activity</h3>
                <p class="py-4">Are you sure you want to delete this activity? This action cannot be undone.</p>

                <form class="flex justify-end gap-4 items-center" method="post">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <label class="btn" for="delete-activity-<?= $row['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete-activity">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-activity-<?= $row['id'] ?>">Close</label>
        </div>
    <?php endwhile; ?>

    <!-- Grade Release Request modal -->
    <input type="checkbox" id="submit-modal" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <h3 class="font-semibold text-center text-lg mb-4">Grade Release Request</h3>
            <form method="post" enctype="multipart/form-data">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Subject</span>
                    <select class="select select-bordered" name="subject" required>
                        <!-- Display all the subject related to the instructor -->
                        <option value="" selected disabled>Select Subject </option>

                        <?php foreach ($subjects as $subject) : ?>
                            <option value="<?= $subject['subject_id'] ?>">(<?= $subject['code'] ?>) <?= $subject['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="flex flex-col gap-2 my-4">
                    <span class="font-bold text-[18px]">Semester</span>
                    <select class="select select-bordered" name="term" required>
                        <!--Display all the Semister here-->
                        <option value="" selected disabled>Select Semester</option>
                        <option value="1st Sem">1st Sem</option>
                        <option value="2nd Sem">2nd Sem</option>
                        <option value="Midyear">Midyear</option>
                    </select>
                </label>

                <label class="flex flex-col gap-2 my-4">
                    <span class="font-bold text-[18px]">Grade Sheet (PDF File)</span>
                    <input type="file" name="file" class="file-input file-input-sm md:file-input-md file-input-bordered w-full" accept="application/pdf" required />
                    <div class="label">
                        <span class="label-text-alt text-error">Only <kbd class="p-1">*.pdf</kbd> files are allowed</span>
                    </div>
                </label>

                <div class="flex justify-end gap-4 items-center mt-4">
                    <label class="btn btn-error" for="submit-modal">Close</label>
                    <button class="btn btn-success" name="submit-release-grade-request">Submit Request</button>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="submit-modal">Close</label>
    </div>

    <!-- Filters Modal -->
    <dialog id="filters" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box">
            <h3 class="font-bold text-lg"><i class="bx bx-filter-alt"></i> Filters</h3>

            <form class="flex flex-col gap-4 mt-4" method="post">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Type of Activity</span>
                    <select class="select select-bordered" name="filter-criteria">
                        <option value="" selected disabled>Select type</option>

                        <?php foreach ($gradingCriterias as $criteria) : ?>
                            <option value="<?= $criteria['id'] ?>" <?php if ($hasFilter && $filterCriteria == $criteria['id']) : ?> selected <?php endif; ?>><?= $criteria['criteria_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <!-- <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Course</span>
                    <select class="select select-bordered" name="filter-course">
                        <option value="" selected disabled>Select course</option>

                        <?php while ($course = $coursesQuery->fetch_assoc()) : ?>

                            <option value="<?= $course['id'] ?>" <?php if ($hasFilter && $filterCourse == $course['id']) : ?> selected <?php endif; ?>><?= $course['course_code'] ?></option>

                        <?php endwhile; ?>
                    </select>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Year Level</span>
                    <select class="select select-bordered" name="filter-yearlevel">
                        <option value="" selected disabled>Select year level</option>

                        <option value="1st Year" <?php if ($hasFilter && $filterYearLevel == '1st Year') : ?> selected <?php endif; ?>>1st Year</option>
                        <option value="2nd Year" <?php if ($hasFilter && $filterYearLevel == '2nd Year') : ?> selected <?php endif; ?>>2nd Year</option>
                        <option value="3rd Year" <?php if ($hasFilter && $filterYearLevel == '3rd Year') : ?> selected <?php endif; ?>>3rd Year</option>
                        <option value="4th Year" <?php if ($hasFilter && $filterYearLevel == '4th Year') : ?> selected <?php endif; ?>>4th Year</option>
                        <option value="5th Year" <?php if ($hasFilter && $filterYearLevel == '5th Year') : ?> selected <?php endif; ?>>5th Year</option>
                    </select>
                </label> -->

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Semester</span>
                    <select class="select select-bordered" name="filter-semester">
                        <option value="" selected disabled>Select semester</option>

                        <option value="1st Sem" <?php if ($hasFilter && $filterSemester == '1st Sem') : ?> selected <?php endif; ?>>1st Semester</option>
                        <option value="2nd Sem" <?php if ($hasFilter && $filterSemester == '2nd Sem') : ?> selected <?php endif; ?>>2nd Semester</option>
                        <option value="Midyear" <?php if ($hasFilter && $filterSemester == 'Midyear') : ?> selected <?php endif; ?>>Midyear</option>
                    </select>
                </label>

                <div class="flex justify-end items-center gap-4 mt-4">
                    <button class="btn btn-error" name="clear-filter">Clear</button>
                    <button class="btn btn-success" name="apply-filter">Apply</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Create Activity Modal -->
    <dialog id="create_activity_modal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box">
            <h3 class="font-semibold text-lg text-center">Create Activity</h3>

            <form class="flex flex-col gap-4 mt-4" method="post">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Activity Name</span>
                    <input class="input input-bordered" placeholder="Activity name" name="activity_name" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                </label>

                <label class="flex flex-col col-span gap-2">
                    <span class="font-bold text-[18px]">Type of Activity</span>
                    <select class="select select-bordered" name="type" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?>>
                        <!--Display all the Course here-->
                        <option value="" selected disabled>Select Type</option>

                        <?php foreach ($gradingCriterias as $row) { ?>
                            <option value="<?= $row['id'] ?>"><?= $row['criteria_name'] ?></option>
                        <?php } ?>
                    </select>
                </label>

                <div class="grid md:grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Passing Rate</span>
                        <input type="number" class="input input-bordered passing-rate" placeholder="EG: 10%" name="passing_rate" min="1" max="100" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Max Score</span>
                        <input type="number" class="input input-bordered" name="max_score" min="0" value="1" pattern="[0-9]+" required <?php if ($gradingCriteriasQuery->num_rows == 0): ?> disabled <?php endif; ?> />
                    </label>
                </div>

                <div class="flex justify-end items-center gap-4 mt-4">
                    <button class="btn btn-error" type="button" onclick="create_activity_modal.close()">Cancel</button>
                    <button class="btn btn-success" name="create-activity">Create</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</main>

<script>
    document.querySelector("input[name='passing_rate']").addEventListener("input", function(e) {
        if (parseInt(e.target.value) > 100) {
            e.target.value = "100";
        } else if (parseInt(e.target.value) < 1) {
            e.target.value = "1";
        }
    })

    document.querySelector("input[name='max_score']").addEventListener("input", function(e) {
        if (parseInt(e.target.value) < 1) {
            e.target.value = "1";
        }
    })
</script>