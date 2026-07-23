<?php
/**
 * MA Logistics ERP — Production Fix Script
 * =========================================
 * Run this ONCE on production via Hostinger File Manager or SSH:
 *   php fix_production.php
 *
 * Then DELETE this file immediately after running.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

// ── 1. Connect to production DB ───────────────────────────────────────────────
$host   = 'localhost';
$user   = 'u163598660_marl_stage';
$pass   = 'Granth@26_';
$dbname = 'u163598660_marl_stage';

$conn = @mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("❌ Cannot connect to production DB: " . mysqli_connect_error() . "\n");
}
echo "✅ Connected to production DB: $dbname\n\n";

// ── 2. Check users table structure ────────────────────────────────────────────
$fields = [];
$res = mysqli_query($conn, "SHOW COLUMNS FROM users");
if (!$res) {
    die("❌ Cannot read users table: " . mysqli_error($conn) . "\n");
}
while ($row = mysqli_fetch_assoc($res)) {
    $fields[] = $row['Field'];
}
echo "✅ Users table columns: " . implode(', ', $fields) . "\n";

$hasBranchId = in_array('branch_id', $fields);

// ── 3. Check if password column is long enough ────────────────────────────────
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM users WHERE Field='password'");
$col = mysqli_fetch_assoc($colRes);
$colType = $col['Type'] ?? '';
echo "   password column type: $colType\n";
if (strpos($colType, '255') === false && strpos($colType, '60') === false) {
    // Expand password column to 255
    $r = mysqli_query($conn, "ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL");
    echo $r ? "✅ Expanded password column to VARCHAR(255)\n" : "❌ Failed to expand: " . mysqli_error($conn) . "\n";
}

// ── 4. Fix admin user ─────────────────────────────────────────────────────────
$hashedPassword = password_hash('admin', PASSWORD_BCRYPT);

$adminRes = mysqli_query($conn, "SELECT id FROM users WHERE username='admin' OR email='admin@gmail.com' LIMIT 1");
$admin = mysqli_fetch_assoc($adminRes);

if ($admin) {
    // Update existing admin
    $stmt = mysqli_prepare($conn, "UPDATE users SET 
        email='admin@gmail.com', 
        password=?, 
        role='admin', 
        is_active=1, 
        can_create=1, 
        can_edit=1, 
        can_delete=1 
        WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'si', $hashedPassword, $admin['id']);
    $ok = mysqli_stmt_execute($stmt);
    echo $ok 
        ? "✅ Admin user UPDATED (id={$admin['id']}) — password reset to 'admin'\n"
        : "❌ Update failed: " . mysqli_error($conn) . "\n";
} else {
    // Insert new admin
    if ($hasBranchId) {
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role, branch_id, is_active, can_create, can_edit, can_delete) VALUES ('admin','admin@gmail.com',?,  'admin', NULL, 1, 1, 1, 1)");
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role, is_active, can_create, can_edit, can_delete) VALUES ('admin','admin@gmail.com',?, 'admin', 1, 1, 1, 1)");
    }
    mysqli_stmt_bind_param($stmt, 's', $hashedPassword);
    $ok = mysqli_stmt_execute($stmt);
    echo $ok 
        ? "✅ Admin user CREATED — username=admin, password=admin\n"
        : "❌ Insert failed: " . mysqli_error($conn) . "\n";
}

// ── 5. Verify password hash works ─────────────────────────────────────────────
$verifyRes = mysqli_query($conn, "SELECT password FROM users WHERE username='admin'");
$verifyRow = mysqli_fetch_assoc($verifyRes);
if ($verifyRow) {
    $verifies = password_verify('admin', $verifyRow['password']);
    echo $verifies 
        ? "✅ Password 'admin' verified against stored hash\n"
        : "❌ Password verification FAILED — hash mismatch!\n";
}

// ── 6. Check companies table ──────────────────────────────────────────────────
$compRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM companies");
$compCount = mysqli_fetch_assoc($compRes)['cnt'] ?? 0;
echo "\n📊 Companies in DB: $compCount\n";
if ($compCount == 0) {
    echo "   ⚠️  No companies found. Log in as admin → Company Selection → Add Company\n";
}

// ── 7. Check session writability ─────────────────────────────────────────────
$sessionPath = __DIR__ . '/writable/session';
echo "\n📁 Session path writable: " . (is_writable($sessionPath) ? "✅ YES" : "❌ NO — chmod 0755 writable/session") . "\n";
$logsPath = __DIR__ . '/writable/logs';
echo "📁 Logs path writable: " . (is_writable($logsPath) ? "✅ YES" : "❌ NO — chmod 0755 writable/logs") . "\n";

echo "\n";
echo "════════════════════════════════════════════════════════\n";
echo "  DONE. Login credentials:\n";
echo "  URL: your-production-url/login\n";
echo "  Username: admin\n";
echo "  Password: admin\n";
echo "════════════════════════════════════════════════════════\n";
echo "\n⚠️  DELETE this file immediately: fix_production.php\n";
