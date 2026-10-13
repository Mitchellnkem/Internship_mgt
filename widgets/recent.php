<?php
$recentInternships = get_recent_internships(5);
if (!$recentInternships) return;
?>
<section class="recent-internships-widget" aria-labelledby="recent-internships-title">
    <div class="recent-widget-heading">
        <div><span class="eyebrow">PICK UP WHERE YOU LEFT OFF</span><h2 id="recent-internships-title">Recently viewed</h2></div>
    </div>
    <p class="recent-widget-note"><?php if (is_logged_in() && isset($user_data['user_type']) && $user_data['user_type'] === 'student'): ?>Only you can see this account’s recent listings. Up to 20 are kept.<?php else: ?>Recent listings are kept in this browser session.<?php endif; ?></p>
    <ul class="recent-internships-list">
        <?php foreach ($recentInternships as $recent): ?>
            <li>
                <a href="application.php?id=<?= (int)$recent['id'] ?>">
                    <strong><?= sanitize($recent['title']) ?></strong>
                    <span><?= sanitize($recent['company']) ?> &middot; <?= sanitize($recent['location']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
