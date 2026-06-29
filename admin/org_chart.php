<?php
require_once 'includes/admin_page_init.php';
admin_page_init('org');
require_once 'includes/hrm_modules_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$tree = get_org_chart_tree($conn, $branch_id);
$manager_candidates = get_manager_candidates($conn, $branch_id);
$bf = branch_employees_sql('e');
$emp_list_stmt = $conn->prepare('SELECT e.emp_id, e.name, e.department, e.manager_emp_id FROM employees e WHERE e.is_active = 1' . $bf['sql'] . ' ORDER BY e.name ASC');
bind_branch_stmt_params($emp_list_stmt, $bf['types'], $bf['params']);
$emp_list_stmt->execute();
$org_employees = $emp_list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function count_org_stats(array $nodes): array
{
    $stats = ['total' => 0, 'managers' => 0, 'max_depth' => 0];
    $walk = static function (array $list, int $depth) use (&$walk, &$stats): void {
        foreach ($list as $node) {
            $stats['total']++;
            if (!empty($node['reports'])) {
                $stats['managers']++;
                $walk($node['reports'], $depth + 1);
            }
            $stats['max_depth'] = max($stats['max_depth'], $depth);
        }
    };
    $walk($nodes, 1);
    return $stats;
}

$org_stats = count_org_stats($tree);

$no_mgr_stmt = $conn->prepare('SELECT COUNT(*) AS c FROM employees e WHERE e.is_active = 1 AND (e.manager_emp_id IS NULL OR e.manager_emp_id = "")' . $bf['sql']);
bind_branch_stmt_params($no_mgr_stmt, $bf['types'], $bf['params']);
$no_mgr_stmt->execute();
$no_manager_count = (int) ($no_mgr_stmt->get_result()->fetch_assoc()['c'] ?? 0);

