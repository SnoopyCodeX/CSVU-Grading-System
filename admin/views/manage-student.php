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
                    <h1 class="text-[32px] font-bold">Section</h1>
                </div>
                <a href="./create/student.php" class="btn">Create</a>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 250px)">
                <table class="table table-md table-pin-rows table-pin-cols ">
                    <thead>
                    <tr>
                        <th>ID</th> 
                        <td>Name</td> 
                        <td>Email</td> 
                        <td>Gender</td> 
                        <td>Contact</td> 
                        <td>Student ID</td> 
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
                                        <td>{$row['firstName']} {$row['middleName']} {$row['lastName']}</th>
                                        <td>{$row['email']}</td>
                                        <td>{$row['gender']}</td>
                                        <td>{$row['contact']}</td>
                                        <td>{$row['sid']}</td>
                                        <td>
                                            <div class='flex gap-2 justify-center items-center'>
                                                <label for='view-student{$row['id']}' class='btn btn-small'>View</label>
                                                <label for='edit-student{$row['id']}' class='btn btn-small'>Edit</label>
                                                <label for='delete-modal{$row['id']}' class='btn btn-small'>Delete</label>
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

                    <!-- Edit modal -->
                    <input type='checkbox' id='edit-student${row['id']}' class='modal-toggle' />
                    <div class='modal' role='dialog'>
                        <div class='modal-box'>
                            <h3 class='text-lg font-bold'>Hello! {$row['firstName']}</h3>
                            <p class='py-4'>This modal works with a hidden checkbox!</p>
                        </div>
                        <label class='modal-backdrop' for='edit-student${row['id']}'>Close</label>
                    </div>

                    <!-- Delete modal -->
                    <input type='checkbox' id='delete-modal{$row['id']}' class='modal-toggle' />
                    <div class='modal' role='dialog'>
                        <div class='modal-box'>
                            <h3 class='text-lg font-bold'>Notice! {$row['firstName']}</h3>
                            <p class='py-4'>Are you sure you want to proceed? This action cannot be undone. Deleting this information will permanently remove it from the system. Ensure that you have backed up any essential data before confirming.</p>

                            <div class='flex justify-end gap-4 items-center'>
                                <label class='btn' for='delete-modal{$row['id']}'>Cancel</label>
                                <button class='btn'>Confirm</button>
                            </div>
                        </div>
                        <label class='modal-backdrop' for='delete-modal{$row['id']}'>Close</label>
                    </div>
                ";
            }

            mysqli_free_result($result);
        }
    ?>
</main>