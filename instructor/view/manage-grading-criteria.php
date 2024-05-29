<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';
require ('../../utils/grades.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once ("../../components/header.php");

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
    <?php require_once ("../layout/sidebar.php") ?>
    <section class=" w-full px-4">
        <?php require_once ("../layout/topbar.php") ?>

        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <!-- Table Header -->
                <div class="flex flex-col items-start">
                    <h1 class="text-[32px] font-bold">Grading Criterias</h1>
                    <span class="badge bg-[#276bae] text-white text-[16px] !p-4" id="total_criteria">Total:
                        <?= ($criteriasSum * 100) ?>%</span>
                </div>

                <div class="flex w-full justify-end items-center">
                    <button class="btn bg-[#276bae] text-white w-full md:max-w-[120px]" onclick="criteria.showModal()"
                        <?php if (($criteriasSum * 100) == 100): ?> disabled <?php endif; ?>>

                        <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                            <title>add_circle_fill</title>
                            <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                <path
                                    d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                <path fill='currentColor'
                                    d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                            </g>
                        </svg>

                        Create</button>
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
            <div class="overflow-x-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <td class="bg-slate-500 text-white">ID</td> -->
                            <td class="bg-[#276bae] text-white text-center">Criteria</td>
                            <td class="bg-[#276bae] text-white text-center">Percentage</td>
                            <td class="bg-[#276bae] text-white text-center">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $gradingCriteriasResult = $dbCon->query($criteriasQuery); ?>
                        <?php if ($gradingCriteriasResult->num_rows > 0): ?>
                        <?php while ($row = $gradingCriteriasResult->fetch_assoc()): ?>
                        <tr class="hover">
                            <!-- <td><?= $row['id'] ?></td> -->
                            <td class="text-center"><?= $row['criteria_name'] ?></td>
                            <td class="text-center"><?= $row['percentage'] * 100 ?>%</td>
                            <td class="text-center">
                                <button class="btn bg-[#276bae] text-white btn-sm mr-4"
                                    onclick="edit_criteria_<?= $row['id'] ?>.showModal()">
                                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                        <title>edit_line</title>
                                        <g id="edit_line" fill='none' fill-rule='nonzero'>
                                            <path
                                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                            <path fill='currentColor'
                                                d='M13 3a1 1 0 0 1 .117 1.993L13 5H5v14h14v-8a1 1 0 0 1 1.993-.117L21 11v8a2 2 0 0 1-1.85 1.995L19 21H5a2 2 0 0 1-1.995-1.85L3 19V5a2 2 0 0 1 1.85-1.995L5 3h8Zm6.243.343a1 1 0 0 1 1.497 1.32l-.083.095-9.9 9.899a1 1 0 0 1-1.497-1.32l.083-.094 9.9-9.9Z' />
                                        </g>
                                    </svg>
                                    Edit</button>
                                <label for="delete-criteria-<?= $row['id'] ?>" class="btn bg-red-500 text-white btn-sm">
                                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                        <title>delete_2_fill</title>
                                        <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                            <path
                                                d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                            <path fill='currentColor'
                                                d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                        </g>
                                    </svg>
                                    Delete</label>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr class="hover">
                            <td class="text-center" colspan="3">No grading criterias to show</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex gap-4 justify-end">
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

    <?php $gradingCriteriasResult = $dbCon->query($criteriasQuery); ?>
    <?php while ($row = $gradingCriteriasResult->fetch_assoc()): ?>
    <!-- Delete Modal -->
    <input type="checkbox" id="delete-criteria-<?= $row['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <h3 class="text-lg font-bold text-error">Notice!</h3>
            <p class="py-4">Are you sure you want to delete this grading criteria? This will never be undone once you
                proceed.</p>

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
                    <input class="input input-bordered" placeholder="Eg: Projects, Assignments, ..."
                        name="criteria_name" value="<?= $row['criteria_name'] ?>" required />
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Percentage</span>

                    <label class="input input-bordered flex items-center gap-2" x-data>
                        <input type="number" class="grow bg-transparent percentage" x-mask="999"
                            @input="editSumCriteriaPercentage(event, <?= $row['percentage'] ?>)"
                            value="<?= $row['percentage'] * 100 ?>" min="1" max="100" placeholder="Eg: 20%"
                            name="criteria_percentage" required />
                        <kbd class="kbd kbd-md">%</kbd>
                    </label>
                </label>

                <div class="flex justify-end items-center gap-4 mt-4">
                    <button type="reset" onclick="closeAndResetModal(<?= $row['id'] ?>)"
                        class="btn btn-error">Cancel</button>
                    <button class="btn bg-[##276bae] text-white" name="update-criteria">Update</button>
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
                    <input class="input input-bordered" placeholder="Eg: Projects, Assignments, ..."
                        name="criteria_name" required />
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Percentage</span>

                    <label class="input input-bordered flex items-center gap-2" x-data>
                        <input type="number" class="grow bg-transparent percentage" x-mask="999"
                            @input="sumCriteriaPercentage" min="1" max="100" placeholder="Eg: 20%"
                            name="criteria_percentage" required />
                        <kbd class="kbd kbd-md">%</kbd>
                    </label>
                </label>

                <div class="flex justify-end items-center gap-4 mt-4">
                    <button type="reset" onclick="criteria.close()" class="btn btn-error">Cancel</button>
                    <button class="btn bg-[##276bae] text-white" name="create">Create</button>
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

    totalPercentageDisplay.innerHTML =
        `Total: ${((parseInt("<?= $criteriasSum * 100 ?>") - (oldValue * 100)) + percentage)}%`;
}

function closeAndResetModal(id) {
    const totalPercentageDisplay = document.querySelector("#total_criteria");
    totalPercentageDisplay.innerHTML = `Total: ${parseInt("<?= $criteriasSum * 100 ?>")}%`;

    eval(`edit_criteria_${id}.close()`);
}
</script>