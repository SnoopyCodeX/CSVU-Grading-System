<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

// Check if the received request is an ajax request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $data = json_decode(file_get_contents('php://input'), true);
    $semester = $data['semester'];
    $id = $data['id'];

    // Get student grades from ap_student_final_grades table
    $gradesResults = $dbCon->query("SELECT 
        ap_student_final_grades.*,
        ap_courses.course_code AS course_code,
        ap_subjects.name AS subject_name,
        ap_subjects.units AS subject_units,
        ap_subjects.credits_units AS subject_credit_units 
        FROM ap_student_final_grades 
        LEFT JOIN ap_sections ON ap_student_final_grades.section = ap_sections.id
        LEFT JOIN ap_courses ON ap_sections.course = ap_courses.id
        LEFT JOIN ap_subjects ON ap_student_final_grades.subject = ap_subjects.id
        WHERE ap_student_final_grades.student = $id AND ap_student_final_grades.term = '$semester'
    ");

    // Get grade request from ap_grade_requests table
    $gradeRequestsResults = $dbCon->query("SELECT * FROM ap_grade_requests WHERE student_id = $id AND term = '$semester'");
    $gradeRequest = $gradeRequestsResults->fetch_assoc();

    $grades = $gradesResults->fetch_all(MYSQLI_ASSOC);
    $grades = array_merge(['grades' => $grades], ['gradeRequest' => $gradeRequest]);


    echo json_encode($grades);
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

// request grade 
if(isset($_POST['request-grade'])) {
    $student_id = $_POST['student_id'];
    $term = $_POST['term'];

    // get section id from ap_section_students table using student's id
    $sectionResults = $dbCon->query("SELECT section_id FROM ap_section_students WHERE student_id = $student_id");
    $section = $sectionResults->fetch_assoc();

    // check if student_id, term and section_id already exists
    $gradeRequestResults = $dbCon->query("SELECT * FROM ap_grade_requests WHERE student_id = $student_id AND term = '$term' AND section_id = {$section['section_id']} AND status NOT IN ('approved', 'pending')");

    // if it already exists, only update its status back to pending
    if($gradeRequestResults->num_rows > 0) {
        $result = $dbCon->query("UPDATE ap_grade_requests SET status = 'pending' WHERE student_id = $student_id AND term = '$term' AND section_id = {$section['section_id']}");

        if($result) {
            $hasSuccess = true;
            $message = "Your request for the release of your grade has been sent to the admin.";
        } else {
            $hasError = true;
            $message = "There was an error sending your request, please try again.";
        }
    } else {
        $result = $dbCon->query("INSERT INTO ap_grade_requests (student_id, section_id, term, status) VALUES ($student_id, {$section['section_id']}, '$term', 'pending')");

        if($result) {
            $hasSuccess = true;
            $message = "Your request for the release of your grade has been sent to the admin.";
        } else {
            $hasError = true;
            $message = "There was an error sending your request, please try again.";
        }
    }
}

// pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// total pages
$result = $dbCon->query("SELECT count(id) AS id FROM ap_student_final_grades");
$gradesCount = $result->fetch_all(MYSQLI_ASSOC);
$total = $gradesCount[0]['id'];
$pages = ceil($total / $limit);

// Get the user id from the database using the user email that is saved in the session
$id = AuthController::user()->id;

// Get student grades from ap_student_final_grades table
$gradesResults = $dbCon->query("SELECT 
    ap_student_final_grades.*,
    ap_courses.course_code AS course_code,
    ap_subjects.name AS subject_name,
    ap_subjects.units AS subject_units,
    ap_subjects.credits_units AS subject_credit_units 
    FROM ap_student_final_grades 
    LEFT JOIN ap_sections ON ap_student_final_grades.section = ap_sections.id
    LEFT JOIN ap_courses ON ap_sections.course = ap_courses.id
    LEFT JOIN ap_subjects ON ap_student_final_grades.subject = ap_subjects.id
    WHERE ap_student_final_grades.student = $id AND ap_student_final_grades.term='1st Sem' LIMIT $start, $limit
");

// Get grade request from ap_grade_requests table
$gradeRequestsResults = $dbCon->query("SELECT * FROM ap_grade_requests WHERE student_id = $id AND term='1st Sem' LIMIT $start, $limit");
$gradeRequest = $gradeRequestsResults->fetch_assoc();
?>


<main class="h-[95%] overflow-x-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <div id="alert-dialogs">
                <?php if($gradeRequestsResults->num_rows > 0) : ?>
                    <?php if($gradeRequest['status'] == 'pending') : ?>
                        <div role="alert" class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span>Your request for the release of your grade is currently pending.</span>
                        </div>
                    <?php endif; ?>

                    <?php if($gradeRequest['status'] == 'rejected') : ?>
                        <div role="alert" class="alert alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Your request for the release of your grade has been rejected by the admin, you may send another request to the admin.</span>
                        </div>
                    <?php endif; ?>

                    <?php if($gradeRequest['status'] == 'approved') : ?>
                        <div role="alert" class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>Your request for the release of your grade has been approved by the admin, you may now be able to view and print your grade.</span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div role="alert" class="alert alert-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span>You currently don't have permission to view and print out your grades. Please send a grade request and wait for the admin's approval.</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">My Grades</h1>
                </div>

                <div class="flex gap-4">
                    <label class="flex flex-col gap-2">
                        <select class="select select-bordered" id="semester-filter">
                            <option value="" disabled>Select Semester</option>
                            <option value="1st Sem" selected>1st Sem</option>
                            <option value="2nd Sem">2nd Sem</option>
                            <option value="3rd Sem">3rd Sem</option>
                        </select>
                    </label>

                    <button class="btn btn-info" id="print-button" onclick="print_modal.showModal()" <?php if($gradeRequestsResults->num_rows == 0 || $gradeRequest['status'] != 'approved') { ?> disabled <?php } ?>>
                        <i class="bx bxs-printer"></i> Print
                    </button>
                    
                    <button class="btn btn-success" id="request-button" onclick="request_modal.showModal()" <?php if($gradeRequestsResults->num_rows > 0 && ($gradeRequest['status'] == 'approved' || $gradeRequest['status'] == 'pending')) { ?> disabled <?php } ?>>
                        <i class="bx bxs-paper-plane "></i> Request for Grade
                    </button>
                </div>

            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)" id="printable-table">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course Code</th>
                            <th>Title</th>
                            <th>Grade</th>
                            <th>Units</th>
                            <th>Credit Units</th>
                        </tr>
                    </thead>
                    <tbody id="grades-body">
                        <?php
                        if ($gradeRequest != null && $gradesResults->num_rows > 0 && $gradeRequest['status'] == 'approved') {
                            while ($row = $gradesResults->fetch_assoc()) {
                        ?>
                                <tr>
                                    <td><?php echo $row['id'] ?></td>
                                    <td><?php echo $row['course_code'] ?></td>
                                    <td><?php echo $row['subject_name'] ?></td>
                                    <td><?php echo $row['grade'] ?></td>
                                    <td><?php echo $row['subject_units'] ?></td>
                                    <td><?php echo $row['subject_credit_units'] ?></td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo "<tr class='text-center'><td colspan='6'>No grades to show</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="flex justify-between items-center">
                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page - 1 ?>" <?php if ($page - 1 <= 0) { ?> disabled <?php } ?>>
                    <i class='bx bx-chevron-left'></i>
                </a>

                <button class="btn btn-sm" type="button">Page <?= $page ?> of <?= $pages ?></button>

                <a class="btn text-[24px] btn-sm" href="<?= $_SERVER['PHP_SELF'] ?>?page=<?= $page + 1 ?>" <?php if ($page + 1 >= $pages) { ?> disabled <?php } ?>>
                    <i class='bx bxs-chevron-right'></i>
                </a>
            </div>
        </div>
    </section>
</main>

<!-- Print modal -->
<dialog id="print_modal" class="modal">
  <div class="modal-box border border-info">
    <h3 class="font-bold text-lg text-info">Print Grade</h3>
    <p class="py-4">Are you really sure you want to print your grade?</p>

    <form class="flex justify-end gap-4" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <button class="btn btn-error" onclick="print_modal.close()">Cancel</button>
        <button class="btn btn-success" onclick="printTable()">Yes</button>
    </form>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<!-- Request modal -->
<dialog id="request_modal" class="modal">
  <div class="modal-box border border-success">
    <h3 class="font-bold text-lg text-success">Request for Grade</h3>
    <p class="py-4">Are you really sure you want to request for your grade?</p>

    <form class="flex justify-end gap-4" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" id="request-form-modal">
        <input type="hidden" name="student_id" value="<?= $id ?>">
        <input type="hidden" name="term" value="1st Sem">

        <button class="btn btn-error" onclick="request_modal.close()">Cancel</button>
        <button class="btn btn-success" name="request-grade" onclick="request_modal.close()">Yes</button>
    </form>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<script>
    const semesterFilter = document.getElementById('semester-filter');
    const alertDialogs = document.getElementById('alert-dialogs');
    const printButton = document.getElementById('print-button');
    const requestButton = document.getElementById('request-button');

    semesterFilter.addEventListener('change', (e) => {
        const selectedSemester = e.target.value;
        const gradesBody = document.getElementById('grades-body');

        // update values of input[name="term"] in the request form modal
        document.getElementById('request-form-modal').querySelector('input[name="term"]').value = selectedSemester;
        
        fetch(`<?= $_SERVER['PHP_SELF'] ?>`, {
                method: 'POST',
                body: JSON.stringify({
                    semester: selectedSemester,
                    id: <?= $id ?>
                }),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                data.grades.forEach(grade => {
                    html += `
                        <tr>
                            <td>${grade.id}</td>
                            <td>${grade.course_code}</td>
                            <td>${grade.subject_name}</td>
                            <td>${grade.grade}</td>
                            <td>${grade.subject_units}</td>
                            <td>${grade.subject_credit_units}</td>
                        </tr>
                    `;
                });

                // Disable print button if there is no grade request or the grade request is not approved
                if(data.gradeRequest) {
                    if(data.gradeRequest.status == 'approved') {
                        printButton.removeAttribute('disabled');
                    } else {
                        printButton.setAttribute('disabled', true);
                    }
                } else {
                    printButton.setAttribute('disabled', true);
                }

                // Display alert dialogs based on the grade request status
                if(data.gradeRequest) {
                    // clear the alert dialogs
                    alertDialogs.innerHTML = '';

                    if(data.gradeRequest.status == 'pending') {
                        alertDialogs.innerHTML = `
                            <div role="alert" class="alert alert-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                <span>Your request for the release of your grade is currently pending.</span>
                            </div>
                        `;

                        requestButton.setAttribute('disabled', true);
                        printButton.setAttribute('disabled', true);
                    }

                    if(data.gradeRequest.status == 'rejected') {
                        alertDialogs.innerHTML = `
                            <div role="alert" class="alert alert-error">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Your request for the release of your grade is has been rejected by the admin, you may send another request to the admin.</span>
                            </div>
                        `;

                        requestButton.removeAttribute('disabled');
                        printButton.setAttribute('disabled', true);
                    }

                    if(data.gradeRequest.status == 'approved') {
                        alertDialogs.innerHTML = `
                            <div role="alert" class="alert alert-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Your request for the release of your grade has been approved by the admin, you may now be able to view and print your grade.</span>
                            </div>
                        `;

                        printButton.removeAttribute('disabled');
                        requestButton.setAttribute('disabled', true);
                    }
                } else {
                    alertDialogs.innerHTML = `
                        <div role="alert" class="alert alert-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span>You currently don't have permission to view and print out your grades. Please send a grade request and wait for the admin's approval.</span>
                        </div>
                    `;

                    printButton.setAttribute('disabled', true);
                    requestButton.removeAttribute('disabled');
                }
                
                gradesBody.innerHTML = !html ? "<tr class='text-center'><td colspan='6'>No grades to show</td></tr>" : html;
            });
    });

    function printTable() {
        let tableContent = document.getElementById('printable-table').outerHTML;
        let originalContents = document.body.innerHTML;

        document.body.innerHTML = tableContent;

        window.print();

        document.body.innerHTML = originalContents;
        print_modal.close()
    }
</script>