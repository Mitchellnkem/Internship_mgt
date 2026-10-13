<?php

require_once dirname(__DIR__).'/config.php';
require_once ROOT_PATH.'helpers/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(array(
        'lifetime' => 0, 'path' => '/', 'secure' => $https,
        'httponly' => true, 'samesite' => 'Lax'
    ));
    session_start();
}

$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if(mysqli_connect_errno()){
    error_log('BIT-InternshipHub database connection failed: '.mysqli_connect_error());
    http_response_code(503);
    echo 'The service is temporarily unavailable. Please try again later.';
    exit;
}
$db->set_charset('utf8mb4');

if (isset($_COOKIE[LEGACY_CART_COOKIE])) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(LEGACY_CART_COOKIE, '', array(
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ));
    unset($_COOKIE[LEGACY_CART_COOKIE]);
}

$user_data = [];
if(isset($_SESSION['SBUser']) && is_numeric($_SESSION['SBUser'])){
    $user_id = (int)$_SESSION['SBUser'];
    $query = $db->query("SELECT * FROM users WHERE id = '{$user_id}' LIMIT 1");
    if($query && $query->num_rows){
        $user_data = mysqli_fetch_assoc($query);
        $names = explode(' ', trim($user_data['full_name']));
        $user_data['first'] = isset($names[0]) ? $names[0] : '';
        $user_data['last'] = isset($names[1]) ? $names[1] : '';
    } else {
        unset($_SESSION['SBUser']);
    }
}
