<?php
require 'includes/header.php';
require 'config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/salary_helper.php';
require_once 'includes/punch_helper.php';
require_once 'includes/payroll_extensions.php';
require 'includes/employee_helper.php';
require_once 'includes/csrf_helper.php';

$settings = get_all_settings($conn);
$branch_filter = branch_employees_sql('e');
$emp_count_stmt = $conn->prepare('SELECT COUNT(*) as count FROM employees e WHERE 1=1' . $branch_filter['sql']);
bind_branch_stmt_params($emp_count_stmt, $branch_filter['types'], $branch_filter['params']);
$emp_count_stmt->execute();
$emp_count = (int) $emp_count_stmt->get_result()->fetch_assoc()['count'];

$att_count_stmt = $conn->prepare('SELECT COUNT(*) as count FROM attendance a INNER JOIN employees e ON e.emp_id = a.emp_id WHERE 1=1' . $branch_filter['sql']);
bind_branch_stmt_params($att_count_stmt, $branch_filter['types'], $branch_filter['params']);
$att_count_stmt->execute();
$att_count = (int) $att_count_stmt->get_result()->fetch_assoc()['count'];

$present_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance a INNER JOIN employees e ON e.emp_id = a.emp_id WHERE LOWER(a.status) = 'present'" . $branch_filter['sql']);
bind_branch_stmt_params($present_count_stmt, $branch_filter['types'], $branch_filter['params']);
$present_count_stmt->execute();
$present_count = (int) $present_count_stmt->get_result()->fetch_assoc()['count'];
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
$require_slip_approval = !isset($settings['require_payroll_approval']) || (int) $settings['require_payroll_approval'] === 1;
$payroll_branch_id = get_active_branch_id() ?? payroll_context_branch_id();
$slips_portal_ready = !$require_slip_approval || can_release_salary_slips_for_period($conn, $payroll_year, $payroll_month, $payroll_branch_id);
$payroll_rows = [];
$total_net_payroll = 0.0;
$employees_with_attendance = 0;
$active_employee_count = 0;

$employees_sql = 'SELECT e.* FROM employees e WHERE 1=1' . $branch_filter['sql'] . ' ORDER BY e.is_active DESC, e.name ASC';
$employees_stmt = $conn->prepare($employees_sql);
bind_branch_stmt_params($employees_stmt, $branch_filter['types'], $branch_filter['params']);
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();
while ($emp = $employees_result->fetch_assoc()) {
    $is_active = employee_is_active($emp);
    if ($is_active) {
        $active_employee_count++;
    }

    $stats = get_attendance_stats_extended($conn, $emp['emp_id'], $payroll_year, $payroll_month, $settings);
    $salary = calculate_employee_salary_full($conn, $emp, $payroll_year, $payroll_month, $settings);
    $has_attendance = $stats['total_records'] > 0;

    if ($has_attendance) {
        $employees_with_attendance++;
    }
    if ($is_active && $has_attendance) {
        $total_net_payroll += $salary['net_salary'];
    }

    $payroll_rows[] = [
        'employee' => $emp,
        'is_active' => $is_active,
        'stats' => $stats,
        'salary' => $salary,
        'has_attendance' => $has_attendance,
    ];
}

$slip_eligible_count = 0;
$slips_in_portal_count = 0;

foreach ($payroll_rows as $row) {
    $emp = $row['employee'];
    if (!$row['is_active'] || (float) $emp['base_salary'] <= 0 || !$row['has_attendance']) {
        continue;
    }
    $slip_eligible_count++;
    if (employee_salary_slip_is_available($conn, $emp, $payroll_year, $payroll_month, $settings)) {
        $slips_in_portal_count++;
    }
}
$company_name = $settings['company_name'] ?? 'Company';
$working_days = (int) get_working_days_per_month($settings);
$payroll_period = get_payroll_period($conn, $payroll_year, $payroll_month);
$period_status = $payroll_period['status'] ?? 'open';
$period_locked = $period_status === 'locked';

$punch_enabled = is_punch_enabled($settings);
$punch_today = $punch_enabled
    ? get_branch_punch_day_stats($conn, get_active_branch_id(), date('Y-m-d'))
    : null;
