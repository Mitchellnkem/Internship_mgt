<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once dirname(__DIR__).'/config.php';
require_once ROOT_PATH.'helpers/helpers.php';

$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if(mysqli_connect_errno()){
    echo 'Database connection failed: '.mysqli_connect_error();
    exit;
}

$cart_id = '';
if(isset($_COOKIE[CART_COOKIE])){
    $cart_id = sanitize($_COOKIE[CART_COOKIE]);
}
if(!empty($cart_id)){
    setcookie(CART_COOKIE, $cart_id, CART_COOKIE_EXPIRE, '/', '', false, true);
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
