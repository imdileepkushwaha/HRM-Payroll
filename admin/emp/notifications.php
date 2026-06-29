<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$settings = get_all_settings($conn);
$items = get_employee_portal_notification_items($conn, $employee['emp_id'], $settings, (int) $employee['branch_id']);
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>
    <header class="emp-page-head"><h1>Notifications</h1><p>Pending actions and updates for you.</p></header>
    <div class="emp-panel">
        <?php if ($items === []): ?>
            <p class="emp-muted">You&apos;re all caught up — no pending notifications.</p>
        <?php else: ?>
        <ul class="emp-notify-list">
            <?php foreach ($items as $item): ?>
            <li>
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="emp-notify-item is-priority-<?php echo htmlspecialchars($item['priority']); ?>">
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                    <span aria-hidden="true">→</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
