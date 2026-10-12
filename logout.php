<?php
require_once 'core/init.php';

if(is_logged_in()){
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    flash('success_flash', 'You have been logged out successfully');
}

redirect('index.php');
?>
