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
require_once("../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result2 = mysqli_query($dbCon, "SELECT COUNT(*) AS id FROM ap_sections");
$sectionsCount = mysqli_fetch_array($result2);
$total = $sectionsCount['id'];
$pages = ceil($total / $limit);

// fetch all sections
$sectionsQuery = "SELECT 
    ap_sections.id,
    ap_sections.name AS name,
    ap_subjects.name AS subject,
    ap_school_year.school_year AS school_year,
    ap_sections.term AS term,
    ap_sections.year_level AS year_level,
    CONCAT(ap_userdetails.firstName, ' ', ap_userdetails.middleName, ' ', ap_userdetails.lastName) AS instructor
    FROM ap_sections 
    INNER JOIN ap_subjects ON ap_sections.subject = ap_subjects.id
    INNER JOIN ap_school_year ON ap_sections.school_year = ap_school_year.id
    INNER JOIN ap_userdetails ON ap_sections.instructor = ap_userdetails.id
    LIMIT $start, $limit
";
?>


<main class="overflow-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4 h-screen">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Sections</h1>
                </div>
                <a href="./create/sections.php" class="btn">Create</a>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Subject</td>
                            <td class="bg-slate-500 text-white">School Year</td>
                            <td class="bg-slate-500 text-white">Term</td>
                            <td class="bg-slate-500 text-white">Year Level</td>
                            <td class="bg-slate-500 text-white">Instructor</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sections = $dbCon->query($sectionsQuery); ?>
                        <?php while ($section = $sections->fetch_assoc()) { ?>

                            <tr>
                                <th><?= $section['id'] ?></th>
                                <td><?= $section['name'] ?></td>
                                <td><?= $section['subject'] ?></td>
                                <td><?= $section['school_year'] ?></td>
                                <td><?= $section['term'] ?></td>
                                <td><?= $section['year_level'] ?></td>
                                <td><?= $section['instructor'] ?></td>
                                <td>
                                    <div class="flex justify-center items-center gap-2">
                                        <a class="btn btn-sm" href="./view/section.php?id=<?= $section['id'] ?>">View</a>
                                        <a class="btn btn-sm" href="./update/section.php?id=<?= $section['id'] ?>">Edit</a>
                                        <label for="delete-section-<?= $section['id'] ?>" class="btn btn-sm">Delete</label>
                                    </div>
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

    <!-- Modals -->
    <?php $sections = $dbCon->query($sectionsQuery); ?>
    <?php while ($section = $sections->fetch_assoc()) { ?>

        <!-- Delete Modal -->
        <input type="checkbox" id="delete-section-<?= $section['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="id" value="<?= $section['id'] ?>">

                    <label class="btn" for="delete-section-<?= $section['id'] ?>">Close</label>
                    <button class="btn btn-error">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-section-<?= $section['id'] ?>">Close</label>
        </div>

    <?php } ?>
</main>