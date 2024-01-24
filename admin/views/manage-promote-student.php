<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require('../../auth/controller/auth.controller.php');

// check if request is an ajax request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $data = json_decode(file_get_contents('php://input'), true);

    // fetch students
    if (isset($data['year_level'])) {
        $yearLevel = $dbCon->real_escape_string($data['year_level']);

        if ($yearLevel == 'All')
            $query = "SELECT * FROM ap_userdetails WHERE roles='student'";
        else
            $query = "SELECT * FROM ap_userdetails WHERE roles='student' AND year_level='$yearLevel'";

        $result = $dbCon->query($query);

        if ($result->num_rows > 0) {
            $students = $result->fetch_all(MYSQLI_ASSOC);

            // filter out selected students
            if (isset($data['selected_student_ids']) && !empty($data['selected_student_ids'])) {
                $selectedStudentIds = $data['selected_student_ids'];
                $filteredStudents = [];

                foreach ($students as $key => $student) {
                    if (!in_array($student['id'], $selectedStudentIds)) {
                        array_push($filteredStudents, $student);
                    }
                }

                $students = $filteredStudents;
            }

            echo json_encode($students);
        } else {
            echo json_encode([]);
        }

        exit();
    }

    exit();
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// Error and success handlers
$hasError = false;
$hasSuccess = false;
$message = "";

// Promote student
if (isset($_POST['promote-student'])) {
    $id = $dbCon->real_escape_string($_POST['id']);
    $year_level = $dbCon->real_escape_string($_POST['year_level']);
    $query = "UPDATE ap_userdetails SET year_level='$year_level' WHERE id='$id'";

    if ($dbCon->query($query)) {
        $hasSuccess = true;
        $message = "Student promoted to $year_level successfully";
    } else {
        $hasError = true;
        $message = "Error promoting student";
    }
}

