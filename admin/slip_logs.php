<?php
require_once 'includes/admin_page_init.php';
admin_page_init('slips');
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_modules_helper.php';

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) { $month = (int) date('n'); }
if ($year < 2000 || $year > 2100) { $year = (int) date('Y'); }

$branch_id = get_active_branch_id();
$logs = get_admin_slip_logs($conn, $year, $month, $branch_id);
$period_label = get_period_label($year, $month);
$q = 'month=' . $month . '&year=' . $year;
$sent_count = 0;
$failed_count = 0;
foreach ($logs as $log) {
    if (($log['status'] ?? '') === 'sent') {
        $sent_count++;
    } elseif (($log['status'] ?? '') === 'failed') {
        $failed_count++;
    }
}
?>
<div class="hrm-page slips-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Payroll</p>
            <h2>Salary slip logs</h2>
            <p>Email sends and portal activity for <strong><?php echo htmlspecialchars($period_label); ?></strong></p>
        </div>
        <div class="page-header-actions">
            <form method="GET" class="hrm-period-form slips-period-form">
                <select name="month" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" onchange="this.form.submit()">
                    <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <a href="send_slips.php?<?php echo $q; ?>" class="btn btn-header">Send by email</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($logs); ?></strong><span>Total entries</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo $sent_count; ?></strong><span>Sent</span></div></div>
        <div class="settings-status-chip <?php echo $failed_count > 0 ? 'warn' : 'neutral'; ?>"><span class="status-dot"></span><div><strong><?php echo $failed_count; ?></strong><span>Failed</span></div></div>
    </div>

    <section class="panel panel-elevated slips-log-panel">
        <div class="panel-header masters-panel-head">
            <div class="panel-title-group">
                <h3>Log entries</h3>
                <span class="panel-badge"><?php echo count($logs); ?> listed</span>
            </div>
        </div>
        <div class="panel-body padded">
            <?php if ($logs === []): ?>
                <div class="masters-empty slips-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <h4>No logs yet</h4>
                    <p>Send slips by email or wait for employees to view slips in the portal.</p>
                    <a href="send_slips.php?<?php echo $q; ?>" class="btn btn-header">Send slips</a>
                </div>
            <?php else: ?>
                <ul class="slip-log-list">
                    <?php foreach ($logs as $log):
                        $st = $log['status'] ?? 'pending';
                        $initial = strtoupper(substr($log['name'] ?? 'E', 0, 1));
                    ?>
                    <li class="slip-log-card slip-log-<?php echo htmlspecialchars($st); ?>">
                        <span class="slip-log-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></span>
                        <div class="slip-log-body">
                            <strong><?php echo htmlspecialchars($log['name']); ?></strong>
                            <span class="slip-log-meta"><?php echo htmlspecialchars($log['emp_id']); ?> · <?php echo htmlspecialchars($log['sent_to'] ?: $log['email'] ?: 'No email'); ?></span>
                            <span class="slip-log-time"><?php echo date('d M Y, h:i A', strtotime($log['sent_at'])); ?></span>
                        </div>
                        <div class="slip-log-side">
                            <strong class="slip-log-amount"><?php echo format_money((float) $log['net_salary']); ?></strong>
                            <span class="slip-status-badge slip-status-<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></span>
                            <a href="preview_slip.php?emp_id=<?php echo urlencode($log['emp_id']); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-sm btn-outline" target="_blank">Preview</a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require 'includes/footer.php'; ?>
