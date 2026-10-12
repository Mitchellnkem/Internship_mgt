<?php
require_once 'includes/header.php';

if(!is_logged_in()){
    login_error_redirect('login.php');
}

$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($application_id > 0){
    $application = $db->query("SELECT a.*, i.title, i.company, i.location FROM applications a LEFT JOIN internships i ON a.int_id = i.id WHERE a.id = '{$application_id}' AND a.cus_id = '{$_SESSION['SBUser']}' LIMIT 1");
    
    if($application && $application->num_rows > 0){
        $app = mysqli_fetch_assoc($application);
        ?>
        
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4>Application Receipt</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6>Applicant Information</h6>
                                    <p>
                                        <strong>Name:</strong> <?= sanitize($user_data['full_name']) ?><br>
                                        <strong>Email:</strong> <?= sanitize($user_data['email']) ?><br>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Application Details</h6>
                                    <p>
                                        <strong>Application ID:</strong> <?= $app['id'] ?><br>
                                        <strong>Date Applied:</strong> <?= pretty_date($app['application_date']) ?><br>
                                        <strong>Status:</strong> <span class="badge badge-primary"><?= ucfirst($app['status']) ?></span>
                                    </p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5>Position Details</h5>
                            <p>
                                <strong>Position:</strong> <?= sanitize($app['title']) ?><br>
                                <strong>Company:</strong> <?= sanitize($app['company']) ?><br>
                                <strong>Location:</strong> <?= sanitize($app['location']) ?><br>
                            </p>
                            
                            <?php if(!empty($app['cover_letter'])): ?>
                                <hr>
                                <h5>Cover Letter</h5>
                                <p><?= nl2br(sanitize($app['cover_letter'])) ?></p>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="text-center">
                                <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
                                <a href="myaccount.php" class="btn btn-secondary">Back to My Account</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
    } else {
        flash('error_flash', 'Application not found');
        redirect('myaccount.php');
    }
} else {
    flash('error_flash', 'Invalid application ID');
    redirect('myaccount.php');
}

include 'includes/footer.php';
?>
