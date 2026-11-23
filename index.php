<?php
require_once __DIR__ . '/db_connect.php';

$page_title = "Home";
$settings = getElectionSettings($pdo);

// Fetch system statistics using CRUD helpers
$total_voters = 0;
$votes_cast = 0;
$total_candidates = 0;
$total_positions = 0;

try {
    $total_voters = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
    $votes_cast = (int)$pdo->query("SELECT COUNT(DISTINCT voter_id) FROM votes")->fetchColumn();
    $total_candidates = (int)$pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
    $total_positions = (int)$pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn();
} catch (Exception $e) {}

$turnout_rate = ($total_voters > 0) ? round(($votes_cast / $total_voters) * 100, 1) : 0;
$voter_has_voted = isset($_SESSION['has_voted']) && $_SESSION['has_voted'] == 1;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-banner mb-5 text-center text-lg-start">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-white bg-opacity-25 rounded-pill mb-3">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span class="small fw-semibold"><?php echo htmlspecialchars($settings['organization_name']); ?></span>
                    </div>
                    <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($settings['election_title']); ?></h1>
                    <p class="lead opacity-90 mb-4">Academic Year <?php echo htmlspecialchars($settings['academic_year']); ?> &bull; Secure, Transparent & Fast Automated Voting</p>
                    
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <?php if ($settings['is_election_active']): ?>
                            <?php if (isset($_SESSION['voter_logged_in'])): ?>
                                <?php if ($voter_has_voted): ?>
                                    <a href="vote_success.php" class="btn btn-success btn-lg fw-bold shadow">
                                        <i class="fa-solid fa-receipt me-2"></i> View Your Voting Receipt
                                    </a>
                                <?php else: ?>
                                    <a href="ballot.php" class="btn btn-warning btn-lg fw-bold shadow">
                                        <i class="fa-solid fa-vote-yea me-2"></i> Cast Your Ballot Now
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-warning btn-lg fw-bold shadow">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Voter Login & Vote
                                </a>
                                <a href="register.php" class="btn btn-outline-light btn-lg fw-semibold">
                                    <i class="fa-solid fa-user-plus me-2"></i> Register as Voter
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg" disabled>
                                <i class="fa-solid fa-lock me-2"></i> Voting is Currently Closed
                            </button>
                        <?php endif; ?>
                        
                        <a href="results.php" class="btn btn-light btn-lg fw-semibold text-primary">
                            <i class="fa-solid fa-chart-pie me-2"></i> View Live Results
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 text-center d-none d-lg-block">
                    <div class="bg-white bg-opacity-10 p-4 rounded-4 backdrop-blur border border-white border-opacity-25">
                        <i class="fa-solid fa-check-to-slot fa-6x text-warning mb-3"></i>
                        <h4 class="text-white fw-bold mb-1">Your Vote Matters</h4>
                        <p class="small text-white-50 mb-0">Exercise your student democratic right securely with verified cryptographic ballot receipts.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Election Status Notice -->
        <div class="row mb-4">
            <div class="col-12">
                <?php if ($settings['is_election_active']): ?>
                    <div class="alert alert-success d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm border-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                            <span class="fw-semibold">ELECTION IS CURRENTLY ACTIVE & ACCEPTING VOTES</span>
                        </div>
                        <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-door-open me-1"></i> Polls Open</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm border-0">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation fs-5"></i>
                            <span class="fw-semibold">VOTING POLLS ARE CURRENTLY PAUSED OR CONCLUDED</span>
                        </div>
                        <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa-solid fa-lock me-1"></i> Polls Closed</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Key Stats Counters -->
        <div class="row g-3 mb-5">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Registered Voters</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_voters); ?></h3>
                        </div>
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle">
                            <i class="fa-solid fa-id-card-clip fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Ballots Cast</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($votes_cast); ?></h3>
                        </div>
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle">
                            <i class="fa-solid fa-envelope-circle-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Voter Turnout</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo $turnout_rate; ?>%</h3>
                        </div>
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle">
                            <i class="fa-solid fa-chart-line fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Active Candidates</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_candidates); ?></h3>
                        </div>
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle">
                            <i class="fa-solid fa-user-tie fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="card border-0 shadow-sm rounded-4 mb-5">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-5">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-2">Simple 4-Step Process</span>
                    <h2 class="fw-bold">How to Vote in the Online Election</h2>
                    <p class="text-muted">The system is designed to be accessible, completely confidential, and verifiable.</p>
                </div>

                <div class="row g-4 text-center">
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-user-lock fs-4"></i>
                            </div>
                            <h5 class="fw-bold">1. Authenticate</h5>
                            <p class="text-muted small">Login with your official Student ID and password. New students can register in seconds.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-list-check fs-4"></i>
                            </div>
                            <h5 class="fw-bold">2. Select Candidates</h5>
                            <p class="text-muted small">Review platforms and select your preferred candidates for each council position.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-paper-plane fs-4"></i>
                            </div>
                            <h5 class="fw-bold">3. Submit Ballot</h5>
                            <p class="text-muted small">Confirm your choices. Once submitted, your vote is recorded anonymously in the database.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-receipt fs-4"></i>
                            </div>
                            <h5 class="fw-bold">4. Digital Receipt</h5>
                            <p class="text-muted small">Receive an official verifiable electronic ballot receipt with a unique audit verification code.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Candidate Spotlight Preview -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Meet the Presidential Candidates</h3>
                <p class="text-muted small mb-0">Learn about their platforms and student welfare advocacies.</p>
            </div>
            <a href="candidates.php" class="btn btn-outline-primary btn-sm">
                View All Positions & Candidates <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4 mb-5">
            <?php
            try {
                $presidents = get_all_candidates($pdo, 1);
                foreach ($presidents as $cand):
                    $fullName = htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']);
            ?>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0 rounded-3 p-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="assets/uploads/candidates/<?php echo htmlspecialchars($cand['photo'] ?: 'default_candidate.svg'); ?>" alt="<?php echo $fullName; ?>" class="candidate-photo shadow-sm">
                            <div>
                                <span class="badge bg-primary mb-1"><?php echo htmlspecialchars($cand['position_title']); ?></span>
                                <h5 class="fw-bold mb-0 text-dark"><?php echo $fullName; ?></h5>
                                <div class="text-muted small mb-1"><i class="fa-solid fa-flag me-1"></i><?php echo htmlspecialchars($cand['party']); ?></div>
                            </div>
                        </div>
                        <hr class="my-2 text-muted">
                        <p class="small text-secondary mb-0">
                            <strong>Platform:</strong> "<?php echo htmlspecialchars($cand['platform']); ?>"
                        </p>
                    </div>
                </div>
            <?php 
                endforeach;
            } catch (Exception $e) {}
            ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
