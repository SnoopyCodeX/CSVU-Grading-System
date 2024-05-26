<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';
require('../../utils/grades.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Create criteria
if (isset($_POST['create'])) {
    $name = $dbCon->real_escape_string($_POST['criteria_name']);
    $percentage = intval($dbCon->real_escape_string($_POST['criteria_percentage'])) / 100;

    if ($percentage <= 0 || $percentage > 1) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid percentage for the criteria! Must be in the range of <strong>1% - 100%</strong>.";
    } else {
        // Check if criteria name already exists
        $checkNameQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE criteria_name='$name' AND instructor='" . AuthController::user()->id . "'");

        if ($checkNameQuery->num_rows > 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Grading criteria '<strong>$name</strong>' already exists in your criterias!";
        } else {
            // Fetch all criterias and sum up all the fetched criterias
            $criteriasSumQuery = $dbCon->query("SELECT SUM(percentage) AS sum FROM grading_criterias WHERE instructor='" . AuthController::user()->id . "'");
            $criteriasSum = $criteriasSumQuery->fetch_assoc()['sum'] ?? 0;

            // If sum is already 100%, the instructor should no longer be able to add new criteria
            if ($criteriasSum == 1) {
                $hasError = true;
                $hasSuccess = false;
                $message = "The sum of all your grading criterias has already reached <strong>100%!</strong> Please <strong>edit</strong> or <strong>delete</strong> other criterias to add more.";
            } else if (($criteriasSum + $percentage) > 1) {
                $hasError = true;
                $hasSuccess = false;
                $message = "The percentage for '<strong>$name</strong>' criteria that you want to create is too big. If added, your total grading criteria percentage will exceed <strong>100%</strong>";
            } else {
                $newCriteriaQuery = $dbCon->query("INSERT INTO grading_criterias(criteria_name, percentage, instructor) VALUES(
                    '$name',
                    '$percentage',
                    '" . AuthController::user()->id . "'
                )");

                if ($newCriteriaQuery) {
                    $hasSuccess = true;
                    $hasError = false;
                    $message = "Successfully created '<strong>$name</strong>' criteria!";
                } else {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "An error occured while creating a new criteria.";
                }
            }
        }
    }
}

