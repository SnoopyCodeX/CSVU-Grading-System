 <?php
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $FirstDir = explode('/', trim($currentDir, '/'));
    $rootFolder = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/student/view";
?>

<aside class="w-[320px] border-r  p-4 flex flex-col gap-4 justify-between sticky top-0">
    <div class="h-full">
        <h1 class="text-[32px] font-bold">CSVU</h1>
        <nav class="my-4 h-full ">
            <ul class="menu">
                <li><a href="<?php echo $rootFolder;?>/dashboard">Dasboard</a></li>
            </ul>
        </nav>
    </div>
</aside>