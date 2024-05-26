<?php
session_start();
// // kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');
require '../../../utils/humanizer.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// error and success handlers
$hasError = false;
$hasWarning = false;
$hasSuccess = false;
$hasSaveError = false;
$warning = "";
$message = "";

// get activity id
$id = $dbCon->real_escape_string($_GET['id']) ? $dbCon->real_escape_string($_GET['id']) : header("Location: ../manage-activity.php");
$subjectId = $dbCon->real_escape_string($_GET['subjectId'] ?? '') ? $dbCon->real_escape_string($_GET['subjectId']) : header("location: ../manage-activity.php");

// get activity details
$query = $dbCon->query("SELECT 
    activities.*,
    subjects.id AS subject_id,
    subjects.name AS subject_name,
    courses.course AS course_name,
    courses.course_code AS course_code
    FROM activities 
    INNER JOIN subjects ON activities.subject = subjects.id
    INNER JOIN courses ON activities.course = courses.id
    INNER JOIN school_year ON activities.school_year = school_year.id
    WHERE activities.instructor = '" . AuthController::user()->id . "' AND activities.id = '$id'");
$activity = $query->fetch_assoc();

// save student scores
if (isset($_POST['save-scores'])) {
    // Student scores array
    $studentScores = [];

    // Get all student scores
    foreach($_POST as $key => $value) {
        if(str_starts_with($key, "grade_")) {
            $studentId = intval(explode('_', $key)[1]);
            $studentScores[$studentId] = intval($value ?? '0');
        }
    }

    $skip = 0;
    foreach($studentScores as $studentID => $score) {
        // check if student is enrolled in the activity
        $query = $dbCon->query("SELECT 
            *,
            CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) AS student_name
            FROM 
            student_enrolled_subjects 
            LEFT JOIN userdetails ON student_enrolled_subjects.student_id = userdetails.id
            WHERE 
            student_enrolled_subjects.student_id = '$studentID' AND 
            student_enrolled_subjects.subject_id='{$activity['subject']}'
        ");

        if ($query->num_rows == 0) {
            $hasError = true;
            $message = "Student is not enrolled in this activity";
        } else {
            // check if score is less than 0 or is greater than the max score 
            if ($score < 0 || $score > $activity['max_score']) {
                $skip += 1;
                continue;
            }

            // check if student score already exists
            $gradecheckquery1 = $dbCon->query("SELECT * FROM activity_scores WHERE student_id = '$studentID' AND activity_id = '{$activity['id']}'");

            if ($gradecheckquery1->num_rows == 0) {

                // insert new student score
                $insertnewscore = $dbCon->query("INSERT INTO activity_scores (
                    student_id, 
                    activity_id, 
                    instructor_id, 
                    score,
                    term,
                    year_level
                ) VALUES (
                    '$studentID', 
                    '{$activity['id']}', 
                    '" . AuthController::user()->id . "', 
                    '$score',
                    '{$activity['term']}',
                    '{$activity['year_level']}'
                )");

                if (!$insertnewscore) {
                    $hasSaveError = true;
                    $message = "An error occured while saving some of student's scores. {$dbCon->error}";
                }
            } else {
                // update student score
                $updatenewscore = $dbCon->query("UPDATE activity_scores SET score = '$score' WHERE student_id = '$studentID' AND activity_id = '{$activity['id']}'");

                if (!$updatenewscore) {
                    $hasSaveError = true;
                    $message = "An error occured while saving some of student's scores. {$dbCon->error}";
                }
            }
        }
    }

    if ($skip > 0) {
        $hasWarning = true;
        $message = "Skipped <strong>$skip students</strong> because their scores are invalid.";
    }

    // If no errors during saving, show success message
    if(!$hasError && !$hasSaveError) {
        $hasSuccess = true;
        $message = "Successfully saved all student scores!";
    }
}

// Get the details of the subject of this activity
$subjectQuery = $dbCon->query("SELECT 
    subject_instructors.*,
    subjects.year_level as year_level,
    subjects.name as name,
    subjects.code as code,
    subjects.units as units,
    subjects.credits_units as credits_units,
    subjects.term as term,
    courses.course_code as course,
    courses.course_code as course_code
    FROM subject_instructors
    LEFT JOIN subjects ON subject_instructors.subject_id = subjects.id
    LEFT JOIN courses ON subjects.course = courses.id
    WHERE subject_instructors.instructor_id = " . AuthController::user()->id . " AND subject_instructors.subject_id = " . $activity['subject']
);
$subject = $subjectQuery->fetch_assoc();

// Fetch assigned sections for the selected subject
$sectionsQuery = "SELECT
    sections.*,
    courses.course as courseName
    FROM subject_instructor_sections
    LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
    LEFT JOIN courses ON sections.course = courses.id
    WHERE subject_instructor_sections.instructor_id = " . AuthController::user()->id . " AND subject_instructor_sections.subject_id = $subject[subject_id]";
$sectionsQueryResult = $dbCon->query($sectionsQuery);
$sections = $sectionsQueryResult->fetch_all(MYSQLI_ASSOC);

$students = [];

// get all students handled by the instructor
foreach ($sections as $section) {
    $studentsQuery = $dbCon->query(
        "SELECT 
        section_students.*,
        userdetails.id as studentID,
        userdetails.firstName as studentFN,
        userdetails.middleName as studentMN,
        userdetails.lastName as studentLN
        FROM section_students 
        INNER JOIN userdetails ON section_students.student_id = userdetails.id
        WHERE section_students.section_id = " . $section['id']
    );
    
    while ($row = $studentsQuery->fetch_assoc()) {
        $enrolledSubjectQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE subject_id = $subject[subject_id] AND student_id = $row[student_id]");
        
        // If the student is enrolled to the subject of this activity, add the student to the array
        if ($enrolledSubjectQuery->num_rows > 0)
            $students[] = $row;
    }
}

$students = removeDuplicates($students, 'studentID');

// get all students grades
$gradesQuery = $dbCon->query("SELECT * FROM activity_scores WHERE activity_id = '$id'");
$gradesQueryResult = $gradesQuery->fetch_all(MYSQLI_ASSOC);

// store all student grades in an array with the student's id as the key
$grades = [];
foreach ($gradesQueryResult as $grade) {
    $grades[$grade['student_id']] = $grade['score'];
}

// Get grade release request
$gradeReleaseRequestQuery = $dbCon->query("SELECT 
    * 
    FROM instructor_grade_release_requests 
    WHERE instructor_id = '" . AuthController::user()->id . "' AND
    subject_id = '$activity[subject]' AND 
    term = '$subject[term]' AND 
    school_year = '$activity[school_year]' AND 
    status IN ('approved', 'pending', 'grade-released')
");
$hasGradeReleased = $gradeReleaseRequestQuery->num_rows > 0;
?>
<style>
    /* Style to hide number input arrows */
    /* Chrome, Safari, Edge, Opera */
    input.grade::-webkit-outer-spin-button,
    input.grade::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input.grade[type=number] {
        -moz-appearance: textfield;
    }
</style>
<main class="w-screen h-screen overflow-x-auto flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-0 md:px-4 h-full">
        <?php require_once("../../layout/topbar.php") ?>
        <div class="w-full h-full px-0 md:px-[200px]">
            <div class="flex justify-center items-center flex-col p-8">
                <h2 class="text-[24px] md:text-[38px] font-bold mb-4 w-full text-center">Student Activity Scores</h2>
                <div class="flex flex-col gap-[24px] px-0 md:px-[32px]  w-full mb-auto flex">

                    <?php if ($hasWarning) { ?>
                        <div role="alert" class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span><?= $warning ?></span>
                        </div>
                    <?php } ?>

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

                    <label class="flex flex-col gap-2">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[14px]">Activity: </span>
                                <span class="text-[14px]"><?= $activity['name'] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[14px]">Subject: </span>
                                <span class="text-[14px]"><?= $activity['subject_name'] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[14px]">Max Score: </span>
                                <span class="text-[14px]"><?= $activity['max_score'] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[14px]">Course: </span>
                                <span class="text-[14px]"><?= $activity['course_code'] ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-[14px]">Year & Sem: </span>
                                <span class="text-[14px]"><?= $activity['year_level'] ?> - <?= $activity['term'] ?></span>
                            </div>
                        </div>

                        <form class="flex flex-col gap-4 h-100" method="post">
                            <div class="overflow-x-auto">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Name</th>
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($students) > 0): ?>
                                            <?php $number = 1; ?>
                                            <?php foreach ($students as $row): ?>
                                                <tr>
                                                    <td><?= $number++ ?></td>
                                                    <td><?= $row['studentFN'] ?> <?= $row['studentMN'] ?> <?= $row['studentLN'] ?></td>
                                                    <td x-data>
                                                        <?php if ($hasGradeReleased): ?>
                                                            <label  class="input input-bordered text-center grade px-6 py-2"><?= $grades[$row['studentID']] ?? '0' ?></label>
                                                        <?php else: ?>
                                                            <input type="number" @input="enforceMinMax" name="grade_<?= $row['studentID'] ?>" class="input input-bordered text-center grade" placeholder="Score" min="0" max="<?= $activity['max_score'] ?>" value="<?= $grades[$row['studentID']] ?? '' ?>" required>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td class="text-center" colspan="3">There are no students in this specific course, year level and subject.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-start gap-4">
                                <a class="btn btn-error text-base" href="./activities.php?subjectId=<?= $subjectId ?><?= isset($_GET['page']) ? '&page=' . $_GET['page'] : '' ?>">Go Back</a>
                                <?php if (!$hasGradeReleased): ?>
                                    <button class="btn btn-success text-base" name="save-scores" <?php if (count($students) == 0): ?> disabled <?php endif; ?>>Save</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </label>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    function enforceMinMax(e) {
        let score = parseInt(e.target.value);

        if(score < 0) {
            e.target.value = 0;
            return;
        }

        if (score > parseInt("<?= $activity['max_score'] ?>")) {
            e.target.value = <?= $activity['max_score'] ?>;
            return;
        }
    }
</script>