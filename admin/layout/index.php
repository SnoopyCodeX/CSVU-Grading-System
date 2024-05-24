<?php
session_start();

require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    $session = $_SESSION['session'];
    header("Location: ../../public/login");
    exit();
}

header("Location: ../dashboard");
exit();
?>