<?php
// students/index.php - Student Management
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin');

$page_title = 'Students';
$base_path = '../';
$msg = '';
$msg_type = 'success';

// --- DELETE ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Get user_id to also delete user
    $s = $conn->prepare("SELECT user_id FROM students WHERE id=?");
    $s->bind_param("i", $del_id); $s->execute();
    $sr = $s->get_result()->fetch_assoc(); $s->close();
    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param("i", $del_id); $stmt->execute(); $stmt->close();
    if ($sr && $sr['user_id']) {
        $du = $conn->prepare("DELETE FROM users WHERE id=?");
        $du->bind_param("i", $sr['user_id']); $du->execute(); $du->close();
    }
    log_activity($conn, $_SESSION['user_id'], 'Delete Student', "Deleted student ID: $del_id");
    header('Location: index.php?success='.urlencode('Student deleted successfully.'));
    exit;
}

// --- ADD / EDIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $full_name  = clean($_POST['full_name']);
    $student_id = clean($_POST['student_id']);
    $course     = clean($_POST['course']);
    $year_level = clean($_POST['year_level']);
    $email      = clean($_POST['email']);
    $password   = $_POST['password'] ?? '';
    $edit_id    = (int)($_POST['edit_id'] ?? 0);

    if ($action === 'add') {
        // Create user account
        $hashed = password_hash($password ?: 'student123', PASSWORD_DEFAULT);
        $uname = strtolower(str_replace(' ', '_', $full_name));
        $cu = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?,?,?,'student')");
        $cu->bind_param("sss", $uname, $email, $hashed); $cu->execute();
        $new_uid = $conn->insert_id; $cu->close();

        $ins = $conn->prepare("INSERT INTO students (student_id, full_name, course, year_level, email, user_id) VALUES (?,?,?,?,?,?)");
        $ins->bind_param("sssssi", $student_id, $full_name, $course, $year_level, $email, $new_uid);
        $ins->execute(); $ins->close();
        log_activity($conn, $_SESSION['user_id'], 'Add Student', "Added: $full_name");
        header('Location: index.php?success='.urlencode('Student added successfully.'));
        exit;
    }

    if ($action === 'edit') {
        $upd = $conn->prepare("UPDATE students SET student_id=?, full_name=?, course=?, year_level=?, email=? WHERE id=?");
        $upd->bind_param("sssssi", $student_id, $full_name, $course, $year_level, $email, $edit_id);
        $upd->execute(); $upd->close();
        // Update email in users table
        $get_uid = $conn->prepare("SELECT user_id FROM students WHERE id=?");
        $get_uid->bind_param("i", $edit_id); $get_uid->execute();
        $uid_res = $get_uid->get_result()->fetch_assoc(); $get_uid->close();
        if ($uid_res && $uid_res['user_id']) {
            $ue = $conn->prepare("UPDATE users SET email=? WHERE id=?");
            $ue->bind_param("si", $email, $uid_res['user_id']); $ue->execute(); $ue->close();
        }
        log_activity($conn, $_SESSION['user_id'], 'Edit Student', "Edited: $full_name");
        header('Location: index.php?success='.urlencode('Student updated successfully.'));
        exit;
    }
}

// Fetch edit record
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $eq = $conn->prepare("SELECT * FROM students WHERE id=?");
    $eq->bind_param("i", (int)$_GET['edit']); $eq->execute();
    $edit_data = $eq->get_result()->fetch_assoc(); $eq->close();
}

// Fetch all students
$students = $conn->query("SELECT * FROM students ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>
<div class="app-wrapper">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1><i class="bi bi-people-fill me-2 text-primary"></i>Student Management</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Students</li>
        </ol></nav>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal">
        <i class="bi bi-plus-circle me-1"></i>Add Student
    </button>
</div>

<div class="table-card">
    <div class="table-toolbar">
        <h5><i class="bi bi-table me-2 text-primary"></i>Students List <span class="badge bg-primary ms-1"><?= count($students) ?></span></h5>
        <div class="ms-auto d-flex gap-2">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="tableSearch" class="form-control" placeholder="Search students...">
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" id="dataTable">
            <thead><tr>
                <th>#</th><th>Student ID</th><th>Full Name</th><th>Course</th><th>Year</th><th>Email</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php if (empty($students)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr>
            <?php endif; ?>
            <?php foreach ($students as $i => $s): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><span class="fw-600 text-primary"><?= htmlspecialchars($s['student_id']) ?></span></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['course']) ?></td>
                <td><?= htmlspecialchars($s['year_level']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td>
                    <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-warning btn-action" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                    <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger btn-action btn-delete" title="Delete"><i class="bi bi-trash-fill"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add/Edit Student -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i><?= $edit_data ? 'Edit Student' : 'Add Student' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?><input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>"><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Student ID *</label>
                            <input type="text" name="student_id" class="form-control" required
                                value="<?= htmlspecialchars($edit_data['student_id'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required
                                value="<?= htmlspecialchars($edit_data['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course *</label>
                            <select name="course" class="form-select" required>
                                <?php foreach (['BSIT','BSCS','BSA','BSECE','BSEd','BSBA','BSN','BSCE'] as $c): ?>
                                <option value="<?=$c?>" <?= ($edit_data['course']??'')===$c?'selected':'' ?>><?=$c?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year Level *</label>
                            <select name="year_level" class="form-select" required>
                                <?php foreach (['1st Year','2nd Year','3rd Year','4th Year'] as $y): ?>
                                <option value="<?=$y?>" <?= ($edit_data['year_level']??'')===$y?'selected':'' ?>><?=$y?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required
                                value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>">
                        </div>
                        <?php if (!$edit_data): ?>
                        <div class="col-12">
                            <label class="form-label">Password <small class="text-muted">(default: student123)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank for default">
                        </div>
                        <?php endif; ?>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>