<?php
// session_start();
// kung walang session mag reredirect sa login //
session_start();
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/public";

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

if (!AuthController::isAuthenticated()) {
    header("Location: {$rootFolder}/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result2 = mysqli_query($dbCon, "SELECT count(*) AS id FROM student_enrolled_subjects WHERE student_id = " . AuthController::user()->id);
$activitiesCount = mysqli_fetch_all($result2, MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// Get all subjects
$subjectsQuery = "SELECT
    student_enrolled_subjects.*,
    subjects.*
    FROM student_enrolled_subjects
    LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
    WHERE student_enrolled_subjects.student_id = " . AuthController::user()->id . " LIMIT $start, $limit";
$subjectsQueryResult = $dbCon->query($subjectsQuery);
?>


<main class="overflow-x-auto md:overflow-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="h-screen w-full px-4">
        <?php require_once("../layout/topbar.php") ?>

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

        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Enrolled Subjects</h1>
                </div>

            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto md:overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="text-center">Subject Code</td>
                            <td class="text-center">Subject Name</td>
                            <td class="text-center">Units</td>
                            <td class="text-center">Credit Units</td>
                            <td class="text-center">Year Level</td>
                            <td class="text-center">Term</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($subjectsQueryResult->num_rows > 0) : ?>
                            <?php while ($row = $subjectsQueryResult->fetch_assoc()) { ?>
                                <tr>
                                    <td class="text-center"><?= $row['code'] ?></td>
                                    <td class="text-center"><?= $row['name'] ?></td>
                                    <td class="text-center"><?= $row['units'] ?></td>
                                    <td class="text-center"><?= $row['credits_units'] ?></td>
                                    <td class="text-center"><?= $row['year_level'] ?></td>
                                    <td class="text-center"><?= $row['term'] ?></td>
                                </tr>
                            <?php } ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-center">No subjects enrolled.</td>
                            </tr>
                        <?php endif ?>
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
</main>