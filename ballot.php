<?php
require_once __DIR__ . '/db_connect.php';

// Authentication Guard: Must be logged in as voter
if (!isset($_SESSION['voter_logged_in']) || $_SESSION['voter_logged_in'] !== true) {
    setFlash('warning', 'Please login to access the official voting ballot.');
    header('Location: login.php');
    exit;
}

$voter_id_pk = $_SESSION['voter_id_pk'];

// Re-verify voter status directly from DB via CRUD helper
$voter = get_voter_by_id($pdo, $voter_id_pk);

if (!$voter) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// -------------------------------------------------------------
// STRICT RULE: Once voted, you cannot vote again!
// -------------------------------------------------------------
if ($voter['has_voted'] == 1) {
    $_SESSION['has_voted'] = 1;
    setFlash('warning', '<strong>Voting Completed:</strong> You have already cast your official ballot for this election. Each student is permitted to vote only once. You cannot vote again.');
    header('Location: vote_success.php');
    exit;
}

$settings = getElectionSettings($pdo);
if (!$settings['is_election_active']) {
    setFlash('danger', 'Voting is currently closed or suspended by the COMELEC Administrator.');
    header('Location: index.php');
    exit;
}

$page_title = "Official Voting Ballot";

// Retrieve positions and candidates using CRUD functions
$positions = get_all_positions($pdo);
$all_candidates = get_all_candidates($pdo);

$candidates_by_pos = [];
foreach ($all_candidates as $cand) {
    $candidates_by_pos[$cand['position_id']][] = $cand;
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Voter Info Banner -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-body p-3 p-md-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                        <i class="fa-solid fa-user-graduate fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']); ?></h4>
                        <div class="text-muted small">
                            <span class="me-2"><i class="fa-solid fa-id-badge me-1"></i> ID: <strong><?php echo htmlspecialchars($voter['voter_id']); ?></strong></span>
                            <span class="me-2">&bull;</span>
                            <span class="me-2"><i class="fa-solid fa-book-open me-1"></i> <?php echo htmlspecialchars($voter['course'] . ' - ' . $voter['year_level']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="text-md-end">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-circle-check me-1"></i> Eligible to Vote (1 Vote Allowed)
                    </span>
                </div>
            </div>
        </div>

        <!-- Instructions Alert -->
        <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-start gap-3 mb-4">
            <i class="fa-solid fa-circle-info fs-4 mt-1 text-info"></i>
            <div>
                <strong>Important Voting Rules &amp; Instructions:</strong>
                <ul class="mb-0 ps-3 mt-1 small">
                    <li><strong>One Vote Per Student:</strong> When done voting, you cannot vote again. Your ballot is final and binding.</li>
                    <li>Select your candidate(s) by clicking on their card or radio/checkbox.</li>
                    <li>For single-slot positions (e.g. President), you can select only <strong>1</strong> candidate.</li>
                    <li>For multi-seat positions (e.g. College Representative), you may select up to the maximum specified limit.</li>
                    <li>After submitting, you will receive an official verifiable digital ballot receipt for your records.</li>
                </ul>
            </div>
        </div>

        <!-- Voting Form -->
        <form id="ballotForm" method="POST" action="vote_submit.php">
            <?php foreach ($positions as $pos): 
                $pos_id = $pos['id'];
                $max_votes = (int)$pos['max_votes'];
                $cands = $candidates_by_pos[$pos_id] ?? [];
            ?>
                <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                    <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0 text-white"><?php echo htmlspecialchars($pos['title']); ?></h5>
                            <?php if (!empty($pos['description'])): ?>
                                <small class="text-white-50"><?php echo htmlspecialchars($pos['description']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                <?php echo $max_votes === 1 ? "Choose 1 Candidate" : "Choose up to {$max_votes} Candidates"; ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-3 p-md-4 bg-light">
                        <?php if (empty($cands)): ?>
                            <div class="text-muted fst-italic">No candidates registered for this position.</div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($cands as $cand): 
                                    $cand_id = $cand['id'];
                                    $cand_name = htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']);
                                ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="candidate-card ballot-candidate-card p-3 d-flex flex-column justify-content-between h-100">
                                            <div>
                                                <div class="d-flex align-items-start gap-3 mb-2">
                                                    <div class="form-check fs-5 mt-1">
                                                        <?php if ($max_votes === 1): ?>
                                                            <input class="form-check-input" type="radio" 
                                                                   name="votes[<?php echo $pos_id; ?>]" 
                                                                   id="cand_<?php echo $cand_id; ?>" 
                                                                   value="<?php echo $cand_id; ?>">
                                                        <?php else: ?>
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="votes[<?php echo $pos_id; ?>][]" 
                                                                   id="cand_<?php echo $cand_id; ?>" 
                                                                   value="<?php echo $cand_id; ?>"
                                                                   data-max-votes="<?php echo $max_votes; ?>">
                                                        <?php endif; ?>
                                                    </div>
                                                    <img src="assets/uploads/candidates/<?php echo htmlspecialchars($cand['photo'] ?: 'default_candidate.svg'); ?>" alt="<?php echo $cand_name; ?>" class="candidate-photo shadow-sm" style="width:65px; height:65px;">
                                                    <div>
                                                        <label class="form-check-label fw-bold text-dark fs-6 d-block mb-1" for="cand_<?php echo $cand_id; ?>">
                                                            <?php echo $cand_name; ?>
                                                        </label>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary small">
                                                            <?php echo htmlspecialchars($cand['party']); ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <?php if (!empty($cand['platform'])): ?>
                                                    <div class="mt-2 pt-2 border-top small text-muted">
                                                        <strong>Platform:</strong> "<?php echo htmlspecialchars(mb_strimwidth($cand['platform'], 0, 110, '...')); ?>"
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Action Buttons -->
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white text-center mb-5">
                <h5 class="fw-bold mb-2">Ready to Submit Your Official Ballot?</h5>
                <p class="text-danger small mb-3 fw-semibold">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Reminder: Once you submit your ballot, you cannot vote again. Please confirm your selections.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="index.php" class="btn btn-outline-secondary px-4">
                        <i class="fa-solid fa-arrow-left me-1"></i> Cancel / Go Back
                    </a>
                    <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow">
                        <i class="fa-solid fa-paper-plane me-2"></i> Submit Official Ballot
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
