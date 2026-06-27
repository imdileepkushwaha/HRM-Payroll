<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/salary_helper.php';
require_once __DIR__ . '/../includes/payroll_extensions.php';

$settings = get_all_settings($conn);
$slip_logs = get_employee_available_salary_slips($conn, $employee, $settings, 36);
$slip_count = count($slip_logs);
$latest_slip = $slip_logs[0] ?? null;
$total_net = 0.0;
$current_year = (int) date('Y');
$slips_this_year = 0;
foreach ($slip_logs as $slip) {
    $total_net += (float) $slip['net_salary'];
    if ((int) $slip['period_year'] === $current_year) {
        $slips_this_year++;
    }
}
$payroll_approval_required = ($settings['require_payroll_approval'] ?? '1') === '1';
?>
<div class="emp-page emp-page-slips">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero emp-page-hero-slips">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Payroll</p>
            <h2 class="emp-page-hero-title">Download approved monthly slips</h2>
            <p>View and download PDF salary slips for months where attendance is recorded<?php echo $payroll_approval_required ? ' and payroll is approved' : ''; ?>.</p>
        </div>
        <div class="emp-period-nav emp-slips-period-hint" aria-label="Slip archive">
            <span class="emp-period-nav-label"><?php echo $slip_count > 0 ? $slip_count . ' slip' . ($slip_count === 1 ? '' : 's') . ' in portal' : 'No slips yet'; ?></span>
        </div>
    </div>

    <?php if ($slip_count > 0): ?>
    <div class="emp-slips-stats">
        <div class="emp-slips-stat emp-slips-stat-count">
            <span class="emp-slips-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
            <div>
                <span class="emp-slips-stat-label">Available slips</span>
                <strong class="emp-slips-stat-value"><?php echo (int) $slip_count; ?></strong>
            </div>
        </div>
        <div class="emp-slips-stat emp-slips-stat-latest">
            <span class="emp-slips-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
            <div>
                <span class="emp-slips-stat-label">Latest net pay</span>
                <strong class="emp-slips-stat-value">₹<?php echo $latest_slip ? format_money($latest_slip['net_salary']) : '0'; ?></strong>
                <?php if ($latest_slip): ?>
                    <small><?php echo htmlspecialchars(get_period_label((int) $latest_slip['period_year'], (int) $latest_slip['period_month'])); ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="emp-slips-stat emp-slips-stat-year">
            <span class="emp-slips-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div>
                <span class="emp-slips-stat-label"><?php echo $current_year; ?> slips</span>
                <strong class="emp-slips-stat-value"><?php echo (int) $slips_this_year; ?></strong>
            </div>
        </div>
        <div class="emp-slips-stat emp-slips-stat-total">
            <span class="emp-slips-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
            </span>
            <div>
                <span class="emp-slips-stat-label">Total net (shown)</span>
                <strong class="emp-slips-stat-value">₹<?php echo format_money($total_net); ?></strong>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="emp-slips-panel">
        <div class="emp-slips-toolbar">
            <div class="emp-slips-policy-chips">
                <span class="emp-slips-policy-chip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    PDF format
                </span>
                <?php if ($payroll_approval_required): ?>
                    <span class="emp-slips-policy-chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Payroll approval required
                    </span>
                <?php endif; ?>
            </div>
            <a href="attendance.php" class="btn btn-outline btn-sm">View attendance</a>
        </div>

        <?php if ($slip_logs === []): ?>
            <div class="emp-slips-empty">
                <span class="emp-slips-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </span>
                <h3>No salary slips yet</h3>
                <p>Slips appear here after your attendance is recorded<?php echo $payroll_approval_required ? ' and admin approves payroll for that month' : ''; ?>.</p>
                <a href="attendance.php" class="btn">Go to attendance</a>
            </div>
        <?php else: ?>
            <div class="emp-slips-grid">
                <?php foreach ($slip_logs as $index => $slip):
                    $slip_month = (int) $slip['period_month'];
                    $slip_year = (int) $slip['period_year'];
                    $slip_label = get_period_label($slip_year, $slip_month);
                    $month_short = date('M', mktime(0, 0, 0, $slip_month, 1, $slip_year));
                    $pdf_url = 'slip_view.php?month=' . $slip_month . '&year=' . $slip_year;
                    $is_latest = $index === 0;
                    ?>
                    <article class="emp-slip-card<?php echo $is_latest ? ' emp-slip-card-latest' : ''; ?>">
                        <?php if ($is_latest): ?>
                            <span class="emp-slip-card-badge">Latest</span>
                        <?php endif; ?>
                        <div class="emp-slip-card-head">
                            <div class="emp-slip-card-period">
                                <span class="emp-slip-card-month"><?php echo htmlspecialchars(strtoupper($month_short)); ?></span>
                                <strong><?php echo (int) $slip_year; ?></strong>
                            </div>
                            <span class="emp-slip-card-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </span>
                        </div>
                        <div class="emp-slip-card-body">
                            <span class="emp-slip-card-label"><?php echo htmlspecialchars($slip_label); ?></span>
                            <div class="emp-slip-card-pay">
                                <span>Net salary</span>
                                <strong>₹<?php echo format_money($slip['net_salary']); ?></strong>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($pdf_url); ?>" class="emp-slip-card-action" target="_blank" rel="noopener">
                            <span>View PDF</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
