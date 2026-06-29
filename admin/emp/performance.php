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
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <header class="emp-page-head">
        <h1>Performance self-review</h1>
        <p>Complete your self-assessment for active review cycles. Your manager will finalize ratings.</p>
    </header>

    <?php if ($reviews === []): ?>
        <div class="emp-panel">
            <p class="emp-muted">No performance reviews assigned yet. HR will open a cycle and generate reviews when ready.</p>
        </div>
    <?php else: ?>
        <div class="emp-performance-layout">
            <div class="emp-panel emp-performance-list">
                <h2>My reviews</h2>
                <ul class="emp-review-picker">
                    <?php foreach ($reviews as $r):
                        $st = $r['status'] ?? 'pending';
                    ?>
                    <li>
                        <a href="performance.php?review_id=<?php echo (int) $r['id']; ?>" class="emp-review-item<?php echo (int) $r['id'] === $selected_id ? ' is-active' : ''; ?>">
                            <strong><?php echo htmlspecialchars($r['cycle_name']); ?></strong>
                            <span><?php echo date('d M Y', strtotime($r['period_start'])); ?> – <?php echo date('d M Y', strtotime($r['period_end'])); ?></span>
                            <span class="emp-badge emp-badge-<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $st)); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($selected): ?>
            <div class="emp-panel emp-performance-detail">
                <h2><?php echo htmlspecialchars($selected['cycle_name']); ?></h2>
                <p class="emp-muted">Cycle: <?php echo htmlspecialchars($selected['cycle_status'] ?? ''); ?> · Status: <?php echo htmlspecialchars(str_replace('_', ' ', $selected['status'] ?? '')); ?></p>

                <?php if ($editable): ?>
                <form method="POST" action="performance_save.php" class="emp-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="review_id" value="<?php echo $selected_id; ?>">
                    <label>KRA / goals summary
                        <textarea name="kra_summary" rows="4" placeholder="What you achieved this period, key projects, outcomes…"><?php echo htmlspecialchars($selected['kra_summary'] ?? ''); ?></textarea>
                    </label>
                    <label>Self-review notes
                        <textarea name="employee_self_notes" rows="5" placeholder="Strengths, challenges, support needed, career goals…"><?php echo htmlspecialchars($selected['employee_self_notes'] ?? ''); ?></textarea>
                    </label>
                    <button type="submit" class="emp-btn emp-btn-primary">Submit self-review</button>
                </form>
                <?php else: ?>
                    <?php if (!empty($selected['kra_summary']) || !empty($selected['employee_self_notes'])): ?>
                        <?php if (!empty($selected['kra_summary'])): ?>
                            <h3>KRA summary</h3>
                            <p><?php echo nl2br(htmlspecialchars($selected['kra_summary'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($selected['employee_self_notes'])): ?>
                            <h3>Your self-review</h3>
                            <p><?php echo nl2br(htmlspecialchars($selected['employee_self_notes'])); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="emp-muted">Self-review window is closed or your manager has taken over this review.</p>
                    <?php endif; ?>
                    <?php if (!empty($selected['manager_notes']) || !empty($selected['overall_rating'])): ?>
                        <hr class="emp-divider">
                        <h3>Manager feedback</h3>
                        <?php if ($selected['overall_rating']): ?>
                            <p><strong>Rating:</strong> <?php echo htmlspecialchars((string) $selected['overall_rating']); ?> / 5</p>
                        <?php endif; ?>
                        <?php if (!empty($selected['manager_notes'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($selected['manager_notes'])); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
