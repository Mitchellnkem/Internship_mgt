<?php
require_once 'includes/header.php';

$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$internship = get_internship($internship_id);

if(!$internship){
    flash('error_flash', 'Internship not found');
    redirect('internship.php');
}

$has_applied = false;
$application = null;
$can_apply = is_logged_in() && isset($user_data['user_type']) && $user_data['user_type'] === 'student';

if(is_logged_in()){
    $application = get_application($_SESSION['SBUser'], $internship_id);
    $has_applied = $application !== null;
}

$errors = array();

if(is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && !$has_applied && $can_apply){
    if (!csrf_is_valid()) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    }
    $cover_letter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
    
    if(empty($cover_letter)){
        $errors[] = 'Cover letter is required';
    }
    
    if(empty($errors)){
        $result = apply_for_internship($_SESSION['SBUser'], $internship_id, $cover_letter);
        if($result){
            flash('success_flash', 'Application submitted successfully!');
            redirect('myaccount.php');
        } else {
            $errors[] = 'Failed to submit application. Please try again.';
        }
    }
}
?>

<div class="row mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-body">
                <h2><?= sanitize($internship['title']) ?></h2>
                <h5 class="text-muted"><?= sanitize($internship['company']) ?></h5>
                
                <hr>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Location:</strong> <?= sanitize($internship['location']) ?></p>
                        <p><strong>Type:</strong> <?= isset($internship['type']) ? sanitize($internship['type']) : 'Not specified' ?></p>
                        <p><strong>Duration:</strong> <?= isset($internship['duration']) ? sanitize($internship['duration']) : 'Not specified' ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Posted:</strong> <?= pretty_date($internship['posted_at']) ?></p>
                        <p><strong>Salary/Stipend:</strong> <?= isset($internship['salary']) ? money($internship['salary']) : 'Not specified' ?></p>
                    </div>
                </div>
                
                <hr>
                
                <h5>Description</h5>
                <p><?= nl2br(sanitize($internship['description'])) ?></p>
                
                <?php if(!empty($internship['requirements'])): ?>
                    <hr>
                    <h5>Requirements</h5>
                    <p><?= nl2br(sanitize($internship['requirements'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-body">
                <?php if(!is_logged_in()): ?>
                    <div class="alert alert-info">
                        <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to apply.</p>
                    </div>
                <?php elseif($has_applied): ?>
                    <div class="alert alert-success">
                        <p><strong>Application Status:</strong> <?= ucfirst($application['status']) ?></p>
                        <p><strong>Applied On:</strong> <?= pretty_date($application['application_date']) ?></p>
                    </div>
                <?php elseif(!$can_apply): ?>
                    <div class="alert alert-info"><p>Student accounts can apply to internships. Sign in or create a student account to continue.</p></div>
                <?php else: ?>
                    <h5>Apply for this Internship</h5>
                    <hr>
                    
                    <?php if(!empty($errors)): ?>
                        <?= display_errors($errors) ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="application.php?id=<?= $internship_id ?>">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="cover_letter">Cover Letter</label>
                            <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5" required></textarea>
                            <small class="form-text text-muted">Tell the employer why you're interested in this position.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Submit Application</button>
                        <a href="internship.php" class="btn btn-secondary btn-block mt-2">Back to Internships</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
