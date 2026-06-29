<?php
require_once 'includes/admin_page_init.php';
admin_page_init('performance');
require_once 'includes/hrm_modules_helper.php';

$cycles = get_review_cycles($conn);
$cycle_id = (int) ($_GET['cycle_id'] ?? ($cycles[0]['id'] ?? 0));
$review_id = (int) ($_GET['review_id'] ?? 0);
$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$reviews = $cycle_id ? get_performance_reviews_for_cycle($conn, $cycle_id, $branch_id) : [];
$selected_review = null;
foreach ($reviews as $r) {
    if ((int) $r['id'] === $review_id) {
        $selected_review = $r;
        break;
    }
}
if (!$selected_review && $reviews !== []) {
    $selected_review = $reviews[0];
    $review_id = (int) $selected_review['id'];
}
$kpis = [];
if ($selected_review && !empty($selected_review['kpi_data'])) {
    $kpis = json_decode($selected_review['kpi_data'], true) ?: [];
}
if ($kpis === []) {
    $kpis = [['name' => '', 'target' => '', 'actual' => '', 'weight' => '1']];
}

$pending_reviews = 0;
$completed_reviews = 0;
$status_counts = ['pending' => 0, 'self_review' => 0, 'manager_review' => 0, 'completed' => 0];
foreach ($reviews as $r) {
    $st = $r['status'] ?? 'pending';
    if (isset($status_counts[$st])) {
        $status_counts[$st]++;
    }
    if ($st === 'completed') {
        $completed_reviews++;
    } else {
        $pending_reviews++;
    }
}

$active_cycle = null;
foreach ($cycles as $c) {
    if ((int) $c['id'] === $cycle_id) {
        $active_cycle = $c;
        break;
    }
}

$cycle_meta = [];
$draft_cycles = 0;
$active_cycles = 0;
$closed_cycles = 0;
foreach ($cycles as $c) {
    $cid = (int) $c['id'];
    $c_reviews = get_performance_reviews_for_cycle($conn, $cid, $branch_id);
    $cycle_meta[$cid] = ['count' => count($c_reviews), 'completed' => 0];
    foreach ($c_reviews as $cr) {
        if (($cr['status'] ?? '') === 'completed') {
            $cycle_meta[$cid]['completed']++;
        }
    }
    $cs = $c['status'] ?? 'draft';
    if ($cs === 'active') {
        $active_cycles++;
    } elseif ($cs === 'closed') {
        $closed_cycles++;
    } else {
        $draft_cycles++;
    }
}

$status_labels = [
    'pending' => 'Pending',
    'self_review' => 'Self review',
    'manager_review' => 'Manager review',
    'completed' => 'Completed',
];
$status_class = [
    'pending' => 'perf-status-pending',
    'self_review' => 'perf-status-self',
    'manager_review' => 'perf-status-mgr',
    'completed' => 'perf-status-done',
];

