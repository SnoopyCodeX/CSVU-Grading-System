 <?php
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $FirstDir = explode('/', trim($currentDir, '/'));
    $baseFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];
    $rootFolder = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/instructor/view";
    $dashboard = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/instructor";
?>

<aside class="h-[100vh] w-[320px] hidden md:block border-r p-4 flex flex-col gap-4 justify-between sticky top-0  bg-[#405D47] text-white">
    <div class="h-full ">
        <div class="flex justify-center items-center flex-col">
            <img src=<?php echo $baseFolder."/assets/images/logo.png"  ?> class='w-[120px] h-[120px]' />
            <h1 class="text-[14px] text-center font-semibold p-4">Cavite State University - General Trias City, Campus (CvSU)</h1>
        </div>
        <nav class="my-4 h-full">
            <ul class="menu">
                <li>
                    <a href="<?php echo $dashboard;?>/dashboard.php">
                        <i class='bx bxs-dashboard text-[24px]'></i>
                        <span class="text-[18px]">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder;?>/manage-sections.php">
                        <i class='bx bxs-school text-[24px]'></i>
                        <span class="text-[18px]">Manage Sections</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder;?>/manage-activity.php">
                        <i class='bx bx-math text-[24px]'></i>
                        <span class="text-[18px]">Manage Activities</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder;?>/manage-grading-criteria.php">
                        <i class='bx bx-calculator text-[24px]'></i>
                        <span class="text-[18px]">Manage Grading Criterias</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder;?>/manage-release-requests.php">
                        <i class='bx bx-briefcase-alt-2 text-[24px]'></i>
                        <span class="text-[18px]">Manage Release Requests</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder;?>/view-grades.php">   
                        <i class='bx bxs-graduation text-[24px]'></i> 
                        <span class="text-[18px]">View Grades</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>