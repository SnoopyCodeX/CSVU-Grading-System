 <?php
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $FirstDir = explode('/', trim($currentDir, '/'));
    $baseFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];
    $rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/admin/views";
    $dasboard = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/admin/dashboard";
    ?>

 <aside class="hidden md:block  w-[320px] h-screen border-r  p-4 flex flex-col gap-4 justify-between sticky top-0 bg-[#405D47] text-white">
     <div class="h-full">
       <div class="flex justify-center items-center flex-col">
        <img src=<?php echo $baseFolder."/assets/images/logo.png"  ?> class='w-[120px] h-[120px]' />
        <h1 class="text-[14px] text-center font-semibold p-4">Cavite State University - General Trias City, Campus (CvSU)</h1>
       </div>
         <nav class="my-4 h-full ">
             <ul class="menu">
                 <li><a href="<?php echo $dasboard; ?>">
                         <i class='bx bxs-dashboard text-[24px]'></i>
                         <span class="text-[18px]">Dashboard</span>
                     </a></li>
                 <li>
                     <details>
                         <summary>
                             <i class='bx bxs-buildings text-[24px]'></i>
                             <span class="text-[18px]">Department</span>
                         </summary>
                         <ul>
                             <li><a href="<?php echo $rootFolder; ?>/manage-course">Manage Course</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/manage-subjects">Manage Subjects</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/manage-sections">Manage Sections</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/manage-schoolyear">Manage School Year</a></li>
                         </ul>
                     </details>
                 </li>

                 <!-- <li>
                     <details>
                         <summary>
                             <i class='bx bxs-buildings text-[24px]'></i>
                             <span class="text-[18px]">Year Level</span>
                         </summary>
                         <ul>
                             <li><a href="<?php echo $rootFolder; ?>/view/yearlevel/1styear.php">1st Year</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/view/yearlevel/2ndyear.php">2nd Year</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/view/yearlevel/3rdyear.php">3rd Year</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/view/yearlevel/4thyear.php">4th Year</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/view/yearlevel/5thyear.php">5th Year</a></li>
                         </ul>
                     </details>
                 </li> -->
                 <li>
                     <details>
                         <summary>
                             <i class='bx bxs-user text-[24px]'></i>
                             <span class="text-[18px]">Users</span>
                         </summary>
                         <ul>
                             <li><a href="<?php echo $rootFolder; ?>/manage-student">Manage Students</a></li>
                             <!-- <li><a href="<?php echo $rootFolder; ?>/manage-promote-student">Promote Students</a></li> -->
                             <li><a href="<?php echo $rootFolder; ?>/manage-instructor">Manage Instructor</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/manage-admin">Manage Admin</a></li>
                         </ul>
                     </details>
                 </li>
                 <li>
                     <a href="<?php echo $rootFolder; ?>/grade-request">
                         <i class='bx bxs-notepad text-[24px]'></i>
                         <span class="text-[18px]">Grade Requests</span>
                     </a>
                 </li>
             </ul>
         </nav>
     </div>
 </aside>