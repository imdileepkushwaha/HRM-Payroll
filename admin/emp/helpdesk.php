<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$tickets = get_employee_helpdesk_tickets($conn, $employee['emp_id']);
$categories = ['General', 'Payroll', 'Leave', 'IT support', 'HR policy', 'Other'];

$open_count = 0;
$answered_count = 0;
$closed_count = 0;
foreach ($tickets as $t) {
    $st = strtolower($t['status'] ?? 'open');
    if ($st === 'closed') {
        $closed_count++;
    } elseif ($st === 'answered' || !empty($t['admin_reply'])) {
        $answered_count++;
    } else {
        $open_count++;
    }
}
?>
<div class="emp-page emp-page-helpdesk">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Company</p>
            <h2 class="emp-page-hero-title">HR helpdesk</h2>
            <p>Raise a support ticket for payroll, leave, IT, or HR policy questions. HR will reply on the same ticket.</p>
        </div>
        <?php if ($open_count > 0): ?>
            <span class="emp-hero-stat-pill emp-hero-stat-pill-warn"><?php echo $open_count; ?> open</span>
        <?php elseif ($tickets !== []): ?>
            <span class="emp-hero-stat-pill"><?php echo count($tickets); ?> ticket<?php echo count($tickets) === 1 ? '' : 's'; ?></span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
            <div><span class="emp-dash-stat-label">Total tickets</span><strong class="emp-dash-stat-value"><?php echo count($tickets); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><span class="emp-dash-stat-label">Open</span><strong class="emp-dash-stat-value"><?php echo $open_count; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span class="emp-dash-stat-label">Answered</span><strong class="emp-dash-stat-value"><?php echo $answered_count; ?></strong></div>
        </div>
    </div>

    <div class="emp-section-grid emp-section-grid-reg">
        <div class="emp-reg-main">
            <section class="emp-card emp-reg-history-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div>
                        <h3 class="emp-card-title">My tickets</h3>
                        <p class="emp-reg-toolbar-sub">Track open requests and read HR replies.</p>
                    </div>
                </header>
                <?php if ($tickets === []): ?>
                    <div class="emp-reg-empty">
                        <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                        <strong>No tickets yet</strong>
                        <p>Submit your first support request using the form on the right.</p>
                    </div>
                <?php else: ?>
                    <div class="emp-timeline emp-reg-timeline emp-helpdesk-timeline">
                        <?php foreach ($tickets as $t):
                            $status = strtolower($t['status'] ?? 'open');
                            $status_label = ucfirst($status);
                        ?>
                        <article class="emp-timeline-item">
                            <div class="emp-timeline-dot emp-timeline-<?php echo htmlspecialchars($status === 'closed' ? 'approved' : ($status === 'answered' || !empty($t['admin_reply']) ? 'approved' : 'pending')); ?>"></div>
                            <div class="emp-timeline-body">
                                <div class="emp-timeline-top">
                                    <strong><?php echo htmlspecialchars($t['subject']); ?></strong>
                                    <span class="emp-req-status emp-req-<?php echo htmlspecialchars($status === 'closed' ? 'approved' : ($status === 'answered' ? 'approved' : 'pending')); ?>"><?php echo htmlspecialchars($status_label); ?></span>
                                </div>
                                <p class="emp-reg-note"><span class="emp-helpdesk-cat"><?php echo htmlspecialchars($t['category'] ?? 'General'); ?></span> — <?php echo nl2br(htmlspecialchars($t['body'])); ?></p>
                                <?php if (!empty($t['admin_reply'])): ?>
                                    <p class="emp-timeline-note"><strong>HR reply:</strong> <?php echo nl2br(htmlspecialchars($t['admin_reply'])); ?></p>
                                <?php endif; ?>
                                <time>Submitted <?php echo date('d M Y, h:i A', strtotime($t['created_at'])); ?></time>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="emp-request-panel emp-request-panel-att">
            <div class="emp-request-panel-header">
                <span class="emp-request-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                <div><h3>New support ticket</h3><p>Describe your issue and choose the right category for faster routing.</p></div>
            </div>
            <div class="emp-request-panel-body">
                <form method="POST" action="helpdesk_save.php" class="emp-request-form">
                    <?php echo csrf_field(); ?>
                    <div class="emp-request-fields">
                        <div class="form-group">
                            <label for="empHelpdeskCategory">Category <span class="req">*</span></label>
                            <select id="empHelpdeskCategory" name="category" required>
                                <?php foreach ($categories as $c): ?>
                                    <option><?php echo htmlspecialchars($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="empHelpdeskSubject">Subject <span class="req">*</span></label>
                            <input type="text" id="empHelpdeskSubject" name="subject" required maxlength="200" placeholder="Brief summary of your issue">
                        </div>
                        <div class="form-group">
                            <label for="empHelpdeskBody">Description <span class="req">*</span></label>
                            <textarea id="empHelpdeskBody" name="body" rows="5" required placeholder="Share details HR or IT will need to help you"></textarea>
                        </div>
                    </div>
                    <div class="emp-request-submit">
                        <button type="submit" class="btn btn-block">Submit ticket</button>
                    </div>
                </form>
            </div>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
