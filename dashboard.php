<?php
// dashboard.php - Main Dashboard (role-aware)
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$page_title = 'Dashboard';
$base_path = '';

// ---- ADMIN STATS ----
if ($role === 'admin') {
    $total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
    $pending = $conn->query("SELECT COUNT(*) as c FROM clearances WHERE status='pending'")->fetch_assoc()['c'];
    $approved = $conn->query("SELECT COUNT(*) as c FROM clearances WHERE status='approved'")->fetch_assoc()['c'];
    $rejected = $conn->query("SELECT COUNT(*) as c FROM clearances WHERE status='rejected'")->fetch_assoc()['c'];
    $total_depts = $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc()['c'];
    $total_clearances = $conn->query("SELECT COUNT(*) as c FROM clearances")->fetch_assoc()['c'];

    // Dept stats for chart
    $dept_stats = $conn->query("
        SELECT d.dept_name,
            SUM(c.status='approved') as appr,
            SUM(c.status='pending') as pend,
            SUM(c.status='rejected') as rej
        FROM departments d
        LEFT JOIN clearances c ON c.dept_id = d.id
        GROUP BY d.id
    ");
    $dept_rows = $dept_stats->fetch_all(MYSQLI_ASSOC);

    // Recent clearances
    $recent = $conn->query("
        SELECT c.*, s.full_name, s.student_id as sid, d.dept_name
        FROM clearances c
        JOIN students s ON s.id = c.student_id
        JOIN departments d ON d.id = c.dept_id
        ORDER BY c.created_at DESC LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);
}

// ---- STUDENT STATS ----
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stud = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $stud_id = $stud ? $stud['id'] : 0;

    $total_depts = $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc()['c'];
    $my_approved = $conn->prepare("SELECT COUNT(*) as c FROM clearances WHERE student_id=? AND status='approved'");
    $my_approved->bind_param("i", $stud_id); $my_approved->execute();
    $approved = $my_approved->get_result()->fetch_assoc()['c']; $my_approved->close();

    $my_pending = $conn->prepare("SELECT COUNT(*) as c FROM clearances WHERE student_id=? AND status='pending'");
    $my_pending->bind_param("i", $stud_id); $my_pending->execute();
    $pending = $my_pending->get_result()->fetch_assoc()['c']; $my_pending->close();

    $my_rejected = $conn->prepare("SELECT COUNT(*) as c FROM clearances WHERE student_id=? AND status='rejected'");
    $my_rejected->bind_param("i", $stud_id); $my_rejected->execute();
    $rejected = $my_rejected->get_result()->fetch_assoc()['c']; $my_rejected->close();

    $pct = $total_depts > 0 ? round(($approved / $total_depts) * 100) : 0;

    // Clearance tracker
    $tracker = $conn->prepare("
        SELECT c.*, d.dept_name FROM clearances c
        JOIN departments d ON d.id = c.dept_id
        WHERE c.student_id = ?
        ORDER BY d.dept_name
    ");
    $tracker->bind_param("i", $stud_id); $tracker->execute();
    $tracker_rows = $tracker->get_result()->fetch_all(MYSQLI_ASSOC); $tracker->close();

    // Depts not yet requested
    $depts_all = $conn->query("SELECT id, dept_name FROM departments ORDER BY dept_name")->fetch_all(MYSQLI_ASSOC);
    $requested_dept_ids = array_column($tracker_rows, 'dept_id');
}

// ---- OFFICER STATS ----
if ($role === 'officer') {
    $dept_q = $conn->prepare("SELECT d.* FROM departments d WHERE d.user_id = ?");
    $dept_q->bind_param("i", $user_id); $dept_q->execute();
    $my_dept = $dept_q->get_result()->fetch_assoc(); $dept_q->close();
    $dept_id = $my_dept['id'] ?? 0;

    $pending = $conn->prepare("SELECT COUNT(*) as c FROM clearances WHERE dept_id=? AND status='pending'");
    $pending->bind_param("i", $dept_id); $pending->execute();
    $pending = $pending->get_result()->fetch_assoc()['c'];

    $approved = $conn->prepare("SELECT COUNT(*) as c FROM clearances WHERE dept_id=? AND status='approved'");
    $approved->bind_param("i", $dept_id); $approved->execute();
    $approved = $approved->get_result()->fetch_assoc()['c'];

    $rejected = $conn->prepare("SELECT COUNT(*) as c FROM clearances WHERE dept_id=? AND status='rejected'");
    $rejected->bind_param("i", $dept_id); $rejected->execute();
    $rejected = $rejected->get_result()->fetch_assoc()['c'];

    // Recent requests for this dept
    $recent_officer = $conn->prepare("
        SELECT c.*, s.full_name, s.student_id as sid, s.course, s.year_level
        FROM clearances c JOIN students s ON s.id = c.student_id
        WHERE c.dept_id = ?
        ORDER BY c.created_at DESC LIMIT 8
    ");
    $recent_officer->bind_param("i", $dept_id); $recent_officer->execute();
    $recent_rows = $recent_officer->get_result()->fetch_all(MYSQLI_ASSOC); $recent_officer->close();
}

include 'includes/header.php';
?>
<div class="app-wrapper">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">

<!-- ADMIN DASHBOARD -->
<?php if ($role === 'admin'): ?>
<div class="page-header">
    <h1><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Admin Dashboard</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item active">Overview</li></ol></nav>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= $total_students ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value"><?= $pending ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $approved ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $rejected ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Clearance Status Chart -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Clearance Status</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="statusChart"></canvas></div>
            </div>
        </div>
    </div>
    <!-- Dept Bar Chart -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Department Statistics</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="deptChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Clearances -->
<div class="table-card">
    <div class="table-toolbar">
        <h5><i class="bi bi-clock-history me-2 text-primary"></i>Recent Clearance Activity</h5>
        <div class="ms-auto">
            <a href="clearances/index.php" class="btn btn-primary btn-sm">View All</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr>
                <th>Student ID</th><th>Name</th><th>Department</th><th>Date</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
                <td><span class="fw-600"><?= htmlspecialchars($r['sid']) ?></span></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['dept_name']) ?></td>
                <td><?= date('M d, Y', strtotime($r['request_date'])) ?></td>
                <td><span class="badge-status badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initDoughnutChart('statusChart',
        ['Approved','Pending','Rejected'],
        [<?= $approved ?>, <?= $pending ?>, <?= $rejected ?>],
        ['#10b981','#f59e0b','#ef4444']
    );
    initBarChart('deptChart',
        [<?= implode(',', array_map(fn($d) => '"'.addslashes($d['dept_name']).'"', $dept_rows)) ?>],
        [
            { label: 'Approved', data: [<?= implode(',', array_column($dept_rows,'appr')) ?>], backgroundColor: '#10b981' },
            { label: 'Pending',  data: [<?= implode(',', array_column($dept_rows,'pend')) ?>], backgroundColor: '#f59e0b' },
            { label: 'Rejected', data: [<?= implode(',', array_column($dept_rows,'rej'))  ?>], backgroundColor: '#ef4444' }
        ]
    );
});
</script>

