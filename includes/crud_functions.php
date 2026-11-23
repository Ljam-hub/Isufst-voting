<?php
/**
 * CRUD Helper Functions Library
 * Online Voting System
 *
 * Implements Create, Read, Update, and Delete operations for Positions,
 * Candidates, Voters, and Election Ballots using PDO Prepared Statements.
 */

// ===================================================================
// 1. REUSABLE DATABASE CRUD HELPERS
// ===================================================================

function db_insert($pdo, $table, array $data) {
    if (empty($data)) return false;
    $cols = array_keys($data);
    $placeholders = array_fill(0, count($cols), '?');
    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute(array_values($data)) ? (int)$pdo->lastInsertId() : false;
}

function db_select_one($pdo, $table, $id, $id_col = 'id') {
    $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `{$id_col}` = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function db_select_all($pdo, $table, array $where = [], $order_by = '') {
    $sql = "SELECT * FROM `{$table}`";
    $vals = [];
    if (!empty($where)) {
        $clauses = [];
        foreach ($where as $col => $val) {
            $clauses[] = "`{$col}` = ?";
            $vals[] = $val;
        }
        $sql .= " WHERE " . implode(' AND ', $clauses);
    }
    if (!empty($order_by)) {
        $sql .= " ORDER BY " . $order_by;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
    return $stmt->fetchAll();
}

function db_update($pdo, $table, array $data, $id, $id_col = 'id') {
    if (empty($data)) return false;
    $clauses = [];
    $vals = [];
    foreach ($data as $col => $val) {
        $clauses[] = "`{$col}` = ?";
        $vals[] = $val;
    }
    $vals[] = $id;
    $sql = "UPDATE `{$table}` SET " . implode(', ', $clauses) . " WHERE `{$id_col}` = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($vals);
}

function db_delete($pdo, $table, $id, $id_col = 'id') {
    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE `{$id_col}` = ?");
    return $stmt->execute([$id]);
}

// ===================================================================
// 2. POSITIONS CRUD FUNCTIONS
// ===================================================================

function create_position($pdo, $title, $max_votes = 1, $priority = 1, $description = '') {
    return db_insert($pdo, 'positions', [
        'title'       => trim($title),
        'max_votes'   => max(1, (int)$max_votes),
        'priority'    => max(1, (int)$priority),
        'description' => trim($description)
    ]);
}

function get_all_positions($pdo) {
    $sql = "SELECT p.*, COUNT(c.id) AS candidate_count 
            FROM positions p 
            LEFT JOIN candidates c ON p.id = c.position_id 
            GROUP BY p.id 
            ORDER BY p.priority ASC, p.id ASC";
    return $pdo->query($sql)->fetchAll();
}

function get_position_by_id($pdo, $id) {
    return db_select_one($pdo, 'positions', (int)$id);
}

function update_position($pdo, $id, $title, $max_votes, $priority, $description) {
    return db_update($pdo, 'positions', [
        'title'       => trim($title),
        'max_votes'   => max(1, (int)$max_votes),
        'priority'    => max(1, (int)$priority),
        'description' => trim($description)
    ], (int)$id);
}

function delete_position($pdo, $id) {
    return db_delete($pdo, 'positions', (int)$id);
}

// ===================================================================
// 3. CANDIDATES CRUD FUNCTIONS
// ===================================================================

function create_candidate($pdo, $position_id, $first_name, $last_name, $party = 'Independent', $platform = '', $photo = 'default_candidate.svg') {
    return db_insert($pdo, 'candidates', [
        'position_id' => (int)$position_id,
        'first_name'  => trim($first_name),
        'last_name'   => trim($last_name),
        'party'       => trim($party ?: 'Independent'),
        'platform'    => trim($platform),
        'photo'       => $photo ?: 'default_candidate.svg'
    ]);
}

function get_all_candidates($pdo, $position_id = null) {
    $sql = "SELECT c.*, p.title AS position_title, COUNT(v.id) AS vote_count 
            FROM candidates c 
            JOIN positions p ON c.position_id = p.id 
            LEFT JOIN votes v ON c.id = v.candidate_id ";
    $params = [];
    if (!empty($position_id)) {
        $sql .= " WHERE c.position_id = ? ";
        $params[] = (int)$position_id;
    }
    $sql .= " GROUP BY c.id ORDER BY p.priority ASC, c.last_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_candidate_by_id($pdo, $id) {
    return db_select_one($pdo, 'candidates', (int)$id);
}

function update_candidate($pdo, $id, $position_id, $first_name, $last_name, $party, $platform, $photo = null) {
    $data = [
        'position_id' => (int)$position_id,
        'first_name'  => trim($first_name),
        'last_name'   => trim($last_name),
        'party'       => trim($party ?: 'Independent'),
        'platform'    => trim($platform)
    ];
    if ($photo !== null) {
        $data['photo'] = $photo;
    }
    return db_update($pdo, 'candidates', $data, (int)$id);
}

function delete_candidate($pdo, $id) {
    return db_delete($pdo, 'candidates', (int)$id);
}

// ===================================================================
// 4. VOTERS CRUD FUNCTIONS
// ===================================================================

function create_voter($pdo, $voter_id, $first_name, $last_name, $email, $password, $course = 'BSIT', $year_level = '1st Year') {
    return db_insert($pdo, 'voters', [
        'voter_id'   => strtoupper(trim($voter_id)),
        'first_name' => trim($first_name),
        'last_name'  => trim($last_name),
        'email'      => strtolower(trim($email)),
        'password'   => password_hash($password, PASSWORD_DEFAULT),
        'course'     => trim($course),
        'year_level' => trim($year_level),
        'has_voted'  => 0
    ]);
}

function get_all_voters($pdo, $status = 'all', $course = 'all') {
    $sql = "SELECT * FROM voters WHERE 1=1";
    $params = [];
    if ($status === 'voted') {
        $sql .= " AND has_voted = 1";
    } elseif ($status === 'not_voted') {
        $sql .= " AND has_voted = 0";
    }
    if (!empty($course) && $course !== 'all') {
        $sql .= " AND course = ?";
        $params[] = $course;
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_voter_by_id($pdo, $id) {
    return db_select_one($pdo, 'voters', (int)$id);
}

function get_voter_by_student_id($pdo, $identifier) {
    $stmt = $pdo->prepare("SELECT * FROM voters WHERE voter_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    return $stmt->fetch();
}

function update_voter($pdo, $id, $first_name, $last_name, $email, $course, $year_level, $new_password = null) {
    $data = [
        'first_name' => trim($first_name),
        'last_name'  => trim($last_name),
        'email'      => strtolower(trim($email)),
        'course'     => trim($course),
        'year_level' => trim($year_level)
    ];
    if (!empty($new_password)) {
        $data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }
    return db_update($pdo, 'voters', $data, (int)$id);
}

function reset_voter_vote($pdo, $id) {
    try {
        $pdo->beginTransaction();
        db_delete($pdo, 'votes', (int)$id, 'voter_id');
        $stmt = $pdo->prepare("UPDATE voters SET has_voted = 0, voted_at = NULL WHERE id = ?");
        $stmt->execute([(int)$id]);
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}

function delete_voter($pdo, $id) {
    return db_delete($pdo, 'voters', (int)$id);
}

// ===================================================================
// 5. ELECTION RESULTS CALCULATION
// ===================================================================

function get_election_results($pdo) {
    $positions = get_all_positions($pdo);
    $results = [];

    foreach ($positions as $pos) {
        $pos_id = (int)$pos['id'];
        $tot_stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE position_id = ?");
        $tot_stmt->execute([$pos_id]);
        $pos_total = (int)$tot_stmt->fetchColumn();

        $cands = get_all_candidates($pdo, $pos_id);
        $max_v = 0;
        if (!empty($cands)) {
            usort($cands, function($a, $b) {
                return (int)$b['vote_count'] <=> (int)$a['vote_count'];
            });
            $max_v = (int)$cands[0]['vote_count'];
        }

        $results[$pos_id] = [
            'position'    => $pos,
            'total_votes' => $pos_total,
            'max_votes'   => $max_v,
            'candidates'  => $cands
        ];
    }

    return $results;
}
