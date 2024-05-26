<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/admin/views";

// Change Password
if (isset($_POST['change-password'])) {
  $old_password = $dbCon->real_escape_string($_POST['old-password']);
  $new_password = $dbCon->real_escape_string($_POST['new-password']);
  $confirm_password = $dbCon->real_escape_string($_POST['confirm-password']);

  if ($new_password != $confirm_password) {
    $hasError = true;
    $message = "New password and confirm password does not match!";
  } else {
    $sql = "SELECT * FROM userdetails WHERE id = " . AuthController::user()->id;
    $result = $dbCon->query($sql);

    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();

      if (crypt($old_password, '$6$Crypt$') == $row['password']) {
        $sql = "UPDATE userdetails SET password = '" . crypt($new_password, '$6$Crypt$') . "' WHERE id = '" . AuthController::user()->id . "'";

        if ($dbCon->query($sql)) {
          $hasSuccess = true;
          $message = "Password has been changed!";
        } else {
          $hasError = true;
          $message = "Failed to change password!";
        }
      }
    } else {
      $hasError = true;
      $message = "Old password is incorrect!";
    }
  }
}
?>

<!-- Navbar -->
<div class="navbar bg-base-100 flex justify-between items-center my-4">
  <div class="flex-none block md:hidden">
    <button class="btn btn-ghost" onclick="my_modal_2.showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>
  </div>

  <div class="flex justify-between items-center w-full">
    <div class="flex flex-col gap-1 items-start">
      <h1 class="text-[1em] md:text-[1.6em] font-bold"><?= AuthController::user()->firstName ?> <?= AuthController::user()->middleName ?> <?= AuthController::user()->lastName ?></h1>
      <h1 class="badge badge-error"><i class="bx bxs-shield"></i> Admin</h1>
    </div>

    <div class="flex-none gap-2">
      <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
          <div class="w-10 rounded-full">
            <img alt="Tailwind CSS Navbar component" src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.jpg" />
          </div>
        </div>
        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52 fixed z-50 ">
          <li><a onclick="change_password_modal.showModal()">Change Password</a></li>
          <li><a onclick="logout_modal.showModal()">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Change Password Modal -->
<dialog id="change_password_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-xl mb-4">Change Password</h3>

    <form class="flex flex-col gap-4  px-[8px] mb-auto" action="" method="post">
      <label class="flex flex-col gap-2" x-data="{show: true}">
        <span class="font-semibold text-base">Old Password</span>
        <div class="relative">
          <input class="input input-bordered w-full" name="old-password" placeholder="Old password" x-bind:type="show ? 'password' : 'text'" required />
          <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
            <i x-show="!show" class='bx bx-hide'></i>
            <i x-show="show" class='bx bx-show'></i>
          </button>
        </div>
      </label>

      <label class="flex flex-col gap-2" x-data="{show: true}">
        <span class="font-semibold text-base">New Password</span>
        <div class="relative">
          <input class="input input-bordered w-full" name="new-password" placeholder="New password" x-bind:type="show ? 'password' : 'text'" required />
          <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
            <i x-show="!show" class='bx bx-hide'></i>
            <i x-show="show" class='bx bx-show'></i>
          </button>
        </div>
      </label>

      <label class="flex flex-col gap-2" x-data="{show: true}">
        <span class="font-semibold text-base">Confirm Password</span>
        <div class="relative">
          <input class="input input-bordered w-full" name="confirm-password" placeholder="Confirm password" x-bind:type="show ? 'password' : 'text'" required />
          <button type="button" class="btn btn-ghost absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" @click="show = !show">
            <i x-show="!show" class='bx bx-hide'></i>
            <i x-show="show" class='bx bx-show'></i>
          </button>
        </div>
      </label>

      <div class="modal-action">
        <button class="btn btn-success" name="change-password">Change password</button>
        <button class="btn btn-error" onclick="change_password_modal.close()">Cancel</button>
      </div>
    </form>

  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<!-- Logout Modal -->
<dialog id="logout_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="modal-action">
      <a class="btn btn-error" href="<?= $rootFolder ?>/logout.php">Yes</a>
      <button class="btn" onclick="logout_modal.close()">No</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<!-- Navigation Menu Modal -->
<dialog id="my_modal_2" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Menu</h3>
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
              <li><a href="<?php echo $rootFolder; ?>/manage-course.php">Manage Course</a></li>
              <li><a href="<?php echo $rootFolder; ?>/manage-subjects.php">Manage Subjects</a></li>
              <li><a href="<?php echo $rootFolder; ?>/manage-sections.php">Manage Sections</a></li>
              <li><a href="<?php echo $rootFolder; ?>/manage-schoolyear.php">Manage School Year</a></li>
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
              <li><a href="<?php echo $rootFolder; ?>/manage-student.php">Manage Students</a></li>
              <!-- <li><a href="<?php echo $rootFolder; ?>/manage-promote-student">Promote Students</a></li> -->
              <li><a href="<?php echo $rootFolder; ?>/manage-instructor.php">Manage Instructor</a></li>
              <li><a href="<?php echo $rootFolder; ?>/manage-admin.php">Manage Admin</a></li>
            </ul>
          </details>
        </li>
        <li>
          <a href="<?php echo $rootFolder; ?>/manage-grade-release-requests.php">
            <i class='bx bxs-notepad text-[24px]'></i>
            <span class="text-[18px]">Requests for Grade Release</span>
          </a>
        </li>
        <!-- <li>
          <a href="<?php echo $rootFolder; ?>/grade-request.php">
            <i class='bx bxs-notepad text-[24px]'></i>
            <span class="text-[18px]">Student Grade Requests</span>
          </a>
        </li> -->
      </ul>
    </nav>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>