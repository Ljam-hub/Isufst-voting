<?php
$is_admin_page = true;
require_once __DIR__ . '/auth_check.php';

$page_title = "Admin Dashboard";
$settings = getElectionSettings($pdo);

// Quick Toggle Election Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_election') {
    $new_status = (int)$_POST['status'];
    $stmt = $pdo->prepare("UPDATE settings SET is_election_active = ? WHERE id = 1");
    $stmt->execute([$new_status]);
    
    $status_text = $new_status ? 'opened' : 'closed';
    logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Election status changed to $status_text");
    setFlash('success', "Election polls have been successfully $status_text.");
    header('Location: index.php');
    exit;
}

// Compute metrics
$total_voters = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$total_voted  = (int)$pdo->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1")->fetchColumn();
$total_candidates = (int)$pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
$total_positions  = (int)$pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn();
$turnout_rate = ($total_voters > 0) ? round(($total_voted / $total_voters) * 100, 1) : 0;

// Fetch Recent Activity Logs
$logs_stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8");
$recent_logs = $logs_stmt->fetchAll();

// Fetch Recent Ballots Cast
$votes_feed_stmt = $pdo->query("SELECT v.voter_id, v.first_name, v.last_name, v.course, v.voted_at 
                                FROM voters v 
                                WHERE v.has_voted = 1 
                                ORDER BY v.voted_at DESC LIMIT 6");
$recent_voters = $votes_feed_stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Dashboard Top Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <span class="badge bg-dark rounded-pill px-3 py-1 mb-1">
                    <i class="fa-solid fa-lock text-warning me-1"></i> Admin Panel
                </span>
                <h2 class="fw-bold mb-0 text-dark">Election Management Dashboard</h2>
                <div class="text-muted small">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_fullname']); ?></strong> (<?php echo htmlspecialchars($_SESSION['admin_role']); ?>)</div>
            </div>

            <!-- Election Poll State Toggle -->
            <div class="d-flex align-items-center gap-2">
                <form method="POST" action="index.php" class="d-inline">
                    <input type="hidden" name="action" value="toggle_election">
                    <?php if ($settings['is_election_active']): ?>
                        <input type="hidden" name="status" value="0">
                        <button type="submit" class="btn btn-danger fw-bold shadow-sm" onclick="return confirm('Are you sure you want to CLOSE the election polls? Voters will not be able to cast ballots while polls are closed.');">
                            <i class="fa-solid fa-pause me-1"></i> Close / Pause Polls
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="status" value="1">
                        <button type="submit" class="btn btn-success fw-bold shadow-sm">
                            <i class="fa-solid fa-play me-1"></i> Open Election Polls
                        </button>
                    <?php endif; ?>
                </form>

                <a href="print_results.php" target="_blank" class="btn btn-outline-dark shadow-sm">
                    <i class="fa-solid fa-print me-1"></i> Print Results
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Total Voters</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_voters); ?></h3>
                        </div>
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle">
                            <i class="fa-solid fa-id-card fs-4"></i>
                        </div>
                    </div>
                    <a href="voters.php" class="small text-primary text-decoration-none mt-2 d-inline-block">Manage Voters &rarr;</a>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Ballots Cast</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_voted); ?></h3>
                        </div>
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle">
                            <i class="fa-solid fa-envelope-circle-check fs-4"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2">Turnout: <strong><?php echo $turnout_rate; ?>%</strong></div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Candidates</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_candidates); ?></h3>
                        </div>
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle">
                            <i class="fa-solid fa-user-tie fs-4"></i>
                        </div>
                    </div>
                    <a href="candidates.php" class="small text-info text-decoration-none mt-2 d-inline-block">Manage Candidates &rarr;</a>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-stat bg-white shadow-sm p-3 h-100 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Positions</span>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo number_format($total_positions); ?></h3>
                        </div>
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle">
                            <i class="fa-solid fa-layer-group fs-4"></i>
                        </div>
                    </div>
                    <a href="positions.php" class="small text-warning text-decoration-none mt-2 d-inline-block">Manage Positions &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Quick Management Shortcuts -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i>Quick Management Actions</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="positions.php?action=create" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> Add New Position
                    </a>
                    <a href="candidates.php?action=create" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-user-plus me-1"></i> Add New Candidate
                    </a>
                    <a href="voters.php?action=create" class="btn btn-outline-info btn-sm">
                        <i class="fa-solid fa-id-badge me-1"></i> Register Voter
                    </a>
                    <a href="results.php" class="btn btn-outline-warning btn-sm">
                        <i class="fa-solid fa-chart-pie me-1"></i> View Live Tally
                    </a>
                    <a href="settings.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-gear me-1"></i> Election Configuration
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Ballots Submitted -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-check-double text-success me-2"></i>Recently Cast Ballots</h5>
                        <a href="voters.php?status=voted" class="small text-decoration-none">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Program</th>
                                        <th>Time Cast</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_voters)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No ballots cast yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_voters as $rv): ?>
                                            <tr>
                                                <td class="font-monospace fw-semibold text-primary"><?php echo htmlspecialchars($rv['voter_id']); ?></td>
                                                <td><?php echo htmlspecialchars($rv['first_name'] . ' ' . $rv['last_name']); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($rv['course']); ?></span></td>
                                                <td class="small text-muted"><?php echo date('M d, h:i A', strtotime($rv['voted_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Logs Audit Trail -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>System Audit Trail</h5>
                        <span class="badge bg-light text-muted border">Live Logs</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>User</th>
                                        <th>Action Taken</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_logs)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No activity logged.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_logs as $lg): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?php echo $lg['user_type'] === 'Admin' ? 'bg-danger' : 'bg-primary'; ?> rounded-pill">
                                                        <?php echo htmlspecialchars($lg['user_type']); ?>
                                                    </span>
                                                </td>
                                                <td class="font-monospace small"><?php echo htmlspecialchars($lg['user_identifier']); ?></td>
                                                <td class="small text-dark"><?php echo htmlspecialchars($lg['action']); ?></td>
                                                <td class="text-muted small"><?php echo date('M d, h:i A', strtotime($lg['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