$today_year = (int) date('Y');
$today_month = (int) date('n');
$punch_logs_today_late_url = 'punch_logs.php?year=' . $today_year . '&month=' . $today_month . '&punctuality=late';
$punch_logs_today_early_url = 'punch_logs.php?year=' . $today_year . '&month=' . $today_month . '&punctuality=early';
$punch_logs_today_url = 'punch_logs.php?year=' . $today_year . '&month=' . $today_month;

?>
<div class="dashboard-page">
<div class="page-header page-header-row dashboard-page-header">
    <div class="page-header-main">
        <p class="page-eyebrow">Overview</p>
        <h2>Dashboard</h2>
        <p>Payroll for <strong><?php echo htmlspecialchars($payroll_period_label); ?></strong> · <?php echo htmlspecialchars($company_name); ?> · <strong><?php echo htmlspecialchars($active_branch_label); ?></strong></p>
    </div>
    <div class="page-header-actions">
        <a href="employees.php" class="btn btn-outline">Employees</a>
        <a href="punch_logs.php" class="btn btn-outline">Punch Logs</a>
        <a href="upload_attendance.php" class="btn btn-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload Attendance
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<div class="settings-status dashboard-status">
    <div class="settings-status-chip neutral">
        <span class="status-dot"></span>
        <div>
            <strong><?php echo htmlspecialchars($payroll_period_label); ?></strong>
            <span><?php echo (int) $employees_with_attendance; ?> with attendance · <?php echo $working_days; ?> working days</span>
        </div>
    </div>
    <div class="settings-status-chip <?php echo $slip_eligible_count > 0 && $slips_in_portal_count >= $slip_eligible_count ? 'ok' : ($slip_eligible_count > 0 && !$slips_portal_ready ? 'warn' : 'neutral'); ?>">
        <span class="status-dot"></span>
        <div>
            <strong><?php echo (int) $slips_in_portal_count; ?> / <?php echo (int) $slip_eligible_count; ?> slips in portal</strong>
            <span><?php echo $slips_portal_ready ? 'Visible in employee portal' : 'Approve payroll to release slips'; ?></span>
        </div>
    </div>
    <div class="settings-status-chip <?php echo $period_status === 'approved' || $period_status === 'locked' ? 'ok' : ($period_status === 'review' ? 'warn' : 'neutral'); ?>">
        <span class="status-dot"></span>
        <div>
            <strong>Payroll: <?php echo htmlspecialchars(payroll_period_status_label($period_status)); ?></strong>
            <span><?php echo $period_locked ? 'Attendance locked' : ($require_slip_approval ? 'Approve to show slips in portal' : 'Slips auto-visible when attendance exists'); ?></span>
        </div>
    </div>
    <?php if ($punch_enabled && $punch_today !== null): ?>
    <div class="settings-status-chip <?php echo ($punch_today['late_in_count'] + $punch_today['early_out_count']) > 0 ? 'warn' : 'ok'; ?>">
        <span class="status-dot"></span>
        <div>
            <strong>Today: <?php echo (int) $punch_today['late_in_count']; ?> late · <?php echo (int) $punch_today['early_out_count']; ?> early</strong>
            <span>
                <?php echo (int) $punch_today['employee_count']; ?> employees punched
                · <a href="<?php echo htmlspecialchars($punch_logs_today_url); ?>">Punch logs</a>
                <?php if ($punch_today['late_in_count'] > 0): ?>
                    · <a href="<?php echo htmlspecialchars($punch_logs_today_late_url); ?>">Late only</a>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card-container dashboard-stats">
    <div class="stat-card stat-card-primary">
        <div class="stat-icon-wrap employees">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-body">
            <h3>Total employees</h3>
            <p class="stat-value"><?php echo (int) $emp_count; ?></p>
            <span class="stat-meta"><?php echo (int) $active_employee_count; ?> active</span>
        </div>
    </div>
    <div class="stat-card stat-card-accent">
        <div class="stat-icon-wrap attendance">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-body">
            <h3>Attendance records</h3>
            <p class="stat-value"><?php echo (int) $att_count; ?></p>
            <span class="stat-meta"><?php echo (int) $present_count; ?> present marks (all time)</span>
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon-wrap present">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-body">
            <h3>With attendance</h3>
            <p class="stat-value"><?php echo (int) $employees_with_attendance; ?></p>
            <span class="stat-meta">For <?php echo htmlspecialchars($payroll_period_label); ?></span>
        </div>
    </div>
    <div class="stat-card stat-card-payroll">
        <div class="stat-icon-wrap payroll">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-body">
            <h3>Net payroll</h3>
            <p class="stat-value stat-value-money">₹<?php echo format_money($total_net_payroll); ?></p>
            <span class="stat-meta">Active employees with attendance</span>
        </div>
    </div>
