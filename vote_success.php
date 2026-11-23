<?php
require_once __DIR__ . '/db_connect.php';

// Authentication Guard
if (!isset($_SESSION['voter_logged_in']) || $_SESSION['voter_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$voter_id_pk = $_SESSION['voter_id_pk'];
$settings = getElectionSettings($pdo);

// Fetch voter info via CRUD helper
$voter = get_voter_by_id($pdo, $voter_id_pk);

if (!$voter || $voter['has_voted'] != 1) {
    header('Location: ballot.php');
    exit;
}

// Fetch the votes cast by this voter
$votes_stmt = $pdo->prepare("SELECT v.*, p.title as position_title, c.first_name, c.last_name, c.party 
                             FROM votes v 
                             JOIN positions p ON v.position_id = p.id 
                             JOIN candidates c ON v.candidate_id = c.id 
                             WHERE v.voter_id = ? 
                             ORDER BY p.priority ASC, c.last_name ASC");
$votes_stmt->execute([$voter_id_pk]);
$my_votes = $votes_stmt->fetchAll();

$ballot_receipt = $my_votes[0]['ballot_receipt'] ?? ($_SESSION['last_receipt'] ?? 'VOTE-VERIFIED');
$voted_at = $voter['voted_at'] ? date('F d, Y - h:i:s A', strtotime($voter['voted_at'])) : date('F d, Y - h:i:s A');

$page_title = "Voting Receipt";

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Success Header Banner -->
                <div class="text-center mb-4 no-print">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center p-4 mb-3">
                        <i class="fa-solid fa-circle-check fa-4x"></i>
                    </div>
                    <h2 class="fw-bold text-dark">Thank You for Voting!</h2>
                    <p class="text-muted">Your vote has been officially cast and permanently logged into the election database.</p>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-pill text-dark small fw-semibold">
                        <i class="fa-solid fa-shield-halved text-warning"></i>
                        <span>Ballot Status: Final &amp; Locked &bull; You cannot vote again</span>
                    </div>
                </div>

                <!-- Printable Digital Ballot Receipt Card -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4 print-page">
                    <div class="card-header bg-dark text-white p-4 text-center border-bottom border-secondary">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <i class="fa-solid fa-check-to-slot text-warning fs-3"></i>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($settings['organization_name']); ?></h4>
                        </div>
                        <div class="text-white-50 small"><?php echo htmlspecialchars($settings['election_title']); ?></div>
                        <span class="badge bg-success mt-2 px-3 py-1 text-uppercase letter-spacing">Official Digital Ballot Receipt</span>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <div class="row g-3 p-3 bg-light rounded-3 mb-4">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Voter Name:</span>
                                <strong class="text-dark fs-6"><?php echo htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']); ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Student Voter ID:</span>
                                <strong class="text-dark fs-6 font-monospace"><?php echo htmlspecialchars($voter['voter_id']); ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Program &amp; Year:</span>
                                <span class="text-dark"><?php echo htmlspecialchars($voter['course'] . ' - ' . $voter['year_level']); ?></span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Timestamp of Cast Vote:</span>
                                <span class="text-dark"><?php echo $voted_at; ?></span>
                            </div>
                            <div class="col-12 pt-2 border-top">
                                <span class="text-muted small d-block">Audit Verification Code:</span>
                                <span class="font-monospace text-primary fw-bold fs-6"><?php echo htmlspecialchars($ballot_receipt); ?></span>
                            </div>
                        </div>

                        <!-- Summary of Voted Candidates -->
                        <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i>Ballot Selection Summary</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 40%;">Position</th>
                                        <th scope="col">Candidate Selected</th>
                                        <th scope="col">Party Affiliation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($my_votes)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No candidate record found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($my_votes as $mv): ?>
                                            <tr>
                                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($mv['position_title']); ?></td>
                                                <td>
                                                    <i class="fa-solid fa-user-check text-success me-1"></i>
                                                    <strong><?php echo htmlspecialchars($mv['first_name'] . ' ' . $mv['last_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($mv['party']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-top pt-3 text-center text-muted small">
                            <i class="fa-solid fa-lock text-success me-1"></i> This document confirms your vote has been locked and permanently recorded. In accordance with university COMELEC rules, re-voting is strictly disallowed.
                        </div>
                    </div>
                </div>

                <!-- Action Controls (Hidden on Print) -->
                <div class="d-flex flex-wrap gap-2 justify-content-center no-print mb-5">
                    <button onclick="window.print();" class="btn btn-outline-dark px-4 fw-semibold">
                        <i class="fa-solid fa-print me-1"></i> Print / Save Receipt
                    </button>
                    <a href="results.php" class="btn btn-primary px-4 fw-semibold">
                        <i class="fa-solid fa-chart-column me-1"></i> View Live Results
                    </a>
                    <a href="logout.php" class="btn btn-danger px-4 fw-semibold">
                        <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
