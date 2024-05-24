<?php
session_start();

require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    $session = $_SESSION['session'];
    header("Location: ../../public/login.php");
    exit();
}

header("Location: ../dashboard.php");
exit();
