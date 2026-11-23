<?php
/**
 * Admin Authentication Middleware
 */
require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    setFlash('warning', 'Access restricted. Please sign in as an Administrator.');
    header('Location: ../admin_login.php');
    exit;
}
