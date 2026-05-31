<?php
// clearances/index.php - Clearance Management (Admin)
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Clearances';
$base_path = '../';

// --- DELETE ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM clearances WHERE id=?");
    $stmt->bind_param("i", $del_id); $stmt->execute(); $stmt->close();
    header('Location: index.php?success='.urlencode('Clearance record deleted.'));
    exit;
}

// --- APPROVE/REJECT quick actions ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $act = $_GET['action'];
    $cid = (int)$_GET['id'];
    if (in_array($act, ['approve','reject'])) {
        $new_status = $act === 'approve' ? 'approved' : 'rejected';
        $upd = $conn->prepare("UPDATE clearances SET status=?, reviewed_at=NOW() WHERE id=?");
        $upd->bind_param("si", $new_status, $cid); $upd->execute(); $upd->close();

        // Notify student
        $cinfo = $conn->prepare("SELECT c.*, s.user_id, d.dept_name FROM clearances c JOIN students s ON s.id=c.student_id JOIN departments d ON d.id=c.dept_id WHERE c.id=?");
        $cinfo->bind_param("i", $cid); $cinfo->execute();
        $cdata = $cinfo->get_result()->fetch_assoc(); $cinfo->close();
        if ($cdata) {
            $msg = "Your clearance for {$cdata['dept_name']} has been " . strtoupper($new_status) . ".";
            add_notification($conn, $cdata['user_id'], $msg);
        }
        log_activity($conn, $_SESSION['user_id'], ucfirst($new_status).' Clearance', "Clearance ID: $cid");
        header('Location: index.php?success='.urlencode('Clearance '.ucfirst($new_status).' successfully.'));
        exit;
    }
}

// --- ADD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add') {
    $student_id = (int)$_POST['student_id'];
    $dept_id    = (int)$_POST['dept_id'];
    $status     = clean($_POST['status']);
    $remarks    = clean($_POST['remarks']);
    $req_date   = clean($_POST['request_date']);

    // Check duplicate
    $chk = $conn->prepare("SELECT id FROM clearances WHERE student_id=? AND dept_id=?");
    $chk->bind_param("ii", $student_id, $dept_id); $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        header('Location: index.php?error='.urlencode('Clearance request already exists for this student/department.'));
        exit;
    }
    $chk->close();

    $ins = $conn->prepare("INSERT INTO clearances (student_id, dept_id, request_date, status, remarks) VALUES (?,?,?,?,?)");
    $ins->bind_param("iisss", $student_id, $dept_id, $req_date, $status, $remarks);
    $ins->execute(); $ins->close();
    header('Location: index.php?success='.urlencode('Clearance added.'));
    exit;
}

// Filters
$filter_status = $_GET['status'] ?? '';
$filter_dept   = $_GET['dept'] ?? '';
$where = "WHERE 1=1";
$params = [];
$types = '';
if ($filter_status) { $where .= " AND c.status=?"; $params[] = $filter_status; $types .= 's'; }
if ($filter_dept && is_numeric($filter_dept)) { $where .= " AND c.dept_id=?"; $params[] = (int)$filter_dept; $types .= 'i'; }

$sql = "SELECT c.*, s.full_name, s.student_id as sid, s.course, d.dept_name
        FROM clearances c
        JOIN students s ON s.id=c.student_id
        JOIN departments d ON d.id=c.dept_id
        $where ORDER BY c.created_at DESC";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $clearances = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $clearances = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$students   = $conn->query("SELECT * FROM students ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$departments= $conn->query("SELECT * FROM departments ORDER BY dept_name")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>
<div class="app-wrapper">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1><i class="bi bi-clipboard2-check-fill me-2 text-primary"></i>Clearance Management</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Clearances</li>
        </ol></nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i>Add Clearance
    </button>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $filter_status==='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $filter_status==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Department</label>
                <select name="dept" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filter_dept==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="index.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <h5><i class="bi bi-table me-2 text-primary"></i>Clearance Records <span class="badge bg-primary ms-1"><?= count($clearances) ?></span></h5>
        <div class="ms-auto"><div class="search-box"><i class="bi bi-search"></i>
            <input type="text" id="tableSearch" class="form-control" placeholder="Search..."></div></div>
    </div>
    <div class="table-responsive">
        <table class="table" id="dataTable">
            <thead><tr>
                <th>#</th><th>Student ID</th><th>Name</th><th>Course</th><th>Department</th>
                <th>Date</th><th>Status</th><th>Remarks</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($clearances as $i => $c): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><span class="fw-600 text-primary"><?= htmlspecialchars($c['sid']) ?></span></td>
                <td><?= htmlspecialchars($c['full_name']) ?></td>
                <td><?= htmlspecialchars($c['course']) ?></td>
                <td><?= htmlspecialchars($c['dept_name']) ?></td>
                <td><?= date('M d, Y', strtotime($c['request_date'])) ?></td>
                <td><span class="badge-status badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                <td><?= htmlspecialchars($c['remarks'] ?? '-') ?></td>
                <td>
                    <?php if ($c['status'] === 'pending'): ?>
                    <a href="?action=approve&id=<?= $c['id'] ?>" class="btn btn-sm btn-success btn-action" title="Approve"><i class="bi bi-check-lg"></i></a>
                    <a href="?action=reject&id=<?= $c['id'] ?>" class="btn btn-sm btn-warning btn-action" title="Reject"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                    <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger btn-action btn-delete" title="Delete"><i class="bi bi-trash-fill"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard2-plus me-2"></i>Add Clearance Record</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student *</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['student_id'].' - '.$s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department *</label>
                        <select name="dept_id" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['dept_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Request Date *</label>
                        <input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>