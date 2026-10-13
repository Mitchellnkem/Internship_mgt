<?php
require_once 'includes/header.php';

$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$internship = get_internship($internship_id);

if(!$internship){
    flash('error_flash', 'Internship not found');
    redirect('internship.php');
}
record_recent_internship_view($internship_id);

$has_applied = false;
$application = null;
$can_apply = is_logged_in() && isset($user_data['user_type']) && $user_data['user_type'] === 'student';
$isSaved = $can_apply ? is_internship_saved($_SESSION['SBUser'], $internship_id) : false;

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

<nav class="page-breadcrumb" aria-label="Breadcrumb"><a href="internship.php">Internships</a><span aria-hidden="true">/</span><span>Opportunity details</span></nav>
<div class="detail-layout">
    <article class="detail-content">
        <header class="detail-heading">
            <span class="job-type-label">INTERNSHIP OPPORTUNITY</span>
            <h1><?= sanitize($internship['title']) ?></h1>
            <p class="detail-company"><?= sanitize($internship['company']) ?></p>
            <div class="detail-facts">
                <span><b>Location</b><?= sanitize($internship['location']) ?></span>
                <span><b>Type</b><?= !empty($internship['type']) ? sanitize($internship['type']) : 'Not specified' ?></span>
                <span><b>Duration</b><?= !empty($internship['duration']) ? sanitize($internship['duration']) : 'Not specified' ?></span>
                <span><b>Posted</b><?= sanitize(pretty_date($internship['posted_at'])) ?></span>
                <?php if (!empty($internship['salary'])): ?><span><b>Compensation</b><?= sanitize(money($internship['salary'])) ?></span><?php endif; ?>
            </div>
        </header>
        <section class="detail-section" aria-labelledby="description-heading">
            <h2 id="description-heading">About this opportunity</h2>
            <div class="detail-copy"><?= nl2br(sanitize($internship['description'])) ?></div>
        </section>
        <?php if (!empty($internship['requirements'])): ?>
            <section class="detail-section requirements-section" aria-labelledby="requirements-heading">
                <h2 id="requirements-heading">What you will bring</h2>
                <div class="detail-copy"><?= nl2br(sanitize($internship['requirements'])) ?></div>
            </section>
        <?php endif; ?>
        <a class="detail-back-link" href="internship.php">&larr; Back to all internships</a>
    </article>

    <aside class="application-sidebar" aria-labelledby="apply-heading">
        <div class="application-card">
            <span class="eyebrow">TAKE THE NEXT STEP</span>
            <?php if (!is_logged_in()): ?>
                <h2 id="apply-heading">Ready to apply?</h2>
                <p>Sign in to continue, or create a student account to send your application.</p>
                <a class="btn btn-primary btn-block" href="login.php">Sign in to apply</a>
                <a class="sidebar-secondary-link" href="register.php">Create a student account</a>
            <?php elseif ($has_applied): ?>
                <h2 id="apply-heading">You are in the running</h2>
                <div class="application-status"><span class="status-dot" aria-hidden="true"></span><span>Application <?= sanitize(ucfirst($application['status'])) ?></span></div>
                <p>Submitted <?= sanitize(pretty_date($application['application_date'])) ?>. You can follow updates from your account.</p>
                <a class="btn btn-secondary btn-block" href="myaccount.php">View my applications</a>
            <?php elseif (!$can_apply): ?>
                <h2 id="apply-heading">Student applications</h2>
                <p>This opportunity is open to student accounts. Sign in with a student account or create one to apply.</p>
                <a class="btn btn-primary btn-block" href="login.php">Sign in</a>
                <a class="sidebar-secondary-link" href="register.php">Create a student account</a>
            <?php else: ?>
                <h2 id="apply-heading">Apply for this role</h2>
                <p>Introduce yourself and tell the employer what interests you about this opportunity.</p>
                <?php if (!empty($errors)): ?><?= display_errors($errors) ?><?php endif; ?>
                <form method="POST" action="application.php?id=<?= (int)$internship_id ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="cover_letter">Your note to the employer</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="7" maxlength="5000" required><?= isset($_POST['cover_letter']) ? sanitize($_POST['cover_letter']) : '' ?></textarea>
                        <small class="form-text">Keep it specific: why this role and what you can bring.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send application <span aria-hidden="true">&rarr;</span></button>
                </form>
            <?php endif; ?>
        </div>
        <?php if ($can_apply): ?>
            <form class="save-internship-form" method="POST" action="student/save_internship.php">
                <?= csrf_field() ?>
                <input type="hidden" name="internship_id" value="<?= (int)$internship_id ?>">
                <input type="hidden" name="action" value="<?= $isSaved ? 'remove' : 'save' ?>">
                <input type="hidden" name="return_to" value="details">
                <button class="btn <?= $isSaved ? 'btn-secondary' : 'btn-primary' ?> btn-block" type="submit"><?= $isSaved ? 'Remove from saved internships' : 'Save this internship' ?></button>
            </form>
        <?php elseif (!is_logged_in()): ?>
            <p class="save-internship-signin"><a href="login.php">Sign in</a> to save this internship to your shortlist.</p>
        <?php endif; ?>
        <p class="application-privacy">Your application is shared with the employer for this opportunity.</p>
    </aside>
</div>

<?php include 'widgets/recent.php'; ?>

<?php include 'includes/footer.php'; ?>
