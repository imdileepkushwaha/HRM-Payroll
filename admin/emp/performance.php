<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/hrm_modules_helper.php';

$reviews = get_employee_performance_reviews($conn, $employee['emp_id']);
$selected_id = (int) ($_GET['review_id'] ?? 0);
$selected = null;
foreach ($reviews as $r) {
    if ((int) $r['id'] === $selected_id) {
        $selected = $r;
        break;
    }
}
if (!$selected && $reviews !== []) {
    $selected = $reviews[0];
    $selected_id = (int) $selected['id'];
}

$editable = $selected && in_array($selected['status'] ?? '', ['pending', 'self_review'], true) && ($selected['cycle_status'] ?? '') !== 'closed';
$open_count = 0;
$completed_count = 0;
foreach ($reviews as $r) {
    $st = $r['status'] ?? '';
    if (in_array($st, ['pending', 'self_review'], true)) {
        $open_count++;
    } elseif (in_array($st, ['completed', 'approved', 'closed'], true)) {
        $completed_count++;
    }
}

$status_label = static function (string $st): string {
    return ucwords(str_replace('_', ' ', $st));
};
?>
<div class="emp-page emp-page-performance">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">People</p>
            <h2 class="emp-page-hero-title">Performance self-review</h2>
            <p>Reflect on your goals, document achievements and submit your self-assessment before your manager finalizes ratings.</p>
        </div>
        <?php if ($open_count > 0): ?>
            <span class="emp-hero-stat-pill emp-hero-stat-pill-warn"><?php echo $open_count; ?> action needed</span>
        <?php endif; ?>
    </div>

    <?php if ($reviews === []): ?>
        <section class="emp-card">
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg></span>
                <strong>No performance reviews yet</strong>
                <p>HR will open a review cycle and assign your self-assessment when ready.</p>
            </div>
        </section>
    <?php else: ?>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg></div>
            <div><span class="emp-dash-stat-label">Review cycles</span><strong class="emp-dash-stat-value"><?php echo count($reviews); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg></div>
            <div><span class="emp-dash-stat-label">Open</span><strong class="emp-dash-stat-value"><?php echo $open_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span class="emp-dash-stat-label">Completed</span><strong class="emp-dash-stat-value"><?php echo $completed_count; ?></strong></div>
        </div>
    </div>

    <div class="emp-section-grid emp-section-grid-perf">
        <aside class="emp-card emp-performance-list-card">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div><h3 class="emp-card-title">Select cycle</h3><p class="emp-reg-toolbar-sub">Pick a review to view or edit.</p></div>
            </header>
            <ul class="emp-perf-cycle-list">
                <?php foreach ($reviews as $r):
                    $st = $r['status'] ?? 'pending';
                    $is_active = (int) $r['id'] === $selected_id;
                ?>
                <li>
                    <a href="performance.php?review_id=<?php echo (int) $r['id']; ?>" class="emp-perf-cycle-card<?php echo $is_active ? ' is-active' : ''; ?>">
                        <span class="emp-perf-cycle-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg></span>
                        <div class="emp-perf-cycle-text">
                            <strong><?php echo htmlspecialchars($r['cycle_name']); ?></strong>
                            <span><?php echo date('d M Y', strtotime($r['period_start'])); ?> – <?php echo date('d M Y', strtotime($r['period_end'])); ?></span>
                        </div>
                        <span class="emp-req-status emp-req-<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($status_label($st)); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <?php if ($selected): ?>
        <section class="emp-card emp-performance-detail-card">
            <header class="emp-perf-detail-head">
                <div>
                    <h3 class="emp-card-title"><?php echo htmlspecialchars($selected['cycle_name']); ?></h3>
                    <p class="emp-reg-toolbar-sub"><?php echo date('d M Y', strtotime($selected['period_start'])); ?> – <?php echo date('d M Y', strtotime($selected['period_end'])); ?></p>
                </div>
                <div class="emp-perf-status-chips">
                    <span class="emp-perf-chip">Cycle: <?php echo htmlspecialchars($selected['cycle_status'] ?? '—'); ?></span>
                    <span class="emp-perf-chip emp-perf-chip-status"><?php echo htmlspecialchars($status_label($selected['status'] ?? '')); ?></span>
                </div>
            </header>

            <div class="emp-performance-detail-body">
                <?php if ($editable): ?>
                    <div class="emp-inline-alert emp-inline-alert-info">
                        <strong>Self-review open</strong>
                        <span>Complete both sections below and submit before the cycle closes.</span>
                    </div>
                    <form method="POST" action="performance_save.php" class="emp-request-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="review_id" value="<?php echo $selected_id; ?>">
                        <div class="emp-request-fields">
                            <div class="form-group emp-perf-field">
                                <label for="empPerfKra">KRA / goals summary</label>
                                <textarea id="empPerfKra" name="kra_summary" rows="4" placeholder="Key results, projects delivered, measurable outcomes…"><?php echo htmlspecialchars($selected['kra_summary'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group emp-perf-field">
                                <label for="empPerfNotes">Self-review notes</label>
                                <textarea id="empPerfNotes" name="employee_self_notes" rows="5" placeholder="Strengths, challenges, support needed, career goals…"><?php echo htmlspecialchars($selected['employee_self_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="emp-request-submit">
                            <button type="submit" class="btn btn-block">Submit self-review</button>
                        </div>
                    </form>
                <?php else: ?>
                    <?php if (!empty($selected['kra_summary'])): ?>
                        <div class="emp-perf-read-block">
                            <h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> KRA summary</h4>
                            <p><?php echo nl2br(htmlspecialchars($selected['kra_summary'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($selected['employee_self_notes'])): ?>
                        <div class="emp-perf-read-block">
                            <h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg> Your self-review</h4>
                            <p><?php echo nl2br(htmlspecialchars($selected['employee_self_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($selected['kra_summary']) && empty($selected['employee_self_notes'])): ?>
                        <div class="emp-inline-alert emp-inline-alert-info"><strong>Review closed</strong><span>Self-review window is closed or your manager has taken over.</span></div>
                    <?php endif; ?>
                    <?php if (!empty($selected['manager_notes']) || !empty($selected['overall_rating'])): ?>
                        <div class="emp-perf-manager-card">
                            <h4>Manager feedback</h4>
                            <?php if ($selected['overall_rating']): ?>
                                <div class="emp-perf-rating-stars" aria-label="Rating <?php echo (int) $selected['overall_rating']; ?> out of 5">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="emp-perf-star<?php echo $i <= (int) $selected['overall_rating'] ? ' is-filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                    <strong><?php echo (int) $selected['overall_rating']; ?>/5</strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($selected['manager_notes'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($selected['manager_notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
