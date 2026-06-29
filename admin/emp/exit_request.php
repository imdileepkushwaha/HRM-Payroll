<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/salary_helper.php';

$settings = get_all_settings($conn);
$active_exit = get_employee_active_exit($conn, $employee['emp_id']);
?>
<div class="emp-page emp-page-exit">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero emp-page-hero-profile">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Career</p>
            <h2 class="emp-page-hero-title">Resignation &amp; exit</h2>
            <p>Submit your resignation request. HR will initiate exit formalities and F&amp;F settlement after approval.</p>
        </div>
        <?php if ($active_exit): ?>
            <span class="emp-page-lock-badge">Exit in progress</span>
        <?php endif; ?>
    </div>

    <?php if ($active_exit): ?>
        <section class="emp-card emp-exit-active-card">
            <header class="emp-card-toolbar emp-reg-toolbar">
                <div><h3 class="emp-card-title">Your exit status</h3><p class="emp-reg-toolbar-sub">Track resignation and full &amp; final settlement progress.</p></div>
            </header>
            <div class="emp-exit-status-grid">
                <div class="emp-info-card">
                    <span class="emp-info-label">Status</span>
                    <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $active_exit['status'] ?? ''))); ?></strong>
                </div>
                <div class="emp-info-card">
                    <span class="emp-info-label">Last working day</span>
                    <strong><?php echo date('d M Y', strtotime($active_exit['last_working_day'])); ?></strong>
                </div>
                <?php if (!empty($active_exit['resignation_date'])): ?>
                <div class="emp-info-card">
                    <span class="emp-info-label">Resignation date</span>
                    <strong><?php echo date('d M Y', strtotime($active_exit['resignation_date'])); ?></strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($active_exit['fnf_status'])): ?>
                <div class="emp-info-card">
                    <span class="emp-info-label">F&amp;F status</span>
                    <strong><?php echo htmlspecialchars(ucfirst($active_exit['fnf_status'])); ?></strong>
                    <?php if (isset($active_exit['net_payable'])): ?>
                        <small>Net payable: <?php echo format_money((float) $active_exit['net_payable']); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="emp-inline-alert emp-inline-alert-info" style="margin-top:1rem">
                <strong>Need help?</strong>
                <span>Contact HR for questions about notice period, handover or settlement.</span>
            </div>
        </section>
    <?php else: ?>
        <div class="emp-section-grid emp-section-grid-exit">
            <section class="emp-card emp-exit-info-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div><h3 class="emp-card-title">Before you submit</h3></div>
                </header>
                <ul class="emp-exit-checklist">
                    <li>Discuss with your reporting manager before resigning.</li>
                    <li>Notice period may apply as per company policy.</li>
                    <li>HR will contact you for handover and asset return.</li>
                    <li>F&amp;F settlement is processed after your last working day.</li>
                </ul>
                <p class="emp-muted"><a href="policies.php">View company policies →</a></p>
            </section>

            <aside class="emp-request-panel emp-request-panel-att">
                <div class="emp-request-panel-header">
                    <span class="emp-request-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                    <div><h3>Submit resignation</h3><p>This notifies HR and starts the exit process. Please confirm dates carefully.</p></div>
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
                                <textarea id="empExitReason" name="reason" rows="4" placeholder="Optional — reason for leaving"></textarea>
                            </div>
                        </div>
                        <div class="emp-request-submit">
                            <button type="submit" class="btn btn-block">Submit resignation</button>
                            <p class="emp-request-footnote">You cannot undo this from the portal — contact HR if submitted by mistake.</p>
                        </div>
                    </form>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
