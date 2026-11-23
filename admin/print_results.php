<?php
require_once __DIR__ . '/../db_connect.php';

$settings = getElectionSettings($pdo);

// 1. Overall Statistics
$total_voters = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$total_voted  = (int)$pdo->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1")->fetchColumn();
$turnout_rate = ($total_voters > 0) ? round(($total_voted / $total_voters) * 100, 1) : 0;

// 2. Fetch all positions & tallies via CRUD helper
$results_by_pos = get_election_results($pdo);
$positions = get_all_positions($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Certificate of Canvass - <?php echo htmlspecialchars($settings['election_title']); ?></title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: 'Times New Roman', Times, serif;
        }
        .document-wrapper {
            max-width: 900px;
            margin: 30px auto;
            background: #ffffff;
            padding: 50px 60px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .cert-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .seal-placeholder {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 10px;
        }
        @media print {
            body {
                background: #ffffff;
            }
            .document-wrapper {
                margin: 0;
                padding: 20px;
                border: none;
                box-shadow: none;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container my-3 no-print text-center">
    <button onclick="window.print();" class="btn btn-primary px-4 fw-bold shadow-sm me-2">
        <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
    </button>
    <a href="results.php" class="btn btn-outline-secondary px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="document-wrapper">
    <!-- Official Header -->
    <div class="cert-header text-center">
        <div class="seal-placeholder">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h5 class="text-uppercase mb-0 tracking-wide text-secondary">Republic of the Philippines</h5>
        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($settings['organization_name']); ?></h3>
        <h5 class="fw-normal mb-1">Commission on Elections (COMELEC)</h5>
        <div class="badge bg-dark text-white px-3 py-1 fs-6 mt-2">OFFICIAL CERTIFICATE OF CANVASS &amp; RESULTS</div>
        <div class="small text-muted mt-1">Generated: <?php echo date('F d, Y - h:i:s A'); ?> PST</div>
    </div>

    <!-- Executive Summary Box -->
    <div class="row g-2 mb-4 p-3 bg-light border rounded">
        <div class="col-4 text-center border-end">
            <div class="small text-muted text-uppercase">Total Eligible Voters</div>
            <div class="fs-5 fw-bold"><?php echo number_format($total_voters); ?></div>
        </div>
        <div class="col-4 text-center border-end">
            <div class="small text-muted text-uppercase">Total Ballots Cast</div>
            <div class="fs-5 fw-bold"><?php echo number_format($total_voted); ?></div>
        </div>
        <div class="col-4 text-center">
            <div class="small text-muted text-uppercase">Official Turnout</div>
            <div class="fs-5 fw-bold"><?php echo $turnout_rate; ?>%</div>
        </div>
    </div>

    <!-- Proclamation of Winners by Position -->
    <h5 class="fw-bold text-uppercase border-bottom pb-1 mb-3">I. Certified Results by Electoral Position</h5>

    <?php foreach ($positions as $pos): 
        $pos_data = $results_by_pos[$pos['id']] ?? ['total_votes' => 0, 'candidates' => []];
        $candidates = $pos_data['candidates'];
        $pos_total = $pos_data['total_votes'];
    ?>
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="text-dark fs-6"><?php echo htmlspecialchars($pos['title']); ?></strong>
                <span class="small text-muted">Total Votes Cast: <strong><?php echo number_format($pos_total); ?></strong></span>
            </div>
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">Rank</th>
                        <th>Candidate Name</th>
                        <th>Party Affiliation</th>
                        <th class="text-end" style="width: 100px;">Votes</th>
                        <th class="text-end" style="width: 90px;">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No candidates registered.</td></tr>
                    <?php else: 
                        $rk = 1;
                        foreach ($candidates as $c): 
                            $cnt = (int)$c['vote_count'];
                            $pct = ($pos_total > 0) ? round(($cnt / $pos_total) * 100, 1) : 0;
                    ?>
                        <tr class="<?php echo ($rk === 1 && $cnt > 0) ? 'table-warning fw-bold' : ''; ?>">
                            <td class="text-center"><?php echo $rk; ?></td>
                            <td>
                                <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                <?php if ($rk === 1 && $cnt > 0): ?>
                                    <span class="badge bg-success ms-1">PROCLAIMED WINNER</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($c['party']); ?></td>
                            <td class="text-end"><?php echo number_format($cnt); ?></td>
                            <td class="text-end"><?php echo $pct; ?>%</td>
                        </tr>
                    <?php 
                        $rk++;
                        endforeach; 
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <!-- Certification & Signature Block -->
    <div class="mt-5 pt-4 border-top">
        <p class="small text-justify">
            WE, THE MEMBERS OF THE COMMISSION ON ELECTIONS, hereby certify that the foregoing canvass of votes is true and correct, and faithfully reflects all ballots cast electronically during the official election period in accordance with university election policies.
        </p>

        <div class="row text-center mt-5">
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                <strong class="d-block small">COMELEC Secretary</strong>
                <span class="text-muted small">Board of Canvassers</span>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                <strong class="d-block small">COMELEC Commissioner</strong>
                <span class="text-muted small">Board of Canvassers</span>
            </div>
            <div class="col-4">
                <div style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                <strong class="d-block small">COMELEC Chairman</strong>
                <span class="text-muted small">Head Commissioner</span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
