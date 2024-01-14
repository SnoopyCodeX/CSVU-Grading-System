<?php

$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "grading-sys";


$dbCon = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if($dbCon->connect_error) {
    die ("Database is not connected");
}

$example = 'test';