<?php
$is_admin_page = true;
require_once __DIR__ . '/auth_check.php';

$page_title = "Manage Voters";

// =====================================================
// BACK-END CRUD OPERATIONS: VOTERS (USING CRUD LIBRARY)
// =====================================================

// 1. INSERT OPERATION (Admin adds new voter)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $voter_id   = strtoupper(trim($_POST['voter_id'] ?? ''));
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $course     = trim($_POST['course'] ?? 'BSIT');
    $year_level = trim($_POST['year_level'] ?? '1st Year');
    $password   = $_POST['password'] ?? 'voter123';

    if (empty($voter_id) || empty($first_name) || empty($last_name) || empty($email)) {
        setFlash('danger', 'All voter fields are required.');
    } else {
        try {
            // Check uniqueness
            $check = $pdo->prepare("SELECT COUNT(*) FROM voters WHERE voter_id = ? OR email = ?");
            $check->execute([$voter_id, $email]);
            if ($check->fetchColumn() > 0) {
                setFlash('danger', "A voter with Student ID '$voter_id' or Email '$email' already exists.");
            } else {
                create_voter($pdo, $voter_id, $first_name, $last_name, $email, $password, $course, $year_level);

                logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Registered new voter: $voter_id ($first_name $last_name)");
                setFlash('success', "Voter '$voter_id' registered successfully!");
                header('Location: voters.php');
                exit;
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error creating voter: ' . $e->getMessage());
        }
    }
}

// 2. UPDATE OPERATION (Edit voter details)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id         = (int)($_POST['id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $course     = trim($_POST['course'] ?? 'BSIT');
    $year_level = trim($_POST['year_level'] ?? '1st Year');
    $password   = $_POST['password'] ?? '';

    if ($id <= 0 || empty($first_name) || empty($last_name) || empty($email)) {
        setFlash('danger', 'Please provide valid voter details.');
    } else {
        try {
            update_voter($pdo, $id, $first_name, $last_name, $email, $course, $year_level, !empty($password) ? $password : null);

            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Updated voter ID #$id ($first_name $last_name)");
            setFlash('success', "Voter '{$first_name} {$last_name}' updated successfully!");
            header('Location: voters.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error updating voter: ' . $e->getMessage());
        }
    }
}

// RESET VOTER BALLOT (Specific UPDATE to clear vote)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_vote') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $voter = get_voter_by_id($pdo, $id);
            $vid = $voter ? $voter['voter_id'] : "ID #$id";

            reset_voter_vote($pdo, $id);

            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Reset voting status for voter: $vid");
            setFlash('warning', "Voting status for voter '$vid' has been reset. They may now vote again.");
            header('Location: voters.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error resetting voter: ' . $e->getMessage());
        }
    }
}

// 3. DELETE OPERATION (Remove voter)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $v = get_voter_by_id($pdo, $id);
            $v_desc = $v ? ($v['voter_id'] . ' - ' . $v['first_name'] . ' ' . $v['last_name']) : "ID #$id";

            delete_voter($pdo, $id);

            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Deleted voter $v_desc");
            setFlash('success', "Voter '$v_desc' was deleted successfully.");
            header('Location: voters.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting voter: ' . $e->getMessage());
        }
    }
}

// 4. SELECT OPERATION with optional filters via CRUD helper
$status_filter = $_GET['status'] ?? 'all';
$course_filter = $_GET['course'] ?? 'all';
$voters = get_all_voters($pdo, $status_filter, $course_filter);

