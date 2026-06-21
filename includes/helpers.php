<?php
// includes/helpers.php

/**
 * Format relative time (e.g., "2 godziny temu")
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'przed chwilą';
    if ($diff < 3600) return floor($diff / 60) . ' min temu';
    if ($diff < 86400) return floor($diff / 3600) . ' godz. temu';
    if ($diff < 2592000) return floor($diff / 86400) . ' dni temu';
    if ($diff < 31536000) return floor($diff / 2592000) . ' mies. temu';
    return floor($diff / 31536000) . ' lat temu';
}

/**
 * Format large numbers (e.g., 1.2k, 1M)
 */
function formatNumber($num) {
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'k';
    return $num;
}

/**
 * Render a standardized note card for dashboards and search results.
 */
function renderPremiumNoteCard($note, $user_id, $purchasedNoteIds = []) {
    $isPres = $note['file_type'] === 'presentation';
    $thumbnailUrl = '';
    
    if ($isPres) {
        $thumbnailUrl = 'download.php?id=' . (int)$note['id'] . '&slide=0';
    } elseif (($note['file_type'] ?? '') === 'image') {
        $thumbnailUrl = 'download.php?id=' . (int)$note['id'];
    }

    $watchHref = 'watch.php?id=' . (int)$note['id'];
    if ((($note['access_type'] ?? 'free') === 'premium') && (int)$note['user_id'] !== (int)$user_id && !in_array((int)$note['id'], $purchasedNoteIds, true)) {
        $watchHref = 'paypal_mock.php?note_id=' . (int)$note['id'];
    }
    
    $isTeacherCreator = isset($note['user_type']) ? ($note['user_type'] === 'teacher') : true;
    $creatorProfileUrl = $isTeacherCreator ? 'channel.php?id=' . $note['user_id'] : 'student_profile.php?id=' . $note['user_id'];
    
    $isBookmarked = (int)($note['is_bookmarked'] ?? 0);
    $priceBadge = (($note['access_type'] ?? 'free') === 'premium') ? 'Premium ' . number_format((float)($note['premium_price'] ?? 0), 2, ',', ' ') . ' PLN' : 'Free';
    
    ob_start();
    ?>
    <article class="note-card glass-card animate__animated animate__fadeInUp">
        <a href="<?= htmlspecialchars($watchHref) ?>" class="note-thumbnail-wrapper">
            <span class="note-badge"><?= htmlspecialchars($note['subject']) ?> • <?= $priceBadge ?></span>
            <?php if (!empty($thumbnailUrl)): ?>
                <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-thumbnail" loading="lazy">
            <?php else: ?>
                <div class="note-file-preview">
                    <div class="note-file-icon">
                        <?php if ($isPres): ?>
                            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <?php else: ?>
                            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </a>
        
        <div class="note-details-youtube" style="padding: 12px; display: flex; gap: 12px; align-items: flex-start;">
            <a href="<?= $creatorProfileUrl ?>" class="note-creator-avatar">
                <?= strtoupper(substr(htmlspecialchars($note['username']), 0, 1)) ?>
            </a>
            <div class="note-text-group">
                <h4 class="note-title"><a href="<?= htmlspecialchars($watchHref) ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($note['title']) ?></a></h4>
                <p class="note-author-name"><a href="<?= $creatorProfileUrl ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($note['username']) ?></a></p>
                <p class="note-metrics-youtube"><?= formatNumber((int)$note['views']) ?> wyświetleń • <?= timeAgo($note['created_at']) ?></p>
            </div>
            <button type="button" class="bookmark-card-btn" data-id="<?= $note['id'] ?>" style="background: none; border: none; color: <?= $isBookmarked ? 'var(--accent-color)' : 'var(--text-secondary)' ?>; cursor: pointer;">
                <svg width="20" height="20" fill="<?= $isBookmarked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            </button>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
