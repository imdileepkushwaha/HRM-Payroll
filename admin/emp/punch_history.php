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
$today_date = date('Y-m-d');

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
$complete_days = 0;
foreach ($history as $row) {
    if (!empty($row['in_at']) && !empty($row['out_at'])) {
        $complete_days++;
    }
}
?>
<div class="emp-page emp-page-punch-history">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <header class="emp-ph-banner">
        <div class="emp-ph-banner-decor" aria-hidden="true">
            <span class="emp-ph-banner-orb emp-ph-banner-orb-1"></span>
            <span class="emp-ph-banner-orb emp-ph-banner-orb-2"></span>
        </div>
        <div class="emp-ph-banner-grid">
            <div class="emp-ph-banner-main">
                <p class="emp-ph-eyebrow">Attendance</p>
                <h1>Punch history</h1>
                <p>Daily punch in/out, work hours, and attendance result for <strong><?php echo htmlspecialchars($period_label); ?></strong>.</p>
                <div class="emp-ph-banner-tags">
                    <span class="emp-ph-banner-tag">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php echo htmlspecialchars($office_start); ?> – <?php echo htmlspecialchars($office_end); ?>
                    </span>
                    <?php if ($grace_minutes > 0): ?>
                        <span class="emp-ph-banner-tag"><?php echo (int) $grace_minutes; ?> min grace</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="emp-ph-banner-aside">
                <nav class="emp-ph-period-nav" aria-label="Select month">
                    <a href="punch_history.php?<?php echo 'year=' . $prev_year . '&month=' . $prev_month; ?>" class="emp-ph-period-btn" aria-label="Previous month">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                    <span class="emp-ph-period-label"><?php echo htmlspecialchars($period_label); ?></span>
                    <a href="punch_history.php?<?php echo 'year=' . $next_year . '&month=' . $next_month; ?>" class="emp-ph-period-btn" aria-label="Next month">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </nav>
                <div class="emp-ph-banner-links">
                    <a href="dashboard.php" class="emp-ph-banner-link">Dashboard</a>
                    <a href="attendance.php?<?php echo $period_query; ?>" class="emp-ph-banner-link emp-ph-banner-link-primary">Attendance</a>
                </div>
            </div>
        </div>
    </header>

    <div class="emp-ph-stats">
        <div class="emp-ph-stat emp-ph-stat-days">
            <span class="emp-ph-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div>
                <span class="emp-ph-stat-label">Punch days</span>
                <strong class="emp-ph-stat-value"><?php echo (int) $punch_days; ?></strong>
                <span class="emp-ph-stat-hint">Days with punch activity</span>
            </div>
        </div>
        <div class="emp-ph-stat emp-ph-stat-late">
            <span class="emp-ph-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <span class="emp-ph-stat-label">Late punch in</span>
                <strong class="emp-ph-stat-value"><?php echo (int) $late_in_count; ?></strong>
                <span class="emp-ph-stat-hint">After office start + grace</span>
            </div>
        </div>
        <div class="emp-ph-stat emp-ph-stat-early">
            <span class="emp-ph-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 8l4 4-4 4"/><path d="M3 12h18"/></svg>
            </span>
            <div>
                <span class="emp-ph-stat-label">Early punch out</span>
                <strong class="emp-ph-stat-value"><?php echo (int) $early_out_count; ?></strong>
                <span class="emp-ph-stat-hint">Before office end − grace</span>
            </div>
        </div>
        <div class="emp-ph-stat emp-ph-stat-hours">
            <span class="emp-ph-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="M2 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="M12 18v4"/><path d="m19.07 19.07-2.83-2.83"/><path d="M22 12h-4"/><path d="m19.07 4.93-2.83 2.83"/><circle cx="12" cy="12" r="4"/></svg>
            </span>
            <div>
                <span class="emp-ph-stat-label">Total work hours</span>
                <strong class="emp-ph-stat-value"><?php echo htmlspecialchars(format_work_hours_label($punch_days > 0 ? $total_work_hours : null)); ?></strong>
                <span class="emp-ph-stat-hint"><?php echo $punch_days > 0 ? 'Sum of completed days' : 'No punches yet'; ?></span>
            </div>
        </div>
    </div>

    <section class="emp-ph-panel">
        <header class="emp-ph-panel-head">
            <div class="emp-ph-panel-title">
                <span class="emp-ph-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <div>
                    <h2>Daily log</h2>
                    <p><?php echo $history === [] ? 'No punch records for this month' : (int) $punch_days . ' day' . ($punch_days === 1 ? '' : 's') . ' · newest first'; ?></p>
                </div>
            </div>
            <div class="emp-ph-panel-chips">
                <?php if ($missing_out_count > 0): ?>
                    <span class="emp-ph-chip emp-ph-chip-warn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <?php echo (int) $missing_out_count; ?> missing punch out
                    </span>
                <?php elseif ($punch_days > 0): ?>
                    <span class="emp-ph-chip emp-ph-chip-ok">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <?php echo (int) $complete_days; ?> complete day<?php echo $complete_days === 1 ? '' : 's'; ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($history === []): ?>
            <div class="emp-ph-empty">
                <span class="emp-ph-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <h3>No punches this month</h3>
                <p>When you punch in or out from the employee portal, your daily history will appear here with work hours and attendance result.</p>
                <div class="emp-ph-empty-actions">
                    <a href="dashboard.php" class="btn">Go to dashboard</a>
                    <a href="attendance.php?<?php echo $period_query; ?>" class="btn btn-outline">View attendance</a>
                </div>
            </div>
        <?php else: ?>
            <div class="emp-ph-list">
                <?php foreach ($history as $row):
                    $row_date = $row['date'] ?? '';
                    $date_ts = strtotime($row_date);
                    $is_today = $row_date === $today_date;
                    $has_in = !empty($row['in_at']);
                    $has_out = !empty($row['out_at']);
                    $in_fmt = format_punch_datetime($row['in_at'] ?? null);
                    $out_fmt = format_punch_datetime($row['out_at'] ?? null);
                    $in_badge = format_punctuality_badge($row['in_status'] ?? null, null, 'in');
                    $out_badge = format_punctuality_badge($row['out_status'] ?? null, null, 'out');
                    $att_status = $row['attendance_status'] ?? '—';
                    $att_class = match (strtolower(trim((string) ($row['attendance_status'] ?? '')))) {
                        'present' => 'emp-ph-result-present',
                        'half day' => 'emp-ph-result-half',
                        'absent' => 'emp-ph-result-absent',
                        default => 'emp-ph-result-na',
                    };

                    $track_progress = 0;
                    $day_state = 'empty';
                    if ($has_in && $has_out) {
                        $track_progress = 100;
                        $day_state = 'complete';
                    } elseif ($has_in) {
                        $track_progress = 50;
                        $day_state = 'partial';
                    }
                    ?>
                    <article class="emp-ph-day emp-ph-day--<?php echo htmlspecialchars($day_state); ?><?php echo $is_today ? ' emp-ph-day--today' : ''; ?>">
                        <div class="emp-ph-day-date">
                            <span class="emp-ph-day-num"><?php echo date('d', $date_ts); ?></span>
                            <span class="emp-ph-day-meta">
                                <strong><?php echo date('D', $date_ts); ?></strong>
                                <?php echo date('M Y', $date_ts); ?>
                                <?php if ($is_today): ?>
                                    <em class="emp-ph-day-today">Today</em>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="emp-ph-day-flow" style="--ph-progress: <?php echo (int) $track_progress; ?>%;">
                            <div class="emp-ph-day-step emp-ph-day-step--in <?php echo $has_in ? 'is-done' : 'is-empty'; ?>">
                                <span class="emp-ph-day-step-dot" aria-hidden="true">
                                    <?php if ($has_in): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                    <?php endif; ?>
                                </span>
                                <div class="emp-ph-day-step-body">
                                    <span class="emp-ph-day-step-label">Punch in</span>
                                    <strong class="emp-ph-day-step-time"><?php echo htmlspecialchars($in_fmt['time'] ?: '—'); ?></strong>
                                    <?php if ($has_in && $in_badge['label'] !== '—'): ?>
                                        <span class="emp-ph-day-tag <?php echo htmlspecialchars($in_badge['class']); ?>"<?php echo !empty($in_badge['title']) ? ' title="' . htmlspecialchars($in_badge['title']) . '"' : ''; ?>>
                                            <?php echo htmlspecialchars($in_badge['label']); ?>
                                            <?php if (!empty($in_badge['suffix'])): ?><small><?php echo htmlspecialchars($in_badge['suffix']); ?></small><?php endif; ?>
                                        </span>
                                    <?php elseif ($has_in): ?>
                                        <span class="emp-ph-day-tag punch-punctuality-on-time">On time</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="emp-ph-day-connector" aria-hidden="true">
                                <span class="emp-ph-day-connector-fill"></span>
                            </div>

                            <div class="emp-ph-day-step emp-ph-day-step--out <?php echo $has_out ? 'is-done' : ($has_in ? 'is-pending' : 'is-empty'); ?>">
                                <span class="emp-ph-day-step-dot" aria-hidden="true">
                                    <?php if ($has_out): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    <?php endif; ?>
                                </span>
                                <div class="emp-ph-day-step-body">
                                    <span class="emp-ph-day-step-label">Punch out</span>
                                    <strong class="emp-ph-day-step-time"><?php echo htmlspecialchars($out_fmt['time'] ?: '—'); ?></strong>
                                    <?php if ($has_out && $out_badge['label'] !== '—'): ?>
                                        <span class="emp-ph-day-tag <?php echo htmlspecialchars($out_badge['class']); ?>"<?php echo !empty($out_badge['title']) ? ' title="' . htmlspecialchars($out_badge['title']) . '"' : ''; ?>>
                                            <?php echo htmlspecialchars($out_badge['label']); ?>
                                            <?php if (!empty($out_badge['suffix'])): ?><small><?php echo htmlspecialchars($out_badge['suffix']); ?></small><?php endif; ?>
                                        </span>
                                    <?php elseif ($has_out): ?>
                                        <span class="emp-ph-day-tag punch-punctuality-on-time">On time</span>
                                    <?php elseif ($has_in): ?>
                                        <span class="emp-ph-day-tag emp-ph-day-tag-warn">Missing</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="emp-ph-day-aside">
                            <div class="emp-ph-day-hours">
                                <span class="emp-ph-day-hours-label">Work hours</span>
                                <strong class="emp-ph-day-hours-value"><?php echo htmlspecialchars(format_work_hours_label($row['work_hours'] ?? null)); ?></strong>
                            </div>
                            <span class="emp-ph-day-result <?php echo htmlspecialchars($att_class); ?>">
                                <?php echo htmlspecialchars($att_status); ?>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
