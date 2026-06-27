<?php
require 'includes/header.php';
require 'config.php';
require_once 'includes/employee_portal_helper.php';
require_once 'includes/payroll_extensions.php';

$branch_filter = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_filter);
$leave_types_map = get_leave_types($conn);
$all_leaves = get_all_leave_requests_with_names($conn, $branch_filter);

$filter_status = strtolower(trim($_GET['status'] ?? ''));
$filter_type = strtoupper(trim($_GET['leave_type'] ?? ''));
$filter_q = trim($_GET['q'] ?? '');
$filter_year = (int) ($_GET['year'] ?? 0);

$allowed_status = ['pending', 'approved', 'rejected', 'cancelled', 'cancellation_pending'];
if (!in_array($filter_status, $allowed_status, true)) {
    $filter_status = '';
}
if ($filter_type !== '' && !isset($leave_types_map[$filter_type])) {
    $filter_type = '';
}
if ($filter_year < 2000 || $filter_year > 2100) {
    $filter_year = 0;
}

$stats = [
    'total' => count($all_leaves),
    'approved' => 0,
    'pending' => 0,
    'closed' => 0,
];
foreach ($all_leaves as $row) {
    $st = $row['request_status'] ?? '';
    if ($st === 'approved') {
        $stats['approved']++;
    } elseif (in_array($st, ['pending', 'cancellation_pending'], true)) {
        $stats['pending']++;
    } elseif (in_array($st, ['rejected', 'cancelled'], true)) {
        $stats['closed']++;
    }
}

$filtered_leaves = array_values(array_filter($all_leaves, static function ($req) use ($filter_status, $filter_type, $filter_q, $filter_year) {
    $st = $req['request_status'] ?? '';
    if ($filter_status !== '' && $st !== $filter_status) {
        return false;
    }
    if ($filter_type !== '' && strtoupper((string) ($req['leave_type'] ?? '')) !== $filter_type) {
        return false;
    }
    if ($filter_q !== '') {
        $hay = strtolower(($req['employee_name'] ?? '') . ' ' . ($req['emp_id'] ?? ''));
        if (strpos($hay, strtolower($filter_q)) === false) {
            return false;
        }
    }
    if ($filter_year > 0) {
        $from_year = (int) date('Y', strtotime($req['from_date']));
        $to_year = (int) date('Y', strtotime($req['to_date']));
        if ($from_year !== $filter_year && $to_year !== $filter_year) {
            return false;
        }
    }
    return true;
}));

$has_active_filters = $filter_status !== '' || $filter_type !== '' || $filter_q !== '' || $filter_year > 0;

function leave_history_status_class(string $status): string
{
    return match ($status) {
        'approved' => 'approved',
        'rejected' => 'rejected',
        'cancelled' => 'cancelled',
        'cancellation_pending' => 'cancel-pending',
        'pending' => 'pending',
        default => 'neutral',
    };
}

function leave_history_status_label(string $status): string
{
    return match ($status) {
        'cancellation_pending' => 'Cancel requested',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        default => ucfirst($status),
    };
}

