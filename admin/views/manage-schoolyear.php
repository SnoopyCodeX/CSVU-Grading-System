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

// error and success message handling //
$hasError = false;
$hasSuccess = false;
$message = "";

// update school year
if (isset($_POST['update_school_year'])) {
    $school_year = $dbCon->real_escape_string($_POST['school_year']);
    $id = $dbCon->real_escape_string($_POST['id']);

    if ($dbCon->query("SELECT * FROM ap_school_year WHERE school_year = '$school_year' AND id = '$id'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "School year already exists";
    } else {
        $sql = "UPDATE ap_school_year SET school_year = '$school_year' WHERE id = '$id'";
        $result = mysqli_query($dbCon, $sql);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "School year <strong>$school_year</strong> updated successfully";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Error updating school year <strong>$school_year</strong>";
        }
    }
}

// delete school year
if (isset($_POST['delete_school_year'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $sql = "DELETE FROM ap_school_year WHERE id = '$id'";
    $result = mysqli_query($dbCon, $sql);

    if ($result) {
        $hasError = false;
        $hasSuccess = true;
        $message = "School year <strong>$school_year</strong> deleted successfully";
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Error deleting school year <strong>$school_year</strong>";
    }
}

// reset school year
if (isset($_POST['reset_school_year'])) {
    $school_year = $dbCon->real_escape_string($_POST['school_year']);

    // check if school year exists only in ap_sections
    if ($dbCon->query("SELECT * FROM ap_sections WHERE school_year = '$school_year'")->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "School year <strong>$school_year</strong> cannot be reset because it is not being used.";
    } else {
        // delete records from ap_sections table with the school_year=$school_year
        $sql = "DELETE FROM ap_sections WHERE school_year = '$school_year'";
        $result = mysqli_query($dbCon, $sql);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "School year <strong>$school_year</strong> has been reset successfully";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Error resetting school year <strong>$school_year</strong>";
        }
    }
}

// pagination 
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result1 = $dbCon->query("SELECT count(id) AS id FROM ap_school_year");
$schoolYearCount = $result1->fetch_all(MYSQLI_ASSOC);
$total = $schoolYearCount[0]['id'];
$pages = ceil($total / $limit);

// get school year
$query = "SELECT * FROM ap_school_year LIMIT $start, $limit";
?>


<main class="overflow-hidden h-screen flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-bold">School Year</h1>
                </div>
                <div class="flex gap-4 flex-col md:flex-row">
                    <label for="reset-academic" class="btn btn-sm ">Reset School Year</label>
                    <a href="./create/academic-year.php" class="btn btn-sm ">Create</a>
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
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Academic Year</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $schoolYears = $dbCon->query($query); ?>
                        <?php while ($row = $schoolYears->fetch_assoc()) { ?>

                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <div class="badge p-4 bg-green-400 text-white font-semibold">
                                        <?= $row['school_year'] ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex justify-center gap-2">
                                        <label for="edit-school-year-<?= $row['id'] ?>" class="btn btn-sm bg-gray-400 text-white">Edit</label>
                                        <label for="delete-school-year-<?= $row['id'] ?>" class="btn btn-sm bg-red-400 text-white">Delete</label>
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
    <?php $schoolYears = $dbCon->query($query); ?>
    <?php while ($row = $schoolYears->fetch_assoc()) { ?>

        <!-- Edit modal -->
        <input type="checkbox" id="edit-school-year-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <!-- ID -->
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <!-- Name -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Edit School Year</span>
                        <select class="select select-bordered" name="school_year" required>
                            <option disabled selected>Select an option</option>
                            <?php
                            $existingSchoolYears = $dbCon->query("SELECT * FROM ap_school_year WHERE id != '$row[id]'");
                            while ($schoolYear = $existingSchoolYears->fetch_assoc()) {
                                echo "<option value='$schoolYear[school_year]'>$schoolYear[school_year]</option>";
                            }
                            ?>
                        </select>
                    </label>

                    <div class="flex gap-2 flex-col mt-4">
                        <button class="btn btn-success w-full" name="update_school_year">Update</button>
                        <label class="btn btn-error w-full" for="edit-school-year-<?= $row['id'] ?>">Close</label>
                    </div>
                </form>
            </div>
            <label class="modal-backdrop" for="edit-school-year-<?= $row['id'] ?>">Close</label>
        </div>

        <!-- Delete modal -->
        <input type="checkbox" id="delete-school-year-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <label class="btn" for="delete-school-year-<?= $row['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete_school-year">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-school-year-<?= $row['id'] ?>">Close</label>
        </div>

    <?php } ?>

    <!-- Reset modal -->
    <input type="checkbox" id="reset-academic" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <!-- Name -->
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px] text-error">Reset School Year</span>
                    <select class="select select-bordered" name="school_year" required>
                        <option disabled="disabled" selected="selected">Select an option</option>
                        <?php
                        $existingSchoolYears = $dbCon->query("SELECT * FROM ap_school_year");
                        while ($schoolYear = $existingSchoolYears->fetch_assoc()) {
                            echo "<option value='$schoolYear[school_year]'>$schoolYear[school_year]</option>";
                        }
                        ?>
                    </select>
                </label>

                <div class="flex gap-2 flex-col mt-4">
                    <button class="btn btn-error w-full" name="reset_school_year">Reset</button>
                    <label class="btn w-full" for="reset-academic">Close</label>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="reset-academic">Close</label>
    </div>

</main>