<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/instructor/view";
?>

<div class="navbar bg-base-100 flex justify-between items-center my-4">
  <div class="flex-none block md:invisible">
      <button class="btn btn-ghost" onclick="my_modal_2.showModal()">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>
  </div>

  <div class="flex-none gap-2">
   
    <div class="dropdown dropdown-end">
      <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
          <div class="w-10 rounded-full">
          <img alt="Tailwind CSS Navbar component" src="https://daisyui.com/images/stock/photo-1534528741775-53994a69daeb.jpg" />
          </div>
      </div>
      <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52 fixed z-50 ">
        <li><a onclick="logout_modal.showModal()">Logout</a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Logout Modal -->
<dialog id="logout_modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="modal-action">
      <a class="btn btn-error" href="<?= $rootFolder ?>/logout">Yes</a>
      <button class="btn" onclick="logout_modal.close()">No</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<!-- Navigation Modal -->
<dialog id="my_modal_2" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Navigation!</h3>
    <nav class="my-4 h-full ">
      <ul class="menu">
        <li><a href="<?php echo $dashboard;?>/dashboard">Dashboard</a></li>
        <li><a href="<?php echo $rootFolder;?>/manage-sections">Manage Sections</a></li>
        <li><a href="<?php echo $rootFolder;?>/manage-activity">Manage Activities</a></li>
        <li><a href="<?php echo $rootFolder;?>/view-grades">View Grades</a></li>
      </ul>
    </nav>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>