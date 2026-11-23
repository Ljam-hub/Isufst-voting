<?php
require_once __DIR__ . '/db_connect.php';

// If already logged in, redirect
if (isset($_SESSION['voter_logged_in']) && $_SESSION['voter_logged_in'] === true) {
    header('Location: ballot.php');
    exit;
}

$page_title = "Voter Registration";
$settings = getElectionSettings($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voter_id   = strtoupper(trim($_POST['voter_id'] ?? ''));
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $course     = trim($_POST['course'] ?? 'BSIT');
    $year_level = trim($_POST['year_level'] ?? '1st Year');
    $password   = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($voter_id) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check for duplicate voter_id or email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM voters WHERE voter_id = ? OR email = ?");
            $stmt->execute([$voter_id, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'A voter with this Student ID or Email is already registered.';
            } else {
                // Call CRUD INSERT Function
                $new_id = create_voter($pdo, $voter_id, $first_name, $last_name, $email, $password, $course, $year_level);

                if ($new_id) {
                    logActivity($pdo, 'Voter', $voter_id, "Registered new voter account ($first_name $last_name)");
                    setFlash('success', "Registration successful! You may now login using your Student ID ($voter_id).");
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'Failed to insert voter record.';
                }
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

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-primary text-white text-center py-4 border-0">
                        <i class="fa-solid fa-address-card fa-3x mb-2"></i>
                        <h4 class="fw-bold mb-0">Student Voter Registration</h4>
                        <p class="small text-white-50 mb-0"><?php echo htmlspecialchars($settings['organization_name']); ?></p>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="register.php" autocomplete="off">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="e.g. Juan" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" id="last_name" class="form-control" placeholder="e.g. Dela Cruz" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="voter_id" class="form-label fw-semibold">Student ID / Voter ID <span class="text-danger">*</span></label>
                                    <input type="text" name="voter_id" id="voter_id" class="form-control" placeholder="e.g. 2026-00109" required value="<?php echo htmlspecialchars($_POST['voter_id'] ?? ''); ?>">
                                    <div class="form-text">Your official university student registration number.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="e.g. juan@isufst.edu.ph" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="course" class="form-label fw-semibold">Course / Degree Program <span class="text-danger">*</span></label>
                                    <select name="course" id="course" class="form-select" required>
                                        <option value="BSIT" <?php echo (($_POST['course'] ?? '') === 'BSIT') ? 'selected' : ''; ?>>BS in Information Technology (BSIT)</option>
                                        <option value="BSCS" <?php echo (($_POST['course'] ?? '') === 'BSCS') ? 'selected' : ''; ?>>BS in Computer Science (BSCS)</option>
                                        <option value="BSIS" <?php echo (($_POST['course'] ?? '') === 'BSIS') ? 'selected' : ''; ?>>BS in Information Systems (BSIS)</option>
                                        <option value="BSED" <?php echo (($_POST['course'] ?? '') === 'BSED') ? 'selected' : ''; ?>>Bachelor of Secondary Education (BSED)</option>
                                        <option value="BEED" <?php echo (($_POST['course'] ?? '') === 'BEED') ? 'selected' : ''; ?>>Bachelor of Elementary Education (BEED)</option>
                                        <option value="BSBA" <?php echo (($_POST['course'] ?? '') === 'BSBA') ? 'selected' : ''; ?>>BS in Business Administration (BSBA)</option>
                                        <option value="BSHM" <?php echo (($_POST['course'] ?? '') === 'BSHM') ? 'selected' : ''; ?>>BS in Hospitality Management (BSHM)</option>
                                        <option value="BSFish" <?php echo (($_POST['course'] ?? '') === 'BSFish') ? 'selected' : ''; ?>>BS in Fisheries (BSFish)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="year_level" class="form-label fw-semibold">Year Level <span class="text-danger">*</span></label>
                                    <select name="year_level" id="year_level" class="form-select" required>
                                        <option value="1st Year" <?php echo (($_POST['year_level'] ?? '') === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo (($_POST['year_level'] ?? '') === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo (($_POST['year_level'] ?? '') === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo (($_POST['year_level'] ?? '') === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-semibold">Create Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-type password" required>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary-custom btn-lg fw-bold">
                                    <i class="fa-solid fa-user-plus me-2"></i> Register Voter Account
                                </button>
                            </div>

                            <div class="text-center small">
                                Already registered? 
                                <a href="login.php" class="fw-bold text-decoration-none">Login to Vote</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
