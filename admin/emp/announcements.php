<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$announcements = get_all_employee_portal_announcements($conn, (int) $employee['branch_id'], 50);
$pinned_count = 0;
foreach ($announcements as $ann) {
    if ((int) ($ann['is_pinned'] ?? 0) === 1) {
        $pinned_count++;
    }
}
$latest_label = $announcements !== [] ? date('j M Y', strtotime($announcements[0]['created_at'])) : '—';
?>
<div class="emp-page emp-page-announcements">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Company</p>
            <h2 class="emp-page-hero-title">Announcements</h2>
            <p>Company news, policy updates, and important notices from HR and management.</p>
        </div>
        <?php if ($announcements !== []): ?>
            <span class="emp-hero-stat-pill"><?php echo count($announcements); ?> update<?php echo count($announcements) === 1 ? '' : 's'; ?></span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
            <div><span class="emp-dash-stat-label">Total posts</span><strong class="emp-dash-stat-value"><?php echo count($announcements); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
            <div><span class="emp-dash-stat-label">Pinned</span><strong class="emp-dash-stat-value"><?php echo $pinned_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-paid">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
            <div><span class="emp-dash-stat-label">Latest</span><strong class="emp-dash-stat-value emp-dash-stat-value-sm"><?php echo htmlspecialchars($latest_label); ?></strong></div>
        </div>
    </div>

    <section class="emp-card emp-announcements-card">
        <header class="emp-card-toolbar emp-reg-toolbar">
            <div>
                <h3 class="emp-card-title">All announcements</h3>
                <p class="emp-reg-toolbar-sub">Newest updates appear first. Pinned posts stay at the top.</p>
            </div>
        </header>
        <?php if ($announcements === []): ?>
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                <strong>No announcements right now</strong>
                <p>Check back later for company news and HR updates.</p>
            </div>
        <?php else: ?>
            <div class="emp-announcements-list emp-announcements-page">
                <?php foreach ($announcements as $ann): ?>
                <article class="emp-announcement-card <?php echo (int) ($ann['is_pinned'] ?? 0) === 1 ? 'is-pinned' : ''; ?>">
                    <?php if ((int) ($ann['is_pinned'] ?? 0) === 1): ?><span class="emp-announcement-pin">Pinned</span><?php endif; ?>
                    <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($ann['body'])); ?></p>
                    <time datetime="<?php echo htmlspecialchars(date('c', strtotime($ann['created_at']))); ?>"><?php echo date('j M Y', strtotime($ann['created_at'])); ?></time>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
