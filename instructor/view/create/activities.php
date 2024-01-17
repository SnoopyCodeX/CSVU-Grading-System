<?php
session_start();
// kung walang session mag reredirect sa login //

// require('../../../auth/controller/auth.controller.php');

// if (!AuthController::isAuthenticated()) {
//     header("Location: ../public/login");
//     exit();
// }
    
// pag meron session mag rerender yung dashboard//
require_once("../../../components/header.php");
require_once("../../../configuration/config.php");

$email = $_SESSION['email'];
$detailsQuery = $dbCon->query("SELECT id from ap_userdetails WHERE email='$email'");
$UID = $detailsQuery->fetch_assoc()['id'];

$schoolYearQuery = $dbCon->query("SELECT * FROM  ap_school_year WHERE 1");
$sectionQuery = $dbCon->query("SELECT * FROM  ap_sections WHERE instructor='$UID'");

$sectionResult = $sectionQuery->fetch_assoc();


// get all the section related to the instructor
// get all the subject that is related to the instructor
// get all the students that is related to the instructor

?>

<main class="w-screen flex" >
    <?php require_once("../../layout/sidebar.php")  ?>
    <section class="w-full px-4">
        <?php require_once("../../layout/topbar.php") ?>
            <div class="w-full h-full ">
                <div class="flex justify-center items-center flex-col p-8 ">
                    <h2 class="text-[38px] font-bold mb-4">Create Activity</h2>
                    <form class="flex flex-col gap-[24px]  px-[32px]  w-[800px] mb-auto flex">
                        
                        <!-- Details -->
                        <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Activity Name</span>
                                <input class="input input-bordered" />
                            </label>


                            <!-- Main Grid -->
                        <div class="grid grid-cols-2 gap-4">
                        
                        
                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Subject</span>
                                <select class="select select-bordered">
                                    <!--Display all the subjects here-->
                                    <option value="">Select Subject</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">School Year</span>
                                <select class="select select-bordered">
                                    <option value="">Select School Year</option>
                                    <?php
                                 while($row = $schoolYearQuery->fetch_assoc()){
                                    ?>
                                    <!--Display all the School Year here-->
                                    <option value="<?=$row['school_year']?>"><?=$row['school_year']?></option>
                                    <?php
                                 }

                                    ?>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">School Term</span>
                                <select class="select select-bordered">
                                    <!--Display all the Semister here-->
                                    <option value="">Select Semester</option>
                                    <option value="first-sem">1st Semester</option>
                                    <option value="second-sem">2nd Semester</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Year level</span>
                                <select class="select select-bordered">
                                    <!--Display all the Year here-->
                                    <option value="">Select Year level</option>
                                    <option value="first-sem">1st Year</option>
                                    <option value="second-sem">2nd Year</option>
                                    <option value="second-sem">3rd Year</option>
                                    <option value="second-sem"> Year</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Course</span>
                                <select class="select select-bordered">
                                    <!--Display all the Course here-->
                                    <option value="">Select Semester</option>
                                    <option value="first-sem">First Semester</option>
                                    <option value="second-sem">Second Semester</option>
                                </select>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            
                        <label class="flex flex-col gap-2">
                            <span class="font-bold text-[18px]">Passing Rate</span>
                            <select class="select select-bordered">
                                <!--Display all the Course here-->
                                <option value="">Select Passing Rate</option>
                                <option value="first-sem">25%</option>
                                <option value="second-sem">50%</option>
                                <option value="second-sem">75%</option>
                                <option value="second-sem">100%</option>
                            </select>
                        </label>


                        <label class="flex flex-col gap-2">
                                <span class="font-bold text-[18px]">Max Score</span>
                                <input type="number" class="input input-bordered" />
                            </label>
                            
                        </div>


                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-4">
                           <div></div>
                           <div class="flex flex-col gap-4">
                                <button class="btn text-base btn-success">Create</button>
                                <a href="../manage-activity.php" class="btn text-base btn-error">Cancel</a>
                           </div>
                        </div>
                    </form>
                </div>
            </div>
    </section>
</main>