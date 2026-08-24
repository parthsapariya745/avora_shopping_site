<?php
require_once __DIR__ . "/includes/session.php";

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_phone']);

setFlashMessage("You have been signed out safely.", "info");
header("Location: login.php");
exit;
