<?php
require_once 'includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$scope = isset($_GET['scope']) && in_array($_GET['scope'], array('ghana', 'africa', 'international'), true) ? $_GET['scope'] : 'ghana';
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
            <label class="sr-only" for="opportunity-scope">External opportunity region</label>
            <select id="opportunity-scope" name="scope" class="form-control listing-scope">
                <option value="ghana" <?= $scope === 'ghana' ? 'selected' : '' ?>>Ghana first</option>
                <option value="africa" <?= $scope === 'africa' ? 'selected' : '' ?>>Across Africa</option>
                <option value="international" <?= $scope === 'international' ? 'selected' : '' ?>>International</option>
            </select>
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

    <section class="external-opportunities" aria-labelledby="external-title">
        <div class="listing-toolbar external-toolbar">
            <div><span class="eyebrow">LIVE DISCOVERY</span><h2 id="external-title">Across the web</h2></div>
            <p>External listings open on their original source. Apply there.</p>
        </div>
        <p id="external-search-status" class="external-search-status" role="status" aria-live="polite"><?php if (JOOBLE_API_KEY === ''): ?>Online listings are not enabled yet. Set <code>JOOBLE_API_KEY</code> in the Apache/PHP environment and restart Apache to enable them. BIT listings can still be searched above.<?php else: ?>Searching external opportunities&hellip;<?php endif; ?></p>
        <div id="external-job-grid" class="job-grid" aria-live="polite"></div>
        <button id="external-load-more" class="btn btn-secondary external-load-more" type="button" hidden>Load more opportunities</button>
    </section>
</section>

<script>
(function () {
    var endpoint = <?= json_encode(BASEURL . 'api/opportunities.php') ?>;
    var queryInput = document.getElementById('internship-search');
    var scopeInput = document.getElementById('opportunity-scope');
    var status = document.getElementById('external-search-status');
    var grid = document.getElementById('external-job-grid');
    var moreButton = document.getElementById('external-load-more');
    var externalSearchConfigured = <?= JOOBLE_API_KEY !== '' ? 'true' : 'false' ?>;
    var currentPage = 1;
    var isLoading = false;

    function addText(parent, tag, className, text) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        element.textContent = text || '';
        parent.appendChild(element);
        return element;
    }

    function appendCard(job) {
        var card = document.createElement('article');
        card.className = 'job-card';
        var top = document.createElement('div');
        top.className = 'job-card-top';
        addText(top, 'span', 'job-type-label', job.type || 'EXTERNAL');
        addText(top, 'span', 'job-posted', job.source ? 'Via ' + job.source : 'External listing');
        card.appendChild(top);
        addText(card, 'h3', '', job.title || 'Opportunity');
        addText(card, 'p', 'job-company', job.company || 'Employer');
        var facts = document.createElement('ul');
        facts.className = 'job-facts';
        var location = document.createElement('li');
        addText(location, 'span', '', 'Location');
        location.appendChild(document.createTextNode(job.location || 'Location not listed'));
        facts.appendChild(location);
        card.appendChild(facts);
        addText(card, 'p', 'job-summary', job.description || 'View the original listing for full details.');
        var link = document.createElement('a');
        link.className = 'job-card-action';
        link.href = job.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.appendChild(document.createTextNode('View on ' + (job.source || 'source')));
        var arrow = document.createElement('span');
        arrow.setAttribute('aria-hidden', 'true');
        arrow.textContent = ' ↗';
        link.appendChild(arrow);
        card.appendChild(link);
        grid.appendChild(card);
    }

    function loadExternal(reset) {
        if (isLoading) return;
        if (reset) {
            currentPage = 1;
            grid.textContent = '';
            moreButton.hidden = true;
        }
        isLoading = true;
        moreButton.disabled = true;
        status.textContent = 'Searching external opportunities...';
        var params = new URLSearchParams({q: queryInput.value.trim() || 'internship', scope: scopeInput.value, page: String(currentPage)});
        fetch(endpoint + '?' + params.toString(), {headers: {'Accept': 'application/json'}})
            .then(function (response) {
                return response.text().then(function (body) {
                    var data;
                    try {
                        data = JSON.parse(body);
                    } catch (parseError) {
                        throw new Error('The search service returned an unreadable response. Check the PHP server error log.');
                    }
                    if (!response.ok) throw new Error(data.message || 'Live search is unavailable.');
                    return data;
                });
            })
            .then(function (data) {
                (data.items || []).forEach(function (job) {
                    if (job.url && /^https:\/\//i.test(job.url)) appendCard(job);
                });
                if (!data.configured || !data.items || data.items.length === 0) {
                    status.textContent = data.message || 'No external opportunities found. Try another search.';
                } else {
                    status.textContent = (data.total || data.items.length) + ' external opportunities found. Listings are supplied by ' + (data.provider || 'our search partner') + '.';
                }
                moreButton.hidden = !data.configured || !data.items || data.items.length < 12 || currentPage >= 5;
            })
            .catch(function (error) {
                status.textContent = error.message || 'External opportunities are temporarily unavailable.';
                moreButton.hidden = true;
            })
            .finally(function () { isLoading = false; moreButton.disabled = false; });
    }

    scopeInput.addEventListener('change', function () { loadExternal(true); });
    moreButton.addEventListener('click', function () { currentPage += 1; loadExternal(false); });
    if (!externalSearchConfigured) return;
    loadExternal(true);
}());
</script>

<?php include 'includes/footer.php'; ?>
