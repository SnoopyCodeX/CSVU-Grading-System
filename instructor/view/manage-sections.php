<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}

// pag meron session mag rerender yung dashboard//

// get user details
$email = $_SESSION['email'];
$detailsQuery = $dbCon->query("SELECT * FROM ap_userdetails WHERE email='$email'");
$result = $detailsQuery->fetch_assoc();

$UID = $result['id'] ?? "";

// add also the following students count, subject title and instructor name
$sectionQuery = $dbCon->query("SELECT * FROM ap_sections WHERE instructor='$UID'");
require_once("../../components/header.php");
?>


<main class="flex overflow-hidden">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="h-screen w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Sections</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white"></th>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Term</td>
                            <td class="bg-slate-500 text-white">Students</td>
                            <td class="bg-slate-500 text-white">Subject</td>
                            <td class="bg-slate-500 text-white">Instructor</td>
                            <td class="bg-slate-500 text-white">Status</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $sectionQuery->fetch_assoc()) {

                        ?>
                            <tr>
                                <th><?= $row['id'] ?></th>
                                <td><?= $row['name'] ?></td>
                                <td><?= $row['term'] ?></td>
                                <!-- Student Count -->
                                <td>1</td>
                                <!-- Subject Name -->
                                <td>DSA-101</td>

                                <td>
                                    <?= $result['lastName'] ?>,
                                    <?= $result['firstName'] ?>
                                    <?= $result['middleName'] ?>
                                </td>
                                <td>
                                    <span class='badge p-4 bg-blue-300'>
                                        On going
                                    </span>
                                </td>
                            </tr>
                        <?php
                        }



                        ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-between items-center">
                <button class="btn text-[24px]">
                    <i class='bx bx-chevron-left'></i>
                </button>
                <button class="btn">Page 22</button>
                <button class="btn text-[24px]">
                    <i class='bx bxs-chevron-right'></i>
                </button>
            </div>
        </div>
    </section>


    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="view-student" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <h3 class="text-lg font-bold">Hello!</h3>
            <p class="py-4">This modal works with a hidden checkbox!</p>
        </div>
        <label class="modal-backdrop" for="view-student">Close</label>
    </div>

    <input type="checkbox" id="edit-student" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <h3 class="text-lg font-bold">Hello!</h3>
            <p class="py-4">This modal works with a hidden checkbox!</p>
        </div>
        <label class="modal-backdrop" for="edit-student">Close</label>
    </div>

    <input type="checkbox" id="delete-modal" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <h3 class="text-lg font-bold">Notice!</h3>
            <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

            <div class="flex justify-end gap-4 items-center">
                <label class="btn" for="delete-modal">Close</label>
                <button class="btn">Confirm</button>
            </div>
        </div>
        <label class="modal-backdrop" for="delete-modal">Close</label>
    </div>
</main>