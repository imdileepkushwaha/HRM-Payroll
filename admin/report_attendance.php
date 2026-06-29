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
$report = get_attendance_summary_report($conn, $year, $month, $settings, $branch_id);
$rows = $report['rows'];
$totals = $report['totals'];
$working_days = $report['working_days'];
$q = 'month=' . $month . '&year=' . $year;
?>
<div class="hrm-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Reports</p>
            <h2>Attendance summary</h2>
            <p><strong><?php echo htmlspecialchars($period_label); ?></strong> · <?php echo htmlspecialchars($active_branch_label); ?> · <?php echo $working_days; ?> working days</p>
        </div>
        <div class="page-header-actions">
            <a href="reports.php?<?php echo $q; ?>" class="btn btn-outline">All reports</a>
            <a href="upload_attendance.php?<?php echo $q; ?>" class="btn btn-header">Upload attendance</a>
        </div>
    </div>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($rows); ?> employees</strong><span>Active in scope</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo (int) $totals['present']; ?></strong><span>Present marks</span></div></div>
        <div class="settings-status-chip warn"><span class="status-dot"></span><div><strong><?php echo (int) $totals['leave']; ?></strong><span>Leave marks</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo format_money($totals['paid_days']); ?></strong><span>Total paid days</span></div></div>
    </div>

    <div class="hrm-toolbar">
        <form method="GET" class="hrm-period-form">
            <div class="form-group"><label for="ra-month">Month</label><select name="month" id="ra-month" onchange="this.form.submit()"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option><?php endfor; ?></select></div>
            <div class="form-group"><label for="ra-year">Year</label><select name="year" id="ra-year" onchange="this.form.submit()"><?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?><option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?></select></div>
        </form>
    </div>

    <div class="panel panel-elevated">
        <div class="panel-body padded">
            <?php if (count($rows) > 0): ?>
            <div class="table-wrap">
                <table class="data-table data-table-compact">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="col-num">Present</th>
                            <th class="col-num">Half</th>
                            <th class="col-num">Leave</th>
                            <th class="col-num">Absent</th>
                            <th class="col-num">Paid days</th>
                            <th class="col-num">Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row):
                            $emp = $row['employee'];
                            $stats = $row['stats'];
                            $pct = $row['attendance_pct'];
                            $pct_class = $pct >= 90 ? 'ok' : ($pct >= 75 ? 'warn' : 'bad');
                        ?>
                        <tr>
                            <td><div class="cell-employee"><span class="emp-avatar"><?php echo htmlspecialchars(strtoupper(substr($emp['name'], 0, 1))); ?></span><div><span class="emp-name"><?php echo htmlspecialchars($emp['name']); ?></span><span class="emp-id"><?php echo htmlspecialchars($emp['emp_id']); ?></span></div></div></td>
                            <td><?php echo htmlspecialchars($emp['department'] ?: '—'); ?></td>
                            <td class="col-num"><?php echo (int) $stats['present_days']; ?></td>
                            <td class="col-num"><?php echo (int) $stats['half_days']; ?></td>
                            <td class="col-num"><?php echo (int) $stats['leave_days']; ?></td>
                            <td class="col-num"><?php echo (int) $stats['absent_days']; ?></td>
                            <td class="col-num"><strong><?php echo format_money($stats['paid_days']); ?></strong></td>
                            <td class="col-num"><span class="hrm-pct hrm-pct-<?php echo $pct_class; ?>"><?php echo $row['marked_days'] > 0 ? $pct . '%' : '—'; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state compact"><h4>No active employees</h4><p>Add employees to generate this report.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
