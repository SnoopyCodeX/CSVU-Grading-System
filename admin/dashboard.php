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

        <div class="stats shadow w-full mb-8">
            <div class="stat">
                <div class="stat-figure   text-[#276BAE]  ">
                    <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24'>
                        <title>user_2_fill</title>
                        <g id="user_2_fill" fill='none' fill-rule='nonzero'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M16 14a5 5 0 0 1 4.995 4.783L21 19v1a2 2 0 0 1-1.85 1.995L19 22H5a2 2 0 0 1-1.995-1.85L3 20v-1a5 5 0 0 1 4.783-4.995L8 14h8ZM12 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z' />
                        </g>
                    </svg>
                </div>
                <div class="stat-title">Total Students</div>
                <div class="stat-value"><?= humanizeNumber($studentCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure   text-[#276BAE]  ">
                    <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24'>
                        <title>notebook_fill</title>
                        <g id="notebook_fill" fill='none' fill-rule='nonzero'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M8 2v19H6c-1.054 0-2-.95-2-2V4c0-1.054.95-2 2-2h2Zm9 0c1.598 0 3 1.3 3 3v13c0 1.7-1.4 3-3 3h-7V2h7Z' />
                        </g>
                    </svg>
                </div>
                <div class="stat-title">Total Subjects</div>
                <div class="stat-value"><?= humanizeNumber($subjectCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure   text-[#276BAE]      ">
                    <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24'>
                        <title>book_2_fill</title>
                        <g id="book_2_fill" fill='none' fill-rule='evenodd'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M4 5a3 3 0 0 1 3-3h11a2 2 0 0 1 2 2v12.99c0 .168-.038.322-.113.472l-.545 1.09a1 1 0 0 0 0 .895l.543 1.088A1 1 0 0 1 19 22H7a3 3 0 0 1-3-3V5Zm3 13h10.408a3 3 0 0 0 0 2H7a1 1 0 1 1 0-2Zm3-11a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2h-4Z' />
                        </g>
                    </svg>
                </div>
                <div class="stat-title">Total Courses</div>
                <div class="stat-value"> <?= humanizeNumber($coursesCount) ?> </div>
            </div>
        </div>

        <div class="stats shadow w-full mb-8">
            <div class="stat">
                <div class="stat-figure   text-[#276BAE]  ">
                    <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24'>
                        <title>information_fill</title>
                        <g id="information_fill" fill='none' fill-rule='nonzero'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm-.01 8H11a1 1 0 0 0-.117 1.993L11 12v4.99c0 .52.394.95.9 1.004l.11.006h.49a1 1 0 0 0 .596-1.803L13 16.134V11.01c0-.52-.394-.95-.9-1.004L11.99 10ZM12 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z' />
                        </g>
                    </svg>
                </div>
                <div class="stat-title">Total 1st Years</div>
                <div class="stat-value"><?= humanizeNumber($firstYearCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure   text-[#276BAE]  ">
                    <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24'>
                        <title>information_fill</title>
                        <g id="information_fill" fill='none' fill-rule='nonzero'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm-.01 8H11a1 1 0 0 0-.117 1.993L11 12v4.99c0 .52.394.95.9 1.004l.11.006h.49a1 1 0 0 0 .596-1.803L13 16.134V11.01c0-.52-.394-.95-.9-1.004L11.99 10ZM12 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z' />
                        </g>
                    </svg>
                </div>
                <div class="stat-title">Total 2nd Years</div>
                <div class="stat-value"><?= humanizeNumber($secondYearCount) ?></div>
            </div>

            <div class="stat">
                <div class="stat-figure   text-[#276BAE]  ">
                    <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24'>
                        <title>information_fill</title>
                        <g id="information_fill" fill='none' fill-rule='nonzero'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm-.01 8H11a1 1 0 0 0-.117 1.993L11 12v4.99c0 .52.394.95.9 1.004l.11.006h.49a1 1 0 0 0 .596-1.803L13 16.134V11.01c0-.52-.394-.95-.9-1.004L11.99 10ZM12 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z' />
                        </g>
                    </svg>
                </div>
                <div class="stat-title">Total 3rd Years</div>
                <div class="stat-value"> <?= humanizeNumber($thirdYearCount) ?> </div>
            </div>

            <div class="stat">
                <div class="stat-figure   text-[#276BAE]  ">
                    <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24'>
                        <title>information_fill</title>
                        <g id="information_fill" fill='none' fill-rule='nonzero'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm-.01 8H11a1 1 0 0 0-.117 1.993L11 12v4.99c0 .52.394.95.9 1.004l.11.006h.49a1 1 0 0 0 .596-1.803L13 16.134V11.01c0-.52-.394-.95-.9-1.004L11.99 10ZM12 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z' />
                        </g>
                    </svg>
                </div>
                <div class="stat-title">Total 4th Years</div>
                <div class="stat-value"> <?= humanizeNumber($fourthYearCount) ?> </div>
            </div>


            <!-- <div class="stat">
                <div class="stat-figure   text-[#276BAE]  ">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div class="stat-title">Total 5th Years</div>
                <div class="stat-value"> <?= humanizeNumber($fifthYearCount) ?> </div>
            </div> -->
        </div>

        <div class="px-4 py-3 flex justify-between flex-col gap-4 ">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Recent Applicants</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 200px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <th class="bg-slate-500 text-white">ID</th> -->
                            <td class="bg-[#276BAE] text-white text-center">Student ID</td>
                            <td class="bg-[#276BAE] text-white text-center">Name</td>
                            <td class="bg-[#276BAE] text-white text-center">Email</td>
                            <td class="bg-[#276BAE] text-white text-center">Gender</td>
                            <td class="bg-[#276BAE] text-white text-center">Contact</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $dbCon->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "
                                    <tr class='hover'>
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
            <div class="flex justify-end items-center gap-4">
                <a class="bg-[#276bae] btn text-[24px] text-white" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>"
                    <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                        <title>left_fill</title>
                        <g id="left_fill" fill='none' fill-rule='evenodd'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M7.94 13.06a1.5 1.5 0 0 1 0-2.12l5.656-5.658a1.5 1.5 0 1 1 2.121 2.122L11.122 12l4.596 4.596a1.5 1.5 0 1 1-2.12 2.122L7.938 13.06Z' />
                        </g>
                    </svg>
                </a>

                <button class="btn bg-[#276bae] text-white" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="bg-[#276bae] btn text-[24px] text-white" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>"
                    <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                        <title>right_fill</title>
                        <g id="right_fill" fill='none' fill-rule='evenodd'>
                            <path
                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                            <path fill='currentColor'
                                d='M16.06 10.94a1.5 1.5 0 0 1 0 2.12l-5.656 5.658a1.5 1.5 0 1 1-2.121-2.122L12.879 12 8.283 7.404a1.5 1.5 0 0 1 2.12-2.122l5.658 5.657Z' />
                        </g>
                    </svg>
                </a>
            </div>
        </div>
    </section>

</main>