<?php
if (!is_logged_in() || empty($user_data['user_type']) || $user_data['user_type'] !== 'student') return;
$savedOpportunities = get_saved_internships((int)$_SESSION['SBUser'], 20);
?>
<section class="saved-opportunities-widget" id="saved-opportunities" aria-labelledby="saved-opportunities-title">
    <div class="saved-widget-heading">
        <div><span class="eyebrow">KEEP GOOD OPTIONS CLOSE</span><h2 id="saved-opportunities-title">Saved internships</h2></div>
        <span class="saved-widget-count"><?= count($savedOpportunities) ?></span>
    </div>
    <?php if (!$savedOpportunities): ?>
        <div class="saved-widget-empty">
            <p>Your shortlist is empty. Save a published internship to come back to it later.</p>
            <a class="btn btn-secondary" href="internship.php">Browse internships</a>
        </div>
    <?php else: ?>
        <ul class="saved-opportunities-list">
            <?php foreach ($savedOpportunities as $saved): ?>
                <li>
                    <div class="saved-opportunity-copy">
                        <?php if ($saved['status'] === 'published'): ?>
                            <a href="application.php?id=<?= (int)$saved['id'] ?>"><?= sanitize($saved['title']) ?></a>
                            <span><?= sanitize($saved['company']) ?> &middot; <?= sanitize($saved['location']) ?></span>
                        <?php else: ?>
                            <span class="saved-opportunity-unavailable"><?= sanitize($saved['title']) ?></span>
                            <span><?= sanitize($saved['company']) ?> &middot; No longer available</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="student/save_internship.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="internship_id" value="<?= (int)$saved['id'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="return_to" value="account">
                        <button class="saved-remove-button" type="submit" aria-label="Remove <?= sanitize($saved['title']) ?> from saved internships">Remove</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <a class="saved-widget-more" href="internship.php">Find more opportunities <span aria-hidden="true">&rarr;</span></a>
    <?php endif; ?>
</section>
