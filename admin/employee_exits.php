<?php
require_once 'includes/admin_page_init.php';
admin_page_init('exits');
require_once 'includes/hrm_modules_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$exits = get_employee_exits($conn, $branch_id);
$selected_id = (int) ($_GET['exit_id'] ?? 0);
$selected = null;
foreach ($exits as $ex) {
    if ((int) $ex['id'] === $selected_id) {
        $selected = $ex;
        break;
    }
}
if (!$selected && $exits !== []) {
    $selected = $exits[0];
    $selected_id = (int) $selected['id'];
}

$bf = branch_employees_sql('e');
$emp_stmt = $conn->prepare('SELECT emp_id, name FROM employees e WHERE e.is_active = 1' . $bf['sql'] . ' ORDER BY e.name');
bind_branch_stmt_params($emp_stmt, $bf['types'], $bf['params']);
$emp_stmt->execute();
$employees = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$active_exits = 0;
$fnf_pending = 0;
$completed_exits = 0;
$status_counts = ['initiated' => 0, 'clearance' => 0, 'fnf_pending' => 0, 'completed' => 0];
foreach ($exits as $ex) {
    $st = $ex['status'] ?? 'initiated';
    if (isset($status_counts[$st])) {
        $status_counts[$st]++;
    }
    if ($st === 'completed') {
        $completed_exits++;
    } else {
        $active_exits++;
        if ($st === 'fnf_pending' || ($ex['fnf_status'] ?? '') === 'draft') {
            $fnf_pending++;
        }
    }
}

$exit_steps = [
    'initiated' => 'Initiated',
    'clearance' => 'Clearance',
    'fnf_pending' => 'F&F pending',
    'completed' => 'Completed',
];
$exit_type_labels = ['resignation' => 'Resignation', 'termination' => 'Termination', 'retirement' => 'Retirement'];
$status_class = [
    'initiated' => 'exit-status-initiated',
    'clearance' => 'exit-status-clearance',
    'fnf_pending' => 'exit-status-fnf',
    'completed' => 'exit-status-done',
];

