<?php
session_start();
// kung walang session mag reredirect sa login //

require("../configuration/config.php");
require('../auth/controller/auth.controller.php');
require("../utils/humanizer.php");

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result1 = $dbCon->query("SELECT count(id) AS id FROM userdetails WHERE roles='student'");
$students = $result1->fetch_all(MYSQLI_ASSOC);
$total = $students[0]['id'];
$pages = ceil($total / $limit);

$query = "SELECT * FROM userdetails WHERE roles='student' LIMIT $start, $limit";

$studentQuery = $dbCon->query("SELECT * FROM userdetails WHERE roles = 'student'");
$studentCount = intval($studentQuery->num_rows);

$subjectQuery = $dbCon->query("SELECT * FROM subjects");
$subjectCount = intval($subjectQuery->num_rows);

$sectionQuery = $dbCon->query("SELECT * FROM sections");
$sectionCount = intval($sectionQuery->num_rows);

$coursesQuery = $dbCon->query("SELECT * FROM courses");
$coursesCount = intval($coursesQuery->num_rows);

$firstYears = $dbCon->query("SELECT * FROM userdetails WHERE roles = 'student' AND year_level = '1st Year'");
$firstYearCount = intval($firstYears->num_rows);

$secondYears = $dbCon->query("SELECT * FROM userdetails WHERE roles = 'student' AND year_level = '2nd Year'");
$secondYearCount = intval($secondYears->num_rows);

$thirdYears = $dbCon->query("SELECT * FROM userdetails WHERE roles = 'student' AND year_level = '3rd Year'");
$thirdYearCount = intval($thirdYears->num_rows);

$fourthYears = $dbCon->query("SELECT * FROM userdetails WHERE roles = 'student' AND year_level = '4th Year'");
$fourthYearCount = intval($fourthYears->num_rows);

$fifthYears = $dbCon->query("SELECT * FROM userdetails WHERE roles = 'student' AND year_level = '5th Year'");
$fifthYearCount = intval($fifthYears->num_rows);
?>


<main class="h-screen flex overflow-auto">
    <?php require_once("./layout/sidebar.php")  ?>
    <section class="w-screen h-screen px-4">
        <?php require_once("./layout/topbar.php") ?>

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

        <div class="stats shadow w-full mb-8">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-title">Total Students</div>
                <div class="stat-value"><?= humanizeNumber($studentCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <div class="stat-title">Total Subjects</div>
                <div class="stat-value"><?= humanizeNumber($subjectCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">Total Courses</div>
                <div class="stat-value"> <?= humanizeNumber($coursesCount) ?> </div>
            </div>
        </div>

        <div class="stats shadow w-full mb-8">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-title">Total 1st Years</div>
                <div class="stat-value"><?= humanizeNumber($firstYearCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <div class="stat-title">Total 2nd Years</div>
                <div class="stat-value"><?= humanizeNumber($secondYearCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">Total 3rd Years</div>
                <div class="stat-value"> <?= humanizeNumber($thirdYearCount) ?> </div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">Total 4th Years</div>
                <div class="stat-value"> <?= humanizeNumber($fourthYearCount) ?> </div>
            </div>


            <!-- <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">Total 5th Years</div>
                <div class="stat-value"> <?= humanizeNumber($fifthYearCount) ?> </div>
            </div> -->
        </div>

        <div class="px-4 py-3 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Recent Applicants</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 330px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <!-- <th class="bg-slate-500 text-white">ID</th> -->
                            <td class="bg-slate-500 text-white text-center">Student ID</td>
                            <td class="bg-slate-500 text-white text-center">Name</td>
                            <td class="bg-slate-500 text-white text-center">Email</td>
                            <td class="bg-slate-500 text-white text-center">Gender</td>
                            <td class="bg-slate-500 text-white text-center">Contact</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $dbCon->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "
                                    <tr>
                                        <td class='text-center'>{$row['sid']}</td>
                                        <td class='text-center'>{$row['firstName']} {$row['middleName']} {$row['lastName']}</th>
                                        <td class='text-center'>{$row['email']}</td>
                                        <td class='text-center'>" . ucfirst($row['gender']) . "</td>
                                        <td class='text-center'>{$row['contact']}</td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "
                                <tr>
                                    <td colspan='5' class='text-center'>No records found</td>
                                </tr>
                            ";
                        }

                        mysqli_free_result($result);
                        ?>
                        <tr>
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