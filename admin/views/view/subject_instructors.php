<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// Subject ID
$subjectId = $dbCon->real_escape_string($_GET['subject'] ?? '');
$prevPage = $dbCon->real_escape_string($_GET['prev_page'] ?? '');
if (!isset($_GET['subject'])) {
    header("../manage-subjects.php");
    exit();
}

// Fetch subject details
$subjectQuery = $dbCon->query("SELECT 
    *, 
    courses.course_code as course_code 
    FROM subjects 
    LEFT JOIN courses ON subjects.course = courses.id 
    WHERE subjects.id = $subjectId
");

// If subject id does not exist, return back to the manage subjects page
if ($subjectQuery->num_rows == 0) {
    header("../manage-subjects.php");
    exit();
}

// Subject details
$subject = $subjectQuery->fetch_assoc();

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";
$search = "";

// Search instructor
if (isset($_POST['search-instructor'])) {
    $search = $dbCon->real_escape_string($_POST['search-instructor']);
    $hasSearch = true;
}

// Add instructor
if (isset($_POST['add-instructor'])) {
    $instructor = $dbCon->real_escape_string($_POST['instructor']);

    $checkIfInstructorIsAlreadyAssignedQuery = $dbCon->query("SELECT * FROM subject_instructors WHERE instructor_id = '$instructor' AND subject_id = '$subjectId'");

    if ($checkIfInstructorIsAlreadyAssignedQuery->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Instructor is already assigned in this subject!";
    } else {
        $assignInstructorQuery = $dbCon->query("INSERT INTO subject_instructors(instructor_id, subject_id) VALUES(
            '$instructor',
            '$subjectId'
        )");

        if ($assignInstructorQuery) {
            $hasSuccess = true;
            $hasError = false;
            $message = "Successfully assigned instructor to this subject!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong while assigning instructor to this subject.";
        }
    }
}

// Remove instructor
if (isset($_POST['remove-instructor'])) {
    $instructor = $dbCon->real_escape_string($_POST['id']);

    $checkIfInstructorIsNotAssignedQuery = $dbCon->query("SELECT * FROM subject_instructors WHERE instructor_id = '$instructor' AND subject_id = '$subjectId'");

    if ($checkIfInstructorIsNotAssignedQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Instructor does not appear to be assigned to this subject!";
    } else {
        $deleteInstructorFromSubject = $dbCon->query("DELETE FROM subject_instructors WHERE instructor_id = '$instructor' AND subject_id = '$subjectId'");

        if ($deleteInstructorFromSubject) {
            // Also delete the sections that is handled by this instructor in this subject
            $dbCon->query("DELETE FROM subject_instructor_sections WHERE subject_id='$subjectId' AND instructor_id='$instructor'");

            $hasSuccess = true;
            $hasError = false;
            $message = "Instructor has been successfully removed from this subject!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong while removing instructor from this subject.";
        }
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;


$result1 = $dbCon->query("SELECT 
    COUNT(*) AS count
    FROM subject_instructors
    LEFT JOIN userdetails ON subject_instructors.instructor_id = userdetails.id
    WHERE subject_instructors.subject_id = $subjectId" . (($hasSearch) ? " AND CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%'" : "")
);
if(isset($result1) && $result1->num_rows > 0) {
    $subjectCount = $result1->fetch_all(MYSQLI_ASSOC);
    $total = $subjectCount[0]['count'];
} else {
    $total = 0;
}
$pages = ceil($total / $limit);


// Prefetch all instructors query
$instructorsQuery = $dbCon->query("SELECT * FROM userdetails WHERE roles='instructor'");
$instructors = $instructorsQuery->fetch_all(MYSQLI_ASSOC);

// Prefetch all selected instructors
$selectedInstructorsQuery = $dbCon->query("SELECT * FROM subject_instructors WHERE subject_id=$subjectId");
$selectedInstructors = $selectedInstructorsQuery->fetch_all(MYSQLI_ASSOC);

// Filter out selected instructors
$filteredNotSelectedInstructors = array_filter($instructors, function($instructor) use ($selectedInstructors) {
    if (count($selectedInstructors) > 0) {
        foreach ($selectedInstructors as $selectedInstructor) {
            if ($instructor['id'] == $selectedInstructor['instructor_id'])
                return false;
        }
    } 
    
    return true;
});

// Filter out non-selected instructors
$filteredSelectedInstructors = array_filter($instructors, function($instructor) use ($selectedInstructors, $hasSearch, $search) {
    if (count($selectedInstructors) > 0) {
        foreach ($selectedInstructors as $selectedInstructor) {
            if ($instructor['id'] == $selectedInstructor['instructor_id'])
                return true;
        }
    } 

    return false;
});

// Filter selected instructors if there is a search
if ($hasSearch) {
    $filteredSelectedInstructors = array_filter($filteredSelectedInstructors, function($instructor) use ($search) {
        if (str_contains(strtolower(implode(" ", array($instructor['firstName'], $instructor['middleName'], $instructor['lastName']))), strtolower($search)))
            return true;

        return false;
    });
}
?>


<main class="overflow-x-auto h-screen flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">
            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center">
                <!-- Table Header -->
                <div class="flex flex-col justify-between">
                    <h1 class="text-[24px] font-semibold">Subject Instructors</h1>
                    <p>Subject: <?= $subject['name'] ?> - <?= $subject['year_level'] ?> (<?= $subject['course_code'] ?>)</p>
                </div>
                
                <div class="flex flex-col md:flex-row md:items-center gap-4 px-4 w-full md:w-auto">
                   <!-- Search bar -->
                   <form class="w-auto md:w-full" method="POST" autocomplete="off">   
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" name="search-instructor" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search name" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <a class="btn bg-[#276bae] text-white" href="../manage-subjects.php<?= !empty($prevPage) ? '?page=' . $prevPage : '' ?>"><i class="bx bxs-chevron-left"></i> Go Back</a>

                    <!-- Create button -->
                    <button class="btn bg-[#276bae] text-white" onclick="add_instructor.showModal()"><i class="bx bx-plus-circle"></i> Assign Instructor</button>
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
                        <tr class="hover">
                            <th class="bg-[#276bae] text-white text-center">Instructor Name</th>
                            <th class="bg-[#276bae] text-white text-center">Sections Handled</th>
                            <th class="bg-[#276bae] text-white text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($filteredSelectedInstructors) > 0): ?>
                            <?php foreach($filteredSelectedInstructors as $instructor): ?>
                                <tr class="hover">
                                    <td class="text-center"><?= "{$instructor['firstName']} {$instructor['middleName']} {$instructor['lastName']}" ?></td>
                                    <td class="text-center">
                                        <?php
                                            // Get assigned sections for this instructor in this subject
                                            $subjectInstructorHandledSectionsQuery = $dbCon->query("SELECT 
                                                *,
                                                sections.name AS section_name,
                                                sections.year_level AS year_level
                                                FROM subject_instructor_sections
                                                LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
                                                WHERE subject_id='$subjectId' AND instructor_id='{$instructor['id']}'
                                            ");
                                            $subjectInstructorHandledSections = $subjectInstructorHandledSectionsQuery->fetch_all(MYSQLI_ASSOC);

                                            if (count($subjectInstructorHandledSections) > 0) {
                                                $sections = array_map(fn ($section) => ($subject['course_code'] . " " . (str_split($section['year_level'])[0] . "-" . $section['section_name'])), $subjectInstructorHandledSections);
                                                echo trim(implode(", ", $sections));
                                            } else {
                                                echo "No sections assigned";
                                            }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center items-center gap-4">
                                            <a href="./subject_instructor_sections.php?subject=<?= $subjectId ?>&instructor=<?= $instructor['id'] ?><?= !empty($prevPage) ? '&prev_page=' . $prevPage : '' ?>" class="btn bg-[#276bae] text-white btn-sm">Assign Section</a>
                                            <label for="remove-instructor-<?= $instructor['id'] ?>"  class="btn btn-error btn-sm">Remove</label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No instructors assigned for this subject</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end items-center gap-4">
                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn bg-[#276bae] text-white" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Remove instructor Modal -->
    <?php foreach ($filteredSelectedInstructors as $instructor) { ?>

        <input type="checkbox" id="remove-instructor-<?= $instructor['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to remove <strong><?= $instructor['firstName'] ?> <?= $instructor['middleName'] ?> <?= $instructor['lastName'] ?></strong> from this subject? This action cannot be undone!</p>

                <form class="flex justify-end gap-4 items-center" method="post">
                    <input type="hidden" name="id" value="<?= $instructor['id'] ?>">

                    <label class="btn" for="remove-instructor-<?= $instructor['id'] ?>">Close</label>
                    <button class="btn btn-error" name="remove-instructor">Remove</button>
                </form>
            </div>
            <label class="modal-backdrop" for="remove-instructor-<?= $instructor['id'] ?>">Close</label>
        </div>

    <?php } ?>

    <!-- Add Modal -->
    <dialog class="modal" id="add_instructor">
        <div class="modal-box min-w-[474px]">
            <form class="flex flex-col gap-4" method="post">
                <h2 class="text-center text-[28px] font-bold">Assign Instructor</h2>

                <label class="flex flex-col gap-2 mb-4">
                    <span class="font-bold text-[18px]">Instructor</span>
                    <select class="select select-bordered" name="instructor" required <?php if(count($filteredNotSelectedInstructors) == 0): ?> disabled <?php endif; ?>>
                        <option value="" selected disabled><?= count($filteredNotSelectedInstructors) > 0 ? 'Select instructor' : 'No available instructors for this subject' ?></option>
                        
                        <?php if (count($filteredNotSelectedInstructors) > 0): ?>
                            <?php foreach($filteredNotSelectedInstructors as $instructor): ?>
                                <option value="<?= $instructor['id'] ?>"><?= $instructor['firstName'] ?> <?= $instructor['middleName'] ?> <?= $instructor['lastName'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <div class="modal-action">
                    <button class="btn btn-sm md:btn-md btn-error text-base" type="button" onclick="add_instructor.close()">Cancel</button>
                    <button class="btn btn-sm md:btn-md btn-success text-base <?= count($filteredNotSelectedInstructors) == 0 ? 'btn-disabled' : '' ?>" name="add-instructor">Assign</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

</main>