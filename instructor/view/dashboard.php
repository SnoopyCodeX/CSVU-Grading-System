<?php
// session_start();
// // kung walang session mag reredirect sa login //

// require ("../configuration/config.php");
// require '../auth/controller/auth.controller.php';

// if (!AuthController::isAuthenticated()) {
//     header("Location: ../public/login");
//     exit();
// }
    
// // pag meron session mag rerender yung dashboard//

require_once("../../components/header.php");
    
?>


<main class="h-screen flex overflow-hidden" >
    <?php require_once("../layout/sidebar.php")  ?>
    <section class="border w-full px-4">
        <?php require_once("../layout/topbar.php") ?>

        <div class="stats shadow w-full mb-8">
            <div class="stat">
                <div class="stat-figure text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="stat-title">My Students</div>
                <div class="stat-value">31K</div>
                <div class="stat-desc">Jan 1st - Feb 1st</div>
            </div>
            
            <div class="stat">
                <div class="stat-figure text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                </div>
                <div class="stat-title">My Subject</div>
                <div class="stat-value">4,200</div>
                <div class="stat-desc">↗︎ 400 (22%)</div>
            </div>
            
            <div class="stat">
                <div class="stat-figure text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <div class="stat-title">My Section</div>
                <div class="stat-value">1,200</div>
                <div class="stat-desc">↘︎ 90 (14%)</div>
            </div>
            
            </div>
        </div>

        <div class="px-4 flex justify-between flex-col gap-4">
            <!-- Table Header -->
            <div class="flex justify-between items-center">
                <!-- Table Header -->
                <div class="flex justify-between items-center">
                    <h1 class="text-[24px] font-bold">Recent Subjects</h1>
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-hidden border border-gray-300 rounded-md" style="height: calc(100vh - 330px)">
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
                    </tr>
                    </tbody> 
                </table>
            </div>
        </div>
        </div>
    </section>

</main>