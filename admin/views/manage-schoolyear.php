<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
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
    $semester = strtolower($dbCon->real_escape_string($_POST['semester']));
    $status = $dbCon->real_escape_string($_POST['status']);
    $id = $dbCon->real_escape_string($_POST['id']);

    if ($dbCon->query("SELECT * FROM school_year WHERE school_year = '$school_year' AND semester='$semester' AND id = '$id' AND status='$status'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "School year <strong>$school_year</strong>@<strong>$semester</strong> wasn't updated because nothing was changed.";
    } else {
        $hasActive = false;

        if ($status == 'active') {
            // check if there are other school_years that are active
            $checkActiveSchoolYear = $dbCon->query("SELECT * FROM school_year WHERE id <> $id AND status='active'");
    
            if ($checkActiveSchoolYear->num_rows > 0) {
                $hasError = true;
                $hasSuccess = false;
                $hasActive = true;
                $message = "Please disable other active school years before setting another school year as active";
            }
        }
        
        if (!$hasActive) {
            $sql = "UPDATE school_year SET school_year = '$school_year', status='$status' WHERE id = '$id'";
            $result = mysqli_query($dbCon, $sql);
    
            if ($result) {
                $hasError = false;
                $hasSuccess = true;
                $message = "School year <strong>$school_year</strong>@<strong>$semester</strong> updated successfully";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Error updating school year <strong>$school_year</strong>@<strong>$semester</strong>";
            }
        }
    }
}

