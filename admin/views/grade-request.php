<?php
session_start();
require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}

require_once("../../components/header.php");
?>

<main class="w-screen h-screen overflow-x-hidden grid grid-cols-[300px_auto] gap-[24px]  overflow-hidden">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4 overflow-hidden">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-[32px] font-bold">Request</h1>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                        <tr>
                            <th class="bg-slate-500 text-white"></th>
                            <td class="bg-slate-500 text-white">ID</td>
                            <td class="bg-slate-500 text-white">Name</td>
                            <td class="bg-slate-500 text-white">Year</td>
                            <td class="bg-slate-500 text-white">Subject</td>
                            <td class="bg-slate-500 text-white">Term</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>20</th>
                            <td>123123123</td>
                            <td>Criztian Jade</td>
                            <td>1st</td>
                            <td>DSA-101</td>
                            <td>1st</td>
                            <td>
                                <div class="flex justify-center items-center gap-2">
                                    <label for="view-student" class="btn btn-sm">View</label>
                                    <label for="edit-student" class="btn btn-sm">Accept</label>
                                    <label for="delete-modal" class="btn btn-sm">Reject</label>
                                </div>
                            </td>
                        </tr>
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
</main>

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
        <h3 class="text-lg font-bold">Notice!</h3>
        <p class="py-4">Are you sure you want to proceed? This action cannot be undone. Accepting this information will let the student to download grade sheet.</p>
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