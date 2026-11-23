<?php
$is_admin_page = true;
require_once __DIR__ . '/auth_check.php';

$page_title = "Manage Candidates";

// Helper for photo upload
function handlePhotoUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'default_candidate.svg';
    }
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
    $file_info = pathinfo($file['name']);
    $ext = strtolower($file_info['extension'] ?? '');

    if (!in_array($ext, $allowed_extensions)) {
        return 'default_candidate.svg';
    }

    $upload_dir = __DIR__ . '/../assets/uploads/candidates/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $new_filename = 'cand_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $new_filename;
    }

    return 'default_candidate.svg';
}

// =========================================================
// BACK-END CRUD OPERATIONS: CANDIDATES (USING CRUD LIBRARY)
// =========================================================

// 1. INSERT OPERATION (Add new candidate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $position_id = (int)($_POST['position_id'] ?? 0);
    $party       = trim($_POST['party'] ?? 'Independent');
    $platform    = trim($_POST['platform'] ?? '');

    if (empty($first_name) || empty($last_name) || $position_id <= 0) {
        setFlash('danger', 'First name, last name, and position are required.');
    } else {
        try {
            $photo = 'default_candidate.svg';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photo = handlePhotoUpload($_FILES['photo']);
            }

            create_candidate($pdo, $position_id, $first_name, $last_name, $party, $platform, $photo);

            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Added candidate: $first_name $last_name");
            setFlash('success', "Candidate '{$first_name} {$last_name}' added successfully!");
            header('Location: candidates.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error adding candidate: ' . $e->getMessage());
        }
    }
}

// 2. UPDATE OPERATION (Edit existing candidate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id          = (int)($_POST['id'] ?? 0);
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $position_id = (int)($_POST['position_id'] ?? 0);
    $party       = trim($_POST['party'] ?? 'Independent');
    $platform    = trim($_POST['platform'] ?? '');

    if ($id <= 0 || empty($first_name) || empty($last_name) || $position_id <= 0) {
        setFlash('danger', 'Please provide all required fields.');
    } else {
        try {
            $photo = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photo = handlePhotoUpload($_FILES['photo']);
            }

            update_candidate($pdo, $id, $position_id, $first_name, $last_name, $party, $platform, $photo);

            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Updated candidate ID #$id ($first_name $last_name)");
            setFlash('success', "Candidate '{$first_name} {$last_name}' updated successfully!");
            header('Location: candidates.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error updating candidate: ' . $e->getMessage());
        }
    }
}

// 3. DELETE OPERATION (Remove candidate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        try {
            $cand = get_candidate_by_id($pdo, $id);
            $cand_name = $cand ? ($cand['first_name'] . ' ' . $cand['last_name']) : "ID #$id";

            delete_candidate($pdo, $id);

            logActivity($pdo, 'Admin', $_SESSION['admin_username'], "Deleted candidate $cand_name");
            setFlash('success', "Candidate '{$cand_name}' was removed from the ballot.");
            header('Location: candidates.php');
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting candidate: ' . $e->getMessage());
        }
    }
}

// Fetch all positions for dropdowns via CRUD function
$all_positions = get_all_positions($pdo);

