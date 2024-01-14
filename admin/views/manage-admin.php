<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
    exit();
}
    
// pag meron session mag rerender yung dashboard//

require_once("../../components/header.php");
?>


<main class="h-[95%] overflow-x-hidden flex" >
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

             <!-- Table Header -->
             <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">Admin</h1>
                </div>
                <a href="./create/admin.php" class="btn">Create</a>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                    <tr>
                        <th></th> 
                        <td>Name</td> 
                        <td>Job</td> 
                        <td>company</td> 
                        <td>location</td> 
                        <td>Last Login</td> 
                        <td>Favorite Color</td>
                        <td class="text-center">Action</td>
                    </tr>
                    </thead> 
                    <tbody>
                    <tr>
                        <th>20</th> 
                        <td>Lorelei Blackstone</td> 
                        <td>Data Coordinator</td> 
                        <td>Witting, Kutch and Greenfelder</td> 
                        <td>Kazakhstan</td> 
                        <td>6/3/2020</td> 
                        <td>6/3/2020</td> 
                        <td>
                           <div class="flex gap-2">
                           <label for="view-student" class="btn btn-small">View</label>
                           <label for="edit-student" class="btn btn-small">Edit</label>
                           <label for="delete-modal" class="btn btn-small">Delete</label>
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
                        <i class='bx bxs-chevron-right' ></i>
                    </button>
                </div>
        </div>
    </section>


    <!-- View Students Modal -->
    <input type="checkbox" id="view-student" class="modal-toggle" />
    <div class="modal" role="dialog">
    <div class="modal-box">
        <h3 class="text-lg font-bold">Hello!</h3>
        <p class="py-4">This modal works with a hidden checkbox!</p>
    </div>
    <label class="modal-backdrop" for="view-student">Close</label>
    </div>


      <!-- Edit Students Modal -->
    <input type="checkbox" id="edit-student" class="modal-toggle" />
    <div class="modal" role="dialog">
    <div class="modal-box">
        <h3 class="text-lg font-bold">Hello!</h3>
        <p class="py-4">This modal works with a hidden checkbox!</p>
    </div>
    <label class="modal-backdrop" for="edit-student">Close</label>
    </div>

      <!-- Delete Students Modal -->
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