// Batch promote students
if (isset($_POST['batch-promote'])) {
    $studentIds = json_decode($_POST['batch-promote-students']);

    if (empty($studentIds)) {
        $hasError = true;
        $message = "No students selected";
    } else {
        // get all students
        $query = "SELECT * FROM ap_userdetails WHERE roles='student'";
        $result = $dbCon->query($query);

        if ($result->num_rows > 0) {
            $students = $result->fetch_all(MYSQLI_ASSOC);

            // filter out selected students
            $filteredStudents = [];

            foreach ($students as $key => $student) {
                if (in_array($student['id'], $studentIds)) {
                    array_push($filteredStudents, $student);
                }
            }

            $students = $filteredStudents;

            // update year level
            foreach ($students as $key => $student) {
                // create a map of year level promotion, eg: 1st year -> 2nd year
                $yearLevelMap = [
                    '1st year' => '2nd Year',
                    '2nd year' => '3rd Year',
                    '3rd year' => '4th Year',
                    '4th year' => '4th Year'
                ];

                // update year level
                $yearLevel = $yearLevelMap[strtolower($student['year_level'])];
                $query = "UPDATE ap_userdetails SET year_level='$yearLevel' WHERE id='{$student['id']}'";

                if (!$dbCon->query($query)) {
                    $hasError = true;
                    $message = "An error occured while promoting <strong>{$student['firstName']} {$student['middleName']} {$student['lastName']}</strong> to <strong>$yearLevel</strong>";
                    break;
                }
            }

            if (!$hasError) {
                $hasSuccess = true;
                $message = "Students promoted successfully";
            }
        } else {
            $hasError = true;
            $message = "Error promoting students";
        }
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result1 = $dbCon->query("SELECT count(id) AS id FROM ap_userdetails WHERE roles='student'");
$students = $result1->fetch_all(MYSQLI_ASSOC);
$total = $students[0]['id'];
$pages = ceil($total / $limit);

// Prefetch all students query
$query = "SELECT * FROM ap_userdetails WHERE roles='student' LIMIT $start, $limit";
?>

<main class="w-screen h-[95%] overflow-x-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../layout/topbar.php") ?>


        <div class="px-4 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Promote Students</h1>
                </div>
                <div class="flex gap-4">
                    <label class="btn" for="batch-promote-modal">Promote Batch</label>
                    <a href="./create/student.php" class="btn">Create</a>
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
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white">ID</th>
                            <td class="bg-slate-500 text-white">Student ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Email</td>
                            <td class="bg-slate-500 text-white">Gender</td>
                            <td class="bg-slate-500 text-white">Contact</td>
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
                                        <td>{$row['sid']}</td>
                                        <td>{$row['firstName']} {$row['middleName']} {$row['lastName']}</th>
                                        <td>{$row['email']}</td>
                                        <td>" . ucfirst($row['gender']) . "</td>
                                        <td>{$row['contact']}</td>
                                        <td>" . strtolower($row['year_level']) . "</td>
                                        <td>
                                            <div class='flex gap-2 justify-center items-center'>
                                                <label for='view-student-{$row['id']}' class='btn btn-sm'>View</label>
                                                <label for='promote-student-{$row['id']}' class='btn btn-sm'>Promote</label>
                                            </div>
                                        </td>
                                    </tr>
                                ";
                            }
                        } else {
                            echo "
                                <tr>
                                    <td colspan='7'>No records found</td>
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
                    <div class="flex flex-col gap-4 px-[32px] mb-auto">

                        <!-- Student ID -->
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Student ID</span>
                            <input class="input input-bordered" name="student_id" value="<?= $row['sid'] ?>" required disabled />
                        </label>

                        <!-- Name -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">First Name</span>
                                <input class="input input-bordered" name="first_name" value="<?= $row['firstName'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Middle Name</span>
                                <input class="input input-bordered" name="middle_name" value="<?= $row['middleName'] ?>" required disabled />
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Last Name</span>
                                <input class="input input-bordered" name="last_name" value="<?= $row['lastName'] ?>" required disabled />
                            </label>
                        </div>

                        <!-- Details -->
                        <div class="grid grid-cols-3 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Gender</span>
                                <select class="select select-bordered" name="gender" required disabled>
                                    <option value="male" <?php if ($row['gender'] == 'male') { ?> selected <?php } ?>>Male</option>
                                    <option value="female" <?php if ($row['gender'] == 'female') { ?> selected <?php } ?>>Female</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Contact</span>
                                <input class="input input-bordered" name="contact" value="<?= $row['contact'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Birthdate</span>
                                <input class="input input-bordered" type="date" name="birthday" value="<?= $row['birthday'] ?? "1900-01-01" ?>" required disabled />
                            </label>
                        </div>



                        <!-- Account -->
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Email</span>
                                <input class="input input-bordered" type="email" name="email" value="<?= $row['email'] ?>" required disabled />
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Password</span>
                                <input class="input input-bordered" name="password" value="" required disabled />
                            </label>
                        </div>

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Year level</span>
                            <select class="select select-bordered" name="year_level" required disabled>
                                <option value="1st year" <?php if ($row['year_level'] == '1st year') { ?> selected <?php } ?>>1st year</option>
                                <option value="2nd year" <?php if ($row['year_level'] == '2nd year') { ?> selected <?php } ?>>2nd year</option>
                                <option value="3rd year" <?php if ($row['year_level'] == '3rd year') { ?> selected <?php } ?>>3rd year</option>
                                <option value="4th year" <?php if ($row['year_level'] == '4th year') { ?> selected <?php } ?>>4th year</option>
                            </select>
                        </label>
                    </div>
                </div>
                <label class="modal-backdrop" for="view-student-<?= $row['id'] ?>">Close</label>
            </div>

            <!-- Promote modal -->
            <input type="checkbox" id="promote-student-<?= $row['id'] ?>" class="modal-toggle" />
            <div class="modal" role="dialog">
                <div class="modal-box">

                    <form class="flex flex-col gap-4  px-[32px] mb-auto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>" />

                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Promote to year level</span>
                            <select class="select select-bordered" name="year_level" required>
                                <option value="" selected disabled>Select year</option>
                                <?php if (strtolower($row['year_level']) != '1st year') { ?> <option vaue="1st Year">1st Year</option> <?php } ?>
                                <?php if (strtolower($row['year_level']) != '2nd year') { ?> <option vaue="2nd Year">2nd Year</option> <?php } ?>
                                <?php if (strtolower($row['year_level']) != '3rd year') { ?> <option vaue="3rd Year">3rd Year</option> <?php } ?>
                                <?php if (strtolower($row['year_level']) != '4th year') { ?> <option vaue="4th Year">4th Year</option> <?php } ?>
                            </select>
                        </label>

                        <div class="grid grid-cols-2 gap-4">
                            <label class="btn w-full btn-error" for="promote-student-<?= $row['id'] ?>">Cancel</label>
                            <button class="btn w-full btn-success" name="promote-student">Promote</button>
                        </div>
                    </form>
                </div>
                <label class="modal-backdrop" for="promote-student-<?= $row['id'] ?>">Close</label>
            </div>

        <?php } ?>
        <?php mysqli_free_result($result); ?>
    <?php } ?>