$workflow_step = 1;
if ($cycles !== []) {
    $workflow_step = 2;
}
if ($cycle_id && $reviews !== []) {
    $workflow_step = 3;
}
if ($cycle_id && $completed_reviews > 0) {
    $workflow_step = 4;
}
?>
<div class="hrm-page performance-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">People</p>
            <h2>Performance reviews</h2>
            <p>KRA/KPI cycles and manager ratings<?php echo $branch_id !== null ? ' · <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?>.</p>
        </div>
        <div class="page-header-actions">
            <?php if ($cycle_id): ?>
                <a href="performance_export.php?cycle_id=<?php echo $cycle_id; ?>" class="btn btn-outline">Export CSV</a>
            <?php else: ?>
                <a href="performance_export.php" class="btn btn-outline">Export CSV</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($cycles); ?></strong><span>Review cycles</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($reviews); ?></strong><span>Reviews in cycle</span></div></div>
        <div class="settings-status-chip warn"><span class="status-dot"></span><div><strong><?php echo $pending_reviews; ?></strong><span>In progress</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo $completed_reviews; ?></strong><span>Completed</span></div></div>
    </div>

    <nav class="performance-workflow" aria-label="Performance review workflow">
        <div class="performance-workflow-step<?php echo $workflow_step >= 1 ? ' is-done' : ''; ?><?php echo $workflow_step === 1 ? ' is-current' : ''; ?>">
            <span class="performance-workflow-num">1</span>
            <span class="performance-workflow-label">Create cycle</span>
        </div>
        <span class="performance-workflow-arrow" aria-hidden="true">→</span>
        <div class="performance-workflow-step<?php echo $workflow_step >= 2 ? ' is-done' : ''; ?><?php echo $workflow_step === 2 ? ' is-current' : ''; ?>">
            <span class="performance-workflow-num">2</span>
            <span class="performance-workflow-label">Generate reviews</span>
        </div>
        <span class="performance-workflow-arrow" aria-hidden="true">→</span>
        <div class="performance-workflow-step<?php echo $workflow_step >= 3 ? ' is-done' : ''; ?><?php echo $workflow_step === 3 ? ' is-current' : ''; ?>">
            <span class="performance-workflow-num">3</span>
            <span class="performance-workflow-label">Rate &amp; document</span>
        </div>
        <span class="performance-workflow-arrow" aria-hidden="true">→</span>
        <div class="performance-workflow-step<?php echo $workflow_step >= 4 ? ' is-done' : ''; ?><?php echo $workflow_step === 4 ? ' is-current' : ''; ?>">
            <span class="performance-workflow-num">4</span>
            <span class="performance-workflow-label">Close cycle</span>
        </div>
    </nav>

    <div class="performance-layout">
        <section class="panel panel-elevated performance-cycles-panel">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Review cycles</h3>
                    <span class="panel-badge" id="perfCycleCount"><?php echo count($cycles); ?> cycle<?php echo count($cycles) === 1 ? '' : 's'; ?></span>
                </div>
                <?php if ($cycles !== []): ?>
                <div class="masters-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="perfCycleSearch" placeholder="Search cycles…" autocomplete="off" aria-label="Search cycles">
                    <button type="button" class="masters-search-clear" id="perfCycleClear" hidden aria-label="Clear">&times;</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="panel-body padded">
                <div class="performance-cycles-split">
                    <div class="settings-add-panel masters-add-panel performance-add-cycle">
                        <div class="settings-add-panel-head">
                            <span class="settings-add-panel-icon performance-cycle-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                            </span>
                            <div class="settings-add-panel-head-text">
                                <h4>New review cycle</h4>
                                <p>Period dates — set <strong>Active</strong> to enable reviews.</p>
                            </div>
                        </div>
                        <form method="POST" action="performance_save.php" class="settings-add-panel-body">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="performance_action" value="cycle">
                            <div class="performance-add-grid">
                                <div class="form-group performance-field-wide">
                                    <label for="cycle_name">Cycle name</label>
                                    <input type="text" name="name" id="cycle_name" required placeholder="H1 2026" class="settings-add-input">
                                </div>
                                <div class="form-group"><label for="cycle_start">Start date</label><input type="date" name="period_start" id="cycle_start" required class="settings-add-input"></div>
                                <div class="form-group"><label for="cycle_end">End date</label><input type="date" name="period_end" id="cycle_end" required class="settings-add-input"></div>
                                <div class="form-group"><label for="cycle_status">Status</label>
                                    <select name="status" id="cycle_status" class="settings-add-select">
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="settings-add-panel-foot">
                                <button type="submit" class="btn btn-header settings-add-submit">Create cycle</button>
                            </div>
                        </form>
                    </div>

                    <div class="performance-cycles-side">
                        <?php if ($cycles === []): ?>
                            <div class="masters-empty performance-cycles-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                                <h4>No cycles yet</h4>
                                <p>Use the form on the left to create your first review period.</p>
                            </div>
                        <?php else: ?>
                            <div class="performance-filter-pills" role="group" aria-label="Filter cycles">
                                <button type="button" class="performance-filter-pill is-active" data-cycle-filter="all">All <span class="performance-filter-count"><?php echo count($cycles); ?></span></button>
                                <button type="button" class="performance-filter-pill" data-cycle-filter="active">Active <span class="performance-filter-count"><?php echo $active_cycles; ?></span></button>
                                <button type="button" class="performance-filter-pill" data-cycle-filter="draft">Draft <span class="performance-filter-count"><?php echo $draft_cycles; ?></span></button>
                                <button type="button" class="performance-filter-pill" data-cycle-filter="closed">Closed <span class="performance-filter-count"><?php echo $closed_cycles; ?></span></button>
                            </div>

                            <ul class="performance-cycle-list" id="perfCycleList">
                                <?php foreach ($cycles as $c):
                                    $cid = (int) $c['id'];
                                    $is_active = $cid === $cycle_id;
                                    $c_status = $c['status'] ?? 'draft';
                                    $meta = $cycle_meta[$cid] ?? ['count' => 0, 'completed' => 0];
                                    $search = strtolower($c['name'] . ' ' . $c_status);
                                ?>
                                <li>
                                    <a href="performance.php?cycle_id=<?php echo $cid; ?>#reviews"
                                       class="performance-cycle-card<?php echo $is_active ? ' is-active' : ''; ?>"
                                       data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                                       data-status="<?php echo htmlspecialchars($c_status, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="performance-cycle-main">
                                            <strong><?php echo htmlspecialchars($c['name']); ?></strong>
                                            <span><?php echo date('d M Y', strtotime($c['period_start'])); ?> → <?php echo date('d M Y', strtotime($c['period_end'])); ?></span>
                                            <span class="performance-cycle-stats"><?php echo $meta['count']; ?> review<?php echo $meta['count'] === 1 ? '' : 's'; ?> · <?php echo $meta['completed']; ?> done</span>
                                        </div>
                                        <div class="performance-cycle-side">
                                            <span class="badge <?php echo $c_status === 'active' ? 'badge-present' : ($c_status === 'closed' ? 'badge-neutral' : 'badge-absent'); ?>"><?php echo htmlspecialchars($c_status); ?></span>
                                            <span class="performance-cycle-hint">Open reviews →</span>
                                        </div>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="masters-empty masters-search-empty" id="perfCycleNoMatch" hidden><h4>No matches</h4><p>Try a different search or filter.</p></div>

                            <?php if ($cycle_id && $active_cycle): ?>
                            <div class="performance-generate-form">
                                <div class="performance-generate-copy">
                                    <strong>Generate employee reviews</strong>
                                    <p>Creates a review record for each active employee<?php echo $branch_id !== null ? ' in this branch' : ''; ?>. Safe to run again — skips existing records.</p>
                                </div>
                                <form method="POST" action="performance_save.php" class="performance-generate-action">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="performance_action" value="generate">
                                    <input type="hidden" name="cycle_id" value="<?php echo $cycle_id; ?>">
                                    <button type="submit" class="btn btn-header btn-sm">Generate reviews</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel panel-elevated performance-reviews-panel" id="reviews">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3><?php echo $active_cycle ? htmlspecialchars($active_cycle['name']) : 'Employee reviews'; ?></h3>
                    <?php if ($cycle_id): ?>
                        <span class="panel-badge" id="perfReviewCount"><?php echo count($reviews); ?> review<?php echo count($reviews) === 1 ? '' : 's'; ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($cycle_id && $reviews !== []): ?>
                <div class="masters-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="perfReviewSearch" placeholder="Search employee…" autocomplete="off" aria-label="Search reviews">
                    <button type="button" class="masters-search-clear" id="perfReviewClear" hidden aria-label="Clear">&times;</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="panel-body padded">
                <?php if (!$cycle_id): ?>
                    <div class="masters-empty performance-reviews-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <h4>Select a review cycle</h4>
                        <p>Pick a cycle above to view and complete employee performance reviews.</p>
                    </div>
                <?php elseif ($reviews === []): ?>
                    <div class="masters-empty performance-reviews-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                        <h4>No reviews yet</h4>
                        <p>Click <strong>Generate reviews</strong> in the cycle panel to create records for active employees.</p>
                    </div>
                <?php else: ?>
                    <?php if ($active_cycle): ?>
                    <div class="performance-cycle-meta">
                        <span class="badge <?php echo ($active_cycle['status'] ?? '') === 'active' ? 'badge-present' : 'badge-neutral'; ?>"><?php echo htmlspecialchars($active_cycle['status'] ?? 'draft'); ?></span>
                        <span><?php echo date('d M Y', strtotime($active_cycle['period_start'])); ?> – <?php echo date('d M Y', strtotime($active_cycle['period_end'])); ?></span>
                        <span><?php echo $completed_reviews; ?> of <?php echo count($reviews); ?> completed</span>
                    </div>
                    <?php endif; ?>

                    <div class="performance-status-strip" role="group" aria-label="Filter by status">
                        <button type="button" class="performance-status-pill is-active" data-status-filter="all">All <span class="performance-filter-count"><?php echo count($reviews); ?></span></button>
                        <?php foreach ($status_labels as $val => $label): ?>
                            <button type="button" class="performance-status-pill <?php echo $status_class[$val]; ?>" data-status-filter="<?php echo $val; ?>">
                                <?php echo $label; ?> <span class="performance-filter-count"><?php echo $status_counts[$val]; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="performance-reviews-split">
                        <div class="performance-reviews-side">
                            <ul class="performance-review-picker" id="perfReviewList">
                                <?php foreach ($reviews as $r):
                                    $st = $r['status'] ?? 'pending';
                                    $search = strtolower($r['emp_name'] . ' ' . ($r['department'] ?? '') . ' ' . $st);
                                ?>
                                <li data-status="<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                                    <a href="performance.php?cycle_id=<?php echo $cycle_id; ?>&review_id=<?php echo (int) $r['id']; ?>#review-detail"
                                       class="performance-review-item<?php echo (int) $r['id'] === $review_id ? ' is-active' : ''; ?>">
                                        <span class="perf-review-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($r['emp_name'], 0, 1))); ?></span>
                                        <span class="perf-review-info">
                                            <strong><?php echo htmlspecialchars($r['emp_name']); ?></strong>
                                            <span><?php echo htmlspecialchars($r['department'] ?? '—'); ?></span>
                                        </span>
                                        <span class="performance-status-badge <?php echo $status_class[$st] ?? ''; ?>"><?php echo htmlspecialchars($status_labels[$st] ?? $st); ?></span>
                                        <?php if ($r['overall_rating']): ?>
                                            <span class="performance-rating-mini"><?php echo htmlspecialchars((string) $r['overall_rating']); ?>/5</span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="masters-empty masters-search-empty" id="perfReviewNoMatch" hidden><h4>No matches</h4><p>Try a different name or status filter.</p></div>
                        </div>

                    <?php if ($selected_review): ?>
                    <div class="performance-review-detail" id="review-detail">
                        <div class="performance-review-detail-head">
                            <h4><?php echo htmlspecialchars($selected_review['emp_name']); ?></h4>
                            <div class="performance-review-detail-meta">
                                <p class="performance-review-sub">
                                    <span class="performance-review-dept"><?php echo htmlspecialchars($selected_review['department'] ?? '—'); ?></span>
                                    <a href="employee_view.php?emp_id=<?php echo urlencode($selected_review['emp_id']); ?>" class="performance-review-link">View employee</a>
                                    <?php if (!empty($selected_review['reviewer_name'])): ?>
                                        <span class="performance-review-reviewer">Reviewer: <?php echo htmlspecialchars($selected_review['reviewer_name']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($selected_review['overall_rating']): ?>
                                    <span class="performance-rating-chip"><?php echo htmlspecialchars((string) $selected_review['overall_rating']); ?> / 5</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" action="performance_save.php" class="performance-review-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="performance_action" value="review">
                            <input type="hidden" name="cycle_id" value="<?php echo $cycle_id; ?>">
                            <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">

                            <div class="form-group">
                                <label for="kra_summary">KRA summary</label>
                                <textarea name="kra_summary" id="kra_summary" rows="3" class="settings-add-input" placeholder="Key result areas, goals achieved, and highlights for this period…"><?php echo htmlspecialchars($selected_review['kra_summary'] ?? ''); ?></textarea>
                            </div>

                            <?php if (!empty($selected_review['employee_self_notes'])): ?>
                            <div class="form-group performance-self-notes-readonly">
                                <label>Employee self-review</label>
                                <div class="performance-self-notes-box"><?php echo nl2br(htmlspecialchars($selected_review['employee_self_notes'])); ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="performance-kpi-block">
                                <div class="performance-kpi-head">
                                    <div>
                                        <h5>KPIs</h5>
                                        <span class="text-muted">Target vs actual — weight used for scoring</span>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-sm" id="perfAddKpi">+ Add KPI row</button>
                                </div>
                                <div class="performance-kpi-labels" aria-hidden="true">
                                    <span>KPI name</span>
                                    <span>Target</span>
                                    <span>Actual</span>
                                    <span>Weight</span>
                                </div>
                                <div class="performance-kpi-rows" id="perfKpiRows">
                                    <?php foreach ($kpis as $kpi): ?>
                                    <div class="performance-kpi-row">
                                        <input type="text" name="kpi_name[]" value="<?php echo htmlspecialchars($kpi['name'] ?? ''); ?>" placeholder="e.g. Sales target" class="settings-add-input">
                                        <input type="number" name="kpi_target[]" value="<?php echo htmlspecialchars((string) ($kpi['target'] ?? '')); ?>" placeholder="100" step="0.01" class="settings-add-input">
                                        <input type="number" name="kpi_actual[]" value="<?php echo htmlspecialchars((string) ($kpi['actual'] ?? '')); ?>" placeholder="95" step="0.01" class="settings-add-input">
                                        <input type="number" name="kpi_weight[]" value="<?php echo htmlspecialchars((string) ($kpi['weight'] ?? '1')); ?>" placeholder="1" step="0.1" min="0" class="settings-add-input kpi-weight">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="manager_notes">Manager notes</label>
                                <textarea name="manager_notes" id="manager_notes" rows="3" class="settings-add-input" placeholder="Strengths, areas to improve, development plan…"><?php echo htmlspecialchars($selected_review['manager_notes'] ?? ''); ?></textarea>
                            </div>

                            <div class="performance-form-row">
                                <div class="form-group">
                                    <label for="overall_rating">Overall rating (1–5)</label>
                                    <input type="number" name="overall_rating" id="overall_rating" min="1" max="5" step="0.1" value="<?php echo htmlspecialchars((string) ($selected_review['overall_rating'] ?? '')); ?>" class="settings-add-input" placeholder="4.5">
                                </div>
                                <div class="form-group">
                                    <label for="review_status">Review status</label>
                                    <select name="status" id="review_status" class="settings-add-select">
                                        <?php foreach ($status_labels as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php echo ($selected_review['status'] ?? '') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="performance-form-actions">
                                <button type="submit" class="btn btn-header">Save review</button>
                                <span class="performance-save-hint">Changes apply to this employee for the selected cycle.</span>
                            </div>
                        </form>
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
    var cycleFilter = 'all';
    var cycleInput = document.getElementById('perfCycleSearch');
    var cycleList = document.getElementById('perfCycleList');

    function applyCycleFilters() {
        if (!cycleList) return;
        var q = cycleInput ? cycleInput.value.trim().toLowerCase() : '';
        var badge = document.getElementById('perfCycleCount');
        var noMatch = document.getElementById('perfCycleNoMatch');
        var clearBtn = document.getElementById('perfCycleClear');
        var items = cycleList.querySelectorAll('.performance-cycle-card');
        var visible = 0;
        items.forEach(function (el) {
            var matchSearch = q === '' || (el.getAttribute('data-search') || '').indexOf(q) !== -1;
            var matchStatus = cycleFilter === 'all' || el.getAttribute('data-status') === cycleFilter;
            var show = matchSearch && matchStatus;
            el.closest('li').style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (badge) badge.textContent = visible + ' shown' + (q || cycleFilter !== 'all' ? ' · filtered' : '');
        if (clearBtn) clearBtn.hidden = q === '';
        if (noMatch) noMatch.hidden = visible > 0;
        cycleList.hidden = visible === 0 && items.length > 0;
    }

    if (cycleInput) {
        cycleInput.addEventListener('input', applyCycleFilters);
        var cycleClear = document.getElementById('perfCycleClear');
        if (cycleClear) cycleClear.addEventListener('click', function () { cycleInput.value = ''; applyCycleFilters(); cycleInput.focus(); });
    }

    document.querySelectorAll('[data-cycle-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-cycle-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            cycleFilter = btn.getAttribute('data-cycle-filter') || 'all';
            applyCycleFilters();
        });
    });

    var statusFilter = 'all';
    var reviewInput = document.getElementById('perfReviewSearch');
    var reviewList = document.getElementById('perfReviewList');

    function applyReviewFilters() {
        if (!reviewList) return;
        var q = reviewInput ? reviewInput.value.trim().toLowerCase() : '';
        var noMatch = document.getElementById('perfReviewNoMatch');
        var clearBtn = document.getElementById('perfReviewClear');
        var badge = document.getElementById('perfReviewCount');
        var items = reviewList.querySelectorAll('li');
        var visible = 0;
        items.forEach(function (el) {
            var matchSearch = q === '' || (el.getAttribute('data-search') || '').indexOf(q) !== -1;
            var matchStatus = statusFilter === 'all' || el.getAttribute('data-status') === statusFilter;
            var show = matchSearch && matchStatus;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (badge) badge.textContent = visible + ' shown' + (q || statusFilter !== 'all' ? ' · filtered' : '');
        if (clearBtn) clearBtn.hidden = q === '';
        if (noMatch) noMatch.hidden = visible > 0;
        reviewList.hidden = visible === 0 && items.length > 0;
    }

    if (reviewInput) {
        reviewInput.addEventListener('input', applyReviewFilters);
        var reviewClear = document.getElementById('perfReviewClear');
        if (reviewClear) reviewClear.addEventListener('click', function () { reviewInput.value = ''; applyReviewFilters(); reviewInput.focus(); });
    }

    document.querySelectorAll('[data-status-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-status-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            statusFilter = btn.getAttribute('data-status-filter') || 'all';
            applyReviewFilters();
        });
    });

    var addKpi = document.getElementById('perfAddKpi');
    var kpiRows = document.getElementById('perfKpiRows');
    if (addKpi && kpiRows) {
        addKpi.addEventListener('click', function () {
            var row = kpiRows.querySelector('.performance-kpi-row');
            if (!row) return;
            var clone = row.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (inp) { inp.value = inp.classList.contains('kpi-weight') ? '1' : ''; });
            kpiRows.appendChild(clone);
        });
    }

    if (window.location.hash === '#reviews' || window.location.hash === '#review-detail') {
        var target = document.querySelector(window.location.hash);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
<?php require 'includes/footer.php'; ?>
