<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result2 = mysqli_query($dbCon, "SELECT COUNT(*) AS id FROM ap_sections WHERE instructor = " . AuthController::user()->id);
$sectionsCount = mysqli_fetch_array($result2);
$total = $sectionsCount['id'];
$pages = ceil($total / $limit);

// get all sections that the instructor is handling. the sections are in ap_sections table and has a column referencing to ap_userdetails table where the instructor's details are stored. use join to get the instructor's details.
$sectionQuery = "SELECT 
    ap_sections.*,
    ap_userdetails.firstName,
    ap_userdetails.lastName,
    ap_userdetails.middleName,
    ap_courses.course_code AS course_code
    FROM ap_sections 
    JOIN ap_userdetails ON ap_sections.instructor = ap_userdetails.id 
    JOIN ap_courses ON ap_sections.course = ap_courses.id
    WHERE ap_sections.instructor = " . AuthController::user()->id . " LIMIT $start, $limit";

require_once("../../components/header.php");
?>


<main class="flex overflow-hidden">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="h-screen w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Sections</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Term</td>
                            <td class="bg-slate-500 text-white">Students</td>
                            <td class="bg-slate-500 text-white">Course</td>
                            <td class="bg-slate-500 text-white">Instructor</td>
                            <td class="bg-slate-500 text-white">Status</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sectionQueryResult = $dbCon->query($sectionQuery);
                        while ($row = $sectionQueryResult->fetch_assoc()) {
                        ?>
                            <tr>
                                <th><?= $row['id'] ?></th>
                                <td><?= $row['name'] ?></td>
                                <td><?= $row['term'] ?></td>
                                <!-- Student Count -->
                                <td><?= $dbCon->query("SELECT COUNT(*) as count FROM ap_section_students JOIN ap_sections ON ap_section_students.section_id = ap_sections.id WHERE ap_section_students.section_id={$row['id']}")->fetch_assoc()['count'] ?></td>
                                <!-- Subject Name -->
                                <td><?= $row['course_code'] ?></td>
                                <!-- Instructor Name -->
                                <td>
                                    <?= $row['lastName'] ?>,
                                    <?= $row['firstName'] ?>
                                    <?= $row['middleName'] ?>
                                </td>
                                <!-- Status -->
                                <td>
                                    <span class='badge p-4 bg-blue-300 text-black'>
                                        On going
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
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
    </section>
</main>