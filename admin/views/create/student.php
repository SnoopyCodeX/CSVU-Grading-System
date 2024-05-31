<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../../configuration/config.php");
require('../../../auth/controller/auth.controller.php');
require('../../../utils/mailer.php');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if(isset($_GET['courseId']) && isset($_GET['yearLevel'])) {
        $yearLevel = $dbCon->real_escape_string($_GET['yearLevel']);
        $courseId = $dbCon->real_escape_string($_GET['courseId']);

        $sectionsQuery = $dbCon->query("SELECT * FROM sections WHERE course='$courseId' AND year_level='$yearLevel'");
        $sections = $sectionsQuery->fetch_all(MYSQLI_ASSOC);

        header('Content-type: application/json');
        echo json_encode($sections, JSON_PRETTY_PRINT);
    }

    exit;
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../../../public/login.php");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
// $hasWarning = false;
// $warning = "";
$message = "";

// Import students
/* if (isset($_POST['import_student'])) {
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

                // remove the header from the read data
                $header = array_shift($sheetData);
                $data = array();

                // combine the header and the data
                foreach ($sheetData as $row) {
                    $data[] = array_combine($header, $row);
                }

                // check if there is data in the file
                if (count($data) > 0) {
                    // insert query
                    $query = "INSERT INTO userdetails(firstName, middleName, lastName, gender, contact,  birthday, email, password, year_level, roles, sid) VALUES";
                    $skippedStudentData = 0;

                    // loop through the data and validate
                    foreach ($data as $student) {
                        $studentId = $dbCon->real_escape_string($student['Student ID'] ?? '');
                        $firstName = $dbCon->real_escape_string($student['First Name']);
                        $middleName = $dbCon->real_escape_string($student['Middle Name']);
                        $lastName = $dbCon->real_escape_string($student['Last Name']);
                        $gender = $dbCon->real_escape_string($student['Gender']);
                        $contact = $dbCon->real_escape_string($student['Contact Number']);
                        $birthday = $dbCon->real_escape_string($student['Birthday']);
                        $email = filter_var($dbCon->real_escape_string($student['Email Address']), FILTER_VALIDATE_EMAIL);
                        $yearLevel = $dbCon->real_escape_string($student['Year Level']);

                        if (str_contains($birthday, "/")) {
                            $birthday = str_replace("/", "-", $birthday);
                            $birthday = date("Y-m-d", strtotime($birthday));
                        }

                        if($fileActualExt === 'csv') {
                            // remove \=" and \" from contact, sid and password
                            $contact = substr($contact, 3, -2);
                            $studentId = substr($studentId, 3, -2);
                        }
                        
                        if (!$email) {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "Please enter a valid email address";
                            break;
                        } else if(!str_ends_with($email, "@cvsu.edu.ph")) {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "One of the imported student data does not have a valid email address. It should use his/her <strong>@cvsu.edu.ph</strong> email address.";
                            break;
                        } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
                            $hasError = true;
                            $hasSuccess = false;
                            $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
                            break;
                        } else {
                            // skip if email or student id is already in the database
                            if ($dbCon->query("SELECT * FROM userdetails WHERE email = '$email' OR sid = '$studentId'")->num_rows > 0) {
                                $skippedStudentData++;

                                $hasWarning = true;
                                $warning = "Skipped $skippedStudentData student data" . (($skippedStudentData > 1) ? 's' : '') . " because the" . (($skippedStudentData > 1) ? 'ir' : '') . " student ID or email address already exists!";

                                continue;
                            }

                            $password = constant("USER_DEFAULT_PASSWORD");

                            $query .= "(
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

                    // unset entered values
                    unset($studentId);
                    unset($firstName);
                    unset($middleName);
                    unset($lastName);   
                    unset($gender);
                    unset($contact);
                    unset($birthday);
                    unset($email);
                    unset($yearLevel);
                } else {
                    $hasError = true;
                    $hasSuccess = false;
                    $message = "No data found in the file!";
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
} */