function render_org_node(array $node): void
{
    $inactive = (int) ($node['is_active'] ?? 1) !== 1;
    $initial = strtoupper(substr($node['name'], 0, 1));
    $report_count = count($node['reports'] ?? []);
    $search = strtolower($node['name'] . ' ' . $node['emp_id'] . ' ' . ($node['designation'] ?? '') . ' ' . ($node['department'] ?? ''));
    ?>
    <li class="org-node <?php echo $inactive ? 'is-inactive' : ''; ?>" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="org-card">
            <span class="org-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></span>
            <div class="org-card-body">
                <a href="employee_view.php?emp_id=<?php echo rawurlencode($node['emp_id']); ?>" class="org-name"><?php echo htmlspecialchars($node['name']); ?></a>
                <span class="org-meta"><?php echo htmlspecialchars($node['designation'] ?: 'Staff'); ?> · <?php echo htmlspecialchars($node['department'] ?: 'General'); ?></span>
                <span class="org-emp-id"><?php echo htmlspecialchars($node['emp_id']); ?></span>
            </div>
            <?php if ($report_count > 0): ?>
                <span class="org-reports-badge" title="Direct reports"><?php echo $report_count; ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($node['reports'])): ?>
            <ul class="org-children">
                <?php foreach ($node['reports'] as $child): render_org_node($child); endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
}
?>
<div class="hrm-page org-chart-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">People</p>
            <h2>Organization chart</h2>
            <p>Reporting hierarchy at <strong><?php echo htmlspecialchars($active_branch_label); ?></strong> — built from each employee’s reporting manager.</p>
        </div>
        <div class="page-header-actions">
            <a href="employees.php" class="btn btn-header">Employees</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <section class="panel panel-elevated org-bulk-panel">
        <div class="panel-header masters-panel-head">
            <div class="panel-title-group">
                <h3>Bulk assign reporting manager</h3>
                <span class="panel-badge"><?php echo count($org_employees); ?> employees</span>
            </div>
        </div>
        <div class="panel-body padded">
            <form method="POST" action="org_save.php" class="org-bulk-form">
                <?php echo csrf_field(); ?>
                <div class="org-bulk-toolbar">
                    <div class="form-group">
                        <label for="bulk_manager">Reporting manager</label>
                        <select name="manager_emp_id" id="bulk_manager" class="settings-add-select">
                            <option value="">— Clear manager —</option>
                            <?php foreach ($manager_candidates as $m): ?>
                                <option value="<?php echo htmlspecialchars($m['emp_id']); ?>"><?php echo htmlspecialchars($m['name'] . ' (' . $m['emp_id'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label class="org-bulk-filter">
                        <input type="checkbox" name="only_no_manager" value="1" id="onlyNoManager">
                        <span>Show only employees without a manager</span>
                    </label>
                    <button type="button" class="btn btn-outline btn-sm" id="orgSelectAll">Select all visible</button>
                    <button type="button" class="btn btn-outline btn-sm" id="orgSelectNone">Clear selection</button>
                </div>
                <ul class="org-bulk-list" id="orgBulkList">
                    <?php foreach ($org_employees as $emp):
                        $has_mgr = !empty($emp['manager_emp_id']);
                    ?>
                    <li class="org-bulk-item<?php echo $has_mgr ? '' : ' is-no-manager'; ?>" data-no-manager="<?php echo $has_mgr ? '0' : '1'; ?>">
                        <label>
                            <input type="checkbox" name="emp_ids[]" value="<?php echo htmlspecialchars($emp['emp_id']); ?>">
                            <span class="org-bulk-name"><?php echo htmlspecialchars($emp['name']); ?></span>
                            <span class="org-bulk-meta"><?php echo htmlspecialchars($emp['emp_id']); ?> · <?php echo htmlspecialchars($emp['department'] ?: 'General'); ?></span>
                            <?php if ($has_mgr): ?>
                                <span class="org-bulk-mgr">Current: <?php echo htmlspecialchars($emp['manager_emp_id']); ?></span>
                            <?php else: ?>
                                <span class="org-bulk-mgr org-bulk-mgr-warn">No manager</span>
                            <?php endif; ?>
                        </label>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="org-bulk-foot">
                    <button type="submit" class="btn btn-header" onclick="return confirm('Update reporting manager for selected employees?');">Apply to selected</button>
                </div>
            </form>
        </div>
    </section>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo $org_stats['total']; ?></strong><span>In chart</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo $org_stats['managers']; ?></strong><span>With direct reports</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($tree); ?></strong><span>Top-level roots</span></div></div>
        <div class="settings-status-chip <?php echo $no_manager_count > 0 ? 'warn' : 'ok'; ?>"><span class="status-dot"></span><div><strong><?php echo $no_manager_count; ?></strong><span>No manager set</span></div></div>
    </div>

    <?php if ($no_manager_count > 0): ?>
        <div class="hrm-callout hrm-callout-warn org-chart-callout">
            <strong><?php echo $no_manager_count; ?> employee<?php echo $no_manager_count === 1 ? '' : 's'; ?> without a reporting manager</strong>
            <span>Use <strong>Bulk assign reporting manager</strong> above or edit individual employee profiles.</span>
        </div>
    <?php endif; ?>

    <div class="panel panel-elevated org-chart-panel">
        <div class="panel-header masters-panel-head">
            <div class="panel-title-group">
                <h3>Hierarchy</h3>
                <span class="panel-badge" id="orgChartCount"><?php echo $org_stats['total']; ?> people</span>
            </div>
            <?php if ($tree !== []): ?>
            <div class="masters-search-wrap">
                <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" id="orgChartSearch" placeholder="Search name, ID, role…" autocomplete="off" aria-label="Search org chart">
                <button type="button" class="masters-search-clear" id="orgChartClear" hidden aria-label="Clear">&times;</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="panel-body padded org-chart-body">
            <?php if ($tree === []): ?>
                <div class="masters-empty org-chart-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="5" r="2"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/><path d="M12 7v4M12 11H6M12 11h6"/></svg>
                    <h4>No org chart yet</h4>
                    <p>Add employees and set a <strong>reporting manager</strong> on each profile to build the hierarchy.</p>
                    <a href="employees.php" class="btn btn-header">Go to employees</a>
                </div>
            <?php else: ?>
                <ul class="org-tree" id="orgChartTree">
                    <?php foreach ($tree as $root): render_org_node($root); endforeach; ?>
                </ul>
                <div class="masters-empty masters-search-empty" id="orgChartNoMatch" hidden>
                    <h4>No matches</h4>
                    <p>Try a different name or employee ID.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    var onlyNoMgr = document.getElementById('onlyNoManager');
    var bulkItems = document.querySelectorAll('.org-bulk-item');
    if (onlyNoMgr && bulkItems.length) {
        function applyBulkFilter() {
            var filter = onlyNoMgr.checked;
            bulkItems.forEach(function (item) {
                var noMgr = item.getAttribute('data-no-manager') === '1';
                item.style.display = !filter || noMgr ? '' : 'none';
            });
        }
        onlyNoMgr.addEventListener('change', applyBulkFilter);
    }
    var selectAll = document.getElementById('orgSelectAll');
    var selectNone = document.getElementById('orgSelectNone');
    if (selectAll) {
        selectAll.addEventListener('click', function () {
            document.querySelectorAll('.org-bulk-item').forEach(function (item) {
                if (item.style.display === 'none') return;
                var cb = item.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = true;
            });
        });
    }
    if (selectNone) {
        selectNone.addEventListener('click', function () {
            document.querySelectorAll('.org-bulk-item input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
        });
    }

    var input = document.getElementById('orgChartSearch');
    var clearBtn = document.getElementById('orgChartClear');
    var badge = document.getElementById('orgChartCount');
    var noMatch = document.getElementById('orgChartNoMatch');
    var tree = document.getElementById('orgChartTree');
    if (!input || !tree) return;

    var nodes = tree.querySelectorAll('.org-node');
    var total = nodes.length;

    function apply() {
        var q = input.value.trim().toLowerCase();
        var visible = 0;
        nodes.forEach(function (node) {
            var hay = node.getAttribute('data-search') || '';
            var match = q === '' || hay.indexOf(q) !== -1;
            node.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (badge) badge.textContent = visible + ' shown' + (q ? ' · filtered' : '');
        if (clearBtn) clearBtn.hidden = q === '';
        if (noMatch) noMatch.hidden = visible > 0;
        tree.hidden = visible === 0 && total > 0;
    }

    input.addEventListener('input', apply);
    if (clearBtn) clearBtn.addEventListener('click', function () { input.value = ''; apply(); input.focus(); });
})();
</script>
<?php require 'includes/footer.php'; ?>
