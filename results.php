<?php
require_once __DIR__ . '/db_connect.php';

$page_title = "Live Election Results";
$settings = getElectionSettings($pdo);

// 1. Overall Statistics
$total_voters = 0;
$total_voted = 0;
try {
    $total_voters = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
    $total_voted = (int)$pdo->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1")->fetchColumn();
} catch (Exception $e) {}

$turnout_rate = ($total_voters > 0) ? round(($total_voted / $total_voters) * 100, 1) : 0;

// 2. Fetch full election results using CRUD helper function
$results_by_pos = get_election_results($pdo);
$positions = get_all_positions($pdo);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Results Header Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <span class="badge bg-danger rounded-pill px-3 py-1 mb-1">
                    <span class="spinner-grow spinner-grow-sm me-1 text-light"></span> Live Tally
                </span>
                <h2 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($settings['election_title']); ?></h2>
                <div class="text-muted small">Real-time election tally and candidate standing updates.</div>
            </div>

            <div class="d-flex gap-2">
                <button onclick="location.reload();" class="btn btn-outline-primary shadow-sm">
                    <i class="fa-solid fa-rotate me-1"></i> Refresh Tally
                </button>
                <a href="admin/print_results.php" target="_blank" class="btn btn-dark shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> Print Summary Report
                </a>
            </div>
        </div>

        <!-- Turnout KPI Cards -->
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
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-info">
                    <span class="text-muted small fw-semibold text-uppercase">Overall Voter Turnout</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo $turnout_rate; ?>%</h3>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $turnout_rate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Position-by-Position Live Results -->
        <?php foreach ($positions as $pos): 
            $pos_data = $results_by_pos[$pos['id']] ?? ['total_votes' => 0, 'max_votes' => 0, 'candidates' => []];
            $pos_total = $pos_data['total_votes'];
            $max_v = $pos_data['max_votes'];
            $candidates = $pos_data['candidates'];
        ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white p-3 p-md-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($pos['title']); ?></h4>
                        <div class="small text-muted">
                            Total Votes Cast: <strong><?php echo number_format($pos_total); ?></strong>
                            &bull; Limit: <?php echo $pos['max_votes'] > 1 ? "Up to {$pos['max_votes']} seats" : "1 seat"; ?>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold">
                            <?php echo count($candidates); ?> Candidates Running
                        </span>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4 bg-white">
                    <?php if (empty($candidates)): ?>
                        <div class="text-muted small fst-italic">No candidates for this position.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">Rank</th>
                                        <th scope="col" style="width: 320px;">Candidate &amp; Party</th>
                                        <th scope="col">Live Vote Distribution</th>
                                        <th scope="col" class="text-end" style="width: 140px;">Total Votes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($candidates as $cand): 
                                        $v_count = (int)$cand['vote_count'];
                                        $percentage = ($pos_total > 0) ? round(($v_count / $pos_total) * 100, 1) : 0;
                                        $cand_name = htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']);
                                        $is_leading = ($v_count > 0 && $v_count === $max_v);
                                    ?>
                                        <tr>
                                            <td class="text-center fw-bold text-muted">
                                                <?php if ($rank === 1 && $v_count > 0): ?>
                                                    <span class="badge bg-warning text-dark rounded-circle p-2" title="Leading Candidate"><i class="fa-solid fa-crown"></i></span>
                                                <?php else: ?>
                                                    #<?php echo $rank; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="assets/uploads/candidates/<?php echo htmlspecialchars($cand['photo'] ?: 'default_candidate.svg'); ?>" alt="<?php echo $cand_name; ?>" class="rounded-circle shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                                                    <div>
                                                        <span class="fw-bold text-dark d-block"><?php echo $cand_name; ?></span>
                                                        <span class="small text-muted"><i class="fa-solid fa-flag me-1"></i><?php echo htmlspecialchars($cand['party']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="flex-grow-1 vote-progress-wrapper">
                                                        <div class="vote-progress-bar <?php echo $is_leading ? 'winner' : ''; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;"></div>
                                                    </div>
                                                    <span class="fw-bold small text-dark" style="min-width: 50px;"><?php echo $percentage; ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="fs-5 fw-bold text-dark"><?php echo number_format($v_count); ?></span>
                                                <small class="text-muted d-block">votes</small>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
