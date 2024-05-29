<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../components/header.php");

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
    <?php require_once ("../layout/sidebar.php") ?>
    <section class="w-full px-4">
        <?php require_once ("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage School Years</h1>
                </div>
                <div class="flex gap-4 flex-col md:flex-row">
                    <!-- <label for="reset-academic" class="btn btn-error"><i class="bx bx-reset"></i> Reset School Year</label> -->
                    <a href="./create/academic-year.php" class="btn bg-[#276bae] text-white">
                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>add_circle_fill</title>
                            <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                            </g>
                        </svg>

                        Create</a>
                </div>
            </div>

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

            <!-- Table Content -->
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-[#276bae] text-white text-center">Academic Year</td>
                            <td class="bg-[#276bae] text-white text-center">Semester</td>
                            <td class="bg-[#276bae] text-white text-center">Status</td>
                            <td class="bg-[#276bae] text-white text-center text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $schoolYears = $dbCon->query($query); ?>
                        <?php while ($row = $schoolYears->fetch_assoc()) { ?>

                        <tr class="hover">
                            <!-- <td><?= $row['id'] ?></td> -->
                            <td class="text-center"><?= $row['school_year'] ?></td>
                            <td class="text-center"><?= ucfirst($row['semester']) ?></td>
                            <td class="text-center">
                                <div
                                    class="badge p-4 <?= strtolower($row['status']) == 'active' ? 'bg-[#27ae60]/50 text-black' : 'bg-red-500/50 text-black' ?> text-white font-semibold">
                                    <?= ucfirst($row['status']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex justify-center gap-2">
                                    <label for="edit-school-year-<?= $row['id'] ?>"
                                        class="btn btn-sm bg-gray-500 text-white">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>edit_line</title>
                                            <g id="edit_line" fill='none' fill-rule='nonzero'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M13 3a1 1 0 0 1 .117 1.993L13 5H5v14h14v-8a1 1 0 0 1 1.993-.117L21 11v8a2 2 0 0 1-1.85 1.995L19 21H5a2 2 0 0 1-1.995-1.85L3 19V5a2 2 0 0 1 1.85-1.995L5 3h8Zm6.243.343a1 1 0 0 1 1.497 1.32l-.083.095-9.9 9.899a1 1 0 0 1-1.497-1.32l.083-.094 9.9-9.9Z' />
                                            </g>
                                        </svg>
                                        <span>
                                            Edit
                                        </span>
                                    </label>
                                    <label for="delete-school-year-<?= $row['id'] ?>"
                                        class="btn btn-sm bg-red-500 text-white">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24'>
                                            <title>delete_2_fill</title>
                                            <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                                <path
                                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                <path fill='currentColor'
                                                    d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                            </g>
                                        </svg>
                                        <span>
                                            Delete
                                        </span>
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center">
                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>"
                    <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>"
                    <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
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
                    <input x-mask="9999 - 9999" placeholder="<?= date('Y') ?> - <?= date('Y', strtotime('+ 1 year')) ?>"
                        name="school_year" class="input input-bordered" value="<?= $row['school_year'] ?>" required>
                </label>

                <label class="flex flex-col gap-2 mb-2">
                    <span class="font-bold text-[18px]">Semester</span>
                    <select class="select select-bordered" name="semester" required>
                        <option disabled selected>Select an option</option>
                        <option value="1st Sem" <?php if (strtolower($row['semester']) == '1st sem'): ?> selected
                            <?php endif; ?>>1st Semester</option>
                        <option value="2nd Sem" <?php if (strtolower($row['semester']) == '2nd sem'): ?> selected
                            <?php endif; ?>>2nd Semester</option>
                        <option value="Midyear" <?php if (strtolower($row['semester']) == 'midyear'): ?> selected
                            <?php endif; ?>>Midyear</option>
                    </select>
                </label>

                <label class="flex flex-col gap-2 mb-2">
                    <span class="font-bold text-[18px]">Edit Status</span>
                    <select class="select select-bordered" name="status" required>
                        <option disabled selected>Select an option</option>
                        <option value="active" <?php if (strtolower($row['status']) == 'active'): ?> selected
                            <?php endif; ?>>Active</option>
                        <option value="inactive" <?php if (strtolower($row['status']) == 'inactive'): ?> selected
                            <?php endif; ?>>Inactive</option>
                    </select>
                </label>

                <div class="flex gap-2 flex-col mt-4">
                    <button class="btn btn-[#27ae60] w-full" name="update_school_year">Update</button>
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
            <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information
                will permanently remove it from the system. Ensure that you have backed up any essential data before
                confirming.</p>

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