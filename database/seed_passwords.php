<?php
/**
 * Password Seeder - Run this ONCE after importing clearance_db.sql
 * This updates all user passwords with properly hashed bcrypt values.
 * 
 * Usage: php seed_passwords.php  OR  access via browser: http://localhost/clearance/database/seed_passwords.php
 */

require_once __DIR__ . '/../includes/db.php';

$users = [
    ['username' => 'admin',             'password' => 'Admin@2025'],
    ['username' => 'library_officer',   'password' => 'Officer@2025'],
    ['username' => 'registrar_officer', 'password' => 'Officer@2025'],
    ['username' => 'finance_officer',   'password' => 'Officer@2025'],
    ['username' => 'guidance_officer',  'password' => 'Officer@2025'],
    ['username' => 'it_officer',        'password' => 'Officer@2025'],
    ['username' => 'clinic_officer',    'password' => 'Officer@2025'],
    ['username' => 'sao_officer',       'password' => 'Officer@2025'],
    ['username' => 'john_dela_cruz',    'password' => 'Student@2025'],
    ['username' => 'maria_santos',      'password' => 'Student@2025'],
    ['username' => 'carlos_garcia',     'password' => 'Student@2025'],
    ['username' => 'anna_reyes',        'password' => 'Student@2025'],
    ['username' => 'pedro_bautista',    'password' => 'Student@2025'],
    ['username' => 'rosa_mendoza',      'password' => 'Student@2025'],
    ['username' => 'miguel_torres',     'password' => 'Student@2025'],
    ['username' => 'elena_villanueva',  'password' => 'Student@2025'],
];

echo "<h2>Password Seeder</h2><pre>";
$success = 0;
foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 10]);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hash, $u['username']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "✅ Updated: {$u['username']} -> {$u['password']}\n";
        $success++;
    } else {
        echo "⚠️  Not found or unchanged: {$u['username']}\n";
    }
    $stmt->close();
}
echo "\nDone! Updated $success users.\n";
echo "\n--- Demo Login Credentials ---\n";
echo "Admin:    admin / Admin@2025\n";
echo "Officers: library_officer / Officer@2025\n";
echo "Students: john_dela_cruz / Student@2025\n";
echo "</pre>";
echo "<p><a href='../login.php'>Go to Login</a></p>";
