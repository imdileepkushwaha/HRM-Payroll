<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/salary_helper.php';

$settings = get_all_settings($conn);
$year = max(2000, min(2100, (int) ($_GET['year'] ?? date('Y')));
$summary = get_employee_ytd_summary($conn, $employee, $settings, $year);
$prev_year = $year - 1;
$next_year = $year + 1;
$current_year = (int) date('Y');
$avg_net = count($summary['months']) > 0 ? $summary['total_net'] / count($summary['months']) : 0;
?>
<div class="emp-page emp-page-ytd">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Payroll</p>
            <h2 class="emp-page-hero-title">Year-to-date summary</h2>
            <p>Your earnings and attendance overview for <?php echo (int) $year; ?>. <a href="salary_slips.php">View salary slips →</a></p>
        </div>
        <div class="emp-period-nav">
            <?php if ($year > 2000): ?><a href="ytd.php?year=<?php echo $prev_year; ?>" class="emp-period-nav-btn" aria-label="Previous year">&larr;</a><?php endif; ?>
            <span class="emp-period-nav-label"><?php echo (int) $year; ?></span>
            <?php if ($year < $current_year): ?><a href="ytd.php?year=<?php echo $next_year; ?>" class="emp-period-nav-btn" aria-label="Next year">&rarr;</a><?php endif; ?>
        </div>
    </div>

    <div class="emp-ytd-highlight">
        <div class="emp-ytd-highlight-main">
            <span class="emp-ytd-highlight-label">Total net salary (YTD)</span>
            <strong class="emp-ytd-highlight-value"><?php echo format_money($summary['total_net']); ?></strong>
            <?php if ($avg_net > 0): ?>
                <span class="emp-ytd-highlight-sub">Avg <?php echo format_money($avg_net); ?> / month with attendance</span>
            <?php endif; ?>
        </div>
        <div class="emp-ytd-highlight-aside">
            <div class="emp-ytd-mini-stat"><span>Months</span><strong><?php echo count($summary['months']); ?></strong></div>
            <div class="emp-ytd-mini-stat"><span>Slips ready</span><strong><?php echo (int) $summary['slip_count']; ?></strong></div>
        </div>
    </div>

    <section class="emp-card emp-ytd-card">
        <header class="emp-card-toolbar emp-reg-toolbar">
            <div><h3 class="emp-card-title">Monthly breakdown</h3><p class="emp-reg-toolbar-sub">Paid days, net salary and slip availability per month.</p></div>
            <form method="GET" class="emp-ytd-year-form">
                <label class="emp-ytd-year-label">Year
                    <select name="year" onchange="this.form.submit()">
                        <?php for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </form>
        </header>

        <?php if ($summary['months'] === []): ?>
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg></span>
                <strong>No payroll data for <?php echo (int) $year; ?></strong>
                <p>Attendance and salary will appear here once payroll is processed.</p>
            </div>
        <?php else: ?>
            <div class="emp-ytd-month-list">
                <?php foreach ($summary['months'] as $row): ?>
                <article class="emp-ytd-month-row">
                    <div class="emp-ytd-month-label">
                        <strong><?php echo htmlspecialchars($row['label']); ?></strong>
                        <span><?php echo format_money((float) $row['paid_days']); ?> paid days</span>
                    </div>
                    <div class="emp-ytd-month-net">
                        <strong><?php echo format_money((float) $row['net_salary']); ?></strong>
                        <span>Net salary</span>
                    </div>
                    <div class="emp-ytd-month-slip">
                        <?php if ($row['has_slip']): ?>
                            <span class="emp-ytd-slip-badge is-ready">Slip ready</span>
                            <a href="salary_slips.php" class="emp-ytd-slip-link">Open slips</a>
                        <?php else: ?>
                            <span class="emp-ytd-slip-badge">No slip yet</span>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <footer class="emp-ytd-footer">
                <span>Total net (<?php echo (int) $year; ?>)</span>
                <strong><?php echo format_money($summary['total_net']); ?></strong>
            </footer>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
