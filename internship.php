<?php
require_once 'includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$internships = $search !== '' ? search_internships($search, 50) : get_internships(50);
?>

<section class="listing-page" aria-labelledby="listing-title">
    <header class="listing-hero">
        <span class="eyebrow">OPPORTUNITIES TO GET YOU STARTED</span>
        <h1 id="listing-title">Find work worth<br class="desktop-break"> doing.</h1>
        <p>Explore internships where you can learn, contribute, and take your next step.</p>
        <form method="GET" action="internship.php" class="listing-search" role="search">
            <label for="internship-search" class="sr-only">Search internships by title, company, or location</label>
            <input type="search" class="form-control" id="internship-search" name="search" placeholder="Try a title, company, or location" value="<?= sanitize($search) ?>">
            <button class="btn btn-primary" type="submit">Search opportunities</button>
            <?php if ($search !== ''): ?><a class="search-clear" href="internship.php">Clear search</a><?php endif; ?>
        </form>
    </header>

    <div class="listing-toolbar">
        <div><span class="eyebrow">LATEST ROLES</span><h2><?= $search !== '' ? 'Search results' : 'Explore internships' ?></h2></div>
        <p><?= number_format(count($internships)) ?> <?= count($internships) === 1 ? 'opportunity' : 'opportunities' ?><?= $search !== '' ? ' for &ldquo;'.sanitize($search).'&rdquo;' : '' ?></p>
    </div>

    <?php if (empty($internships)): ?>
        <section class="listing-empty" aria-live="polite">
            <span class="empty-mark" aria-hidden="true">?</span>
            <?php if ($search !== ''): ?>
                <h2>No matching opportunities yet</h2>
                <p>Try a broader search, or clear your search to see all current listings.</p>
                <a class="btn btn-secondary" href="internship.php">View all internships</a>
            <?php else: ?>
                <h2>No internships posted yet</h2>
                <p>New opportunities will appear here when they are added. Please check back soon.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <div class="job-grid">
            <?php foreach ($internships as $job): ?>
                <article class="job-card">
                    <div class="job-card-top"><span class="job-type-label">INTERNSHIP</span><span class="job-posted">Posted <?= sanitize(pretty_date($job['posted_at'])) ?></span></div>
                    <h3><a href="application.php?id=<?= (int)$job['id'] ?>"><?= sanitize($job['title']) ?></a></h3>
                    <p class="job-company"><?= sanitize($job['company']) ?></p>
                    <ul class="job-facts" aria-label="Opportunity details">
                        <li><span>Location</span><?= sanitize($job['location']) ?></li>
                        <?php if (!empty($job['duration'])): ?><li><span>Duration</span><?= sanitize($job['duration']) ?></li><?php endif; ?>
                    </ul>
                    <p class="job-summary"><?= sanitize(substr(strip_tags($job['description']), 0, 160)) ?><?= strlen(strip_tags($job['description'])) > 160 ? '...' : '' ?></p>
                    <a class="job-card-action" href="application.php?id=<?= (int)$job['id'] ?>">View opportunity <span aria-hidden="true">&rarr;</span></a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
