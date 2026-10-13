
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
    $limit = max(1, min(100, (int)$limit));
    try {
        $query = $db->query("SELECT * FROM internships WHERE status = 'published' ORDER BY posted_at DESC LIMIT {$limit}");
    } catch (mysqli_sql_exception $exception) {
        error_log('BIT-InternshipHub could not load internship listings: '.$exception->getMessage());
        return array();
    }
    if (!$query) {
        error_log('BIT-InternshipHub could not load internship listings: '.$db->error);
        return array();
    }
    $jobs = array();
    while($row = $query->fetch_assoc()){
        $jobs[] = $row;
    }
    return $jobs;
}

function search_internships($search = '', $limit = 20){
    global $db;
    $limit = max(1, min(100, (int)$limit));
    $search = $db->real_escape_string($search);
    $sql = "SELECT * FROM internships WHERE status = 'published'";
    if(!empty($search)){
        $sql .= " AND (title LIKE '%{$search}%' OR company LIKE '%{$search}%' OR location LIKE '%{$search}%' OR description LIKE '%{$search}%')";
    }
    $sql .= " ORDER BY posted_at DESC LIMIT " . $limit;
    try {
        $query = $db->query($sql);
    } catch (mysqli_sql_exception $exception) {
        error_log('BIT-InternshipHub could not search internship listings: '.$exception->getMessage());
        return array();
    }
    if (!$query) {
        error_log('BIT-InternshipHub could not search internship listings: '.$db->error);
        return array();
    }
    $jobs = array();
    while($row = $query->fetch_assoc()){
        $jobs[] = $row;
    }
    return $jobs;
}

function get_internship($id){
    global $db;
    $id = (int)$id;
    try {
        $query = $db->query("SELECT * FROM internships WHERE id = '{$id}' AND status = 'published' LIMIT 1");
    } catch (mysqli_sql_exception $exception) {
        error_log('BIT-InternshipHub could not load an internship: '.$exception->getMessage());
        return null;
    }
    return ($query && $query->num_rows) ? mysqli_fetch_assoc($query) : null;
}

function record_recent_internship_view($internship_id){
    global $db, $user_data;
    $internship_id = (int)$internship_id;
    if ($internship_id < 1 || session_status() !== PHP_SESSION_ACTIVE) return;

    // Keep a small session history for guests and as a fallback if database
    // history is temporarily unavailable.
    $sessionHistory = isset($_SESSION['recent_internships']) && is_array($_SESSION['recent_internships']) ? $_SESSION['recent_internships'] : array();
    $sessionHistory = array_values(array_unique(array_merge(array($internship_id), array_map('intval', $sessionHistory))));
    $_SESSION['recent_internships'] = array_slice($sessionHistory, 0, 10);

    if (!is_logged_in() || empty($user_data['user_type']) || $user_data['user_type'] !== 'student') return;

    $user_id = (int)$_SESSION['SBUser'];
    try {
        $statement = $db->prepare('INSERT INTO recently_viewed_internships (user_id, internship_id, viewed_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE viewed_at = NOW()');
        $statement->bind_param('ii', $user_id, $internship_id);
        $statement->execute();
        $statement->close();

        // Retain only the most recent 20 viewed internships for each student.
        $old = $db->prepare('SELECT internship_id FROM recently_viewed_internships WHERE user_id = ? ORDER BY viewed_at DESC LIMIT 20, 1000');
        $old->bind_param('i', $user_id);
        $old->execute();
        $oldRows = $old->get_result();
        $oldIds = array();
        while ($row = $oldRows->fetch_assoc()) $oldIds[] = (int)$row['internship_id'];
        $old->close();
        if ($oldIds) {
            $delete = $db->prepare('DELETE FROM recently_viewed_internships WHERE user_id = ? AND internship_id = ?');
            foreach ($oldIds as $oldId) {
                $delete->bind_param('ii', $user_id, $oldId);
                $delete->execute();
            }
            $delete->close();
        }
    } catch (mysqli_sql_exception $exception) {
        error_log('BIT-InternshipHub could not record recent internship view: '.$exception->getMessage());
    }
}

function get_recent_internships($limit = 5){
    global $db, $user_data;
    $limit = max(1, min(10, (int)$limit));
    if (is_logged_in() && !empty($user_data['user_type']) && $user_data['user_type'] === 'student') {
        try {
            $user_id = (int)$_SESSION['SBUser'];
            $statement = $db->prepare("SELECT i.id, i.title, i.company, i.location, i.posted_at, r.viewed_at FROM recently_viewed_internships r INNER JOIN internships i ON i.id = r.internship_id WHERE r.user_id = ? AND i.status = 'published' ORDER BY r.viewed_at DESC LIMIT {$limit}");
            $statement->bind_param('i', $user_id);
            $statement->execute();
            $result = $statement->get_result();
            $jobs = array();
            while ($row = $result->fetch_assoc()) $jobs[] = $row;
            $statement->close();
            if ($jobs) return $jobs;
        } catch (mysqli_sql_exception $exception) {
            error_log('BIT-InternshipHub could not load student recent internships: '.$exception->getMessage());
        }
    }

    $ids = isset($_SESSION['recent_internships']) && is_array($_SESSION['recent_internships']) ? array_slice(array_values(array_unique(array_filter(array_map('intval', $_SESSION['recent_internships']), function($id){ return $id > 0; }))), 0, $limit) : array();
    if (!$ids) return array();
    // IDs come only from the server-side session and are cast to integers.
    $idList = implode(',', $ids);
    try {
        $query = $db->query("SELECT id, title, company, location, posted_at FROM internships WHERE status = 'published' AND id IN ({$idList}) ORDER BY FIELD(id, {$idList})");
    } catch (mysqli_sql_exception $exception) {
        error_log('BIT-InternshipHub could not load guest recent internships: '.$exception->getMessage());
        return array();
    }
    if (!$query) return array();
    $jobs = array();
    while ($row = $query->fetch_assoc()) $jobs[] = $row;
    return $jobs;
}

function get_saved_internships($user_id, $limit = 20){
    global $db;
    $user_id = (int)$user_id;
    $limit = max(1, min(50, (int)$limit));
    try {
        $statement = $db->prepare("SELECT i.id, i.title, i.company, i.location, i.posted_at, i.status, s.saved_at FROM saved_internships s INNER JOIN internships i ON i.id = s.internship_id WHERE s.user_id = ? ORDER BY s.saved_at DESC LIMIT {$limit}");
        $statement->bind_param('i', $user_id);
        $statement->execute();
        $result = $statement->get_result();
        $jobs = array();
        while ($row = $result->fetch_assoc()) $jobs[] = $row;
        $statement->close();
        return $jobs;
    } catch (mysqli_sql_exception $exception) {
        error_log('BIT-InternshipHub could not load saved internships: '.$exception->getMessage());
        return array();
    }
}

function is_internship_saved($user_id, $internship_id){
    global $db;
    $user_id = (int)$user_id;
    $internship_id = (int)$internship_id;
    try {
        $statement = $db->prepare('SELECT 1 FROM saved_internships WHERE user_id = ? AND internship_id = ? LIMIT 1');
        $statement->bind_param('ii', $user_id, $internship_id);
        $statement->execute();
        $saved = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $saved;
    } catch (mysqli_sql_exception $exception) {
        error_log('BIT-InternshipHub could not check saved internship: '.$exception->getMessage());
        return false;
    }
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
