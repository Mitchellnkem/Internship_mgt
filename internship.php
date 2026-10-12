<?php
require_once 'includes/header.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$internships = !empty($search) ? search_internships($search, 50) : get_internships(50);
?>

<div class="row mt-5 mb-4">
    <div class="col-md-12">
        <h2>Browse Internships</h2>
        <hr>
        <form method="GET" action="internship.php" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search by title, company, location..." 
                    value="<?= isset($_GET['search']) ? sanitize($_GET['search']) : '' ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <a href="internship.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if(empty($internships)): ?>
    <div class="alert alert-info">
        <p>No internships found. Try adjusting your search criteria.</p>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach($internships as $job): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= sanitize($job['title']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?= sanitize($job['company']) ?></h6>
                        
                        <p class="card-text flex-grow-1">
                            <small>
                                <i class="fas fa-map-marker-alt"></i> <?= sanitize($job['location']) ?><br>
                                <i class="fas fa-calendar"></i> Posted: <?= pretty_date($job['posted_at']) ?><br>
                                <?php if(!empty($job['duration'])): ?>
                                    <i class="fas fa-hourglass-half"></i> Duration: <?= sanitize($job['duration']) ?><br>
                                <?php endif; ?>
                            </small>
                        </p>
                        
                        <p class="card-text">
                            <small><?= substr(sanitize($job['description']), 0, 100) ?>...</small>
                        </p>
                        
                        <a href="application.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm btn-block">View & Apply</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
