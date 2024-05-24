<?php 
session_start();

require("../../../configuration/config.php");
require '../../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header('../manage-release-requests.php');
    exit;
}

if (!isset($_GET['uid'])) {
    header('../manage-release-requests.php');
    exit;
}

$fileUID = $dbCon->real_escape_string($_GET['uid']);
$fileDataQuery = $dbCon->query("SELECT * FROM instructor_grade_release_requests WHERE file_uid = '$fileUID'");

if ($fileDataQuery->num_rows > 0) {
    $fileData = $fileDataQuery->fetch_assoc();

    $currentDir = dirname($_SERVER['PHP_SELF']);
    $firstDir = explode("/", trim($currentDir, "/"));

    header("Content-type: application/pdf");
    readfile(str_repeat("../", count($firstDir) - 1) . "uploads/" . $fileData['grade_sheet_file']);
} else {
    header('../manage-release-requests.php');
    exit;
}

?>