<?php
require_once 'includes/admin_page_init.php';
admin_page_init('leave');
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_helper.php';

$settings = get_all_settings($conn);
$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$filter_q = trim($_GET['q'] ?? '');
$data = get_leave_balance_rows_for_branch($conn, $settings, $branch_id);
$quota_codes = $data['quota_codes'];
$leave_types = $data['leave_types'];
$rows = $data['rows'];
$total_rows = count($rows);

$active_count = 0;
$low_balance_count = 0;
foreach ($data['rows'] as $row) {
    if ((int) ($row['employee']['is_active'] ?? 0) === 1) {
        $active_count++;
    }
    foreach ($quota_codes as $code) {
        if ((float) ($row['balances'][$code] ?? 0) <= 0.5) {
            $low_balance_count++;
            break;
        }
    }
}
?>
<div class="hrm-page leave-balances-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Leave</p>
            <h2>Leave balances</h2>
            <p>Current leave balances<?php echo $branch_id !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ' across branches'; ?> — includes pending requests.</p>
        </div>
        <div class="page-header-actions">
            <a href="leave_history.php" class="btn btn-outline">Leave history</a>
            <a href="approvals.php" class="btn btn-header">Approvals</a>
        </div>
    </div>

    <?php if ($branch_id === null): ?>
        <div class="alert alert-page">Showing all branches. Select a branch from the top bar to filter.</div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($data['rows']); ?></strong><span>Employees listed</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo $active_count; ?></strong><span>Active employees</span></div></div>
        <div class="settings-status-chip warn"><span class="status-dot"></span><div><strong><?php echo $low_balance_count; ?></strong><span>Low or zero balance</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($quota_codes); ?></strong><span>Tracked leave types</span></div></div>
    </div>

    <div class="panel panel-elevated leave-balances-panel">
        <div class="panel-header leave-bal-panel-head">
            <div class="panel-title-group">
                <h3>Balance directory</h3>
                <span class="panel-badge" id="leaveBalCount"><?php echo $total_rows; ?> shown</span>
            </div>
            <div class="leave-bal-search-form" role="search">
                <div class="leave-bal-search">
                    <svg class="leave-bal-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="lb-q" value="<?php echo htmlspecialchars($filter_q); ?>" placeholder="Search by name or employee ID…" autocomplete="off" aria-label="Search employees">
                    <button type="button" class="leave-bal-search-clear" id="lbSearchClear" hidden aria-label="Clear search" title="Clear search">&times;</button>
                </div>
            </div>
            <a href="settings.php?tab=leave" class="btn btn-outline btn-sm leave-bal-quotas-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Leave quotas
            </a>
        </div>
        <div class="panel-body padded">
            <?php if ($total_rows > 0 && count($quota_codes) > 0): ?>
            <div class="table-wrap" id="leaveBalTableWrap">
                <table class="data-table data-table-compact leave-balances-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <?php foreach ($quota_codes as $code): ?>
                                <th class="col-num" title="<?php echo htmlspecialchars($leave_types[$code]['name'] ?? $code); ?>"><?php echo htmlspecialchars($code); ?></th>
                            <?php endforeach; ?>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row):
                            $emp = $row['employee'];
                            $is_active = (int) ($emp['is_active'] ?? 0) === 1;
                            $search_blob = strtolower(implode(' ', [
                                $emp['name'] ?? '',
                                $emp['emp_id'] ?? '',
                                $emp['department'] ?? '',
                            ]));
                        ?>
                        <tr class="leave-bal-row<?php echo $is_active ? '' : ' emp-row-inactive'; ?>" data-search="<?php echo htmlspecialchars($search_blob, ENT_QUOTES, 'UTF-8'); ?>">
                            <td>
                                <div class="cell-employee">
                                    <span class="emp-avatar"><?php echo htmlspecialchars(strtoupper(substr($emp['name'], 0, 1))); ?></span>
                                    <div>
                                        <a href="employee_view.php?emp_id=<?php echo rawurlencode($emp['emp_id']); ?>" class="emp-name"><?php echo htmlspecialchars($emp['name']); ?></a>
                                        <span class="emp-id"><?php echo htmlspecialchars($emp['emp_id']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($emp['department'] ?: '—'); ?></td>
                            <?php foreach ($quota_codes as $code):
                                $bal = (float) ($row['balances'][$code] ?? 0);
                                $pending = (float) ($row['pending'][$code] ?? 0);
                                $cell_class = $bal <= 0 ? 'is-zero' : ($bal <= 1 ? 'is-low' : '');
                            ?>
                            <td class="col-num leave-bal-cell <?php echo $cell_class; ?>">
                                <strong><?php echo format_money($bal); ?></strong>
                                <?php if ($pending > 0): ?><span class="leave-bal-pending" title="Pending approval">+<?php echo format_money($pending); ?> pending</span><?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                            <td><?php echo $is_active ? '<span class="badge badge-present">Active</span>' : '<span class="badge badge-absent">Inactive</span>'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="empty-state compact leave-bal-search-empty" id="leaveBalSearchEmpty" hidden>
                <h4>No matches</h4>
                <p>Try a different name or employee ID.</p>
            </div>
            <?php elseif (count($quota_codes) === 0): ?>
            <div class="empty-state compact"><h4>No leave quotas configured</h4><p>Set PL, SL and CL quotas in Settings → Leave &amp; Attendance.</p><a href="settings.php?tab=leave" class="btn btn-sm">Open settings</a></div>
            <?php else: ?>
            <div class="empty-state compact"><h4>No employees found</h4></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    var searchInput = document.getElementById('lb-q');
    if (!searchInput) return;

    var rows = document.querySelectorAll('.leave-balances-table tbody .leave-bal-row');
    var badge = document.getElementById('leaveBalCount');
    var clearBtn = document.getElementById('lbSearchClear');
    var tableWrap = document.getElementById('leaveBalTableWrap');
    var noResults = document.getElementById('leaveBalSearchEmpty');
    var totalRows = rows.length;

    function applyFilter() {
        var q = searchInput.value.trim().toLowerCase();
        var visible = 0;

        rows.forEach(function (row) {
            var haystack = row.getAttribute('data-search') || '';
            var match = q === '' || haystack.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (badge) {
            badge.textContent = visible + ' shown' + (q ? ' · filtered' : '');
        }
        if (clearBtn) clearBtn.hidden = q === '';
        if (noResults) noResults.hidden = visible > 0 || totalRows === 0;
        if (tableWrap) tableWrap.hidden = visible === 0 && totalRows > 0;
    }

    searchInput.addEventListener('input', applyFilter);
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            applyFilter();
            searchInput.focus();
        });
    }

    applyFilter();
})();
</script>
<?php require 'includes/footer.php'; ?>
