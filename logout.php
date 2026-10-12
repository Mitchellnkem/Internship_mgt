<?php
require_once 'core/init.php';

if(is_logged_in()){
    session_destroy();
    flash('success_flash', 'You have been logged out successfully');
}

redirect('index.php');
?>
