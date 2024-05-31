<?php
session_start();

require ('../../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// kung walang session mag reredirect sa login //

require ("../../configuration/config.php");
require ('../../auth/controller/auth.controller.php');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (isset($_GET['courseId']) && isset($_GET['yearLevel'])) {
        $yearLevel = $dbCon->real_escape_string($_GET['yearLevel']);
        $courseId = $dbCon->real_escape_string($_GET['courseId']);

        $courseQuery = $dbCon->query("SELECT * FROM courses WHERE id='$courseId'");
        $course = $courseQuery->fetch_assoc();

        $sectionsQuery = $dbCon->query("SELECT * FROM sections WHERE course='$courseId' AND year_level='$yearLevel'");
        $sections = $sectionsQuery->fetch_all(MYSQLI_ASSOC);

        header('Content-type: application/json');
        echo json_encode($sections, JSON_PRETTY_PRINT);
    }

    exit;
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login.php");
    exit();
}

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$hasWarning = false;
$warning = "";
$message = "";

// Download template student excel data
if (isset($_POST['download-template-excel'])) {
    $template = "../../utils/templates/import-students-template.xlsx";
    $fragments = explode("/", $template);

    header('Content-Type: ' . mime_content_type($template));
    header('Content-Disposition: attachment;filename=' . end($fragments));
    header('Cache-Control: max-age=0');
    readfile($template);
    exit();
}

