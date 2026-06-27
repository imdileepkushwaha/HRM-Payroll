<?php
require 'includes/header.php';
require 'config.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/punch_helper.php';
require_once 'includes/settings_helper.php';

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
$period_locked = is_payroll_period_locked($conn, $year, $month, $branch_id);

$filter_punctuality = strtolower(trim($_GET['punctuality'] ?? ''));
$filter_punch_type = strtolower(trim($_GET['punch_type'] ?? ''));
$filter_status = strtolower(trim($_GET['status'] ?? ''));
$filter_emp_id = trim($_GET['emp_id'] ?? '');
$punch_filters = [];
if (in_array($filter_punctuality, ['late', 'early', 'on_time'], true)) {
    $punch_filters['punctuality'] = $filter_punctuality;
}
if (in_array($filter_punch_type, ['in', 'out'], true)) {
    $punch_filters['punch_type'] = $filter_punch_type;
}
if (in_array($filter_status, ['ok', 'rejected'], true)) {
    $punch_filters['record_status'] = $filter_status;
}
if ($filter_emp_id !== '') {
    $punch_filters['emp_id'] = $filter_emp_id;
}

$punches = get_recent_employee_punches($conn, $branch_id, 300, $month, $year, $punch_filters);
$has_active_filters = $punch_filters !== [];

$punch_in_count = 0;
$punch_out_count = 0;
$ok_count = 0;
$rejected_count = 0;
$on_time_count = 0;
$late_count = 0;
$early_count = 0;
$geo_inside = 0;
$unique_employees = [];

foreach ($punches as $p) {
    $unique_employees[$p['emp_id']] = true;
    if (($p['punch_type'] ?? '') === 'in') {
        $punch_in_count++;
        if (($p['record_status'] ?? 'ok') === 'ok') {
            if (($p['punctuality_status'] ?? '') === 'late') {
                $late_count++;
            } elseif (($p['punctuality_status'] ?? '') === 'on_time') {
                $on_time_count++;
            }
        }
    } elseif (($p['punch_type'] ?? '') === 'out') {
        $punch_out_count++;
        if (($p['record_status'] ?? 'ok') === 'ok') {
            if (($p['punctuality_status'] ?? '') === 'early') {
                $early_count++;
            } elseif (($p['punctuality_status'] ?? '') === 'on_time') {
                $on_time_count++;
            }
        }
    }
    if (($p['record_status'] ?? 'ok') === 'ok') {
        $ok_count++;
    } else {
        $rejected_count++;
    }
    if (!empty($p['geo_required']) && !empty($p['within_geofence'])) {
        $geo_inside++;
    }
}

