<?php
// ============================================================
// includes/auth.php - Session & Auth Helper
// Student Clearance Processing System
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login for any page that includes this file with $require_login = true
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// Require specific role
function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        header('Location: /dashboard.php');
        exit;
    }
}

// Check if logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get current user role
function get_role() {
    return $_SESSION['role'] ?? null;
}

// Log activity
function log_activity($conn, $user_id, $action, $details = null) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

// Add notification
function add_notification($conn, $user_id, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $stmt->close();
}

// Get unread notification count
function get_unread_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['cnt'];
}

// Sanitize input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>