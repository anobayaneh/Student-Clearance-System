<?php
// includes/header.php - Top navbar partial
if (!isset($page_title)) $page_title = 'Dashboard';
$unread = (isset($conn) && isset($_SESSION['user_id'])) ? get_unread_count($conn, $_SESSION['user_id']) : 0;
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - ClearanceMS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $base_path ?? '' ?>css/style.css" rel="stylesheet">
</head>
<body>

<!-- Mobile sidebar backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg top-navbar px-3 px-lg-4">
    <button class="btn sidebar-toggle me-3" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
    </button>
    <span class="navbar-brand-text">
        <i class="bi bi-mortarboard-fill text-primary me-2"></i>
        <span class="fw-700">ClearanceMS</span>
    </span>
    <div class="ms-auto d-flex align-items-center gap-2">
        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn btn-icon position-relative" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5"></i>
                <?php if ($unread > 0): ?>
                <span class="badge-dot"><?= $unread ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0">
                <div class="notif-header px-3 py-2 border-bottom">
                    <strong>Notifications</strong>
                    <?php if ($unread > 0): ?>
                    <a href="<?= $base_path ?? '' ?>includes/mark_read.php" class="float-end small text-primary">Mark all read</a>
                    <?php endif; ?>
                </div>
                <?php
                if (isset($conn)) {
                    $uid = $_SESSION['user_id'];
                    $nq = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                    $nq->bind_param("i", $uid);
                    $nq->execute();
                    $nres = $nq->get_result();
                    if ($nres->num_rows === 0) {
                        echo '<div class="px-3 py-3 text-muted small">No notifications.</div>';
                    } else {
                        while ($n = $nres->fetch_assoc()) {
                            $unread_class = $n['is_read'] ? '' : 'notif-unread';
                            echo "<div class='notif-item px-3 py-2 {$unread_class}'>";
                            if (!empty($n['title'])) echo "<div class='small fw-600'>".htmlspecialchars($n['title'])."</div>"; echo "<div class='small'>".htmlspecialchars($n['message'])."</div>";
                            echo "<div class='text-muted' style='font-size:11px'>" . date('M d, Y h:i A', strtotime($n['created_at'])) . "</div>";
                            echo "</div>";
                        }
                    }
                    $nq->close();
                }
                ?>
            </div>
        </div>
        <!-- User -->
        <div class="dropdown">
            <button class="btn user-pill dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
                <span class="d-none d-md-inline"><?= htmlspecialchars($username) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted"><?= ucfirst($role) ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= $base_path ?? '' ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>