// Create new student
if (isset($_POST['create_student'])) {
    $studentId = $dbCon->real_escape_string($_POST['student_id']);
    $firstName = $dbCon->real_escape_string($_POST['first_name']);
    $middleName = $dbCon->real_escape_string($_POST['middle_name']);
    $lastName = $dbCon->real_escape_string($_POST['last_name']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = str_replace("-", "", $dbCon->real_escape_string($_POST['contact']));
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);
    $course = $dbCon->real_escape_string($_POST['course']);
    $section = $dbCon->real_escape_string($_POST['section']);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address";
    } else if(!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please use the student's <strong>@cvsu.edu.ph</strong> email address.";
    } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
    } else if ($dbCon->query("SELECT * FROM userdetails WHERE sid = '$studentId' OR email = '$email'")->num_rows > 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "A student with the same Student ID or email address already exists!";
    } else {
        // Auto generate password using uuid to prevent collision and with at least 8 characters    
        // $password = substr(md5(uniqid()), 0, 8);

        // randomly insert at least 1-3 special character to the password
        // $specialChars = ['!', '@', '#', '$', '&', '_', '?'];
        // $specialCharCount = rand(1, 3);
        // for($i = 0; $i < $specialCharCount; $i++) {
        //     $password = substr_replace($password, $specialChars[rand(0, count($specialChars) - 1)], rand(0, strlen($password) - 1), 0);
        // }

        $password = constant("USER_DEFAULT_PASSWORD");

        $query = "INSERT INTO userdetails(firstName, middleName, lastName, email, password, gender, contact,  birthday, year_level, roles, sid) VALUES(
            '$firstName',
            '$middleName',
            '$lastName',
            '$email',
            '" . crypt($password, '$6$Crypt$') . "',
            '$gender',
            '$contact',
            '" . date('Y-m-d', strtotime($birthday)) . "',
            '$yearLevel',
            'student',
            '$studentId'
        )";
        $result = $dbCon->query($query);

        if ($result) {
            $studentId = $dbCon->insert_id;
            $dbCon->query("INSERT INTO section_students(section_id, student_id) VALUES('$section', '$studentId')");

            // get the email template
            $template = getNewAccountMailTemplate(
                $email, 
                "$firstName $middleName $lastName", 
                $password, 
                "Welcome to CvSU Grading System", 
                constant('APP_URL'), 
                "We've sent you this email to notify you that we have created your account and you may login using this email address and this generated password. Under no circumstances are you to share this password to anyone. You may change your password once you've logged in.", 
                date('Y')
            );

            // send the email
            sendMail($email, 'CvSU Grading System', $template);

            $hasError = false;
            $hasSuccess = true;
            $message = "Successfully added a new student and successfully notified the student!";

            // unset entered values
            unset($studentId);
            unset($firstName);
            unset($middleName);
            unset($lastName);   
            unset($gender);
            unset($contact);
            unset($birthday);
            unset($email);
            unset($password);
            unset($yearLevel);
        } else {
            $hasError = true;
            $hasSuccess = false;
            $message = "Failed to add a new student!";
        }
    }
}

$coursesQuery = $dbCon->query("SELECT * FROM courses");
$courses = $coursesQuery->fetch_all(MYSQLI_ASSOC);
?>

