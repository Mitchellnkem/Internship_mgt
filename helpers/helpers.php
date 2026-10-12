
<?php

function display_errors($errors){
    $display = '<div class="alert alert-danger"><ul class="mb-0">';
    foreach($errors as $error){
        $display .= '<li>'.sanitize($error).'</li>';
    }
    $display .= '</ul></div>';
    return $display;
}

function sanitize($dirty){
    return htmlentities($dirty, ENT_QUOTES, 'UTF-8');
}

function money($number){
    return '$'.number_format((float)$number, 2);
}

function pretty_date($date){
    return date('M d, Y h:i A', strtotime($date));
}

function redirect($url){
    header('Location: '.$url);
    exit;
}

function flash($name = '', $string = ''){
    if(!empty($name)){
        if(!empty($string)){
            $_SESSION[$name] = $string;
            return true;
        }
        if(isset($_SESSION[$name])){
            $message = $_SESSION[$name];
            unset($_SESSION[$name]);
            return $message;
        }
    }
    return '';
}

function login($user_id){
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['SBUser'] = (int)$user_id;
    global $db;
    $stmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['SBUser']);
        $stmt->execute();
        $stmt->close();
    }
    flash('success_flash', 'You are now logged in!');
}

function is_logged_in(){
    return isset($_SESSION['SBUser']) && $_SESSION['SBUser'] > 0;
}

function login_error_redirect($url = 'login.php'){
    flash('error_flash', 'You must be logged in to access this page');
    redirect($url);
}

function permission_error_redirect($url = 'login.php'){
    flash('error_flash', 'You do not have permission to access this page');
    redirect($url);
}

function has_permission($permission = 'admin'){
    global $user_data;
    if(empty($user_data['permissions'])){
        return false;
    }
    $permissions = array_map('trim', explode(',', $user_data['permissions']));
    return in_array($permission, $permissions, true);
}

function csrf_token(){
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(){
    return '<input type="hidden" name="csrf_token" value="'.sanitize(csrf_token()).'">';
}

function csrf_is_valid(){
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
        is_string($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function get_user_by_id($id){
    global $db;
    $id = (int)$id;
    $query = $db->query("SELECT * FROM users WHERE id = '{$id}' LIMIT 1");
    return ($query && $query->num_rows) ? mysqli_fetch_assoc($query) : null;
}

function get_internships($limit = 12){
    global $db;
    $limit = (int)$limit;
    $query = $db->query("SELECT * FROM internships ORDER BY posted_at DESC LIMIT {$limit}");
    $jobs = array();
    while($row = mysqli_fetch_assoc($query)){
        $jobs[] = $row;
    }
    return $jobs;
}

function search_internships($search = '', $limit = 20){
    global $db;
    $search = $db->real_escape_string($search);
    $sql = "SELECT * FROM internships";
    if(!empty($search)){
        $sql .= " WHERE title LIKE '%{$search}%' OR company LIKE '%{$search}%' OR location LIKE '%{$search}%' OR description LIKE '%{$search}%'";
    }
    $sql .= " ORDER BY posted_at DESC LIMIT " . (int)$limit;
    $query = $db->query($sql);
    $jobs = array();
    while($row = mysqli_fetch_assoc($query)){
        $jobs[] = $row;
    }
    return $jobs;
}

function get_internship($id){
    global $db;
    $id = (int)$id;
    $query = $db->query("SELECT * FROM internships WHERE id = '{$id}' LIMIT 1");
    return ($query && $query->num_rows) ? mysqli_fetch_assoc($query) : null;
}

function get_application($user_id, $internship_id){
    global $db;
    $user_id = (int)$user_id;
    $internship_id = (int)$internship_id;
    $query = $db->query("SELECT * FROM applications WHERE cus_id = '{$user_id}' AND int_id = '{$internship_id}' LIMIT 1");
    return ($query && $query->num_rows) ? mysqli_fetch_assoc($query) : null;
}

function apply_for_internship($user_id, $internship_id, $cover_letter = ''){
    global $db;
    $user_id = (int)$user_id;
    $internship_id = (int)$internship_id;
    if(get_application($user_id, $internship_id)){
        return false;
    }
    $cover_letter = $db->real_escape_string($cover_letter);
    $db->query("INSERT INTO applications (cus_id, int_id, applied, application_date, status, cover_letter) VALUES ('{$user_id}', '{$internship_id}', 1, NOW(), 'pending', '{$cover_letter}')");
    return $db->insert_id;
}

function get_user_applications($user_id){
    global $db;
    $user_id = (int)$user_id;
    $sql = "SELECT a.*, i.title, i.company, i.location, i.posted_at FROM applications a LEFT JOIN internships i ON a.int_id = i.id WHERE a.cus_id = '{$user_id}' ORDER BY a.application_date DESC";
    $query = $db->query($sql);
    $applications = array();
    while($row = mysqli_fetch_assoc($query)){
        $applications[] = $row;
    }
    return $applications;
}

function get_logged_in_user_name(){
    global $user_data;
    return !empty($user_data['full_name']) ? $user_data['full_name'] : 'Guest';
}

?>
