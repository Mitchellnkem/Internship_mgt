<?php
require_once dirname(__DIR__).'/core/init.php';
if (!is_logged_in()) {
    flash('error_flash', 'Sign in with a superadmin account to continue.');
    redirect('../login.php');
}
if (!has_permission('superadmin')) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

function dashboard_count($db, $sql) {
    try {
        $result = $db->query($sql);
        if (!$result) return 'N/A';
        $row = $result->fetch_row();
        return number_format((int)$row[0]);
    } catch (mysqli_sql_exception $exception) {
        error_log('Superadmin metric query failed: '.$exception->getMessage());
        return 'N/A';
    }
}

$metrics = array(
    array('Registered people', dashboard_count($db, 'SELECT COUNT(*) FROM users'), 'Student, employer and admin accounts', 'users'),
    array('Student accounts', dashboard_count($db, "SELECT COUNT(*) FROM users WHERE user_type = 'student'"), 'Students on the platform', 'students'),
    array('Internship listings', dashboard_count($db, 'SELECT COUNT(*) FROM internships'), 'Published opportunities', 'listings'),
    array('Applications', dashboard_count($db, 'SELECT COUNT(*) FROM applications'), 'Applications received', 'applications')
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102a43">
    <title>Platform overview | BIT-InternshipHub</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/components.css">
</head>
<body>
<a class="skip-link" href="#admin-content">Skip to content</a>
<main class="admin-shell" id="admin-content" tabindex="-1">
    <nav class="admin-top" aria-label="Superadmin navigation">
        <a class="footer-logo" href="../index.php"><span class="brand-mark" aria-hidden="true">B</span> BIT-InternshipHub</a>
        <a class="admin-signout" href="../logout.php">Sign out</a>
    </nav>
    <header class="admin-title">
        <span class="eyebrow">SUPERADMIN | PLATFORM OPERATIONS</span>
        <h1>Platform overview</h1>
        <p>A clear snapshot of the people and opportunities on your platform.</p>
    </header>
    <section class="metric-grid" aria-label="Platform metrics">
        <?php foreach ($metrics as $metric): ?>
            <article class="metric metric-<?= sanitize($metric[3]) ?>">
                <span class="metric-label"><?= sanitize($metric[0]) ?></span>
                <strong><?= sanitize($metric[1]) ?></strong>
                <span class="metric-description"><?= sanitize($metric[2]) ?></span>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="admin-note" aria-labelledby="access-title">
        <span class="admin-note-mark" aria-hidden="true">!</span>
        <div><h2 id="access-title">Operator access</h2><p>This area is limited to accounts whose <code>permissions</code> include <code>superadmin</code>. Grant that permission only to trusted operators. Database credentials should come from <code>INTERNSHIP_DB_USER</code> and <code>INTERNSHIP_DB_PASS</code> environment variables outside local development.</p></div>
    </section>
</main>
</body>
</html>
