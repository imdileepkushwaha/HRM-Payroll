<?php
require_once 'includes/admin_page_init.php';
admin_page_init('reports');
require_once 'includes/settings_helper.php';

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}
$period_label = get_period_label($year, $month);
$active_branch_label = get_branch_label($conn, get_active_branch_id());
$q = 'month=' . $month . '&year=' . $year;

$reports = [
    [
        'title' => 'Attendance summary',
        'desc' => 'Present, absent, half-day and leave counts per employee with attendance %.',
        'href' => 'report_attendance.php?' . $q,
        'icon' => 'attendance',
        'tag' => 'Monthly',
    ],
    [
        'title' => 'Payroll cost',
        'desc' => 'Net and gross payroll grouped by department for the selected period.',
        'href' => 'report_payroll.php?' . $q,
        'icon' => 'payroll',
        'tag' => 'Monthly',
    ],
    [
        'title' => 'Punch report',
        'desc' => 'Late punch-in and early punch-out counts per employee.',
        'href' => 'punch_report.php?' . $q,
        'icon' => 'punch',
        'tag' => 'Monthly',
    ],
    [
        'title' => 'Leave balances',
        'desc' => 'Current PL, SL, CL balances and pending leave requests for all employees.',
        'href' => 'leave_balances.php',
        'icon' => 'leave',
        'tag' => 'Live',
    ],
    [
        'title' => 'Leave history',
        'desc' => 'All approved, pending and closed leave applications.',
        'href' => 'leave_history.php',
        'icon' => 'history',
        'tag' => 'All time',
    ],
    [
        'title' => 'Team calendar',
        'desc' => 'Holidays and approved leave on a monthly calendar view.',
        'href' => 'team_calendar.php?' . $q,
        'icon' => 'calendar',
        'tag' => 'Monthly',
    ],
];
if (has_permission('expenses')) {
    $reports[] = [
        'title' => 'Expense claims',
        'desc' => 'Pending and approved employee reimbursement requests.',
        'href' => 'expenses.php',
        'icon' => 'expense',
        'tag' => 'Live',
    ];
}
if (has_permission('assets')) {
    $reports[] = [
        'title' => 'Asset inventory',
        'desc' => 'Company assets — available, assigned and retired.',
        'href' => 'assets.php',
        'icon' => 'asset',
        'tag' => 'Live',
    ];
}
if (has_permission('recruitment')) {
    $reports[] = [
        'title' => 'Recruitment pipeline',
        'desc' => 'Open jobs and candidate stages.',
        'href' => 'recruitment.php',
        'icon' => 'recruit',
        'tag' => 'Live',
    ];
}
if (has_permission('org')) {
    $reports[] = [
        'title' => 'Organization chart',
        'desc' => 'Reporting hierarchy and team structure.',
        'href' => 'org_chart.php',
        'icon' => 'org',
        'tag' => 'Live',
    ];
}
?>
<div class="hrm-page reports-hub-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Analytics</p>
            <h2>Reports</h2>
            <p>HR and payroll reports for <strong><?php echo htmlspecialchars($period_label); ?></strong> · <?php echo htmlspecialchars($active_branch_label); ?></p>
        </div>
    </div>

    <div class="hrm-toolbar">
        <form method="GET" action="reports.php" class="hrm-period-form">
            <div class="form-group">
                <label for="rep-month">Default month</label>
                <select name="month" id="rep-month" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="rep-year">Year</label>
                <select name="year" id="rep-year" onchange="this.form.submit()">
                    <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="hrm-report-grid">
        <?php foreach ($reports as $report): ?>
            <a href="<?php echo htmlspecialchars($report['href']); ?>" class="hrm-report-card">
                <span class="hrm-report-card-icon hrm-report-icon-<?php echo htmlspecialchars($report['icon']); ?>" aria-hidden="true">
                    <?php if ($report['icon'] === 'attendance'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php elseif ($report['icon'] === 'payroll'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <?php elseif ($report['icon'] === 'punch'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php elseif ($report['icon'] === 'leave'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/></svg>
                    <?php elseif ($report['icon'] === 'history'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                    <?php elseif ($report['icon'] === 'expense'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <?php elseif ($report['icon'] === 'asset'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/></svg>
                    <?php elseif ($report['icon'] === 'recruit'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                    <?php elseif ($report['icon'] === 'org'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/><path d="M12 7v4M12 11H6M12 11h6"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    <?php endif; ?>
                </span>
                <span class="hrm-report-card-tag"><?php echo htmlspecialchars($report['tag']); ?></span>
                <strong class="hrm-report-card-title"><?php echo htmlspecialchars($report['title']); ?></strong>
                <span class="hrm-report-card-desc"><?php echo htmlspecialchars($report['desc']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
