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
require_once("../../components/header.php");
?>


<main class="h-[95%] overflow-x-hidden flex">
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Activities</h1>
                </div>

                <div class="flex gap-4">
                    <label for="submit-modal" class="btn">Submit</label>
                    <a href="./create/activities.php" class="btn">Create</a>
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
                            <td class="bg-slate-500 text-white">Passing Score</td>
                            <td class="bg-slate-500 text-white">Status</td>
                            <td class="bg-slate-500 text-white text-center">Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>20</th>
                            <td>Activity 1</td>
                            <td>1st sem</td>
                            <td>32</td>
                            <td>DSA-101</td>
                            <td>50</td>
                            <td>
                                <div class="badge p-4  bg-blue-300">
                                    On going
                                </div>
                            </td>
                            <td>
                                <div class="flex justify-center items-center gap-2">
                                    <a class="btn btn-sm" href="./view/activities.php">View</a>
                                    <a class="btn btn-sm" href="./update/activities.php">Edit</a>
                                    <label for="delete-modal" class="btn btn-sm">Delete</label>
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



    <input type="checkbox" id="submit-modal" class="modal-toggle" />
    <div class="modal" role="dialog">
        <div class="modal-box">
            <form>
                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Subject</span>
                    <select class="select select-bordered">
                        <!-- Display all the subject related to the instructor -->
                        <option value="">Select Subject </option>

                    </select>
                </label>

                <label class="flex flex-col gap-2 my-4">
                    <span class="font-bold text-[18px]">Term</span>
                    <select class="select select-bordered">
                        <!--Display all the Course here-->
                        <option value="">Select Term</option>
                        <option value="first-sem">Prelim</option>
                        <option value="second-sem">Midterm</option>
                        <option value="second-sem">Finals</option>
                    </select>
                </label>

                <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Semester</span>
                    <select class="select select-bordered">
                        <!--Display all the Course here-->
                        <option value="">Select Semester</option>
                        <option value="first-sem">1st Sem</option>
                        <option value="first-sem">2nd Sem</option>
                    </select>
                </label>



                <div class="flex justify-end gap-4 items-center my-4">
                    <label class="btn" for="submit-modal">Close</label>
                    <button class="btn">Confirm</button>
                </div>
            </form>

        </div>
        <label class="modal-backdrop" for="submit-modal">Close</label>
    </div>
</main>