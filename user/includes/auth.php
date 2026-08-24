<?php
require_once __DIR__ . "/session.php";

if (!isUserLoggedIn()) {
    setFlashMessage("Please sign in to access this page.", "warning");
    header("Location: login.php");
    exit;
}
