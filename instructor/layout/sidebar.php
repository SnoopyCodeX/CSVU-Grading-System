<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$baseFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/instructor/view";
$dashboard = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/instructor";
?>

<aside
    class="relative hidden  md:block w-[350px] border-r p-4 flex flex-col gap-4 justify-between sticky top-0 bg-[#1b7a58] text-white">
    <div class="h-full w-full ">
        <div class="flex justify-center items-center flex-col">
            <img src=<?php echo $baseFolder . "/assets/images/logo.png" ?> class='md:size-[64px] lg:size-[100px]' />
            <h1 class="text-[14px] text-center font-semibold p-4">Web-based Grading System</h1>
        </div>
        <nav class="my-4 h-full">
            <ul class="menu">
                <li>
                    <a href="<?php echo $dashboard; ?>/dashboard.php">
                        <i class='bx bxs-dashboard text-[24px]'></i>
                        <span class="text-[18px]">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-sections.php">
                        <i class='bx bxs-school text-[24px]'></i>
                        <span class="text-[18px]"> Sections</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-activity.php">
                        <i class='bx bx-math text-[24px]'></i>
                        <span class="text-[18px]"> Activities</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-grading-criteria.php">
                        <i class='bx bx-calculator text-[24px]'></i>
                        <span class="text-[18px]"> Creteria</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/manage-release-requests.php">
                        <i class='bx bx-briefcase-alt-2 text-[24px]'></i>
                        <span class="text-[18px]"> Requests</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/view-grades.php">
                        <i class='bx bxs-graduation text-[24px]'></i>
                        <span class="text-[18px]">View Grades</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>