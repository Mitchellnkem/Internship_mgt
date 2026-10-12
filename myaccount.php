<?php
require_once 'includes/header.php';

if(!is_logged_in()){
    login_error_redirect('login.php');
}

$applications = get_user_applications($_SESSION['SBUser']);
?>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-body">
                <h5 class="card-title">My Profile</h5>
                <hr>
                <p><strong>Name:</strong> <?= sanitize($user_data['full_name']) ?></p>
                <p><strong>Email:</strong> <?= sanitize($user_data['email']) ?></p>
                <p><strong>Account Type:</strong> 
                    <span class="badge badge-primary"><?= ucfirst($user_data['user_type']) ?></span>
                </p>
                <p><strong>Member Since:</strong> <?= pretty_date($user_data['created_at']) ?></p>
                <?php if(!empty($user_data['last_login'])): ?>
                    <p><strong>Last Login:</strong> <?= pretty_date($user_data['last_login']) ?></p>
                <?php endif; ?>
                
                <hr>
                <a href="logout.php" class="btn btn-danger btn-block">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-body">
                <h5 class="card-title">My Applications</h5>
                <hr>
                
                <?php if(empty($applications)): ?>
                    <p class="text-muted">You haven't applied to any internships yet.</p>
                    <a href="internship.php" class="btn btn-primary">Browse Internships</a>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Position</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($applications as $app): ?>
                                    <tr>
                                        <td><?= sanitize($app['title']) ?></td>
                                        <td><?= sanitize($app['company']) ?></td>
                                        <td><?= sanitize($app['location']) ?></td>
                                        <td><?= pretty_date($app['application_date']) ?></td>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            switch($app['status']){
                                                case 'pending': $status_class = 'warning'; break;
                                                case 'reviewed': $status_class = 'info'; break;
                                                case 'shortlisted': $status_class = 'success'; break;
                                                case 'rejected': $status_class = 'danger'; break;
                                                case 'hired': $status_class = 'success'; break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $status_class ?>">
                                                <?= ucfirst($app['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="application.php?id=<?= $app['id'] ?>" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
