<?php
require_once __DIR__ . '/db_connect.php';

if (isset($_SESSION['voter_id'])) {
    logActivity($pdo, 'Voter', $_SESSION['voter_id'], 'Voter logged out');
}

unset($_SESSION['voter_logged_in']);
unset($_SESSION['voter_id_pk']);
unset($_SESSION['voter_id']);
unset($_SESSION['voter_name']);
unset($_SESSION['voter_email']);
unset($_SESSION['voter_course']);
unset($_SESSION['has_voted']);
unset($_SESSION['last_receipt']);

setFlash('info', 'You have been successfully logged out of your voting session.');
header('Location: login.php');
exit;
