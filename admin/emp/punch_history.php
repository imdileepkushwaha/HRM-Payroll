<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/attendance_helper.php';
require_once __DIR__ . '/../includes/punch_helper.php';
require_once __DIR__ . '/includes/period.php';

$settings = get_all_settings($conn);
$emp_id = $employee['emp_id'];
[$year, $month] = emp_parse_period();
$period_label = get_period_label($year, $month);
$history = get_employee_punch_history_for_period($conn, $emp_id, $year, $month, $settings, $employee);
$branch_settings = get_branch_punch_settings($conn, (int) $employee['branch_id'], $settings);

[$prev_month, $prev_year] = get_adjacent_period($month, $year, -1);
[$next_month, $next_year] = get_adjacent_period($month, $year, 1);
$period_query = 'year=' . $year . '&month=' . $month;

$punch_days = count($history);
$late_in_count = 0;
$early_out_count = 0;
$missing_out_count = 0;
$total_work_hours = 0.0;
foreach ($history as $row) {
    if (($row['in_status'] ?? '') === 'late') {
        $late_in_count++;
    }
    if (($row['out_status'] ?? '') === 'early') {
        $early_out_count++;
    }
    if (!empty($row['in_at']) && empty($row['out_at'])) {
        $missing_out_count++;
    }
    if ($row['work_hours'] !== null) {
        $total_work_hours += (float) $row['work_hours'];
    }
}

