<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
if (!isset($conn)) {
    require __DIR__ . '/../config.php';
}
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$employee = require_logged_in_employee($conn);
if (!employee_is_manager($conn, $employee['emp_id'])) {
    $_SESSION['emp_flash_message'] = 'Team approvals are only for employees with direct reports.';
    $_SESSION['emp_flash_success'] = false;
    header('Location: team.php');
    exit;
}
require __DIR__ . '/includes/header.php';

$pending = get_manager_team_pending_items($conn, $employee['emp_id']);
$leave_count = count($pending['leave']);
$wfh_count = count($pending['wfh']);
$reg_count = count($pending['regularization']);
$total_pending = $leave_count + $wfh_count + $reg_count;

$format_time = static function (?string $time): string {
    if ($time === null || trim($time) === '') {
        return '—';
    }
    $ts = strtotime($time);
    return $ts ? date('g:i A', $ts) : htmlspecialchars($time);
};

$render_queue = static function (array $items, string $type, string $accent) use ($format_time): void {
    foreach ($items as $r):
        $initial = strtoupper(substr($r['employee_name'] ?? '?', 0, 1));
?>
        <article class="emp-queue-card emp-queue-card--<?php echo htmlspecialchars($accent); ?>">
            <div class="emp-queue-card-head">
                <span class="emp-avatar emp-avatar-sm" aria-hidden="true"><?php echo $initial; ?></span>
                <div class="emp-queue-card-who">
                    <strong><?php echo htmlspecialchars($r['employee_name']); ?></strong>
                    <?php if ($type === 'leave'): ?>
                        <span><?php echo htmlspecialchars($r['leave_type']); ?> · <?php echo date('d M', strtotime($r['from_date'])); ?> – <?php echo date('d M Y', strtotime($r['to_date'])); ?></span>
                    <?php elseif ($type === 'wfh'): ?>
                        <span>WFH · <?php echo date('D, d M Y', strtotime($r['wfh_date'])); ?></span>
                    <?php else: ?>
                        <span>Punch · <?php echo date('D, d M Y', strtotime($r['punch_date'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($type === 'regularization'): ?>
            <div class="emp-reg-time-row">
                <span class="emp-reg-time-chip">In <?php echo $format_time($r['requested_in_time'] ?? null); ?></span>
                <span class="emp-reg-time-chip">Out <?php echo $format_time($r['requested_out_time'] ?? null); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($r['employee_note'])): ?>
                <p class="emp-queue-note"><?php echo nl2br(htmlspecialchars($r['employee_note'])); ?></p>
            <?php endif; ?>

            <form method="POST" action="team_approval_save.php" class="emp-queue-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <input type="hidden" name="request_id" value="<?php echo (int) $r['id']; ?>">
                <?php if ($type === 'leave'): ?>
                    <input type="text" name="review_note" placeholder="Optional note to employee" class="emp-approval-note-input">
                <?php endif; ?>
                <div class="emp-queue-actions">
                    <button type="submit" name="action" value="approve" class="btn btn-sm emp-btn-approve">Approve</button>
                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline emp-btn-reject">Reject</button>
                </div>
            </form>
        </article>
<?php
    endforeach;
};
?>
<div class="emp-page emp-page-team-approvals">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">My team</p>
            <h2 class="emp-page-hero-title">Team approvals</h2>
            <p>Review leave, WFH and punch regularization from your direct reports. <a href="team.php">← Back to my team</a></p>
        </div>
        <?php if ($total_pending > 0): ?>
            <span class="emp-hero-stat-pill emp-hero-stat-pill-warn"><?php echo $total_pending; ?> pending</span>
        <?php else: ?>
            <span class="emp-hero-stat-pill">All clear</span>
        <?php endif; ?>
    </div>

    <div class="emp-approval-tabs" role="tablist" aria-label="Request types">
        <span class="emp-approval-tab<?php echo $leave_count > 0 ? ' has-count' : ''; ?>">Leave <strong><?php echo $leave_count; ?></strong></span>
        <span class="emp-approval-tab<?php echo $wfh_count > 0 ? ' has-count' : ''; ?>">WFH <strong><?php echo $wfh_count; ?></strong></span>
        <span class="emp-approval-tab<?php echo $reg_count > 0 ? ' has-count' : ''; ?>">Regularization <strong><?php echo $reg_count; ?></strong></span>
    </div>

    <?php if ($total_pending === 0): ?>
        <section class="emp-card">
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                <strong>You're all caught up</strong>
                <p>No pending requests from your team. Check back later or view the <a href="calendar.php">team calendar</a>.</p>
            </div>
        </section>
    <?php else: ?>

        <?php if ($pending['leave'] !== []): ?>
        <section class="emp-card emp-queue-section">
            <header class="emp-queue-section-head emp-queue-section-head--leave">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                <div><h3>Leave requests</h3><span><?php echo $leave_count; ?> awaiting your decision</span></div>
            </header>
            <div class="emp-queue-list"><?php $render_queue($pending['leave'], 'leave', 'leave'); ?></div>
        </section>
        <?php endif; ?>

        <?php if ($pending['wfh'] !== []): ?>
        <section class="emp-card emp-queue-section">
            <header class="emp-queue-section-head emp-queue-section-head--wfh">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                <div><h3>WFH requests</h3><span><?php echo $wfh_count; ?> awaiting your decision</span></div>
            </header>
            <div class="emp-queue-list"><?php $render_queue($pending['wfh'], 'wfh', 'wfh'); ?></div>
        </section>
        <?php endif; ?>

        <?php if ($pending['regularization'] !== []): ?>
        <section class="emp-card emp-queue-section">
            <header class="emp-queue-section-head emp-queue-section-head--reg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <div><h3>Punch regularization</h3><span><?php echo $reg_count; ?> awaiting your decision</span></div>
            </header>
            <div class="emp-queue-list"><?php $render_queue($pending['regularization'], 'regularization', 'reg'); ?></div>
        </section>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
