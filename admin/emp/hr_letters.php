<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/employee_document_helper.php';

$letter_types = [
    'office_experience' => 'Experience letter',
    'office_relieving' => 'Relieving letter',
    'office_noc' => 'NOC letter',
    'office_form16' => 'Form 16 request',
];

$all_requests = get_employee_document_requests($conn, $employee['emp_id'], 30);
$letter_requests = array_values(array_filter($all_requests, static function ($r) use ($letter_types) {
    return isset($letter_types[$r['doc_type'] ?? '']);
}));

$pending_count = 0;
$approved_count = 0;
foreach ($letter_requests as $r) {
    $st = $r['request_status'] ?? '';
    if ($st === 'pending') {
        $pending_count++;
    } elseif ($st === 'approved') {
        $approved_count++;
    }
}
?>
<div class="emp-page emp-page-hr-letters">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Company</p>
            <h2 class="emp-page-hero-title">HR letters</h2>
            <p>Request official letters such as experience, relieving, NOC, or Form 16. HR will process and share documents when ready.</p>
        </div>
        <?php if ($pending_count > 0): ?>
            <span class="emp-hero-stat-pill emp-hero-stat-pill-warn"><?php echo $pending_count; ?> pending</span>
        <?php else: ?>
            <span class="emp-hero-stat-pill"><?php echo count($letter_types); ?> letter types</span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div><span class="emp-dash-stat-label">Total requests</span><strong class="emp-dash-stat-value"><?php echo count($letter_requests); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><span class="emp-dash-stat-label">Pending</span><strong class="emp-dash-stat-value"><?php echo $pending_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span class="emp-dash-stat-label">Approved</span><strong class="emp-dash-stat-value"><?php echo $approved_count; ?></strong></div>
        </div>
    </div>

    <div class="emp-section-grid emp-section-grid-reg">
        <div class="emp-reg-main">
            <section class="emp-card emp-reg-history-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div>
                        <h3 class="emp-card-title">Letter request history</h3>
                        <p class="emp-reg-toolbar-sub">Track status of your HR letter requests. <a href="documents.php">View all documents →</a></p>
                    </div>
                </header>
                <?php if ($letter_requests === []): ?>
                    <div class="emp-reg-empty">
                        <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
                        <strong>No letter requests yet</strong>
                        <p>Use the form on the right to request an official letter from HR.</p>
                    </div>
                <?php else: ?>
                    <div class="emp-timeline emp-reg-timeline">
                        <?php foreach ($letter_requests as $req):
                            $status = $req['request_status'] ?? 'pending';
                            $type_label = $letter_types[$req['doc_type'] ?? ''] ?? ($req['doc_label'] ?? 'Letter');
                        ?>
                        <article class="emp-timeline-item">
                            <div class="emp-timeline-dot emp-timeline-<?php echo htmlspecialchars($status); ?>"></div>
                            <div class="emp-timeline-body">
                                <div class="emp-timeline-top">
                                    <strong><?php echo htmlspecialchars($type_label); ?></strong>
                                    <span class="emp-req-status emp-req-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                                </div>
                                <?php if (!empty($req['employee_note'])): ?>
                                    <p class="emp-reg-note"><?php echo nl2br(htmlspecialchars($req['employee_note'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($req['review_note'])): ?>
                                    <p class="emp-timeline-note">HR: <?php echo nl2br(htmlspecialchars($req['review_note'])); ?></p>
                                <?php endif; ?>
                                <time>Requested <?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></time>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="emp-request-panel emp-request-panel-att">
            <div class="emp-request-panel-header">
                <span class="emp-request-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
                <div><h3>Request a letter</h3><p>Select the letter type and add context for HR processing.</p></div>
            </div>
            <div class="emp-request-panel-body">
                <form method="POST" action="hr_letter_save.php" class="emp-request-form">
                    <?php echo csrf_field(); ?>
                    <div class="emp-request-fields">
                        <div class="form-group">
                            <label for="empLetterType">Letter type <span class="req">*</span></label>
                            <select id="empLetterType" name="doc_type" required>
                                <?php foreach ($letter_types as $k => $label): ?>
                                    <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="empLetterNote">Note for HR</label>
                            <textarea id="empLetterNote" name="employee_note" rows="4" placeholder="Purpose, delivery format, or any details HR should know"></textarea>
                        </div>
                    </div>
                    <div class="emp-request-submit">
                        <button type="submit" class="btn btn-block">Request letter</button>
                    </div>
                </form>
            </div>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
