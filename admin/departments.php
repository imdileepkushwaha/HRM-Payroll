<?php
require_once 'includes/admin_page_init.php';
admin_page_init('masters');
require_once 'includes/hrm_modules_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$departments = get_departments($conn, $branch_id, false);
$designations = get_designations($conn, null, false);
$branches = get_branches($conn);
$branch_map = [];
foreach ($branches as $b) {
    $branch_map[(int) $b['id']] = $b['name'];
}

$active_dept_count = 0;
foreach ($departments as $d) {
    if ((int) ($d['is_active'] ?? 0) === 1) {
        $active_dept_count++;
    }
}

$desig_by_dept = [];
foreach ($designations as $des) {
    $key = (int) ($des['department_id'] ?? 0);
    $desig_by_dept[$key] = ($desig_by_dept[$key] ?? 0) + 1;
}

$dept_emp_counts = [];
$bf = branch_employees_sql('e');
$count_sql = 'SELECT e.department_id, COUNT(*) AS c FROM employees e WHERE e.department_id IS NOT NULL' . $bf['sql'] . ' GROUP BY e.department_id';
$count_stmt = $conn->prepare($count_sql);
bind_branch_stmt_params($count_stmt, $bf['types'], $bf['params']);
$count_stmt->execute();
$count_res = $count_stmt->get_result();
while ($row = $count_res->fetch_assoc()) {
    $dept_emp_counts[(int) $row['department_id']] = (int) $row['c'];
}
?>
<div class="hrm-page masters-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">People</p>
            <h2>Departments &amp; designations</h2>
            <p>Organize your workforce structure<?php echo $branch_id !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ' across all branches'; ?> — used when adding or editing employees.</p>
        </div>
        <div class="page-header-actions">
            <a href="org_chart.php" class="btn btn-outline">Org chart</a>
            <a href="employees.php" class="btn btn-header">Employees</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if ($branch_id === null): ?>
        <div class="alert alert-page masters-branch-alert">Showing company-wide masters. Select a branch from the top bar to scope new departments.</div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row masters-status">
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div><strong><?php echo count($departments); ?></strong><span>Departments</span></div>
        </div>
        <div class="settings-status-chip ok">
            <span class="status-dot"></span>
            <div><strong><?php echo $active_dept_count; ?></strong><span>Active departments</span></div>
        </div>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div><strong><?php echo count($designations); ?></strong><span>Designations</span></div>
        </div>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div><strong><?php echo count($branches); ?></strong><span>Branches</span></div>
        </div>
    </div>

    <div class="masters-grid">
        <section class="panel panel-elevated masters-panel masters-panel-dept">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Departments</h3>
                    <span class="panel-badge" id="mastersDeptCount"><?php echo count($departments); ?> listed</span>
                </div>
                <div class="masters-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="mastersDeptSearch" placeholder="Search departments…" autocomplete="off" aria-label="Search departments">
                    <button type="button" class="masters-search-clear" id="mastersDeptClear" hidden aria-label="Clear">&times;</button>
                </div>
            </div>

            <div class="panel-body padded masters-panel-body">
                <div class="settings-add-panel masters-add-panel masters-add-dept">
                    <div class="settings-add-panel-head">
                        <span class="settings-add-panel-icon masters-add-icon-dept" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9h18v10H3z"/><path d="M9 9V5h6v4"/></svg>
                        </span>
                        <div class="settings-add-panel-head-text">
                            <h4>Add department</h4>
                            <p>Short code + display name. Leave branch as “All branches” for company-wide use.</p>
                        </div>
                    </div>
                    <form method="POST" action="departments_save.php" class="settings-add-panel-body masters-add-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="master_action" value="department">
                        <div class="masters-add-fields">
                            <div class="form-group">
                                <label for="dept_code">Code</label>
                                <input type="text" name="code" id="dept_code" required placeholder="ENG" maxlength="20" class="settings-add-input">
                            </div>
                            <div class="form-group masters-field-grow">
                                <label for="dept_name">Name</label>
                                <input type="text" name="name" id="dept_name" required placeholder="Engineering" maxlength="100" class="settings-add-input">
                            </div>
                            <div class="form-group">
                                <label for="dept_branch">Branch</label>
                                <select name="branch_id" id="dept_branch" class="settings-add-select">
                                    <option value="">All branches</option>
                                    <?php foreach ($branches as $b): ?>
                                        <option value="<?php echo (int) $b['id']; ?>" <?php echo $branch_id === (int) $b['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="settings-add-panel-foot">
                            <button type="submit" class="btn btn-header settings-add-submit">Add department</button>
                        </div>
                    </form>
                </div>

                <?php if ($departments === []): ?>
                    <div class="masters-empty" id="mastersDeptEmpty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 9h18v10H3z"/><path d="M9 9V5h6v4"/><path d="M12 13v4M10 15h4"/></svg>
                        <h4>No departments yet</h4>
                        <p>Add your first department above, or they will be created when you migrate from existing employee records.</p>
                    </div>
                <?php else: ?>
                    <ul class="masters-card-list" id="mastersDeptList">
                        <?php foreach ($departments as $d):
                            $is_active = (int) ($d['is_active'] ?? 0) === 1;
                            $dept_id = (int) $d['id'];
                            $emp_count = $dept_emp_counts[$dept_id] ?? 0;
                            $desig_count = $desig_by_dept[$dept_id] ?? 0;
                            $branch_label = $d['branch_id'] ? ($branch_map[(int) $d['branch_id']] ?? 'Branch') : 'All branches';
                            $search_blob = strtolower($d['code'] . ' ' . $d['name'] . ' ' . $branch_label);
                        ?>
                        <li class="masters-card masters-dept-card<?php echo $is_active ? '' : ' is-inactive'; ?>" data-search="<?php echo htmlspecialchars($search_blob, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="masters-card-icon" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($d['name'], 0, 2))); ?></div>
                            <div class="masters-card-main">
                                <div class="masters-card-title-row">
                                    <strong class="masters-card-title"><?php echo htmlspecialchars($d['name']); ?></strong>
                                    <span class="masters-code-chip"><?php echo htmlspecialchars($d['code']); ?></span>
                                </div>
                                <p class="masters-card-meta">
                                    <span><?php echo htmlspecialchars($branch_label); ?></span>
                                    <span><?php echo $emp_count; ?> employee<?php echo $emp_count === 1 ? '' : 's'; ?></span>
                                    <span><?php echo $desig_count; ?> designation<?php echo $desig_count === 1 ? '' : 's'; ?></span>
                                </p>
                            </div>
                            <div class="masters-card-actions">
                                <span class="badge <?php echo $is_active ? 'badge-present' : 'badge-absent'; ?>"><?php echo $is_active ? 'Active' : 'Inactive'; ?></span>
                                <button type="button" class="masters-edit-btn" aria-expanded="false" aria-controls="masters-edit-dept-<?php echo $dept_id; ?>">
                                    <svg class="masters-edit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="masters-edit-panel-wrap" id="masters-edit-dept-<?php echo $dept_id; ?>" hidden>
                                <div class="masters-edit-panel">
                                    <div class="masters-edit-panel-head">
                                        <strong>Edit department</strong>
                                        <span>Update code, name, branch, or status</span>
                                    </div>
                                    <form method="POST" action="departments_save.php" class="masters-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="master_action" value="department">
                                        <input type="hidden" name="id" value="<?php echo $dept_id; ?>">
                                        <div class="masters-inline-fields">
                                            <div class="form-group"><label>Code</label><input type="text" name="code" value="<?php echo htmlspecialchars($d['code']); ?>" required class="settings-add-input"></div>
                                            <div class="form-group"><label>Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($d['name']); ?>" required class="settings-add-input"></div>
                                            <div class="form-group"><label>Branch</label>
                                                <select name="branch_id" class="settings-add-select">
                                                    <option value="0" <?php echo empty($d['branch_id']) ? 'selected' : ''; ?>>All branches</option>
                                                    <?php foreach ($branches as $b): ?>
                                                        <option value="<?php echo (int) $b['id']; ?>" <?php echo (int) ($d['branch_id'] ?? 0) === (int) $b['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <label class="masters-check-label"><input type="checkbox" name="is_active" value="1" <?php echo $is_active ? 'checked' : ''; ?>> Active</label>
                                        </div>
                                        <div class="masters-edit-panel-foot">
                                            <button type="submit" class="btn btn-sm btn-header">Save changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="masters-empty masters-search-empty" id="mastersDeptNoMatch" hidden>
                        <h4>No matches</h4>
                        <p>Try a different department name or code.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel panel-elevated masters-panel masters-panel-desig">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Designations</h3>
                    <span class="panel-badge" id="mastersDesigCount"><?php echo count($designations); ?> listed</span>
                </div>
                <div class="masters-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="mastersDesigSearch" placeholder="Search designations…" autocomplete="off" aria-label="Search designations">
                    <button type="button" class="masters-search-clear" id="mastersDesigClear" hidden aria-label="Clear">&times;</button>
                </div>
            </div>

            <div class="panel-body padded masters-panel-body">
                <div class="settings-add-panel masters-add-panel masters-add-desig">
                    <div class="settings-add-panel-head">
                        <span class="settings-add-panel-icon masters-add-icon-desig" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <div class="settings-add-panel-head-text">
                            <h4>Add designation</h4>
                            <p>Job titles linked to a department — e.g. Senior Engineer under Engineering.</p>
                        </div>
                    </div>
                    <form method="POST" action="departments_save.php" class="settings-add-panel-body masters-add-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="master_action" value="designation">
                        <div class="masters-add-fields">
                            <div class="form-group">
                                <label for="desig_code">Code</label>
                                <input type="text" name="code" id="desig_code" required placeholder="SE" maxlength="20" class="settings-add-input">
                            </div>
                            <div class="form-group masters-field-grow">
                                <label for="desig_name">Title</label>
                                <input type="text" name="name" id="desig_name" required placeholder="Senior Engineer" maxlength="100" class="settings-add-input">
                            </div>
                            <div class="form-group">
                                <label for="desig_dept">Department</label>
                                <select name="department_id" id="desig_dept" class="settings-add-select">
                                    <option value="">— Any department —</option>
                                    <?php foreach ($departments as $d): if (!(int) $d['is_active']) continue; ?>
                                        <option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="settings-add-panel-foot">
                            <button type="submit" class="btn btn-header settings-add-submit">Add designation</button>
                        </div>
                    </form>
                </div>

                <?php if ($designations === []): ?>
                    <div class="masters-empty" id="mastersDesigEmpty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                        <h4>No designations yet</h4>
                        <p>Create job titles and optionally link them to a department.</p>
                    </div>
                <?php else: ?>
                    <ul class="masters-card-list masters-desig-list" id="mastersDesigList">
                        <?php foreach ($designations as $d):
                            $desig_id = (int) $d['id'];
                            $desig_active = (int) ($d['is_active'] ?? 0) === 1;
                            $search_blob = strtolower($d['code'] . ' ' . $d['name'] . ' ' . ($d['department_name'] ?? ''));
                        ?>
                        <li class="masters-card masters-desig-card<?php echo $desig_active ? '' : ' is-inactive'; ?>" data-search="<?php echo htmlspecialchars($search_blob, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="masters-card-icon masters-card-icon-desig" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($d['code'], 0, 2))); ?></div>
                            <div class="masters-card-main">
                                <div class="masters-card-title-row">
                                    <strong class="masters-card-title"><?php echo htmlspecialchars($d['name']); ?></strong>
                                    <span class="masters-code-chip"><?php echo htmlspecialchars($d['code']); ?></span>
                                </div>
                                <p class="masters-card-meta">
                                    <?php if (!empty($d['department_name'])): ?>
                                        <span class="dept-badge"><?php echo htmlspecialchars($d['department_name']); ?></span>
                                    <?php else: ?>
                                        <span class="masters-meta-muted">All departments</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="masters-card-actions">
                                <span class="badge <?php echo $desig_active ? 'badge-present' : 'badge-absent'; ?>"><?php echo $desig_active ? 'Active' : 'Inactive'; ?></span>
                                <button type="button" class="masters-edit-btn" aria-expanded="false" aria-controls="masters-edit-desig-<?php echo $desig_id; ?>">
                                    <svg class="masters-edit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    <span>Edit</span>
                                </button>
                            </div>
                            <div class="masters-edit-panel-wrap" id="masters-edit-desig-<?php echo $desig_id; ?>" hidden>
                                <div class="masters-edit-panel">
                                    <div class="masters-edit-panel-head">
                                        <strong>Edit designation</strong>
                                        <span>Update code, title, department, or status</span>
                                    </div>
                                    <form method="POST" action="departments_save.php" class="masters-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="master_action" value="designation">
                                        <input type="hidden" name="id" value="<?php echo $desig_id; ?>">
                                        <div class="masters-inline-fields">
                                            <div class="form-group"><label>Code</label><input type="text" name="code" value="<?php echo htmlspecialchars($d['code']); ?>" required class="settings-add-input"></div>
                                            <div class="form-group"><label>Title</label><input type="text" name="name" value="<?php echo htmlspecialchars($d['name']); ?>" required class="settings-add-input"></div>
                                            <div class="form-group"><label>Department</label>
                                                <select name="department_id" class="settings-add-select">
                                                    <option value="">— Any —</option>
                                                    <?php foreach ($departments as $dept): if (!(int) ($dept['is_active'] ?? 0)) continue; ?>
                                                        <option value="<?php echo (int) $dept['id']; ?>" <?php echo (int) ($d['department_id'] ?? 0) === (int) $dept['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <label class="masters-check-label"><input type="checkbox" name="is_active" value="1" <?php echo $desig_active ? 'checked' : ''; ?>> Active</label>
                                        </div>
                                        <div class="masters-edit-panel-foot">
                                            <button type="submit" class="btn btn-sm btn-header">Save changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="masters-empty masters-search-empty" id="mastersDesigNoMatch" hidden>
                        <h4>No matches</h4>
                        <p>Try a different designation or department name.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="hrm-callout hrm-callout-info masters-tip">
        <strong>Tip</strong>
        <span>When editing an employee, pick from these masters or type a custom department/designation. Set a <a href="org_chart.php">reporting manager</a> to build your org chart.</span>
    </div>
</div>
<script>
(function () {
    function wireSearch(inputId, clearId, listId, badgeId, noMatchId, emptyId, label) {
        var input = document.getElementById(inputId);
        var list = document.getElementById(listId);
        if (!input || !list) return;

        var clearBtn = document.getElementById(clearId);
        var badge = document.getElementById(badgeId);
        var noMatch = document.getElementById(noMatchId);
        var empty = emptyId ? document.getElementById(emptyId) : null;
        var items = list.querySelectorAll('.masters-card');
        var total = items.length;

        function apply() {
            var q = input.value.trim().toLowerCase();
            var visible = 0;
            items.forEach(function (el) {
                var hay = el.getAttribute('data-search') || '';
                var match = q === '' || hay.indexOf(q) !== -1;
                el.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (badge) badge.textContent = visible + ' shown' + (q ? ' · filtered' : '');
            if (clearBtn) clearBtn.hidden = q === '';
            if (noMatch) noMatch.hidden = visible > 0 || total === 0;
            if (empty) empty.hidden = q !== '' || total > 0;
            list.hidden = visible === 0 && total > 0;
        }

        input.addEventListener('input', apply);
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                apply();
                input.focus();
            });
        }
        apply();
    }

    wireSearch('mastersDeptSearch', 'mastersDeptClear', 'mastersDeptList', 'mastersDeptCount', 'mastersDeptNoMatch', 'mastersDeptEmpty', 'department');
    wireSearch('mastersDesigSearch', 'mastersDesigClear', 'mastersDesigList', 'mastersDesigCount', 'mastersDesigNoMatch', 'mastersDesigEmpty', 'designation');

    function closeAllMastersEdits() {
        document.querySelectorAll('.masters-edit-panel-wrap').forEach(function (panel) {
            panel.hidden = true;
        });
        document.querySelectorAll('.masters-edit-btn').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
            btn.classList.remove('is-active');
        });
        document.querySelectorAll('.masters-card.is-editing').forEach(function (card) {
            card.classList.remove('is-editing');
        });
    }

    document.querySelectorAll('.masters-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.masters-card');
            var panelId = btn.getAttribute('aria-controls');
            var panel = panelId ? document.getElementById(panelId) : null;
            if (!card || !panel) return;

            var willOpen = panel.hidden;
            closeAllMastersEdits();

            if (willOpen) {
                panel.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
                btn.classList.add('is-active');
                card.classList.add('is-editing');
            }
        });
    });
})();
</script>
<?php require 'includes/footer.php'; ?>
