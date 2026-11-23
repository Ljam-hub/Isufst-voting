<?php
$root_path = isset($is_admin_page) && $is_admin_page ? '../' : './';
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_voter_logged_in = isset($_SESSION['voter_logged_in']) && $_SESSION['voter_logged_in'] === true;
$voter_has_voted = isset($_SESSION['has_voted']) && $_SESSION['has_voted'] == 1;
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $root_path; ?>index.php">
            <i class="fa-solid fa-check-to-slot text-warning fs-4"></i>
            <span>ISUFST <span class="text-primary-emphasis text-light opacity-75 fw-normal">iVote</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' && !isset($is_admin_page) ? 'active' : ''; ?>" href="<?php echo $root_path; ?>index.php">
                        <i class="fa-solid fa-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'candidates.php' && !isset($is_admin_page) ? 'active' : ''; ?>" href="<?php echo $root_path; ?>candidates.php">
                        <i class="fa-solid fa-users me-1"></i> Candidates
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'results.php' && !isset($is_admin_page) ? 'active' : ''; ?>" href="<?php echo $root_path; ?>results.php">
                        <i class="fa-solid fa-chart-column me-1"></i> Live Results
                    </a>
                </li>
                <?php if ($is_voter_logged_in): ?>
                    <?php if ($voter_has_voted): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'vote_success.php' ? 'active text-success fw-bold' : 'text-success'; ?>" href="<?php echo $root_path; ?>vote_success.php">
                                <i class="fa-solid fa-receipt me-1"></i> My Voting Receipt
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'ballot.php' ? 'active text-warning fw-bold' : 'text-warning'; ?>" href="<?php echo $root_path; ?>ballot.php">
                                <i class="fa-solid fa-vote-yea me-1"></i> Cast Vote
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <?php if ($is_admin_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-shield-halved me-1"></i> Admin (<?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>)
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="<?php echo $root_path; ?>admin/index.php"><i class="fa-solid fa-gauge me-2 text-primary"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo $root_path; ?>admin/positions.php"><i class="fa-solid fa-layer-group me-2 text-info"></i>Positions</a></li>
                            <li><a class="dropdown-item" href="<?php echo $root_path; ?>admin/candidates.php"><i class="fa-solid fa-user-tie me-2 text-success"></i>Candidates</a></li>
                            <li><a class="dropdown-item" href="<?php echo $root_path; ?>admin/voters.php"><i class="fa-solid fa-id-card me-2 text-secondary"></i>Voters</a></li>
                            <li><a class="dropdown-item" href="<?php echo $root_path; ?>admin/results.php"><i class="fa-solid fa-square-poll-vertical me-2 text-warning"></i>Results Management</a></li>
                            <li><a class="dropdown-item" href="<?php echo $root_path; ?>admin/settings.php"><i class="fa-solid fa-sliders me-2 text-dark"></i>Election Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $root_path; ?>admin/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Admin Logout</a></li>
                        </ul>
                    </li>
                <?php elseif ($is_voter_logged_in): ?>
                    <li class="nav-item">
                        <span class="nav-link text-light opacity-90">
                            <i class="fa-solid fa-circle-user me-1 text-info"></i> 
                            <?php echo htmlspecialchars($_SESSION['voter_name'] ?? 'Voter'); ?>
                            (<?php echo htmlspecialchars($_SESSION['voter_id'] ?? ''); ?>)
                            <?php if ($voter_has_voted): ?>
                                <span class="badge bg-success ms-1 px-2 py-1"><i class="fa-solid fa-check me-1"></i>Voted</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-danger" href="<?php echo $root_path; ?>logout.php">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-light" href="<?php echo $root_path; ?>login.php">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Voter Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-primary" href="<?php echo $root_path; ?>register.php">
                            <i class="fa-solid fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-warning" href="<?php echo $root_path; ?>admin_login.php" title="COMELEC Admin Portal">
                            <i class="fa-solid fa-lock me-1"></i> Admin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
