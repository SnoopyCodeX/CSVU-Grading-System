<?php
session_start();
// kung walang session mag reredirect sa login //

require("../../configuration/config.php");
require('../../auth/controller/auth.controller.php');

if (!AuthController::isAuthenticated()) {
    header("Location: ../public/login");
    exit();
}
    
// pag meron session mag rerender yung dashboard//
require_once("../../components/header.php");

// Prefetch all students query
$query = "SELECT id, firstName, middleName, lastName, email, gender, contact, sid FROM ap_userdetails WHERE roles='student'";
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
                        <th>ID</th> 
                        <td>Student ID</td> 
                        <td>Name</td> 
                        <td>Email</td> 
                        <td>Gender</td> 
                        <td>Contact</td> 
                        <td class="text-center">Action</td>
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
                                        <td>{$row['gender']}</td>
                                        <td>{$row['contact']}</td>
                                        <td>
                                            <div class='flex gap-2 justify-center items-center'>
                                                <label for='view-student{$row['id']}' class='btn btn-sm'>View</label>
                                                <label for='edit-student{$row['id']}' class='btn btn-sm'>Promote</label>
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

    <?php
        // Modals for each row in the table
        $result = $dbCon->query($query);

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "
                    <!-- View modal -->
                    <input type='checkbox' id='view-student{$row['id']}' class='modal-toggle' />
                    <div class='modal' role='dialog'>
                        <div class='modal-box'>
                            <h3 class='text-lg font-bold'>Hello! {$row['firstName']}</h3>
                            <p class='py-4'>This modal works with a hidden checkbox!</p>
                        </div>
                        <label class='modal-backdrop' for='view-student{$row['id']}'>Close</label>
                    </div>

                    <!-- Promite modal -->
                    <input type='checkbox' id='edit-student${row['id']}' class='modal-toggle' />
                    <div class='modal' role='dialog'>
                        <div class='modal-box flex flex-col gap-3'>
                            <h3 class='text-lg font-bold'>{$row['firstName']} Promotion Status: Pending Confirmation</h3>
                            <p>Please review and confirm the promotion details for <span class='font-semibold text-lg underline'>{$row['firstName']}</span> to proceed.</p>
                           <div class='flex flex-col gap-2'>
                                <button class='btn btn-sm'>Confirm</button>
                                <label class='btn btn-sm'for='edit-student${row['id']}'>Cancel</label>
                           </div>
                        </div>
                        <label class='modal-backdrop' for='edit-student${row['id']}'>Close</label>
                    </div>
                ";
            }

            mysqli_free_result($result);
        }
    ?>
</main>



<input type="checkbox" id="reset-academic" class="modal-toggle" />
    <div class="modal" role="dialog">
    <div class="modal-box">
        <form>
        <label class="flex flex-col gap-2">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-[18px]">Students</span>

                    <label class="flex flex-col gap-2">
                        <select class="select select-bordered select-sm">
                            <!--Display all the Year level here-->
                            <option value="">Select Year level</option>
                            <option value="first-year">1st Year</option>
                            <option value="second-year">2nd Year</option>
                            <option value="third-year">3rd Year</option>
                            <option value="fourth-year">4th Year</option>
                        </select>
                    </label>

                    <!-- Select All -->
                    <button class='btn btn-sm'>Select All</button>
                </div>
                <div class="border border-black rounded-[5px] w-full h-[300px] grid grid-cols-2 gap-4 p-4 overflow-y-scroll ">
                    
                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>

                <div class="h-[48px] flex gap-4 justify-start px-4 items-center  gap-4 border border-gray-400 rounded-[5px]">
                    <input type="checkbox" class="checkbox checkbox-sm" />
                    <span>Criztian Jade M Tuplano</span>
                </div>


                </div>
        </label>

                <div class="flex gap-2 flex-col mt-4">
                    <button class="btn w-full">Submit</button>
                    <label class="btn w-full" for="reset-academic">Close</label>
                </div>
        </form>
    </div>
    <label class="modal-backdrop" for="reset-academic">Close</label>
</div>