<?php
require_once __DIR__.'/matrix_common.php';
matrix_require_login();
$currentUserId = (int)$_SESSION['SBUser'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    matrix_json_response(200, array('ok' => true, 'configured' => MATRIX_HOMESERVER_URL !== '' && MATRIX_SERVER_NAME !== '', 'matrix_user_id' => matrix_current_link($db, $currentUserId)));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    matrix_json_response(405, array('ok' => false, 'message' => 'Method not allowed.'));
}
matrix_require_csrf();
$body = matrix_read_json_body();
$accessToken = matrix_access_token_from_body($body);
$whoami = matrix_api_request('GET', '/_matrix/client/v3/account/whoami', $accessToken);
if ($whoami['status'] !== 200 || empty($whoami['data']['user_id']) || !matrix_validate_user_id($whoami['data']['user_id'])) {
    matrix_json_response(403, array('ok' => false, 'message' => 'The Matrix account could not be verified on this homeserver.'));
}
$matrixUserId = $whoami['data']['user_id'];
$existing = matrix_current_link($db, $currentUserId);
if ($existing !== null && !hash_equals($existing, $matrixUserId)) {
    matrix_json_response(409, array('ok' => false, 'message' => 'A different Matrix account is already linked. Contact support to change it safely.'));
}
try {
    $statement = $db->prepare('INSERT INTO matrix_user_links (user_id, matrix_user_id, verified_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE verified_at = NOW()');
    $statement->bind_param('is', $currentUserId, $matrixUserId);
    $statement->execute();
    $statement->close();
} catch (mysqli_sql_exception $exception) {
    error_log('BIT-InternshipHub Matrix account link failed: '.$exception->getMessage());
    matrix_json_response(409, array('ok' => false, 'message' => 'That Matrix account is already linked to another BIT account.'));
}
matrix_json_response(200, array('ok' => true, 'matrix_user_id' => $matrixUserId));