<main class="w-screen h-screen overflow-scroll flex">
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>

        <div class="flex flex-col gap-4 justify-center items-center md:w-[700px] mx-auto">
            <div class="flex justify-center items-center flex-col gap-4 w-full">
                <h2 class="text-[38px] font-bold">Create Student</h2>
                <form class="flex flex-col gap-4 w-full mb-8" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">

                    <!-- <?php // if ($hasWarning) { ?>
                        <div role="alert" class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span><?= $warning ?></span>
                        </div>
                    <?php // } ?> -->

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
                        <input class="input input-bordered" name="student_id" placeholder="Enter Student ID" value="<?= $studentId ?? "" ?>" required />
                    </label>

                    <!-- Name -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">First Name</span>
                            <input class="input input-bordered" name="first_name" placeholder="Enter First name" value="<?= $firstName ?? "" ?>" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Middle Name</span>
                            <input class="input input-bordered" name="middle_name" placeholder="Enter Middle Name" value="<?= $middleName ?? "" ?>" />
                        </label>
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Last Name</span>
                            <input class="input input-bordered" name="last_name" placeholder="Enter Last name" value="<?= $lastName ?? "" ?>" required />
                        </label>
                    </div>

                    <!-- Details -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Sex</span>
                            <select class="select select-bordered" name="gender" required>
                                <option value="" selected disabled>Select Sex</option>
                                <option value="male" <?php if(isset($gender) && strtolower($gender) == 'male') { ?> selected <?php } ?>>Male</option>
                                <option value="female" <?php if(isset($gender) && strtolower($gender) == 'female') { ?> selected <?php } ?>>Female</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-2" x-data>
                            <span class="font-bold text-[18px]">Contact</span>
                            <input x-mask="9999-999-9999" @input="enforcePrefix" type="tel" class="input input-bordered" name="contact" placeholder="0912-345-6789" value="<?= $contact ?? "" ?>" required />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Birthdate</span>
                            <input class="input input-bordered" type="date" name="birthday" value="2001-01-01" value="<?= $birthday ?? "" ?>" required />
                        </label>
                    </div>

                    <!-- Account -->
                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Email</span>
                        <input type="email" placeholder="Enter email" class="input input-bordered w-full" type="email" name="email" value="<?= $email ?? "" ?>" required />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Course</span>
                        <select class="select select-bordered" name="course" required>
                            <option value="" selected disabled>Select course</option>

                            <?php foreach($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= $course['course'] ?> (<?= $course['course_code'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Year level</span>
                        <select class="select select-bordered" name="year_level" required disabled>
                            <option value="" selected disabled>Select year level</option>
                            <option value="1st year" <?php if(isset($yearLevel) && strtolower($yearLevel) == '1st year') { ?> selected <?php } ?>>1st year</option>
                            <option value="2nd year" <?php if(isset($yearLevel) && strtolower($yearLevel) == '2nd year') { ?> selected <?php } ?>>2nd year</option>
                            <option value="3rd year" <?php if(isset($yearLevel) && strtolower($yearLevel) == '3rd year') { ?> selected <?php } ?>>3rd year</option>
                            <option value="4th year" <?php if(isset($yearLevel) && strtolower($yearLevel) == '4th year') { ?> selected <?php } ?>>4th year</option>
                            <option value="5th year" <?php if(isset($yearLevel) && strtolower($yearLevel) == '5th year') { ?> selected <?php } ?>>5th year</option>
                        </select>
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="font-bold text-[18px]">Section</span>
                        <select class="select select-bordered" name="section" required disabled>
                            <option value="" selected disabled>Select section</option>
                        </select>
                    </label>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4">
                        <a href="../manage-student.php" class="btn btn-error text-base">Cancel</a>
                        <button class="btn bg-[#276bae] text-white text-base" name="create_student">Create</button>
                    </div>
                </form>

                <!-- <div class="divider">OR</div>

                Form with file upload input for importing xlsx or csv filee
                <form class="flex flex-col gap-4 w-full mb-8" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" enctype="multipart/form-data">
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
                </form> -->
            </div>
        </div>
    </section>
</main>

<script>
    const titleCase = (string) => string.split(' ').map((str) => (str.substr(0, 1).toUpperCase() + str.substring(1))).join(' ');

    const updateSectionOptions = async () => {
        const yearLevel = titleCase(document.querySelector("select[name='year_level']").value);
        const courseId = document.querySelector("select[name='course']").value;
        const section = document.querySelector("select[name='section']");

        // Remove all options
        section.innerHTML = "<option value='' selected disabled>Loading sections...</option>";

        // Disable section
        section.setAttribute('disabled', '');

        // Fetch all available section from selected course and year level
        const sections = await fetch(`<?= $_SERVER['PHP_SELF'] ?>?courseId=${courseId}&yearLevel=${yearLevel}`, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "content-type": "application/json"
            }})
            .then(res => res.json());

        // If there are sections found, display them all
        if(sections.length > 0) {
            section.removeAttribute('disabled');
            section.innerHTML = "<option value='' selected disabled>Select sections</option>";

            sections.forEach(sec => {
                const option = document.createElement('option');
                option.setAttribute('value', sec.id);

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

    document.querySelector("select[name='course']").addEventListener('change', (e) => {
        const yearLevel = document.querySelector("select[name='year_level']:disabled");

        if(yearLevel) {
            yearLevel.removeAttribute('disabled');
        } else {
            updateSectionOptions();
        }
    });

    document.querySelector("select[name='year_level']").addEventListener('change', (e) => {
        updateSectionOptions()
    });

    function enforcePrefix(e) {
        let currentValue = e.target.value;

        if (!currentValue.startsWith("09")) {
            e.target.value = "09" + currentValue.substring(2);
        }
    }
</script>