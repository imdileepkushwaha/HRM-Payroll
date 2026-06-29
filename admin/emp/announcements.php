<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$announcements = get_all_employee_portal_announcements($conn, (int) $employee['branch_id'], 50);
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>
    <header class="emp-page-head"><h1>Announcements</h1><p>Company news and updates.</p></header>
    <?php if ($announcements === []): ?>
        <div class="emp-panel"><p class="emp-muted">No announcements right now.</p></div>
    <?php else: ?>
        <div class="emp-announcements-list emp-announcements-page">
            <?php foreach ($announcements as $ann): ?>
            <article class="emp-announcement-card <?php echo (int) ($ann['is_pinned'] ?? 0) === 1 ? 'is-pinned' : ''; ?>">
                <?php if ((int) ($ann['is_pinned'] ?? 0) === 1): ?><span class="emp-announcement-pin">Pinned</span><?php endif; ?>
                <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($ann['body'])); ?></p>
                <time><?php echo date('j M Y', strtotime($ann['created_at'])); ?></time>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
