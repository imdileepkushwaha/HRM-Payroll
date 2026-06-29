<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$settings = get_all_settings($conn);
$year = max(2000, min(2100, (int) ($_GET['year'] ?? date('Y'))));
$summary = get_employee_ytd_summary($conn, $employee, $settings, $year);
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>
    <header class="emp-page-head">
        <h1>Year-to-date summary</h1>
        <p>Payroll overview for <?php echo (int) $year; ?>.</p>
    </header>
    <form method="GET" class="emp-form emp-form-inline emp-panel">
        <label>Year <input type="number" name="year" value="<?php echo $year; ?>" min="2000" max="2100" onchange="this.form.submit()"></label>
    </form>
    <div class="emp-dash-stats">
        <div class="emp-dash-stat"><span class="emp-dash-stat-label">Total net (YTD)</span><strong class="emp-dash-stat-value"><?php echo format_money($summary['total_net']); ?></strong></div>
        <div class="emp-dash-stat"><span class="emp-dash-stat-label">Months with attendance</span><strong class="emp-dash-stat-value"><?php echo count($summary['months']); ?></strong></div>
        <div class="emp-dash-stat"><span class="emp-dash-stat-label">Slips in portal</span><strong class="emp-dash-stat-value"><?php echo (int) $summary['slip_count']; ?></strong></div>
    </div>
    <div class="emp-panel">
        <h2>Monthly breakdown</h2>
        <?php if ($summary['months'] === []): ?>
            <p class="emp-muted">No attendance recorded for this year yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table emp-ytd-table">
                <thead><tr><th>Month</th><th>Paid days</th><th>Net salary</th><th>Slip</th></tr></thead>
                <tbody>
                    <?php foreach ($summary['months'] as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['label']); ?></td>
                        <td><?php echo format_money((float) $row['paid_days']); ?></td>
                        <td><?php echo format_money((float) $row['net_salary']); ?></td>
                        <td><?php echo $row['has_slip'] ? 'Available' : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
