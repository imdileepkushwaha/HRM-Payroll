<?php
require 'includes/header.php';
require 'config.php';
require_once 'includes/punch_helper.php';
require_once 'includes/settings_helper.php';
require_once 'includes/salary_helper.php';

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$period_label = date('F Y', mktime(0, 0, 0, $month, 1, $year));
$settings = get_all_settings($conn);
$rows = get_branch_punch_report_for_period($conn, $year, $month, $branch_id);
$late_threshold = (int) ($settings['late_count_for_half_day'] ?? 0);

$total_late = 0;
$total_early = 0;
foreach ($rows as $row) {
    $total_late += (int) ($row['late_count'] ?? 0);
    $total_early += (int) ($row['early_count'] ?? 0);
}
?>
<div class="punch-logs-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Attendance</p>
            <h2>Monthly punch report</h2>
            <p>Late and early summary per employee for <strong><?php echo htmlspecialchars($period_label); ?></strong> · <strong><?php echo htmlspecialchars($active_branch_label); ?></strong></p>
        </div>
        <div class="page-header-actions">
            <a href="punch_logs.php?year=<?php echo $year; ?>&amp;month=<?php echo $month; ?>" class="btn btn-outline">Punch logs</a>
            <a href="settings.php?tab=punch" class="btn btn-header">Punch settings</a>
        </div>
    </div>

    <div class="settings-status punch-logs-status">
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo count($rows); ?> employees</strong>
                <span>Active in branch</span>
            </div>
        </div>
        <div class="settings-status-chip warn">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo $total_late; ?> late punch-ins</strong>
                <span>Across all employees</span>
            </div>
        </div>
        <div class="settings-status-chip warn">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo $total_early; ?> early punch-outs</strong>
                <span>Across all employees</span>
            </div>
        </div>
        <?php if ($late_threshold > 0): ?>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div>
                <strong>Payroll rule</strong>
                <span>Every <?php echo $late_threshold; ?> lates = 1 half-day deduction</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="punch-logs-layout">
        <div class="panel panel-elevated punch-logs-panel">
            <div class="dashboard-panel-head dashboard-panel-head-table">
                <div class="dashboard-panel-icon punch">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                </div>
                <div>
                    <h3>Employee summary</h3>
                    <p>Late in, early out, and payroll late penalty for the month</p>
                </div>
                <div class="dashboard-panel-head-actions">
                    <form method="GET" class="dashboard-panel-period-filter dashboard-period-form">
                        <div class="form-group">
                            <label for="punch-report-month">Month</label>
                            <select name="month" id="punch-report-month" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="punch-report-year">Year</label>
                            <select name="year" id="punch-report-year" onchange="this.form.submit()">
                                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <div class="panel-body padded">
                <?php if ($rows === []): ?>
                    <p class="form-hint">No active employees in this branch.</p>
                <?php else: ?>
                    <div class="table-wrap punch-logs-table-wrap">
                        <table class="data-table data-table-compact punch-logs-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Punch in</th>
                                    <th>Punch out</th>
                                    <th>Late in</th>
                                    <th>Early out</th>
                                    <th>Late penalty</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row):
                                    $emp_id = $row['emp_id'] ?? '';
                                    $late_count = (int) ($row['late_count'] ?? 0);
                                    $early_count = (int) ($row['early_count'] ?? 0);
                                    $penalty = calculate_late_punch_penalty_days($late_count, $settings);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['name'] ?? $emp_id); ?></strong>
                                            <div class="form-hint"><?php echo htmlspecialchars($emp_id); ?></div>
                                        </td>
                                        <td><?php echo (int) ($row['punch_in_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($row['punch_out_count'] ?? 0); ?></td>
                                        <td><span class="punch-punctuality-late"><?php echo $late_count; ?></span></td>
                                        <td><span class="punch-punctuality-early"><?php echo $early_count; ?></span></td>
                                        <td><?php echo $penalty > 0 ? format_money($penalty) . ' day' : '—'; ?></td>
                                        <td><a href="employee_view.php?emp_id=<?php echo urlencode($emp_id); ?>&amp;year=<?php echo $year; ?>&amp;month=<?php echo $month; ?>">Profile</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
