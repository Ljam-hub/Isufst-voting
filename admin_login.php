<?php
require_once __DIR__ . '/db_connect.php';

// If already logged in as admin, redirect to admin dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin/index.php');
    exit;
}

$page_title = "Admin Login";
$settings = getElectionSettings($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $admin['id'];
                $_SESSION['admin_username']  = $admin['username'];
                $_SESSION['admin_fullname']  = $admin['fullname'];
                $_SESSION['admin_role']      = $admin['role'];

                logActivity($pdo, 'Admin', $admin['username'], 'Administrator logged in to COMELEC panel');

                setFlash('success', 'Welcome, ' . htmlspecialchars($admin['fullname']) . '! Successfully signed in to the COMELEC Admin Panel.');
                header('Location: admin/index.php');
                exit;
            } else {
                $error = 'Invalid admin username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<main class="py-5 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-dark text-white text-center py-4 border-0">
                        <i class="fa-solid fa-shield-halved fa-3x mb-2 text-warning"></i>
                        <h4 class="fw-bold mb-0">COMELEC Admin Portal</h4>
                        <p class="small text-white-50 mb-0"><?php echo htmlspecialchars($settings['organization_name']); ?></p>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="admin_login.php" autocomplete="off">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-user-gear text-primary me-1"></i> Admin Username
                                </label>
                                <input type="text" name="username" id="username" class="form-control form-control-lg" placeholder="e.g. admin" required autofocus value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-key text-primary me-1"></i> Admin Password
                                </label>
                                <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Enter admin password" required>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-dark btn-lg fw-bold shadow-sm">
                                    <i class="fa-solid fa-right-to-bracket me-2 text-warning"></i> Access Dashboard
                                </button>
                            </div>
                        </form>

                        <!-- Test Credentials -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="alert alert-warning border small text-dark mb-0">
                                <strong class="d-block mb-1"><i class="fa-solid fa-flask text-dark me-1"></i> Default Administrator Credentials:</strong>
                                <div><strong>Username:</strong> <code>admin</code></div>
                                <div><strong>Password:</strong> <code>admin123</code></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
