<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';
require ('../../utils/grades.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$search = "";
$message = "";

// Search
if (isset($_POST['search-name'])) {
    $search = $dbCon->real_escape_string($_POST['search-name']);
    $hasSearch = true;
}

// Approve request
if (isset($_POST['approve-request'])) {
    $requestID = $dbCon->real_escape_string($_POST['request_id']);

    $checkRequestIfExistQuery = $dbCon->query("SELECT 
        subjects.*,
        instructor_change_grade_request.status,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS instructor_name
        FROM instructor_change_grade_request 
        LEFT JOIN subjects ON instructor_change_grade_request.subject_id = subjects.id 
        LEFT JOIN userdetails ON instructor_change_grade_request.instructor_id = userdetails.id
        WHERE instructor_change_grade_request.id = '$requestID'
    ");

    if ($checkRequestIfExistQuery->num_rows > 0) {
        $requestData = $checkRequestIfExistQuery->fetch_assoc();
        
        if ($requestData['status'] == 'pending') {
            $approveRequestQuery = $dbCon->query("UPDATE instructor_change_grade_request SET status='approved', approved_at = NOW() WHERE id = '$requestID'");

            if ($approveRequestQuery) {
                $hasError = false;
                $hasSuccess = true;
                $message = "Change of grade request for <strong>($requestData[code]) $requestData[name]</strong> by <strong>Prof. $requestData[instructor_name]</strong> has been approved!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "An error occured while approving the change of grade request for <strong>($requestData[code]) $requestData[name]</strong> by <strong>Prof. $requestData[instructor_name]</strong>";
            }
        } else if ($requestData['status'] == 'approved') {
            $hasError = true;
            $message = "Approval failed, change of grade request has already been approved!";
        }
    } else {
        $hasError = true;
        $message = "Approval failed, change of grade request does not exist!";
    }
}

// Reject request
if (isset($_POST['reject-request'])) {
    $requestID = $dbCon->real_escape_string($_POST['request_id']);

    $checkRequestIfExistQuery = $dbCon->query("SELECT 
        subjects.*,
        instructor_change_grade_request.status,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS instructor_name
        FROM instructor_change_grade_request 
        LEFT JOIN subjects ON instructor_change_grade_request.subject_id = subjects.id 
        LEFT JOIN userdetails ON instructor_change_grade_request.instructor_id = userdetails.id
        WHERE instructor_change_grade_request.id = '$requestID'
    ");

    if ($checkRequestIfExistQuery->num_rows > 0) {
        $requestData = $checkRequestIfExistQuery->fetch_assoc();
        
        if ($requestData['status'] == 'pending') {
            $approveRequestQuery = $dbCon->query("UPDATE instructor_change_grade_request SET status='rejected' WHERE id = '$requestID'");

            if ($approveRequestQuery) {
                $hasError = false;
                $hasSuccess = true;
                $message = "Change of grade request for <strong>($requestData[code]) $requestData[name]</strong> by <strong>Prof. $requestData[instructor_name]</strong> has been rejected!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "An error occured while rejecting the change of graderequest for <strong>($requestData[code]) $requestData[name]</strong> by <strong>Prof. $requestData[instructor_name]</strong>";
            }
        } else if ($requestData['status'] == 'rejected') {
            $hasError = true;
            $message = "Rejection failed, change of grade request has already been rejected!";
        }
    } else {
        $hasError = true;
        $message = "Rejection failed, change of graderequest does not exist!";
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if ($hasSearch) {
    $paginationQuery = "SELECT 
        COUNT(*) AS id
        FROM instructor_change_grade_request
        LEFT JOIN userdetails ON instructor_change_grade_request.instructor_id = userdetails.id
        WHERE CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%'
        ORDER BY instructor_change_grade_request.updated_at DESC
    ";
} else {
    $paginationQuery = "SELECT COUNT(*) AS id FROM instructor_change_grade_request";
}
$result = $dbCon->query($paginationQuery);
$activitiesCount = $result->fetch_all(MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// Fetch requests
if ($hasSearch) {
    $changeGradeRequestsQuery = $dbCon->query("SELECT 
        subjects.*,
        school_year.*,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS instructor_name,
        instructor_change_grade_request.subject_id AS request_subject,
        instructor_change_grade_request.id AS request_id,
        instructor_change_grade_request.token AS request_token,
        instructor_change_grade_request.status AS request_status,
        instructor_change_grade_request.created_at AS request_created_at,
        instructor_change_grade_request.approved_at AS request_approved_at
        FROM instructor_change_grade_request
        LEFT JOIN subjects ON instructor_change_grade_request.subject_id = subjects.id 
        LEFT JOIN school_year ON instructor_change_grade_request.school_year = school_year.id
        LEFT JOIN userdetails ON instructor_change_grade_request.instructor_id = userdetails.id
        WHERE CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%'
        ORDER BY instructor_change_grade_request.approved_at DESC, instructor_change_grade_request.created_at DESC
        LIMIT $start, $limit
    ");
} else {
    $changeGradeRequestsQuery = $dbCon->query("SELECT 
        subjects.*,
        school_year.*,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS instructor_name,
        instructor_change_grade_request.subject_id AS request_subject,
        instructor_change_grade_request.id AS request_id,
        instructor_change_grade_request.token AS request_token,
        instructor_change_grade_request.status AS request_status,
        instructor_change_grade_request.created_at AS request_created_at,
        instructor_change_grade_request.approved_at AS request_approved_at
        FROM instructor_change_grade_request
        LEFT JOIN subjects ON instructor_change_grade_request.subject_id = subjects.id 
        LEFT JOIN school_year ON instructor_change_grade_request.school_year = school_year.id
        LEFT JOIN userdetails ON instructor_change_grade_request.instructor_id = userdetails.id
        ORDER BY instructor_change_grade_request.approved_at DESC, instructor_change_grade_request.created_at DESC
        LIMIT $start, $limit
    ");
}
$changeGradeRequests = $changeGradeRequestsQuery->fetch_all(MYSQLI_ASSOC);
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
    <?php require_once ("../layout/sidebar.php") ?>
    <section class=" w-full px-4">
        <?php require_once ("../layout/topbar.php") ?>

        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center w-full">
                <!-- Table Header -->
                <div class="flex md:items-center w-full md:w-auto">
                    <h1 class="text-[24px] font-semibold">Manage Change of Grade Requests</h1>
                </div>

                <div class="flex gap-4 md:px-4 w-full md:w-auto">
                    <!-- Search bar -->
                    <form class="w-full md:w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>"
                        autocomplete="off">
                        <label for="default-search"
                            class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="search" name="search-name" id="default-search"
                                class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Search instructor" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit"
                                class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

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

            <!-- Table Content -->
            <div class="overflow-x-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <td class="bg-[#276bae] text-white text-center">Subject Code</td>
                            <td class="bg-[#276bae] text-white text-center">Subject Name</td>
                            <td class="bg-[#276bae] text-white text-center">Instructor</td>
                            <td class="bg-[#276bae] text-white text-center">Approved On</td>
                            <td class="bg-[#276bae] text-white text-center">Requested On</td>
                            <td class="bg-[#276bae] text-white text-center">Status</td>
                            <td class="bg-[#276bae] text-white text-center">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($changeGradeRequests) > 0): ?>
                            <?php foreach ($changeGradeRequests as $changeGradeRequest): ?>
                                <tr class="hover">
                                    <td class="text-center"><?= $changeGradeRequest['code'] ?></td>
                                    <td class="text-center"><?= $changeGradeRequest['name'] ?></td>
                                    <td class="text-center"><?= $changeGradeRequest['instructor_name'] ?></td>
                                    <td class="text-center">
                                        <?= $changeGradeRequest['request_approved_at'] == null ? '' : date("h:i A \\| F d, Y", strtotime($changeGradeRequest['request_approved_at'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= date("h:i A \\| F d, Y", strtotime($changeGradeRequest['request_created_at'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge p-4 <?= (($changeGradeRequest['request_status'] == 'pending') ? 'badge-warning' : (($changeGradeRequest['request_status'] == 'approved') ? 'bg-[#27ae60] text-black' : (($changeGradeRequest['request_status'] == 'grade-changed') ? 'badge-success' : 'badge-error'))) ?>"><?= $changeGradeRequest['request_status'] != 'grade-changed' ? ucfirst($changeGradeRequest['request_status']) : 'Grade Changed' ?></span>
                                    </td>
                                    <td class="flex gap-2 items-center justify-center">
                                        <a class="btn btn-sm bg-[#276bae] text-white"
                                            href="./view/notarized-request.php?uid=<?= $changeGradeRequest['request_token'] ?>"
                                            target="_blank">
                                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                                <title>document_2_fill</title>
                                                <g id="document_2_fill" fill='none' fill-rule='evenodd'>
                                                    <path
                                                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                    <path fill='currentColor'
                                                        d='M12 2v6.5a1.5 1.5 0 0 0 1.5 1.5H20v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h6Zm3 13H9a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2Zm-5-4H9a1 1 0 1 0 0 2h1a1 1 0 1 0 0-2Zm4-8.957a2 2 0 0 1 1 .543L19.414 7a2 2 0 0 1 .543 1H14Z' />
                                                </g>
                                            </svg>

                                            Notarized Request
                                        </a>
                                        <?php if (in_array($changeGradeRequest['request_status'], ['pending'])): ?>
                                            <label class="btn btn-sm btn-success"
                                                for="approve-request-<?= $changeGradeRequest['request_id'] ?>">
                                                <i class="fa fa-thumbs-up"></i>
                                            </label>
                                            <label class="btn btn-sm btn-error"
                                                for="reject-request-<?= $changeGradeRequest['request_id'] ?>">
                                                <i class="fa fa-thumbs-down"></i>
                                            </label>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="hover">
                                <td class="text-center" colspan="8">No grade requests to show</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end items-center gap-4">
                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>"
                    <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn bg-[#276bae] text-white" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>"
                    <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <?php foreach ($changeGradeRequests as $changeGradeRequest): ?>
        <!-- Approve Request Modal -->
        <input type="checkbox" id="approve-request-<?= $changeGradeRequest['request_id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-success border-2">
                <h3 class="text-lg font-bold text-success">Approve Request</h3>
                <p class="py-4">Are you sure you want to approve this grade release request?</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="request_id" value="<?= $changeGradeRequest['request_id'] ?>">

                    <label class="btn" for="approve-request-<?= $changeGradeRequest['request_id'] ?>">No</label>
                    <button class="btn btn-success" name="approve-request">Yes, Approve</button>
                </form>
            </div>
            <label class="modal-backdrop" for="approve-request-<?= $changeGradeRequest['request_id'] ?>">Close</label>
        </div>

        <!-- Reject Request Modal -->
        <input type="checkbox" id="reject-request-<?= $changeGradeRequest['request_id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Reject Request</h3>
                <p class="py-4">Are you sure you want to reject this grade release request?</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="request_id" value="<?= $changeGradeRequest['request_id'] ?>">

                    <label class="btn" for="reject-request-<?= $changeGradeRequest['request_id'] ?>">No</label>
                    <button class="btn btn-error" name="reject-request">Yes, Reject</button>
                </form>
            </div>
            <label class="modal-backdrop" for="reject-request-<?= $changeGradeRequest['request_id'] ?>">Close</label>
        </div>
    <?php endforeach; ?>
</main>