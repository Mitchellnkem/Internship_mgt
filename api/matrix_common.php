<?php
require_once dirname(__DIR__).'/core/init.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function matrix_json_response($status, $payload) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function matrix_require_login() {
    if (!is_logged_in()) matrix_json_response(401, array('ok' => false, 'message' => 'Sign in to use secure messaging.'));
}

function matrix_require_csrf() {
    $token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        matrix_json_response(403, array('ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'));
    }
}

function matrix_read_json_body() {
    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 32768) matrix_json_response(413, array('ok' => false, 'message' => 'Request is too large.'));
    $body = json_decode($raw, true);
    if (!is_array($body)) matrix_json_response(400, array('ok' => false, 'message' => 'Invalid request body.'));
    return $body;
}

function matrix_api_request($method, $path, $accessToken = '', $body = null) {
    if (!MATRIX_HOMESERVER_URL || !MATRIX_SERVER_NAME || !function_exists('curl_init')) return array('status' => 503, 'data' => null);
    $parts = parse_url(MATRIX_HOMESERVER_URL);
    $isLocalHttp = isset($parts['host']) && in_array(strtolower($parts['host']), array('localhost', '127.0.0.1', '::1'), true);
    if (!$parts || !isset($parts['scheme'], $parts['host']) || ($parts['scheme'] !== 'https' && !($parts['scheme'] === 'http' && $isLocalHttp))) {
        error_log('BIT-InternshipHub Matrix endpoint rejected: HTTPS is required outside localhost.');
        return array('status' => 503, 'data' => null);
    }

    $headers = array('Accept: application/json');
    if ($accessToken !== '') $headers[] = 'Authorization: Bearer '.$accessToken;
    $curl = curl_init(MATRIX_HOMESERVER_URL.$path);
    $options = array(
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'BIT-InternshipHub Matrix bridge'
    );
    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($body);
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_HTTPHEADER] = $headers;
    }
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($raw === false || strlen($raw) > 2097152) {
        error_log('BIT-InternshipHub Matrix request failed: HTTP '.$status.' '.$error);
        return array('status' => $status ?: 503, 'data' => null);
    }
    $data = json_decode($raw, true);
    return array('status' => $status, 'data' => is_array($data) ? $data : null);
}

function matrix_access_token_from_body($body) {
    $token = isset($body['access_token']) && is_string($body['access_token']) ? $body['access_token'] : '';
    if ($token === '' || strlen($token) > 4096 || preg_match('/[\x00-\x20\x7F]/', $token)) {
        matrix_json_response(422, array('ok' => false, 'message' => 'Connect a valid Matrix account first.'));
    }
    return $token;
}

function matrix_validate_user_id($userId) {
    if (!is_string($userId) || strlen($userId) > 255 || strpos($userId, '@') !== 0 || substr($userId, -strlen(':'.MATRIX_SERVER_NAME)) !== ':'.MATRIX_SERVER_NAME) {
        return false;
    }
    return (bool)preg_match('/^@[A-Za-z0-9._=+\/-]+:[A-Za-z0-9.:-]+$/', $userId);
}

function matrix_current_link($db, $userId) {
    $statement = $db->prepare('SELECT matrix_user_id FROM matrix_user_links WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $row ? $row['matrix_user_id'] : null;
}

function matrix_verify_request_user($db, $userId, $accessToken) {
    $linkedMatrixId = matrix_current_link($db, $userId);
    if (!$linkedMatrixId) matrix_json_response(409, array('ok' => false, 'message' => 'Link your Matrix account before opening secure conversations.'));
    $whoami = matrix_api_request('GET', '/_matrix/client/v3/account/whoami', $accessToken);
    if ($whoami['status'] !== 200 || empty($whoami['data']['user_id']) || !hash_equals($linkedMatrixId, $whoami['data']['user_id'])) {
        matrix_json_response(403, array('ok' => false, 'message' => 'The Matrix account does not match your linked account.'));
    }
    return $linkedMatrixId;
}
