<?php
// reports/index.php - Admin Reports
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Reports';
$base_path = '../';

$filter_dept = $_GET['dept'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where = "WHERE 1=1";
$params = []; $types = '';
if ($filter_dept && is_numeric($filter_dept)) { $where .= " AND c.dept_id=?"; $params[] = (int)$filter_dept; $types .= 'i'; }
if ($filter_status) { $where .= " AND c.status=?"; $params[] = $filter_status; $types .= 's'; }

$sql = "SELECT s.student_id as sid, s.full_name, s.course, s.year_level,
        d.dept_name,
        c.request_date, c.status, c.remarks, c.reviewed_at
        FROM clearances c
        JOIN students s ON s.id=c.student_id
        JOIN departments d ON d.id=c.dept_id
        $where
        ORDER BY s.full_name, d.dept_name";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name")->fetch_all(MYSQLI_ASSOC);

// Stats
$stats = [
    'total' => count($rows),
    'approved' => count(array_filter($rows, fn($r) => $r['status'] === 'approved')),
    'pending'  => count(array_filter($rows, fn($r) => $r['status'] === 'pending')),
    'rejected' => count(array_filter($rows, fn($r) => $r['status'] === 'rejected')),
];

include '../includes/header.php';
?>
<div class="app-wrapper">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

<div class="page-header d-flex justify-content-between align-items-center no-print">
    <div>
        <h1><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Reports</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol></nav>
    </div>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print Report
    </button>
</div>

<!-- Print Header -->
<div class="print-header mb-4">
    <div class="text-center">
        <h3 style="font-family:'Space Grotesk',sans-serif">STUDENT CLEARANCE REPORT</h3>
        <p class="text-muted">Generated: <?= date('F d, Y h:i A') ?></p>
        <?php if ($filter_dept): ?>
        <p><?= htmlspecialchars($departments[array_search($filter_dept, array_column($departments,'id'))]['dept_name'] ?? '') ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Filters (no print) -->
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Department</label>
                <select name="dept" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filter_dept==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $filter_status==='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $filter_status==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm">Apply</button>
                <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3 no-print">
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-clipboard2-data-fill"></i></div>
            <div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total Records</div></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div><div class="stat-value"><?= $stats['approved'] ?></div><div class="stat-label">Approved</div></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending</div></div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
            <div><div class="stat-value"><?= $stats['rejected'] ?></div><div class="stat-label">Rejected</div></div></div>
    </div>
</div>

<div class="table-card">
    <div class="table-toolbar no-print">
        <h5><i class="bi bi-table me-2 text-primary"></i>Clearance Report <span class="badge bg-primary ms-1"><?= count($rows) ?></span></h5>
        <div class="ms-auto"><div class="search-box"><i class="bi bi-search"></i>
            <input type="text" id="tableSearch" class="form-control" placeholder="Search..."></div></div>
    </div>
    <div class="table-responsive">
        <table class="table" id="dataTable">
            <thead><tr>
                <th>Student ID</th><th>Name</th><th>Course</th><th>Year</th>
                <th>Department</th><th>Request Date</th><th>Status</th><th>Remarks</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td class="fw-600"><?= htmlspecialchars($r['sid']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['course']) ?></td>
                <td><?= htmlspecialchars($r['year_level']) ?></td>
                <td><?= htmlspecialchars($r['dept_name']) ?></td>
                <td><?= date('M d, Y', strtotime($r['request_date'])) ?></td>
                <td><span class="badge-status badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td><?= htmlspecialchars($r['remarks'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Credits footer for print -->
<div class="mt-4" style="font-size:.78rem;color:#64748b;text-align:center">
    <em>Developed by <strong>Gideon Agtas</strong></em>
</div>

<?php include '../includes/footer.php'; ?>