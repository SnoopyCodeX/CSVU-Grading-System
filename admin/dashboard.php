<?php
session_start();
// kung walang session mag reredirect sa login //

require("../configuration/config.php");
require('../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
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
$result1 = $dbCon->query("SELECT count(id) AS id FROM ap_userdetails WHERE roles='student'");
$students = $result1->fetch_all(MYSQLI_ASSOC);
$total = $students[0]['id'];
$pages = ceil($total / $limit);

$query = "SELECT * FROM ap_userdetails WHERE roles='student' LIMIT $start, $limit";


$stundetQuery = $dbCon->query("SELECT * FROM ap_userdetails WHERE roles = 'student'");
$studentCount = $stundetQuery->num_rows;

$subjectQuery = $dbCon->query("SELECT * FROM ap_subjects");
$subjectCount = $subjectQuery->num_rows;

$sectionQuery = $dbCon->query("SELECT * FROM ap_sections");
$sectionCount = $sectionQuery->num_rows;

?>


<main class="h-screen flex overflow-hidden">
    <?php require_once("./layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("./layout/topbar.php") ?>

        <div class="stats shadow w-full mb-8">
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="stat-title">Students</div>
                <div class="stat-value"><?php echo $studentCount ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <div class="stat-title">Subjects</div>
                <div class="stat-value"><?php echo $subjectCount ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">Sections</div>
                <div class="stat-value">
                    <div class="stat-value"><?php echo $sectionCount ?></div>
                </div>
            </div>

        </div>
        </div>

        <div class="px-4 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-bold">Recent Applicant</h1>
                </div>
            </div>

            <!-- Table Content -->
            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 330px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white">ID</th>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Email</td>
                            <td class="bg-slate-500 text-white">Gender</td>
                            <td class="bg-slate-500 text-white">Contact</td>
                            <td class="bg-slate-500 text-white">Student ID</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $dbCon->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "
                                    <tr>
                                        <td>{$row['id']}</td>
                                        <td>{$row['firstName']} {$row['middleName']} {$row['lastName']}</th>
                                        <td>{$row['email']}</td>
                                        <td>" . ucfirst($row['gender']) . "</td>
                                        <td>{$row['contact']}</td>
                                        <td>{$row['sid']}</td>
                                        <td>
                                            <div class='flex gap-2 justify-center items-center'>
                                                <label for='view-student-{$row['id']}' class='btn btn-sm bg-blue-400 text-white'>View</label>
                                                <label for='edit-student-{$row['id']}' class='btn btn-sm bg-gray-400 text-white'>Edit</label>
                                                <label for='delete-student-{$row['id']}' class='btn btn-sm bg-red-400 text-white'>Delete</label>
                                            </div>
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "
                                <tr>
                                    <td colspan='7'>No records found</td>
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

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 >= $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
        </div>
    </section>

</main>