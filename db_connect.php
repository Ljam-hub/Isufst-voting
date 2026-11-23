<?php
/**
 * Database Connection & Global Configuration
 * Online Voting System (Supports Localhost XAMPP & InfinityFree)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

// Auto-detect environment: Local XAMPP vs. Live Host (InfinityFree)
$is_local = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1', '::1']) 
            || (php_sapi_name() === 'cli');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

if ($is_local) {
    // -------------------------------------------------------------
    // LOCALHOST XAMPP SETTINGS
    // -------------------------------------------------------------
    $db_host = 'localhost';
    $db_name = 'online_voting_db';
    $db_user = 'root';
    $db_pass = '';

    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        // Fallback for port 3307 or password '1234'
        try {
            $pdo = new PDO("mysql:host={$db_host};port=3307;dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options);
        } catch (PDOException $e2) {
            try {
                $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, '1234', $options);
            } catch (PDOException $e3) {
                die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;'>
                    <h3 style='margin-top:0;'>Database Connection Failed (Localhost)</h3>
                    <p>Could not connect to database <strong>{$db_name}</strong> on <strong>{$db_host}</strong>.</p>
                    <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                    <hr>
                    <p><strong>Troubleshooting:</strong></p>
                    <ol>
                        <li>Make sure <strong>Apache</strong> and <strong>MySQL</strong> are started in XAMPP.</li>
                        <li>Make sure you imported <code>database.sql</code> in phpMyAdmin.</li>
                    </ol>
                </div>");
            }
        }
    }
} else {
    // -------------------------------------------------------------
    // INFINITYFREE HOSTING SETTINGS
    // (Check your InfinityFree vPanel -> MySQL Databases for these details)
    // -------------------------------------------------------------
    $db_host = 'sql311.infinityfree.com';
    $db_user = 'if0_43386545';
    $db_pass = 'MThBapGnR8';
    $db_name = 'if0_43386545_voting';

    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;'>
            <h3 style='margin-top:0;'>Database Connection Failed (InfinityFree)</h3>
            <p>Could not connect to database <strong>{$db_name}</strong> on <strong>{$db_host}</strong>.</p>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr>
            <p>Please double check your MySQL credentials in <code>db_connect.php</code> from your InfinityFree control panel.</p>
        </div>");
    }
}

// Load CRUD functions library
require_once __DIR__ . '/includes/crud_functions.php';

// Global Helper Functions
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function getElectionSettings($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
        return $stmt->fetch() ?: [
            'election_title'     => 'Student Council Elections',
            'academic_year'      => '2026-2027',
            'organization_name'  => 'COMELEC',
            'is_election_active' => 1
        ];
    } catch (Exception $e) {
        return [
            'election_title'     => 'Student Council Elections',
            'academic_year'      => '2026-2027',
            'organization_name'  => 'COMELEC',
            'is_election_active' => 1
        ];
    }
}

function logActivity($pdo, $user_type, $identifier, $action) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_type, user_identifier, action, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_type, $identifier, $action, $ip]);
    } catch (Exception $e) {}
}

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
