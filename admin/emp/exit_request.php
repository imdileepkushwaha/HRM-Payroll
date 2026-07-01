<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/salary_helper.php';

$settings = get_all_settings($conn);
$active_exit = get_employee_active_exit($conn, $employee['emp_id']);

$exit_steps = [
    ['key' => 'initiated', 'label' => 'Resignation submitted'],
    ['key' => 'notice', 'label' => 'Notice period'],
    ['key' => 'fnf', 'label' => 'F&amp;F settlement'],
    ['key' => 'completed', 'label' => 'Exit complete'],
];
$current_step = 0;
if ($active_exit) {
    $st = strtolower($active_exit['status'] ?? 'initiated');
    if ($st === 'completed') {
        $current_step = 3;
    } elseif (!empty($active_exit['fnf_status'])) {
        $current_step = 2;
    } elseif (!empty($active_exit['last_working_day'])) {
        $current_step = 1;
    }
}
?>
<div class="emp-page emp-page-exit">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Career</p>
            <h2 class="emp-page-hero-title">Resignation &amp; exit</h2>
            <p>Initiate your resignation and track full &amp; final settlement. HR will guide you through handover and clearance.</p>
        </div>
        <?php if ($active_exit): ?>
            <span class="emp-hero-stat-pill emp-hero-stat-pill-warn">In progress</span>
        <?php endif; ?>
    </div>

    <?php if ($active_exit): ?>
        <section class="emp-card emp-exit-tracker-card">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div><h3 class="emp-card-title">Exit progress</h3><p class="emp-reg-toolbar-sub">Your resignation and settlement journey.</p></div>
            </header>

            <ol class="emp-exit-stepper">
                <?php foreach ($exit_steps as $i => $step): ?>
                <li class="emp-exit-step<?php echo $i <= $current_step ? ' is-done' : ''; ?><?php echo $i === $current_step ? ' is-current' : ''; ?>">
                    <span class="emp-exit-step-dot"><?php echo $i < $current_step ? '✓' : ($i + 1); ?></span>
                    <span class="emp-exit-step-label"><?php echo $step['label']; ?></span>
                </li>
                <?php endforeach; ?>
            </ol>

            <div class="emp-exit-status-grid">
                <div class="emp-exit-stat-tile">
                    <span>Status</span>
                    <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $active_exit['status'] ?? ''))); ?></strong>
                </div>
                <div class="emp-exit-stat-tile">
                    <span>Last working day</span>
                    <strong><?php echo date('d M Y', strtotime($active_exit['last_working_day'])); ?></strong>
                </div>
                <?php if (!empty($active_exit['resignation_date'])): ?>
                <div class="emp-exit-stat-tile">
                    <span>Resignation date</span>
                    <strong><?php echo date('d M Y', strtotime($active_exit['resignation_date'])); ?></strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($active_exit['fnf_status'])): ?>
                <div class="emp-exit-stat-tile emp-exit-stat-tile-highlight">
                    <span>F&amp;F status</span>
                    <strong><?php echo htmlspecialchars(ucfirst($active_exit['fnf_status'])); ?></strong>
                    <?php if (isset($active_exit['net_payable'])): ?>
                        <small>Net payable <?php echo format_money((float) $active_exit['net_payable']); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="emp-exit-help-banner">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <div><strong>Questions about your exit?</strong><span>Contact HR for notice period, asset return, or settlement timelines.</span></div>
            </div>
        </section>
    <?php else: ?>
        <div class="emp-section-grid emp-section-grid-exit">
            <section class="emp-card emp-exit-guide-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div><h3 class="emp-card-title">Before you submit</h3><p class="emp-reg-toolbar-sub">A smooth exit starts with the right conversations.</p></div>
                </header>
                <ul class="emp-exit-guide-list">
                    <li><span class="emp-exit-guide-num">1</span><div><strong>Talk to your manager</strong><span>Align on transition plan and knowledge handover.</span></div></li>
                    <li><span class="emp-exit-guide-num">2</span><div><strong>Check notice period</strong><span>Refer to your employment terms and company policy.</span></div></li>
                    <li><span class="emp-exit-guide-num">3</span><div><strong>Return company assets</strong><span>Laptop, ID card and other assigned items.</span></div></li>
                    <li><span class="emp-exit-guide-num">4</span><div><strong>F&amp;F after LWD</strong><span>Settlement is processed after your last working day.</span></div></li>
                </ul>
                <p class="emp-exit-policy-link"><a href="policies.php">Read company policies →</a> · <a href="assets.php">View my assets →</a></p>
            </section>

            <aside class="emp-request-panel emp-request-panel-att emp-request-panel-exit">
                <div class="emp-request-panel-header">
                    <span class="emp-request-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                    <div><h3>Submit resignation</h3><p>This notifies HR immediately. Double-check your last working day.</p></div>
                </div>
                <div class="emp-request-panel-body">
                    <form method="POST" action="exit_request_save.php" class="emp-request-form" onsubmit="return confirm('Submit resignation request? This will notify HR.');">
                        <?php echo csrf_field(); ?>
                        <div class="emp-request-fields">
                            <div class="form-group">
                                <label for="empExitResign">Resignation date</label>
                                <input type="date" id="empExitResign" name="resignation_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="empExitLwd">Last working day <span class="req">*</span></label>
                                <input type="date" id="empExitLwd" name="last_working_day" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="empExitReason">Reason (optional)</label>
                                <textarea id="empExitReason" name="reason" rows="4" placeholder="Share context for HR (optional)"></textarea>
                            </div>
                        </div>
                        <div class="emp-request-submit">
                            <button type="submit" class="btn btn-block emp-btn-exit">Submit resignation</button>
                            <p class="emp-request-footnote">Cannot be undone from the portal — contact HR if submitted by mistake.</p>
                        </div>
                    </form>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
