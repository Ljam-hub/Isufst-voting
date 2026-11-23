<?php
$is_admin_page = true;
require_once __DIR__ . '/auth_check.php';

$page_title = "Manage Positions";
$error = '';

// ========================================================
// BACK-END CRUD OPERATIONS: POSITIONS (USING CRUD LIBRARY)
// ========================================================

// 1. INSERT OPERATION (Create new position)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title       = trim($_POST['title'] ?? '');
    $max_votes   = max(1, (int)($_POST['max_votes'] ?? 1));
    $priority    = max(1, (int)($_POST['priority'] ?? 1));
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        setFlash('danger', 'Position title is required.');
    } else {
        try {
            $inserted_id = create_position($pdo, $title, $max_votes, $priority, $description);
            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Created position: $title (ID #$inserted_id)");
            setFlash('success', "Position '{$title}' created successfully!");
            header('Location: positions.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error adding position: ' . $e->getMessage());
        }
    }
}

// 2. UPDATE OPERATION (Edit existing position)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $max_votes   = max(1, (int)($_POST['max_votes'] ?? 1));
    $priority    = max(1, (int)($_POST['priority'] ?? 1));
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0 || empty($title)) {
        setFlash('danger', 'Invalid position details.');
    } else {
        try {
            update_position($pdo, $id, $title, $max_votes, $priority, $description);
            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Updated position ID #$id: $title");
            setFlash('success', "Position '{$title}' updated successfully!");
            header('Location: positions.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error updating position: ' . $e->getMessage());
        }
    }
}

// 3. DELETE OPERATION (Remove position)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        try {
            $pos = get_position_by_id($pdo, $id);
            $title = $pos ? $pos['title'] : "ID #$id";

            delete_position($pdo, $id);
            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Deleted position ID #$id ($title)");
            setFlash('success', "Position '{$title}' and its candidates were deleted successfully.");
            header('Location: positions.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting position: ' . $e->getMessage());
        }
    }
}

// 4. SELECT OPERATION (View all positions with candidate count)
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
                        <li class="breadcrumb-item active">Positions</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0 text-dark">Electoral Positions (CRUD)</h2>
                <div class="text-muted small">Create, view, update, and manage official student council positions.</div>
            </div>

            <div>
                <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createPositionModal">
                    <i class="fa-solid fa-plus me-1"></i> Add New Position
                </button>
            </div>
        </div>

        <!-- Search and Count Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center justify-content-between">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 table-search-input" data-target-table="positionsTable" placeholder="Search position title or description...">
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end text-muted small">
                        Total Positions: <strong class="text-dark fs-6"><?php echo count($positions); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Positions Table (SELECT) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="positionsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;">Order</th>
                                <th>Position Title</th>
                                <th>Max Votes</th>
                                <th>Candidates</th>
                                <th>Role Description</th>
                                <th class="text-end" style="width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($positions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open fa-3x mb-2 d-block"></i>
                                        No positions created yet. Click "Add New Position" above.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($positions as $pos): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary font-monospace"><?php echo $pos['priority']; ?></span>
                                        </td>
                                        <td class="fw-bold text-dark fs-6">
                                            <?php echo htmlspecialchars($pos['title']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?php echo $pos['max_votes'] > 1 ? "Up to {$pos['max_votes']} votes" : "1 vote"; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="fa-solid fa-users me-1 text-primary"></i> <?php echo $pos['candidate_count']; ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo htmlspecialchars($pos['description'] ?: 'None provided'); ?>
                                        </td>
                                        <td class="text-end">
                                            <!-- UPDATE Trigger Modal -->
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editPositionModal_<?php echo $pos['id']; ?>" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- DELETE Trigger -->
                                            <form method="POST" action="positions.php" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete \'<?php echo htmlspecialchars(addslashes($pos['title'])); ?>\'? Deleting this position will also delete all associated candidates.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $pos['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- EDIT MODAL (UPDATE) -->
                                    <div class="modal fade" id="editPositionModal_<?php echo $pos['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="positions.php">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?php echo $pos['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit Position: <?php echo htmlspecialchars($pos['title']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Position Title <span class="text-danger">*</span></label>
                                                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($pos['title']); ?>">
                                                        </div>
                                                        <div class="row g-2 mb-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Max Votes Allowed</label>
                                                                <input type="number" name="max_votes" class="form-control" min="1" max="10" required value="<?php echo $pos['max_votes']; ?>">
                                                                <small class="text-muted">E.g., 1 for President, 2 for Reps</small>
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-semibold">Display Priority</label>
                                                                <input type="number" name="priority" class="form-control" min="1" max="99" required value="<?php echo $pos['priority']; ?>">
                                                                <small class="text-muted">Order in ballot</small>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Description / Role Info</label>
                                                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($pos['description']); ?></textarea>
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
<div class="modal fade" id="createPositionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="positions.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Electoral Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Position Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Sports Coordinator" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Max Votes Allowed</label>
                            <input type="number" name="max_votes" class="form-control" min="1" max="10" value="1" required>
                            <small class="text-muted">Seats available</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Display Priority</label>
                            <input type="number" name="priority" class="form-control" min="1" max="99" value="<?php echo count($positions) + 1; ?>" required>
                            <small class="text-muted">Order in ballot</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description / Duties</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of duties and responsibilities"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Create Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
