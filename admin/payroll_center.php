<?php
require_once 'includes/admin_page_init.php';
admin_page_init('payroll');
require_once 'includes/settings_helper.php';
require_once 'includes/salary_helper.php';
require_once 'includes/payroll_extensions.php';
require_once 'includes/hrm_helper.php';
require_once 'includes/csrf_helper.php';

$settings = get_all_settings($conn);
$active_branch_label = get_branch_label($conn, get_active_branch_id());
$payroll_month = (int) ($_GET['month'] ?? date('n'));
$payroll_year = (int) ($_GET['year'] ?? date('Y'));
if ($payroll_month < 1 || $payroll_month > 12) {
    $payroll_month = (int) date('n');
}
if ($payroll_year < 2000 || $payroll_year > 2100) {
    $payroll_year = (int) date('Y');
}

$payroll_period_label = get_period_label($payroll_year, $payroll_month);
$payroll_branch_id = get_active_branch_id() ?? payroll_context_branch_id();
$payroll_period = get_payroll_period($conn, $payroll_year, $payroll_month, $payroll_branch_id);
$period_status = $payroll_period['status'] ?? 'open';
$period_locked = $period_status === 'locked';
$working_days = (int) get_working_days_per_month($settings);

$center = build_payroll_center_rows($conn, $payroll_year, $payroll_month, $settings);
$payroll_rows = $center['rows'];
$total_net_payroll = $center['total_net'];
$employees_with_attendance = $center['employees_with_attendance'];
$slip_eligible_count = $center['slip_eligible'];
$slips_in_portal_count = $center['slips_in_portal'];
$require_slip_approval = $center['require_approval'];
$slips_portal_ready = !$require_slip_approval || can_release_salary_slips_for_period($conn, $payroll_year, $payroll_month, $payroll_branch_id);
$period_query = 'month=' . $payroll_month . '&year=' . $payroll_year;
?>
<div class="hrm-page payroll-center-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Payroll</p>
            <h2>Payroll Center</h2>
            <p>Run monthly payroll for <strong><?php echo htmlspecialchars($payroll_period_label); ?></strong> · <?php echo htmlspecialchars($active_branch_label); ?></p>
        </div>
        <div class="page-header-actions">
            <a href="upload_attendance.php?<?php echo $period_query; ?>" class="btn btn-outline">Upload attendance</a>
            <a href="report_payroll.php?<?php echo $period_query; ?>" class="btn btn-outline">Payroll report</a>
            <a href="dashboard.php?<?php echo $period_query; ?>" class="btn btn-header">Dashboard</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo htmlspecialchars($payroll_period_label); ?></strong>
                <span><?php echo (int) $employees_with_attendance; ?> with attendance · <?php echo $working_days; ?> working days</span>
            </div>
        </div>
        <div class="settings-status-chip <?php echo $period_status === 'approved' || $period_status === 'locked' ? 'ok' : ($period_status === 'review' ? 'warn' : 'neutral'); ?>">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo htmlspecialchars(payroll_period_status_label($period_status)); ?></strong>
                <span><?php echo $period_locked ? 'Attendance locked' : 'Approve to release slips in portal'; ?></span>
            </div>
        </div>
        <div class="settings-status-chip <?php echo $slip_eligible_count > 0 && $slips_in_portal_count >= $slip_eligible_count ? 'ok' : ($slip_eligible_count > 0 && !$slips_portal_ready ? 'warn' : 'neutral'); ?>">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo (int) $slips_in_portal_count; ?> / <?php echo (int) $slip_eligible_count; ?> slips in portal</strong>
                <span><?php echo $slips_portal_ready ? 'Visible to employees' : 'Awaiting payroll approval'; ?></span>
            </div>
        </div>
        <div class="settings-status-chip payroll">
            <span class="status-dot"></span>
            <div>
                <strong>₹<?php echo format_money($total_net_payroll); ?></strong>
                <span>Total net payroll</span>
            </div>
        </div>
    </div>

    <div class="hrm-toolbar">
        <form method="GET" action="payroll_center.php" class="hrm-period-form">
            <div class="form-group">
                <label for="pc-month">Month</label>
                <select name="month" id="pc-month" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m === $payroll_month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="pc-year">Year</label>
                <select name="year" id="pc-year" onchange="this.form.submit()">
                    <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $payroll_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
        <?php if (get_active_branch_id() === null): ?>
            <p class="form-hint hrm-toolbar-hint">Select a branch from the top bar to approve or lock this payroll period.</p>
        <?php else: ?>
            <form method="POST" action="payroll_period_save.php" class="hrm-period-actions">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="month" value="<?php echo $payroll_month; ?>">
                <input type="hidden" name="year" value="<?php echo $payroll_year; ?>">
                <input type="hidden" name="return_to" value="payroll_center.php">
                <?php if ($period_status === 'open'): ?>
                    <button type="submit" name="period_action" value="submit_review" class="btn btn-outline btn-sm">Submit for review</button>
                <?php elseif ($period_status === 'review'): ?>
                    <button type="submit" name="period_action" value="approve" class="btn btn-sm">Approve payroll</button>
                <?php elseif ($period_status === 'approved'): ?>
                    <button type="submit" name="period_action" value="lock" class="btn btn-outline btn-sm">Lock period</button>
                <?php else: ?>
                    <button type="submit" name="period_action" value="reopen" class="btn btn-outline btn-sm">Reopen period</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <div class="panel panel-elevated">
        <div class="dashboard-panel-head dashboard-panel-head-table">
            <div class="dashboard-panel-icon payroll">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <h3>Employee payroll</h3>
                <p><?php echo count($payroll_rows); ?> employees · Net total ₹<?php echo format_money($total_net_payroll); ?></p>
            </div>
        </div>
        <div class="panel-body padded">
            <?php if (count($payroll_rows) > 0): ?>
                <div class="table-wrap">
                    <table class="data-table data-table-compact">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th class="col-num">Paid days</th>
                                <th class="col-num">P / HD / L</th>
                                <th class="col-money">Gross</th>
                                <th class="col-money">Net payable</th>
                                <th>Portal</th>
                                <th class="col-action">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payroll_rows as $row):
                                $emp = $row['employee'];
                                $salary = $row['salary'];
                                $stats = $row['stats'];
                                $initial = strtoupper(substr($emp['name'], 0, 1));
                                $view_url = 'employee_view.php?emp_id=' . rawurlencode($emp['emp_id']) . '&' . $period_query;
                                $gross = (float) ($salary['gross_salary'] ?? $salary['earned_salary'] ?? 0);
                            ?>
                            <tr class="<?php echo $row['is_active'] ? '' : 'emp-row-inactive'; ?>">
                                <td>
                                    <div class="cell-employee">
                                        <span class="emp-avatar"><?php echo htmlspecialchars($initial); ?></span>
                                        <div>
                                            <span class="emp-name"><?php echo htmlspecialchars($emp['name']); ?></span>
                                            <span class="emp-id"><?php echo htmlspecialchars($emp['emp_id']); ?><?php if (!$row['is_active']): ?> · Inactive<?php endif; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-num"><?php echo $row['has_attendance'] ? '<strong>' . format_money($salary['paid_days']) . '</strong>' : '—'; ?></td>
                                <td class="col-num"><?php echo $row['has_attendance'] ? (int) $stats['present_days'] . ' / ' . (int) $stats['half_days'] . ' / ' . (int) $stats['leave_days'] : '—'; ?></td>
                                <td class="col-money"><?php echo $row['has_attendance'] ? '₹' . format_money($gross) : '—'; ?></td>
                                <td class="col-money"><?php echo $row['has_attendance'] ? '<strong class="net-pay-cell">₹' . format_money($salary['net_salary']) . '</strong>' : '<span class="payroll-muted">No attendance</span>'; ?></td>
                                <td>
                                    <?php if ($row['has_attendance'] && (float) $emp['base_salary'] > 0): ?>
                                        <?php if ($row['slip_available']): ?>
                                            <span class="badge badge-present">In portal</span>
                                        <?php elseif ($require_slip_approval): ?>
                                            <span class="badge badge-absent">Awaiting approval</span>
                                        <?php else: ?>—<?php endif; ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="col-action">
                                    <div class="action-btns">
                                        <a href="<?php echo htmlspecialchars($view_url); ?>" class="btn-action btn-view" title="View employee"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                                        <?php if ($row['has_attendance']): ?>
                                            <a href="preview_slip.php?emp_id=<?php echo urlencode($emp['emp_id']); ?>&<?php echo $period_query; ?>" class="btn-action btn-pdf" title="Preview slip" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if ($employees_with_attendance > 0): ?>
                        <tfoot>
                            <tr class="payroll-total-row">
                                <td colspan="4"><strong>Total (active, with attendance)</strong></td>
                                <td class="col-money"><strong class="net-pay-cell">₹<?php echo format_money($total_net_payroll); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state compact">
                    <h4>No employees</h4>
                    <p>Add employees and upload attendance to run payroll.</p>
                    <a href="employees.php" class="btn btn-sm">Manage employees</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
