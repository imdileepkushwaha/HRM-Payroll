<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$settings = get_all_settings($conn);
$items = get_employee_portal_notification_items($conn, $employee['emp_id'], $settings, (int) $employee['branch_id']);
$warn_count = 0;
$info_count = 0;
foreach ($items as $item) {
    if (($item['priority'] ?? '') === 'warn') {
        $warn_count++;
    } else {
        $info_count++;
    }
}
?>
<div class="emp-page emp-page-notifications">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Company</p>
            <h2 class="emp-page-hero-title">Notifications</h2>
            <p>Pending actions and updates that need your attention across leave, attendance, expenses, and more.</p>
        </div>
        <?php if ($items !== []): ?>
            <span class="emp-hero-stat-pill<?php echo $warn_count > 0 ? ' emp-hero-stat-pill-warn' : ''; ?>"><?php echo count($items); ?> active</span>
        <?php else: ?>
            <span class="emp-hero-stat-pill">All clear</span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
            <div><span class="emp-dash-stat-label">Total alerts</span><strong class="emp-dash-stat-value"><?php echo count($items); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
            <div><span class="emp-dash-stat-label">Action needed</span><strong class="emp-dash-stat-value"><?php echo $warn_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></div>
            <div><span class="emp-dash-stat-label">Informational</span><strong class="emp-dash-stat-value"><?php echo $info_count; ?></strong></div>
        </div>
    </div>

    <section class="emp-card emp-notify-card">
        <header class="emp-card-toolbar emp-reg-toolbar">
            <div>
                <h3 class="emp-card-title">Your notifications</h3>
                <p class="emp-reg-toolbar-sub">Tap an item to open the related page and take action.</p>
            </div>
        </header>
        <?php if ($items === []): ?>
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                <strong>You&apos;re all caught up</strong>
                <p>No pending notifications right now. We&apos;ll surface new items here when something needs your attention.</p>
            </div>
        <?php else: ?>
            <ul class="emp-notify-list emp-notify-list-page">
                <?php foreach ($items as $item): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>" class="emp-notify-item is-priority-<?php echo htmlspecialchars($item['priority']); ?>">
                        <span class="emp-notify-item-label"><?php echo htmlspecialchars($item['label']); ?></span>
                        <span class="emp-notify-item-arrow" aria-hidden="true">→</span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
