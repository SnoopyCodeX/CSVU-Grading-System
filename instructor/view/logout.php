<?php

$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));
$rootFolder = "//".$_SERVER['SERVER_NAME'] . "/".$FirstDir['0']."/public/login";

session_start();
unset($_SESSION['session']);
header("Location: {$rootFolder}");
exit();

