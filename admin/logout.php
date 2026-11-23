<?php
require_once __DIR__ . '/../db_connect.php';

if (isset($_SESSION['admin_username'])) {
    logActivity($pdo, 'Admin', $_SESSION['admin_username'], 'Admin logged out');
}

unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_fullname']);
unset($_SESSION['admin_role']);

setFlash('info', 'Admin logged out successfully.');
header('Location: ../admin_login.php');
exit;
