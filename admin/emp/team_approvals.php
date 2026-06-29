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
?>
<div class="emp-page emp-page-team-approvals">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">My team</p>
            <h2 class="emp-page-hero-title">Team approvals</h2>
            <p>Review and approve leave, WFH and punch regularization requests from your direct reports. <a href="team.php">Back to my team</a></p>
        </div>
        <?php if ($total_pending > 0): ?>
            <span class="emp-page-lock-badge"><?php echo $total_pending; ?> pending</span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg></div>
            <div><span class="emp-dash-stat-label">Leave</span><strong class="emp-dash-stat-value"><?php echo $leave_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div>
            <div><span class="emp-dash-stat-label">WFH</span><strong class="emp-dash-stat-value"><?php echo $wfh_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><span class="emp-dash-stat-label">Regularization</span><strong class="emp-dash-stat-value"><?php echo $reg_count; ?></strong></div>
        </div>
    </div>

    <?php if ($total_pending === 0): ?>
        <section class="emp-card emp-reg-history-card">
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                <strong>All caught up</strong>
                <p>No pending requests from your team right now.</p>
            </div>
        </section>
    <?php else: ?>

        <?php if ($pending['leave'] !== []): ?>
        <section class="emp-card emp-approval-section">
            <header class="emp-card-toolbar emp-reg-toolbar"><div><h3 class="emp-card-title">Leave requests</h3><p class="emp-reg-toolbar-sub"><?php echo $leave_count; ?> pending</p></div></header>
            <div class="emp-approval-list">
                <?php foreach ($pending['leave'] as $r): ?>
                <article class="emp-approval-card">
                    <div class="emp-approval-card-main">
                        <strong><?php echo htmlspecialchars($r['employee_name']); ?></strong>
                        <span class="emp-approval-meta"><?php echo htmlspecialchars($r['leave_type']); ?> · <?php echo date('d M', strtotime($r['from_date'])); ?> – <?php echo date('d M Y', strtotime($r['to_date'])); ?></span>
                        <?php if (!empty($r['employee_note'])): ?><p class="emp-reg-note"><?php echo nl2br(htmlspecialchars($r['employee_note'])); ?></p><?php endif; ?>
                    </div>
                    <form method="POST" action="team_approval_save.php" class="emp-approval-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="type" value="leave">
                        <input type="hidden" name="request_id" value="<?php echo (int) $r['id']; ?>">
                        <input type="text" name="review_note" placeholder="Note (optional)" class="emp-approval-note-input">
                        <div class="emp-approval-actions">
                            <button type="submit" name="action" value="approve" class="btn btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline">Reject</button>
                        </div>
                    </form>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($pending['wfh'] !== []): ?>
        <section class="emp-card emp-approval-section">
            <header class="emp-card-toolbar emp-reg-toolbar"><div><h3 class="emp-card-title">WFH requests</h3><p class="emp-reg-toolbar-sub"><?php echo $wfh_count; ?> pending</p></div></header>
            <div class="emp-approval-list">
                <?php foreach ($pending['wfh'] as $r): ?>
                <article class="emp-approval-card">
                    <div class="emp-approval-card-main">
                        <strong><?php echo htmlspecialchars($r['employee_name']); ?></strong>
                        <span class="emp-approval-meta"><?php echo date('D, d M Y', strtotime($r['wfh_date'])); ?></span>
                        <?php if (!empty($r['employee_note'])): ?><p class="emp-reg-note"><?php echo nl2br(htmlspecialchars($r['employee_note'])); ?></p><?php endif; ?>
                    </div>
                    <form method="POST" action="team_approval_save.php" class="emp-approval-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="type" value="wfh">
                        <input type="hidden" name="request_id" value="<?php echo (int) $r['id']; ?>">
                        <div class="emp-approval-actions">
                            <button type="submit" name="action" value="approve" class="btn btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline">Reject</button>
                        </div>
                    </form>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($pending['regularization'] !== []): ?>
        <section class="emp-card emp-approval-section">
            <header class="emp-card-toolbar emp-reg-toolbar"><div><h3 class="emp-card-title">Punch regularization</h3><p class="emp-reg-toolbar-sub"><?php echo $reg_count; ?> pending</p></div></header>
            <div class="emp-approval-list">
                <?php foreach ($pending['regularization'] as $r): ?>
                <article class="emp-approval-card">
                    <div class="emp-approval-card-main">
                        <strong><?php echo htmlspecialchars($r['employee_name']); ?></strong>
                        <span class="emp-approval-meta"><?php echo date('D, d M Y', strtotime($r['punch_date'])); ?></span>
                        <div class="emp-reg-time-row">
                            <span class="emp-reg-time-chip">In <?php echo $format_time($r['requested_in_time'] ?? null); ?></span>
                            <span class="emp-reg-time-chip">Out <?php echo $format_time($r['requested_out_time'] ?? null); ?></span>
                        </div>
                        <?php if (!empty($r['employee_note'])): ?><p class="emp-reg-note"><?php echo nl2br(htmlspecialchars($r['employee_note'])); ?></p><?php endif; ?>
                    </div>
                    <form method="POST" action="team_approval_save.php" class="emp-approval-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="type" value="regularization">
                        <input type="hidden" name="request_id" value="<?php echo (int) $r['id']; ?>">
                        <div class="emp-approval-actions">
                            <button type="submit" name="action" value="approve" class="btn btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline">Reject</button>
                        </div>
                    </form>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
