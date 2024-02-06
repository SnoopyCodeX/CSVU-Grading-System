<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasWarning = false;
$warning = "";
$message = "";

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
            if ($fileSize < (1000000) * 1024 * 1024) {
                $fileNameNew = uniqid('', true) . "." . $fileActualExt;
                $fileDestination = 'uploads/' . $fileNameNew;

                // auto create destination if it does not exist
                if (!file_exists('uploads')) {
                    @mkdir('uploads', 0777, true);
                }

                @move_uploaded_file($fileTmpName, $fileDestination);

                if ($fileActualExt === "xlsx") {
                    require_once("../../../vendor/autoload.php");
                    $reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    $spreadsheet = $reader->load($fileDestination);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray();
                } else if ($fileActualExt === "csv") {
                    $file = fopen($fileDestination, "r");
                    $sheetData = array();

                    while (!feof($file)) {
                        $line = fgetcsv($file);

                        if(!is_array($line)) {
                            continue;
                        }

                        $sheetData[] = $line;
                    }

                    fclose($file);
                }

                $header = array_shift($sheetData);
                $data = array();
                foreach ($sheetData as $row) {
                    $data[] = array_combine($header, $row);
                }

                if (count($data) > 0) {
                    // insert query
                    $query = "INSERT INTO ap_userdetails(firstName, middleName, lastName, gender, contact,  birthday, email, password, year_level, roles, sid) VALUES";
                    $skippedStudentData = 0;

                    // loop through the data and validate
                    foreach ($data as $student) {
                        $studentId = $dbCon->real_escape_string($student['sid']);
                        $firstName = $dbCon->real_escape_string($student['firstName']);
                        $middleName = $dbCon->real_escape_string($student['middleName']);
                        $lastName = $dbCon->real_escape_string($student['lastName']);
                        $gender = $dbCon->real_escape_string($student['gender']);
                        $contact = $dbCon->real_escape_string($student['contact']);
                        $birthday = $dbCon->real_escape_string($student['birthday']);
                        $email = filter_var($dbCon->real_escape_string($student['email']), FILTER_VALIDATE_EMAIL);
                        $password = $dbCon->real_escape_string($student['password'] ?? 'cvsu@123');
                        $yearLevel = $dbCon->real_escape_string($student['year_level']);

                        if($fileActualExt === 'csv') {
                            // remove \=" and \" from contact, sid and password
                            $contact = substr($contact, 3, -2);
                            $studentId = substr($studentId, 3, -2);
                            $password = substr($password, 3, -2);
                        }
                        
                        if (!$email) {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "Please enter a valid email address";
                            break;
                        } else if(!str_ends_with($email, "@cvsu.edu.ph")) {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "One of the imported student data does not have a valid email address. It should end with <strong>@cvsu.edu.ph</strong>";
                            break;
                        } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
                            break;
                        } else {
                            // skip if email or student id is already in the database
                            if ($dbCon->query("SELECT * FROM ap_userdetails WHERE email = '$email' OR sid = '$studentId'")->num_rows > 0) {
                                $skippedStudentData++;

                                $hasWarning = true;
                                $warning = "Skipped $skippedStudentData student data because the student ID or email address already exists!";

                                continue;
                            }

                            // check if password is already encrypted, if not, encrypt it
                            if (strlen($password) < 60) {
                                $password = crypt($password, '$6$Crypt$');
                            }

                            $query .= "(
                                '$firstName',
                                '$middleName',
                                '$lastName',
                                '$gender',
                                '$contact',
                                '$birthday',
                                '$email',
                                '$password',
                                '$yearLevel',
                                'student',
                                '$studentId'
                            ),";
                        }
                    }

                    // execute the query if there are no errors
                    if(!str_ends_with($query, "VALUES")) {
                        $query = substr($query, 0, -1);
                        $result = $dbCon->query($query);


                        if ($result) {
                            $hasError = false;
                            $hasSuccess = true;
                            $message = "Successfully imported students!";
                        } else {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "Failed to import students!";
                        }
                    }
                } else {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "No data found in the file!";
                }

                // delete the file after importing
                @unlink($fileDestination);
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