</div>

<div class="dashboard-grid dashboard-grid-single">
<aside class="dashboard-aside">
    <div class="dashboard-aside-card">
        <h4>Period filter</h4>
        <p class="dashboard-aside-desc">Switch month for the payroll summary table.</p>
        <form method="GET" action="dashboard.php" class="dashboard-period-form">
            <div class="form-group">
                <label for="payroll-month">Month</label>
                <select name="month" id="payroll-month" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m === $payroll_month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="payroll-year">Year</label>
                <select name="year" id="payroll-year" onchange="this.form.submit()">
                    <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $payroll_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="dashboard-aside-card">
        <h4>Today's punch</h4>
        <p class="dashboard-aside-desc">Live attendance punch summary for <?php echo htmlspecialchars(date('j M Y')); ?>.</p>
        <?php if (!$punch_enabled): ?>
            <p class="form-hint">Punch is disabled. Enable it in <a href="settings.php?tab=punch">Settings → Punch &amp; Geo</a>.</p>
        <?php else: ?>
            <div class="dashboard-punch-today-stats">
                <div class="dashboard-punch-today-stat">
                    <span>Late in</span>
                    <strong class="<?php echo $punch_today['late_in_count'] > 0 ? 'is-warn' : ''; ?>"><?php echo (int) $punch_today['late_in_count']; ?></strong>
                </div>
                <div class="dashboard-punch-today-stat">
                    <span>Early out</span>
                    <strong class="<?php echo $punch_today['early_out_count'] > 0 ? 'is-warn' : ''; ?>"><?php echo (int) $punch_today['early_out_count']; ?></strong>
                </div>
                <div class="dashboard-punch-today-stat">
                    <span>Punched</span>
                    <strong><?php echo (int) $punch_today['employee_count']; ?></strong>
                </div>
                <div class="dashboard-punch-today-stat">
                    <span>Rejected</span>
                    <strong class="<?php echo $punch_today['rejected_count'] > 0 ? 'is-warn' : ''; ?>"><?php echo (int) $punch_today['rejected_count']; ?></strong>
                </div>
            </div>
            <div class="dashboard-punch-today-actions">
                <a href="<?php echo htmlspecialchars($punch_logs_today_url); ?>" class="btn btn-outline btn-sm btn-block">View punch logs</a>
                <?php if ($punch_today['late_in_count'] > 0): ?>
                    <a href="<?php echo htmlspecialchars($punch_logs_today_late_url); ?>" class="btn-link">Filter late in today</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="dashboard-aside-card">
        <h4>Payroll period</h4>
        <p class="dashboard-aside-desc">Approve or lock this month<?php echo get_active_branch_id() === null ? ' for a branch' : ' for ' . htmlspecialchars($active_branch_label); ?>. Employees see slips in their portal after approval.</p>
        <?php if (get_active_branch_id() === null): ?>
            <p class="form-hint">Select <strong>Indra Nagar</strong> or <strong>Alambagh</strong> from the top bar to manage period status.</p>
        <?php else: ?>
        <form method="POST" action="payroll_period_save.php" class="dashboard-period-actions">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="month" value="<?php echo $payroll_month; ?>">
            <input type="hidden" name="year" value="<?php echo $payroll_year; ?>">
            <?php if ($period_status === 'open'): ?>
                <button type="submit" name="period_action" value="submit_review" class="btn btn-outline btn-sm btn-block">Submit for review</button>
            <?php elseif ($period_status === 'review'): ?>
                <button type="submit" name="period_action" value="approve" class="btn btn-sm btn-block">Approve payroll</button>
            <?php elseif ($period_status === 'approved'): ?>
                <button type="submit" name="period_action" value="lock" class="btn btn-outline btn-sm btn-block">Lock period</button>
            <?php else: ?>
                <button type="submit" name="period_action" value="reopen" class="btn btn-outline btn-sm btn-block">Reopen period</button>
            <?php endif; ?>
        </form>
        <?php endif; ?>
    </div>
