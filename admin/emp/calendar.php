<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/hrm_helper.php';
require_once __DIR__ . '/includes/period.php';

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) { $month = (int) date('n'); }
if ($year < 2000 || $year > 2100) { $year = (int) date('Y'); }

$settings = get_all_settings($conn);
$cal = get_team_calendar_month_data($conn, $year, $month, (int) $employee['branch_id'], $settings);
$period_label = get_period_label($year, $month);
$days_in_month = $cal['days_in_month'];
$first_dow = (int) date('N', strtotime($cal['start']));
$holidays = $cal['holidays'];
$leave_by_date = $cal['leave_by_date'];
$today = date('Y-m-d');

[$prev_month, $prev_year] = get_adjacent_period($month, $year, -1);
[$next_month, $next_year] = get_adjacent_period($month, $year, 1);

$holiday_count = count($holidays);
$leave_day_count = 0;
foreach ($leave_by_date as $entries) {
    $leave_day_count += count($entries);
}
$today_leave_count = count($cal['today_on_leave'] ?? []);
?>
<div class="emp-page emp-page-calendar">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">My team</p>
            <h2 class="emp-page-hero-title">Team calendar</h2>
            <p>Holidays and approved leave at <strong><?php echo htmlspecialchars($branch_label); ?></strong>. <a href="team.php">My team</a><?php if (employee_is_manager($conn, $employee['emp_id'])): ?> · <a href="team_approvals.php">Team approvals</a><?php endif; ?></p>
        </div>
        <div class="emp-period-nav">
            <a href="calendar.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="emp-period-nav-btn" aria-label="Previous month">&larr;</a>
            <span class="emp-period-nav-label"><?php echo htmlspecialchars($period_label); ?></span>
            <a href="calendar.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="emp-period-nav-btn" aria-label="Next month">&rarr;</a>
        </div>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg></div>
            <div><span class="emp-dash-stat-label">Holidays</span><strong class="emp-dash-stat-value"><?php echo $holiday_count; ?></strong><span class="emp-dash-stat-hint">This month</span></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div><span class="emp-dash-stat-label">Leave entries</span><strong class="emp-dash-stat-value"><?php echo $leave_day_count; ?></strong><span class="emp-dash-stat-hint">Approved leave</span></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><span class="emp-dash-stat-label">On leave today</span><strong class="emp-dash-stat-value"><?php echo $today_leave_count; ?></strong><span class="emp-dash-stat-hint"><?php echo date('d M Y'); ?></span></div>
        </div>
    </div>

    <div class="emp-section-grid emp-section-grid-cal">
        <section class="emp-card emp-calendar-card">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div><h3 class="emp-card-title"><?php echo htmlspecialchars($period_label); ?></h3><p class="emp-reg-toolbar-sub">Branch holidays and team leave overview.</p></div>
            </header>
            <div class="emp-cal-legend-foot emp-cal-legend-team">
                <div class="emp-cal-legend-items">
                    <span class="emp-cal-legend-item"><i class="emp-cal-legend-swatch emp-cal-legend-holiday"></i> Holiday</span>
                    <span class="emp-cal-legend-item"><i class="emp-cal-legend-swatch emp-cal-legend-leave"></i> On leave</span>
                    <span class="emp-cal-legend-item"><i class="emp-cal-legend-swatch emp-cal-legend-today"></i> Today</span>
                </div>
            </div>
            <div class="emp-calendar-panel">
                <div class="emp-calendar-grid">
                    <span class="emp-cal-head">Mon</span><span class="emp-cal-head">Tue</span><span class="emp-cal-head">Wed</span><span class="emp-cal-head">Thu</span><span class="emp-cal-head">Fri</span><span class="emp-cal-head">Sat</span><span class="emp-cal-head">Sun</span>
                    <?php for ($i = 1; $i < $first_dow; $i++): ?><span class="emp-cal-day is-empty"></span><?php endfor; ?>
                    <?php for ($d = 1; $d <= $days_in_month; $d++):
                        $date = sprintf('%d-%02d-%02d', $year, $month, $d);
                        $is_hol = isset($holidays[$date]);
                        $day_leave = $leave_by_date[$date] ?? [];
                        $leave_count = count($day_leave);
                        $classes = ['emp-cal-day'];
                        if ($date === $today) { $classes[] = 'is-today'; }
                        if ($is_hol) { $classes[] = 'is-holiday'; }
                        if ($leave_count > 0) { $classes[] = 'has-leave'; }
                    ?>
                    <div class="<?php echo implode(' ', $classes); ?>" title="<?php echo $is_hol ? htmlspecialchars($holidays[$date]['label'] ?? 'Holiday') : ''; ?>">
                        <strong><?php echo $d; ?></strong>
                        <?php if ($is_hol): ?><span class="emp-cal-tag"><?php echo htmlspecialchars($holidays[$date]['label'] ?? 'Holiday'); ?></span><?php endif; ?>
                        <?php if ($leave_count > 0): ?><span class="emp-cal-tag"><?php echo $leave_count; ?> on leave</span><?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <aside class="emp-card emp-calendar-aside">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div><h3 class="emp-card-title">On leave today</h3><p class="emp-reg-toolbar-sub"><?php echo date('l, d M Y'); ?></p></div>
            </header>
            <?php if (empty($cal['today_on_leave'])): ?>
                <div class="emp-reg-empty emp-reg-empty-compact">
                    <strong>Everyone available</strong>
                    <p>No approved leave for your branch today.</p>
                </div>
            <?php else: ?>
                <ul class="emp-team-roster">
                    <?php foreach ($cal['today_on_leave'] as $l): ?>
                    <li class="emp-team-roster-item">
                        <span class="emp-avatar emp-avatar-sm" aria-hidden="true"><?php echo strtoupper(substr($l['name'], 0, 1)); ?></span>
                        <div>
                            <strong><?php echo htmlspecialchars($l['name']); ?></strong>
                            <span><?php echo htmlspecialchars($l['leave_type']); ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($holiday_count > 0): ?>
            <header class="emp-card-toolbar emp-reg-toolbar" style="margin-top:1.25rem;border-top:1px solid var(--border);padding-top:1rem">
                <div><h3 class="emp-card-title">Holidays this month</h3></div>
            </header>
            <ul class="emp-cal-holiday-list">
                <?php foreach ($holidays as $hdate => $h): ?>
                <li><time datetime="<?php echo htmlspecialchars($hdate); ?>"><?php echo date('d M', strtotime($hdate)); ?></time> <?php echo htmlspecialchars($h['label'] ?? 'Holiday'); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
