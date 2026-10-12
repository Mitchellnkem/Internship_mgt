<?php
/**
 * Create local demo logins. Run from the project root with:
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

// The checked-in project SQL does not define users. Create the columns used by
// the current registration, login, and permission checks when the table is absent.
$db->query("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(160) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type VARCHAR(32) NOT NULL DEFAULT 'student',
    permissions VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    KEY users_type_index (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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