</aside>
</div>

<div class="panel panel-elevated dashboard-payroll-panel" id="payroll-overview">
    <div class="dashboard-panel-head dashboard-panel-head-table">
        <div class="dashboard-panel-icon payroll">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div>
            <h3>Monthly payroll summary</h3>
            <p><?php echo htmlspecialchars($payroll_period_label); ?> · <?php echo count($payroll_rows); ?> employees listed</p>
        </div>
        <span class="dashboard-panel-total">₹<?php echo format_money($total_net_payroll); ?></span>
    </div>
    <div class="panel-body padded">
        <?php if (count($payroll_rows) > 0): ?>
            <div class="table-wrap dashboard-table-wrap">
                <table class="data-table data-table-compact dashboard-payroll-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th class="col-num">Paid days</th>
                            <th class="col-num">P / HD / L</th>
                            <th class="col-money">Net payable</th>
                            <th>Portal</th>
                            <th class="col-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payroll_rows as $row): ?>
                            <?php
                            $emp = $row['employee'];
                            $salary = $row['salary'];
                            $stats = $row['stats'];
                            $initial = strtoupper(substr($emp['name'], 0, 1));
                            $view_url = 'employee_view.php?emp_id=' . rawurlencode($emp['emp_id']) . '&month=' . $payroll_month . '&year=' . $payroll_year;
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
                                <td class="col-num">
                                    <?php if ($row['has_attendance']): ?>
                                        <strong><?php echo format_money($salary['paid_days']); ?></strong>
                                    <?php else: ?>
                                        <span class="payroll-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-num payroll-phl">
                                    <?php if ($row['has_attendance']): ?>
                                        <?php echo (int) $stats['present_days']; ?> / <?php echo (int) $stats['half_days']; ?> / <?php echo (int) $stats['leave_days']; ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="col-money">
                                    <?php if ($row['has_attendance']): ?>
                                        <strong class="net-pay-cell">₹<?php echo format_money($salary['net_salary']); ?></strong>
                                    <?php else: ?>
                                        <span class="payroll-muted">No attendance</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['has_attendance'] && (float) $emp['base_salary'] > 0): ?>
                                        <?php if (employee_salary_slip_is_available($conn, $emp, $payroll_year, $payroll_month, $settings)): ?>
                                            <span class="badge badge-present">In portal</span>
                                        <?php elseif ($require_slip_approval): ?>
                                            <span class="badge badge-absent">Awaiting approval</span>
                                        <?php else: ?>
                                            <span class="payroll-muted">—</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="payroll-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-action">
                                    <div class="action-btns">
                                        <a href="<?php echo htmlspecialchars($view_url); ?>" class="btn-action btn-view" title="View employee">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <?php if ($row['has_attendance']): ?>
                                            <a href="preview_slip.php?emp_id=<?php echo urlencode($emp['emp_id']); ?>&month=<?php echo $payroll_month; ?>&year=<?php echo $payroll_year; ?>" class="btn-action btn-pdf" title="Preview PDF slip" target="_blank" rel="noopener">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($employees_with_attendance > 0): ?>
                        <tfoot>
                            <tr class="payroll-total-row">
                                <td colspan="3"><strong>Total (active, with attendance)</strong></td>
                                <td class="col-money"><strong class="net-pay-cell">₹<?php echo format_money($total_net_payroll); ?></strong></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            <p class="dashboard-payroll-footnote">
                Net salary uses paid days (P + HD + L credits). Employees view slips in <strong>Employee portal → Salary slips</strong> after payroll is approved. <a href="upload_attendance.php">Upload attendance</a> if figures are missing.
            </p>
        <?php else: ?>
            <div class="empty-state compact dashboard-empty">
                <h4>No employees yet</h4>
                <p>Add employees and upload attendance to see monthly payroll here.</p>
                <a href="employees.php" class="btn btn-sm">Manage employees</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</div>

<?php require 'includes/footer.php'; ?>
