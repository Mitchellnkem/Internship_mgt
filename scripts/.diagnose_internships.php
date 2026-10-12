<?php
require_once dirname(__DIR__).'/config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
foreach ($db->query('SHOW TABLES') as $row) {
    echo reset($row), PHP_EOL;
}
$result = $db->query('SELECT * FROM internships ORDER BY posted_at DESC LIMIT 1');
if (!$result) {
    echo 'QUERY_ERROR: ', $db->error, PHP_EOL;
} else {
    echo 'QUERY_OK rows=', $result->num_rows, PHP_EOL;
}
