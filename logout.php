<?php
require_once __DIR__ . '/config/auth.php';

logout_user();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['flash_success'] = "تم تسجيل الخروج بنجاح.";
header("Location: login.php");
exit();