// export as excel using phpspreadsheet
if (isset($_POST['export-excel'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'First Name');
    $sheet->setCellValue('B1', 'Middle Name');
    $sheet->setCellValue('C1', 'Last Name');
    $sheet->setCellValue('D1', 'Email Address');
    $sheet->setCellValue('E1', 'Gender');
    $sheet->setCellValue('F1', 'Birthday');
    $sheet->setCellValue('G1', 'Contact Number');
    $sheet->setCellValue('H1', 'Student ID');
    $sheet->setCellValue('I1', 'Course Code');
    $sheet->setCellValue('J1', 'Year Level');
    $sheet->setCellValue('K1', 'Section Name');


    $query = "SELECT * FROM userdetails WHERE roles='student'";
    $result = $dbCon->query($query);

    if ($result->num_rows > 0) {
        $skippedStudentExport = 0;
        $i = 2;

        while ($row = $result->fetch_assoc()) {
            $sectionQuery = $dbCon->query("SELECT
                sections.*,
                courses.course_code as course_code
                FROM section_students
                LEFT JOIN sections ON section_students.section_id = sections.id
                LEFT JOIN courses ON sections.course = courses.id
                WHERE section_students.student_id = '$row[id]' AND section_students.is_irregular='0'
            ");

            if ($sectionQuery->num_rows == 0) {
                $skippedStudentExport += 1;
                $hasWarning = true;
                $warning = "Skipped $skippedStudentExport student data" . (($skippedStudentExport > 1) ? 's' : '') . " during the export process because" . (($skippedStudentExport > 1) ? 'they' : 'he/she') . " does not have a course and section!";
                continue;
            }

            $section = $sectionQuery->fetch_assoc();

            $sheet->setCellValue('A' . $i, $row['firstName']);
            $sheet->setCellValue('B' . $i, $row['middleName']);
            $sheet->setCellValue('C' . $i, $row['lastName']);
            $sheet->setCellValue('D' . $i, $row['email']);
            $sheet->setCellValue('E' . $i, $row['gender']);
            $sheet->setCellValue('F' . $i, $row['birthday']);
            $sheet->setCellValue('G' . $i, '="' . $row['contact'] . '"');
            $sheet->setCellValue('H' . $i, '="' . $row['sid'] . '"');
            $sheet->setCellValue('I' . $i, $section['course_code']);
            $sheet->setCellValue('J' . $i, $row['year_level']);
            $sheet->setCellValue('K' . $i, $section['name']);
            $i++;
        }

        $filename = "exported-students-" . date('Y-m-d') . ".xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=' . $filename);
        header('Cache-Control: max-age=0');
        readfile($filename);

        @unlink($filename);
        exit();
    }

    mysqli_free_result($result);
}

// export as csv
if (isset($_POST['export-csv'])) {
    $filename = "exported-students-" . date('Y-m-d') . ".csv";
    $query = "SELECT * FROM userdetails WHERE roles='student'";
    $result = $dbCon->query($query);

    if ($result->num_rows > 0) {
        $skippedStudentExport = 0;

        $fp = fopen($filename, 'w');
        fputcsv($fp, array(
            'First Name',
            'Middle Name',
            'Last Name',
            'Gender',
            'Birthday',
            'Contact Number',
            'Email Address',
            'Student ID',
            'Course Code',
            'Year Level',
            'Section Name'
        )
        );

        while ($row = $result->fetch_assoc()) {
            $sectionQuery = $dbCon->query("SELECT
                sections.*,
                courses.course_code as course_code
                FROM section_students
                LEFT JOIN sections ON section_students.section_id = sections.id
                LEFT JOIN courses ON sections.course = courses.id
                WHERE section_students.student_id = '$row[id]' AND section_students.is_irregular='0'
            ");

            if ($sectionQuery->num_rows == 0) {
                $skippedStudentExport += 1;
                $hasWarning = true;
                $warning = "Skipped $skippedStudentExport student data" . (($skippedStudentExport > 1) ? 's' : '') . " during the export process because" . (($skippedStudentExport > 1) ? 'they' : 'he/she') . " does not have a course and section!";
                continue;
            }

            $section = $sectionQuery->fetch_assoc();

            fputcsv($fp, array(
                $row['firstName'],
                $row['middleName'],
                $row['lastName'],
                $row['gender'],
                $row['birthday'],
                '="' . $row['contact'] . '"',
                $row['email'],
                '="' . $row['sid'] . '"',
                $section['course_code'],
                $row['year_level'],
                $section['name']
            )
            );
        }

        fclose($fp);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        readfile($filename);

        // Delete csv file after exporting
        @unlink($filename);

        exit();
    }

    mysqli_free_result($result);
}

// pag meron session mag rerender yung dashboard//
require_once ("../../components/header.php");

// search student
if (isset($_POST['search-student'])) {
    $search = $dbCon->real_escape_string($_POST['search-student']);
    $hasSearch = true;
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
if ($hasSearch) {
    $result1 = $dbCon->query("SELECT count(*) AS count FROM userdetails WHERE (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%' OR sid LIKE '%$search%') AND roles='student'");
} else {
    $result1 = $dbCon->query("SELECT count(*) AS count FROM userdetails WHERE roles='student'");
}

$students = $result1->fetch_assoc();
$total = $students['count'];
$pages = ceil($total / $limit);

// Import students
if (isset($_POST['import_student'])) {
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
                $fileNameNew = uniqid('', true) . "." . $fileActualExt;
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
                $header = array_map(fn($head) => trim($head), array_shift($sheetData));
                $data = array();

                $diff = array_diff([
                    'Student ID',
                    'First Name',
                    'Middle Name',
                    'Last Name',
                    'Gender',
                    'Contact Number',
                    'Birthday',
                    'Email Address',
                    'Course Code',
                    'Year Level',
                    'Section Name',
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
                    $data = array_filter($data, function ($student) {
                        if (
                            !empty (trim($student['Student ID'] ?? '')) &&
                            !empty (trim($student['First Name'] ?? '')) &&
                            !empty (trim($student['Last Name'] ?? '')) &&
                            !empty (trim($student['Gender'] ?? '')) &&
                            !empty (trim($student['Contact Number'] ?? '')) &&
                            !empty (trim($student['Birthday'] ?? '')) &&
                            !empty (trim($student['Email Address'] ?? '')) &&
                            !empty (trim($student['Course Code'] ?? '')) &&
                            !empty (trim($student['Year Level'] ?? '')) &&
                            !empty (trim($student['Section Name'] ?? ''))
                        )
                            return $student;
                    });

                    $newDataCount = count($data);
                    $skippedDataCount = $oldDatCount - $newDataCount;
                    $skippedStudentData = 0;
                    $successfulCount = 0;

                    if ($skippedDataCount > 0) {
                        $skippedStudentData += $skippedDataCount;
                        $hasWarning = true;
                        $warning = "Skipped $skippedStudentData student data" . (($skippedStudentData > 1) ? 's' : '') . " because the" . (($skippedStudentData > 1) ? 'ir' : '') . " student ID or email address already exists OR some of the" . (($skippedStudentData > 1) ? 'ir' : '') . " data are empty!";
                    }

                    // check if there is data in the file
                    if (count($data) > 0) {
                        // insert query
                        $insertStudentQuery = "INSERT INTO userdetails(firstName, middleName, lastName, gender, contact,  birthday, email, password, year_level, roles, sid) VALUES";
                        $insertStudentEmailQuery = "INSERT INTO pending_account_mails(email, raw_password) VALUES";
                        $emails = [];
                        $sids = [];
                        $sections = [];

                        // loop through the data and validate
                        foreach ($data as $student) {
                            $studentId = trim($dbCon->real_escape_string($student['Student ID']));
                            $firstName = trim($dbCon->real_escape_string($student['First Name']));
                            $middleName = trim($dbCon->real_escape_string($student['Middle Name'] ?? ''));
                            $lastName = trim($dbCon->real_escape_string($student['Last Name']));
                            $gender = strtolower(trim($dbCon->real_escape_string($student['Gender'])));
                            $contact = $dbCon->real_escape_string($student['Contact Number']);
                            $birthday = trim($dbCon->real_escape_string($student['Birthday']));
                            $email = filter_var(trim($dbCon->real_escape_string($student['Email Address'])), FILTER_VALIDATE_EMAIL);
                            $yearLevel = trim($dbCon->real_escape_string($student['Year Level']));
                            $course = trim($dbCon->real_escape_string($student['Course Code']));
                            $section = trim($dbCon->real_escape_string($student['Section Name']));

                            if (str_contains($birthday, "/")) {
                                $birthday = str_replace("/", "-", $birthday);
                                $birthday = date("Y-m-d", strtotime($birthday));
                            }

                            if ($fileActualExt === 'csv') {
                                // remove \=" and \" from contact, sid and password
                                $contact = substr($contact, 3, -2);
                                $studentId = substr($studentId, 3, -2);
                            }

                            if (!$email) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "Please enter a valid email address";
                                break;
                            } else if (!str_ends_with($email, "@cvsu.edu.ph")) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "One of the imported student data does not have a valid email address. It should use his/her <strong>@cvsu.edu.ph</strong> email address.";
                                break;
                            } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
                                break;
                            } else if (!in_array(strtolower($yearLevel), ['1st year', '2nd year', '3rd year', '4th year', '5th year'])) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "One of the students have an invalid year level, please enter a valid year level!";
                                break;
                            } else if ($dbCon->query("SELECT * FROM courses WHERE course_code = '$course'")->num_rows == 0) {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "One of the students have an invalid course code, please enter a valid course code!";
                                break;
                            } else {
                                // Get course
                                $courseQuery = $dbCon->query("SELECT * FROM courses WHERE course_code = '$course'");
                                $course = $courseQuery->fetch_assoc();

                                // Check if section name is valid
                                $sectionQuery = $dbCon->query("SELECT * FROM sections WHERE name = '$section' AND course = '$course[id]' AND year_level = '" . ucwords(strtolower($yearLevel)) . "'");

                                if ($sectionQuery->num_rows == 0) {
                                    $hasError = true;
                                    $hasSuccess = false;
                                    $message = "One of the students have an invalid section name, please enter a valid section name!";
                                    break;
                                }

                                $sectionData = $sectionQuery->fetch_assoc();

                                // skip if email or student id is already in the database
                                if ($dbCon->query("SELECT * FROM userdetails WHERE email = '$email' OR sid = '$studentId'")->num_rows > 0) {
                                    $skippedStudentData++;

                                    $hasWarning = true;
                                    $warning = "Skipped $skippedStudentData student data" . (($skippedStudentData > 1) ? 's' : '') . " because the" . (($skippedStudentData > 1) ? 'ir' : '') . " student ID or email address already exists OR some of the" . (($skippedStudentData > 1) ? 'ir' : '') . " data are empty!";

                                    continue;
                                }

                                // Check if email has already been queued to be added
                                if (in_array($email, $emails)) {
                                    $skippedStudentData++;

                                    $hasWarning = true;
                                    $warning = "Skipped $skippedStudentData student data" . (($skippedStudentData > 1) ? 's' : '') . " because the" . (($skippedStudentData > 1) ? 'ir' : '') . " student ID or email address already exists OR some of the" . (($skippedStudentData > 1) ? 'ir' : '') . " data are empty!";

                                    continue;
                                } else
                                    $emails[] = $email;

                                // Check if student id has already been queued to be added
                                if (in_array($studentId, $sids)) {
                                    $skippedStudentData++;

                                    $hasWarning = true;
                                    $warning = "Skipped $skippedStudentData student data" . (($skippedStudentData > 1) ? 's' : '') . " because the" . (($skippedStudentData > 1) ? 'ir' : '') . " student ID or email address already exists OR some of the" . (($skippedStudentData > 1) ? 'ir' : '') . " data are empty!";

                                    continue;
                                } else
                                    $sids[] = $studentId;

                                $password = constant("USER_DEFAULT_PASSWORD");
                                $sections[$studentId] = $sectionData['id'];
                                $successfulCount += 1;

                                $insertStudentQuery .= "(
                                    '$firstName',
                                    '$middleName',
                                    '$lastName',
                                    '$gender',
                                    '$contact',
                                    '$birthday',
                                    '$email',
                                    '" . crypt($password, '$6$Crypt$') . "',
                                    '$yearLevel',
                                    'student',
                                    '$studentId'
                                ),";

                                $insertStudentEmailQuery .= "(
                                    '$email',
                                    '$password'
                                ),";
                            }
                        }

                        // execute the query if there are no errors
                        if (!str_ends_with($insertStudentQuery, "VALUES") && !$hasError) {
                            $insertStudentQuery = substr($insertStudentQuery, 0, -1);
                            $insertStudentEmailQuery = substr($insertStudentEmailQuery, 0, -1);
                            $result1 = $dbCon->query($insertStudentQuery);
                            $result2 = $dbCon->query($insertStudentEmailQuery);

                            if ($result1) {
                                foreach ($sids as $sid) {
                                    $studentDataQuery = $dbCon->query("SELECT * FROM userdetails WHERE sid = '$sid' AND roles = 'student'");

                                    if ($studentDataQuery->num_rows > 0) {
                                        $studentData = $studentDataQuery->fetch_assoc();
                                        $assignedSection = $sections[$sid];

                                        $dbCon->query("INSERT INTO section_students (section_id, student_id) VALUES(
                                            '$assignedSection',
                                            '$studentData[id]'
                                        )");
                                    }
                                }

                                $hasError = false;
                                $hasSuccess = true;
                                $message = "Successfully imported <strong>$successfulCount student" . ($successfulCount > 1 ? 's' : '') . "!</strong>";
                            } else {
                                $hasError = true;
                                $hasSuccess = false;
                                $message = "Failed to import students!";
                            }
                        }

                        // unset entered values
                        unset($studentId);
                        unset($firstName);
                        unset($middleName);
                        unset($lastName);
                        unset($gender);
                        unset($contact);
                        unset($birthday);
                        unset($email);
                        unset($course);
                        unset($yearLevel);
                        unset($section);
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

// update student
if (isset($_POST['update_student'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $studentId = $dbCon->real_escape_string($_POST['student_id']);
    $firstName = $dbCon->real_escape_string($_POST['first_name']);
    $middleName = $dbCon->real_escape_string($_POST['middle_name']);
    $lastName = $dbCon->real_escape_string($_POST['last_name']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = str_replace("-", "", $dbCon->real_escape_string($_POST['contact']));
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $section = $dbCon->real_escape_string($_POST['section'] ?? '');
    // $newPassword = $dbCon->real_escape_string($_POST['new-password']);
    // $confirmPassword = $dbCon->real_escape_string($_POST['confirm-password']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address";
    } else if (empty($section)) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter section of the student!";
    } else if (!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please use the student's <strong>@cvsu.edu.ph</strong> email address.";
    } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
    } else if ($dbCon->query("SELECT * FROM userdetails WHERE id='$id' AND roles = 'student'")->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Student does not exist!";
    } else {
        // get student details that matches the given id
        $result = $dbCon->query("SELECT * FROM userdetails WHERE id='$id' AND roles = 'student'");

        if ($result->num_rows == 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Student does not exist!";
        } else {
            $student = $result->fetch_assoc();

            // update student query 
            $query = "UPDATE userdetails SET 
                sid='$studentId',
                firstName='$firstName',
                middleName='$middleName',
                lastName='$lastName',
                email='$email',
                gender='$gender',
                birthday='$birthday',
                contact='$contact',
                year_level='$yearLevel'
            ";

            /* if ($newPassword) {
                // check if new password matches with the confirm password
                $newPasswordHashed = crypt($newPassword, '$6$Crypt$');
                $confirmPasswordHashed = crypt($confirmPassword, '$6$Crypt$');

                if ($newPasswordHashed != $confirmPasswordHashed) {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "The given passwords doesn't match!";
                } else {
                    $query .= ", password='" . $newPasswordHashed . "'";
                }
            } */

            if (!$hasError) {
                $query .= " WHERE id='$id'";

                $update = $dbCon->query($query);

                if ($update) {
                    $deleteStudentFromSection = $dbCon->query("DELETE FROM section_students WHERE student_id='$id' AND is_irregular='0'");
                    $insertAgain = $dbCon->query("INSERT INTO section_students(section_id, student_id) VALUES('$section', '$id')");

                    $hasError = false;
                    $hasSuccess = true;
                    $message = "Successfully updated student!";
                } else {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "Failed to update student!";
                }
            }

        }
    }
}

// delete student
if (isset($_POST['delete-student'])) {
    $id = $dbCon->real_escape_string($_POST['id']);

    if ($dbCon->query("SELECT * FROM userdetails WHERE id='$id' AND roles = 'student'")->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Student does not exist!";
    } else {
        $query = "DELETE FROM userdetails WHERE id='$id'";
        $delete = $dbCon->query($query);

        // check if student id is also in section_students. If so, delete it as well
        if ($dbCon->query("SELECT * FROM section_students WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM section_students WHERE student_id='$id'");
        }

        // check if student id is also in activity_scires. If so, delete it as well
        if ($dbCon->query("SELECT * FROM activity_scores WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM activity_scores WHERE student_id='$id'");
        }

        // check if student id is also in student_final_grades. If so, delete it as well
        if ($dbCon->query("SELECT * FROM student_final_grades WHERE student='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM student_final_grades WHERE student='$id'");
        }

        // check if student id is also in student_final_grades. If so, delete it as well
        if ($dbCon->query("SELECT * FROM student_enrolled_subjects WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM student_enrolled_subjects WHERE student_id='$id'");
        }

        // check if student id is also in grade_requests. If so, delete it as well
        if ($dbCon->query("SELECT * FROM grade_requests WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM grade_requests WHERE student_id='$id'");
        }

        if ($delete) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Successfully deleted student!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Failed to delete student!";
        }
    }
}

// Prefetch all students query
if ($hasSearch) {
    $query = "SELECT 
        userdetails.* ,
        courses.course_code AS course,
        sections.name AS section,
        sections.id AS sectionId,
        section_students.is_irregular
        FROM userdetails 
        LEFT JOIN section_students ON section_students.student_id = userdetails.id
        LEFT JOIN sections ON section_students.section_id = sections.id
        LEFT JOIN courses ON sections.course = courses.id
        WHERE (CONCAT(userdetails.firstName, ' ', userdetails.middleName, ' ', userdetails.lastName) LIKE '%$search%' 
        OR userdetails.email LIKE '%$search%' OR userdetails.sid LIKE '%$search%') 
        AND userdetails.roles='student'
        LIMIT $start, $limit
    ";
} else {
    $query = "SELECT 
        userdetails.* ,
        courses.course_code AS course,
        sections.name AS section,
        sections.id AS sectionId,
        section_students.is_irregular
        FROM userdetails 
        LEFT JOIN section_students ON section_students.student_id = userdetails.id
        LEFT JOIN sections ON section_students.section_id = sections.id
        LEFT JOIN courses ON sections.course = courses.id
        WHERE userdetails.roles='student' 
        LIMIT $start, $limit
    ";
}

$coursesQuery = $dbCon->query("SELECT * FROM courses");
$courses = $coursesQuery->fetch_all(MYSQLI_ASSOC);
?>

<main class="w-screen overflow-x-auto flex">
    <?php require_once ("../layout/sidebar.php") ?>
    <section class="h-screen w-full px-4">
        <?php require_once ("../layout/topbar.php") ?>


        <div class="px-4 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex  flex-col md:flex-row justify-between md:items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Manage Students</h1>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 md:items-center gap-4 px-0 md:px-4">
                    <!-- Search bar -->
                    <form class="w-auto md:w-full" method="POST" autocomplete="off">
                        <label for="default-search"
                            class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="search" name="search-student" id="default-search"
                                class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Search name, email or I.D." value="<?= $hasSearch ? $search : '' ?>"
                                required>
                            <button type="submit"
                                class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </button>
                        </div>
                    </form>

                    <div class="w-full grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Import -->
                        <button class="btn hover:bg-[#276bae] hover:text-white" onclick="import_file_modal.showModal()">
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                <title>file_import_fill</title>
                                <g id="file_import_fill" fill='none' fill-rule='nonzero'>
                                    <path
                                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                    <path fill='currentColor'
                                        d='M12 2v6.5a1.5 1.5 0 0 0 1.5 1.5H20v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1h3.414l-1.121 1.121a1 1 0 1 0 1.414 1.415l2.829-2.829a1 1 0 0 0 0-1.414l-2.829-2.828a1 1 0 1 0-1.414 1.414L7.414 17H4V4a2 2 0 0 1 2-2h6ZM4 17v2H3a1 1 0 1 1 0-2h1ZM14 2.043a2 2 0 0 1 1 .543L19.414 7a2 2 0 0 1 .543 1H14V2.043Z' />
                                </g>
                            </svg>

                            Import
                        </button>

                        <!-- Export Button -->
                        <div class="dropdown dropdown-end">
                            <button tabindex="0" role="button"
                                class="btn  hover:bg-[#276bae] hover:text-white !w-full md:!w-auto">
                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                    <title>file_export_fill</title>
                                    <g id="file_export_fill" fill='none' fill-rule='nonzero'>
                                        <path
                                            d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                        <path fill='currentColor'
                                            d='M12 2v6.5a1.5 1.5 0 0 0 1.5 1.5H20v5.757l-1.293-1.293a1 1 0 1 0-1.414 1.415L18.414 17H14a1 1 0 1 0 0 2h4.414l-1.121 1.121a1 1 0 0 0 1.414 1.415l1.276-1.277A2 2 0 0 1 18 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h6Zm8 13.757 1.535 1.536a1 1 0 0 1 0 1.414L20 20.243v-4.486ZM14 2.043a2 2 0 0 1 1 .543L19.414 7a2 2 0 0 1 .543 1H14V2.043Z' />
                                    </g>
                                </svg>

                                Export
                            </button>
                            <ul tabindex="0"
                                class="dropdown-content z-[99] menu p-2 shadow bg-base-100 rounded-box w-52">
                                <li>
                                    <label for="export_excel_modal" onclick="export_excel_modal.showModal()">
                                        <i class="fa fa-file-excel"></i> 
                                        Excel
                                    </label>
                                </li>
                                <li>
                                    <label for="export_csv_modal" onclick="export_csv_modal.showModal()">
                                        <i class="fa fa-file-csv"></i> 
                                        CSV
                                    </label>
                                </li>
                            </ul>
                        </div>

                        <!-- Create button -->
                        <a href="./create/student.php" class="btn bg-[#276bae] text-white">
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                <title>add_circle_fill</title>
                                <g id="add_circle_fill" fill='none' fill-rule='nonzero'>
                                    <path
                                        d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z' />
                                    <path fill='currentColor'
                                        d='M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2Zm0 5a1 1 0 0 0-.993.883L11 8v3H8a1 1 0 0 0-.117 1.993L8 13h3v3a1 1 0 0 0 1.993.117L13 16v-3h3a1 1 0 0 0 .117-1.993L16 11h-3V8a1 1 0 0 0-1-1Z' />
                                </g>
                            </svg>

                            Create
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($hasWarning) { ?>
            <div role="alert" class="alert alert-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?= $warning ?></span>
            </div>
            <?php } ?>

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
            <div class="overflow-auto border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-zebra table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr class="hover">
                            <!-- <th class="bg-slate-500 text-white">ID</th> -->
                            <td class="bg-[#276bae] text-white text-center">Student ID</td>
                            <td class="bg-[#276bae] text-white">Name</td>
                            <td class="bg-[#276bae] text-white">Email</td>
                            <td class="bg-[#276bae] text-white text-center">Sex</td>
                            <td class="bg-[#276bae] text-white text-center">Course</td>
                            <td class="bg-[#276bae] text-white text-center">Year Level/Section</td>
                            <td class="bg-[#276bae] text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $dbCon->query($query);

                        if ($result->num_rows > 0) {
                            $students = $result->fetch_all(MYSQLI_ASSOC);

                            foreach ($students as $row) {
                                if ($row['is_irregular'] == '1')
                                    continue;

                                echo "
                                    <tr class='hover'>
                                        <td class='text-center'>{$row['sid']}</td>
                                        <td>{$row['firstName']} {$row['middleName']} {$row['lastName']}</th>
                                        <td>{$row['email']}</td>
                                        <td class='text-center'>" . ucfirst($row['gender']) . "</td>
                                        <td class='text-center'>" . (isset($row['course']) ? ($row['course']) : 'No course') . "</td>
                                        <td class='text-center'>" . (isset($row['section']) ? (str_split($row['year_level'])[0] . '-' . $row['section']) : 'No section') . "</td>
                                        <td>
                                            <div class='flex gap-2 justify-center items-center'>
                                                <a href='./view/student_enrolled_subjects.php?student={$row['id']}" . ($page != 1 ? '&prev_page=' . $page : '') . "' class='btn bg-[#276bae] btn-sm text-white " . (!isset($row['course']) ? 'btn-disabled' : '') . "'>Enrolled Subjects</a>
                                                <label for='view-student-{$row['id']}' class='btn btn-sm bg-[#276bae] text-white'>
                                                
                                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><title>eye_2_fill</title><g id='eye_2_fill' fill='none' fill-rule='nonzero'><path d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z'/><path fill='currentColor' d='M12 5c3.679 0 8.162 2.417 9.73 5.901.146.328.27.71.27 1.099 0 .388-.123.771-.27 1.099C20.161 16.583 15.678 19 12 19c-3.679 0-8.162-2.417-9.73-5.901C2.124 12.77 2 12.389 2 12c0-.388.123-.771.27-1.099C3.839 7.417 8.322 5 12 5Zm0 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z'/></g></svg>
                                                
                                                View</label>
                                                <label for='edit-student-{$row['id']}' onclick='updateSectionOptions({$row['id']}, {$row['sectionId']})' class='btn btn-sm bg-gray-500 text-white'>
                                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><title>edit_line</title><g  fill='none' fill-rule='nonzero'><path d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z'/><path fill='currentColor' d='M13 3a1 1 0 0 1 .117 1.993L13 5H5v14h14v-8a1 1 0 0 1 1.993-.117L21 11v8a2 2 0 0 1-1.85 1.995L19 21H5a2 2 0 0 1-1.995-1.85L3 19V5a2 2 0 0 1 1.85-1.995L5 3h8Zm6.243.343a1 1 0 0 1 1.497 1.32l-.083.095-9.9 9.899a1 1 0 0 1-1.497-1.32l.083-.094 9.9-9.9Z'/></g></svg>
                                                <span>Edit</span>
                                                
                                                </label>
                                                <label for='delete-student-{$row['id']}' class='btn btn-sm bg-red-500 text-white'>
                                                
                                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><title>delete_2_fill</title><g  fill='none' fill-rule='evenodd'><path d='M24 0v24H0V0h24ZM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01-.184-.092Z'/><path fill='currentColor' d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2h4.558ZM9 10a1 1 0 0 0-.993.883L8 11v6a1 1 0 0 0 1.993.117L10 17v-6a1 1 0 0 0-1-1Zm6 0a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-1-1Zm-.72-6H9.72l-.333 1h5.226l-.334-1Z'/></g></svg>
                                                Delete</label>
                                            </div>
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "
                                <tr class='hover'>
                                    <td colspan='8' class='text-center'>No records found</td>
                                </tr>
                            ";
                        }

                        mysqli_free_result($result);
                        ?>
                        <tr class="hover">
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end items-center gap-4">
                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>"
                    <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn bg-[#276bae] text-white" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn bg-[#276bae] text-white text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>"
                    <?php if ($page + 1 > $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <?php $result = $dbCon->query($query); ?>
    <?php if ($result->num_rows > 0) { ?>
    <?php while ($row = $result->fetch_assoc()) { ?>

    <?php if ($row['is_irregular'] == '1')
                continue; ?>

    <!-- View modal -->
    <input type="checkbox" id="view-student-<?= $row['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <div class="flex flex-col gap-4  px-[32px] mb-auto">

                <!-- Student ID -->
                <label class="flex flex-col gap-2">
                    <span class="font-semibold text-base">Student ID</span>
                    <input class="input input-bordered" name="student_id" value="<?= $row['sid'] ?>" required
                        disabled />
                </label>

                <!-- Name -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">First Name</span>
                        <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" required
                            disabled />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Middle Name</span>
                        <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>"
                            required disabled />
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Last Name</span>
                        <input class="input input-bordered" name="last_name" value="<?= $row['lastName'] ?>" required
                            disabled />
                    </label>
                </div>

                <!-- Details -->
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Sex</span>
                        <select class="select select-bordered" name="gender" required disabled>
                            <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male
                            </option>
                            <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female
                            </option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Contact</span>
                        <input class="input input-bordered" name="contact" value="<?= $row['contact'] ?>" required
                            disabled />
                    </label>
                </div>

                <label class="flex flex-col gap-2">
                    <span class="font-semibold text-base">Birthdate</span>
                    <input class="input input-bordered" type="text" name="birthday"
                        value="<?= date("F j, Y", strtotime($row['birthday'] ?? "2001-01-01")) ?>" required disabled />
                </label>

                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Course</span>
                        <input class="input input-bordered" type="text" name="course"
                            value="<?= $row['course'] ?? 'No course' ?>" required disabled />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Year Level</span>
                        <input class="input input-bordered" type="text" name="year_level"
                            value="<?= $row['year_level'] ?? 'No year level' ?>" required disabled />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Section</span>
                        <input class="input input-bordered" type="text" name="section"
                            value="<?= $row['section'] ?? 'No section' ?>" required disabled />
                    </label>
                </div>

                <!-- Account -->
                <label class="flex flex-col gap-2">
                    <span class="font-semibold text-base">Email</span>
                    <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" required
                        disabled />
                </label>
            </div>
        </div>
        <label class="modal-backdrop" for="view-student-<?= $row['id'] ?>">Close</label>
    </div>

    <!-- Edit modal -->
    <input type="checkbox" id="edit-student-<?= $row['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <form class="flex flex-col gap-4  px-[32px] mb-auto" method="post">
                <input type="hidden" name="id" value="<?= $row['id'] ?>" />

                <!-- Student ID -->
                <label class="flex flex-col gap-2">
                    <span class="font-semibold text-base">Student ID</span>
                    <input class="input input-bordered" name="student_id" value="<?= $row['sid'] ?>" required />
                </label>

                <!-- Name -->
                <div class="grid grid-cols-3 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">First Name</span>
                        <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>"
                            required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Middle Name</span>
                        <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" />
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Last Name</span>
                        <input class="input input-bordered" name="last_name" value="<?= $row['lastName'] ?>" required />
                    </label>
                </div>

                <!-- Details -->
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Sex</span>
                        <select class="select select-bordered" name="gender" required>
                            <option value="" selected disabled>Select Sex</option>
                            <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male
                            </option>
                            <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female
                            </option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2" x-data>
                        <span class="font-semibold text-base">Contact</span>
                        <input x-mask="9999-999-9999" @input="enforcePrefix" type="tel" class="input input-bordered"
                            name="contact" placeholder="0912-345-6789" class="input input-bordered" name="contact"
                            value="<?= $row['contact'] ?>" required />
                    </label>
                </div>

                <label class="flex flex-col gap-2">
                    <span class="font-semibold text-base">Birthdate</span>
                    <input class="input input-bordered" type="date" name="birthday"
                        value="<?= $row['birthday'] ?? "2001-01-01" ?>" required />
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-semibold text-base">Course</span>

                    <select class="select select-bordered" name="course" id="edit-course-<?= $row['id'] ?>"
                        onchange="onCourseChanged(event)" required>
                        <option value="" selected disabled>Select course</option>

                        <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['id'] ?>" <?php if ($course['course_code'] == $row['course']): ?>
                            selected <?php endif; ?>><?= $course['course'] ?> (<?= $course['course_code'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <label class="flex flex-col gap-2">
                        <span class="font-semibold text-base">Year Level</span>

                        <select class="select select-bordered" name="year_level" id="edit-yearlevel-<?= $row['id'] ?>"
                            onchange="onYearLevelChanged(event)" required <?php if (!isset($row['course'])): ?> disabled
                            <?php endif; ?>>
                            <option value="" selected disabled>Select year level</option>
                            <option value="1st year"
                                <?php if (isset($row['year_level']) && strtolower($row['year_level']) == '1st year') { ?>
                                selected <?php } ?>>1st year</option>
                            <option value="2nd year"
                                <?php if (isset($row['year_level']) && strtolower($row['year_level']) == '2nd year') { ?>
                                selected <?php } ?>>2nd year</option>
                            <option value="3rd year"
                                <?php if (isset($row['year_level']) && strtolower($row['year_level']) == '3rd year') { ?>
                                selected <?php } ?>>3rd year</option>
                            <option value="4th year"
                                <?php if (isset($row['year_level']) && strtolower($row['year_level']) == '4th year') { ?>
                                selected <?php } ?>>4th year</option>
                            <option value="5th year"
                                <?php if (isset($row['year_level']) && strtolower($row['year_level']) == '5th year') { ?>
                                selected <?php } ?>>5th year</option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Section</span>
                        <select class="select select-bordered" name="section" id="edit-section-<?= $row['id'] ?>"
                            required <?php if (!isset($row['course']) || !isset($row['year_level'])): ?> disabled
                            <?php endif; ?>>
                            <option value="" selected disabled>Select section</option>
                        </select>
                    </label>
                </div>

                <!-- Account -->
                <label class="flex flex-col gap-2">
                    <span class="font-semibold text-base">Email</span>
                    <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>"
                        required />
                </label>

                <!-- <div class="grid grid-cols-2 gap-4">
                            <label class="flex flex-col gap-2" x-data="{show: true}">
                                <span class="font-semibold text-base">New Password</span>
                                <div class="relative">
                                    <input class="input input-bordered w-full" name="new-password" placeholder="New password" x-bind:type="show ? 'password' : 'text'" />
                                    <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
                                        <i x-show="!show" class='bx bx-hide'></i>
                                        <i x-show="show" class='bx bx-show'></i>
                                    </button>
                                </div>
                            </label>

                            <label class="flex flex-col gap-2" x-data="{show: true}">
                                <span class="font-semibold text-base">Confirm Password</span>
                                <div class="relative">
                                    <input class="input input-bordered w-full" name="confirm-password" placeholder="Confirm password" x-bind:type="show ? 'password' : 'text'" />
                                    <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
                                        <i x-show="!show" class='bx bx-hide'></i>
                                        <i x-show="show" class='bx bx-show'></i>
                                    </button>
                                </div>
                            </label>
                        </div> -->

                <!-- Actions -->
                <div class="grid grid-cols-2 gap-4">
                    <label for="edit-student-<?= $row['id'] ?>" class="btn btn-error text-base">Cancel</label>
                    <button class="btn bg-[#276bae] text-white text-base" name="update_student">Update</button>
                </div>
            </form>
        </div>
        <label class="modal-backdrop" for="edit-student-<?= $row['id'] ?>">Close</label>
    </div>

    <!-- Delete modal -->
    <input type="checkbox" id="delete-student-<?= $row['id'] ?>" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box border border-error border-2">
            <h3 class="text-lg font-bold text-error">Notice!</h3>
            <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information
                will permanently remove it from the system. Ensure that you have backed up any essential data before
                confirming.</p>

            <form class="flex justify-end gap-4 items-center" method="post">
                <input type="hidden" name="id" value="<?= $row['id'] ?>" />

                <label class="btn" for="delete-student-<?= $row['id'] ?>">Cancel</label>
                <button class="btn btn-error" name="delete-student">Delete</button>
            </form>
        </div>
        <label class="modal-backdrop" for="delete-student-<?= $row['id'] ?>">Close</label>
    </div>

    <?php } ?>
    <?php mysqli_free_result($result); ?>
    <?php } ?>

    <!-- Import file modal -->
    <dialog class="modal" id="import_file_modal">
        <div class="modal-box min-w-[474px]">
            <form class="hidden" id="downloadTemplateForm" method="post">
                <input type="hidden" name="download-template-excel">
            </form>
            <form class="flex flex-col gap-4" method="post" enctype="multipart/form-data">
                <h2 class="text-center text-[28px] font-bold">Import Students</h2>
                <p class="text-center text-[16px]">You can import students by uploading a <strong>CSV</strong> or
                    <strong>EXCEL</strong> file.
                </p>
                <label class="flex flex-col gap-2 mb-4">
                    <span class="font-bold text-[18px]">Upload file</span>
                    <input type="file" name="file"
                        class="file-input file-input-sm md:file-input-md file-input-bordered w-full"
                        accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required />
                    <div class="label">
                        <span class="label-text-alt text-error">Only <kbd class="p-1">*.xlsx</kbd> and <kbd
                                class="p-1">*.csv</kbd> files are allowed</span>
                    </div>
                </label>

                <div class="modal-action">
                    <button class="btn btn-sm md:btn-md btn-warning text-base" type="button"
                        onclick="downloadTemplate()"><i class="fa fa-download"></i> Download template</button>
                    <button class="btn btn-sm md:btn-md btn-error text-base" type="button"
                        onclick="import_file_modal.close()">Cancel</button>
                    <button class="btn btn-sm md:btn-md btn-success text-base" name="import_student">Import</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Export as excel modal -->
    <dialog class="modal" id="export_excel_modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Export as Excel</h3>
            <p class="py-4">Do you really want to export all this data to excel file? Students without courses and
                sections will be excluded.</p>

            <!-- Actions -->
            <form class="modal-action" method="post">
                <button class="btn btn-error text-base" type="button"
                    onclick="export_excel_modal.close()">Cancel</button>
                <button class="btn btn-success text-base" name="export-excel"
                    onclick="export_excel_modal.close()">Export</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Export as csv modal -->
    <dialog class="modal" id="export_csv_modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Export as CSV</h3>
            <p class="py-4">Do you really want to export all this data to csv file? Students without courses and
                sections will be excluded.</p>

            <!-- Actions -->
            <form class="modal-action" method="post">
                <button class="btn btn-error text-base" type="button" onclick="export_csv_modal.close()">Cancel</button>
                <button class="btn btn-success text-base" name="export-csv"
                    onclick="export_csv_modal.close()">Export</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</main>

<script>
const titleCase = (string) => string.split(' ').map((str) => (str.substr(0, 1).toUpperCase() + str.substring(1))).join(
    ' ');

const updateSectionOptions = async (id, defaultSelected = null) => {
    const yearLevel = titleCase(document.querySelector(`#edit-yearlevel-${id}`).value);
    const courseId = document.querySelector(`#edit-course-${id}`).value;
    const section = document.querySelector(`#edit-section-${id}`);

    if (!yearLevel || !courseId)
        return;

    // Remove all options
    section.innerHTML = "<option value='' selected disabled>Loading sections...</option>";

    // Disable section
    section.setAttribute('disabled', '');

    // Fetch all available section from selected course and year level
    const sections = await fetch(`<?= $_SERVER['PHP_SELF'] ?>?courseId=${courseId}&yearLevel=${yearLevel}`, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "content-type": "application/json"
            }
        })
        .then(res => res.json());

    // If there are sections found, display them all
    if (sections.length > 0) {
        section.removeAttribute('disabled');
        section.innerHTML = "<option value='' selected disabled>Select sections</option>";

        sections.forEach(sec => {
            const option = document.createElement('option');
            option.setAttribute('value', sec.id);

            if (defaultSelected != null && sec.id == defaultSelected) {
                option.setAttribute('selected', '');
            }

            const textNode = document.createTextNode(sec.name);
            option.appendChild(textNode);
            section.appendChild(option);
        });
    } else {
        section.setAttribute('disabled', '');

        const option = document.createElement('option');
        option.setAttribute('value', '');
        option.setAttribute('selected', '');
        option.setAttribute('disabled', '');

        const textNode = document.createTextNode('No available sections')
        option.appendChild(textNode);
        section.appendChild(option);
    }
}

const onCourseChanged = (e) => {
    const yearLevel = document.querySelector(`#edit-yearlevel-${e.target.id.split('-').pop()}:disabled`);

    if (yearLevel) {
        if (!!yearLevel.value) {
            updateSectionOptions(e.target.id.split('-').pop());
        }

        yearLevel.removeAttribute('disabled');
    } else {
        updateSectionOptions(e.target.id.split('-').pop());
    }
};

const onYearLevelChanged = (e) => {
    updateSectionOptions(e.target.id.split('-').pop());
};

function enforcePrefix(e) {
    let currentValue = e.target.value;

    if (!currentValue.startsWith("09")) {
        e.target.value = "09" + currentValue.substring(2);
    }
}

function downloadTemplate(e) {
    document.querySelector("#downloadTemplateForm").submit();
    import_file_modal.close();
}
</script>