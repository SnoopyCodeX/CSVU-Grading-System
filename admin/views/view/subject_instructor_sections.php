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
$instructorId = $dbCon->real_escape_string($_GET['instructor'] ?? '');
$prevPage = $dbCon->real_escape_string($_GET['prev_page'] ?? '');

if (!isset($_GET['subject'])) {
    header("./subject_instructors.php?subject=$subjectId");
    exit();
}

if (!isset($_GET['instructor'])) {
    header("./subject_instructors.php?subject=$subjectId");
    exit();
}

// Fetch subject details
$subjectQuery = $dbCon->query("SELECT 
    *, 
    courses.id AS course_id,
    courses.course_code AS course_code 
    FROM subjects 
    LEFT JOIN courses ON subjects.course = courses.id 
    WHERE subjects.id = '$subjectId'
");

// If subject id does not exist, return back to the subject instructors page
if ($subjectQuery->num_rows == 0) {
    header("./subject_instructors.php?subject=$subjectId");
    exit();
}

// Fetch instructor details
$instructorQuery = $dbCon->query("SELECT
    *
    FROM userdetails
    WHERE id = '$instructorId'
");

// If instructor id does not exist, return back to the subject instructors page
if ($instructorQuery->num_rows == 0) {
    header("./subject_instructors.php?subject=$subjectId");
    exit();
}

// Subject details and instructor details
$subject = $subjectQuery->fetch_assoc();
$instructor = $instructorQuery->fetch_assoc();

// Format year levels into title case format
foreach ($subject as $key => $value) {
    if ($key == 'year_level') {
        $subject[$key] = ucwords($value);
    }
}


// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";
$search = "";

// Search section
if (isset($_POST['search-section'])) {
    $search = $dbCon->real_escape_string($_POST['search-section']);
    $hasSearch = true;
}

// Add section
if (isset($_POST['add-section'])) {
    $section = $dbCon->real_escape_string($_POST['section']);

    $checkIfSectionIsAlreadyAssignedQuery = $dbCon->query("SELECT * FROM subject_instructor_sections WHERE subject_id='$subjectId' AND section_id='$section'");

    if ($checkIfSectionIsAlreadyAssignedQuery->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Section is already assigned to this instructor in this subject!";
    } else {
        $assignSectionQuery = $dbCon->query("INSERT INTO subject_instructor_sections (subject_id, instructor_id, section_id) VALUES(
            '$subjectId',
            '$instructorId',
            '$section'
        )");

        if ($assignSectionQuery) {
            $hasSuccess = true;
            $hasError = false;
            $message = "Section has been succeessfully assigned to this instructor in this subject!";
        } else {    
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong while assigning section to this instructor in this subject";
        }
    }
}

// Remove section
if (isset($_POST['remove-section'])) {
    $section = $dbCon->real_escape_string($_POST['id']);

    $checkIfSectionIsNotAssignedQuery = $dbCon->query("SELECT * FROM subject_instructor_sections WHERE subject_id='$subjectId' AND section_id='$section'");

    if ($checkIfSectionIsNotAssignedQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Section does not appear to be assigned to this instructor of this subject!";
    } else {
        $deleteAssignedSectionQuery = $dbCon->query("DELETE FROM subject_instructor_sections WHERE subject_id='$subjectId' AND instructor_id='$instructorId' AND section_id='$section'");

        if ($deleteAssignedSectionQuery) {
            $hasSuccess = true;
            $hasError = false;
            $message = "Successfully removed section from the instructor of this subject!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong while removing section from this subject's instructor";
        }
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;


$result1 = $dbCon->query("SELECT 
    COUNT(*) AS count
    FROM subject_instructor_sections
    LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
    WHERE subject_instructor_sections.subject_id = '$subjectId' AND  subject_instructor_sections.instructor_id = '$instructorId'" . (($hasSearch) ? " AND sections.name LIKE '%$search%'" : "")
);
if(isset($result1) && $result1->num_rows > 0) {
    $subjectCount = $result1->fetch_all(MYSQLI_ASSOC);
    $total = $subjectCount[0]['count'];
} else {
    $total = 0;
}
$pages = ceil($total / $limit);

// Fetch all sections
$sectionsQuery = $dbCon->query("SELECT 
    *
    FROM sections
    WHERE course='{$subject['course_id']}' AND year_level='{$subject['year_level']}'
");
$sections = $sectionsQuery->fetch_all(MYSQLI_ASSOC);

// Fetch all assigned sections to this instructor
$handledSectionsQuery = $dbCon->query("SELECT 
    *,
    courses.course_code AS course_code,
    sections.year_level AS year_level,
    sections.name AS section_name
    FROM subject_instructor_sections 
    LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
    LEFT JOIN courses ON sections.course = courses.id
    WHERE subject_id='$subjectId' AND instructor_id='$instructorId'
");
$handledSections = $handledSectionsQuery->fetch_all(MYSQLI_ASSOC);

// Fetch all assigned sections to all instructors
$otherInstructorsHandledSectionsQuery = $dbCon->query("SELECT 
    *,
    courses.course_code AS course_code,
    sections.year_level AS year_level,
    sections.name AS section_name
    FROM subject_instructor_sections 
    LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
    LEFT JOIN courses ON sections.course = courses.id
    WHERE subject_id='$subjectId' AND (instructor_id<>'$instructorId' OR instructor_id='$instructorId')
");
$otherInstructorsHandledSections = $otherInstructorsHandledSectionsQuery->fetch_all(MYSQLI_ASSOC);

// Filter out non-selected sections (sections that are not handled by the instructor)
$notSelectedSections = array_filter($sections, function($section) use ($otherInstructorsHandledSections) {
    if (count($otherInstructorsHandledSections) > 0) {
        foreach ($otherInstructorsHandledSections as $handledSection) {
            if ($section['id'] == $handledSection['section_id'])
                return false;
        }
    }

    return true;
});


// Filter selected sections if there is a search
if ($hasSearch) {
    $handledSections = array_filter($handledSections, function($handledSection) use ($search) {
        return (str_contains(strtolower($handledSection['section_name']), strtolower($search)));
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
                    <h1 class="text-[24px] font-semibold">Assigned Sections</h1>
                    <p>Subject: <?= $subject['name'] ?> - <?= $subject['year_level'] ?> (<?= $subject['course_code'] ?>)</p>
                    <p>Instructor: <?= $instructor['firstName'] ?> <?= $instructor['middleName'] ?> <?= $instructor['lastName'] ?></p>
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
                            <input type="search" name="search-section" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search section" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <a class="btn btn-info" href="./subject_instructors.php?subject=<?= $subjectId ?><?= !empty($prevPage) ? '&prev_page=' . $prevPage : '' ?>"><i class="bx bxs-chevron-left"></i> Go Back</a>
                    
                    <!-- Create button -->
                    <button class="btn btn-success" onclick="add_section.showModal()"><i class="bx bx-plus-circle"></i> Assign Section</button>
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
                            <th class="bg-slate-500 text-white text-center">Course / Year Level / Section</th>
                            <th class="bg-slate-500 text-white text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($handledSections) > 0): ?>
                            <?php foreach($handledSections as $handledSection): ?>
                                <tr>
                                    <td class="text-center"><?= "{$handledSection['course_code']} " . str_split($handledSection['year_level'])[0] . "-{$handledSection['section_name']}" ?></td>
                                    <td class="text-center">
                                        <label for="remove-section-<?= $handledSection['section_id'] ?>"  class="btn btn-error btn-sm">Remove</label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center">No sections assigned for this instructor in this subject</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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

    <!-- Remove Section Modal -->
    <?php foreach ($handledSections as $handledSection) { ?>

        <input type="checkbox" id="remove-section-<?= $handledSection['section_id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to remove <strong><?= "{$handledSection['course_code']} " . str_split($handledSection['year_level'])[0] . "-{$handledSection['section_name']}" ?></strong> from this instructor's assigned sections? This action cannot be undone!</p>

                <form class="flex justify-end gap-4 items-center" method="post">
                    <input type="hidden" name="id" value="<?= $handledSection['section_id'] ?>">

                    <label class="btn" for="remove-section-<?= $handledSection['section_id'] ?>">Close</label>
                    <button class="btn btn-error" name="remove-section">Remove</button>
                </form>
            </div>
            <label class="modal-backdrop" for="remove-section-<?= $handledSection['section_id'] ?>">Close</label>
        </div>

    <?php } ?>

    <!-- Add Modal -->
    <dialog class="modal" id="add_section">
        <div class="modal-box min-w-[474px]">
            <form class="flex flex-col gap-4" method="post">
                <h2 class="text-center text-[28px] font-bold">Assign Section</h2>

                <label class="flex flex-col gap-2 mb-4">
                    <span class="font-bold text-[18px]">Section</span>
                    <select class="select select-bordered" name="section" required <?php if(count($notSelectedSections) == 0): ?> disabled <?php endif; ?>>
                        <option value="" selected disabled><?= count($notSelectedSections) > 0 ? 'Select section' : 'No available sections for this instructor in this subject' ?></option>
                        
                        <?php if (count($notSelectedSections) > 0): ?>
                            <?php foreach($notSelectedSections as $section): ?>
                                <option value="<?= $section['id'] ?>"><?= "{$subject['course_code']} " . str_split($section['year_level'])[0] . "-{$section['name']}" ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <div class="modal-action">
                    <button class="btn btn-sm md:btn-md btn-error text-base" type="button" onclick="add_section.close()">Cancel</button>
                    <button class="btn btn-sm md:btn-md btn-success text-base <?= count($notSelectedSections) == 0 ? 'btn-disabled' : '' ?>" name="add-section">Assign</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

</main>