$workflow_step = 1;
if ($exits !== []) {
    $workflow_step = 2;
}
if ($selected_id && $selected) {
    $workflow_step = 3;
}
if ($selected && ($selected['status'] ?? '') === 'completed') {
    $workflow_step = 4;
}
?>
<div class="hrm-page exits-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">People</p>
            <h2>Exit &amp; full &amp; final</h2>
            <p>Offboarding workflow<?php echo $branch_id !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?> — resignation through F&amp;F settlement.</p>
        </div>
        <div class="page-header-actions">
            <a href="employees.php" class="btn btn-outline">Employees</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip warn"><span class="status-dot"></span><div><strong><?php echo $active_exits; ?></strong><span>Active exits</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo $fnf_pending; ?></strong><span>F&amp;F pending</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo $completed_exits; ?></strong><span>Completed</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($exits); ?></strong><span>Total records</span></div></div>
    </div>

    <nav class="exits-workflow" aria-label="Exit workflow">
        <div class="exits-workflow-step<?php echo $workflow_step >= 1 ? ' is-done' : ''; ?><?php echo $workflow_step === 1 ? ' is-current' : ''; ?>">
            <span class="exits-workflow-num">1</span>
            <span class="exits-workflow-label">Initiate exit</span>
        </div>
        <span class="exits-workflow-arrow" aria-hidden="true">→</span>
        <div class="exits-workflow-step<?php echo $workflow_step >= 2 ? ' is-done' : ''; ?><?php echo $workflow_step === 2 ? ' is-current' : ''; ?>">
            <span class="exits-workflow-num">2</span>
            <span class="exits-workflow-label">Clearance</span>
        </div>
        <span class="exits-workflow-arrow" aria-hidden="true">→</span>
        <div class="exits-workflow-step<?php echo $workflow_step >= 3 ? ' is-done' : ''; ?><?php echo $workflow_step === 3 ? ' is-current' : ''; ?>">
            <span class="exits-workflow-num">3</span>
            <span class="exits-workflow-label">F&amp;F settlement</span>
        </div>
        <span class="exits-workflow-arrow" aria-hidden="true">→</span>
        <div class="exits-workflow-step<?php echo $workflow_step >= 4 ? ' is-done' : ''; ?><?php echo $workflow_step === 4 ? ' is-current' : ''; ?>">
            <span class="exits-workflow-num">4</span>
            <span class="exits-workflow-label">Complete</span>
        </div>
    </nav>

    <div class="exits-layout">
        <section class="panel panel-elevated exits-initiate-panel">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Initiate exit</h3>
                    <span class="panel-badge"><?php echo count($employees); ?> active employee<?php echo count($employees) === 1 ? '' : 's'; ?></span>
                </div>
            </div>
            <div class="panel-body padded">
                <div class="exits-initiate-split">
                    <div class="settings-add-panel masters-add-panel exits-add-panel">
                        <div class="settings-add-panel-head">
                            <span class="settings-add-panel-icon exits-add-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            </span>
                            <div class="settings-add-panel-head-text">
                                <h4>Start offboarding</h4>
                                <p>F&amp;F draft is calculated automatically from salary and leave balance.</p>
                            </div>
                        </div>
                        <form method="POST" action="exit_save.php" class="settings-add-panel-body">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="exit_action" value="initiate">
                            <div class="form-group">
                                <label for="exit_emp">Employee</label>
                                <select name="emp_id" id="exit_emp" required class="settings-add-select">
                                    <option value="">Select employee</option>
                                    <?php foreach ($employees as $e): ?>
                                        <option value="<?php echo htmlspecialchars($e['emp_id']); ?>"><?php echo htmlspecialchars($e['name'] . ' (' . $e['emp_id'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="exits-form-grid">
                                <div class="form-group">
                                    <label for="exit_type">Exit type</label>
                                    <select name="exit_type" id="exit_type" class="settings-add-select">
                                        <?php foreach ($exit_type_labels as $val => $label): ?>
                                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><label for="resignation_date">Resignation date</label><input type="date" name="resignation_date" id="resignation_date" class="settings-add-input"></div>
                                <div class="form-group"><label for="last_working_day">Last working day</label><input type="date" name="last_working_day" id="last_working_day" required class="settings-add-input"></div>
                            </div>
                            <div class="form-group">
                                <label for="exit_reason">Reason</label>
                                <textarea name="reason" id="exit_reason" rows="2" class="settings-add-input" placeholder="Optional notes…"></textarea>
                            </div>
                            <div class="settings-add-panel-foot">
                                <button type="submit" class="btn btn-header settings-add-submit">Start exit process</button>
                            </div>
                        </form>
                    </div>

                    <div class="exits-initiate-side">
                        <div class="exits-guide-card">
                            <h4>How it works</h4>
                            <ol class="exits-guide-list">
                                <li>Select employee and last working day</li>
                                <li>System drafts F&amp;F from payroll data</li>
                                <li>Run clearance and approve settlement</li>
                                <li>Mark complete to deactivate employee</li>
                            </ol>
                        </div>
                        <?php if ($exits !== []): ?>
                        <div class="exits-quick-stats">
                            <div class="exits-quick-stat"><strong><?php echo $active_exits; ?></strong><span>In progress</span></div>
                            <div class="exits-quick-stat"><strong><?php echo $fnf_pending; ?></strong><span>F&amp;F pending</span></div>
                            <div class="exits-quick-stat"><strong><?php echo $completed_exits; ?></strong><span>Completed</span></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel panel-elevated exits-detail-panel" id="exit-records">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Exit records</h3>
                    <span class="panel-badge" id="exitListCount"><?php echo count($exits); ?> record<?php echo count($exits) === 1 ? '' : 's'; ?></span>
                </div>
                <?php if ($exits !== []): ?>
                <div class="masters-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="exitSearch" placeholder="Search employee…" autocomplete="off" aria-label="Search exits">
                    <button type="button" class="masters-search-clear" id="exitSearchClear" hidden aria-label="Clear">&times;</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="panel-body padded">
                <?php if ($exits === []): ?>
                    <div class="masters-empty exits-records-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/></svg>
                        <h4>No exits yet</h4>
                        <p>Use the form above to start the first offboarding process.</p>
                    </div>
                <?php else: ?>
                    <div class="exits-status-strip" role="group" aria-label="Filter by status">
                        <button type="button" class="exits-filter-pill is-active" data-exit-filter="all">All <span class="exits-filter-count"><?php echo count($exits); ?></span></button>
                        <?php foreach ($exit_steps as $key => $label): ?>
                            <button type="button" class="exits-filter-pill <?php echo $status_class[$key]; ?>" data-exit-filter="<?php echo $key; ?>">
                                <?php echo $label; ?> <span class="exits-filter-count"><?php echo $status_counts[$key]; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="exits-records-split">
                        <div class="exits-records-side">
                            <ul class="exits-exit-picker" id="exitList">
                                <?php foreach ($exits as $ex):
                                    $is_sel = (int) $ex['id'] === $selected_id;
                                    $st = $ex['status'] ?? 'initiated';
                                    $search = strtolower($ex['emp_name'] . ' ' . ($ex['emp_id'] ?? '') . ' ' . ($exit_type_labels[$ex['exit_type']] ?? '') . ' ' . $st);
                                ?>
                                <li data-status="<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                                    <a href="employee_exits.php?exit_id=<?php echo (int) $ex['id']; ?>#exit-detail"
                                       class="exits-exit-item<?php echo $is_sel ? ' is-active' : ''; ?><?php echo $st === 'completed' ? ' is-completed' : ''; ?>">
                                        <span class="exits-card-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($ex['emp_name'], 0, 1))); ?></span>
                                        <span class="exits-card-body">
                                            <strong><?php echo htmlspecialchars($ex['emp_name']); ?></strong>
                                            <span><?php echo htmlspecialchars($exit_type_labels[$ex['exit_type']] ?? $ex['exit_type']); ?> · LWD <?php echo date('d M Y', strtotime($ex['last_working_day'])); ?></span>
                                        </span>
                                        <span class="exits-status-badge <?php echo $status_class[$st] ?? ''; ?>"><?php echo htmlspecialchars($exit_steps[$st] ?? $st); ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="masters-empty masters-search-empty" id="exitNoMatch" hidden><h4>No matches</h4><p>Try a different name or status filter.</p></div>
                        </div>

                        <?php if ($selected): ?>
                        <div class="exits-exit-detail" id="exit-detail">
                            <div class="exits-exit-detail-head">
                                <h4><?php echo htmlspecialchars($selected['emp_name']); ?></h4>
                                <div class="exits-exit-detail-meta">
                                    <p class="exits-exit-detail-sub">
                                        <span class="exits-exit-dept"><?php echo htmlspecialchars($exit_type_labels[$selected['exit_type']] ?? $selected['exit_type']); ?></span>
                                        <span class="exits-exit-lwd">LWD <?php echo date('d M Y', strtotime($selected['last_working_day'])); ?></span>
                                        <a href="employee_view.php?emp_id=<?php echo urlencode($selected['emp_id']); ?>" class="exits-exit-link">View employee</a>
                                    </p>
                                    <span class="exits-status-badge exits-status-badge-lg <?php echo $status_class[$selected['status'] ?? 'initiated'] ?? ''; ?>"><?php echo htmlspecialchars($exit_steps[$selected['status']] ?? $selected['status']); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($selected['reason'])): ?>
                                <p class="exits-exit-reason"><strong>Reason:</strong> <?php echo htmlspecialchars($selected['reason']); ?></p>
                            <?php endif; ?>

                            <div class="exits-fnf-panel">
                                <div class="hrm-callout hrm-callout-warn exits-fnf-disclaimer">
                                    <strong>F&amp;F amounts are auto-calculated drafts</strong>
                                    <span>Verify salary due, leave encashment, notice pay, and deductions against payroll records before approving final payment.</span>
                                </div>
                                <div class="exits-fnf-head">
                                    <div>
                                        <h5>F&amp;F settlement</h5>
                                        <span class="text-muted">Adjust amounts and recalculate net payable</span>
                                    </div>
                                    <?php if (!empty($selected['fnf_status'])): ?>
                                        <span class="badge badge-neutral"><?php echo htmlspecialchars($selected['fnf_status']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="exit_save.php" class="exits-fnf-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="exit_action" value="recalc_fnf">
                                    <input type="hidden" name="exit_id" value="<?php echo (int) $selected['id']; ?>">
                                    <input type="hidden" name="last_working_day" value="<?php echo htmlspecialchars($selected['last_working_day']); ?>">
                                    <div class="exits-fnf-grid">
                                        <div class="form-group"><label>Salary due</label><input type="number" name="salary_due" step="0.01" value="<?php echo htmlspecialchars((string) ($selected['salary_due'] ?? $selected['net_payable'] ?? '')); ?>" class="settings-add-input"></div>
                                        <div class="form-group"><label>Leave encashment</label><input type="number" name="leave_encashment" step="0.01" value="<?php echo htmlspecialchars((string) ($selected['leave_encashment'] ?? '')); ?>" class="settings-add-input"></div>
                                        <div class="form-group"><label>Notice pay</label><input type="number" name="notice_pay" step="0.01" value="<?php echo htmlspecialchars((string) ($selected['notice_pay'] ?? '0')); ?>" class="settings-add-input"></div>
                                        <div class="form-group"><label>Deductions</label><input type="number" name="deductions" step="0.01" value="<?php echo htmlspecialchars((string) ($selected['deductions'] ?? '0')); ?>" class="settings-add-input"></div>
                                    </div>
                                    <button type="submit" class="btn btn-outline btn-sm">Recalculate F&amp;F</button>
                                </form>

                                <?php if (!empty($selected['net_payable']) || isset($selected['salary_due'])): ?>
                                <div class="exits-fnf-summary-card">
                                    <span class="exits-fnf-label">Net payable (draft)</span>
                                    <strong class="exits-fnf-amount"><?php echo format_money((float) ($selected['net_payable'] ?? 0)); ?></strong>
                                    <a href="fnf_pdf.php?exit_id=<?php echo (int) $selected['id']; ?>" class="btn btn-outline btn-sm exits-fnf-pdf-btn">Download F&amp;F PDF</a>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="exits-action-bar">
                                <form method="POST" action="exit_save.php" class="exits-status-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="exit_action" value="status">
                                    <input type="hidden" name="exit_id" value="<?php echo (int) $selected['id']; ?>">
                                    <label class="sr-only" for="exit_status">Update status</label>
                                    <select name="status" id="exit_status" class="settings-add-select">
                                        <option value="clearance"<?php echo ($selected['status'] ?? '') === 'clearance' ? ' selected' : ''; ?>>Mark clearance</option>
                                        <option value="fnf_pending"<?php echo ($selected['status'] ?? '') === 'fnf_pending' ? ' selected' : ''; ?>>F&amp;F pending</option>
                                        <option value="completed"<?php echo ($selected['status'] ?? '') === 'completed' ? ' selected' : ''; ?>>Complete exit</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline btn-sm">Update status</button>
                                </form>
                                <form method="POST" action="exit_save.php" class="exits-inline-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="exit_action" value="approve_fnf">
                                    <input type="hidden" name="exit_id" value="<?php echo (int) $selected['id']; ?>">
                                    <button type="submit" class="btn btn-header btn-sm">Approve F&amp;F</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<script>
(function () {
    var exitFilter = 'all';
    var input = document.getElementById('exitSearch');
    var list = document.getElementById('exitList');

    function applyExitFilters() {
        if (!list) return;
        var q = input ? input.value.trim().toLowerCase() : '';
        var badge = document.getElementById('exitListCount');
        var noMatch = document.getElementById('exitNoMatch');
        var clearBtn = document.getElementById('exitSearchClear');
        var items = list.querySelectorAll('li');
        var visible = 0;
        items.forEach(function (el) {
            var matchSearch = q === '' || (el.getAttribute('data-search') || '').indexOf(q) !== -1;
            var matchStatus = exitFilter === 'all' || el.getAttribute('data-status') === exitFilter;
            var show = matchSearch && matchStatus;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (badge) badge.textContent = visible + ' shown' + (q || exitFilter !== 'all' ? ' · filtered' : '');
        if (clearBtn) clearBtn.hidden = q === '';
        if (noMatch) noMatch.hidden = visible > 0;
        list.hidden = visible === 0 && items.length > 0;
    }

    if (input) {
        input.addEventListener('input', applyExitFilters);
        var clear = document.getElementById('exitSearchClear');
        if (clear) clear.addEventListener('click', function () { input.value = ''; applyExitFilters(); input.focus(); });
    }

    document.querySelectorAll('[data-exit-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-exit-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            exitFilter = btn.getAttribute('data-exit-filter') || 'all';
            applyExitFilters();
        });
    });

    if (window.location.hash === '#exit-detail' || window.location.hash === '#exit-records') {
        var target = document.querySelector(window.location.hash);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
<?php require 'includes/footer.php'; ?>
