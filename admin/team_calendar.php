<?php
require_once 'includes/admin_page_init.php';
admin_page_init('calendar');
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
$cal = get_team_calendar_month_data($conn, $year, $month, $branch_id, $settings);
$days_in_month = $cal['days_in_month'];
$first_dow = (int) date('N', strtotime($cal['start']));
$leave_by_date = $cal['leave_by_date'];
$holidays = $cal['holidays'];
$today = date('Y-m-d');
$q = 'month=' . $month . '&year=' . $year;

$selected_day = (int) ($_GET['day'] ?? 0);
if ($selected_day < 1 || $selected_day > $days_in_month) {
    $selected_day = 0;
}
$selected_date = $selected_day > 0 ? sprintf('%d-%02d-%02d', $year, $month, $selected_day) : '';
if ($selected_date === '' && $today >= $cal['start'] && $today <= date('Y-m-t', strtotime($cal['start']))) {
    $selected_day = (int) date('j', strtotime($today));
    $selected_date = $today;
}

$leave_count_month = 0;
foreach ($leave_by_date as $entries) {
    $leave_count_month += count($entries);
}

$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$selected_holiday = $selected_date !== '' ? ($holidays[$selected_date] ?? null) : null;
$selected_leave = $selected_date !== '' ? ($leave_by_date[$selected_date] ?? []) : [];
$upcoming = array_filter($holidays, static fn($h, $d) => $d >= $today, ARRAY_FILTER_USE_BOTH);
?>
<div class="hrm-page team-calendar-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Calendar</p>
            <h2>Team calendar</h2>
            <p>Holidays and approved leave for <strong><?php echo htmlspecialchars($period_label); ?></strong> · <?php echo htmlspecialchars($active_branch_label); ?></p>
        </div>
        <div class="page-header-actions">
            <a href="weekoff_roster.php?<?php echo $q; ?>" class="btn btn-outline">Weekoff roster</a>
            <a href="holidays.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="btn btn-outline">Holidays</a>
            <a href="leave_history.php" class="btn btn-header">Leave history</a>
        </div>
    </div>

    <?php if ($branch_id === null): ?>
        <div class="hrm-callout hrm-callout-info">
            <strong>Select a branch</strong>
            <span>Branch-specific holidays appear after you choose a location from the top bar. Leave data includes all branches.</span>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-stat-row team-cal-stats">
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo count($holidays); ?></strong>
                <span>Holidays &amp; week offs</span>
            </div>
        </div>
        <div class="settings-status-chip <?php echo count($cal['today_on_leave']) > 0 ? 'warn' : 'ok'; ?>">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo count($cal['today_on_leave']); ?></strong>
                <span>On leave today</span>
            </div>
        </div>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo $leave_count_month; ?></strong>
                <span>Leave person-days</span>
            </div>
        </div>
        <?php if ($cal['today_holiday']): ?>
        <div class="settings-status-chip ok">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo htmlspecialchars($cal['today_holiday']['name']); ?></strong>
                <span>Today is a holiday</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="team-cal-layout">
        <div class="panel panel-elevated team-cal-main-panel">
            <div class="team-cal-panel-head">
                <div class="hrm-period-nav">
                    <a href="team_calendar.php?year=<?php echo $prev_year; ?>&month=<?php echo $prev_month; ?>" class="hrm-period-nav-btn" aria-label="Previous month">&larr;</a>
                    <div class="hrm-period-nav-center">
                        <strong><?php echo htmlspecialchars($period_label); ?></strong>
                        <span><?php echo $days_in_month; ?> days</span>
                    </div>
                    <a href="team_calendar.php?year=<?php echo $next_year; ?>&month=<?php echo $next_month; ?>" class="hrm-period-nav-btn" aria-label="Next month">&rarr;</a>
                </div>
                <form method="GET" class="team-cal-jump-form">
                    <select name="month" aria-label="Month" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('M', mktime(0, 0, 0, $m, 1)); ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" aria-label="Year" onchange="this.form.submit()">
                        <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 2; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
            <div class="panel-body team-cal-panel-body">
                <div class="team-cal-weekdays" aria-hidden="true">
                    <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd): ?>
                        <span><?php echo $wd; ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="team-cal-grid" role="grid" aria-label="Calendar for <?php echo htmlspecialchars($period_label); ?>">
                    <?php for ($i = 1; $i < $first_dow; $i++): ?>
                        <div class="team-cal-cell team-cal-cell-empty" aria-hidden="true"></div>
                    <?php endfor; ?>
                    <?php for ($day = 1; $day <= $days_in_month; $day++):
                        $date = sprintf('%d-%02d-%02d', $year, $month, $day);
                        $is_today = $date === $today;
                        $is_selected = $date === $selected_date;
                        $holiday = $holidays[$date] ?? null;
                        $on_leave = $leave_by_date[$date] ?? [];
                        $cell_classes = ['team-cal-cell'];
                        if ($is_today) {
                            $cell_classes[] = 'is-today';
                        }
                        if ($is_selected) {
                            $cell_classes[] = 'is-selected';
                        }
                        if ($holiday) {
                            $cell_classes[] = 'is-holiday';
                        }
                        if (count($on_leave) > 0) {
                            $cell_classes[] = 'has-leave';
                        }
                        $dow = date('D', strtotime($date));
                        $cell_url = 'team_calendar.php?year=' . $year . '&month=' . $month . '&day=' . $day;
                    ?>
                    <a href="<?php echo htmlspecialchars($cell_url); ?>" class="<?php echo implode(' ', $cell_classes); ?>" role="gridcell" title="<?php echo htmlspecialchars($date); ?>">
                        <span class="team-cal-day-top">
                            <span class="team-cal-day-num"><?php echo $day; ?></span>
                            <span class="team-cal-day-dow"><?php echo htmlspecialchars($dow); ?></span>
                        </span>
                        <?php if ($holiday): ?>
                            <span class="team-cal-event team-cal-event-holiday" title="<?php echo htmlspecialchars($holiday['name']); ?>">
                                <?php echo htmlspecialchars($holiday['kind'] === 'weekoff' ? 'Week off' : $holiday['name']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (count($on_leave) > 0): ?>
                            <span class="team-cal-leave-row">
                                <?php foreach (array_slice($on_leave, 0, 2) as $person): ?>
                                    <span class="team-cal-leave-dot" title="<?php echo htmlspecialchars($person['name'] . ' · ' . $person['leave_type']); ?>"><?php echo htmlspecialchars(strtoupper(substr($person['name'], 0, 1))); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($on_leave) > 2): ?>
                                    <span class="team-cal-leave-more">+<?php echo count($on_leave) - 2; ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <div class="team-cal-legend">
                    <span><em class="team-cal-legend-dot holiday"></em> Holiday</span>
                    <span><em class="team-cal-legend-dot leave"></em> Leave</span>
                    <span><em class="team-cal-legend-dot today"></em> Today</span>
                    <span><em class="team-cal-legend-dot selected"></em> Selected</span>
                </div>
            </div>
        </div>

        <aside class="team-cal-aside">
            <?php if ($selected_date !== ''): ?>
            <div class="panel panel-elevated team-cal-detail-card">
                <div class="panel-body padded">
                    <div class="team-cal-detail-head">
                        <div>
                            <p class="team-cal-detail-eyebrow">Selected day</p>
                            <h3><?php echo date('l, j F Y', strtotime($selected_date)); ?></h3>
                        </div>
                        <?php if ($selected_date === $today): ?>
                            <span class="team-cal-today-badge">Today</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($selected_holiday): ?>
                        <div class="team-cal-detail-block team-cal-detail-holiday">
                            <span class="team-cal-detail-label">Holiday</span>
                            <strong><?php echo htmlspecialchars($selected_holiday['name']); ?></strong>
                            <span class="badge <?php echo $selected_holiday['kind'] === 'weekoff' ? 'badge-weekoff' : 'badge-holiday'; ?>"><?php echo $selected_holiday['kind'] === 'weekoff' ? 'Week off' : 'Public holiday'; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="team-cal-detail-block">
                        <span class="team-cal-detail-label">On leave (<?php echo count($selected_leave); ?>)</span>
                        <?php if (count($selected_leave) > 0): ?>
                            <ul class="team-cal-people-list">
                                <?php foreach ($selected_leave as $person): ?>
                                <li>
                                    <span class="team-cal-person-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($person['name'], 0, 1))); ?></span>
                                    <div>
                                        <strong><?php echo htmlspecialchars($person['name']); ?></strong>
                                        <span><?php echo htmlspecialchars($person['emp_id']); ?> · <?php echo htmlspecialchars($person['leave_type']); ?></span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="team-cal-empty-hint">No approved leave on this day.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="panel panel-elevated team-cal-side-card">
                <div class="panel-body padded">
                    <div class="team-cal-side-head">
                        <span class="team-cal-side-icon leave" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </span>
                        <div>
                            <h3>On leave today</h3>
                            <p><?php echo htmlspecialchars(date('j M Y')); ?></p>
                        </div>
                    </div>
                    <?php if (count($cal['today_on_leave']) > 0): ?>
                        <ul class="team-cal-people-list">
                            <?php foreach ($cal['today_on_leave'] as $person): ?>
                            <li>
                                <span class="team-cal-person-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($person['name'], 0, 1))); ?></span>
                                <div>
                                    <strong><?php echo htmlspecialchars($person['name']); ?></strong>
                                    <span><?php echo htmlspecialchars($person['emp_id']); ?> · <?php echo htmlspecialchars($person['leave_type']); ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="team-cal-empty-hint">Everyone is expected to be available today.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel panel-elevated team-cal-side-card">
                <div class="panel-body padded">
                    <div class="team-cal-side-head">
                        <span class="team-cal-side-icon holiday" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                        </span>
                        <div>
                            <h3>Upcoming holidays</h3>
                            <p>Rest of <?php echo htmlspecialchars(date('F', mktime(0, 0, 0, $month, 1, $year))); ?></p>
                        </div>
                    </div>
                    <?php if (count($upcoming) > 0): ?>
                        <ul class="team-cal-holiday-list">
                            <?php foreach (array_slice($upcoming, 0, 8, true) as $date => $hol): ?>
                            <li>
                                <span class="team-cal-holiday-date"><?php echo date('j', strtotime($date)); ?></span>
                                <div>
                                    <strong><?php echo htmlspecialchars($hol['name']); ?></strong>
                                    <span><?php echo date('D, j M', strtotime($date)); ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="team-cal-empty-hint">No more holidays this month<?php echo $branch_id === null ? ' — select a branch' : ''; ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
