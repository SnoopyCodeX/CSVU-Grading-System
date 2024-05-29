<?php
session_start();
// kung walang session mag reredirect sa login //

require ("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';
require '../../utils/humanizer.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//

// Count all subjects that the instructor is handling
$subjectsQuery = $dbCon->query("SELECT 
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
    WHERE subject_instructors.instructor_id = " . AuthController::user()->id
);
$subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);
$subjectsCount = count($subjects);


// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result2 = mysqli_query($dbCon, "SELECT COUNT(*) as count FROM subject_instructor_sections WHERE subject_instructor_sections.instructor_id = " . AuthController::user()->id . " GROUP BY subject_instructor_sections.section_id");
$sectionsCount = mysqli_fetch_array($result2);
$total = $sectionsCount['count'];
$pages = ceil($total / $limit);

// get all sections that the instructor is handling. 
$sectionsQuery = "SELECT
    sections.*,
    courses.id as course_id,
    courses.course_code as course_code,
    courses.course as course_name
    FROM subject_instructor_sections
    LEFT JOIN sections ON subject_instructor_sections.section_id = sections.id
    LEFT JOIN courses ON sections.course = courses.id
    WHERE subject_instructor_sections.instructor_id = " . AuthController::user()->id . " GROUP BY subject_instructor_sections.section_id LIMIT $start, $limit";
$sectionsQueryResult = $dbCon->query($sectionsQuery);
$sections = $sectionsQueryResult->fetch_all(MYSQLI_ASSOC);

require_once ("../../components/header.php");
?>


<main class="flex overflow-x-auto md:overflow-x-hidden overflow-y-scroll h-screen">
    <?php require_once ("../layout/sidebar.php") ?>
    <section class=" w-full px-4">
        <?php require_once ("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4  overflow-y-hidden">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Assigned Sections</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto md:overflow-x-hidden border border-gray-300 rounded-md"
                style="height: calc(100vh - 150px)">
                <table class="table table-zebra table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <td class="bg-[#276bae] text-white text-center">Course / Year Level / Section</td>
                            <td class="bg-[#276bae] text-white text-center">Course</td>
                            <td class="bg-[#276bae] text-white text-center">Year Level</td>
                            <td class="bg-[#276bae] text-white text-center">Students</td>
                            <td class="bg-[#276bae] text-white text-center">Status</td>
                            <td class="bg-[#276bae] text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sections) > 0): ?>
                        <?php foreach ($sections as $row) { ?>
                        <tr class="hover">
                            <td class="text-center"><?= $row['course_code'] ?>
                                <?= str_split($row['year_level'])[0] ?>-<?= $row['name'] ?></td>
                            <td class="text-center"><?= $row['course_code'] ?></td>
                            <td class="text-center"><?= $row['year_level'] ?></td>
                            <td class="text-center"><?php
                                    // Only count students that are enrolled to the instructor's subjects
                                    $studentsQuery = $dbCon->query("SELECT * FROM section_students WHERE section_id = {$row['id']}");
                                    $students = $studentsQuery->fetch_all(MYSQLI_ASSOC);
                                    $filteredStudents = [];

                                    foreach ($subjects as $subject) {
                                        foreach ($students as $student) {
                                            $studentEnrolledSubjectsQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE student_id = " . $student['student_id']);
                                            $studentEnrolledSubjects = $studentEnrolledSubjectsQuery->fetch_all(MYSQLI_ASSOC);

                                            $enrolledSubjectIds = array_map(fn($enrolledSubject) => $enrolledSubject['subject_id'], $studentEnrolledSubjects);

                                            if (in_array($subject['subject_id'], $enrolledSubjectIds)) {
                                                $filteredStudents[] = $student;
                                                continue;
                                            }
                                        }
                                    }

                                    $filteredStudents = removeDuplicates($filteredStudents, 'student_id');
                                    echo count($filteredStudents);
                                    ?></td>
                            <td class="text-center">
                                <span class='badge p-4 bg-[#276bae] text-white text-black'>
                                    On going
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="./view/section_students.php?sectionId=<?= $row['id'] ?>&courseId=<?= $row['course_id'] ?>&yearLevel=<?= $row['year_level'] ?>"
                                    class="btn bg-[#276bae] text-white btn-sm">
                                    Students
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php else: ?>
                        <tr class="hover">
                            <td colspan="6" class="text-center">No sections to show</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end gap-4 ">
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
</main>