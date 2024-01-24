<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/admin/views";
?>


<div class="navbar bg-base-100">

  <div class="flex-none">
      <button class="btn btn-ghost" onclick="my_modal_2.showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>
  </div>


  <div class="flex-1">
    <a class="btn btn-ghost text-xl">CSVU</a>
  </div>
  <div class="flex-none gap-2">
    <div class="dropdown dropdown-end">
      <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
        <div class="w-10 rounded-full">
          <img alt="Tailwind CSS Navbar component" src="https://daisyui.com/images/stock/photo-1534528741775-53994a69daeb.jpg" />
        </div>
      </div>
      <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-[5px] w-52">
        <li><a href="<?= $rootFolder ?>/logout">Logout</a></li>
      </ul>
    </div>
  </div>
</div>


<dialog id="my_modal_2" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Navigation!</h3>
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

                 <li>
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
                 </li>
                 <li>
                     <details>
                         <summary>
                             <i class='bx bxs-user text-[24px]'></i>
                             <span class="text-[18px]">Users</span>
                         </summary>
                         <ul>
                             <li><a href="<?php echo $rootFolder; ?>/manage-student">Manage Students</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/manage-promote-student">Promote Students</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/manage-instructor">Manage Instructor</a></li>
                             <li><a href="<?php echo $rootFolder; ?>/manage-admin">Manage Admin</a></li>
                         </ul>
                     </details>
                 </li>
                 <!-- <li>
                     <a href="<?php echo $rootFolder; ?>/grade-request">
                         <i class='bx bxs-notepad text-[24px]'></i>
                         <span class="text-[18px]">Request</span>
                     </a>
                 </li> -->
             </ul>
         </nav>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