// Get unique courses for filter
$courses_stmt = $pdo->query("SELECT DISTINCT course FROM voters ORDER BY course ASC");
$all_courses = $courses_stmt->fetchAll(PDO::FETCH_COLUMN);

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
                        <li class="breadcrumb-item active">Voters</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0 text-dark">Voters Management (CRUD)</h2>
                <div class="text-muted small">Register new voters, update student records, inspect voting status, or reset votes.</div>
            </div>

            <div>
                <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createVoterModal">
                    <i class="fa-solid fa-user-plus me-1"></i> Register New Voter
                </button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-3">
                <form method="GET" action="voters.php">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 table-search-input" data-target-table="votersTable" placeholder="Search student ID, name, email...">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Voting Statuses</option>
                                <option value="voted" <?php echo $status_filter === 'voted' ? 'selected' : ''; ?>>Ballot Cast (Voted)</option>
                                <option value="not_voted" <?php echo $status_filter === 'not_voted' ? 'selected' : ''; ?>>Not Yet Voted</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select name="course" class="form-select" onchange="this.form.submit()">
                                <option value="all">All Academic Programs</option>
                                <?php foreach ($all_courses as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $course_filter === $c ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 text-md-end text-muted small">
                            Total: <strong class="text-dark fs-6"><?php echo count($voters); ?></strong>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Voters Table (SELECT) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="votersTable">
                        <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Voter Name</th>
                                <th>Email</th>
                                <th>Program &amp; Year</th>
                                <th>Status</th>
                                <th>Voted At</th>
                                <th class="text-end" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($voters)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash fa-3x mb-2 d-block"></i>
                                        No registered voters match the selected filter.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($voters as $v): 
                                    $voter_name = htmlspecialchars($v['first_name'] . ' ' . $v['last_name']);
                                ?>
                                    <tr>
                                        <td>
                                            <span class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($v['voter_id']); ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-dark"><?php echo $voter_name; ?></strong>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo htmlspecialchars($v['email']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($v['course']); ?></span>
                                            <span class="badge bg-secondary bg-opacity-25 text-dark"><?php echo htmlspecialchars($v['year_level']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($v['has_voted']): ?>
                                                <span class="badge bg-success rounded-pill px-2 py-1">
                                                    <i class="fa-solid fa-check me-1"></i> Voted
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary rounded-pill px-2 py-1">
                                                    <i class="fa-solid fa-clock me-1"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo $v['voted_at'] ? date('M d, Y h:i A', strtotime($v['voted_at'])) : '&mdash;'; ?>
                                        </td>
                                        <td class="text-end">
                                            <!-- Reset Vote Button (if already voted) -->
                                            <?php if ($v['has_voted']): ?>
                                                <form method="POST" action="voters.php" class="d-inline"
                                                      onsubmit="return confirm('Reset voting status for <?php echo htmlspecialchars(addslashes($voter_name)); ?>? This will ERASE their cast votes and allow them to vote again.');">
                                                    <input type="hidden" name="action" value="reset_vote">
                                                    <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Reset Vote (Allows re-voting)">
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- UPDATE Trigger Modal -->
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editVoterModal_<?php echo $v['id']; ?>" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- DELETE Trigger -->
                                            <form method="POST" action="voters.php" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete voter \'<?php echo htmlspecialchars(addslashes($voter_name)); ?>\'?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- EDIT MODAL (UPDATE) -->
                                    <div class="modal fade" id="editVoterModal_<?php echo $v['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="voters.php">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit Voter: <?php echo htmlspecialchars($v['voter_id']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($v['first_name']); ?>">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($v['last_name']); ?>">
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($v['email']); ?>">
                                                        </div>

                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Program / Course</label>
                                                                <input type="text" name="course" class="form-control" required value="<?php echo htmlspecialchars($v['course']); ?>">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Year Level</label>
                                                                <select name="year_level" class="form-select">
                                                                    <option value="1st Year" <?php echo $v['year_level'] === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                                                    <option value="2nd Year" <?php echo $v['year_level'] === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                                                    <option value="3rd Year" <?php echo $v['year_level'] === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                                                    <option value="4th Year" <?php echo $v['year_level'] === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Reset Password (Optional)</label>
                                                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep existing password">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary fw-semibold">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- CREATE MODAL (INSERT) -->
<div class="modal fade" id="createVoterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="voters.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register New Student Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student ID <span class="text-danger">*</span></label>
                        <input type="text" name="voter_id" class="form-control" placeholder="e.g. 2026-00120" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" placeholder="Juan" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" placeholder="Dela Cruz" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="juan@isufst.edu.ph" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Course / Degree</label>
                            <input type="text" name="course" class="form-control" value="BSIT" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Year Level</label>
                            <select name="year_level" class="form-select">
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="text" name="password" class="form-control" value="voter123" required>
                        <div class="form-text">Default password is <code>voter123</code>. Voter can change it later.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Register Voter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