$year_options = [];
foreach ($all_leaves as $row) {
    $y = (int) date('Y', strtotime($row['from_date']));
    $year_options[$y] = true;
    $y2 = (int) date('Y', strtotime($row['to_date']));
    $year_options[$y2] = true;
}
krsort($year_options);
?>
<div class="leave-history-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Employee portal</p>
            <h2>Leave history</h2>
            <p>All leave applications<?php echo $branch_filter !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ' across branches'; ?> — approved, pending, and closed requests.</p>
        </div>
        <div class="page-header-actions">
            <a href="approvals.php" class="btn btn-outline">Pending approvals</a>
            <a href="employees.php" class="btn btn-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Employees
            </a>
        </div>
    </div>

    <?php if ($branch_filter === null): ?>
        <div class="alert alert-page leave-history-branch-alert">
            <strong>Viewing all branches.</strong> Choose a branch from the top bar to filter leave records for one location.
        </div>
    <?php endif; ?>

    <div class="leave-history-stats">
        <div class="leave-history-stat leave-history-stat-total">
            <span class="leave-history-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div>
                <span class="leave-history-stat-label">Total requests</span>
                <strong class="leave-history-stat-value"><?php echo (int) $stats['total']; ?></strong>
                <span class="leave-history-stat-hint">All time for this view</span>
            </div>
        </div>
        <div class="leave-history-stat leave-history-stat-approved">
            <span class="leave-history-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </span>
            <div>
                <span class="leave-history-stat-label">Approved</span>
                <strong class="leave-history-stat-value"><?php echo (int) $stats['approved']; ?></strong>
                <span class="leave-history-stat-hint">Marked on attendance</span>
            </div>
        </div>
        <div class="leave-history-stat leave-history-stat-pending">
            <span class="leave-history-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <span class="leave-history-stat-label">Awaiting action</span>
                <strong class="leave-history-stat-value"><?php echo (int) $stats['pending']; ?></strong>
                <span class="leave-history-stat-hint">Pending or cancel request</span>
            </div>
        </div>
        <div class="leave-history-stat leave-history-stat-closed">
            <span class="leave-history-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </span>
            <div>
                <span class="leave-history-stat-label">Rejected / cancelled</span>
                <strong class="leave-history-stat-value"><?php echo (int) $stats['closed']; ?></strong>
                <span class="leave-history-stat-hint">Closed without approval</span>
            </div>
        </div>
    </div>

    <section class="panel panel-elevated leave-history-panel">
        <div class="leave-history-panel-head">
            <div class="leave-history-panel-title">
                <span class="leave-history-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                </span>
                <div>
                    <h3>Leave records</h3>
                    <p><?php echo count($filtered_leaves); ?> shown<?php echo $has_active_filters ? ' (filtered)' : ''; ?></p>
                </div>
            </div>
            <form method="GET" action="leave_history.php" class="leave-history-filters">
                <div class="form-group leave-history-search-group">
                    <label for="lh-q">Search</label>
                    <div class="leave-history-search">
                        <svg class="leave-history-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search" id="lh-q" name="q" value="<?php echo htmlspecialchars($filter_q); ?>" placeholder="Name or employee ID" autocomplete="off" enterkeyhint="search">
                        <?php if ($filter_q !== ''): ?>
                            <button type="button" class="leave-history-search-clear" aria-label="Clear search" onclick="var el=document.getElementById('lh-q');el.value='';el.form.submit();">&times;</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="lh-status">Status</label>
                    <select id="lh-status" name="status" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="cancellation_pending" <?php echo $filter_status === 'cancellation_pending' ? 'selected' : ''; ?>>Cancel requested</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="lh-type">Leave type</label>
                    <select id="lh-type" name="leave_type" onchange="this.form.submit()">
                        <option value="">All types</option>
                        <?php foreach ($leave_types_map as $code => $lt): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $filter_type === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($code . ' — ' . ($lt['name'] ?? $code)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="lh-year">Year</label>
                    <select id="lh-year" name="year" onchange="this.form.submit()">
                        <option value="">All years</option>
                        <?php foreach (array_keys($year_options) as $y): ?>
                            <option value="<?php echo (int) $y; ?>" <?php echo $filter_year === (int) $y ? 'selected' : ''; ?>><?php echo (int) $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group leave-history-filter-actions">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-sm">Apply</button>
                    <?php if ($has_active_filters): ?>
                        <a href="leave_history.php" class="btn btn-outline btn-sm">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="panel-body leave-history-panel-body">
            <?php if (empty($all_leaves)): ?>
                <div class="leave-history-empty">
                    <div class="leave-history-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    </div>
                    <h4>No leave records yet</h4>
                    <p>When employees apply for leave from the portal, their requests will appear here after submission.</p>
                    <a href="approvals.php" class="btn btn-sm">Go to approvals</a>
                </div>
            <?php elseif ($filtered_leaves === []): ?>
                <div class="leave-history-empty">
                    <div class="leave-history-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <h4>No matching records</h4>
                    <p>Try changing filters or clearing the search.</p>
                    <a href="leave_history.php" class="btn btn-outline btn-sm">Clear filters</a>
                </div>
            <?php else: ?>
                <ul class="leave-history-list">
                    <?php foreach ($filtered_leaves as $req):
                        $emp_initial = strtoupper(substr((string) ($req['employee_name'] ?? 'E'), 0, 1));
                        $days = leave_request_day_count($req['from_date'], $req['to_date']);
                        $from_ts = strtotime($req['from_date']);
                        $to_ts = strtotime($req['to_date']);
                        $status = (string) ($req['request_status'] ?? '');
                        $status_class = leave_history_status_class($status);
                        $status_label = leave_history_status_label($status);
                        $lt_label = $leave_types_map[$req['leave_type']]['name'] ?? $req['leave_type'];
                        $review_note = trim((string) ($req['review_note'] ?? ''));
                        $employee_note = trim((string) ($req['employee_note'] ?? ''));
                        $reviewed_at = $req['reviewed_at'] ?? '';
                        ?>
                    <li class="leave-history-card leave-history-card--<?php echo htmlspecialchars($status_class); ?>">
                        <div class="leave-history-card-main">
                            <div class="leave-history-card-employee">
                                <span class="leave-history-card-avatar" aria-hidden="true"><?php echo htmlspecialchars($emp_initial); ?></span>
                                <div class="leave-history-card-employee-text">
                                    <a href="employee_view.php?emp_id=<?php echo urlencode($req['emp_id']); ?>" class="leave-history-card-name"><?php echo htmlspecialchars($req['employee_name']); ?></a>
                                    <span class="leave-history-card-meta"><?php echo htmlspecialchars($req['emp_id']); ?><?php if ($branch_filter === null): ?> · <?php echo htmlspecialchars(get_branch_label($conn, (int) $req['branch_id'])); ?><?php endif; ?></span>
                                </div>
                            </div>

                            <div class="leave-history-card-period">
                                <span class="leave-history-period-label">Leave period</span>
                                <div class="leave-history-period-range">
                                    <time datetime="<?php echo htmlspecialchars($req['from_date']); ?>">
                                        <strong><?php echo date('d M Y', $from_ts); ?></strong>
                                    </time>
                                    <?php if ($req['from_date'] !== $req['to_date']): ?>
                                        <span class="leave-history-period-sep" aria-hidden="true">→</span>
                                        <time datetime="<?php echo htmlspecialchars($req['to_date']); ?>">
                                            <strong><?php echo date('d M Y', $to_ts); ?></strong>
                                        </time>
                                    <?php endif; ?>
                                </div>
                                <span class="leave-history-period-days"><?php echo (int) $days; ?> day<?php echo $days === 1 ? '' : 's'; ?></span>
                            </div>

                            <div class="leave-history-card-type">
                                <span class="leave-history-type-code"><?php echo htmlspecialchars($req['leave_type']); ?></span>
                                <span class="leave-history-type-name"><?php echo htmlspecialchars($lt_label); ?></span>
                            </div>

                            <div class="leave-history-card-status-wrap">
                                <span class="leave-history-status leave-history-status--<?php echo htmlspecialchars($status_class); ?>"><?php echo htmlspecialchars($status_label); ?></span>
                                <time class="leave-history-submitted" datetime="<?php echo htmlspecialchars($req['created_at']); ?>">Applied <?php echo date('d M Y', strtotime($req['created_at'])); ?></time>
                            </div>
                        </div>

                        <?php if ($employee_note !== '' || $review_note !== '' || $reviewed_at !== ''): ?>
                            <div class="leave-history-card-notes">
                                <?php if ($employee_note !== ''): ?>
                                    <div class="leave-history-note leave-history-note-employee">
                                        <strong>Employee reason</strong>
                                        <p><?php echo htmlspecialchars($employee_note); ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if ($review_note !== '' || $reviewed_at !== ''): ?>
                                    <div class="leave-history-note leave-history-note-review">
                                        <strong>Review<?php echo !empty($req['reviewed_by']) ? ' · ' . htmlspecialchars($req['reviewed_by']) : ''; ?></strong>
                                        <?php if ($review_note !== ''): ?>
                                            <p><?php echo htmlspecialchars($review_note); ?></p>
                                        <?php endif; ?>
                                        <?php if ($reviewed_at !== ''): ?>
                                            <time datetime="<?php echo htmlspecialchars($reviewed_at); ?>"><?php echo date('d M Y, h:i A', strtotime($reviewed_at)); ?></time>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require 'includes/footer.php'; ?>
