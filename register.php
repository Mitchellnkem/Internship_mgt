<?php
require_once 'includes/header.php';

if(is_logged_in()){
    redirect('index.php');
}

$errors = array();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : 'student';
    
    if(empty($full_name)){
        $errors[] = 'Full name is required';
    }
    if(empty($email)){
        $errors[] = 'Email is required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = 'Please provide a valid email address';
    }
    if(empty($password)){
        $errors[] = 'Password is required';
    } elseif(strlen($password) < 6){
        $errors[] = 'Password must be at least 6 characters';
    }
    if($password !== $password_confirm){
        $errors[] = 'Passwords do not match';
    }
    if(!in_array($user_type, array('student', 'employer'), true)){
        $errors[] = 'Invalid user type';
    }
    
    if(empty($errors)){
        $email_escaped = $db->real_escape_string($email);
        $check = $db->query("SELECT id FROM users WHERE email = '{$email_escaped}' LIMIT 1");
        if($check && $check->num_rows > 0){
            $errors[] = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $full_name_escaped = $db->real_escape_string($full_name);
            $permissions = ($user_type === 'employer') ? 'employer,post_jobs' : 'student,apply_jobs';
            $db->query("INSERT INTO users (full_name, email, password, user_type, permissions, created_at) VALUES ('{$full_name_escaped}', '{$email_escaped}', '{$hashed_password}', '{$user_type}', '{$permissions}', NOW())");
            
            if($db->affected_rows > 0){
                flash('success_flash', 'Registration successful! Please login.');
                redirect('login.php');
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Create Your Account</h2>
                
                <?php if(!empty($errors)): ?>
                    <?= display_errors($errors) ?>
                <?php endif; ?>
                
                <form method="POST" action="register.php">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                            value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_type">Account Type</label>
                        <select class="form-control" id="user_type" name="user_type" required>
                            <option value="student" <?= isset($_POST['user_type']) && $_POST['user_type'] === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="employer" <?= isset($_POST['user_type']) && $_POST['user_type'] === 'employer' ? 'selected' : '' ?>>Employer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center">Already have an account? 
                    <a href="login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