// 4. SELECT OPERATION (View candidates with position & vote count)
$filter_position = (int)($_GET['pos'] ?? 0);
$candidates = get_all_candidates($pdo, $filter_position > 0 ? $filter_position : null);

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
                        <li class="breadcrumb-item active">Candidates</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0 text-dark">Candidates Management (CRUD)</h2>
                <div class="text-muted small">Add, view, edit, and manage running student council candidates.</div>
            </div>

            <div>
                <button type="button" class="btn btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createCandidateModal">
                    <i class="fa-solid fa-user-plus me-1"></i> Add New Candidate
                </button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center justify-content-between">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 table-search-input" data-target-table="candidatesTable" placeholder="Search candidate by name, party, or platform...">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <form method="GET" action="candidates.php" class="d-flex align-items-center gap-2">
                            <select name="pos" class="form-select" onchange="this.form.submit()">
                                <option value="0">All Electoral Positions</option>
                                <?php foreach ($all_positions as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $filter_position == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div class="col-md-3 text-md-end text-muted small">
                        Showing: <strong class="text-dark fs-6"><?php echo count($candidates); ?></strong> Candidates
                    </div>
                </div>
            </div>
        </div>

        <!-- Candidates Table (SELECT) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="candidatesTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Photo</th>
                                <th>Candidate Name</th>
                                <th>Position</th>
                                <th>Party Affiliation</th>
                                <th>Platform Summary</th>
                                <th>Votes</th>
                                <th class="text-end" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($candidates)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-user-slash fa-3x mb-2 d-block"></i>
                                        No candidates found. Click "Add New Candidate" to register one.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidates as $cand): 
                                    $cand_name = htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']);
                                    $photo_src = (!empty($cand['photo']) && file_exists(__DIR__ . '/../assets/uploads/candidates/' . $cand['photo']))
                                        ? ('../assets/uploads/candidates/' . $cand['photo'])
                                        : '../assets/uploads/candidates/default_candidate.svg';
                                ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $photo_src; ?>" alt="<?php echo $cand_name; ?>" class="rounded-circle shadow-sm" style="width: 44px; height: 44px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong class="text-dark fs-6"><?php echo $cand_name; ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                                <?php echo htmlspecialchars($cand['position_title']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($cand['party']); ?></span>
                                        </td>
                                        <td class="small text-muted" style="max-width: 250px;">
                                            <?php echo htmlspecialchars(mb_strimwidth($cand['platform'], 0, 75, '...')); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="fa-solid fa-check text-success me-1"></i> <?php echo number_format($cand['vote_count']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <!-- UPDATE Trigger Modal -->
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCandidateModal_<?php echo $cand['id']; ?>" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- DELETE Trigger -->
                                            <form method="POST" action="candidates.php" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete candidate \'<?php echo htmlspecialchars(addslashes($cand_name)); ?>\'? All associated votes for this candidate will also be removed.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $cand['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- EDIT MODAL (UPDATE) -->
                                    <div class="modal fade" id="editCandidateModal_<?php echo $cand['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form method="POST" action="candidates.php" enctype="multipart/form-data">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?php echo $cand['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Edit Candidate: <?php echo $cand_name; ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($cand['first_name']); ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($cand['last_name']); ?>">
                                                            </div>
                                                        </div>

                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Running Position <span class="text-danger">*</span></label>
                                                                <select name="position_id" class="form-select" required>
                                                                    <?php foreach ($all_positions as $p): ?>
                                                                        <option value="<?php echo $p['id']; ?>" <?php echo $cand['position_id'] == $p['id'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($p['title']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Political Party / Slate</label>
                                                                <input type="text" name="party" class="form-control" value="<?php echo htmlspecialchars($cand['party']); ?>">
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Platform &amp; Advocacy</label>
                                                            <textarea name="platform" class="form-control" rows="3"><?php echo htmlspecialchars($cand['platform']); ?></textarea>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Update Candidate Photo (Optional)</label>
                                                            <input type="file" name="photo" class="form-control" accept="image/*">
                                                            <div class="form-text">Leave blank to keep current photo. Supported formats: JPG, PNG, WEBP, SVG.</div>
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
<div class="modal fade" id="createCandidateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="candidates.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" placeholder="e.g. John" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" placeholder="e.g. Smith" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Running Position <span class="text-danger">*</span></label>
                            <select name="position_id" class="form-select" required>
                                <option value="">-- Choose Position --</option>
                                <?php foreach ($all_positions as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Political Party / Slate</label>
                            <input type="text" name="party" class="form-control" placeholder="e.g. Sulong ISUFST Party" value="Independent">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Platform &amp; Advocacy Statements</label>
                        <textarea name="platform" class="form-control" rows="3" placeholder="Summary of policies, vision, and student services proposed..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Candidate Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <div class="form-text">Optional. If not provided, a default avatar will be assigned.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-semibold">Register Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
