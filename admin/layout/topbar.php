<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/admin/views";
?>

<div class="navbar bg-base-100 flex justify-between items-center my-4">
  <div class="flex-1">
  </div>

  <div class="flex-none gap-2">
   
    <div class="dropdown dropdown-end">
      <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
          <div class="w-10 rounded-full">
          <img alt="Tailwind CSS Navbar component" src="https://daisyui.com/images/stock/photo-1534528741775-53994a69daeb.jpg" />
          </div>
      </div>
      <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52 fixed z-50 ">
          <li><a href="<?= $rootFolder ?>/logout">Logout</a></li>
      </ul>
    </div>
  </div>
</div>