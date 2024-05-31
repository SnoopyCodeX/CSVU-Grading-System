<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../vendor/autoload.php");
require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';
require('../../utils/grades.php');
require ('../../utils/files.php');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-type: application/json');

    if (isset($_GET['schoolYear']) && !empty('schoolYear')) {
        $schoolYearId = $dbCon->real_escape_string($_GET['schoolYear']);

        $releasedSubjectsQuery = $dbCon->query("SELECT
            subjects.code,
            subjects.name,
            subjects.id
            FROM instructor_grade_release_requests
            LEFT JOIN subjects ON instructor_grade_release_requests.subject_id = subjects.id
            WHERE instructor_grade_release_requests.instructor_id = " . AuthController::user()->id . " AND instructor_grade_release_requests.school_year = '{$schoolYearId}' AND instructor_grade_release_requests.status = 'grade-released'
        ");
        $releasedSubjects = $releasedSubjectsQuery->fetch_all(MYSQLI_ASSOC);

        echo json_encode($releasedSubjects, JSON_PRETTY_PRINT);
        exit;
    }

    echo json_encode([], JSON_PRETTY_PRINT);
    exit;
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Cancel request
if (isset($_POST['cancel-request'])) {
    $requestID = $dbCon->real_escape_string($_POST['request_id']);

    $checkRequestIfExistQuery = $dbCon->query("SELECT * FROM instructor_change_grade_request WHERE id = '$requestID'");

    if ($checkRequestIfExistQuery->num_rows > 0) {
        $currentDir = dirname($_SERVER['PHP_SELF']);
        $firstDir = explode("/", trim($currentDir, "/"));
        $requestData = $checkRequestIfExistQuery->fetch_assoc();

        $deleteRequestQuery = $dbCon->query("DELETE FROM instructor_change_grade_request WHERE id = '$requestID'");

        if ($deleteRequestQuery) {
            @unlink(str_repeat("../", count($firstDir) - 1) . "uploads/change_of_grade_request/" . $requestData['pdf_file']);

            $hasSuccess = true;
            $message = "Change of grade request has been successfully canceled!";
        } else {
            $hasError = true;
            $message = "Cancelation failed, an error occured during the cancelation process.";
        }
    } else {
        $hasError = true;
        $message = "Cancelation failed, change of grade request does not exist!";
    }
}

if (isset($_POST['request-change-grade'])) {
    $schoolYearId = $dbCon->real_escape_string($_POST['school_year']);
    $subjectId = $dbCon->real_escape_string($_POST['subject']);
    $pdfFile = $_FILES['file'];

    // Get file details
    $fileName = $pdfFile['name'];
    $fileTmpName = $pdfFile['tmp_name'];
    $fileSize = $pdfFile['size'];
    $fileError = $pdfFile['error'];
    $fileType = $pdfFile['type'];

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
                $newFileName = "Instructor Notarized COG Request " . date("Y-m-d-h-i-s") . ".pdf";
                $newFilePath = str_repeat("../", count($firstDir) - 1) . "uploads/change_of_grade_request/$newFileName";

                if (@move_uploaded_file($fileTmpName, $newFilePath)) {
                    // Fetch school year details
                    $schoolYearDataQuery = $dbCon->query("SELECT * FROM school_year WHERE id = $schoolYearId");
                    $schoolYearData = $schoolYearDataQuery->fetch_assoc();

                    // Fetch subject details
                    $subjectDataQuery = $dbCon->query("SELECT * FROM subjects WHERE id = $subjectId");
                    $subjectData = $subjectDataQuery->fetch_assoc();

                    // Query to check for already existing request that has a status of pending or approved
                    $checkRequestIfExistQuery = $dbCon->query("SELECT
                        *
                        FROM instructor_change_grade_request
                        WHERE instructor_id = " . AuthController::user()->id . " AND subject_id = '{$subjectId}' AND school_year = '{$schoolYearId}' AND status IN ('pending', 'approved')
                    ");

                    if ($checkRequestIfExistQuery->num_rows == 0) {

                        $checkForRejectedRequestQuery =  $dbCon->query("SELECT
                            *
                            FROM instructor_change_grade_request
                            WHERE instructor_id = " . AuthController::user()->id . " AND subject_id = '{$subjectId}' AND school_year = '{$schoolYearId}' AND status = 'rejected'
                        ");

                        if ($checkForRejectedRequestQuery->num_rows == 0) {

                            $createRequest = $dbCon->query("INSERT INTO instructor_change_grade_request(instructor_id, subject_id, school_year, pdf_file, token, status) VALUES (
                                '" . AuthController::user()->id . "',
                                '$subjectId',
                                '$schoolYearId',
                                '$newFileName',
                                '" . md5(uniqid("", true)) . "',
                                'pending'
                            )");

                            if ($createRequest) {
                                $hasSuccess = true;
                                $message = "Successfully sent change of grade request for <strong>($subjectData[code]) $subjectData[name]</strong> to the admin!";
                            } else {
                                $hasError = true;
                                $message = "An error occued while creating your request, please try again later.";
                            }

                        } else {
                            $hasError = true;
                            $message = "Failed to send change of grade request. Please <strong>cancel</strong> the <strong>rejected request</strong> that has the same specified <strong>school year & subject</strong>.";
                        }

                    } else {
                        $hasError = true;
                        $message = "Failed to send grade change request for <strong>($subjectData[code]) $subjectData[name]</strong> <strong>S.Y. $schoolYearData[school_year] @ $schoolYearData[semester]</strong> to the admin. You already have a pending request with the same school year and subject!";

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
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$paginationQuery = "SELECT COUNT(*) AS id FROM instructor_grade_release_requests WHERE instructor_id = '" . AuthController::user()->id . "'";
$result = $dbCon->query($paginationQuery);
$activitiesCount = $result->fetch_all(MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// Fetch requests
$gradeChangeRequestsQuery = $dbCon->query("SELECT 
    subjects.code,
    subjects.name,
    subjects.year_level,
    subjects.term,
    instructor_change_grade_request.id,
    instructor_change_grade_request.subject_id,
    instructor_change_grade_request.created_at,
    instructor_change_grade_request.approved_at,
    instructor_change_grade_request.token,
    instructor_change_grade_request.status
    FROM instructor_change_grade_request
    LEFT JOIN subjects ON instructor_change_grade_request.subject_id = subjects.id 
    WHERE instructor_change_grade_request.instructor_id = '" . AuthController::user()->id . "'
    ORDER BY instructor_change_grade_request.approved_at DESC, instructor_change_grade_request.created_at DESC
    LIMIT $start, $limit
");
$gradeChangeRequests = $gradeChangeRequestsQuery->fetch_all(MYSQLI_ASSOC);

?>

<style>
    /* Style to hide number input arrows */
    /* Chrome, Safari, Edge, Opera */
    input.percentage::-webkit-outer-spin-button,
    input.percentage::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input.percentage[type=number] {
        -moz-appearance: textfield;
    }
</style>

<main class="h-screen overflow-x-auto flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class=" w-full px-4">
        <?php require_once("../layout/topbar.php") ?>

        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-2">
                <!-- Table Header -->
                <div class="flex items-center">
                    <h1 class="text-[32px] font-bold">Request Change of Grade</h1>
                </div>

                <button class="btn bg-[#276bae] text-white w-full md:w-auto" onclick="new_request_modal.showModal()"><i class="bx bx-plus"></i> New Request</button>
            </div>

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
                        <tr class="hover">
                            <th class="bg-[#276bae] text-white text-center">Subject Code</td>
                            <th class="bg-[#276bae] text-white text-center">Subject Name</td>
                            <th class="bg-[#276bae] text-white text-center">Year Level</td>
                            <th class="bg-[#276bae] text-white text-center">Term</td>
                            <th class="bg-[#276bae] text-white text-center">Approved On</td>
                            <th class="bg-[#276bae] text-white text-center">Requested On</td>
                            <th class="bg-[#276bae] text-white text-center">Status</td>
                            <th class="bg-[#276bae] text-white text-center">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($gradeChangeRequests) > 0) : ?>
                            <?php foreach ($gradeChangeRequests as $gradeChangeRequest) : ?>
                                <tr class="hover">
                                    <td class="text-center"><?= $gradeChangeRequest['code'] ?></td>
                                    <td class="text-center"><?= $gradeChangeRequest['name'] ?></td>
                                    <td class="text-center"><?= $gradeChangeRequest['year_level'] ?></td>
                                    <td class="text-center"><?= $gradeChangeRequest['term'] ?></td>
                                    <td class="text-center"><?= $gradeChangeRequest['approved_at'] != null ? date("h:i A \\| F d, Y", strtotime($gradeChangeRequest['approved_at'])) : '' ?></td>
                                    <td class="text-center"><?= date("h:i A \\| F d, Y", strtotime($gradeChangeRequest['created_at'])) ?></td>
                                    <td class="text-center">
                                        <span class="badge p-4 <?= (($gradeChangeRequest['status'] == 'pending') ? 'badge-warning' : (($gradeChangeRequest['status'] == 'approved') ? 'badge-success' : (($gradeChangeRequest['status'] == 'grade-changed') ? 'badge-success' : 'badge-error'))) ?>"><?= ucwords(str_replace('-', ' ', $gradeChangeRequest['status'])) ?></span>
                                    </td>
                                    <td class="flex gap-2 items-center justify-center">
                                        <a class="btn btn-sm bg-[#276bae] text-white" href="./view/notarized-request.php?uid=<?= $gradeChangeRequest['token'] ?>">Notarized Request</a>

                                        <?php if (in_array($gradeChangeRequest['status'], ['pending', 'rejected'])): ?>
                                            <label class="btn btn-sm btn-error" for="cancel-request-<?= $gradeChangeRequest['id'] ?>">Cancel</label>
                                        <?php elseif ($gradeChangeRequest['status'] == 'approved'): ?>
                                            <a class="btn btn-sm bg-[#276bae] text-white" href="./view/activities.php?subjectId=<?= $gradeChangeRequest['subject_id'] ?>&action=edit&token=<?= md5($gradeChangeRequest['token']) ?>">Edit Grade</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr class="hover">
                                <td class="text-center" colspan="8">No grade requests to show</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end items-center gap-4 pb-4">
                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn bg-[#276bae] text-white" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <dialog id="new_request_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-center">Request Change of Grade</h3>
            <form class="flex flex-col py-4 gap-4" method="post" enctype="multipart/form-data" id="change_of_grade_form_request">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">School Year</span>
                    <select class="select select-bordered" name="school_year" required>
                        <option value="" selected disabled>Select School Year</option>

                        <?php
                        $schoolYearsQuery = $dbCon->query("SELECT * FROM school_year");
                        $schoolYears = $schoolYearsQuery->fetch_all(MYSQLI_ASSOC);
                        ?>

                        <?php foreach ($schoolYears as $schoolYear) : ?>
                            <option value="<?= $schoolYear['id'] ?>"><?= $schoolYear['school_year'] ?> @ <?= $schoolYear['semester'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Subject</span>
                    <select class="select select-bordered" name="subject" required disabled>
                        <option value="" selected disabled>Select Subject </option>
                    </select>
                </label>

                <label class="flex flex-col gap-2 my-4">
                    <span class="font-bold text-[18px]">Notarized Change of Grade Request (PDF File)</span>
                    <input type="file" name="file"
                        class="file-input file-input-sm md:file-input-md file-input-bordered w-full"
                        accept="application/pdf" required disabled />
                    <div class="label">
                        <span class="label-text-alt text-error">Only <kbd class="p-1">*.pdf</kbd> files are allowed</span>
                    </div>
                </label>

                <div class="flex justify-end items-center gap-4 mt-8">
                    <button type="reset" onclick="new_request_modal.close()" class="btn btn-error">Cancel</button>
                    <button class="btn bg-[#276bae] text-white" name="request-change-grade">Request</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <?php foreach ($gradeChangeRequests as $gradeChangeRequest) : ?>
        <!-- Delete Modal -->
        <input type="checkbox" id="cancel-request-<?= $gradeChangeRequest['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Cancel Request</h3>
                <p class="py-4">Are you sure you want to cancel this grade release request?</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="request_id" value="<?= $gradeChangeRequest['id'] ?>">

                    <label class="btn" for="cancel-request-<?= $gradeChangeRequest['id'] ?>">No</label>
                    <button class="btn btn-error" name="cancel-request">Yes, Cancel</button>
                </form>
            </div>
            <label class="modal-backdrop" for="cancel-request-<?= $gradeChangeRequest['id'] ?>">Close</label>
        </div>

    <?php endforeach; ?>
</main>

<script>
    document.querySelector("select[name='school_year']").addEventListener('change', function(e) {
        const selectedSchoolYearID = e.target.value;

        fetch(`<?= $_SERVER['PHP_SELF'] ?>?schoolYear=${selectedSchoolYearID}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(json => json.json())
            .then(response => {
                const subjectSelect = document.querySelector("select[name='subject']");
                const fileInput = document.querySelector("input[name='file']");

                if (response.length > 0) {
                    subjectSelect.innerHTML = "<option value='' selected disabled>Select Subject</option>";

                    for(let subject of response) {
                        let option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = `(${subject.code}) ${subject.name}`;

                        subjectSelect.appendChild(option);
                    }

                    subjectSelect.removeAttribute('disabled');
                    fileInput.removeAttribute('disabled');
                } else {
                    subjectSelect.innerHTML = `<option value='' selected disabled>No subjects released</option>`;
                    subjectSelect.setAttribute('disabled', true);
                    fileInput.setAttribute('disabled', true);
                }
            });
    });

    document.querySelector("form#change_of_grade_form_request").addEventListener('submit', function(e) {
        const schoolYearSelect = document.querySelector("select[name='school_year'");
        const subjectSelect = document.querySelector("select[name='subject']");
        const fileInput = document.querySelector("input[name='file']");

        if (!schoolYearSelect.value.trim() || !subjectSelect.value.trim() || fileInput.files.length === 0) {
            e.preventDefault();
        }
    });
</script>