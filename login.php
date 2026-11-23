<?php
require_once __DIR__ . '/db_connect.php';

// If already logged in as voter, check voting status
if (isset($_SESSION['voter_logged_in']) && $_SESSION['voter_logged_in'] === true) {
    if (isset($_SESSION['has_voted']) && $_SESSION['has_voted'] == 1) {
        setFlash('info', 'You have already voted in this election. You cannot vote again. Here is your official receipt.');
        header('Location: vote_success.php');
    } else {
        header('Location: ballot.php');
    }
    exit;
}

$page_title = "Voter Login";
$settings = getElectionSettings($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter both your Student Voter ID / Email and password.';
    } else {
        try {
            // Find voter using CRUD helper
            $voter = get_voter_by_student_id($pdo, $identifier);

            if ($voter && password_verify($password, $voter['password'])) {
                $_SESSION['voter_logged_in'] = true;
                $_SESSION['voter_id_pk']     = $voter['id'];
                $_SESSION['voter_id']        = $voter['voter_id'];
                $_SESSION['voter_name']      = $voter['first_name'] . ' ' . $voter['last_name'];
                $_SESSION['voter_email']     = $voter['email'];
                $_SESSION['voter_course']    = $voter['course'];
                $_SESSION['has_voted']       = (int)$voter['has_voted'];

                logActivity($pdo, 'Voter', $voter['voter_id'], 'Voter logged in successfully');

                // STRICT CHECK: Has this student already voted?
                if ((int)$voter['has_voted'] === 1) {
                    setFlash('warning', '<strong>Voting Completed:</strong> Welcome, ' . htmlspecialchars($voter['first_name']) . '! You have already cast your official vote. Each student is only allowed to vote once. You cannot vote again.');
                    header('Location: vote_success.php');
                } else {
                    setFlash('success', 'Welcome, ' . htmlspecialchars($voter['first_name']) . '! You may now cast your vote.');
                    header('Location: ballot.php');
                }
                exit;
            } else {
                $error = 'Invalid Student Voter ID or password. Please verify your credentials.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred during authentication. Please try again.';
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
                    <div class="card-header bg-primary text-white text-center py-4 border-0">
                        <i class="fa-solid fa-vote-yea fa-3x mb-2"></i>
                        <h4 class="fw-bold mb-0">Student Voter Login</h4>
                        <p class="small text-white-50 mb-0"><?php echo htmlspecialchars($settings['organization_name']); ?></p>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php" autocomplete="off">
                            <div class="mb-3">
                                <label for="identifier" class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-id-card text-primary me-1"></i> Student ID or Email
                                </label>
                                <input type="text" name="identifier" id="identifier" class="form-control form-control-lg" placeholder="e.g. 2026-00101" required autofocus value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>">
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold text-dark">
                                    <i class="fa-solid fa-lock text-primary me-1"></i> Password
                                </label>
                                <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Enter your password" required>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary-custom btn-lg fw-bold">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Login to Vote
                                </button>
                            </div>

                            <div class="text-center small">
                                Don't have a registered voter account? 
                                <a href="register.php" class="fw-bold text-decoration-none">Register Here</a>
                            </div>
                        </form>

                        <!-- Demo Credentials Box -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="alert alert-light border small text-muted mb-0">
                                <strong class="d-block text-dark mb-1"><i class="fa-solid fa-flask text-warning me-1"></i> Test Voter Credentials:</strong>
                                <div><strong>Student ID:</strong> <code>2026-00101</code></div>
                                <div><strong>Password:</strong> <code>voter123</code></div>
                                <div class="mt-1 text-secondary opacity-75">When done voting, this account cannot vote again.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