<!-- STUDENT DASHBOARD -->
<?php elseif ($role === 'student'): ?>
<div class="page-header">
    <h1><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>My Dashboard</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item active">Clearance Overview</li></ol></nav>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-building-fill"></i></div>
            <div><div class="stat-value"><?= $total_depts ?></div><div class="stat-label">Departments</div></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div><div class="stat-value"><?= $approved ?></div><div class="stat-label">Approved</div></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="stat-value"><?= $pending ?></div><div class="stat-label">Pending</div></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
            <div><div class="stat-value"><?= $rejected ?></div><div class="stat-label">Rejected</div></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-speedometer2 me-2 text-primary"></i>Clearance Progress</div>
            <div class="card-body">
                <div class="clearance-progress">
                    <div class="label"><span>Overall Completion</span><span class="fw-700 text-primary"><?= $pct ?>%</span></div>
                    <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
                </div>
                <div class="mt-3">
                    <?php foreach ($tracker_rows as $tr): ?>
                    <div class="tracker-step">
                        <div class="tracker-dot <?= $tr['status'] ?>">
                            <i class="bi bi-<?= $tr['status']==='approved'?'check-lg':($tr['status']==='rejected'?'x-lg':'hourglass-split') ?>"></i>
                        </div>
                        <div class="tracker-info">
                            <div class="dept-name"><?= htmlspecialchars($tr['dept_name']) ?></div>
                            <div class="dept-status"><?= ucfirst($tr['status']) ?><?= $tr['remarks'] ? ' — '.$tr['remarks'] : '' ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <a href="clearances/my_clearance.php" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-circle me-1"></i>Manage Clearance Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Status Overview</div>
            <div class="card-body">
                <div class="chart-container"><canvas id="studentChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initDoughnutChart('studentChart',
        ['Approved','Pending','Rejected','Not Requested'],
        [<?= $approved ?>,<?= $pending ?>,<?= $rejected ?>,<?= max(0, $total_depts - count($tracker_rows)) ?>],
        ['#10b981','#f59e0b','#ef4444','#cbd5e1']
    );
});
</script>

<!-- OFFICER DASHBOARD -->
<?php elseif ($role === 'officer'): ?>
<div class="page-header">
    <h1><i class="bi bi-building-fill me-2 text-primary"></i><?= htmlspecialchars($my_dept['dept_name'] ?? 'Department') ?> Dashboard</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="stat-value"><?= $pending ?></div><div class="stat-label">Pending</div></div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div><div class="stat-value"><?= $approved ?></div><div class="stat-label">Approved</div></div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
            <div><div class="stat-value"><?= $rejected ?></div><div class="stat-label">Rejected</div></div></div>
    </div>
</div>

<div class="table-card">
    <div class="table-toolbar"><h5><i class="bi bi-clipboard2-list me-2 text-primary"></i>Recent Requests</h5>
        <div class="ms-auto"><a href="clearances/dept_clearances.php" class="btn btn-primary btn-sm">Manage All</a></div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Student ID</th><th>Name</th><th>Course</th><th>Year</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($recent_rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['sid']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['course']) ?></td>
                <td><?= htmlspecialchars($r['year_level']) ?></td>
                <td><span class="badge-status badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                    <a href="clearances/dept_clearances.php?action=approve&id=<?= $r['id'] ?>" class="btn btn-sm btn-success btn-action" title="Approve"><i class="bi bi-check-lg"></i></a>
                    <a href="clearances/dept_clearances.php?action=reject&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger btn-action" title="Reject"><i class="bi bi-x-lg"></i></a>
                    <?php else: ?>
                    <span class="text-muted small">Reviewed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>