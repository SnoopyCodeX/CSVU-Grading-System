<?php 
session_start();

require("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header('../manage-change-grade-requests.php');
    exit;
}

if (!isset($_GET['uid'])) {
    header('../manage-change-grade-requests.php');
    exit;
}

$fileUID = $dbCon->real_escape_string($_GET['uid']);
$fileDataQuery = $dbCon->query("SELECT * FROM instructor_change_grade_request WHERE token = '$fileUID'");

if ($fileDataQuery->num_rows > 0) {
    $fileData = $fileDataQuery->fetch_assoc();

    $currentDir = dirname($_SERVER['PHP_SELF']);
    $firstDir = explode("/", trim($currentDir, "/"));

    header("Content-type: application/pdf");
    readfile(str_repeat("../", count($firstDir) - 1) . "uploads/change_of_grade_request/" . $fileData['pdf_file']);
} else {
    header('../manage-change-grade-requests.php');
    exit;
}

?>