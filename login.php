<?php
require_once 'includes/header.php';

if(is_logged_in()){
    redirect('index.php');
}

$errors = array();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if(empty($email)){
        $errors[] = 'Email is required';
    }
    if(empty($password)){
        $errors[] = 'Password is required';
    }
    
    if(empty($errors)){
        $email_escaped = $db->real_escape_string($email);
        $query = $db->query("SELECT * FROM users WHERE email = '{$email_escaped}' LIMIT 1");
        if($query && $query->num_rows > 0){
            $user = mysqli_fetch_assoc($query);
            if(password_verify($password, $user['password'])){
                login($user['id']);
                redirect('index.php');
            } else {
                $errors[] = 'Invalid email or password';
            }
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Login to Your Account</h2>
                
                <?php if(!empty($errors)): ?>
                    <?= display_errors($errors) ?>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center">Don't have an account? 
                    <a href="register.php">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
