<?php
// clearances/dept_clearances.php - Officer view
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('officer');

$page_title = 'Clearance Requests';
$base_path = '../';
$user_id = $_SESSION['user_id'];

// Get officer's department
$dq = $conn->prepare("SELECT * FROM departments WHERE user_id=?");
$dq->bind_param("i", $user_id); $dq->execute();
$my_dept = $dq->get_result()->fetch_assoc(); $dq->close();

if (!$my_dept) {
    echo '<div class="alert alert-warning m-4">No department linked to your account. Contact admin.</div>';
    exit;
}
$dept_id = $my_dept['id'];

// Approve/Reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $act = $_GET['action'];
    $cid = (int)$_GET['id'];
    if (in_array($act, ['approve','reject'])) {
        $new_status = $act === 'approve' ? 'approved' : 'rejected';
        $remarks = clean($_GET['remarks'] ?? '');
        $upd = $conn->prepare("UPDATE clearances SET status=?, remarks=?, reviewed_at=NOW() WHERE id=? AND dept_id=?");
        $upd->bind_param("ssii", $new_status, $remarks, $cid, $dept_id); $upd->execute(); $upd->close();
        // Notify student
        $ci = $conn->prepare("SELECT c.*, s.user_id FROM clearances c JOIN students s ON s.id=c.student_id WHERE c.id=?");
        $ci->bind_param("i", $cid); $ci->execute();
        $cd = $ci->get_result()->fetch_assoc(); $ci->close();
        if ($cd) add_notification($conn, $cd['user_id'], "Your clearance for {$my_dept['dept_name']} has been ".strtoupper($new_status).".");
        log_activity($conn, $user_id, ucfirst($new_status).' Clearance', "Clearance ID: $cid");
        header('Location: dept_clearances.php?success='.urlencode('Clearance '.ucfirst($new_status).'.'));
        exit;
    }
}

$filter = $_GET['filter'] ?? '';
$where = "WHERE c.dept_id=?";
$params = [$dept_id];
$types = 'i';
if ($filter) { $where .= " AND c.status=?"; $params[] = $filter; $types .= 's'; }

$sql = "SELECT c.*, s.full_name, s.student_id as sid, s.course, s.year_level, s.email
        FROM clearances c JOIN students s ON s.id=c.student_id
        $where ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params); $stmt->execute();
$clearances = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

include '../includes/header.php';
?>
<div class="app-wrapper">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

<div class="page-header">
    <h1><i class="bi bi-clipboard2-check-fill me-2 text-primary"></i><?= htmlspecialchars($my_dept['dept_name']) ?> — Clearance Requests</h1>
</div>

<!-- Filter tabs -->
<div class="mb-3">
    <a href="dept_clearances.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <a href="?filter=pending" class="btn btn-sm <?= $filter==='pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Pending</a>
    <a href="?filter=approved" class="btn btn-sm <?= $filter==='approved' ? 'btn-success' : 'btn-outline-success' ?>">Approved</a>
    <a href="?filter=rejected" class="btn btn-sm <?= $filter==='rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Rejected</a>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <h5><i class="bi bi-table me-2 text-primary"></i>Requests <span class="badge bg-primary ms-1"><?= count($clearances) ?></span></h5>
        <div class="ms-auto"><div class="search-box"><i class="bi bi-search"></i>
            <input type="text" id="tableSearch" class="form-control" placeholder="Search..."></div></div>
    </div>
    <div class="table-responsive">
        <table class="table" id="dataTable">
            <thead><tr><th>#</th><th>Student ID</th><th>Name</th><th>Course</th><th>Year</th><th>Date</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($clearances as $i => $c): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td class="fw-600 text-primary"><?= htmlspecialchars($c['sid']) ?></td>
                <td><?= htmlspecialchars($c['full_name']) ?></td>
                <td><?= htmlspecialchars($c['course']) ?></td>
                <td><?= htmlspecialchars($c['year_level']) ?></td>
                <td><?= date('M d, Y', strtotime($c['request_date'])) ?></td>
                <td><span class="badge-status badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                <td><?= htmlspecialchars($c['remarks'] ?? '-') ?></td>
                <td>
                    <?php if ($c['status'] === 'pending'): ?>
                    <a href="?action=approve&id=<?= $c['id'] ?>" class="btn btn-sm btn-success btn-action" title="Approve"><i class="bi bi-check-lg"></i></a>
                    <button class="btn btn-sm btn-danger btn-action" title="Reject"
                        onclick="rejectClearance(<?= $c['id'] ?>)"><i class="bi bi-x-lg"></i></button>
                    <?php else: ?>
                    <span class="text-muted small">Done</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#ef4444">
                <h5 class="modal-title text-white">Reject Clearance</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Remarks / Reason</label>
                <textarea id="rejectRemarks" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm" onclick="confirmReject()">Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
let rejectId = null;
function rejectClearance(id) {
    rejectId = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
function confirmReject() {
    const remarks = encodeURIComponent(document.getElementById('rejectRemarks').value);
    window.location.href = `dept_clearances.php?action=reject&id=${rejectId}&remarks=${remarks}`;
}
</script>

<?php include '../includes/footer.php'; ?>