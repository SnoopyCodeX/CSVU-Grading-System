<?php
session_start();
require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

require_once("../../components/header.php");

$hasSuccess = false;
$hasError = false;
$hasSearch = false;
$message = "";

if(isset($_POST['search-name'])) {
    $search = $dbCon->real_escape_string($_POST['search-name']);
    $hasSearch = true;
}

if(isset($_POST['approve-request'])) {
    $id = $_POST['approve-request'];
    $result = mysqli_query($dbCon, "UPDATE grade_requests SET status = 'approved' WHERE id = $id");
    if($result) {
        $hasSuccess = true;
        $message = "Request has been approved successfully";
    } else {
        $hasError = true;
        $message = "An error occurred while approving the request";
    }
}

if(isset($_POST['reject-request'])) {
    $id = $_POST['reject-request'];
    $result = mysqli_query($dbCon, "UPDATE grade_requests SET status = 'rejected' WHERE id = $id");
    if($result) {
        $hasSuccess = true;
        $message = "Request has been rejected successfully";
    } else {
        $hasError = true;
        $message = "An error occurred while rejecting the request";
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if($hasSearch) {
    $result = mysqli_query($dbCon, "SELECT 
        COUNT(*) AS id 
        FROM grade_requests 
        LEFT JOIN userdetails ON grade_requests.student_id = userdetails.id
        WHERE CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%' OR userdetails.sid LIKE '%$search%' OR userdetails.email LIKE '%$search%'
        ORDER BY grade_requests.id DESC 
        LIMIT $start, $limit
    ");
} else {
    $result = mysqli_query($dbCon, "SELECT 
        COUNT(*) AS id 
        FROM grade_requests 
        ORDER BY grade_requests.id DESC 
        LIMIT $start, $limit
    ");
}
$total = mysqli_fetch_assoc($result);
$pages = ceil($total['id'] / $limit);

// get all grade requests joining student and subjects
if($hasSearch) {
    $gradeRequestsQuery = "SELECT 
        grade_requests.id AS id,
        grade_requests.student_id as student_id,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS name, 
        userdetails.sid AS studentID,
        userdetails.email AS studentEmail,
        userdetails.year_level, 
        sections.name AS section,
        school_year.semester AS term,
        courses.course_code AS course, 
        grade_requests.status AS status
        FROM grade_requests 
        JOIN userdetails ON grade_requests.student_id = userdetails.id 
        JOIN sections ON grade_requests.section_id = sections.id
        JOIN courses ON sections.course = courses.id
        JOIN school_year ON sections.school_year = school_year.id
        WHERE CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%' OR userdetails.sid LIKE '%$search%' OR userdetails.email LIKE '%$search%' ORDER BY grade_requests.id DESC LIMIT $start, $limit";
} else {
    $gradeRequestsQuery = "SELECT 
        grade_requests.id AS id,
        grade_requests.student_id as student_id,
        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS name, 
        userdetails.sid AS studentID,
        userdetails.email AS studentEmail,
        userdetails.year_level,
        sections.name AS section,
        school_year.semester AS term,
        courses.course_code AS course,
        grade_requests.status AS status
        FROM grade_requests 
        JOIN userdetails ON grade_requests.student_id = userdetails.id 
        JOIN sections ON grade_requests.section_id = sections.id
        JOIN courses ON sections.course = courses.id
        JOIN school_year ON sections.school_year = school_year.id
        ORDER BY grade_requests.id DESC LIMIT $start, $limit";
}
?>

<main class="w-screen h-screen overflow-auto md:grid grid-cols-[300px_auto] gap-[24px] ">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 w-full">

            <div class="flex flex-col md:flex-row justify-between items-center w-full">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Print Grade Requests</h1>
                </div>

                <div class="flex gap-4 px-4">
                    <!-- Search bar -->
                    <form class="w-[200px] md:w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" autocomplete="off">   
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" name="search-name" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search name, email or I.D." value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
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
            <div class="overflow-auto w-full border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols w-full">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white text-center">Student ID</th>
                            <th class="bg-slate-500 text-white text-center">Name</th>
                            <th class="bg-slate-500 text-white text-center">Email Address</th>
                            <th class="bg-slate-500 text-white text-center">Section</th>
                            <th class="bg-slate-500 text-white text-center">Course</th>
                            <th class="bg-slate-500 text-white text-center">Year Level</th>
                            <th class="bg-slate-500 text-white text-center">Term</th>
                            <th class="bg-slate-500 text-white text-center">Status</th>
                            <th class="bg-slate-500 text-white text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $result = $dbCon->query($gradeRequestsQuery); ?>
                        <?php if($result->num_rows > 0) : ?>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                                <?php 

                                // Get active school year
                                $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status = 'active'");
                                $schoolYear = $schoolYearQuery->fetch_assoc();

                                // Fetch all subjects from the student's course and year level
                                $subjectsQuery = $dbCon->query("SELECT
                                    student_enrolled_subjects.*,
                                    subjects.*
                                    FROM student_enrolled_subjects
                                    LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
                                    WHERE student_enrolled_subjects.student_id='{$row['student_id']}'
                                ");
                                $subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

                                // Fetch the final grades from the database
                                $finalGradesQuery = $dbCon->query("SELECT
                                    *
                                    FROM student_final_grades
                                    WHERE student='{$row['student_id']}' AND school_year='{$schoolYear['id']}' AND term='{$row['term']}'
                                ");
                                $finalGrades = $finalGradesQuery->fetch_all(MYSQLI_ASSOC);

                                // Get all grades
                                $grades = array_map(function($subject) use ($finalGrades) {
                                    $subjectGrade = null;
                                    
                                    foreach ($finalGrades as $finalGrade) {
                                        if ($finalGrade['subject'] == $subject['subject_id'])
                                            $subjectGrade = $finalGrade['grade'];
                                    }

                                    return $subjectGrade;
                                }, $subjects);

                                // Filter null grades
                                $grades = array_filter($grades, fn ($grade) => $grade != null);

                                $areAllGradesFromSubjectsReleased = count($grades) == count($subjects);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $row['studentID'] ?></td>
                                    <td class="text-center"><?= $row['name'] ?></td>
                                    <td class="text-center"><?= $row['studentEmail'] ?></td>
                                    <td class="text-center"><?= $row['course'] . " " . str_split($row['year_level'])[0] . "-" . $row['section'] ?></td>
                                    <td class="text-center"><?= $row['course'] ?></td>
                                    <td class="text-center"><?= $row['year_level'] ?></td>
                                    <td class="text-center"><?= $row['term'] ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'rejected' ? 'error' :  'success') ?> p-4">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="flex justify-center">
                                        <div class="flex !justify-start gap-2">
                                            <label class="btn btn-info text-white btn-sm" for="view-request-<?= $row['id'] ?>">
                                                <i class='bx bxs-show'></i>
                                            </label>

                                            <?php if($row['status'] == 'pending'): ?>
                                                <div class="<?= $areAllGradesFromSubjectsReleased ? '' : 'tooltip tooltip-left' ?>" data-tip="All grades have not yet been released">
                                                    <label class="btn text-white btn-sm <?= $areAllGradesFromSubjectsReleased ? 'btn-success' : 'btn-disabled' ?>" for="approve-request-<?= $row['id'] ?>">
                                                        <i class='bx bxs-like'></i>
                                                    </label>
                                                </div>

                                                <label class="btn btn-error text-white btn-sm" for="reject-request-<?= $row['id'] ?>">
                                                    <i class='bx bxs-dislike'></i>
                                                </label>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">No requests available</td>
                            </tr>
                        <?php endif; ?>
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
</main>

<?php $result = mysqli_query($dbCon, $gradeRequestsQuery); ?>
<?php if($result->num_rows > 0) : ?>
    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        
        <!-- View Request Modal -->
        <input type="checkbox" id="view-request-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <h3 class="text-lg font-bold">Print Request Details</h3>

                <!-- Display all rows in a disabled inputs -->
                <div class="grid grid-cols-2 gap-4 mb-2">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Name</span>
                        </label>
                        <input type="text" class="input input-bordered" value="<?= $row['name'] ?>" disabled>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Section</span>
                        </label>
                        <input type="text" class="input input-bordered" value="<?= $row['section'] ?>" disabled>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Course</span>
                        </label>
                        <input type="text" class="input input-bordered" value="<?= $row['course'] ?>" disabled>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Year Level</span>
                        </label>
                        <input type="text" class="input input-bordered" value="<?= $row['year_level'] ?>" disabled>
                    </div>


                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Term</span>
                        </label>
                        <input type="text" class="input input-bordered" value="<?= $row['term'] ?>" disabled>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Status</span>
                        </label>
                        <input type="text" class="input input-bordered" value="<?= $row['status'] ?>" disabled>
                    </div>
                </div>


                <div class="flex justify-end gap-4 items-center">
                    <label class="btn" for="view-request-<?= $row['id'] ?>">Close</label>
                </div>
            </div>
            <label class="modal-backdrop" for="view-request-<?= $row['id'] ?>">Close</label>
        </div>

        <!-- Approve Request Modal -->
        <input type="checkbox" id="approve-request-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-success">
                <h3 class="text-lg font-bold text-success">Approve Print Request</h3>
                <p class="py-4">Are you sure you want to approve this request? Approving this request will allow the student to download and print his/her grade sheet.</p>

                <form class="flex justify-end gap-4 items-center" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="approve-request" value="<?= $row['id'] ?>">

                    <label class="btn" for="approve-request-<?= $row['id'] ?>">Close</label>
                    <button class="btn btn-success">Approve</button>
                </form>
            </div>
            <label class="modal-backdrop" for="approve-request-<?= $row['id'] ?>">Close</label>
        </div>

        <!-- Reject Request Modal -->
        <input type="checkbox" id="reject-request-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error">
                <h3 class="text-lg font-bold text-error">Reject Print Request</h3>
                <p class="py-4">Are you sure you want to reject this request? Rejecting this request will not allow the student to download and print his/her grade sheet.</p>

                <form class="flex justify-end gap-4 items-center" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>"> 
                    <input type="hidden" name="reject-request" value="<?= $row['id'] ?>">

                    <label class="btn" for="reject-request-<?= $row['id'] ?>">Close</label>
                    <button class="btn btn-error">Reject</button>
                </form>
            </div>
            <label class="modal-backdrop" for="reject-request-<?= $row['id'] ?>">Close</label>
        </div>
    <?php endwhile; ?>
<?php endif; ?>