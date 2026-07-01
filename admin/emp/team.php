<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$manager = get_employee_manager($conn, $employee['emp_id']);
$reports = get_employee_direct_reports($conn, $employee['emp_id']);
$is_mgr = employee_is_manager($conn, $employee['emp_id']);

if ($is_mgr) {
    $pending = get_manager_team_pending_items($conn, $employee['emp_id']);
    $pending_total = count($pending['leave']) + count($pending['wfh']) + count($pending['regularization']);
} else {
    $pending_total = 0;
}
?>
<div class="emp-page emp-page-team">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">People</p>
            <h2 class="emp-page-hero-title">My team</h2>
            <p>Your reporting line and direct reports. Managers can approve requests and view the team calendar.</p>
        </div>
        <?php if ($is_mgr): ?>
            <span class="emp-hero-stat-pill"><?php echo count($reports); ?> direct report<?php echo count($reports) === 1 ? '' : 's'; ?></span>
        <?php endif; ?>
    </div>

    <?php if ($is_mgr): ?>
    <div class="emp-team-quick-grid">
        <a href="team_approvals.php" class="emp-team-quick-card<?php echo $pending_total > 0 ? ' has-alert' : ''; ?>">
            <span class="emp-team-quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
            <div>
                <strong>Team approvals</strong>
                <span><?php echo $pending_total > 0 ? $pending_total . ' pending request' . ($pending_total === 1 ? '' : 's') : 'All caught up'; ?></span>
            </div>
            <?php if ($pending_total > 0): ?><span class="emp-team-quick-badge"><?php echo $pending_total; ?></span><?php endif; ?>
        </a>
        <a href="calendar.php" class="emp-team-quick-card">
            <span class="emp-team-quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg></span>
            <div><strong>Team calendar</strong><span>Holidays &amp; leave overview</span></div>
        </a>
    </div>
    <?php endif; ?>

    <div class="emp-section-grid emp-section-grid-team">
        <section class="emp-card emp-team-manager-panel">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div><h3 class="emp-card-title">Reporting manager</h3><p class="emp-reg-toolbar-sub">Your line manager for escalations and approvals.</p></div>
            </header>
            <?php if ($manager): ?>
                <div class="emp-team-manager-hero">
                    <span class="emp-avatar emp-avatar-xl emp-team-manager-avatar" aria-hidden="true"><?php echo strtoupper(substr($manager['name'], 0, 1)); ?></span>
                    <div class="emp-team-manager-info">
                        <strong><?php echo htmlspecialchars($manager['name']); ?></strong>
                        <span class="emp-team-manager-role"><?php echo htmlspecialchars($manager['designation'] ?: 'Manager'); ?><?php if (!empty($manager['department'])): ?> · <?php echo htmlspecialchars($manager['department']); ?><?php endif; ?></span>
                        <div class="emp-team-contact-row">
                            <?php if (!empty($manager['email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($manager['email']); ?>" class="emp-team-contact-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    Email
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($manager['phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $manager['phone'])); ?>" class="emp-team-contact-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    Call
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="emp-reg-empty emp-reg-empty-compact">
                    <strong>No manager assigned</strong>
                    <p>Ask HR to set your reporting manager in the org chart.</p>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($is_mgr): ?>
        <section class="emp-card emp-team-reports-panel">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div>
                    <h3 class="emp-card-title">Direct reports</h3>
                    <p class="emp-reg-toolbar-sub"><?php echo count($reports); ?> team member<?php echo count($reports) === 1 ? '' : 's'; ?>.</p>
                </div>
                <a href="team_approvals.php" class="btn btn-sm">Review requests</a>
            </header>
            <?php if ($reports === []): ?>
                <div class="emp-reg-empty emp-reg-empty-compact">
                    <strong>No direct reports</strong>
                    <p>Employees assigned to you in the org chart will appear here.</p>
                </div>
            <?php else: ?>
                <div class="emp-team-roster-grid">
                    <?php foreach ($reports as $r): ?>
                    <article class="emp-team-member-card">
                        <span class="emp-avatar emp-avatar-lg" aria-hidden="true"><?php echo strtoupper(substr($r['name'], 0, 1)); ?></span>
                        <div>
                            <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                            <span class="emp-team-member-id"><?php echo htmlspecialchars($r['emp_id']); ?></span>
                            <span class="emp-team-member-role"><?php echo htmlspecialchars($r['designation'] ?: 'Staff'); ?></span>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
