<?php
// session_start();
// kung walang session mag reredirect sa login //
session_start();
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/public";

require("../configuration/config.php");
require '../auth/controller/auth.controller.php';



if (!AuthController::isAuthenticated()) {
    header("Location: {$rootFolder}/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../components/header.php");

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result2 = mysqli_query($dbCon, "SELECT count(*) AS id FROM ap_student_grades WHERE student_id = " . AuthController::user()->id);
$activitiesCount = mysqli_fetch_all($result2, MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// Get all activities
$activitiesQuery = "SELECT 
    ap_student_grades.*,
    ap_subjects.name as subjectName,
    ap_subjects.id as subjectId,
    ap_sections.name as sectionName,
    ap_activities.name as activityName,
    ap_activities.max_score as activityMaxScore,
    ap_activities.passing_rate as activityPassingRate,
    CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) as instructorName
    FROM ap_student_grades 
    INNER JOIN ap_userdetails ON ap_userdetails.id = ap_student_grades.instructor_id
    INNER JOIN ap_sections ON ap_sections.id = ap_student_grades.section_id
    INNER JOIN ap_activities ON ap_activities.id = ap_student_grades.activity_id
    INNER JOIN ap_subjects ON ap_subjects.id = ap_activities.subject
    WHERE ap_student_grades.student_id = " . AuthController::user()->id . " LIMIT $start, $limit";

$activitiesQueryResult = $dbCon->query($activitiesQuery);
?>


<main class="overflow-hidden flex">
    <?php require_once("layout/sidebar.php")  ?>
    <section class="h-screen w-full px-4">
        <?php require_once("layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Activities</h1>
                </div>

                <div class="flex gap-4">
                    <!-- <label class="flex flex-col gap-2">
                        <select class="select select-bordered" id="dashboard-term-dropdown">
                            <option value="" selected disabled>Select Term</option>
                            <option value="All">All</option>
                            <option value="1st Sem">1st Sem</option>
                            <option value="2nd Sem">2nd Sem</option>
                            <option value="3rd Sem">3rd Sem</option>
                        </select>
                    </label> -->
                </div>

            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td>ID</td>
                            <td>Name</td>
                            <td>Term</td>
                            <td>Subject</td>
                            <td>Instructor</td>
                            <td>Score</td>
                            <td>Status</td>
                            <td>Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($activitiesQueryResult->num_rows > 0) : ?>
                            <?php while ($row = $activitiesQueryResult->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= $row['activityName'] ?></td>
                                    <td><?= $row['term'] ?></td>
                                    <td><?= $row['subjectName'] ?></td>
                                    <td><?= $row['instructorName'] ?></td>
                                    <td><?= $row['grade'] ?></td>
                                    <td><?= $row['activityMaxScore'] ?></td>
                                    <td>
                                        <div class="badge p-4 text-base <?= ($row['grade'] >= ($row['activityMaxScore'] * $row['activityPassingRate'])) ? "bg-green-400" : "bg-red-400" ?> text-black font-md">
                                            Passed
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <button class="btn btn-sm">View</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="text-center">No activities found.</td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center">
                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn btn-sm" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 >= $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>
</main>