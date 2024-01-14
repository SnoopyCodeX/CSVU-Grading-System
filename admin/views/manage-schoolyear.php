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


<main class="overflow-hidden flex" >
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../layout/topbar.php") ?>
        <div class="px-4 flex justify-between flex-col gap-4">

              <!-- Table Header -->
              <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[32px] font-bold">School Year</h1>
                </div>
               <div class="flex gap-4">
                    <label for="reset-academic" class="btn btn-small">Reset</label>
                    <a href="./create/academic-year.php" class="btn">Create</a>
               </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                    <tr>
                        <th></th> 
                        <td>Academic Year</td> 
                        <td class="text-center">Action</td>
                    </tr>
                    </thead> 
                    <tbody>
                    <tr>
                        <th>20</th> 
                        <td>
                            <div class="badge p-4 bg-green-400 text-base font-semibold">
                                2023 - 2025
                            </div>
                        </td> 
                        <td>
                           <div class="flex justify-center gap-2">
                           <label for="edit-student" class="btn btn-sm">Edit</label>
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
                        <i class='bx bxs-chevron-right' ></i>
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
        <form>
            <!-- Name -->
            <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">School Year</span>
                    <select class="select select-bordered">
                        <option disabled="disabled" selected="selected">Select an option</option>
                        // school year options 2022 - 2023 using item //
                        <?php
                            $earlyYear = 2022;
                            $lateYear = 2030;
                            for ($i = $earlyYear; $i <= $lateYear; $i++) {
                                echo "<option value='$i'>$i - " . ($i + 1) . "</option>";
                            }
                        ?>
                    </select>
                </label>

                <div class="flex gap-2 flex-col mt-4">
                    <button class="btn w-full">Submit</button>
                    <label class="btn w-full" for="edit-student">Close</label>
                </div>
        </form>
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


    <input type="checkbox" id="reset-academic" class="modal-toggle" />
    <div class="modal" role="dialog">
    <div class="modal-box">
        <form>
            <!-- Name -->
            <label class="flex flex-col gap-2">
                    <span class="font-bold text-[18px]">Reset School Year</span>
                    <select class="select select-bordered">
                        <option disabled="disabled" selected="selected">Select an option</option>
                        // school year options 2022 - 2023 using item //
                        <?php
                            $earlyYear = 2022;
                            $lateYear = 2030;
                            for ($i = $earlyYear; $i <= $lateYear; $i++) {
                                echo "<option value='$i'>$i - " . ($i + 1) . "</option>";
                            }
                        ?>
                    </select>
                </label>

                <div class="flex gap-2 flex-col mt-4">
                    <button class="btn w-full">Submit</button>
                    <label class="btn w-full" for="edit-student">Close</label>
                </div>
        </form>
    </div>
    <label class="modal-backdrop" for="edit-student">Close</label>
    </div>