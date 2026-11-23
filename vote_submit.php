<?php
require_once __DIR__ . '/db_connect.php';

// Authentication Guard
if (!isset($_SESSION['voter_logged_in']) || $_SESSION['voter_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ballot.php');
    exit;
}

$voter_id_pk = (int)$_SESSION['voter_id_pk'];
$voter_id_str = $_SESSION['voter_id'];

// Check election active
$settings = getElectionSettings($pdo);
if (!$settings['is_election_active']) {
    setFlash('danger', 'Voting has been closed by the Election Committee. Your ballot could not be submitted.');
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // -------------------------------------------------------------
    // STRICT DOUBLE-VOTING PREVENTION CHECKS
    // -------------------------------------------------------------
    
    // Check 1: Row-level lock on the voter's has_voted status
    $stmt = $pdo->prepare("SELECT has_voted FROM voters WHERE id = ? FOR UPDATE");
    $stmt->execute([$voter_id_pk]);
    $has_voted = $stmt->fetchColumn();

    // Check 2: Check if any votes are already registered for this voter ID
    $check_votes = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE voter_id = ?");
    $check_votes->execute([$voter_id_pk]);
    $existing_votes_count = (int)$check_votes->fetchColumn();

    if ($has_voted == 1 || $existing_votes_count > 0) {
        $pdo->rollBack();
        $_SESSION['has_voted'] = 1;
        setFlash('danger', '<strong>Double Voting Prohibited:</strong> You have already submitted your ballot for this election. When done voting, you cannot vote again.');
        header('Location: vote_success.php');
        exit;
    }

    $raw_votes = $_POST['votes'] ?? [];
    if (empty($raw_votes) || !is_array($raw_votes)) {
        $pdo->rollBack();
        setFlash('danger', 'You must select at least one candidate before submitting.');
        header('Location: ballot.php');
        exit;
    }

    // Generate unique verifiable ballot receipt code
    $ballot_receipt = 'VOTE-' . strtoupper(substr(md5($voter_id_str . microtime() . uniqid()), 0, 12));

    // Prepare positions validation map
    $all_pos = get_all_positions($pdo);
    $pos_rules = [];
    foreach ($all_pos as $row) {
        $pos_rules[(int)$row['id']] = (int)$row['max_votes'];
    }

    $check_cand = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE id = ? AND position_id = ?");
    $total_choices_recorded = 0;

    foreach ($raw_votes as $position_id => $selection) {
        $position_id = (int)$position_id;
        if (!isset($pos_rules[$position_id])) {
            continue;
        }

        $max_allowed = $pos_rules[$position_id];
        $selected_candidates = is_array($selection) ? $selection : [$selection];

        if (count($selected_candidates) > $max_allowed) {
            $pdo->rollBack();
            setFlash('danger', "Exceeded maximum allowed votes for position ID {$position_id}.");
            header('Location: ballot.php');
            exit;
        }

        foreach ($selected_candidates as $cand_id) {
            $cand_id = (int)$cand_id;
            $check_cand->execute([$cand_id, $position_id]);
            if ($check_cand->fetchColumn() > 0) {
                // Record vote using CRUD function
                db_insert($pdo, 'votes', [
                    'voter_id'       => $voter_id_pk,
                    'position_id'    => $position_id,
                    'candidate_id'   => $cand_id,
                    'ballot_receipt' => $ballot_receipt
                ]);
                $total_choices_recorded++;
            }
        }
    }

    if ($total_choices_recorded === 0) {
        $pdo->rollBack();
        setFlash('danger', 'No valid candidate choices were selected.');
        header('Location: ballot.php');
        exit;
    }

    // Permanently mark voter as voted in the database
    $update_voter = $pdo->prepare("UPDATE voters SET has_voted = 1, voted_at = NOW() WHERE id = ?");
    $update_voter->execute([$voter_id_pk]);

    // Update active session flags
    $_SESSION['has_voted'] = 1;
    $_SESSION['last_receipt'] = $ballot_receipt;

    // Log Activity
    logActivity($pdo, 'Voter', $voter_id_str, "Official ballot cast. Verification Receipt: $ballot_receipt");

    $pdo->commit();

    setFlash('success', '<strong>Success!</strong> Your official ballot has been securely submitted and recorded. Your voting session is now complete.');
    header('Location: vote_success.php');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'An error occurred while submitting your vote: ' . $e->getMessage());
    header('Location: ballot.php');
    exit;
}