// delete school year
if (isset($_POST['delete_school_year'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE id = '$id'");

    if ($schoolYearQuery->num_rows > 0) {
        $schoolYear = $schoolYearQuery->fetch_assoc();

        if ($schoolYear['status'] == 'inactive') {
            $sql = "DELETE FROM school_year WHERE id = '$id'";
            $result = $dbCon->query($sql);

            if ($result) {
                $hasError = false;
                $hasSuccess = true;
                $message = "School year <strong>$schoolYear[school_year]</strong>@<strong>$schoolYear[semester]</strong> has been deleted successfully!";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Error deleting school year <strong>$schoolYear[school_year]</strong>@<strong>$schoolYear[semester]</strong>";
            }
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Cannot delete <strong>$schoolYear[school_year]</strong>@<strong>$schoolYear[semester]</strong> because it is currently active";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "School year does not exist!";
    }
}

// reset school year
/* if (isset($_POST['reset_school_year'])) {
    $school_yearId = $dbCon->real_escape_string($_POST['school_year']);

    $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE id = $school_yearId");
    $schoolYear = $schoolYearQuery->fetch_assoc();

    $multiCheckQuery = "SELECT * FROM activities WHERE school_year = '$school_yearId';";
    $multiCheckQuery .= "SELECT * FROM sections WHERE school_year = '$school_yearId'";

    // check if school year exists only in sections
    if ($dbCon->multi_query($multiCheckQuery)) {
        $allUsed = 0;

        do {
            if ($result = $dbCon->store_result()) {
                if ($result->num_rows > 0) {
                    $allUsed += 1;
                }
            }
        } while ($dbCon->more_results() && $dbCon->next_result());

        if ($allUsed == 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "School year <strong>$schoolYear[school_year] @ $schoolYear[semester]</strong> has no data to be reset";
        } else {
            // First, delete all records in activities
            $deleteActivitiesQuery = "DELETE FROM activities WHERE school_year = '$school_yearId'";
            $deleteActivitiesResult = mysqli_query($dbCon, $deleteActivitiesQuery);

            // Get all section ids in the school year
            $getSectionIdsQuery = "SELECT id FROM sections WHERE school_year = '$school_yearId'";
            $sectionIds = $dbCon->query($getSectionIdsQuery);

            // store all section ids in an array
            $sectionIdsArray = [];
            while ($sectionId = $sectionIds->fetch_assoc()) {
                array_push($sectionIdsArray, $sectionId['id']);
            }

            // Loop through the section ids array and delete all data in student_final_grades, activity_scores using the section ids
            foreach ($sectionIdsArray as $sectionId) {
                $deleteStudentActivityScoresQuery = "DELETE FROM activity_scores WHERE section_id = '$sectionId'";
                $deleteStudentFinalGradesQuery = "DELETE FROM student_final_grades WHERE section = '$sectionId'";

                $deleteStudentFinalGradesResult = $dbCon->query($deleteStudentFinalGradesQuery);
                $deleteStudentActivityScoresResult = $dbCon->query($deleteStudentActivityScoresQuery);
            }

            // check if all queries are successful
            if ($deleteStudentFinalGradesResult && $deleteStudentActivityScoresResult) {
                $hasError = false;
                $hasSuccess = true;
                $message = "School year <strong>$schoolYear[school_year] @ $schoolYear[semester]</strong> has been reset successfully";
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Error resetting school year <strong>$schoolYear[school_year] @ $schoolYear[semester]</strong>";
            }
        } 
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Error resetting school year <strong>$schoolYear[school_year] @ $schoolYear[semester]</strong>";
    }
} */

// pagination 
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result1 = $dbCon->query("SELECT count(id) AS id FROM school_year");
$schoolYearCount = $result1->fetch_all(MYSQLI_ASSOC);
$total = $schoolYearCount[0]['id'];
$pages = ceil($total / $limit);

// get school year
$query = "SELECT * FROM school_year LIMIT $start, $limit";
?>


<main class="overflow-x-auto h-screen flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage School Years</h1>
                </div>
                <div class="flex gap-4 flex-col md:flex-row">
                    <!-- <label for="reset-academic" class="btn btn-error"><i class="bx bx-reset"></i> Reset School Year</label> -->
                    <a href="./create/academic-year.php" class="btn btn-success"><i class="bx bx-plus-circle"></i> Create</a>
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
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-slate-500 text-white text-center">Academic Year</td>
                            <td class="bg-slate-500 text-white text-center">Semester</td>
                            <td class="bg-slate-500 text-white text-center">Status</td>
                            <td class="bg-slate-500 text-white text-center text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $schoolYears = $dbCon->query($query); ?>
                        <?php while ($row = $schoolYears->fetch_assoc()) { ?>

                            <tr>
                                <!-- <td><?= $row['id'] ?></td> -->
                                <td class="text-center"><?= $row['school_year'] ?></td>
                                <td class="text-center"><?= ucfirst($row['semester']) ?></td>
                                <td class="text-center">
                                    <div class="badge p-4 <?= strtolower($row['status']) == 'active' ? 'bg-green-400' : 'bg-red-400' ?> text-white font-semibold">
                                        <?= ucfirst($row['status']) ?>
                                    </div>
                                </td>
                                <td class="text-center">
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

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
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
                    <label class="flex flex-col gap-2 mb-2" x-data>
                        <span class="font-bold text-[18px]">School Year</span>
                        <input x-mask="9999 - 9999" placeholder="<?= date('Y') ?> - <?= date('Y', strtotime('+ 1 year')) ?>" name="school_year" class="input input-bordered" value="<?= $row['school_year'] ?>" required>
                    </label>

                    <label class="flex flex-col gap-2 mb-2">
                        <span class="font-bold text-[18px]">Semester</span>
                        <select class="select select-bordered" name="semester" required>
                            <option disabled selected>Select an option</option>
                            <option value="1st Sem" <?php if(strtolower($row['semester']) == '1st sem'): ?> selected <?php endif; ?>>1st Semester</option>
                            <option value="2nd Sem" <?php if(strtolower($row['semester']) == '2nd sem'): ?> selected <?php endif; ?>>2nd Semester</option>
                            <option value="Midyear" <?php if(strtolower($row['semester']) == 'midyear'): ?> selected <?php endif; ?>>Midyear</option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2 mb-2">
                        <span class="font-bold text-[18px]">Edit Status</span>
                        <select class="select select-bordered" name="status" required>
                            <option disabled selected>Select an option</option>
                            <option value="active" <?php if(strtolower($row['status']) == 'active'): ?> selected  <?php endif; ?>>Active</option>
                            <option value="inactive" <?php if(strtolower($row['status']) == 'inactive'): ?> selected  <?php endif; ?>>Inactive</option>
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
                    <button class="btn btn-error" name="delete_school_year">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-school-year-<?= $row['id'] ?>">Close</label>
        </div>

    <?php } ?>

    <!-- Reset modal -->
    <!-- <input type="checkbox" id="reset-academic" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <form method="post" action="<?= "" //$_SERVER['PHP_SELF'] ?>">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px] text-error">Reset School Year</span>

                    <div role="alert" class="alert alert-warning mb-2 text-[14px]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>Resetting a school year means <strong>deleting all the data related to a specific school year.</strong> Examples of which are the <strong>activities, scores and computed grades</strong> that are made during the school year will be <strong>gone and will not be retrieved again</strong>. <br><br> Do you still wish to proceed?</span>
                    </div>

                    <select class="select select-bordered" name="school_year" required>
                        <option disabled="disabled" selected="selected">Select school year</option>
                        <?php
                        /*$existingSchoolYears = $dbCon->query("SELECT * FROM school_year ORDER BY school_year");
                        while ($schoolYear = $existingSchoolYears->fetch_assoc()) {
                            echo "<option value='$schoolYear[id]'>$schoolYear[school_year] @ $schoolYear[semester]</option>";
                        }*/
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
    </div> -->

</main>