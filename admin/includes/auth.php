<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loginRedirect = isset($basePath) ? $basePath . "login.php" : "login.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . $loginRedirect);
    exit;
}
