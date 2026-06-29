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
foreach ($reviews as $r) {
    if (in_array($r['status'] ?? '', ['pending', 'self_review'], true)) {
        $open_count++;
    }
}
?>
<div class="emp-page emp-page-performance">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">People</p>
            <h2 class="emp-page-hero-title">Performance self-review</h2>
            <p>Complete your self-assessment for active review cycles. Your manager will finalize ratings and feedback.</p>
        </div>
        <?php if ($open_count > 0): ?>
            <span class="emp-page-lock-badge"><?php echo $open_count; ?> open</span>
        <?php endif; ?>
    </div>

    <?php if ($reviews === []): ?>
        <section class="emp-card emp-reg-history-card">
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg></span>
                <strong>No performance reviews yet</strong>
                <p>HR will open a review cycle and assign your self-assessment when ready.</p>
            </div>
        </section>
    <?php else: ?>
        <div class="emp-section-grid emp-section-grid-perf">
            <aside class="emp-card emp-performance-list-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div><h3 class="emp-card-title">My reviews</h3><p class="emp-reg-toolbar-sub"><?php echo count($reviews); ?> cycle<?php echo count($reviews) === 1 ? '' : 's'; ?></p></div>
                </header>
                <ul class="emp-review-picker">
                    <?php foreach ($reviews as $r):
                        $st = $r['status'] ?? 'pending';
                    ?>
                    <li>
                        <a href="performance.php?review_id=<?php echo (int) $r['id']; ?>" class="emp-review-item<?php echo (int) $r['id'] === $selected_id ? ' is-active' : ''; ?>">
                            <strong><?php echo htmlspecialchars($r['cycle_name']); ?></strong>
                            <span><?php echo date('d M Y', strtotime($r['period_start'])); ?> – <?php echo date('d M Y', strtotime($r['period_end'])); ?></span>
                            <span class="emp-req-status emp-req-<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $st)); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </aside>

            <?php if ($selected): ?>
            <section class="emp-card emp-performance-detail-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div>
                        <h3 class="emp-card-title"><?php echo htmlspecialchars($selected['cycle_name']); ?></h3>
                        <p class="emp-reg-toolbar-sub">Cycle: <?php echo htmlspecialchars($selected['cycle_status'] ?? ''); ?> · <?php echo htmlspecialchars(str_replace('_', ' ', $selected['status'] ?? '')); ?></p>
                    </div>
                </header>

                <div class="emp-performance-detail-body">
                    <?php if ($editable): ?>
                    <form method="POST" action="performance_save.php" class="emp-request-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="review_id" value="<?php echo $selected_id; ?>">
                        <div class="emp-request-fields">
                            <div class="form-group">
                                <label for="empPerfKra">KRA / goals summary</label>
                                <textarea id="empPerfKra" name="kra_summary" rows="4" placeholder="What you achieved this period, key projects, outcomes…"><?php echo htmlspecialchars($selected['kra_summary'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="empPerfNotes">Self-review notes</label>
                                <textarea id="empPerfNotes" name="employee_self_notes" rows="5" placeholder="Strengths, challenges, support needed, career goals…"><?php echo htmlspecialchars($selected['employee_self_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="emp-request-submit">
                            <button type="submit" class="btn btn-block">Submit self-review</button>
                        </div>
                    </form>
                    <?php else: ?>
                        <?php if (!empty($selected['kra_summary']) || !empty($selected['employee_self_notes'])): ?>
                            <?php if (!empty($selected['kra_summary'])): ?>
                                <div class="emp-perf-block"><h4>KRA summary</h4><p><?php echo nl2br(htmlspecialchars($selected['kra_summary'])); ?></p></div>
                            <?php endif; ?>
                            <?php if (!empty($selected['employee_self_notes'])): ?>
                                <div class="emp-perf-block"><h4>Your self-review</h4><p><?php echo nl2br(htmlspecialchars($selected['employee_self_notes'])); ?></p></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="emp-inline-alert emp-inline-alert-info"><strong>Review closed</strong><span>Self-review window is closed or your manager has taken over.</span></div>
                        <?php endif; ?>
                        <?php if (!empty($selected['manager_notes']) || !empty($selected['overall_rating'])): ?>
                            <div class="emp-perf-block emp-perf-manager">
                                <h4>Manager feedback</h4>
                                <?php if ($selected['overall_rating']): ?>
                                    <p class="emp-perf-rating"><strong>Rating:</strong> <?php echo htmlspecialchars((string) $selected['overall_rating']); ?> / 5</p>
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
