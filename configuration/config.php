<?php
$currentDir = dirname($_SERVER['PHP_SELF']);
$FirstDir = explode('/', trim($currentDir, '/'));

require_once (str_repeat("../", count($FirstDir) - 1) . "configuration/constants.php");

$dbCon = new mysqli(
    constant('DB_HOST'), 
    constant('DB_USER'), 
    constant('DB_PASS'), 
    constant('DB_NAME')
);

if($dbCon->connect_error) {
    die ("Database is not connected");
}
?>