</main>

<!-- Batch promote modal -->
<input type="checkbox" id="batch-promote-modal" class="modal-toggle" />
<div class="modal" role="dialog">
    <div class="modal-box">
        <form id="batch-promote-form" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" id="batch-promote-form">
            <label class="flex flex-col gap-2">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-[18px]">Students</span>

                    <label class="flex flex-col gap-2">
                        <select class="select select-bordered select-sm" id="batch-year-level-selector">
                            <!--Display all the Year level here-->
                            <option value="" selected disabled>Select Year level</option>
                            <option value="All">All</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </label>

                    <!-- Select All -->
                    <button type="button" class='btn btn-sm' id="batch-select-all">Select All</button>
                </div>

                <div class="border border-black rounded-[5px] w-full h-[300px] grid grid-cols-2 gap-4 p-4 overflow-y-scroll" id="batch-promote-body">

                    <?php $students = $dbCon->query($query); ?>
                    <?php while ($student = $students->fetch_assoc()) { ?>
                        <div class="h-[48px] flex gap-4 justify-start px-4 items-center border border-gray-400 rounded-[5px]">
                            <input type="checkbox" class="checkbox checkbox-sm" />
                            <span data-studentId="<?= $student['id'] ?>"><?= $student['firstName'] ?> <?= $student['middleName'] ?> <?= $student['lastName'] ?></span>
                        </div>
                    <?php } ?>

                </div>
            </label>

            <input type="hidden" name="batch-promote-students" id="batch-promote-students" />

            <div class="flex gap-2 flex-col mt-4">
                <button class="btn btn-success w-full" name="batch-promote">Promote</button>
                <label class="btn w-full" for="batch-promote-modal">Close</label>
            </div>
        </form>
    </div>
    <label class="modal-backdrop" for="batch-promote-modal">Close</label>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.querySelector('#batch-year-level-selector');
        const body = document.querySelector('#batch-promote-body');
        const selectAll = document.querySelector('#batch-select-all');

        // Select all students
        selectAll.addEventListener('click', () => {
            const checkboxes = document.querySelectorAll('#batch-promote-body .checkbox');
            selectAll.textContent = selectAll.textContent == "Select All" ? "Unselect All" : "Select All";

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.textContent == "Select All" ? false : true;
            });
        });

        // Batch promote form
        const batchPromoteForm = document.querySelector('#batch-promote-form');
        batchPromoteForm.addEventListener('submit', (e) => {
            const selectedStudents = Array.from(body.querySelectorAll("input[type='checkbox']:checked"));
            const selectedStudentIds = selectedStudents.map(student => student.parentElement.querySelector('span').dataset.studentid);

            document.querySelector('#batch-promote-students').value = JSON.stringify(selectedStudentIds);
        });

        // Year level filter dropdown
        select.addEventListener('change', (e) => {
            const selectedStudents = Array.from(body.querySelectorAll("input[type='checkbox']:checked"));
            const selectedStudentIds = selectedStudents.map(student => student.parentElement.querySelector('span').dataset.studentid);
            const yearLevel = e.target.value;

            // Fetch all students with the same year level
            fetch(`<?= $_SERVER['PHP_SELF'] ?>`, {
                    method: 'POST',
                    body: JSON.stringify({
                        year_level: yearLevel,
                        selected_student_ids: selectedStudentIds
                    }),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'content-type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    // get all unselected students from studentContainer
                    const unselectedStudents = Array.from(body.querySelectorAll("input[type='checkbox']:not(:checked)"));

                    // remove all unselected students from studentContainer
                    unselectedStudents.forEach(student => student.parentElement.remove());

                    // Append the students
                    data.forEach(student => {
                        const studentDiv = document.createElement("div");
                        studentDiv.classList.add("h-[48px]", "flex", "gap-4", "justify-start", "px-4", "items-center", "gap-4", "border", "border-gray-400", "rounded-[5px]");
                        studentDiv.innerHTML = `
                            <input type="checkbox" class="checkbox checkbox-sm" />
                            <span data-studentId="${student.id}">${student.firstName} ${student.middleName} ${student.lastName}</span>
                        `;

                        body.appendChild(studentDiv);
                    })
                });
        });
    });
</script>