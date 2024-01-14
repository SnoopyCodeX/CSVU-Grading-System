<?php
session_start();

require '../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    $session = $_SESSION['session'];
    header("Location: ../../public/login");
    echo "<script>alert('Session is time out!')</script>";
    exit();
}

header("Location: ./dashboard/");
exit();