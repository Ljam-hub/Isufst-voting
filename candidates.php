<?php
require_once __DIR__ . '/db_connect.php';

$page_title = "Candidates & Platforms";
$settings = getElectionSettings($pdo);

// Fetch all positions using CRUD helper
$positions = get_all_positions($pdo);

// Fetch all candidates grouped by position_id using CRUD helper
$all_candidates = get_all_candidates($pdo);
$candidates_by_position = [];
foreach ($all_candidates as $row) {
    $candidates_by_position[$row['position_id']][] = $row;
}

$voter_has_voted = isset($_SESSION['has_voted']) && $_SESSION['has_voted'] == 1;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<main class="py-4">
    <div class="container">
        <!-- Page Title Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h2 class="fw-bold mb-1">Official Candidates &amp; Platforms</h2>
                <p class="text-muted mb-0">Browse through the candidates running for the <?php echo htmlspecialchars($settings['academic_year']); ?> Student Council.</p>
            </div>
            <div>
                <?php if (isset($_SESSION['voter_logged_in'])): ?>
                    <?php if ($voter_has_voted): ?>
                        <a href="vote_success.php" class="btn btn-success fw-bold shadow-sm">
                            <i class="fa-solid fa-receipt me-1"></i> View Your Voting Receipt
                        </a>
                    <?php else: ?>
                        <a href="ballot.php" class="btn btn-warning fw-bold shadow-sm">
                            <i class="fa-solid fa-vote-yea me-1"></i> Proceed to Ballot
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary fw-semibold shadow-sm">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login to Vote
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Filter & Search Form -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-7">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="candidateSearchInput" class="form-control border-start-0" placeholder="Search candidate by name, political party, or keywords...">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <select id="positionFilterSelect" class="form-select">
                            <option value="all">All Positions</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="pos-<?php echo $pos['id']; ?>"><?php echo htmlspecialchars($pos['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Positions & Candidates Display -->
        <?php if (empty($positions)): ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-folder-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No positions or candidates have been added yet.</h5>
            </div>
        <?php else: ?>
            <?php foreach ($positions as $pos): 
                $cands = $candidates_by_position[$pos['id']] ?? [];
            ?>
                <section class="position-section mb-5" id="pos-<?php echo $pos['id']; ?>">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <h4 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($pos['title']); ?></h4>
                        <span class="badge bg-secondary rounded-pill">
                            <?php echo $pos['max_votes'] > 1 ? "Vote up to {$pos['max_votes']} candidates" : "Vote for 1 candidate"; ?>
                        </span>
                        <?php if (!empty($pos['description'])): ?>
                            <span class="text-muted small ms-2 d-none d-md-inline">&bull; <?php echo htmlspecialchars($pos['description']); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($cands)): ?>
                        <div class="alert alert-light text-muted border">No registered candidates for this position yet.</div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($cands as $cand): 
                                $candName = htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']);
                            ?>
                                <div class="col-md-6 col-lg-4 candidate-item" data-name="<?php echo strtolower($candName . ' ' . $cand['party'] . ' ' . $cand['platform']); ?>">
                                    <div class="card h-100 shadow-sm border-0 rounded-3 p-3">
                                        <div class="text-center mb-3">
                                            <img src="assets/uploads/candidates/<?php echo htmlspecialchars($cand['photo'] ?: 'default_candidate.svg'); ?>" alt="<?php echo $candName; ?>" class="candidate-photo shadow-sm mb-2">
                                            <h5 class="fw-bold mb-1 text-dark"><?php echo $candName; ?></h5>
                                            <span class="badge bg-info text-dark fw-semibold px-2 py-1">
                                                <i class="fa-solid fa-flag me-1"></i><?php echo htmlspecialchars($cand['party']); ?>
                                            </span>
                                        </div>

                                        <div class="bg-light p-3 rounded-2 small text-secondary flex-grow-1">
                                            <strong class="text-dark d-block mb-1"><i class="fa-solid fa-bullhorn text-primary me-1"></i> Platform &amp; Agenda:</strong>
                                            <?php echo nl2br(htmlspecialchars($cand['platform'] ?: 'No platform statement submitted.')); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('candidateSearchInput');
    const filterSelect = document.getElementById('positionFilterSelect');
    const candidateItems = document.querySelectorAll('.candidate-item');
    const positionSections = document.querySelectorAll('.position-section');

    function applyFilter() {
        const query = searchInput.value.toLowerCase().trim();
        const selectedPos = filterSelect.value;

        positionSections.forEach(section => {
            const sectionId = section.id;
            const matchesPos = (selectedPos === 'all' || selectedPos === sectionId);
            let hasVisibleCandidate = false;

            const items = section.querySelectorAll('.candidate-item');
            items.forEach(item => {
                const data = item.getAttribute('data-name');
                const matchesQuery = !query || data.includes(query);

                if (matchesPos && matchesQuery) {
                    item.style.display = '';
                    hasVisibleCandidate = true;
                } else {
                    item.style.display = 'none';
                }
            });

            if (matchesPos && (hasVisibleCandidate || (!query && items.length === 0))) {
                section.style.display = '';
            } else {
                section.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('keyup', applyFilter);
    filterSelect.addEventListener('change', applyFilter);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