// Delete grading criteria
if (isset($_POST['delete-criteria'])) {
    $id = $dbCon->real_escape_string($_POST['criteria_id']);

    // Check if id exists
    $criteriaIdExistsQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE id='$id' AND instructor='" . AuthController::user()->id . "'");

    if ($criteriaIdExistsQuery->num_rows > 0) {
        // Delete criteria
        $deleteCriteriaQuery = $dbCon->query("DELETE FROM grading_criterias WHERE id='$id' AND instructor='" . AuthController::user()->id . "'");
        $criteriaDeletedData = $criteriaIdExistsQuery->fetch_assoc();

        if ($deleteCriteriaQuery) {
            $hasSuccess = true;
            $hasError = false;
            $message = "Successfully deleted '<strong>{$criteriaDeletedData['criteria_name']}</strong>' from your grading criterias!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Failed to delete '<strong>{$criteriaDeletedData['criteria_name']}</strong>' from your grading criterias!";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Grading criteria does not exist anymore. Refresh the page if you're still seeing an already deleted criteria.";
    }
}

// Edit grading criteria
if (isset($_POST['update-criteria'])) {
    $id = $dbCon->real_escape_string($_POST['criteria_id']);
    $name = $dbCon->real_escape_string($_POST['criteria_name']);
    $percentage = intval($dbCon->real_escape_string($_POST['criteria_percentage'])) / 100;

    // Check if id exists
    $criteriaIdExistsQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE id='$id' AND instructor='" . AuthController::user()->id . "'");

    if ($criteriaIdExistsQuery->num_rows > 0) {
        // Check if percentage is valid
        if ($percentage <= 0 || $percentage > 1) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Please enter a valid percentage for the criteria! Must be in the range of <strong>1% - 100%</strong>.";
        } else {
            // Check if criteria name already exists
            $checkNameQuery = $dbCon->query("SELECT * FROM grading_criterias WHERE criteria_name='$name' AND id<>'$id' AND instructor='" . AuthController::user()->id . "'");
    
            if ($checkNameQuery->num_rows > 0) {
                $hasError = true;
                $hasSuccess = false;
                $message = "Grading criteria '<strong>$name</strong>' already exists in your criterias!";
            } else {
                // Fetch all criterias and sum up all the fetched criterias
                $criteriasSumQuery = $dbCon->query("SELECT SUM(percentage) AS sum FROM grading_criterias WHERE instructor='" . AuthController::user()->id . "'");
                $criteriasSum = $criteriasSumQuery->fetch_assoc()['sum'] ?? 0;

                // Subtract old percentage value from the sum
                $criteriasSum -= $criteriaIdExistsQuery->fetch_assoc()['percentage'];
    
                // If sum is already 100%, the instructor should no longer be able to add new criteria
                if ($criteriasSum == 1) {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "The sum of all your grading criterias has already reached <strong>100%!</strong> Please <strong>edit</strong> or <strong>delete</strong> other criterias to update '<strong>$name</strong>' grading criteria.";
                } else if (($criteriasSum + $percentage) > 1) {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "The percentage for '<strong>$name</strong>' criteria that you want to update is too big. If updated, your total grading criteria percentage will exceed <strong>100%</strong>";
                } else {
                    $updateCriteria = $dbCon->query("UPDATE grading_criterias SET criteria_name='$name', percentage='$percentage' WHERE id='$id' AND instructor='" . AuthController::user()->id . "'");
    
                    if ($updateCriteria) {
                        $hasSuccess = true;
                        $hasError = false;
                        $message = "Successfully updated '<strong>$name</strong>' criteria!";
                    } else {
                        $hasError = true;
                        $hasSuccess = false;
                        $message = "An error occured while updating '<strong>$name</strong>' criteria.";
                    }
                }
            }
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Grading criteria does not exist anymore. Refresh the page if you're still seeing an already deleted criteria.";
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$paginationQuery = "SELECT COUNT(*) AS id FROM grading_criterias WHERE instructor = '" . AuthController::user()->id . "'";
$result = $dbCon->query($paginationQuery);
$activitiesCount = $result->fetch_all(MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// Fetch all criterias
$criteriasQuery = "SELECT * FROM grading_criterias WHERE instructor='" . AuthController::user()->id . "' LIMIT $start, $limit";

// Fetch sum of all criterias
$criteriasSumQuery = $dbCon->query("SELECT SUM(percentage) AS sum FROM grading_criterias WHERE instructor='" . AuthController::user()->id . "'");
$criteriasSum = $criteriasSumQuery->fetch_assoc()['sum'] ?? 0;
?>

<style>
    /* Style to hide number input arrows */
    /* Chrome, Safari, Edge, Opera */
    input.percentage::-webkit-outer-spin-button,
    input.percentage::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input.percentage[type=number] {
        -moz-appearance: textfield;
    }
</style>

<main class="h-screen overflow-x-auto flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class=" w-full px-4">
        <?php require_once("../layout/topbar.php") ?>

        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <!-- Table Header -->
                <div class="flex flex-col items-start">
                    <h1 class="text-[32px] font-bold">Grading Criterias</h1>
                    <span class="badge badge-success text-[16px] !p-4" id="total_criteria">Total: <?= ($criteriasSum * 100) ?>%</span>
                </div>

                <div class="flex w-full justify-end items-center">
                    <button class="btn btn-success" onclick="criteria.showModal()" <?php if (($criteriasSum * 100) == 100): ?> disabled <?php endif; ?>><i class="bx bx-plus-circle"></i> Create</button>
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
            <div class="overflow-x-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-slate-500 text-white text-center">Criteria</td>
                            <td class="bg-slate-500 text-white text-center">Percentage</td>
                            <td class="bg-slate-500 text-white text-center">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $gradingCriteriasResult = $dbCon->query($criteriasQuery); ?>
                        <?php if ($gradingCriteriasResult->num_rows > 0) : ?>
                            <?php while ($row = $gradingCriteriasResult->fetch_assoc()) : ?>
                                <tr>
                                    <!-- <td><?= $row['id'] ?></td> -->
                                    <td class="text-center"><?= $row['criteria_name'] ?></td>
                                    <td class="text-center"><?= $row['percentage'] * 100 ?>%</td>
                                    <td class="text-center">
                                        <button class="btn btn-info btn-sm mr-4" onclick="edit_criteria_<?= $row['id'] ?>.showModal()">Edit</button>
                                        <label for="delete-criteria-<?= $row['id'] ?>" class="btn btn-error btn-sm">Delete</label>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td class="text-center" colspan="3">No grading criterias to show</td>
                            </tr>
                        <?php endif; ?>
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

    <?php $gradingCriteriasResult = $dbCon->query($criteriasQuery); ?>
    <?php while ($row = $gradingCriteriasResult->fetch_assoc()) : ?>
        <!-- Delete Modal -->
        <input type="checkbox" id="delete-criteria-<?= $row['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to delete this grading criteria? This will never be undone once you proceed.</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="criteria_id" value="<?= $row['id'] ?>">

                    <label class="btn" for="delete-criteria-<?= $row['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete-criteria">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-criteria-<?= $row['id'] ?>">Close</label>
        </div>

        <!-- Edit modal -->
        <dialog id="edit_criteria_<?= $row['id'] ?>" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Edit Criteria</h3>

                <form class="flex flex-col gap-4 mt-4" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="criteria_id" value="<?= $row['id'] ?>" required>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Name</span>
                        <input class="input input-bordered" placeholder="Eg: Projects, Assignments, ..." name="criteria_name" value="<?= $row['criteria_name'] ?>" required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Percentage</span>

                        <label class="input input-bordered flex items-center gap-2" x-data>
                            <input type="number" class="grow bg-transparent percentage" x-mask="999" @input="editSumCriteriaPercentage(event, <?= $row['percentage'] ?>)" value="<?= $row['percentage'] * 100 ?>" min="1" max="100" placeholder="Eg: 20%" name="criteria_percentage" required />
                            <kbd class="kbd kbd-md">%</kbd>
                        </label>
                    </label>

                    <div class="flex justify-end items-center gap-4 mt-4">
                        <button type="reset" onclick="closeAndResetModal(<?= $row['id'] ?>)" class="btn btn-error">Cancel</button> 
                        <button class="btn btn-success" name="update-criteria">Update</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    <?php endwhile; ?>

    <!-- Create modal -->
    <dialog id="criteria" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Create Criteria</h3>

            <form class="flex flex-col gap-4 mt-4" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Name</span>
                    <input class="input input-bordered" placeholder="Eg: Projects, Assignments, ..." name="criteria_name" required />
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Percentage</span>

                    <label class="input input-bordered flex items-center gap-2" x-data>
                        <input type="number" class="grow bg-transparent percentage" x-mask="999" @input="sumCriteriaPercentage" min="1" max="100" placeholder="Eg: 20%" name="criteria_percentage" required />
                        <kbd class="kbd kbd-md">%</kbd>
                    </label>
                </label>

                <div class="flex justify-end items-center gap-4 mt-4">
                    <button type="reset" onclick="criteria.close()" class="btn btn-error">Cancel</button> 
                    <button class="btn btn-success" name="create">Create</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</main>

<script>
function sumCriteriaPercentage(e) {
    const totalPercentageDisplay = document.querySelector("#total_criteria");
    const value = !!e.target.value.trim() ? e.target.value.trim() : "0";
    const percentage = parseInt(value);

    if (percentage <= 0) {
        e.target.percentage = 1;
    } else if (percentage > 100) {
        e.target.percentage = 100;
    }

    totalPercentageDisplay.innerHTML = `Total: ${(parseInt("<?= $criteriasSum * 100 ?>") + percentage)}%`;
}

function editSumCriteriaPercentage(e, oldValue) {
    const totalPercentageDisplay = document.querySelector("#total_criteria");
    const value = !!e.target.value.trim() ? e.target.value.trim() : "0";
    const percentage = parseInt(value);

    if (percentage <= 0) {
        e.target.percentage = 1;
    } else if (percentage > 100) {
        e.target.percentage = 100;
    }

    totalPercentageDisplay.innerHTML = `Total: ${((parseInt("<?= $criteriasSum * 100 ?>") - (oldValue * 100)) + percentage)}%`;
}

function closeAndResetModal(id) {
    const totalPercentageDisplay = document.querySelector("#total_criteria");
    totalPercentageDisplay.innerHTML = `Total: ${parseInt("<?= $criteriasSum * 100 ?>")}%`;

    eval(`edit_criteria_${id}.close()`);
}
</script>