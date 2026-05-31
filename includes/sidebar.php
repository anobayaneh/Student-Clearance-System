<?php
// includes/sidebar.php - Sidebar navigation
$role = $_SESSION['role'] ?? '';
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="bi bi-mortarboard-fill"></i>
        <span>ClearanceMS</span>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
        <div class="nav-section">MAIN</div>
        <a href="<?= $base_path ?? '' ?>dashboard.php" class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>
        <div class="nav-section">MANAGEMENT</div>
        <a href="<?= $base_path ?? '' ?>students/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'students') !== false ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i><span>Students</span>
        </a>
        <a href="<?= $base_path ?? '' ?>departments/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'departments') !== false ? 'active' : '' ?>">
            <i class="bi bi-building-fill"></i><span>Departments</span>
        </a>
        <a href="<?= $base_path ?? '' ?>clearances/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'clearances') !== false ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check-fill"></i><span>Clearances</span>
        </a>
        <div class="nav-section">REPORTS</div>
        <a href="<?= $base_path ?? '' ?>reports/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-fill"></i><span>Reports</span>
        </a>

        <?php elseif ($role === 'student'): ?>
        <div class="nav-section">MY CLEARANCE</div>
        <a href="<?= $base_path ?? '' ?>dashboard.php" class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>
        <a href="<?= $base_path ?? '' ?>clearances/my_clearance.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'clearances') !== false ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check-fill"></i><span>My Clearance</span>
        </a>
        <a href="<?= $base_path ?? '' ?>reports/my_report.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : '' ?>">
            <i class="bi bi-printer-fill"></i><span>Print Clearance</span>
        </a>

        <?php elseif ($role === 'officer'): ?>
        <div class="nav-section">DEPARTMENT</div>
        <a href="<?= $base_path ?? '' ?>dashboard.php" class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>
        <a href="<?= $base_path ?? '' ?>clearances/dept_clearances.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'clearances') !== false ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check-fill"></i><span>Clearance Requests</span>
        </a>
        <?php endif; ?>

        <div class="nav-section">ACCOUNT</div>
        <a href="<?= $base_path ?? '' ?>logout.php" class="nav-item text-danger-nav">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </nav>

    <!-- Credits Footer -->
    <div class="sidebar-credits">
        <div class="credits-inner">
            <i class="bi bi-code-slash me-1"></i>
            <div>
                <div class="fw-600">Gideon Agtas</div>
                
            </div>
        </div>
    </div>
</aside>