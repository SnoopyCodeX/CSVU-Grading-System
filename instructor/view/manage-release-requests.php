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

// Cancel request
if (isset($_POST['cancel-request'])) {
    $requestID = $dbCon->real_escape_string($_POST['request_id']);

    $checkRequestIfExistQuery = $dbCon->query("SELECT * FROM instructor_grade_release_requests WHERE id = '$requestID'");

    if ($checkRequestIfExistQuery->num_rows > 0) {
        $currentDir = dirname($_SERVER['PHP_SELF']);
        $firstDir = explode("/", trim($currentDir, "/"));
        $requestData = $checkRequestIfExistQuery->fetch_assoc();

        $deleteRequestQuery = $dbCon->query("DELETE FROM instructor_grade_release_requests WHERE id = '$requestID'");

        if ($deleteRequestQuery) {
            @unlink(str_repeat("../", count($firstDir) - 1) . "uploads/" . $requestData['grade_sheet_file']);

            $hasSuccess = true;
            $message = "Grade release request has been successfully canceled!";
        } else {
            $hasError = true;
            $message = "Cancelation failed, an error occured during the cancelation process.";
        }
    } else {
        $hasError = true;
        $message = "Cancelation failed, grade release request does not exist!";
    }
}

// Release Grades
if (isset($_POST['release-grades'])) {
    $requestID = $dbCon->real_escape_string($_POST['request_id']);
    $subject = $dbCon->real_escape_string($_POST['request_subject']);
    $term = $dbCon->real_escape_string($_POST['request_term']);

    // Get active school year
    $schoolYearsQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");

    if ($schoolYearsQuery->num_rows > 0) {
        $schoolYear = $schoolYearsQuery->fetch_assoc();

        // Get the data of the subject that the instructor is handling
        $subjectQuery = $dbCon->query("SELECT 
            subject_instructors.*,
            subjects.year_level as year_level,
            subjects.name as name,
            subjects.code as code,
            subjects.units as units,
            subjects.credits_units as credits_units,
            subjects.term as term,
            courses.course_code as course,
            courses.course_code as course_code,
            courses.id as course_id
            FROM subject_instructors
            LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
            LEFT JOIN courses ON subjects.course = courses.id
            WHERE subject_instructors.instructor_id = " . AuthController::user()->id . " AND subject_instructors.subject_id = $subject"
        );
        $subjectData = $subjectQuery->fetch_assoc();

        // Fetch assigned sections for the selected subject
        $sectionsQuery = "SELECT
            sections.*,
            courses.course as courseName
            FROM subject_instructor_sections
            LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
            LEFT JOIN courses ON sections.course = courses.id
            WHERE subject_instructor_sections.instructor_id = " . AuthController::user()->id . " AND subject_instructor_sections.subject_id = $subject";
        $sectionsQueryResult = $dbCon->query($sectionsQuery);
        $sections = $sectionsQueryResult->fetch_all(MYSQLI_ASSOC);

        if (count($sections) > 0) {
            $sectionIds = array_map(fn($section) => $section['id'], $sections);

            // Fetch all student from the section
            $studentsQuery = $dbCon->query("SELECT
                student_id
                FROM section_students
                WHERE section_id IN (" . implode(",", $sectionIds) . ") GROUP BY student_id
            ");
            $students = $studentsQuery->fetch_all(MYSQLI_ASSOC);

            if (count($students) > 0) {
                $someFailToRelease = false;
                $studentsWithNoScores = [];

                // Loop through each students
                foreach ($students as $student) {
                    // Check if student is enrolled to the subject, if not, we will skip the student and not give grade to him/her
                    $enrolledSubjectQuery = $dbCon->query("SELECT 
                        student_enrolled_subjects.*,
                        CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS studentName
                        FROM student_enrolled_subjects 
                        LEFT JOIN userdetails ON student_enrolled_subjects.student_id = userdetails.id
                        WHERE student_enrolled_subjects.subject_id = $subject AND student_enrolled_subjects.student_id = $student[student_id]
                    ");

                    if ($enrolledSubjectQuery->num_rows == 0) {
                        continue;
                    }

                    $enrolledSubject = $enrolledSubjectQuery->fetch_assoc();

                    // Compute the grade of the student from this subject
                    $computedGrade = computeStudentGradesFromSubject($dbCon, $subject, $subjectData['course_id'], $student['student_id'], AuthController::user()->id, $schoolYear['id'], $term);

                    // -1 means student has no activity score
                    if ($computedGrade != -1) {
                        $computedGrade = number_format($computedGrade, 2);

                        // Check if student already has a grade for this semester, year level, school year and subject
                        $check = $dbCon->query("SELECT * FROM student_final_grades WHERE student='{$student['student_id']}' AND subject = '$subject' AND term = '$term' AND school_year = '{$schoolYear['id']}'");

                        // If student's final grade already exists, show error message
                        if ($check->num_rows > 0) {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "Grades for the subject <strong>{$subjectData['name']}</strong>@<strong>$term</strong> has <strong>already</strong> been released!";
                        } else {
                            $insertNewStudentGrade = $dbCon->query("INSERT INTO student_final_grades (subject, term, student, school_year, grade) VALUES(
                                '$subject',
                                '$term',
                                '{$student['student_id']}',
                                '{$schoolYear['id']}',
                                '$computedGrade'
                            )");

                            if (!$insertNewStudentGrade) {
                                $someFailToRelease = true;
                            }
                        }
                    } else {
                        $studentsWithNoScores[] = $enrolledSubject['studentName'];
                        $hasError = true;
                        $message = "Some students enrolled to <strong>($subjectData[code]) $subjectData[name]</strong>@<strong>$term</strong> does not have any scores on their activities yet. Students: <strong>" . implode(", ", $studentsWithNoScores) . "</strong>";
                        continue;
                    }
                }

                if ($someFailToRelease) {
                    $message = "Succesfully released all grades but some errors have occured during the process.";
                    $hasError = false;
                    $hasSuccess = true;
                } else if (!$hasError) {
                    $message = "Successfully released all grades!";
                    $hasSuccess = true;
                }

                if ($someFailToRelease || !$hasError) {
                    $updateRequestQuery = $dbCon->query("UPDATE instructor_grade_release_requests SET status='grade-released' WHERE id = '$requestID'");

                    if (!$updateRequestQuery) {
                        $message .= " However, updating the status of your grade release request failed.";
                    }
                }
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "There are no students to grade in the sections that you are assigned for <strong>{$subjectData['name']}</strong> subject!";
            }
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "You have no sections handled for <strong>{$subjectData['name']}</strong> subject!";
        }
    } else {
        $hasError = true;
        $message = "Failed to release grade. There's no active school year and semester. Contact your admin to create new active school year and semester.";
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$paginationQuery = "SELECT COUNT(*) AS id FROM instructor_grade_release_requests WHERE instructor_id = '" . AuthController::user()->id . "'";
$result = $dbCon->query($paginationQuery);
$activitiesCount = $result->fetch_all(MYSQLI_ASSOC);
$total = $activitiesCount[0]['id'];
$pages = ceil($total / $limit);

// Fetch requests
$gradeReleaseRequestsQuery = $dbCon->query("SELECT 
    subjects.*,
    school_year.*,
    instructor_grade_release_requests.subject_id AS request_subject,
    instructor_grade_release_requests.id AS request_id,
    instructor_grade_release_requests.file_uid AS request_file_uid,
    instructor_grade_release_requests.term AS request_term,
    instructor_grade_release_requests.status AS request_status,
    instructor_grade_release_requests.created_at AS request_created_at
    FROM instructor_grade_release_requests
    LEFT JOIN subjects ON instructor_grade_release_requests.subject_id = subjects.id 
    LEFT JOIN school_year ON instructor_grade_release_requests.school_year = school_year.id
    WHERE instructor_grade_release_requests.instructor_id = '" . AuthController::user()->id . "'
    ORDER BY instructor_grade_release_requests.updated_at DESC
    LIMIT $start, $limit
");
$gradeReleaseRequests = $gradeReleaseRequestsQuery->fetch_all(MYSQLI_ASSOC);

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
                <div class="flex items-center">
                    <h1 class="text-[32px] font-bold">Manage Release Requests</h1>
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
                        <tr>
                            <td class="bg-[#276bae] text-white text-center">Subject Code</td>
                            <td class="bg-[#276bae] text-white text-center">Subject Name</td>
                            <td class="bg-[#276bae] text-white text-center">Year Level</td>
                            <td class="bg-[#276bae] text-white text-center">Term</td>
                            <td class="bg-[#276bae] text-white text-center">Requested On</td>
                            <td class="bg-[#276bae] text-white text-center">Status</td>
                            <td class="bg-[#276bae] text-white text-center">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($gradeReleaseRequests) > 0): ?>
                        <?php foreach ($gradeReleaseRequests as $gradeReleaseRequest): ?>
                        <tr>
                            <td class="text-center"><?= $gradeReleaseRequest['code'] ?></td>
                            <td class="text-center"><?= $gradeReleaseRequest['name'] ?></td>
                            <td class="text-center"><?= $gradeReleaseRequest['year_level'] ?></td>
                            <td class="text-center"><?= $gradeReleaseRequest['request_term'] ?></td>
                            <td class="text-center">
                                <?= date("h:i A \\| F d, Y", strtotime($gradeReleaseRequest['request_created_at'])) ?>
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge p-4 <?= (($gradeReleaseRequest['request_status'] == 'pending') ? 'badge-warning' : (($gradeReleaseRequest['request_status'] == 'approved') ? 'badge-success' : (($gradeReleaseRequest['request_status'] == 'grade-released') ? 'badge-success' : 'badge-error'))) ?>"><?= $gradeReleaseRequest['request_status'] != 'grade-released' ? ucfirst($gradeReleaseRequest['request_status']) : 'Graded' ?></span>
                            </td>
                            <td class="flex gap-2 items-center justify-center">
                                <a class="btn btn-sm btn-info"
                                    href="./view/grade-sheet.php?uid=<?= $gradeReleaseRequest['request_file_uid'] ?>"
                                    target="_blank">Grade Sheet</a>
                                <button class="btn btn-sm btn-info"
                                    onclick="view_grade_<?= $gradeReleaseRequest['request_id'] ?>.showModal()">View</button>
                                <?php if (in_array($gradeReleaseRequest['request_status'], ['pending', 'rejected'])): ?>
                                <label class="btn btn-sm btn-error"
                                    for="cancel-request-<?= $gradeReleaseRequest['request_id'] ?>">Cancel</label>
                                <?php elseif ($gradeReleaseRequest['request_status'] == 'approved'): ?>
                                <label class="btn btn-sm btn-success"
                                    for="release-grades-<?= $gradeReleaseRequest['request_id'] ?>">Release
                                    Grades</label>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td class="text-center" colspan="7">No grade requests to show</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end gap-4">
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
    <?php foreach ($gradeReleaseRequests as $gradeReleaseRequest): ?>
    <!-- Release Grades Modal -->
    <input type="checkbox" id="release-grades-<?= $gradeReleaseRequest['request_id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-success border-2">
            <h3 class="text-lg font-bold text-success">Release Grades</h3>
            <p class="py-4">By clicking <strong>Release</strong>, the
                <strong><?= $gradeReleaseRequest['request_term'] ?> grades</strong> of all the students enrolled to
                <strong>(<?= $gradeReleaseRequest['code'] ?>) <?= $gradeReleaseRequest['name'] ?></strong> will be
                released and this action cannot be undone. Do you still wish to proceed?
            </p>

            <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <input type="hidden" name="request_id" value="<?= $gradeReleaseRequest['request_id'] ?>">
                <input type="hidden" name="request_subject" value="<?= $gradeReleaseRequest['request_subject'] ?>">
                <input type="hidden" name="request_term" value="<?= $gradeReleaseRequest['request_term'] ?>">

                <label class="btn" for="release-grades-<?= $gradeReleaseRequest['request_id'] ?>">No</label>
                <button class="btn btn-success" name="release-grades">Release</button>
            </form>
        </div>
        <label class="modal-backdrop" for="release-grades-<?= $gradeReleaseRequest['request_id'] ?>">Close</label>
    </div>

    <!-- Delete Modal -->
    <input type="checkbox" id="cancel-request-<?= $gradeReleaseRequest['request_id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <h3 class="text-lg font-bold text-error">Cancel Request</h3>
            <p class="py-4">Are you sure you want to cancel this grade release request?</p>

            <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <input type="hidden" name="request_id" value="<?= $gradeReleaseRequest['request_id'] ?>">

                <label class="btn" for="cancel-request-<?= $gradeReleaseRequest['request_id'] ?>">No</label>
                <button class="btn btn-error" name="cancel-request">Yes, Cancel</button>
            </form>
        </div>
        <label class="modal-backdrop" for="cancel-request-<?= $gradeReleaseRequest['request_id'] ?>">Close</label>
    </div>

    <!-- View modal -->
    <dialog id="view_grade_<?= $gradeReleaseRequest['request_id'] ?>" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box">
            <h3 class="font-bold text-lg">View Release Request</h3>

            <form class="flex flex-col gap-4 mt-4" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Subject Code</span>
                        <input class="input input-bordered" name="code" value="<?= $gradeReleaseRequest['code'] ?>"
                            readonly required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Subject Name</span>
                        <input class="input input-bordered" name="name" value="<?= $gradeReleaseRequest['name'] ?>"
                            readonly required />
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">School Year</span>
                        <input class="input input-bordered" name="school_year"
                            value="<?= $gradeReleaseRequest['school_year'] ?>" readonly required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year Level</span>
                        <input class="input input-bordered" name="year_level"
                            value="<?= $gradeReleaseRequest['year_level'] ?>" readonly required />
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Semester</span>
                        <input class="input input-bordered" name="request_term"
                            value="<?= $gradeReleaseRequest['request_term'] ?>" readonly required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Requested On</span>
                        <input class="input input-bordered" name="request_created_at"
                            value="<?= date("h:i A \\| F d, Y", strtotime($gradeReleaseRequest['request_created_at'])) ?>"
                            readonly required />
                    </label>
                </div>

                <div class="flex justify-end items-center gap-4 mt-4">
                    <button type="reset" onclick="view_grade_<?= $gradeReleaseRequest['request_id'] ?>.close()"
                        class="btn btn-error">Close</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
    <?php endforeach; ?>
</main>