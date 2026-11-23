<?php
$is_admin_page = true;
require_once __DIR__ . '/auth_check.php';

$page_title = "Election Settings";
$settings = getElectionSettings($pdo);

// UPDATE ELECTION CONFIGURATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $election_title     = trim($_POST['election_title'] ?? '');
    $academic_year      = trim($_POST['academic_year'] ?? '');
    $organization_name  = trim($_POST['organization_name'] ?? '');
    $is_election_active = isset($_POST['is_election_active']) ? 1 : 0;

    if (empty($election_title) || empty($academic_year)) {
        setFlash('danger', 'Election title and academic year cannot be empty.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE settings SET election_title = ?, academic_year = ?, organization_name = ?, is_election_active = ? WHERE id = 1");
            $stmt->execute([$election_title, $academic_year, $organization_name, $is_election_active]);

            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Updated election general settings");
            setFlash('success', 'Election settings updated successfully.');
            header('Location: settings.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error updating settings: ' . $e->getMessage());
        }
    }
}

// UPDATE ADMIN PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass)) {
        setFlash('danger', 'Please fill in all password fields.');
    } elseif ($new_pass !== $confirm_pass) {
        setFlash('danger', 'New passwords do not match.');
    } elseif (strlen($new_pass) < 6) {
        setFlash('danger', 'New password must be at least 6 characters.');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $current_hash = $stmt->fetchColumn();

            if (password_verify($current_pass, $current_hash)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $upd->execute([$new_hash, $_SESSION['admin_id']]);

                logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Changed administrator password");
                setFlash('success', 'Admin password changed successfully!');
                header('Location: settings.php');
                exit;
            } else {
                setFlash('danger', 'Current password is incorrect.');
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error changing password: ' . $e->getMessage());
        }
    }
}

$settings = getElectionSettings($pdo);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0 text-dark">Election Configuration &amp; Security</h2>
                <div class="text-muted small">Configure election parameters, voting schedules, and administrative security credentials.</div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Election Parameters Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white p-4 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-sliders text-primary me-2"></i>Election Parameters
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_settings">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Election Title <span class="text-danger">*</span></label>
                                <input type="text" name="election_title" class="form-control form-control-lg" required value="<?php echo htmlspecialchars($settings['election_title']); ?>">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                                    <input type="text" name="academic_year" class="form-control" required value="<?php echo htmlspecialchars($settings['academic_year']); ?>" placeholder="e.g. 2026-2027">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Commission / Organization Name</label>
                                    <input type="text" name="organization_name" class="form-control" value="<?php echo htmlspecialchars($settings['organization_name']); ?>">
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 mb-4 border">
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_election_active" id="electionToggle" <?php echo $settings['is_election_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold text-dark fs-6" for="electionToggle">
                                        Polls Are Open &amp; Accepting Ballots
                                    </label>
                                </div>
                                <div class="small text-muted mt-1 ps-5">
                                    When turned OFF, voters will not be able to cast new ballots. Existing votes remain safely preserved.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary fw-semibold px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Election Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Admin Password Security Form -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white p-4 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-key text-warning me-2"></i>Change Admin Password
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="update_password">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-dark fw-semibold w-100">
                                <i class="fa-solid fa-lock me-1"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
