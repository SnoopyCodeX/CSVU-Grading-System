<?php

$currentDir = dirname($_SERVER['PHP_SELF']);
$firstDir = explode("/", trim($currentDir, "/"));
header("location: " . str_repeat("../", count($firstDir) - 1) . "index.php");

?>