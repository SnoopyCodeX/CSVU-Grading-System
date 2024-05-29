<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$baseFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'];
$rootFolder = "//" . $_SERVER['SERVER_NAME'] . "/" . $FirstDir['0'] . "/student";
?>

<aside
    class="w-[320px] hidden md:block border-r  p-4 flex flex-col gap-4 justify-between sticky top-0 bg-[#1b7a58] text-white">
    <div class="h-full">
        <div class="flex justify-center items-center flex-col">
            <img src=<?php echo $baseFolder . "/assets/images/logo.png" ?> class='w-[120px] h-[120px]' />
            <h1 class="text-[14px] text-center font-semibold p-4">Cavite State University - General Trias City, Campus
                (CvSU)</h1>
        </div>
        <nav class="my-4 h-full ">
            <ul class="menu">
                <li>
                    <a href="<?php echo $rootFolder; ?>/dashboard.php">
                        <i class='bx bxs-dashboard text-[24px]'></i>
                        <span class="text-[18px]">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/view/enrolled-subjects.php">
                        <i class='bx bxs-book text-[24px]'></i>
                        <span class="text-[18px]">Enrolled Subjects</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $rootFolder; ?>/view/grades.php">
                        <i class='bx bxs-graduation text-[24px]'></i>
                        <span class="text-[18px]">My Grades</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>