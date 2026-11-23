<?php
$is_admin_page = true;
require_once __DIR__ . '/auth_check.php';

$page_title = "Election Results Management";
$settings = getElectionSettings($pdo);

// RESET ALL VOTES OPERATION (Wipe test ballots before live demo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_all_votes') {
    try {
        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM votes");
        $pdo->exec("UPDATE voters SET has_voted = 0, voted_at = NULL");
        logActivity($pdo, 'Admin', $_SESSION['admin_username'], "RESET ALL VOTES: All ballots cleared and voters reset.");
        $pdo->commit();

        setFlash('success', 'All election votes have been reset to zero. All voters can now vote again.');
        header('Location: results.php');
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Error resetting votes: ' . $e->getMessage());
    }
}

// 1. Overall Statistics
$total_voters = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$total_voted  = (int)$pdo->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1")->fetchColumn();
$turnout_rate = ($total_voters > 0) ? round(($total_voted / $total_voters) * 100, 1) : 0;

// 2. Fetch all positions & tallies using CRUD helper
$results_by_pos = get_election_results($pdo);
$positions = get_all_positions($pdo);

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
                        <li class="breadcrumb-item active">Results Management</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0 text-dark">Official Election Results &amp; Canvass</h2>
                <div class="text-muted small">Live analytics, candidate rankings, proclamation of winners, and audit tally.</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="print_results.php" target="_blank" class="btn btn-outline-dark shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> Print Canvass Certificate
                </a>

                <!-- Reset Votes Button -->
                <form method="POST" action="results.php" class="d-inline"
                      onsubmit="return confirm('DANGER: Are you sure you want to RESET ALL VOTES? This will wipe all recorded votes in the database and reset all voter statuses to Not Voted. Use this only to reset test data.');">
                    <input type="hidden" name="action" value="reset_all_votes">
                    <button type="submit" class="btn btn-outline-danger shadow-sm">
                        <i class="fa-solid fa-trash-can-arrow-up me-1"></i> Reset All Votes
                    </button>
                </form>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-primary">
                    <span class="text-muted small fw-semibold text-uppercase">Total Eligible Voters</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_voters); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-success">
                    <span class="text-muted small fw-semibold text-uppercase">Total Ballots Cast</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_voted); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-info">
                    <span class="text-muted small fw-semibold text-uppercase">Turnout Rate</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo $turnout_rate; ?>%</h3>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $turnout_rate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Positions Breakdown -->
        <?php foreach ($positions as $pos): 
            $pos_data = $results_by_pos[$pos['id']] ?? ['total_votes' => 0, 'max_votes' => 0, 'candidates' => []];
            $pos_total = $pos_data['total_votes'];
            $max_v = $pos_data['max_votes'];
            $candidates = $pos_data['candidates'];
        ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
                <div class="card-header bg-white p-3 p-md-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($pos['title']); ?></h4>
                        <div class="small text-muted">
                            Total Votes Cast: <strong><?php echo number_format($pos_total); ?></strong> &bull; Allowed: 
                            <span class="badge bg-light text-dark border"><?php echo $pos['max_votes']; ?> max choice(s)</span>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-secondary rounded-pill px-3 py-2">
                            Priority #<?php echo $pos['priority']; ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    <?php if (empty($candidates)): ?>
                        <div class="text-muted small fst-italic">No candidates registered for this position.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Rank</th>
                                        <th style="width: 300px;">Candidate</th>
                                        <th>Party Affiliation</th>
                                        <th style="width: 30%;">Vote Distribution</th>
                                        <th class="text-end" style="width: 140px;">Total Votes</th>
                                        <th class="text-center" style="width: 130px;">Standing</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($candidates as $cand): 
                                        $v_count = (int)$cand['vote_count'];
                                        $percentage = ($pos_total > 0) ? round(($v_count / $pos_total) * 100, 1) : 0;
                                        $cand_name = htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']);
                                        $is_winner = ($v_count > 0 && $v_count === $max_v);
                                    ?>
                                        <tr>
                                            <td class="text-center fw-bold">
                                                #<?php echo $rank; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="../assets/uploads/candidates/<?php echo htmlspecialchars($cand['photo'] ?: 'default_candidate.svg'); ?>" alt="<?php echo $cand_name; ?>" class="rounded-circle shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
                                                    <div>
                                                        <strong class="text-dark d-block"><?php echo $cand_name; ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($cand['party']); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="flex-grow-1 vote-progress-wrapper">
                                                        <div class="vote-progress-bar <?php echo $is_winner ? 'winner' : ''; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;"></div>
                                                    </div>
                                                    <span class="fw-bold small text-dark" style="min-width: 45px;"><?php echo $percentage; ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="fs-5 fw-bold text-dark"><?php echo number_format($v_count); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($is_winner): ?>
                                                    <span class="badge bg-success rounded-pill px-3 py-1">
                                                        <i class="fa-solid fa-crown me-1 text-warning"></i> Leading
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border px-2 py-1">Contender</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php 
                                        $rank++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
