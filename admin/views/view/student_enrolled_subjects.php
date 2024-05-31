<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-type: application/json');

    // Get sections
    if (isset($_GET['course']) && isset($_GET['yearLevel']) && isset($_GET['student']) && isset($_GET['section'])) {
        $courseId = $dbCon->real_escape_string($_GET['course']);
        $yearLevel = $dbCon->real_escape_string($_GET['yearLevel']);
        $student = $dbCon->real_escape_string($_GET['student']);

        $sectionsQuery = $dbCon->query("SELECT * FROM sections WHERE year_level='$yearLevel' AND course='$courseId'");
        $sections = $sectionsQuery->fetch_all(MYSQLI_ASSOC);

        header('Content-type: application/json');
        echo json_encode($sections, JSON_PRETTY_PRINT);

        exit;
    }
    // Get subjects
    else if(isset($_GET['course']) && isset($_GET['yearLevel']) && isset($_GET['student'])) {
        $courseId = $dbCon->real_escape_string($_GET['course']);
        $yearLevel = $dbCon->real_escape_string($_GET['yearLevel']);
        $student = $dbCon->real_escape_string($_GET['student']);

        // Fetch active school year
        $schoolYearQuery = $dbCon->query("SELECT * FROM school_year WHERE status='active'");
        $schoolYear = $schoolYearQuery->fetch_assoc();

        // Return empty if there is no active school year
        if ($schoolYearQuery->num_rows == 0) {
            header('Content-type: application/json');
            echo json_encode([], JSON_PRETTY_PRINT);
            exit;
        }

        // Fetch all enrolled subjects
        $enrolledSubjectsQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE student_id='$student'");
        $enrolledSubjects = $enrolledSubjectsQuery->fetch_all(MYSQLI_ASSOC);

        // Fetch all subjects from this student's course and year level and current semester
        $subjectsQuery = $dbCon->query("SELECT 
            *
            FROM subjects
            WHERE year_level='$yearLevel' AND course='$courseId'
        ");
        $subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

        // Filter not enrolled subjects
        $filteredNotEnrolledSubjects = [...array_filter($subjects, function($subject) use ($enrolledSubjects) {
            if (count($enrolledSubjects) > 0) {
                foreach ($enrolledSubjects as $enrolledSubject) {
                    if ($subject['id'] == $enrolledSubject['subject_id'])
                        return false;
                }
            }

            return true;
        })];

        // Filter out subjects that have already been released
        $filteredNotEnrolledSubjects = [...array_filter($filteredNotEnrolledSubjects, function($subject) use ($dbCon, $schoolYear) {
            $subjectReleasedQuery = $dbCon->query("SELECT 
                * 
                FROM instructor_grade_release_requests 
                WHERE subject_id = '$subject[id]' AND school_year='$schoolYear[id]' AND term='$schoolYear[semester]' AND status IN ('approved', 'pending', 'grade-released')
            ");

            return $subjectReleasedQuery->num_rows == 0;
        })];

        header('Content-type: application/json');
        echo json_encode($filteredNotEnrolledSubjects, JSON_PRETTY_PRINT);
        exit;
    }

    header('Content-type: application/json');
    echo json_encode([], JSON_PRETTY_PRINT);
    exit;
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// Student ID
$studentId = $dbCon->real_escape_string($_GET['student'] ?? '');
$prevPage = $dbCon->real_escape_string($_GET['prev_page'] ?? '');

if (!isset($_GET['student'])) {
    header("../manage-student.php");
    exit();
}

// Student details
$studentQuery = $dbCon->query("SELECT 
    userdetails.*,
    sections.name AS section_name,
    courses.course_code AS course_code,
    courses.id AS course_id,
    school_year.id AS school_year_id,
    school_year.semester AS school_year_term
    FROM userdetails 
    LEFT JOIN section_students ON section_students.student_id = userdetails.id
    LEFT JOIN sections ON section_students.section_id = sections.id
    LEFT JOIN courses ON sections.course = courses.id
    LEFT JOIN school_year ON sections.school_year = school_year.id
    WHERE userdetails.id = '$studentId'
");

if ($studentQuery->num_rows == 0) {
    header("../manage-student.php");
    exit();
}

// Student details
$student = $studentQuery->fetch_assoc();

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";
$search = "";

// Search section
if (isset($_POST['search-subject'])) {
    $search = $dbCon->real_escape_string($_POST['search-subject']);
    $hasSearch = true;
}

// Enroll subject
if (isset($_POST['enroll-subject'])) {
    $subject = $dbCon->real_escape_string($_POST['subject']);

    $checkIfStudentAlreadyEnrolledQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE subject_id='$subject' AND student_id='$studentId'");

    if ($checkIfStudentAlreadyEnrolledQuery->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "This student is already enrolled to this subject!";
    } else {
        $isIrregular = isset($_POST['is_irregular']);
        $section = $dbCon->real_escape_string($_POST['section'] ?? '');

        if ($isIrregular && empty($section)) {
            $hasError = true;
            $hasSuccess = false;
            $message = "If the subject is <strong>irregular</strong>, please assign a section on where the student should attend that subject.";
        } else {
            $enrollSubjectQuery = $dbCon->query("INSERT INTO student_enrolled_subjects (subject_id, student_id, is_irregular) VALUES(
                '$subject',
                '$studentId',
                '" . ($isIrregular ? '1' : '0') . "'
            )");
    
            if ($enrollSubjectQuery) {
                if ($isIrregular) {
                    $insertIntoSection = $dbCon->query("INSERT INTO section_students (section_id, student_id, is_irregular, irregular_subject_id) VALUES (
                        '$section',
                        '$studentId',
                        '1',
                        '$subject'
                    )");
                } 
    
                $hasSuccess = true;
                $hasError = false;
                $message = "Student has been successfully enrolled!";
            } else {    
                $hasError = true;
                $hasSuccess = false;
                $message = "Something went wrong while enrolling student to a subject";
            }
        }
    }
}

// Remove subject
if (isset($_POST['remove-subject'])) {
    $subject = $dbCon->real_escape_string($_POST['id']);

    $checkIfSubjectAlreadyGoneQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE subject_id='$subject' AND student_id='$studentId'");

    if ($checkIfSubjectAlreadyGoneQuery->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Student does not seem to be enrolled on the subject that you're trying to remove";
    } else {
        $enrolledSubjectData = $checkIfSubjectAlreadyGoneQuery->fetch_assoc();
        $deleteAssignedSectionQuery = $dbCon->query("DELETE FROM student_enrolled_subjects WHERE subject_id='$subject' AND student_id='$studentId'");

        if ($deleteAssignedSectionQuery) {
            if ($enrolledSubjectData['is_irregular'] == 1) {
                $deleteStudentFromSection = $dbCon->query("DELETE FROM section_students WHERE is_irregular = '1' AND student_id = '$studentId' AND irregular_subject_id = '$subject'");
            }

            $hasSuccess = true;
            $hasError = false;
            $message = "Successfully removed subject from the student's enrolled subjects!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Something went wrong while removing subject from the student's enrolled subjects";
        }
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;


$result1 = $dbCon->query("SELECT 
    COUNT(*) AS count
    FROM student_enrolled_subjects
    LEFT JOIN subjects ON student_enrolled_subjects.subject_id = subjects.id
    WHERE student_enrolled_subjects.student_id='$studentId'" . (($hasSearch) ? " AND (subjects.name LIKE '%$search%' OR subjects.code LIKE '%$search%')" : "") . ""
);
if(isset($result1) && $result1->num_rows > 0) {
    $subjectCount = $result1->fetch_all(MYSQLI_ASSOC);
    $total = $subjectCount[0]['count'];
} else {
    $total = 0;
}
$pages = ceil($total / $limit);

// Fetch all enrolled subjects
$enrolledSubjectsQuery = $dbCon->query("SELECT * FROM student_enrolled_subjects WHERE student_id='$studentId'");
$enrolledSubjects = $enrolledSubjectsQuery->fetch_all(MYSQLI_ASSOC);

// Fetch all subjects
$subjectsQuery = $dbCon->query("SELECT * FROM subjects");
$subjects = $subjectsQuery->fetch_all(MYSQLI_ASSOC);

// Filter enrolled subjects
$filteredEnrolledSubjects = array_filter($subjects, function($subject) use ($enrolledSubjects) {
    if (count($enrolledSubjects) > 0) {
        foreach ($enrolledSubjects as $enrolledSubject) {
            if ($subject['id'] == $enrolledSubject['subject_id']) {
                return true;
            }
        }
    }

    return false;
});

// Filter not enrolled subjects
$filteredNotEnrolledSubjects = array_filter($subjects, function($subject) use ($enrolledSubjects) {
    if (count($enrolledSubjects) > 0) {
        foreach ($enrolledSubjects as $enrolledSubject) {
            if ($subject['id'] == $enrolledSubject['subject_id'])
                return false;
        }
    }

    return true;
});

// Filter selected sections if there is a search
if ($hasSearch) {
    $filteredEnrolledSubjects = array_filter($filteredEnrolledSubjects, function($enrolledSubject) use ($search) {
        return (str_contains(strtolower($enrolledSubject['name']), strtolower($search)) || str_contains(strtolower($enrolledSubject['code']), strtolower($search)));
    });
}

// Regular or irregular
$filteredEnrolledSubjects = array_map(function($enrolledSubject) use ($enrolledSubjects) {
    $enrolledSubject['is_irregular'] = 0;

    foreach($enrolledSubjects as $enrolledSub) {
        if ($enrolledSubject['id'] == $enrolledSub['subject_id']) {
            $enrolledSubject['is_irregular'] = $enrolledSub['is_irregular'];
            $enrolledSubject['student_id'] = $enrolledSub['student_id'];
        }
    }

    return $enrolledSubject;
}, $filteredEnrolledSubjects);
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
                    <h1 class="text-[24px] font-semibold">Enrolled Subjects</h1>
                    <p>Student: <?= $student['firstName'] ?> <?= $student['middleName'] ?> <?= $student['lastName'] ?></p>
                    <p>Course/Year Level: <?= $student['course_code'] ?> - <?= $student['year_level'] ?></p>
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
                            <input type="search" name="search-subject" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search subject" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <a class="btn bg-[#276bae] text-white" href="../manage-student.php<?= !empty($prevPage) ? '?page=' . $prevPage : '' ?>"><i class="bx bxs-chevron-left"></i> Go Back</a>

                    <!-- Create button -->
                    <button class="btn bg-[#276bae] text-white" onclick="enroll_subject.showModal()"><i class="bx bx-plus-circle"></i> Enroll Subject</button>
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
                            <th class="bg-[#276bae] text-white text-center">Subject Code</th>
                            <th class="bg-[#276bae] text-white text-center">Subject Name</th>
                            <th class="bg-[#276bae] text-white text-center">Year Level</th>
                            <th class="bg-[#276bae] text-white text-center">Semester</th>
                            <th class="bg-[#276bae] text-white text-center">Course / Year Level / Section</th>
                            <th class="bg-[#276bae] text-white text-center">Status</th>
                            <th class="bg-[#276bae] text-white text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($filteredEnrolledSubjects) > 0): ?>
                            <?php foreach($filteredEnrolledSubjects as $enrolledSubject): ?>
                                <tr class="hover">
                                    <td class="text-center"><?= $enrolledSubject['code'] ?></td>
                                    <td class="text-center"><?= $enrolledSubject['name'] ?></td>
                                    <td class="text-center"><?= ucwords($enrolledSubject['year_level']) ?></td>
                                    <td class="text-center"><?= $enrolledSubject['term'] ?></td>
                                    <td class="text-center"><?php 
                                        // Display section
                                        if ($enrolledSubject['is_irregular'] == 1) {
                                            $sectionQuery = $dbCon->query("SELECT
                                                sections.*,
                                                courses.course_code as course_code
                                                FROM section_students
                                                LEFT JOIN sections ON section_students.section_id = sections.id
                                                LEFT JOIN courses ON sections.course = courses.id
                                                WHERE section_students.student_id = '$enrolledSubject[student_id]' AND section_students.is_irregular = '$enrolledSubject[is_irregular]' AND section_students.irregular_subject_id = '$enrolledSubject[id]'
                                            ");
                                        } else {
                                            $sectionQuery = $dbCon->query("SELECT
                                                sections.*,
                                                courses.course_code as course_code
                                                FROM section_students
                                                LEFT JOIN sections ON section_students.section_id = sections.id
                                                LEFT JOIN courses ON sections.course = courses.id
                                                WHERE section_students.student_id = '$enrolledSubject[student_id]' AND section_students.is_irregular = '$enrolledSubject[is_irregular]'
                                            ");
                                        }
                                        $section = $sectionQuery->fetch_assoc();
                                        
                                        echo "$section[course_code] " . str_split($section['year_level'])[0] . "-" . $section['name'];
                                    ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $enrolledSubject['is_irregular'] == 1 ? 'badge-warning' : 'badge-success' ?>">
                                            <?= $enrolledSubject['is_irregular'] == 1 ? 'Irregular' : 'Regular' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <label for="remove-subject-<?= $enrolledSubject['id'] ?>"  class="btn btn-error btn-sm">Remove</label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No enrolled subjects to show</td>
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

    <!-- Remove subject Modal -->
    <?php foreach ($filteredEnrolledSubjects as $enrolledSubject) { ?>

        <input type="checkbox" id="remove-subject-<?= $enrolledSubject['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Notice!</h3>
                <p class="py-4">Are you sure you want to remove <strong><?= "({$enrolledSubject['code']}) - {$enrolledSubject['name']}" ?></strong> from this student's enrolled subjects? This action cannot be undone!</p>

                <form class="flex justify-end gap-4 items-center" method="post">
                    <input type="hidden" name="id" value="<?= $enrolledSubject['id'] ?>">

                    <label class="btn" for="remove-subject-<?= $enrolledSubject['id'] ?>">Close</label>
                    <button class="btn btn-error" name="remove-subject">Remove</button>
                </form>
            </div>
            <label class="modal-backdrop" for="remove-subject-<?= $enrolledSubject['id'] ?>">Close</label>
        </div>

    <?php } ?>

    <!-- Add Modal -->
    <dialog class="modal" id="enroll_subject">
        <div class="modal-box min-w-[474px] overflow=auto">
            <form class="flex flex-col gap-4" method="post">
                <h2 class="text-center text-[28px] font-bold">Enroll Subject</h2>

                <!-- Course -->
                <label class="flex flex-col gap-2 mb-4">
                    <?php
                    $coursesQuery = $dbCon->query("SELECT * FROM courses");
                    $courses = $coursesQuery->fetch_all(MYSQLI_ASSOC);
                    ?>

                    <span class="font-bold text-[18px]">Course</span>
                    <select class="select select-bordered" name="course" required <?php if(count($courses) == 0): ?> disabled <?php endif; ?>>
                        <option value="" selected disabled><?= count($courses) > 0 ? 'Select course' : 'No available courses to show' ?></option>
                        
                        <?php if (count($courses) > 0): ?>
                            <?php foreach($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= "({$course['course_code']}) - {$course['course']}" ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <!-- Year Level -->
                <label class="flex flex-col gap-2 mb-4">
                    <span class="font-bold text-[18px]">Year Level</span>
                    <select class="select select-bordered" name="year_level" required disabled>
                        <option value="" selected disabled>Select year level</option>
                        
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                        <option value="5th Year">5th Year</option>
                    </select>
                </label>

                <!-- Subject -->
                <label class="flex flex-col gap-2 mb-4">
                    <span class="font-bold text-[18px]">Subject</span>
                    <select class="select select-bordered" name="subject" required disabled>
                        <option value="" selected disabled>Select subject</option>
                    </select>
                </label>
                
                <!-- Section (Will only be shown if the student is an irregular student, this is where we will assign a section to the irreg student) -->
                <label class="flex flex-col gap-2 mb-4 hidden" id="irregular-section">
                    <span class="font-bold text-[18px]">Section</span>
                    <select class="select select-bordered" name="section" required disabled>
                        <option value="" selected disabled>Select section</option>
                    </select>
                </label>

                <!-- Check box for if the student is irregular or not -->
                <label class="flex items-center gap-2 mb-4">
                    <input name="is_irregular" type="checkbox" class="toggle" />
                    <span class="label-text">Irregular Student</span> 
                </label>

                <div class="modal-action">
                    <button class="btn btn-sm md:btn-md btn-error text-base" type="button" onclick="enroll_subject.close()">Cancel</button>
                    <button class="btn btn-sm md:btn-md bg-[#276bae] text-white text-base" name="enroll-subject" disabled>Enroll Subject</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</main>

<script>
    const getSubjects = async () => {
        const course = document.querySelector("select[name='course']").value;
        const yearLevel = document.querySelector("select[name='year_level']").value;
        const subject = document.querySelector("select[name='subject']");
        const button = document.querySelector("button[name='enroll-subject']");

        if (!course || !yearLevel) 
            return;

        subject.innerHTML = "<option value disabled selected>Loading subjects...</option>";
        subject.setAttribute('disabled', '');

        const data = await fetch(`<?= $_SERVER['PHP_SELF'] ?>?student=<?= $studentId ?>&course=${course}&yearLevel=${yearLevel}`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    'Content-type': 'application/json'
                }
            })
            .then(res => res.json())

        if (data.length > 0) {
            subject.innerHTML = "<option selected disabled>Select subject</option>";
            subject.removeAttribute('disabled');
            button.removeAttribute('disabled');

            data.forEach((_subject) => {
                const option = document.createElement('option');
                option.setAttribute('value', _subject.id);

                const text = document.createTextNode(`(${_subject.code}) - ${_subject.name}`)
                option.appendChild(text);
                subject.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.setAttribute('value', '');
            option.setAttribute('selected', '');
            option.setAttribute('disabled', '');

            const textNode = document.createTextNode('No available subjects')
            option.appendChild(textNode);
            subject.appendChild(option);

            button.setAttribute('disabled', '');
        }
    };

    const getSections = async () => {
        const course = document.querySelector("select[name='course']").value;
        const yearLevel = document.querySelector("select[name='year_level']").value;
        const section = document.querySelector("select[name='section']");
        const button = document.querySelector("button[name='enroll-subject']");

        if (!course || !yearLevel) 
            return;

        section.innerHTML = "<option value disabled selected>Loading sections...</option>";
        section.setAttribute('disabled', '');

        const data = await fetch(`<?= $_SERVER['PHP_SELF'] ?>?student=<?= $studentId ?>&course=${course}&yearLevel=${yearLevel}&section=1`, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    'Content-type': 'application/json'
                }
            })
            .then(res => res.json())

        if (data.length > 0) {
            section.innerHTML = "<option selected disabled>Select section</option>";
            section.removeAttribute('disabled');
            button.removeAttribute('disabled');

            data.forEach((_section) => {
                const option = document.createElement('option');
                option.setAttribute('value', _section.id);

                const text = document.createTextNode(`${_section.name}`)
                option.appendChild(text);
                section.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.setAttribute('value', '');
            option.setAttribute('selected', '');
            option.setAttribute('disabled', '');

            const textNode = document.createTextNode('No available sections')
            option.appendChild(textNode);
            section.appendChild(option);

            button.setAttribute('disabled', '');
        }
    };

    document.querySelector("input[name='is_irregular']").addEventListener('change', function(e) {
        const toggle = document.querySelector("input[name='is_irregular']");
        const section = document.querySelector("label#irregular-section");
        const checked = e.target.checked;

        checked ? section.classList.remove('hidden') : section.classList.add('hidden');
        toggle.classList.toggle('toggle-success');

        if (checked)
            document.querySelector("input[name='section]'").setAttribute('required', '');
        else
            document.querySelector("input[name='section]'").removeAttribute('required');
    });

    document.querySelector("select[name='course']").addEventListener('change', function(e) {
        const yearLevel = document.querySelector("select[name='year_level']:disabled");

        if (yearLevel) {
            if (!!yearLevel.value) {
                // Trigger getting subjects
                getSubjects();
                getSections();
            }

            yearLevel.removeAttribute('disabled');
        } else {
            getSubjects();
            getSections();
        }
    });

    document.querySelector("select[name='year_level']").addEventListener('change', function(e) {
        // Trigger getting subjects
        getSubjects();
        getSections();
    });
</script>