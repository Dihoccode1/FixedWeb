<?php
require_once __DIR__ . '/../includes/common.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin'])) {
    unset($_SESSION['admin']);
}

redirect(app_url('Admin/login.php'));
