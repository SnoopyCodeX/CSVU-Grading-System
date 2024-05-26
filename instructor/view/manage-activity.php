<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../vendor/autoload.php");
require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';
require('../../utils/grades.php');
require('../../utils/files.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$hasWarning = false;
$hasSearch = false;
$warning = "";
$message = "";

if(isset($_POST['search-subject'])) {
    $hasSearch = true;
    $search = $_POST['search-subject'];
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

// Get all subjects that the instructor is handling
if($hasSearch) {
    $subjectsQuery = "SELECT
        subject_instructors.*,
        subjects.name as name,
        subjects.year_level as year_level,
        subjects.code as code,
        courses.course AS course,
        courses.course_code AS course_code
        FROM subject_instructors
        LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
        LEFT JOIN courses ON subjects.course = courses.id
        WHERE subject_instructors.instructor_id='" . AuthController::user()->id . "' AND subjects.name LIKE '%$search%'
    ";
} else {
    $subjectsQuery = "SELECT
        subject_instructors.*,
        subjects.name as name,
        subjects.year_level as year_level,
        subjects.code as code,
        courses.course AS course,
        courses.course_code AS course_code
        FROM subject_instructors
        LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
        LEFT JOIN courses ON subjects.course = courses.id
        WHERE subject_instructors.instructor_id='" . AuthController::user()->id . "'";
}

// fetch all grading criterias
$gradingCriteriasQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE instructor = " . AuthController::user()->id);

$subjectsResult = $dbCon->query($subjectsQuery);
$subjects = $subjectsResult->fetch_all(MYSQLI_ASSOC);

if (count($subjects) == 0) {
    $hasWarning = true;
    $warning = "There are no subjects assigned to you. Contact your admins to have them assign you a subject.";
}
?>


<main class="h-screen overflow-x-auto flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class=" w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage Activities (Select Subject)</h1>
                </div>

                <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto items-center">
                    <!-- Release Grades -->
                    <label for="submit-modal" class="btn w-full md:w-auto" <?php if ($gradingCriteriasQuery->num_rows == 0 || count($subjects) == 0): ?> disabled <?php endif; ?>>Release Grades</label>

                    <!-- Search bar -->
                    <form class="w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" autocomplete="off">   
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" name="search-subject" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search subject" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

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
            <div class=' overflow-hidden sm:pr-[48px] sm:grid sm:grid-cols-2 gap-4 md:grid-cols-2 lg:grid-cols-3 p-4 mt-8'>
                <?php if($subjectsResult->num_rows > 0): ?>
                    <?php foreach($subjects as $key => $subject): ?>
                        <a href="./view/activities.php?subjectId=<?= $subject['subject_id'] ?>" class="">
                            <div class='cursor-pointer hover:shadow-md h-[300px] rounded-[5px] rounded-[5px] border border-gray-400 flex justify-center items-center p-4 flex-col gap-2 mb-4'>
                                <h1 class='text-[32px] font-semibold text-center cursor-pointer'><?= $subject['name'] ?></h1> <!-- Section name -->
                                <span><?= $subject['course_code'] ?> (<?= $subject['year_level'] ?>)</span> <!-- Course code -->
                                <span><?= $subject['code'] ?></span> <!-- Subject code -->
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="flex justify-center items-center h-[300px] rounded-[5px] border border-gray-400 p-4 flex-col gap-2 mb-4">
                        <h1 class="text-[32px] font-semibold text-center">No subjects found</h1>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
</main>