<?php
require_once 'includes/admin_page_init.php';
admin_page_init('settings');
require_once 'includes/audit_helper.php';

$filter_type = trim($_GET['entity_type'] ?? '');
$logs = get_admin_audit_logs($conn, 300, $filter_type !== '' ? $filter_type : null);

$entity_types = [];
foreach ($logs as $log) {
    if (!empty($log['entity_type'])) {
        $entity_types[$log['entity_type']] = true;
    }
}
?>
<div class="hrm-page audit-log-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">System</p>
            <h2>Audit log</h2>
            <p>Who changed what — recent admin actions across payroll and HR modules.</p>
        </div>
        <div class="page-header-actions">
            <a href="settings.php" class="btn btn-outline">Settings</a>
        </div>
    </div>

    <?php if ($entity_types !== []): ?>
    <div class="audit-log-filters">
        <a href="audit_log.php" class="recruitment-filter-pill<?php echo $filter_type === '' ? ' is-active' : ''; ?>">All</a>
        <?php foreach (array_keys($entity_types) as $type): ?>
            <a href="audit_log.php?entity_type=<?php echo urlencode($type); ?>" class="recruitment-filter-pill<?php echo $filter_type === $type ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($type); ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="panel panel-elevated">
        <div class="panel-header masters-panel-head">
            <div class="panel-title-group">
                <h3>Recent activity</h3>
                <span class="panel-badge"><?php echo count($logs); ?> entries</span>
            </div>
        </div>
        <div class="panel-body padded">
            <?php if ($logs === []): ?>
                <div class="masters-empty">
                    <h4>No audit entries yet</h4>
                    <p>Changes to employees, recruitment, exits, and roles will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table audit-log-table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d M Y H:i', strtotime($log['created_at']))); ?></td>
                                <td><?php echo htmlspecialchars($log['admin_username']); ?></td>
                                <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                                <td><?php echo htmlspecialchars(($log['entity_type'] ?? '') . ($log['entity_id'] ? ' · ' . $log['entity_id'] : '')); ?></td>
                                <td class="audit-log-detail"><?php echo htmlspecialchars($log['detail'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
