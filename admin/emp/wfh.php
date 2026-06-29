<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$requests = get_employee_wfh_requests($conn, $employee['emp_id'], 50);
$pending_count = 0;
$approved_count = 0;
foreach ($requests as $req) {
    $st = $req['request_status'] ?? '';
    if ($st === 'pending') {
        $pending_count++;
    } elseif ($st === 'approved') {
        $approved_count++;
    }
}
?>
<div class="emp-page emp-page-wfh">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Requests</p>
            <h2 class="emp-page-hero-title">Work from home</h2>
            <p>Request WFH for upcoming dates. Your manager or branch admin will approve before it is recorded.</p>
        </div>
        <?php if ($pending_count > 0): ?>
            <span class="emp-page-lock-badge"><?php echo $pending_count; ?> pending</span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div>
            <div><span class="emp-dash-stat-label">Total requests</span><strong class="emp-dash-stat-value"><?php echo count($requests); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span class="emp-dash-stat-label">Approved</span><strong class="emp-dash-stat-value"><?php echo $approved_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg></div>
            <div><span class="emp-dash-stat-label">Pending</span><strong class="emp-dash-stat-value"><?php echo $pending_count; ?></strong></div>
        </div>
    </div>

    <div class="emp-section-grid emp-section-grid-reg">
        <div class="emp-reg-main">
            <section class="emp-card emp-reg-history-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div><h3 class="emp-card-title">WFH history</h3><p class="emp-reg-toolbar-sub">Past and upcoming work-from-home requests.</p></div>
                </header>
                <?php if ($requests === []): ?>
                    <div class="emp-reg-empty">
                        <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                        <strong>No WFH requests yet</strong>
                        <p>Submit a request from the panel on the right for dates you plan to work remotely.</p>
                    </div>
                <?php else: ?>
                    <div class="emp-timeline emp-reg-timeline">
                        <?php foreach ($requests as $req):
                            $status = $req['request_status'] ?? 'pending';
                        ?>
                        <article class="emp-timeline-item">
                            <div class="emp-timeline-dot emp-timeline-<?php echo htmlspecialchars($status); ?>"></div>
                            <div class="emp-timeline-body">
                                <div class="emp-timeline-top">
                                    <strong><?php echo date('D, d M Y', strtotime($req['wfh_date'])); ?></strong>
                                    <span class="emp-req-status emp-req-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                                </div>
                                <?php if (!empty($req['employee_note'])): ?>
                                    <p class="emp-reg-note"><?php echo nl2br(htmlspecialchars($req['employee_note'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($req['review_note'])): ?>
                                    <p class="emp-timeline-note">Admin: <?php echo nl2br(htmlspecialchars($req['review_note'])); ?></p>
                                <?php endif; ?>
                                <time>Submitted <?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></time>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="emp-request-panel emp-request-panel-att">
            <div class="emp-request-panel-header">
                <span class="emp-request-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                <div><h3>New WFH request</h3><p>Select a future date and share your work plan or reason.</p></div>
            </div>
            <div class="emp-request-panel-body">
                <form method="POST" action="wfh_save.php" class="emp-request-form">
                    <?php echo csrf_field(); ?>
                    <div class="emp-request-fields">
                        <div class="form-group">
                            <label for="empWfhDate">WFH date <span class="req">*</span></label>
                            <input type="date" id="empWfhDate" name="wfh_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="empWfhNote">Note</label>
                            <textarea id="empWfhNote" name="employee_note" rows="4" placeholder="Reason or work plan for the day"></textarea>
                        </div>
                    </div>
                    <div class="emp-request-submit">
                        <button type="submit" class="btn btn-block">Send for approval</button>
                    </div>
                </form>
            </div>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
