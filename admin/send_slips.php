<?php
require_once 'includes/admin_page_init.php';
admin_page_init('slips');
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/csrf_helper.php';

$month = (int) ($_GET['month'] ?? $_POST['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? $_POST['year'] ?? date('Y'));
$period_label = get_period_label($year, $month);
$settings = get_all_settings($conn);
$smtp_ready = is_smtp_configured($settings);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_or_redirect('send_slips.php?month=' . $month . '&year=' . $year);
    $result = send_salary_slips_for_period($conn, $year, $month, $settings, get_active_branch_id(), $_SESSION['admin_username'] ?? 'admin');
    $_SESSION['flash_message'] = $result['message'];
    $_SESSION['flash_success'] = $result['ok'];
    header('Location: slip_logs.php?month=' . $month . '&year=' . $year);
    exit;
}
?>
<div class="hrm-page slips-page send-slips-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Payroll</p>
            <h2>Send salary slips</h2>
            <p>Email PDF slips to employees with valid email for <strong><?php echo htmlspecialchars($period_label); ?></strong>.</p>
        </div>
        <div class="page-header-actions"><a href="slip_logs.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-outline">Slip logs</a></div>
    </div>

    <div class="send-slips-layout">
        <section class="panel panel-elevated send-slips-panel">
            <div class="panel-body padded">
                <div class="settings-add-panel send-slips-card">
                    <div class="settings-add-panel-head">
                        <span class="settings-add-panel-icon send-slips-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </span>
                        <div class="settings-add-panel-head-text">
                            <h4>Batch email send</h4>
                            <p>Eligible employees with email addresses receive their salary slip PDF for this period.</p>
                        </div>
                    </div>
                    <ul class="send-slips-checklist">
                        <li class="<?php echo $smtp_ready ? 'ok' : 'warn'; ?>">SMTP <?php echo $smtp_ready ? 'configured' : 'not configured'; ?> — <a href="settings.php?tab=smtp">Settings</a></li>
                        <li>Payroll must be approved for the period</li>
                        <li>Employees without email are skipped (portal only)</li>
                    </ul>
                    <form method="POST" action="send_slips.php" class="send-slips-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                        <button type="submit" class="btn btn-header" <?php echo $smtp_ready ? '' : 'disabled'; ?> onclick="return confirm('Send salary slip emails for <?php echo htmlspecialchars($period_label, ENT_QUOTES); ?>?');">Send all eligible slips</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
