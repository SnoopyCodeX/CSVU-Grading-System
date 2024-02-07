<?php
require('../../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require('../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}

// export as excel using phpspreadsheet
if (isset($_POST['export-excel'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'id');
    $sheet->setCellValue('B1', 'firstName');
    $sheet->setCellValue('C1', 'middleName');
    $sheet->setCellValue('D1', 'lastName');
    $sheet->setCellValue('E1', 'email');
    $sheet->setCellValue('F1', 'gender');
    $sheet->setCellValue('G1', 'birthday');
    $sheet->setCellValue('H1', 'contact');
    $sheet->setCellValue('I1', 'sid');
    $sheet->setCellValue('J1', 'year_level');
    $sheet->setCellValue('K1', 'password');


    $query = "SELECT * FROM ap_userdetails WHERE roles='student'";
    $result = $dbCon->query($query);

    if ($result->num_rows > 0) {
        $i = 2;
        while ($row = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $i, $row['id']);
            $sheet->setCellValue('B' . $i, $row['firstName']);
            $sheet->setCellValue('C' . $i, $row['middleName']);
            $sheet->setCellValue('D' . $i, $row['lastName']);
            $sheet->setCellValue('E' . $i, $row['email']);
            $sheet->setCellValue('F' . $i, $row['gender']);
            $sheet->setCellValue('G' . $i, $row['birthday']);
            $sheet->setCellValue('H' . $i, '="' . $row['contact'] . '"');
            $sheet->setCellValue('I' . $i, '="' . $row['sid'] . '"');
            $sheet->setCellValue('J' . $i, $row['year_level']);
            $sheet->setCellValue('K' . $i, '="' . $row['password'] . '"');
            $i++;
        }

        $filename = "students-" . date('Y-m-d') . ".xlsx";
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
    $filename = "students-" . date('Y-m-d') . ".csv";
    $query = "SELECT * FROM ap_userdetails WHERE roles='student'";
    $result = $dbCon->query($query);

    if ($result->num_rows > 0) {
        $fp = fopen($filename, 'w');
        fputcsv($fp, array('id', 'firstName', 'middleName', 'lastName', 'gender', 'birthday', 'contact', 'email', 'sid', 'year_level', 'password'));

        while ($row = $result->fetch_assoc()) {
            fputcsv($fp, array(
                $row['id'], 
                $row['firstName'], 
                $row['middleName'], 
                $row['lastName'], 
                $row['gender'], 
                $row['birthday'],
                '="' . $row['contact'] . '"', 
                $row['email'], 
                '="' . $row['sid'] . '"', 
                $row['year_level'], 
                '="' . $row['password'] . '"'
            ));
        }

        fclose($fp);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        readfile($filename);
        exit();
    }

    mysqli_free_result($result);
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$hasSearch = false;
$message = "";

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
if($hasSearch) {
    $result1 = $dbCon->query("SELECT count(id) AS id FROM ap_userdetails WHERE (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%' OR sid LIKE '%$search%') AND roles='student'");
} else {
    $result1 = $dbCon->query("SELECT count(id) AS id FROM ap_userdetails WHERE roles='student'");
}

$students = $result1->fetch_all(MYSQLI_ASSOC);
$total = $students[0]['id'];
$pages = ceil($total / $limit);

// update student
if (isset($_POST['update_student'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $studentId = $dbCon->real_escape_string($_POST['student_id']);
    $firstName = $dbCon->real_escape_string($_POST['first_name']);
    $middleName = $dbCon->real_escape_string($_POST['middle_name']);
    $lastName = $dbCon->real_escape_string($_POST['last_name']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $contact = $dbCon->real_escape_string($_POST['contact']);
    $birthday = $dbCon->real_escape_string($_POST['birthday']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $newPassword = $dbCon->real_escape_string($_POST['new-password']);
    $confirmPassword = $dbCon->real_escape_string($_POST['confirm-password']);
    $yearLevel = $dbCon->real_escape_string($_POST['year_level']);

    if (!$email) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address";
     }else if(!str_ends_with($email, "@cvsu.edu.ph")) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid email address. It should end with <strong>@cvsu.edu.ph</strong>";
    } else if (!str_starts_with($contact, "09") || strlen($contact) != 11) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Please enter a valid contact number. It should start with <strong>09</strong> and has <strong>11 digits</strong>.";
    } else if ($dbCon->query("SELECT * FROM ap_userdetails WHERE id='$id' AND roles = 'student'")->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Student does not exist!";
    } else {
        // get student details that matches the given id
        $result = $dbCon->query("SELECT * FROM ap_userdetails WHERE id='$id' AND roles = 'student'");

        if ($result->num_rows == 0) {
            $hasError = true;
            $hasSuccess = false;
            $message = "Student does not exist!";
        } else {
            $student = $result->fetch_assoc();

            // update student query 
            $query = "UPDATE ap_userdetails SET 
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

            if ($newPassword) {
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
            }

            if(!$hasError) {
                $query .= " WHERE id='$id'";

                $update = $dbCon->query($query);

                if ($update) {
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

    if ($dbCon->query("SELECT * FROM ap_userdetails WHERE id='$id' AND roles = 'student'")->num_rows == 0) {
        $hasError = true;
        $hasSuccess = false;
        $message = "Student does not exist!";
    } else {
        $query = "DELETE FROM ap_userdetails WHERE id='$id'";
        $delete = $dbCon->query($query);

        // check if student id is also in ap_section_students. If so, delete it as well
        if ($dbCon->query("SELECT * FROM ap_section_students WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM ap_section_students WHERE student_id='$id'");
        }

        // check if student id is also in ap_student_grades. If so, delete it as well
        if ($dbCon->query("SELECT * FROM ap_student_grades WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM ap_student_grades WHERE student_id='$id'");
        }

        // check if student id is also in ap_student_final_grades. If so, delete it as well
        if ($dbCon->query("SELECT * FROM ap_student_final_grades WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM ap_student_final_grades WHERE student_id='$id'");
        }

        // check if student id is also in ap_grade_requests. If so, delete it as well
        if ($dbCon->query("SELECT * FROM ap_grade_requests WHERE student_id='$id'")->num_rows > 0) {
            $dbCon->query("DELETE FROM ap_grade_requests WHERE student_id='$id'");
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
if($hasSearch) {
    $query = "SELECT * FROM ap_userdetails WHERE (CONCAT(firstName, ' ', middleName, ' ', lastName) LIKE '%$search%' OR email LIKE '%$search%' OR sid LIKE '%$search%') AND roles='student' LIMIT $start, $limit";
} else {
    $query = "SELECT * FROM ap_userdetails WHERE roles='student' LIMIT $start, $limit";
}
?>

<main class="w-screen overflow-x-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="h-screen w-full px-4">
        <?php require_once("../layout/topbar.php") ?>


        <div class="px-4 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-semibold">Student</h1>
                </div>
                <div class="flex gap-4 px-4">
                    <!-- Search bar -->
                    <form class="w-[300px]" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" autocomplete="off">   
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" name="search-student" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search name" value="<?= $hasSearch ? $search : '' ?>" required>
                            <button type="submit" class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Create button -->
                    <a href="./create/student.php" class="btn">Create</a>

                    <!-- Export Button -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn m-1"><i class="bx bxs-file-export"></i> Export As</div>
                        <ul tabindex="0" class="dropdown-content z-[99] menu p-2 shadow bg-base-100 rounded-box w-52">
                            <li><label for="export_excel_modal">Excel</label></li>
                            <li><label for="export_csv_modal">CSV</label></li>
                        </ul>
                    </div>
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
                <table class="table table-xs sm:table-sm md:table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white">ID</th>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Email</td>
                            <td class="bg-slate-500 text-white">Gender</td>
                            <td class="bg-slate-500 text-white">Contact</td>
                            <td class="bg-slate-500 text-white">Student ID</td>
                            <td class="bg-slate-500 text-white">Year Level</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $dbCon->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "
                                    <tr>
                                        <td>{$row['id']}</td>
                                        <td>{$row['firstName']} {$row['middleName']} {$row['lastName']}</th>
                                        <td>{$row['email']}</td>
                                        <td>" . ucfirst($row['gender']) . "</td>
                                        <td>{$row['contact']}</td>
                                        <td>{$row['sid']}</td>
                                        <td>{$row['year_level']}</td>
                                        <td>
                                            <div class='flex gap-2 justify-center items-center'>
                                                <label for='view-student-{$row['id']}' class='btn btn-sm bg-blue-400 text-white'>View</label>
                                                <label for='edit-student-{$row['id']}' class='btn btn-sm bg-gray-400 text-white'>Edit</label>
                                                <label for='delete-student-{$row['id']}' class='btn btn-sm bg-red-400 text-white'>Delete</label>
                                            </div>
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "
                                <tr>
                                    <td colspan='8' class='text-center'>No records found</td>
                                </tr>
                            ";
                        }

                        mysqli_free_result($result);
                        ?>
                        <tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center">
                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 >= $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <?php $result = $dbCon->query($query); ?>
    <?php if ($result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>

            <!-- View modal -->
            <input type="checkbox" id="view-student-<?= $row['id'] ?>" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">
                    <div class="flex flex-col gap-4  px-[32px] mb-auto">

                        <!-- Student ID -->
                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Student ID</span>
                            <input class="input input-bordered" name="student_id" value="<?= $row['sid'] ?>" required disabled />
                        </label>

                        <!-- Name -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">First Name</span>
                                <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Middle Name</span>
                                <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" required disabled />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Last Name</span>
                                <input class="input input-bordered" name="last_name" value="<?= $row['lastName'] ?>" required disabled />
                            </label>
                        </div>

                        <!-- Details -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Gender</span>
                                <select class="select select-bordered" name="gender" required disabled>
                                    <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                    <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Contact</span>
                                <input class="input input-bordered" name="contact" value="<?= $row['contact'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Birthdate</span>
                                <input class="input input-bordered" type="date" name="birthday" value="<?= $row['birthday'] ?? "1900-01-01" ?>" required disabled />
                            </label>
                        </div>

                        <!-- Account -->
                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Email</span>
                            <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" required disabled />
                        </label>

                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Year level</span>
                            <select class="select select-bordered" name="year_level" required disabled>
                                <option value="1st year" <?php if (strtolower($row['year_level']) == '1st year') { ?> selected <?php } ?>>1st year</option>
                                <option value="2nd year" <?php if (strtolower($row['year_level']) == '2nd year') { ?> selected <?php } ?>>2nd year</option>
                                <option value="3rd year" <?php if (strtolower($row['year_level']) == '3rd year') { ?> selected <?php } ?>>3rd year</option>
                                <option value="4th year" <?php if (strtolower($row['year_level']) == '4th year') { ?> selected <?php } ?>>4th year</option>
                                <option value="5th year" <?php if (strtolower($row['year_level']) == '5th year') { ?> selected <?php } ?>>5th year</option>
                            </select>
                        </label>
                    </div>
                </div>
                <label class="modal-backdrop" for="view-student-<?= $row['id'] ?>">Close</label>
            </div>

            <!-- Edit modal -->
            <input type="checkbox" id="edit-student-<?= $row['id'] ?>" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">
                    <form class="flex flex-col gap-4  px-[32px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
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
                                <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" required />
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
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Gender</span>
                                <select class="select select-bordered" name="gender" required>
                                    <option value="" selected disabled>Select Gender</option>
                                    <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                    <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Contact</span>
                                <input class="input input-bordered" name="contact" value="<?= $row['contact'] ?>" required />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-semibold text-base">Birthdate</span>
                                <input class="input input-bordered" type="date" name="birthday" value="<?= $row['birthday'] ?? "1900-01-01" ?>" required />
                            </label>
                        </div>



                        <!-- Account -->
                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Email</span>
                            <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" required />
                        </label>

                        <div class="grid grid-cols-2 gap-4">
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
                        </div>

                        <label class="flex flex-col gap-2">
                            <span class="font-semibold text-base">Year level</span>
                            <select class="select select-bordered" name="year_level" required>
                                <option value="" selected disabled>Select year level</option>
                                <option value="1st year" <?php if (strtolower($row['year_level']) == '1st year') { ?> selected <?php } ?>>1st year</option>
                                <option value="2nd year" <?php if (strtolower($row['year_level']) == '2nd year') { ?> selected <?php } ?>>2nd year</option>
                                <option value="3rd year" <?php if (strtolower($row['year_level']) == '3rd year') { ?> selected <?php } ?>>3rd year</option>
                                <option value="4th year" <?php if (strtolower($row['year_level']) == '4th year') { ?> selected <?php } ?>>4th year</option>
                                <option value="5th year" <?php if (strtolower($row['year_level']) == '5th year') { ?> selected <?php } ?>>5th year</option>
                            </select>
                        </label>

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-4">
                            <label for="edit-student-<?= $row['id'] ?>" class="btn btn-error text-base">Cancel</label>
                            <button class="btn btn-success text-base" name="update_student">Update</button>
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
                    <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                    <form class="flex justify-end gap-4 items-center" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
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

    <!-- Export as excel modal -->
    <input type="checkbox" class="modal-toggle" id="export_excel_modal">
    <div class="modal" role="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Export as Excel</h3>
            <p class="py-4">Do you really want to export all this data to excel file?</p>

            <!-- Actions -->
            <form class="modal-action" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <label class="btn btn-error text-base" for="export_excel_modal">Cancel</label>
                <button class="btn btn-success text-base" name="export-excel">Export</button>
            </form>
        </div>
        <label class="modal-backdrop" for="export_excel_modal">close</label>
    </div>

    <!-- Export as csv modal -->
    <input type="checkbox" id="export_csv_modal" class="modal-toggle">
    <div class="modal" role="dialog">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Export as CSV</h3>
            <p class="py-4">Do you really want to export all this data to csv file?</p>

            <!-- Actions -->
            <form class="modal-action" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <label class="btn btn-error text-base" for="export_csv_modal">Cancel</label>
                <button class="btn btn-success text-base" name="export-csv">Export</button>
            </form>
        </div>
        <label class="modal-backdrop" for="export_csv_modal">close</label>
    </div>
</main>