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

// Edit subject
if (isset($_POST['update_subject'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $subjectName = $dbCon->real_escape_string($_POST['subject_name']);
    $units = $dbCon->real_escape_string($_POST['units']);
    $creditsUnits = $dbCon->real_escape_string($_POST['credits_units']);
    $term = $dbCon->real_escape_string($_POST['term']);

    $subjectExistQuery = $dbCon->query("SELECT * FROM ap_subjects WHERE id = '$id'");

    if ($subjectExistQuery->num_rows <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject does not exist!";
    } else {
        $query = "UPDATE ap_subjects SET course='$course', year_level='$yearLevel', name='$subjectName', units='$units', credits_units='$creditsUnits', term='$term' WHERE id='$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Subject updated successfully!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Subject update failed!";
        }
    }
}

// Delete subject
if (isset($_POST['delete_subject'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $subjectExistQuery = $dbCon->query("SELECT * FROM ap_subjects WHERE id = '$id'");

    if ($subjectExistQuery->num_rows <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject does not exist!";
    } else {
        $query = "DELETE FROM ap_subjects WHERE id='$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Subject deleted successfully!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Subject deletion failed!";
        }
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Total pages
$result1 = $dbCon->query("SELECT COUNT(*) AS id FROM ap_subjects");
$subjectCount = $result1->fetch_all(MYSQLI_ASSOC);
$total = $subjectCount[0]['id'];
$pages = ceil($total / $limit);

// Prefetch all subjects
$subjects = $dbCon->query("SELECT * FROM ap_subjects LIMIT $start, $limit");
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
                    <h1 class="text-[32px] font-bold">Subject</h1>
                </div>
                <a href="./create/subject.php" class="btn">Create</a>
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
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Course</td>
                            <td class="bg-slate-500 text-white">Units</td>
                            <td class="bg-slate-500 text-white">Credits</td>
                            <td class="bg-slate-500 text-white">Yearlevel</td>
                            <td class="bg-slate-500 text-white">Term</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($subject = $subjects->fetch_assoc()) { ?>

                            <tr>
                                <th><?= $subject['id'] ?></th>
                                <td class="capitalize"><?= $subject['name'] ?></td>
                                <td class="capitalize">
                                    <span class="badge p-4 bg-blue-200 text-semibold text-black">
                                        <?= $dbCon->query("SELECT * FROM ap_courses WHERE id='{$subject['course']}'")->fetch_assoc()['course'] ?>
                                    </span>
                                </td>
                                <td><?= $subject['units'] ?></td>
                                <td><?= $subject['credits_units'] ?></td>
                                <td><?= $subject['year_level'] ?></td>
                                <td><?= $subject['term'] ?></td>
                                <td>
                                    <div class="flex justify-center gap-2">
                                        <label for="view-subject-<?= $subject['id'] ?>" class="bg-blue-400 btn btn-sm text-white">View</label>
                                        <label for="edit-subject-<?= $subject['id'] ?>" class="bg-gray-400 btn btn-sm text-white">Edit</label>
                                        <label for="delete-subject-<?= $subject['id'] ?>" class="bg-red-400 btn btn-sm text-white">Delete</label>
                                    </div>
                                </td>
                            </tr>

                        <?php } ?>
                    </tbody>
                </table>
            </div>
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

    <!-- Fetch all subjects again -->
    <?php $subjects = $dbCon->query("SELECT * FROM ap_subjects LIMIT $start, $limit"); ?>

    <!-- Modals -->
    <?php while ($subject = $subjects->fetch_assoc()) { ?>

        <!-- View Modal -->
        <input type="checkbox" id="view-subject-<?= $subject['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <div class="flex flex-col gap-4 px-[32px] mb-auto">

                    <label class="flex flex-col gap-2">
                        <span class=" text-[18px] font-semibold"></span>
                        <select class="p-4 rounded-[5px] border border-gray-200 capitalize" name="course" required aria-readonly="true" disabled>
                            <option value="" selected><?= $dbCon->query("SELECT * FROM ap_courses WHERE id='{$subject['course']}'")->fetch_assoc()['course'] ?></option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class=" text-[18px]">Year level</span>
                        <select class="p-4 rounded-[5px] border border-gray-200 capitalize" name="year_level" required aria-readonly="true" disabled>
                            <option value="" selected><?= $subject['year_level'] ?></option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class=" text-[18px]">Subject</span>
                        <input class="p-4 rounded-[5px] border border-gray-200 capitalize" placeholder="Enter Subject Name" name="subject_name" value="<?= $subject['name'] ?>" required readonly disabled />
                    </label>

                    <!-- Name -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class=" text-[18px]">Units</span>
                            <input class="p-4 rounded-[5px] border border-gray-200 capitalize" placeholder="Enter Subject Units" name="units" value="<?= $subject['units'] ?>" required readonly disabled />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class=" text-[18px]">Credits Units</span>
                            <input class="p-4 rounded-[5px] border border-gray-200 capitalize" placeholder="Enter Subject Credits" name="credits_units" value="<?= $subject['credits_units'] ?>" required readonly disabled />
                        </label>

                        <label class="flex flex-col gap-2 col-span-3">
                            <span class=" text-[18px]">Term</span>
                            <select class="p-4 rounded-[5px] border border-gray-200 capitalize" name="term" aria-readonly="true" disabled>
                                <option value="" selected><?= $subject['term'] ?></option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
            <label class="modal-backdrop" for="view-subject-<?= $subject['id'] ?>">Close</label>
        </div>

        <!-- Edit Modal -->
        <input type="checkbox" id="edit-subject-<?= $subject['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <form class="flex flex-col gap-4 px-[32px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

                    <input type="hidden" name="id" value="<?= $subject['id'] ?>">

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <select class="select select-bordered" name="course" required>
                            <option value="" disabled>Select Course</option>
                            <?php $courses = $dbCon->query("SELECT * FROM ap_courses"); ?>
                            <?php while ($course = $courses->fetch_assoc()) { ?>
                                <option value="<?php echo $course['id'] ?>" <?php if ($subject['course'] == $course['id']) { ?> selected <?php } ?>><?php echo $course['course'] . " - #" . $course['course_code'] ?></option>
                            <?php } ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required>
                            <option value="" disabled>Select Year level</option>
                            <option value="1st year" <?php if ($subject['year_level'] == "1st year") { ?> selected <?php } ?>>1st year</option>
                            <option value="2nd year" <?php if ($subject['year_level'] == "2nd year") { ?> selected <?php } ?>>2nd year</option>
                            <option value="3rd year" <?php if ($subject['year_level'] == "3rd year") { ?> selected <?php } ?>>3rd year</option>
                            <option value="4th year" <?php if ($subject['year_level'] == "4th year") { ?> selected <?php } ?>>4th year</option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Subject Name</span>
                        <input class="input input-bordered" placeholder="Enter Subject Name" name="subject_name" value="<?= $subject['name'] ?>" required />
                    </label>

                    <!-- Name -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Units</span>
                            <input class="input input-bordered" placeholder="Enter Subject Units" name="units" value="<?= $subject['units'] ?>" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Credits Units</span>
                            <input class="input input-bordered" placeholder="Enter Subject Credits" name="credits_units" value="<?= $subject['credits_units'] ?>" required />
                        </label>

                        <label class="flex flex-col gap-2 col-span-3">
                            <span class="font-bold text-[18px]">Term</span>
                            <select class="select select-bordered" name="term">
                                <option value="" disabled>Select Term</option>
                                <option value="1st Sem" <?php if ($subject['term'] == "1st Sem") { ?> selected <?php } ?>>1st Sem</option>
                                <option value="2nd Sem" <?php if ($subject['term'] == "2nd Sem") { ?> selected <?php } ?>>2nd Sem</option>
                                <option value="3rd Sem" <?php if ($subject['term'] == "3rd Sem") { ?> selected <?php } ?>>3rd Sem</option>
                            </select>
                        </label>

                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="btn btn-error text-base" for="edit-subject-<?= $subject['id'] ?>">Cancel</label>
                        <button class="btn btn-success" name="update_subject">Update</button>
                    </div>

                </form>
            </div>
            <label class="modal-backdrop" for="edit-subject-<?= $subject['id'] ?>">Close</label>
        </div>

        <!-- Delete Modal -->
        <input type="checkbox" id="delete-subject-<?= $subject['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                    <input type="hidden" name="id" value="<?= $subject['id'] ?>">

                    <label class="btn" for="delete-subject-<?= $subject['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete_subject">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-subject-<?= $subject['id'] ?>">Close</label>
        </div>]

    <?php } ?>

</main>