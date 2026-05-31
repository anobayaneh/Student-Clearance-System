<?php
// Run this file ONCE to see the correct hashes for your passwords
// Then copy them into clearance_db.sql if needed

$passwords = [
    'Admin@2025'   => 'admin',
    'Officer@2025' => 'all officers',
    'Student@2025' => 'all students',
];

echo "<pre>\n";
foreach ($passwords as $pw => $label) {
    $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 10]);
    echo "$label ($pw):\n$hash\n\n";
}

// Also show verification
echo "--- Verify ---\n";
$testHash = password_hash('Admin@2025', PASSWORD_BCRYPT);
echo "Verify Admin@2025: " . (password_verify('Admin@2025', $testHash) ? 'OK' : 'FAIL') . "\n";
echo "</pre>";
