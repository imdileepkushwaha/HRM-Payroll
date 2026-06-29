<?php
require_once 'includes/admin_page_init.php';
admin_page_init('reports');
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_helper.php';

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$settings = get_all_settings($conn);
$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$period_label = get_period_label($year, $month);
$report = get_payroll_summary_by_department($conn, $year, $month, $settings, $branch_id);
$q = 'month=' . $month . '&year=' . $year;
?>
<div class="hrm-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Reports</p>
            <h2>Payroll cost report</h2>
            <p>Department-wise payroll for <strong><?php echo htmlspecialchars($period_label); ?></strong> · <?php echo htmlspecialchars($active_branch_label); ?></p>
        </div>
        <div class="page-header-actions">
            <a href="reports.php?<?php echo $q; ?>" class="btn btn-outline">All reports</a>
            <a href="payroll_center.php?<?php echo $q; ?>" class="btn btn-header">Payroll Center</a>
        </div>
    </div>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip payroll"><span class="status-dot"></span><div><strong>₹<?php echo format_money($report['grand_net']); ?></strong><span>Total net payroll</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong>₹<?php echo format_money($report['grand_gross']); ?></strong><span>Total gross</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo (int) $report['employee_count']; ?></strong><span>Employees with attendance</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($report['departments']); ?></strong><span>Departments</span></div></div>
    </div>

    <div class="hrm-toolbar">
        <form method="GET" class="hrm-period-form">
            <div class="form-group"><label for="rp-month">Month</label><select name="month" id="rp-month" onchange="this.form.submit()"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option><?php endfor; ?></select></div>
            <div class="form-group"><label for="rp-year">Year</label><select name="year" id="rp-year" onchange="this.form.submit()"><?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?><option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?></select></div>
        </form>
    </div>

    <div class="panel panel-elevated">
        <div class="panel-body padded">
            <?php if (count($report['departments']) > 0): ?>
            <div class="table-wrap">
                <table class="data-table data-table-compact">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th class="col-num">Employees</th>
                            <th class="col-num">Paid days</th>
                            <th class="col-money">Gross</th>
                            <th class="col-money">Net payable</th>
                            <th class="col-num">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['departments'] as $dept):
                            $share = $report['grand_net'] > 0 ? round(($dept['net'] / $report['grand_net']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['department']); ?></strong></td>
                            <td class="col-num"><?php echo (int) $dept['employees']; ?></td>
                            <td class="col-num"><?php echo format_money($dept['paid_days']); ?></td>
                            <td class="col-money">₹<?php echo format_money($dept['gross']); ?></td>
                            <td class="col-money"><strong class="net-pay-cell">₹<?php echo format_money($dept['net']); ?></strong></td>
                            <td class="col-num"><?php echo $share; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="payroll-total-row">
                            <td><strong>Total</strong></td>
                            <td class="col-num"><?php echo (int) $report['employee_count']; ?></td>
                            <td></td>
                            <td class="col-money">₹<?php echo format_money($report['grand_gross']); ?></td>
                            <td class="col-money"><strong class="net-pay-cell">₹<?php echo format_money($report['grand_net']); ?></strong></td>
                            <td class="col-num">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state compact"><h4>No payroll data</h4><p>Upload attendance for this month to see department-wise costs.</p><a href="upload_attendance.php?<?php echo $q; ?>" class="btn btn-sm">Upload attendance</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
