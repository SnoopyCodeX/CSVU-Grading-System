<?php
session_start();

require('../../vendor/autoload.php');

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasWarning = false;
$hasSearch = false;
$hasSearchError = false;
$message = "";
$warning = "";

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// Download template student excel data
if (isset($_POST['download-template-excel'])) {
    $template = "../../utils/templates/import-subjects-template.xlsx";
    $fragments = explode("/", $template);

    header('Content-Type: ' . mime_content_type($template));
    header('Content-Disposition: attachment;filename=' . end($fragments));
    header('Cache-Control: max-age=0');
    readfile($template);
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");


// Search subject
if (isset($_POST['search-subject'])) {
    $search = $dbCon->real_escape_string($_POST['search-subject']);
    $searchBy = $dbCon->real_escape_string($_POST['search-by']);

    if ($searchBy == 'course') {
        $courseResult = $dbCon->query("SELECT * FROM courses WHERE course LIKE '%$search%' OR course_code LIKE '%$search%'");
        $courseAssoc = $courseResult->fetch_all(MYSQLI_ASSOC);

        if ($courseResult->num_rows > 0) {
            $course = $search;
            $search = array_map(fn ($course) => $course['id'], $courseAssoc);
            $searchBy = 'course';
        } else {
            $course = $search;
            $search = [];
            $searchBy = 'course';
            $hasSearchError = true;
        }
    }

    $hasSearch = true;
}

// Edit subject
if (isset($_POST['update_subject'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $subjectName = $dbCon->real_escape_string($_POST['subject_name']);
    $subjectCode = $dbCon->real_escape_string($_POST['subject_code']);
    $units = $dbCon->real_escape_string($_POST['units']);
    $creditsUnits = $dbCon->real_escape_string($_POST['credits_units']);
    $term = $dbCon->real_escape_string($_POST['term']);

    $subjectExistQuery = $dbCon->query("SELECT * FROM subjects WHERE id = '$id'");

    if ($subjectExistQuery->num_rows <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject does not exist!";
    } else if (!is_numeric($units) || intval($units) <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject units must be a numeric value and must be a positive integer greater than 0!";
    } else if (!is_numeric($creditsUnits) || intval($creditsUnits) <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject credit units must be a numeric value and must be a positive integer greater than 0!";
    } else {
        $subjectCodeExistQuery = $dbCon->query("SELECT * FROM subjects WHERE code = '$subjectCode' AND course='$course' AND id <> $id");

        if ($subjectCodeExistQuery->num_rows > 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Subject code already exists!";
        } else {
            $query = "UPDATE subjects SET course='$course', year_level='$yearLevel', name='$subjectName', code='$subjectCode', units='$units', credits_units='$creditsUnits', term='$term' WHERE id='$id'";
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
}

// Delete subject
if (isset($_POST['delete_subject'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    $subjectExistQuery = $dbCon->query("SELECT * FROM subjects WHERE id = '$id'");

    if ($subjectExistQuery->num_rows <= 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Subject does not exist!";
    } else {
        $query = "DELETE FROM subjects WHERE id='$id'";
        $result = mysqli_query($dbCon, $query);

        if ($result) {
            // Get all activities for the current subject
            $activitiesQuery = $dbCon->query("SELECT * FROM activities WHERE subject = $id");

            if ($activitiesQuery->num_rows > 0) {
                $activities = $activitiesQuery->fetch_all(MYSQLI_ASSOC);

                // Loop through each activities
                foreach ($activities as $activity) {
                    // Delete all activity scores for the current activity
                    $dbCon->query("DELETE FROM activity_scores WHERE activity_id = {$activity['id']}");
                }

                // Delete all activities under the current subject
                $dbCon->query("DELETE FROM activities WHERE subject = $id");
            }

            // Delete all activities for the current subject
            $dbCon->query("DELETE FROM activities WHERE subject = $id");

            // Delete all grade release request for the current subject
            $dbCon->query("DELETE FROM instructor_grade_release_requests WHERE subject_id=$id");

            // Delete all enrolled subject from the student
            $dbCon->query("DELETE FROM student_enrolled_subjects WHERE subject_id = $id");

            // Delete all irregular subject from the student
            $dbCon->query("DELETE FROM section_students WHERE irregular_subject_id = $id");

            // Delete all student final grades
            $dbCon->query("DELETE FROM student_final_grades WHERE subject = $id");

            // Delete subject from instructor's assigned subjects
            $dbCon->query("DELETE FROM subject_instructors WHERE subject_id = $id");

            // Delete subject from instructor's assigned sections
            $dbCon->query("DELETE FROM subject_instructor_sections WHERE subject_id = $id");

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

// Import subject
if (isset($_POST['import_subject'])) {
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    $fileExt = explode('.', $fileName);
    $fileActualExt = strtolower(end($fileExt));

    $allowed = array('xlsx', 'csv');

    // Check if file type is allowed and check if the mime type is allowed
    if (in_array($fileActualExt, $allowed) && ($fileType === "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" || $fileType === "text/csv")) {
        if ($fileError === 0) {
            // Check if file size is less than 100MB
            if ($fileSize < (1000000) * 1024 * 1024) {
                $fileNameNew = uniqid(md5(strval(time())), true) . "." . $fileActualExt;
                $fileDestination = 'uploads/' . $fileNameNew;

                // auto create destination if it does not exist
                if (!file_exists('uploads')) {
                    @mkdir('uploads', 0777, true);
                }

                // move the file to the destination
                @move_uploaded_file($fileTmpName, $fileDestination);

                // check if the file is an excel or csv file, if it is an excel file, use PhpSpreadsheet to read the file, if it is a csv file, use fgetcsv to read the file
                if ($fileActualExt === "xlsx") {
                    $reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    $spreadsheet = $reader->load($fileDestination);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray();
                } else if ($fileActualExt === "csv") {
                    $file = fopen($fileDestination, "r");
                    $sheetData = array();

                    while (!feof($file)) {
                        $line = fgetcsv($file);

                        if (!is_array($line)) {
                            continue;
                        }

                        $sheetData[] = $line;
                    }

                    fclose($file);
                }

                // remove the header from the read data
                $header = array_map(fn ($head) => trim($head), array_shift($sheetData));
                $data = array();

                $diff = array_diff([
                    'Subject Code',
                    'Subject Name',
                    'Course Code',
                    'Units',
                    'Credit Units',
                    'Year Level',
                    'Semester'
                ], $header);

                // Check if all columns are present in the imported file
                if (count($diff) > 0) {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "Missing columns: <strong>" . implode(", ", $diff) . "</strong> in the imported file!";
                } else {
                    // combine the header and the data
                    foreach ($sheetData as $row) {
                        $data[] = array_combine($header, $row);
                    }

                    $oldDatCount = count($data);

                    // Filter out empty cells
                    $data = array_filter($data, function ($subject) {
                        if (
                            !empty(trim($subject['Subject Code'] ?? '')) &&
                            !empty(trim($subject['Subject Name'] ?? '')) &&
                            !empty(trim($subject['Course Code'] ?? '')) &&
                            !empty(trim($subject['Units'] ?? '')) &&
                            !empty(trim($subject['Credit Units'] ?? '')) &&
                            !empty(trim($subject['Year Level'] ?? '')) &&
                            !empty(trim($subject['Semester'] ?? ''))
                        )
                            return $subject;
                    });

                    $newDataCount = count($data);
                    $skippedDataCount = $oldDatCount - $newDataCount;
                    $skippedSubjectData = 0;
                    $successfulCount = 0;

                    if ($skippedDataCount > 0) {
                        $skippedSubjectData += $skippedDataCount;
                        $hasWarning = true;
                        $warning = "Skipped <strong>$skippedSubjectData subject data" . (($skippedSubjectData > 1) ? 's' : '') . "</strong> because the subject code already exists OR some of its data are empty!";
                    }

                    // check if there is data in the file
                    if (count($data) > 0) {
                        // insert query
                        $query = "INSERT INTO subjects(course, year_level, name, code, units, credits_units, term) VALUES";
                        $codes = [];

                        // loop through the data and validate
                        foreach ($data as $subject) {
                            $subjectCode = strtoupper(trim($dbCon->real_escape_string($subject['Subject Code'])));
                            $subjectName = ucwords(trim($dbCon->real_escape_string($subject['Subject Name'])));
                            $courseCode = strtoupper($dbCon->real_escape_string($subject['Course Code']));
                            $units = trim($dbCon->real_escape_string($subject['Units']));
                            $creditUnits = trim($dbCon->real_escape_string($subject['Credit Units']));
                            $yearLevel = trim($dbCon->real_escape_string($subject['Year Level']));
                            $semester = trim($dbCon->real_escape_string($subject['Semester']));

                            if (!in_array(strtolower($yearLevel), ['1st year', '2nd year', '3rd year', '4th year', '5th year'])) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "One of the subject's <strong>year level</strong> is invalid.";
                            } else if (!is_numeric($units) || intval($units) <= 0) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "One of the subject's <strong>units</strong> is invalid. Only numeric values are allowed and <strong>units</strong> must be a positive integer greater than 0!";
                            } else if (!is_numeric($creditUnits) || intval($creditUnits) <= 0) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "One of the subject's <strong>credit units</strong> is invalid. Only numeric values are allowed and <strong>credit units</strong> must be a positive integer greater than 0!";
                            } else if (!in_array(strtolower($semester), ['1st sem', '2nd sem', 'midyear', '1st semester', '2nd semester', 'midyear'])) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "One of the subject's <strong>semester</strong> is invalid.";
                            } else {
                                // Get course
                                $courseQuery = $dbCon->query("SELECT * FROM courses WHERE course_code = '$courseCode'");
                                $course = $courseQuery->fetch_assoc();

                                // If course code does not exist, skip this subject
                                if ($courseQuery->num_rows == 0) {
                                    $skippedSubjectData++;

                                    $hasWarning = true;
                                    $warning = "Skipped <strong>$skippedSubjectData subject data" . (($skippedSubjectData > 1) ? 's' : '') . "</strong> because the subject code already exists OR some of its data are empty!";

                                    continue;
                                }

                                // Check if subject code already exists in the database
                                $checkSubjectCodeQuery = $dbCon->query("SELECT * FROM subjects WHERE code='$subjectCode' AND course='$course[id]'");
                                if ($checkSubjectCodeQuery->num_rows > 0) {
                                    $skippedSubjectData++;

                                    $hasWarning = true;
                                    $warning = "Skipped <strong>$skippedSubjectData subject data" . (($skippedSubjectData > 1) ? 's' : '') . "</strong> because the subject code already exists OR some of its data are empty!";

                                    continue;
                                }

                                // Check if subject code has already been queued to be added
                                if (in_array("$courseCode-$subjectCode", $codes)) {
                                    $skippedSubjectData++;

                                    $hasWarning = true;
                                    $warning = "Skipped <strong>$skippedSubjectData subject data" . (($skippedSubjectData > 1) ? 's' : '') . "</strong> because the subject code already exists OR some of its data are empty!";

                                    continue;
                                } else
                                    $codes[] = "$courseCode-$subjectCode";

                                $successfulCount += 1;

                                $semester = match (strtolower($semester)) {
                                    '1st semester' => '1st Sem',
                                    '2nd semester' => '2nd Sem',
                                    'Midyear' => 'Midyear',
                                    default => ucwords($semester)
                                };

                                $yearLevel = strtolower($yearLevel);

                                $query .= "(
                                    '$course[id]',
                                    '$yearLevel',
                                    '$subjectName',
                                    '$subjectCode',
                                    '$units',
                                    '$creditUnits',
                                    '$semester'
                                ),";
                            }
                        }

                        // execute the query if there are no errors
                        if (!str_ends_with($query, "VALUES") && !$hasError) {
                            $query = substr($query, 0, -1);
                            $result = $dbCon->query($query);

                            if ($result) {
                                $hasError = false;
                                $hasSuccess = true;
                                $message = "Successfully imported <strong>$successfulCount subject" . ($successfulCount > 1 ? 's' : '') . "!</strong>";
                            } else {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "Failed to import subjects!";
                            }
                        }

                        // unset entered values
                        unset($subjectCode);
                        unset($subjectName);
                        unset($courseCode);
                        unset($units);
                        unset($creditUnits);
                        unset($yearLevel);
                        unset($semester);
                    } else {
                        $hasError = true;
                        $hasSuccess = false;
                        $message = "No data found in the file!";
                    }
                }

                // delete the file after importing
                @unlink($fileDestination);

                // delete the folder if it exists, this is to prevent the users from accessing the upload folder
                if (is_dir('uploads')) {
                    @rmdir('uploads');
                }
            } else {
                $hasError = true;
                $hasSuccess = false;
                $message = "Your file exceeeded the maximum file sie of 100MB!";
            }
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "An error occurred while uploading the file. Please try again.";
        }
    } else {
        $hasError = true;
        $hasSuccess = false;
        $message = "Invalid file type. Only <strong>EXCEL</strong> and <strong>CSV</strong> files are allowed!";
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Total pages
if ($hasSearch) {
    if ($searchBy == 'course') {
        if (count($search) > 0) {
            $result1 = $dbCon->query("SELECT 
                COUNT(*) AS count 
                FROM subjects 
                WHERE subjects.$searchBy IN (" . implode(',', $search) . ")
            ");
        }
    } else {
        $result1 = $dbCon->query("SELECT 
            COUNT(*) AS count 
            FROM subjects 
            WHERE subjects.$searchBy LIKE '%$search%'
        ");
    }
} else {
    $result1 = $dbCon->query("SELECT 
        COUNT(*) AS count 
        FROM subjects
    ");
}

if (isset($result1) && $result1->num_rows > 0) {
    $subjectCount = $result1->fetch_all(MYSQLI_ASSOC);
    $total = $subjectCount[0]['count'];
} else {
    $total = 0;
}

$pages = ceil($total / $limit);

// Prefetch all subjects
if ($hasSearch) {
    if ($searchBy == 'course') {
        if (count($search) > 0) {
            $subjects = $dbCon->query("SELECT 
                subjects.*
                FROM subjects 
                WHERE subjects.$searchBy IN (" . implode(',', $search) . ") 
                LIMIT $start, $limit
            ");
        }
    } else {
        $subjects = $dbCon->query("SELECT 
            subjects.*
            FROM subjects 
            WHERE subjects.$searchBy LIKE '%$search%' 
            LIMIT $start, $limit
        ");
    }
} else {
    $subjects = $dbCon->query("SELECT 
        subjects.*
        FROM subjects 
        LIMIT $start, $limit
    ");
}
?>


<main class="overflow-x-auto h-screen flex">
    <?php require_once("../layout/sidebar.php") ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4 mt-6">
            <!-- Table Header -->
            <div class="flex flex-col md:flex-row justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage Subjects</h1>
                </div>

                <div class="flex flex-col md:flex-row md:items-center gap-4 px-4 w-full md:w-auto">
                    <!-- Search bar (md screens up)-->
                    <form x-data="{placeholder: 'Search subject'}" method="POST" class="md:grid md:grid-cols-1 gap-4 hidden md:block" autocomplete="off">
                        <div class="join w-[280px] md:w-auto mb-2 md:mb-0">
                            <div>
                                <div>
                                    <input class="input input-bordered join-item" name="search-subject" :placeholder="placeholder" value="<?= (isset($search) ? ($searchBy == 'course' ? $course : $search) : '') ?>" required />
                                </div>
                            </div>

                            <select class="select select-bordered join-item hidden md:block" name="search-by" @change="placeholder = 'Search ' + $event.target.value.replace(/_/gi, ' ').replace(/term/gi, 'semester').replace(/name/gi, 'subject')" required>
                                <option disabled>Search by</option>
                                <option value="name" <?php if (isset($search) && strtolower($searchBy) == 'name') : ?> selected <?php endif; ?>>Subject</option>
                                <option value="course" <?php if (isset($search) && strtolower($searchBy) == 'course') : ?> selected <?php endif; ?>>Course</option>
                                <option value="year_level" <?php if (isset($search) && strtolower($searchBy) == 'year_level') : ?> selected <?php endif; ?>>Year Level
                                </option>
                                <option value="term" <?php if (isset($search) && strtolower($searchBy) == 'term') : ?> selected <?php endif; ?>>Semester</option>
                            </select>

                            <div class="indicator hidden md:block">
                                <button class="btn join-item">Search</button>
                            </div>
                        </div>
                    </form>

                    <!-- Search bar (mobile screen) -->
                    <form x-data="{placeholder: 'Search subject'}" method="POST" class="grid grid-cols-1 gap-2 block md:hidden !w-full" autocomplete="off">
                        <div class="join w-full md:w-auto mb-2 md:mb-0">
                            <input class="input input-bordered w-full join-item" name="search-subject" :placeholder="placeholder" value="<?= (isset($search) ? ($searchBy == 'course' ? $course : $search) : '') ?>" required />
                        </div>

                        <div class="flex gap-2 w-full block md:hidden">
                            <select class="select select-bordered block md:hidden w-full" name="search-by" @change="placeholder = 'Search ' + $event.target.value.replace(/_/gi, ' ').replace(/term/gi, 'semester').replace(/name/gi, 'subject')" required>
                                <option disabled>Search by</option>
                                <option value="name" <?php if (isset($search) && strtolower($searchBy) == 'name') : ?> selected <?php endif; ?>>Subject</option>
                                <option value="course" <?php if (isset($search) && strtolower($searchBy) == 'course') : ?> selected <?php endif; ?>>Course</option>
                                <option value="year_level" <?php if (isset($search) && strtolower($searchBy) == 'year_level') : ?> selected <?php endif; ?>>Year Level
                                </option>
                                <option value="term" <?php if (isset($search) && strtolower($searchBy) == 'term') : ?> selected <?php endif; ?>>Semester</option>
                            </select>

                            <div class=" indicator block md:hidden">
                                <button class="btn text-white bg-[#276bae]">Search</button>
                            </div>
                        </div>
                    </form>

                    <!-- Import Button -->
                    <button class="btn text-white bg-[#276bae]" onclick="import_file_modal.showModal()">
                        <i class="bx bx-import"></i>
                        Import
                    </button>

                    <!-- Create button -->
                    <a href="./create/subject.php" class="btn text-white bg-[#276bae]">
                        <i class="bx bx-plus-circle"></i>
                        Create
                    </a>
                </div>
            </div>

            <?php if ($hasWarning) { ?>
                <div role="alert" class="alert alert-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
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

            <!-- Table Content -->
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <th class="bg-slate-500 text-white">ID</th> -->
                            <th class="bg-[#276bae] text-white text-center">Subject Code</th>
                            <th class="bg-[#276bae] text-white text-center">Subject Name</th>
                            <th class="bg-[#276bae] text-white text-center">Course</th>
                            <th class="bg-[#276bae] text-white text-center">Units</th>
                            <th class="bg-[#276bae] text-white text-center">Credits</th>
                            <th class="bg-[#276bae] text-white text-center">Yearlevel</th>
                            <th class="bg-[#276bae] text-white text-center">Term</th>
                            <th class="bg-[#276bae] text-white text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!isset($subjects) || $subjects->num_rows == 0) { ?>
                            <tr class="hover">
                                <td colspan="9" class="text-center">No records found</td>
                            </tr>
                        <?php } else { ?>
                            <?php while ($subject = $subjects->fetch_assoc()) { ?>
                                <tr class="hover">
                                    <!-- <td><?= $subject['id'] ?></td> -->
                                    <td class="capitalize text-center"><?= $subject['code'] ?></td>
                                    <td class="capitalize text-center"><?= $subject['name'] ?></td>
                                    <td class="capitalize text-center">
                                        <?= $dbCon->query("SELECT * FROM courses WHERE id='{$subject['course']}'")->fetch_assoc()['course_code'] ?? '' ?>
                                    </td>
                                    <td class="text-center"><?= $subject['units'] ?></td>
                                    <td class="text-center"><?= $subject['credits_units'] ?></td>
                                    <td class="text-center"><?= $subject['year_level'] ?></td>
                                    <td class="text-center"><?= $subject['term'] ?></td>
                                    <td>
                                        <div class="flex justify-center gap-2">
                                            <a href="./view/subject_instructors.php?subject=<?= $subject['id'] ?><?= $page != 1 ? '&prev_page=' . $page : '' ?>" class="btn bg-[#276bae] btn-sm text-white">
                                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                                    <title>user_3_fill</title>
                                                    <g id="user_3_fill" fill='none' fill-rule='nonzero'>
                                                        <path d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                        <path fill='currentColor' d='M12 13c2.396 0 4.575.694 6.178 1.672.8.488 1.484 1.064 1.978 1.69.486.615.844 1.351.844 2.138 0 .845-.411 1.511-1.003 1.986-.56.45-1.299.748-2.084.956-1.578.417-3.684.558-5.913.558s-4.335-.14-5.913-.558c-.785-.208-1.524-.506-2.084-.956C3.41 20.01 3 19.345 3 18.5c0-.787.358-1.523.844-2.139.494-.625 1.177-1.2 1.978-1.69C7.425 13.695 9.605 13 12 13Zm0-11a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z' />
                                                    </g>
                                                </svg>

                                                <span>Instructors</span>
                                            </a>
                                            <label for="edit-subject-<?= $subject['id'] ?>" class="bg-gray-500 btn btn-sm text-white">
                                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                                    <title>edit_line</title>
                                                    <g id="edit_line" fill='none' fill-rule='nonzero'>
                                                        <path d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                        <path fill='currentColor' d='M13 3a1 1 0 0 1 .117 1.993L13 5H5v14h14v-8a1 1 0 0 1 1.993-.117L21 11v8a2 2 0 0 1-1.85 1.995L19 21H5a2 2 0 0 1-1.995-1.85L3 19V5a2 2 0 0 1 1.85-1.995L5 3h8Zm6.243.343a1 1 0 0 1 1.497 1.32l-.083.095-9.9 9.899a1 1 0 0 1-1.497-1.32l.083-.094 9.9-9.9Z' />
                                                    </g>
                                                </svg>
                                                <span>
                                                    Edit
                                                </span>
                                            </label>
                                            <label for="delete-subject-<?= $subject['id'] ?>" class="bg-red-500 btn btn-sm text-white">

                                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                                    <title>delete_2_fill</title>
                                                    <g id="delete_2_fill" fill='none' fill-rule='evenodd'>
                                                        <path d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                                        <path fill='currentColor' d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z' />
                                                    </g>
                                                </svg>
                                                <span>Delete</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end items-center gap-4">
                <a class="btn text-[24px] bg-[#276bae] text-white" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn bg-[#276bae] text-white" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px] bg-[#276bae] text-white" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Fetch all subjects again -->
    <?php
    if ($hasSearch) {
        if ($searchBy == 'course') {
            if (count($search) > 0) {
                $subjects = $dbCon->query("SELECT 
                    subjects.*
                    FROM subjects 
                    WHERE subjects.$searchBy IN (" . implode(',', $search) . ") 
                    LIMIT $start, $limit
                ");
            }
        } else {
            $subjects = $dbCon->query("SELECT 
                subjects.*
                FROM subjects 
                WHERE subjects.$searchBy LIKE '%$search%' 
                LIMIT $start, $limit
            ");
        }
    } else {
        $subjects = $dbCon->query("SELECT 
            subjects.*
            FROM subjects 
            LIMIT $start, $limit
        ");
    }
    ?>

    <!-- Modals -->
    <?php while ($subject = $subjects->fetch_assoc()) { ?>
        <!-- Edit Modal -->
        <input type="checkbox" id="edit-subject-<?= $subject['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box">
                <form class="flex flex-col gap-4 px-[32px] mb-auto" method="post">

                    <input type="hidden" name="id" value="<?= $subject['id'] ?>">

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <select class="select select-bordered" name="course" required>
                            <option value="" disabled>Select Course</option>
                            <?php $courses = $dbCon->query("SELECT * FROM courses"); ?>
                            <?php while ($course = $courses->fetch_assoc()) { ?>
                                <option value="<?php echo $course['id'] ?>" <?php if ($subject['course'] == $course['id']) { ?> selected <?php } ?>><?php echo $course['course'] . " - #" . $course['course_code'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required>
                            <option value="" disabled>Select Year level</option>
                            <option value="1st year" <?php if ($subject['year_level'] == "1st year") { ?> selected <?php } ?>>
                                1st year</option>
                            <option value="2nd year" <?php if ($subject['year_level'] == "2nd year") { ?> selected <?php } ?>>
                                2nd year</option>
                            <option value="3rd year" <?php if ($subject['year_level'] == "3rd year") { ?> selected <?php } ?>>
                                3rd year</option>
                            <option value="4th year" <?php if ($subject['year_level'] == "4th year") { ?> selected <?php } ?>>
                                4th year</option>
                        </select>
                    </label>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject Name</span>
                            <input class="input input-bordered" placeholder="Enter Subject Name" name="subject_name" value="<?= $subject['name'] ?>" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Subject Code</span>
                            <input class="input input-bordered" placeholder="Enter Subject Code" name="subject_code" value="<?= $subject['code'] ?>" required />
                        </label>
                    </div>

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
                                <option value="1st Sem" <?php if ($subject['term'] == "1st Sem") { ?> selected <?php } ?>>
                                    1st
                                    Sem</option>
                                <option value="2nd Sem" <?php if ($subject['term'] == "2nd Sem") { ?> selected <?php } ?>>
                                    2nd
                                    Sem</option>
                                <option value="Midyear" <?php if ($subject['term'] == "Midyear") { ?> selected <?php } ?>>
                                    Midyear</option>
                            </select>
                        </label>

                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <label class="btn btn-error " for="edit-subject-<?= $subject['id'] ?>">Cancel</label>
                        <button class="btn bg-[#276bae] text-white" name="update_subject">Update</button>
                    </div>

                </form>
            </div>
            <label class="modal-backdrop" for="edit-subject-<?= $subject['id'] ?>">Close</label>
        </div>

        <!-- Delete Modal -->
        <input type="checkbox" id="delete-subject-<?= $subject['id'] ?>" class="modal-toggle" />
        <div class="modal" role="dialog">
            <div class="modal-box border border-error border-2">
                <h3 class="text-lg font-bold text-error">Delete Subject</h3>
                <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this subject will
                    permanently remove it from the system.</p>

                <form class="flex justify-end gap-4 items-center" method="post">
                    <input type="hidden" name="id" value="<?= $subject['id'] ?>">

                    <label class="btn" for="delete-subject-<?= $subject['id'] ?>">Close</label>
                    <button class="btn btn-error" name="delete_subject">Delete</button>
                </form>
            </div>
            <label class="modal-backdrop" for="delete-subject-<?= $subject['id'] ?>">Close</label>
        </div>

    <?php } ?>

    <!-- Import file modal -->
    <dialog class="modal" id="import_file_modal">
        <div class="modal-box min-w-[474px]">
            <form class="hidden" id="downloadTemplateForm" method="post">
                <input type="hidden" name="download-template-excel">
            </form>
            <form class="flex flex-col gap-4" method="post" enctype="multipart/form-data">
                <h2 class="text-center text-[28px] font-bold">Import Subjects</h2>
                <p class="text-center text-[16px]">You can import subjects by uploading a <strong>CSV</strong> or
                    <strong>EXCEL</strong> file.
                </p>
                <label class="flex flex-col gap-2 mb-4">
                    <span class="font-bold text-[18px]">Upload file</span>
                    <input type="file" name="file" class="file-input file-input-sm md:file-input-md file-input-bordered w-full" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required />
                    <div class="label">
                        <span class="label-text-alt text-error">Only <kbd class="p-1">*.xlsx</kbd> and <kbd class="p-1">*.csv</kbd> files are allowed</span>
                    </div>
                </label>

                <div class="modal-action">
                    <button class="btn btn-sm md:btn-md text-black bg-gray-400 hover:text-white hover:bg-[#276bae] " type="button" onclick="downloadTemplate()"><i class="fa fa-download"></i> Download
                        template</button>
                    <button class="btn btn-sm md:btn-md text-black bg-gray-400 hover:text-white hover:bg-red-500" type="button" onclick="import_file_modal.close()">Cancel</button>
                    <button class="btn btn-sm md:btn-md text-white bg-[#276bae] " name="import_subject">Import</button>

                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

</main>

<script>
    function downloadTemplate(e) {
        document.querySelector("#downloadTemplateForm").submit();
        import_file_modal.close();
    }
</script>