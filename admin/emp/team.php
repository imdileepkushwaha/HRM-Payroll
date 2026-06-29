<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$manager = get_employee_manager($conn, $employee['emp_id']);
$reports = get_employee_direct_reports($conn, $employee['emp_id']);
$is_mgr = employee_is_manager($conn, $employee['emp_id']);
?>
<div class="emp-page emp-page-team">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">People</p>
            <h2 class="emp-page-hero-title">My team</h2>
            <p>Your reporting manager and direct reports. Managers can approve team requests and view the branch calendar.</p>
        </div>
        <?php if ($is_mgr): ?>
            <span class="emp-page-lock-badge"><?php echo count($reports); ?> report<?php echo count($reports) === 1 ? '' : 's'; ?></span>
        <?php endif; ?>
    </div>

    <?php if ($is_mgr): ?>
    <div class="emp-team-actions">
        <a href="team_approvals.php" class="emp-dash-shortcut">Team approvals</a>
        <a href="calendar.php" class="emp-dash-shortcut">Team calendar</a>
    </div>
    <?php endif; ?>

    <div class="emp-section-grid emp-section-grid-team">
        <section class="emp-card">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div><h3 class="emp-card-title">Reporting manager</h3><p class="emp-reg-toolbar-sub">Your line manager for approvals and escalations.</p></div>
            </header>
            <?php if ($manager): ?>
                <div class="emp-team-manager-card">
                    <span class="emp-avatar emp-avatar-lg" aria-hidden="true"><?php echo strtoupper(substr($manager['name'], 0, 1)); ?></span>
                    <div class="emp-team-manager-info">
                        <strong><?php echo htmlspecialchars($manager['name']); ?></strong>
                        <span><?php echo htmlspecialchars($manager['designation'] ?: 'Manager'); ?><?php if (!empty($manager['department'])): ?> · <?php echo htmlspecialchars($manager['department']); ?><?php endif; ?></span>
                        <?php if (!empty($manager['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($manager['email']); ?>" class="emp-team-contact-link"><?php echo htmlspecialchars($manager['email']); ?></a>
                        <?php endif; ?>
                        <?php if (!empty($manager['phone'])): ?>
                            <span class="emp-muted"><?php echo htmlspecialchars($manager['phone']); ?></span>
                        <?php endif; ?>
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
        <section class="emp-card">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div>
                    <h3 class="emp-card-title">Direct reports</h3>
                    <p class="emp-reg-toolbar-sub"><?php echo count($reports); ?> team member<?php echo count($reports) === 1 ? '' : 's'; ?> reporting to you.</p>
                </div>
                <a href="team_approvals.php" class="btn btn-sm">Approvals</a>
            </header>
            <?php if ($reports === []): ?>
                <div class="emp-reg-empty emp-reg-empty-compact">
                    <strong>No direct reports</strong>
                    <p>Employees assigned to you in the org chart will appear here.</p>
                </div>
            <?php else: ?>
                <ul class="emp-team-roster">
                    <?php foreach ($reports as $r): ?>
                    <li class="emp-team-roster-item">
                        <span class="emp-avatar emp-avatar-sm" aria-hidden="true"><?php echo strtoupper(substr($r['name'], 0, 1)); ?></span>
                        <div>
                            <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                            <span><?php echo htmlspecialchars($r['emp_id']); ?> · <?php echo htmlspecialchars($r['designation'] ?: 'Staff'); ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
