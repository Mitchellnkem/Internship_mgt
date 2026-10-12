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
    $result = $db->query($sql);
    if (!$result) return '—';
    $row = $result->fetch_row();
    return number_format((int)$row[0]);
}
$metrics = array(
    array('Registered people', dashboard_count($db, 'SELECT COUNT(*) FROM users'), 'Across student, employer and admin accounts'),
    array('Student accounts', dashboard_count($db, "SELECT COUNT(*) FROM users WHERE user_type = 'student'"), 'Students on the platform'),
    array('Internship listings', dashboard_count($db, 'SELECT COUNT(*) FROM internships'), 'Published opportunities'),
    array('Applications', dashboard_count($db, 'SELECT COUNT(*) FROM applications'), 'Applications received')
);
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Platform overview · InternshipHub</title><link rel="stylesheet" href="../css/bootstrap.min.css"><link rel="stylesheet" href="../css/style.css"><style>
body{background:#f4f6f1}.admin-shell{max-width:1180px;margin:45px auto;padding:0 24px}.admin-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:44px}.admin-top a{color:#174b40;font-weight:700}.admin-title h1{font-size:2.6rem;font-weight:800}.admin-title p{color:#63716c}.metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}.metric{background:white;border:1px solid #e7e9df;border-radius:14px;padding:24px}.metric span{display:block;color:#63716c;font-size:.8rem}.metric strong{display:block;font:800 2.4rem Manrope,sans-serif;margin:12px 0 5px}.admin-note{background:#e5ecea;border-radius:14px;padding:25px;margin-top:22px}.admin-note h2{font-size:1.2rem;font-weight:800}.admin-note p{margin:0;color:#53645c}@media(max-width:700px){.metric-grid{grid-template-columns:1fr 1fr}.admin-shell{margin-top:28px}.metric{padding:17px}}
</style></head><body><main class="admin-shell"><nav class="admin-top"><a href="../index.php">↖ InternshipHub</a><a href="../logout.php">Sign out</a></nav><header class="admin-title"><span class="eyebrow">SUPERADMIN · PLATFORM OPERATIONS</span><h1>Platform overview</h1><p>Quick pulse on the people and opportunities using InternshipHub.</p></header><section class="metric-grid" aria-label="Platform metrics"><?php foreach($metrics as $metric): ?><article class="metric"><span><?= sanitize($metric[0]) ?></span><strong><?= sanitize($metric[1]) ?></strong><span><?= sanitize($metric[2]) ?></span></article><?php endforeach; ?></section><section class="admin-note"><h2>Access and setup</h2><p>This area is restricted to accounts whose existing <code>permissions</code> value contains <code>superadmin</code>. Assign that permission only to trusted operators. Use environment variables <code>INTERNSHIP_DB_USER</code> and <code>INTERNSHIP_DB_PASS</code> for database credentials outside local development.</p></section></main></body></html>