$office_start = date('g:i A', strtotime(get_office_start_time($branch_settings)));
$office_end = date('g:i A', strtotime(get_office_end_time($branch_settings)));
$grace_minutes = (int) get_late_grace_minutes($branch_settings);
?>
<div class="emp-page emp-page-punch-history">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero emp-page-hero-punch">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Punch history</p>
            <h2 class="emp-page-hero-title">In / out times by day</h2>
            <p>Daily punch in/out, work hours, and attendance result for <strong><?php echo htmlspecialchars($period_label); ?></strong>.</p>
        </div>
        <div class="emp-period-nav">
            <a href="punch_history.php?<?php echo 'year=' . $prev_year . '&month=' . $prev_month; ?>" class="emp-period-nav-btn" aria-label="Previous month">&larr;</a>
            <span class="emp-period-nav-label"><?php echo htmlspecialchars($period_label); ?></span>
            <a href="punch_history.php?<?php echo 'year=' . $next_year . '&month=' . $next_month; ?>" class="emp-period-nav-btn" aria-label="Next month">&rarr;</a>
        </div>
    </div>

    <div class="emp-punch-history-stats">
        <div class="emp-punch-stat emp-punch-stat-days">
            <span class="emp-punch-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div>
                <span class="emp-punch-stat-label">Punch days</span>
                <strong class="emp-punch-stat-value"><?php echo (int) $punch_days; ?></strong>
            </div>
        </div>
        <div class="emp-punch-stat emp-punch-stat-late">
            <span class="emp-punch-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <span class="emp-punch-stat-label">Late punch in</span>
                <strong class="emp-punch-stat-value"><?php echo (int) $late_in_count; ?></strong>
            </div>
        </div>
        <div class="emp-punch-stat emp-punch-stat-early">
            <span class="emp-punch-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4-4 4"/><path d="M3 12h18"/></svg>
            </span>
            <div>
                <span class="emp-punch-stat-label">Early punch out</span>
                <strong class="emp-punch-stat-value"><?php echo (int) $early_out_count; ?></strong>
            </div>
        </div>
        <div class="emp-punch-stat emp-punch-stat-hours">
            <span class="emp-punch-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="M2 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="M12 18v4"/><path d="m19.07 19.07-2.83-2.83"/><path d="M22 12h-4"/><path d="m19.07 4.93-2.83 2.83"/><circle cx="12" cy="12" r="4"/></svg>
            </span>
            <div>
                <span class="emp-punch-stat-label">Total work hours</span>
                <strong class="emp-punch-stat-value"><?php echo htmlspecialchars(format_work_hours_label($punch_days > 0 ? $total_work_hours : null)); ?></strong>
            </div>
        </div>
    </div>

    <div class="emp-punch-history-panel">
        <div class="emp-punch-history-toolbar">
            <div class="emp-punch-policy-chips">
                <span class="emp-punch-policy-chip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo htmlspecialchars($office_start); ?> – <?php echo htmlspecialchars($office_end); ?>
                </span>
                <span class="emp-punch-policy-chip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Grace <?php echo $grace_minutes; ?> min
                </span>
                <?php if ($missing_out_count > 0): ?>
                    <span class="emp-punch-policy-chip emp-punch-policy-chip-warn">
                        <?php echo (int) $missing_out_count; ?> day<?php echo $missing_out_count === 1 ? '' : 's'; ?> missing punch out
                    </span>
                <?php endif; ?>
            </div>
            <a href="attendance.php?<?php echo $period_query; ?>" class="btn btn-outline btn-sm">Back to attendance</a>
        </div>

        <?php if ($history === []): ?>
            <div class="emp-punch-history-empty">
                <span class="emp-punch-history-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <h3>No punches this month</h3>
                <p>When you punch in or out from the employee portal, your daily history will appear here.</p>
                <a href="attendance.php?<?php echo $period_query; ?>" class="btn">Go to attendance</a>
            </div>
        <?php else: ?>
            <div class="emp-punch-history-list">
                <?php foreach ($history as $row):
                    $date_ts = strtotime($row['date']);
                    $in_fmt = format_punch_datetime($row['in_at'] ?? null);
                    $out_fmt = format_punch_datetime($row['out_at'] ?? null);
                    $in_badge = format_punctuality_badge($row['in_status'] ?? null, null, 'in');
                    $out_badge = format_punctuality_badge($row['out_status'] ?? null, null, 'out');
                    $att_status = $row['attendance_status'] ?? '—';
                    $att_class = match (strtolower(trim((string) ($row['attendance_status'] ?? '')))) {
                        'present' => 'emp-punch-result-present',
                        'half day' => 'emp-punch-result-half',
                        'absent' => 'emp-punch-result-absent',
                        default => 'emp-punch-result-na',
                    };
                    ?>
                    <article class="emp-punch-day-card">
                        <header class="emp-punch-day-head">
                            <div class="emp-punch-day-date">
                                <span class="emp-punch-day-dow"><?php echo date('D', $date_ts); ?></span>
                                <strong><?php echo date('d M Y', $date_ts); ?></strong>
                            </div>
                            <span class="emp-punch-day-result <?php echo htmlspecialchars($att_class); ?>">
                                <?php echo htmlspecialchars($att_status); ?>
                            </span>
                        </header>
                        <div class="emp-punch-day-body">
                            <div class="emp-punch-day-slot emp-punch-day-slot-in">
                                <span class="emp-punch-day-slot-label">Punch in</span>
                                <strong class="emp-punch-day-slot-time"><?php echo htmlspecialchars($in_fmt['time'] ?: '—'); ?></strong>
                                <span class="punch-punctuality-badge <?php echo htmlspecialchars($in_badge['class']); ?>"<?php echo !empty($in_badge['title']) ? ' title="' . htmlspecialchars($in_badge['title']) . '"' : ''; ?>>
                                    <?php echo htmlspecialchars($in_badge['label']); ?>
                                    <?php if (!empty($in_badge['suffix'])): ?><small><?php echo htmlspecialchars($in_badge['suffix']); ?></small><?php endif; ?>
                                </span>
                            </div>
                            <div class="emp-punch-day-track" aria-hidden="true">
                                <span class="emp-punch-day-track-dot emp-punch-day-track-dot-in"></span>
                                <span class="emp-punch-day-track-line"></span>
                                <span class="emp-punch-day-track-dot emp-punch-day-track-dot-out"></span>
                            </div>
                            <div class="emp-punch-day-slot emp-punch-day-slot-out">
                                <span class="emp-punch-day-slot-label">Punch out</span>
                                <strong class="emp-punch-day-slot-time"><?php echo htmlspecialchars($out_fmt['time'] ?: '—'); ?></strong>
                                <span class="punch-punctuality-badge <?php echo htmlspecialchars($out_badge['class']); ?>"<?php echo !empty($out_badge['title']) ? ' title="' . htmlspecialchars($out_badge['title']) . '"' : ''; ?>>
                                    <?php echo htmlspecialchars($out_badge['label']); ?>
                                    <?php if (!empty($out_badge['suffix'])): ?><small><?php echo htmlspecialchars($out_badge['suffix']); ?></small><?php endif; ?>
                                </span>
                            </div>
                            <div class="emp-punch-day-hours">
                                <span class="emp-punch-day-slot-label">Work hours</span>
                                <strong class="emp-punch-day-hours-value"><?php echo htmlspecialchars(format_work_hours_label($row['work_hours'] ?? null)); ?></strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