$entry_count = count($punches);
$employee_count = count($unique_employees);
$geo_enabled = is_geo_attendance_enabled($settings);
$office_start_label = date('g:i A', strtotime(get_office_start_time($settings)));
$office_end_label = date('g:i A', strtotime(get_office_end_time($settings)));
$grace_minutes = get_late_grace_minutes($settings);
$filter_query = http_build_query(array_filter([
    'month' => $month,
    'year' => $year,
    'punctuality' => $filter_punctuality !== '' ? $filter_punctuality : null,
    'punch_type' => $filter_punch_type !== '' ? $filter_punch_type : null,
    'status' => $filter_status !== '' ? $filter_status : null,
    'emp_id' => $filter_emp_id !== '' ? $filter_emp_id : null,
]));
?>
<div class="punch-logs-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Attendance</p>
            <h2>Punch logs</h2>
            <p>Employee punch in/out history for <strong><?php echo htmlspecialchars($period_label); ?></strong> · <strong><?php echo htmlspecialchars($active_branch_label); ?></strong></p>
        </div>
        <div class="page-header-actions">
            <a href="punch_report.php?year=<?php echo $year; ?>&amp;month=<?php echo $month; ?>" class="btn btn-outline">Monthly report</a>
            <a href="employees.php" class="btn btn-outline">Employees</a>
            <a href="settings.php?tab=punch" class="btn btn-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/></svg>
                Punch settings
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if ($period_locked): ?>
        <div class="alert alert-page">
            <strong>Payroll period locked.</strong> Punch records for <?php echo htmlspecialchars($period_label); ?> cannot be deleted.
        </div>
    <?php endif; ?>

    <div class="settings-status punch-logs-status">
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo htmlspecialchars($period_label); ?></strong>
                <span><?php echo (int) $entry_count; ?> punch<?php echo $entry_count === 1 ? '' : 'es'; ?> logged</span>
            </div>
        </div>
        <div class="settings-status-chip <?php echo $employee_count > 0 ? 'ok' : 'neutral'; ?>">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo (int) $employee_count; ?> employees</strong>
                <span>Used punch this period</span>
            </div>
        </div>
        <div class="settings-status-chip <?php echo $rejected_count > 0 ? 'warn' : 'neutral'; ?>">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo (int) $rejected_count; ?> rejected</strong>
                <span><?php echo $rejected_count > 0 ? 'Outside geofence or blocked' : 'All punches accepted'; ?></span>
            </div>
        </div>
        <?php if ($geo_enabled): ?>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div>
                <strong>Geo on</strong>
                <span><?php echo (int) $geo_inside; ?> inside office radius</span>
            </div>
        </div>
        <?php endif; ?>
        <div class="settings-status-chip <?php echo ($late_count + $early_count) > 0 ? 'warn' : 'ok'; ?>">
            <span class="status-dot"></span>
            <div>
                <strong><?php echo htmlspecialchars($office_start_label); ?> – <?php echo htmlspecialchars($office_end_label); ?></strong>
                <span><?php echo (int) $late_count; ?> late in · <?php echo (int) $early_count; ?> early out<?php echo $grace_minutes > 0 ? ' · ' . (int) $grace_minutes . ' min grace' : ''; ?></span>
            </div>
        </div>
    </div>

    <div class="punch-logs-layout">
        <div class="panel panel-elevated punch-logs-panel">
            <div class="dashboard-panel-head dashboard-panel-head-table">
                <div class="dashboard-panel-icon punch">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h3>Punch activity</h3>
                    <p>Newest first · up to 300 records for selected month</p>
                </div>
                <div class="dashboard-panel-head-actions">
                    <form method="GET" class="dashboard-panel-period-filter dashboard-period-form punch-logs-filter-form">
                        <div class="form-group">
                            <label for="punch-log-month">Month</label>
                            <select name="month" id="punch-log-month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="punch-log-year">Year</label>
                            <select name="year" id="punch-log-year">
                                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="punch-filter-type">Type</label>
                            <select name="punch_type" id="punch-filter-type">
                                <option value="">All types</option>
                                <option value="in" <?php echo $filter_punch_type === 'in' ? 'selected' : ''; ?>>Punch in</option>
                                <option value="out" <?php echo $filter_punch_type === 'out' ? 'selected' : ''; ?>>Punch out</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="punch-filter-punctuality">Punctuality</label>
                            <select name="punctuality" id="punch-filter-punctuality">
                                <option value="">All</option>
                                <option value="late" <?php echo $filter_punctuality === 'late' ? 'selected' : ''; ?>>Late in</option>
                                <option value="early" <?php echo $filter_punctuality === 'early' ? 'selected' : ''; ?>>Early out</option>
                                <option value="on_time" <?php echo $filter_punctuality === 'on_time' ? 'selected' : ''; ?>>On time</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="punch-filter-status">Status</label>
                            <select name="status" id="punch-filter-status">
                                <option value="">All</option>
                                <option value="ok" <?php echo $filter_status === 'ok' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="punch-filter-emp">Employee ID</label>
                            <input type="text" name="emp_id" id="punch-filter-emp" value="<?php echo htmlspecialchars($filter_emp_id); ?>" placeholder="EMP001">
                        </div>
                        <div class="form-group form-group-btn">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                        </div>
                        <?php if ($has_active_filters): ?>
                        <div class="form-group form-group-btn">
                            <label>&nbsp;</label>
                            <a href="punch_logs.php?month=<?php echo $month; ?>&amp;year=<?php echo $year; ?>" class="btn btn-outline btn-sm">Clear filters</a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="panel-body padded">
                <?php if ($has_active_filters): ?>
                    <p class="form-hint punch-logs-filter-note">Showing filtered results<?php echo $entry_count > 0 ? ' (' . (int) $entry_count . ')' : ''; ?>.</p>
                <?php endif; ?>
                <?php if ($entry_count > 0): ?>
                    <div class="punch-logs-stats">
                        <div class="punch-logs-stat">
                            <span>Total punches</span>
                            <strong><?php echo (int) $entry_count; ?></strong>
                        </div>
                        <div class="punch-logs-stat punch-in">
                            <span>Punch in</span>
                            <strong><?php echo (int) $punch_in_count; ?></strong>
                        </div>
                        <div class="punch-logs-stat punch-out">
                            <span>Punch out</span>
                            <strong><?php echo (int) $punch_out_count; ?></strong>
                        </div>
                        <div class="punch-logs-stat warn">
                            <span>Late in</span>
                            <strong><?php echo (int) $late_count; ?></strong>
                        </div>
                        <div class="punch-logs-stat early">
                            <span>Early out</span>
                            <strong><?php echo (int) $early_count; ?></strong>
                        </div>
                    </div>

                    <div class="table-wrap punch-logs-table-wrap">
                        <table class="data-table data-table-compact punch-logs-table">
                            <thead>
                                <tr>
                                    <th>Date &amp; time</th>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Punctuality</th>
                                    <th>Status</th>
                                    <th>Distance</th>
                                    <th>Location</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($punches as $p):
                                    $emp_name = $p['employee_name'] ?? $p['emp_id'];
                                    $initial = strtoupper(substr($emp_name, 0, 1));
                                    $is_in = ($p['punch_type'] ?? '') === 'in';
                                    $is_ok = ($p['record_status'] ?? 'ok') === 'ok';
                                    $punch_display = format_punch_datetime($p['punched_at'] ?? null);
                                    $punch_date = $punch_display['date'];
                                    $punch_time = $punch_display['time'];
                                    $view_url = 'employee_view.php?emp_id=' . urlencode($p['emp_id']) . '&month=' . $month . '&year=' . $year;
                                    $distance = $p['distance_meters'] !== null && $p['distance_meters'] !== '' ? (float) $p['distance_meters'] : null;
                                    $punct = format_punctuality_badge($p['punctuality_status'] ?? null, $p['late_by_minutes'] ?? null, $p['punch_type'] ?? 'in');
                                    ?>
                                <tr class="punch-log-row <?php echo $is_ok ? 'is-ok' : 'is-rejected'; ?>">
                                    <td class="punch-log-date">
                                        <span class="punch-log-date-main"><?php echo htmlspecialchars($punch_date); ?></span>
                                        <?php if ($punch_time !== ''): ?>
                                            <span class="punch-log-date-sub"><?php echo htmlspecialchars($punch_time); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="cell-employee">
                                            <span class="emp-avatar"><?php echo htmlspecialchars($initial); ?></span>
                                            <div>
                                                <a href="<?php echo htmlspecialchars($view_url); ?>" class="emp-name emp-name-link"><?php echo htmlspecialchars($emp_name); ?></a>
                                                <span class="emp-id"><?php echo htmlspecialchars($p['emp_id']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="punch-type-badge <?php echo $is_in ? 'punch-type-in' : 'punch-type-out'; ?>">
                                            <?php echo $is_in ? 'Punch in' : 'Punch out'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($punct['label'] === '—'): ?>
                                            <span class="text-muted">—</span>
                                        <?php else: ?>
                                            <span class="punch-punctuality-badge <?php echo htmlspecialchars($punct['class']); ?>" title="<?php echo htmlspecialchars($punct['title']); ?>">
                                                <?php echo htmlspecialchars($punct['label']); ?>
                                                <?php if (!empty($punct['suffix'])): ?>
                                                    <small><?php echo htmlspecialchars($punct['suffix']); ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_ok): ?>
                                            <span class="badge badge-present">Accepted</span>
                                        <?php else: ?>
                                            <span class="badge badge-absent" title="<?php echo htmlspecialchars($p['record_status']); ?>">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="punch-log-distance">
                                        <?php if ($distance !== null): ?>
                                            <strong><?php echo htmlspecialchars(number_format($distance, 0)); ?></strong>
                                            <span>m from office</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($p['geo_required'])): ?>
                                            <span class="punch-geo-badge punch-geo-off">Geo off</span>
                                        <?php elseif (!empty($p['within_geofence'])): ?>
                                            <span class="punch-geo-badge punch-geo-in">Inside</span>
                                        <?php else: ?>
                                            <span class="punch-geo-badge punch-geo-out">Outside</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-actions">
                                        <?php if (!$period_locked): ?>
                                        <form method="POST" action="punch_delete.php" class="action-delete-form" onsubmit="return confirm('Delete this <?php echo $is_in ? 'punch in' : 'punch out'; ?> for <?php echo htmlspecialchars($emp_name, ENT_QUOTES); ?>? Attendance will be recalculated.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="punch_id" value="<?php echo (int) ($p['id'] ?? 0); ?>">
                                            <input type="hidden" name="month" value="<?php echo $month; ?>">
                                            <input type="hidden" name="year" value="<?php echo $year; ?>">
                                            <?php if ($filter_punctuality !== ''): ?>
                                                <input type="hidden" name="punctuality" value="<?php echo htmlspecialchars($filter_punctuality); ?>">
                                            <?php endif; ?>
                                            <?php if ($filter_punch_type !== ''): ?>
                                                <input type="hidden" name="punch_type" value="<?php echo htmlspecialchars($filter_punch_type); ?>">
                                            <?php endif; ?>
                                            <?php if ($filter_status !== ''): ?>
                                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                                            <?php endif; ?>
                                            <?php if ($filter_emp_id !== ''): ?>
                                                <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($filter_emp_id); ?>">
                                            <?php endif; ?>
                                            <button type="submit" class="btn btn-outline btn-sm btn-danger-outline" title="Delete punch">Delete</button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="punch-logs-footnote">Office hours <?php echo htmlspecialchars($office_start_label); ?> – <?php echo htmlspecialchars($office_end_label); ?> (<?php echo (int) $grace_minutes; ?> min grace). Late = punch in after start+grace; Early = punch out before end−grace.</p>
                <?php else: ?>
                    <div class="punch-logs-empty">
                        <div class="punch-logs-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <h4><?php echo $has_active_filters ? 'No punches match your filters' : 'No punches for ' . htmlspecialchars($period_label); ?></h4>
                        <p><?php echo $has_active_filters ? 'Try clearing filters or choosing another month.' : 'Employees can punch from the portal dashboard once punch and geo settings are configured.'; ?></p>
                        <div class="punch-logs-empty-actions">
                            <?php if ($has_active_filters): ?>
                                <a href="punch_logs.php?month=<?php echo $month; ?>&amp;year=<?php echo $year; ?>" class="btn">Clear filters</a>
                            <?php else: ?>
                            <a href="settings.php?tab=punch" class="btn">Configure punch</a>
                            <a href="employees.php" class="btn btn-outline">View employees</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
