<?php
// clearances/my_clearance.php - Student clearance view and request
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('student');

$page_title = 'My Clearance';
$base_path = '../';
$user_id = $_SESSION['user_id'];

// Get student record
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$student) {
    die('<div class="alert alert-danger m-4">Student record not found for your account. Please contact admin.</div>');
}

$stud_id = $student['id'];

// --- SUBMIT REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'submit') {
    $dept_id = (int)$_POST['dept_id'];
    // Check if already requested
    $chk = $conn->prepare("SELECT id FROM clearances WHERE student_id=? AND dept_id=?");
    $chk->bind_param("ii", $stud_id, $dept_id); $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        header('Location: my_clearance.php?error='.urlencode('You already have a request for this department.'));
        exit;
    }
    $chk->close();
    $today = date('Y-m-d');
    $ins = $conn->prepare("INSERT INTO clearances (student_id, dept_id, request_date, status) VALUES (?,?,?,'pending')");
    $ins->bind_param("iis", $stud_id, $dept_id, $today);
    $ins->execute(); $ins->close();
    log_activity($conn, $user_id, 'Submit Clearance Request', "Dept ID: $dept_id");
    header('Location: my_clearance.php?success='.urlencode('Clearance request submitted!'));
    exit;
}

// --- DELETE REQUEST (only pending) ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM clearances WHERE id=? AND student_id=? AND status='pending'");
    $stmt->bind_param("ii", $del, $stud_id); $stmt->execute(); $stmt->close();
    header('Location: my_clearance.php?success='.urlencode('Request cancelled.'));
    exit;
}

// Fetch all departments and clearance status
$all_depts = $conn->query("SELECT * FROM departments ORDER BY dept_name")->fetch_all(MYSQLI_ASSOC);
$my_clears = $conn->prepare("SELECT c.*, d.dept_name FROM clearances c JOIN departments d ON d.id=c.dept_id WHERE c.student_id=?");
$my_clears->bind_param("i", $stud_id); $my_clears->execute();
$clearances = $my_clears->get_result()->fetch_all(MYSQLI_ASSOC); $my_clears->close();

$cleared_dept_ids = array_column($clearances, 'dept_id');
$total_depts = count($all_depts);
$approved_count = count(array_filter($clearances, fn($c) => $c['status'] === 'approved'));
$pct = $total_depts > 0 ? round(($approved_count / $total_depts) * 100) : 0;

include '../includes/header.php';
?>
<div class="app-wrapper">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

<div class="page-header">
    <h1><i class="bi bi-clipboard2-check-fill me-2 text-primary"></i>My Clearance</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">My Clearance</li>
    </ol></nav>
</div>

<!-- Student Info Card -->
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
            <div class="user-avatar" style="width:50px;height:50px;font-size:1.2rem;border-radius:12px;background:var(--primary)">
                <?= strtoupper(substr($student['full_name'],0,1)) ?>
            </div>
            <div>
                <div class="fw-700" style="font-size:1rem"><?= htmlspecialchars($student['full_name']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($student['student_id']) ?> &bull; <?= htmlspecialchars($student['course']) ?> &bull; <?= htmlspecialchars($student['year_level']) ?></div>
            </div>
            <div class="ms-auto text-end">
                <div class="fw-700 text-primary" style="font-size:1.4rem"><?= $pct ?>%</div>
                <div class="text-muted small">Cleared</div>
            </div>
        </div>
        <div class="progress mt-3" style="height:8px">
            <div class="progress-bar" style="width:<?= $pct ?>%"></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Clearance Tracker -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-check me-2 text-primary"></i>Clearance Status per Department</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Department</th><th>Status</th><th>Remarks</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($all_depts as $dept):
                        $clear = null;
                        foreach ($clearances as $c) { if ($c['dept_id'] == $dept['id']) { $clear = $c; break; } }
                    ?>
                    <tr>
                        <td class="fw-600"><?= htmlspecialchars($dept['dept_name']) ?></td>
                        <td>
                            <?php if ($clear): ?>
                            <span class="badge-status badge-<?= $clear['status'] ?>"><?= ucfirst($clear['status']) ?></span>
                            <?php else: ?>
                            <span class="badge-status" style="background:#f1f5f9;color:#64748b">Not Requested</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= $clear ? htmlspecialchars($clear['remarks'] ?? '-') : '-' ?></td>
                        <td>
                            <?php if (!$clear): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="submit">
                                <input type="hidden" name="dept_id" value="<?= $dept['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm" style="font-size:.75rem;padding:3px 10px">
                                    <i class="bi bi-send me-1"></i>Request
                                </button>
                            </form>
                            <?php elseif ($clear['status'] === 'pending'): ?>
                            <a href="?delete=<?= $clear['id'] ?>" class="btn btn-danger btn-sm btn-delete" style="font-size:.75rem;padding:3px 10px">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Summary</div>
            <div class="card-body">
                <div class="chart-container mb-3"><canvas id="summaryChart"></canvas></div>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 rounded" style="background:#d1fae5">
                            <div class="fw-700 text-success"><?= $approved_count ?></div>
                            <div style="font-size:.72rem;color:#065f46">Approved</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded" style="background:#fef3c7">
                            <div class="fw-700 text-warning"><?= count(array_filter($clearances, fn($c)=>$c['status']==='pending')) ?></div>
                            <div style="font-size:.72rem;color:#92400e">Pending</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded" style="background:#fee2e2">
                            <div class="fw-700 text-danger"><?= count(array_filter($clearances, fn($c)=>$c['status']==='rejected')) ?></div>
                            <div style="font-size:.72rem;color:#991b1b">Rejected</div>
                        </div>
                    </div>
                </div>
                <?php if ($pct >= 100): ?>
                <div class="alert alert-success mt-3 py-2 mb-0 text-center">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <strong>Fully Cleared!</strong><br>
                    <a href="../reports/my_report.php" class="btn btn-success btn-sm mt-2">
                        <i class="bi bi-printer me-1"></i>Print Clearance
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var appr = <?= count(array_filter($clearances, fn($c)=>$c['status']==='approved')) ?>;
    var pend = <?= count(array_filter($clearances, fn($c)=>$c['status']==='pending')) ?>;
    var rej  = <?= count(array_filter($clearances, fn($c)=>$c['status']==='rejected')) ?>;
    var none = <?= max(0, $total_depts - count($clearances)) ?>;
    initDoughnutChart('summaryChart',
        ['Approved','Pending','Rejected','Not Requested'],
        [appr,pend,rej,none],
        ['#10b981','#f59e0b','#ef4444','#cbd5e1']
    );
});
</script>

<?php include '../includes/footer.php'; ?>