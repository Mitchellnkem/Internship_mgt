<?php
/**
 * Create local demo logins after the schema is installed. Run from the project root with:
 *   php scripts/setup_internship_schema.php
 *   php scripts/create_demo_accounts.php
 * This script is intentionally unavailable over HTTP.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}

require_once dirname(__DIR__).'/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->set_charset('utf8mb4');

$usersTable = $db->query("SHOW TABLES LIKE 'users'");
if (!$usersTable || $usersTable->num_rows === 0) {
    fwrite(STDERR, "Users table is missing. Run php scripts/setup_internship_schema.php first.\n");
    exit(1);
}

$accounts = array(
    array('Demo Student', 'student.demo@internshiphub.test', 'StudentDemo2026!', 'student', 'student,apply_jobs'),
    array('Demo Employer', 'employer.demo@internshiphub.test', 'EmployerDemo2026!', 'employer', 'employer,post_jobs'),
    array('Demo Superadmin', 'superadmin.demo@internshiphub.test', 'SuperAdminDemo2026!', 'admin', 'superadmin')
);

try {
    $db->begin_transaction();
    $find = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $insert = $db->prepare('INSERT INTO users (full_name, email, password, user_type, permissions, created_at) VALUES (?, ?, ?, ?, ?, NOW())');

    foreach ($accounts as $account) {
        list($name, $email, $plainPassword, $userType, $permissions) = $account;
        $find->bind_param('s', $email);
        $find->execute();
        $exists = $find->get_result()->fetch_assoc();
        if ($exists) {
            fwrite(STDOUT, "Already exists; left unchanged: {$email}\n");
            continue;
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $insert->bind_param('sssss', $name, $email, $passwordHash, $userType, $permissions);
        $insert->execute();
        fwrite(STDOUT, "Created: {$email}\n");
    }

    $find->close();
    $insert->close();
    $db->commit();
    fwrite(STDOUT, "Demo accounts are ready. Sign in at login.php.\n");
} catch (Throwable $exception) {
    $db->rollback();
    fwrite(STDERR, "Could not create demo accounts: {$exception->getMessage()}\n");
    exit(1);
} finally {
    $db->close();
}