// Create new student
if (isset($_POST['create_student'])) {
    $studentId = $dbCon->real_escape_string($_POST['student_id']);
    $firstName = $dbCon->real_escape_string($_POST['first_name']);
    $middleName = $dbCon->real_escape_string($_POST['middle_name']);
    $lastName = $dbCon->real_escape_string($_POST['last_name']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = $dbCon->real_escape_string($_POST['contact']);
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $dbCon->real_escape_string($_POST['password']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address";
    } else if(!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address. It should end with <strong>@cvsu.edu.ph</strong>";
    } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
    } else if ($dbCon->query("SELECT * FROM ap_userdetails WHERE sid = '$studentId' OR email = '$email'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "A student with the same Student ID or email address already exists!";
    } else {
        $query = "INSERT INTO ap_userdetails(firstName, middleName, lastName, email, password, gender, contact,  birthday, year_level, roles, sid) VALUES(
            '$firstName',
            '$middleName',
            '$lastName',
            '$email',
            '" . crypt($password, '$6$Crypt$') . "',
            '$gender',
            '$contact',
            '$birthday',
            '$yearLevel',
            'student',
            '$studentId'
        )";
        $result = $dbCon->query($query);

        if ($result) {
            $hasError = false;
            $hasSuccess = true;
            $message = "Successfully added a new student!";
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Failed to add a new student!";
        }
    }
}
?>

<main class="w-screen h-screen overflow-scroll flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center md:w-[700px] mx-auto">
            <div class="flex justify-center items-center flex-col gap-4 w-full">
                <h2 class="text-[38px] font-bold">Create Student</h2>
                <form class="flex flex-col gap-4 w-full " method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

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

                    <!-- Student ID -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Student ID</span>
                        <input class="input input-bordered" name="student_id" placeholder="Enter Student ID" required />
                    </label>

                    <!-- Name -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">First Name</span>
                            <input class="input input-bordered" name="first_name" placeholder="Enter First name" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middle_name" placeholder="Enter Middle Name" />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="last_name" placeholder="Enter Last name" required />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Gender</span>
                            <select class="select select-bordered" name="gender" required>
                                <option value="" selected disabled>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Contact</span>
                            <input class="input input-bordered" name="contact" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="1900-01-01" required />
                        </label>
                    </div>



                    <!-- Account -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Email</span>
                            <input type="email" placeholder="Enter email" class="input input-bordered w-full" type="email" name="email" required />
                        </label>

                        <label class="flex flex-col gap-2" x-data="{show: true}">
                            <span class="font-bold text-[18px]">Password</span>
                            <div class="relative">
                                <input type="password" placeholder="Enter Password" class="input input-bordered w-full" name="password" x-bind:type="show ? 'password' : 'text'" required />
                                <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
                                    <i x-show="!show" class='bx bx-hide'></i>
                                    <i x-show="show" class='bx bx-show'></i>
                                </button>
                            </div>
                        </label>
                    </div>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required>
                            <option value="" selected disabled>Select year level</option>
                            <option value="1st year">1st year</option>
                            <option value="2nd year">2nd year</option>
                            <option value="3rd year">3rd year</option>
                            <option value="4th year">4th year</option>
                            <option value="4th year">5th year</option>
                        </select>
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-student.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success text-base" name="create_student">Create</button>
                    </div>
                </form>

                <div class="divider">OR</div>

                <!-- Form with file upload input for importing xlsx or csv filee -->
                <form class="flex flex-col gap-4 w-full" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" enctype="multipart/form-data">
                    <h2 class="text-center text-[28px] font-bold">Import Students</h2>
                    <p class="text-center text-[16px]">You can import students by uploading a <strong>CSV</strong> or <strong>EXCEL</strong> file.</p>
                    <label class="flex flex-col gap-2 mb-4">
                        <span class="font-bold text-[18px]">Upload file</span>
                        <input type="file" name="file" class="file-input file-input-bordered w-full" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required />
                        <div class="label">
                            <span class="label-text-alt text-error">Only <kbd class="p-1">*.xlsx</kbd> and <kbd class="p-1">*.csv</kbd> files are allowed</span>
                        </div>
                    </label>

                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-student.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn btn-success text-base" name="import_student">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>