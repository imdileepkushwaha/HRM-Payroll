<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$assets = get_employee_assigned_assets($conn, $employee['emp_id']);
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>
    <header class="emp-page-head"><h1>My assets</h1><p>Company equipment assigned to you.</p></header>
    <div class="emp-panel">
        <?php if ($assets === []): ?>
            <p class="emp-muted">No assets currently assigned.</p>
        <?php else: ?>
        <ul class="emp-asset-list">
            <?php foreach ($assets as $a): ?>
            <li class="emp-asset-card">
                <strong><?php echo htmlspecialchars($a['name']); ?></strong>
                <span class="emp-muted"><?php echo htmlspecialchars($a['asset_tag']); ?> · <?php echo htmlspecialchars($a['category'] ?? 'General'); ?></span>
                <?php if (!empty($a['serial_no'])): ?><span>Serial: <?php echo htmlspecialchars($a['serial_no']); ?></span><?php endif; ?>
                <span class="emp-muted">Assigned <?php echo date('d M Y', strtotime($a['assigned_at'])); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
