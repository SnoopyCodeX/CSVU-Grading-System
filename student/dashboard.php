<?php
// session_start();
// kung walang session mag reredirect sa login //
session_start();
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/public";

require ("../configuration/config.php");
require '../auth/controller/auth.controller.php';

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

if (!AuthController::isAuthenticated()) {
    header("Location: {$rootFolder}/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../components/header.php");

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result2 = mysqli_query($dbCon, "SELECT count(*) AS id FROM activity_scores WHERE student_id = " . AuthController::user()->id);
$activitiesCount = mysqli_fetch_all($result2, MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// Get all activities
$activitiesQuery = "SELECT 
    activity_scores.*,
    subjects.name as subjectName,
    subjects.id as subjectId,
    activities.name as activityName,
    activities.max_score as activityMaxScore,
    activities.passing_rate as activityPassingRate,
    CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) as instructorName
    FROM activity_scores 
    INNER JOIN userdetails ON userdetails.id = activity_scores.instructor_id
    INNER JOIN activities ON activities.id = activity_scores.activity_id
    INNER JOIN subjects ON subjects.id = activities.subject
    WHERE activity_scores.student_id = " . AuthController::user()->id . " LIMIT $start, $limit";

$activitiesQueryResult = $dbCon->query($activitiesQuery);
?>


<main class="overflow-x-auto flex">
    <?php require_once ("layout/sidebar.php") ?>
    <section class="h-screen w-full px-4">
        <?php require_once ("layout/topbar.php") ?>

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

        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Activity Scores</h1>
                </div>

            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto md:overflow-x-hidden border border-gray-300 rounded-md"
                style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <td>ID</td> -->
                            <td class="bg-[#276bae] text-white text-center">Activity Name</td>
                            <td class="bg-[#276bae] text-white text-center">Term</td>
                            <td class="bg-[#276bae] text-white text-center">Subject</td>
                            <td class="bg-[#276bae] text-white text-center">Instructor</td>
                            <td class="bg-[#276bae] text-white text-center">Score</td>
                            <td class="bg-[#276bae] text-white text-center">Max Score</td>
                            <td class="bg-[#276bae] text-white text-center">Status</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($activitiesQueryResult->num_rows > 0): ?>
                            <?php while ($row = $activitiesQueryResult->fetch_assoc()) { ?>
                                <tr class="hover">
                                    <!-- <td><?= $row['id'] ?></td> -->
                                    <td class="text-center"><?= $row['activityName'] ?></td>
                                    <td class="text-center"><?= $row['term'] ?></td>
                                    <td class="text-center"><?= $row['subjectName'] ?></td>
                                    <td class="text-center"><?= $row['instructorName'] ?></td>
                                    <td class="text-center"><?= $row['score'] ?></td>
                                    <td class="text-center"><?= $row['activityMaxScore'] ?></td>
                                    <td class="text-center">
                                        <div
                                            class="badge p-4 text-base <?= ($row['score'] >= ($row['activityMaxScore'] * $row['activityPassingRate'])) ? "bg-green-400" : "bg-red-400" ?> text-black font-md">
                                            <?= ($row['score'] >= ($row['activityMaxScore'] * $row['activityPassingRate'])) ? "Passed" : "Failed" ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No activities found.</td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end items-center gap-4 pb-4">
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
</main>