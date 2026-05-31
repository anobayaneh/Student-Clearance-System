<?php
// reports/my_report.php - Printable student clearance
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('student');

$page_title = 'Print Clearance';
$base_path = '../';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM students WHERE user_id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$student = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$student) { die('<p>Student not found.</p>'); }
$stud_id = $student['id'];

$clears = $conn->prepare("SELECT c.*, d.dept_name, d.officer_name FROM clearances c JOIN departments d ON d.id=c.dept_id WHERE c.student_id=? ORDER BY d.dept_name");
$clears->bind_param("i", $stud_id); $clears->execute();
$clearances = $clears->get_result()->fetch_all(MYSQLI_ASSOC); $clears->close();

$total_depts = $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc()['c'];
$approved_count = count(array_filter($clearances, fn($c) => $c['status'] === 'approved'));
$fully_cleared = ($approved_count === $total_depts);

include '../includes/header.php';
?>
<div class="app-wrapper">
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">

<div class="page-header d-flex justify-content-between align-items-center no-print">
    <h1><i class="bi bi-printer-fill me-2 text-primary"></i>Print Clearance</h1>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<!-- Printable Clearance Certificate -->
<div class="card" style="max-width:750px;margin:0 auto">
    <div class="card-body p-5">
        <!-- Header -->
        <div class="text-center mb-4">
            <div style="font-size:2rem;color:var(--primary);margin-bottom:6px"><i class="bi bi-mortarboard-fill"></i></div>
            <h3 style="font-family:'Space Grotesk',sans-serif;font-weight:800;margin:0">UNIVERSITY CLEARANCE FORM</h3>
            <p class="text-muted" style="font-size:.85rem">Academic Year 2024–2025 | ClearanceMS</p>
            <div style="height:3px;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:2px;margin:12px auto;width:80px"></div>
        </div>

        <!-- Student Info -->
        <div class="row mb-4" style="border:1px solid #e2e8f0;border-radius:10px;padding:16px">
            <div class="col-6">
                <div class="mb-2"><span class="text-muted small">Student ID</span><br><strong><?= htmlspecialchars($student['student_id']) ?></strong></div>
                <div class="mb-2"><span class="text-muted small">Full Name</span><br><strong><?= htmlspecialchars($student['full_name']) ?></strong></div>
            </div>
            <div class="col-6">
                <div class="mb-2"><span class="text-muted small">Course</span><br><strong><?= htmlspecialchars($student['course']) ?></strong></div>
                <div class="mb-2"><span class="text-muted small">Year Level</span><br><strong><?= htmlspecialchars($student['year_level']) ?></strong></div>
            </div>
            <div class="col-12 mt-1">
                <div><span class="text-muted small">Date Printed</span><br><strong><?= date('F d, Y') ?></strong></div>
            </div>
        </div>

        <!-- Clearance Table -->
        <table class="table table-bordered" style="font-size:.85rem">
            <thead style="background:#f8fafc">
                <tr>
                    <th>Department / Office</th>
                    <th>Officer</th>
                    <th class="text-center">Status</th>
                    <th>Remarks</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clearances as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['dept_name']) ?></strong></td>
                <td><?= htmlspecialchars($c['officer_name']) ?></td>
                <td class="text-center">
                    <span class="badge-status badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span>
                </td>
                <td><?= htmlspecialchars($c['remarks'] ?? '-') ?></td>
                <td><?= $c['reviewed_at'] ? date('M d, Y', strtotime($c['reviewed_at'])) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Overall Status -->
        <div class="text-center mt-4 p-3 rounded" style="background:<?= $fully_cleared ? '#d1fae5' : '#fef3c7' ?>">
            <strong style="font-size:1.1rem;color:<?= $fully_cleared ? '#065f46' : '#92400e' ?>">
                <i class="bi bi-<?= $fully_cleared ? 'check-circle-fill' : 'hourglass-split' ?> me-2"></i>
                <?= $fully_cleared ? 'FULLY CLEARED' : "IN PROGRESS ($approved_count / $total_depts departments cleared)" ?>
            </strong>
        </div>

        <!-- Signature Line -->
        <?php if ($fully_cleared): ?>
        <div class="row mt-5 text-center">
            <div class="col-6">
                <div style="border-top:1px solid #000;padding-top:8px;margin-top:40px">
                    <strong><?= htmlspecialchars($student['full_name']) ?></strong><br>
                    <small>Student Signature over Printed Name</small>
                </div>
            </div>
            <div class="col-6">
                <div style="border-top:1px solid #000;padding-top:8px;margin-top:40px">
                    <strong>Registrar / Authorized Signatory</strong><br>
                    <small>Signature over Printed Name</small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer Credits -->
        <div class="text-center mt-4" style="font-size:.72rem;color:#94a3b8">
            Generated by ClearanceMS &bull;
            Developed by <strong style="color:var(--primary)">Gideon Agtas</strong>
        </div>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>