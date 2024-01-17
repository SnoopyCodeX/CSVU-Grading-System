<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require('../../auth/controller/auth.controller.php');

// check if request is an ajax request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // fetch students
    if (isset($_GET['year_level'])) {
        $yearLevel = $dbCon->real_escape_string($_GET['year_level']);

        $query = "SELECT * FROM ap_userdetails WHERE year_level='$yearLevel' AND roles='student'";
        $result = $dbCon->query($query);

        if ($result->num_rows > 0) {
            $students = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($students);
        } else {
            echo json_encode([]);
        }
    }

    exit();
}

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
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

<main class="w-screen h-[95%] overflow-x-hidden flex" >
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
               <label class="btn" for="reset-academic">Promote Batch</label>
                <a href="./create/student.php" class="btn">Create</a>
               </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white" >ID</th> 
                            <td class="bg-slate-500 text-white" >Student ID</td> 
                            <td class="bg-slate-500 text-white" >Name</td> 
                            <td class="bg-slate-500 text-white" >Email</td> 
                            <td class="bg-slate-500 text-white" >Gender</td> 
                            <td class="bg-slate-500 text-white" >Contact</td> 
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
                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if($page - 1 <= 0) { ?> disabled <?php } ?>> 
                    <i class='bx bx-chevron-left'></i>
                </a>
                
                <button class="btn" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px]" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if($page + 1 >= $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right' ></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <?php $result = $dbCon->query($query); ?>
    <?php  if($result->num_rows > 0) { ?>
        <?php while($row = $result->fetch_assoc()) { ?>

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
                                    <option value="male" <?php if($row['gender'] == 'male') { ?>  selected  <?php } ?>>Male</option>
                                    <option value="female" <?php if($row['gender'] == 'female') { ?>  selected  <?php } ?>>Female</option>
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
                                <option value="1st year" <?php if($row['year_level'] == '1st year') { ?>  selected  <?php } ?>>1st year</option>
                                <option value="2nd year" <?php if($row['year_level'] == '2nd year') { ?>  selected  <?php } ?>>2nd year</option>
                                <option value="3rd year" <?php if($row['year_level'] == '3rd year') { ?>  selected  <?php } ?>>3rd year</option>
                                <option value="4th year" <?php if($row['year_level'] == '4th year') { ?>  selected  <?php } ?>>4th year</option>
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
                                <?php if($row['year_level'] != '1st year') { ?>  <option vaue="1st Year">1st Year</option>  <?php } ?>
                                <?php if($row['year_level'] != '2nd year') { ?>  <option vaue="2nd Year">2nd Year</option>  <?php } ?>
                                <?php if($row['year_level'] != '3rd year') { ?>  <option vaue="3rd Year">3rd Year</option>  <?php } ?>
                                <?php if($row['year_level'] != '4th year') { ?>  <option vaue="4th Year">4th Year</option>  <?php } ?>
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
<input type="checkbox" id="reset-academic" class="modal-toggle" />
<div class="modal" role="dialog">
    <div class="modal-box">
        <form id="batch-promote-form" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
            <label class="flex flex-col gap-2">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-[18px]">Students</span>

                    <label class="flex flex-col gap-2">
                        <select class="select select-bordered select-sm" id="batch-year-level-selector">
                            <!--Display all the Year level here-->
                            <option value="" selected disabled>Select Year level</option>
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
                    <?php while($student = $students->fetch_assoc()) { ?>
                        <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                            <input type="checkbox" class="checkbox checkbox-sm" />
                            <span><?= $student['firstName'] ?> <?= $student['middleName'] ?> <?= $student['lastName'] ?></span>
                        </div>
                    <?php } ?>

                </div>
            </label>

            <div class="flex gap-2 flex-col mt-4">
                <button class="btn btn-success w-full">Submit</button>
                <label class="btn w-full" for="reset-academic">Close</label>
            </div>
        </form>
    </div>
    <label class="modal-backdrop" for="reset-academic">Close</label>
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
            checkbox.checked = !checkbox.checked;
        });
    });

    // Year level filter dropdown
    select.addEventListener('change', (e) => {
        const yearLevel = e.target.value;

        // Fetch all students with the same year level
        fetch(`<?= $_SERVER['PHP_SELF'] ?>?year_level=${encodeURIComponent(yearLevel)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'content-type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            // Clear the body
            body.innerHTML = "";

            // Append the students
            data.forEach(student => {
                body.innerHTML += `
                    <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                        <input type="checkbox" class="checkbox checkbox-sm" />
                        <span>${student.firstName} ${student.middleName} ${student.lastName}</span>
                    </div>
                `;
            });
        });
    });
});

</script>