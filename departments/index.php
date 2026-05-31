<?php
// departments/index.php - Department Management
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Departments';
$base_path = '../';

// --- DELETE ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM departments WHERE id=?");
    $stmt->bind_param("i", $del_id); $stmt->execute(); $stmt->close();
    log_activity($conn, $_SESSION['user_id'], 'Delete Department', "Deleted dept ID: $del_id");
    header('Location: index.php?success='.urlencode('Department deleted.'));
    exit;
}

// --- ADD / EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $dept_name   = clean($_POST['dept_name']);
    $officer_name= clean($_POST['officer_name']);
    $dept_email  = clean($_POST['dept_email']);
    $edit_id     = (int)($_POST['edit_id'] ?? 0);
    $user_id_link= (int)($_POST['user_id_link'] ?? 0);

    if ($action === 'add') {
        $ins = $conn->prepare("INSERT INTO departments (dept_name, officer_name, dept_email, user_id) VALUES (?,?,?,?)");
        $uid_val = $user_id_link ?: null;
        $ins->bind_param("sssi", $dept_name, $officer_name, $dept_email, $uid_val);
        $ins->execute(); $ins->close();
        log_activity($conn, $_SESSION['user_id'], 'Add Department', "Added: $dept_name");
        header('Location: index.php?success='.urlencode('Department added.'));
        exit;
    }
    if ($action === 'edit') {
        $upd = $conn->prepare("UPDATE departments SET dept_name=?, officer_name=?, dept_email=? WHERE id=?");
        $upd->bind_param("sssi", $dept_name, $officer_name, $dept_email, $edit_id);
        $upd->execute(); $upd->close();
        log_activity($conn, $_SESSION['user_id'], 'Edit Department', "Edited dept ID: $edit_id");
        header('Location: index.php?success='.urlencode('Department updated.'));
        exit;
    }
}

// Edit fetch
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $eq = $conn->prepare("SELECT * FROM departments WHERE id=?");
    $eq->bind_param("i", (int)$_GET['edit']); $eq->execute();
    $edit_data = $eq->get_result()->fetch_assoc(); $eq->close();
}

$depts = $conn->query("SELECT d.*, u.username FROM departments d LEFT JOIN users u ON u.id=d.user_id ORDER BY d.dept_name")->fetch_all(MYSQLI_ASSOC);
$officers = $conn->query("SELECT * FROM users WHERE role='officer' ORDER BY username")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>
<div class="app-wrapper">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1><i class="bi bi-building-fill me-2 text-primary"></i>Department Management</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Departments</li>
        </ol></nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deptModal">
        <i class="bi bi-plus-circle me-1"></i>Add Department
    </button>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <h5><i class="bi bi-table me-2 text-primary"></i>Departments <span class="badge bg-primary ms-1"><?= count($depts) ?></span></h5>
        <div class="ms-auto"><div class="search-box"><i class="bi bi-search"></i>
            <input type="text" id="tableSearch" class="form-control" placeholder="Search..."></div></div>
    </div>
    <div class="table-responsive">
        <table class="table" id="dataTable">
            <thead><tr><th>#</th><th>Department</th><th>Officer</th><th>Email</th><th>Account</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($depts as $i => $d): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><span class="fw-600"><?= htmlspecialchars($d['dept_name']) ?></span></td>
                <td><?= htmlspecialchars($d['officer_name']) ?></td>
                <td><?= htmlspecialchars($d['dept_email']) ?></td>
                <td><?= $d['username'] ? '<span class="badge bg-success-subtle text-success">'.htmlspecialchars($d['username']).'</span>' : '<span class="text-muted small">None</span>' ?></td>
                <td>
                    <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-warning btn-action"><i class="bi bi-pencil-fill"></i></a>
                    <a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-danger btn-action btn-delete"><i class="bi bi-trash-fill"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-building me-2"></i><?= $edit_data ? 'Edit' : 'Add' ?> Department</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?><input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Department Name *</label>
                        <input type="text" name="dept_name" class="form-control" required value="<?= htmlspecialchars($edit_data['dept_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Officer Name *</label>
                        <input type="text" name="officer_name" class="form-control" required value="<?= htmlspecialchars($edit_data['officer_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Email *</label>
                        <input type="email" name="dept_email" class="form-control" required value="<?= htmlspecialchars($edit_data['dept_email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link Officer Account</label>
                        <select name="user_id_link" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach ($officers as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= ($edit_data['user_id']??'')==$o['id']?'selected':'' ?>><?= htmlspecialchars($o['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

<?php if ($edit_data): ?>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('deptModal')).show());</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>