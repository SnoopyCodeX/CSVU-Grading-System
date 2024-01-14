 <?php
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $FirstDir = explode('/', trim($currentDir, '/'));
    $rootFolder = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/admin/views";
?>

<aside class="w-[320px] border-r  p-4 flex flex-col gap-4 justify-between sticky top-0">
    <div class="h-full">
        <h1 class="text-[32px] font-bold">CSVU</h1>
        <nav class="my-4 h-full ">
            <ul class="menu">
                <li><a href="../dashboard" >Dashboard</a></li>
                <li>
                    <details>
                        <summary>Department</summary>
                        <ul>
                            <li><a href="<?php echo $rootFolder;?>/manage-course">Manage Course</a></li>
                            <li><a href="<?php echo $rootFolder;?>/manage-subjects">Manage Subjects</a></li>
                            <li><a href="<?php echo $rootFolder;?>/manage-sections">Manage Sections</a></li>
                            <li><a href="<?php echo $rootFolder;?>/manage-schoolyear">Manage School Year</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary>Users</summary>
                        <ul>
                            <li><a href="<?php echo $rootFolder;?>/manage-student">Manage Students</a></li>
                            <li><a href="<?php echo $rootFolder;?>/manage-promote-student">Promote Students</a></li>
                            <li><a href="<?php echo $rootFolder;?>/manage-instructor">Manage Instructor</a></li>
                            <li><a href="<?php echo $rootFolder;?>/manage-admin">Manage Admin</a></li>
                        </ul>
                    </details>
                </li>
                <li><a href="<?php echo $rootFolder;?>/grade-request">Requests</a></li>
            </ul>
        </nav>
    </div>
</aside>