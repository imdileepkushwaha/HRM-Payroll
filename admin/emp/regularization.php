<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/includes/period.php';

[$year, $month] = emp_parse_period();
$period_label = get_period_label($year, $month);
$period_query = 'year=' . $year . '&month=' . $month;

$requests = get_employee_punch_regularization_requests($conn, $employee['emp_id'], 50);

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

$format_punch_time = static function (?string $time): string {
    if ($time === null || trim($time) === '') {
        return '—';
    }
    $ts = strtotime($time);
    return $ts ? date('g:i A', $ts) : htmlspecialchars($time);
};
?>
<div class="emp-page emp-page-regularization">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Attendance</p>
            <h2 class="emp-page-hero-title">Punch regularization</h2>
            <p>Request correction when you missed a punch or need in/out times updated. Your manager or branch admin will review before changes apply. <a href="punch_history.php?<?php echo $period_query; ?>">View punch history</a> · <a href="attendance.php?<?php echo $period_query; ?>">My attendance</a></p>
        </div>
        <?php if ($pending_count > 0): ?>
            <span class="emp-page-lock-badge"><?php echo $pending_count; ?> pending</span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3 emp-reg-stats">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <span class="emp-dash-stat-label">Total requests</span>
                <strong class="emp-dash-stat-value"><?php echo count($requests); ?></strong>
                <span class="emp-dash-stat-hint">All time</span>
            </div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <span class="emp-dash-stat-label">Approved</span>
                <strong class="emp-dash-stat-value"><?php echo $approved_count; ?></strong>
                <span class="emp-dash-stat-hint">Corrections accepted</span>
            </div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-absent">
            <div class="emp-dash-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <span class="emp-dash-stat-label">Pending review</span>
                <strong class="emp-dash-stat-value"><?php echo $pending_count; ?></strong>
                <span class="emp-dash-stat-hint">Awaiting approval</span>
            </div>
        </div>
    </div>

    <div class="emp-section-grid emp-section-grid-reg">
        <div class="emp-reg-main">
            <section class="emp-card emp-reg-history-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div>
                        <h3 class="emp-card-title">Request history</h3>
                        <p class="emp-reg-toolbar-sub">Track submitted punch corrections and admin responses.</p>
                    </div>
                </header>

                <?php if ($requests === []): ?>
                    <div class="emp-reg-empty">
                        <span class="emp-reg-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <strong>No regularization requests yet</strong>
                        <p>Submit a request from the panel on the right if you missed a punch or need times corrected.</p>
                    </div>
                <?php else: ?>
                    <div class="emp-timeline emp-reg-timeline">
                        <?php foreach ($requests as $req):
                            $status = $req['request_status'] ?? 'pending';
                            $date_ts = strtotime($req['punch_date']);
                        ?>
                        <article class="emp-timeline-item emp-reg-timeline-item">
                            <div class="emp-timeline-dot emp-timeline-<?php echo htmlspecialchars($status); ?>"></div>
                            <div class="emp-timeline-body">
                                <div class="emp-timeline-top">
                                    <strong><?php echo date('D, d M Y', $date_ts); ?></strong>
                                    <span class="emp-req-status emp-req-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                                </div>
                                <div class="emp-reg-time-row">
                                    <span class="emp-reg-time-chip">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                        In <?php echo $format_punch_time($req['requested_in_time'] ?? null); ?>
                                    </span>
                                    <span class="emp-reg-time-chip">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/><path d="M19 12H5"/></svg>
                                        Out <?php echo $format_punch_time($req['requested_out_time'] ?? null); ?>
                                    </span>
                                </div>
                                <?php if (!empty($req['employee_note'])): ?>
                                    <p class="emp-reg-note"><?php echo nl2br(htmlspecialchars($req['employee_note'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($req['review_note'])): ?>
                                    <p class="emp-timeline-note">Admin: <?php echo nl2br(htmlspecialchars($req['review_note'])); ?></p>
                                <?php endif; ?>
                                <time datetime="<?php echo htmlspecialchars(date('c', strtotime($req['created_at']))); ?>">Submitted <?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></time>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="emp-request-panel emp-request-panel-att emp-request-panel-reg">
            <div class="emp-request-panel-header">
                <span class="emp-request-panel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </span>
                <div>
                    <h3>New regularization request</h3>
                    <p>Enter the date and corrected in/out times. Provide a clear reason so admin can approve quickly.</p>
                </div>
            </div>
            <div class="emp-request-panel-body">
                <div class="emp-inline-alert emp-inline-alert-info">
                    <strong>Tip</strong>
                    <span>Enter at least one of in-time or out-time. Past dates only — today or earlier.</span>
                </div>

                <form method="POST" action="regularization_save.php" class="emp-request-form" id="empRegForm">
                    <?php echo csrf_field(); ?>
                    <div class="emp-request-fields">
                        <div class="form-group">
                            <label for="empRegDate">Punch date <span class="req">*</span></label>
                            <input type="date" id="empRegDate" name="punch_date" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                            <span class="form-hint">Date you missed or need to correct</span>
                        </div>
                        <div class="emp-request-fields emp-reg-time-fields">
                            <div class="form-group">
                                <label for="empRegIn">Corrected in-time</label>
                                <input type="time" id="empRegIn" name="requested_in_time">
                            </div>
                            <div class="form-group">
                                <label for="empRegOut">Corrected out-time</label>
                                <input type="time" id="empRegOut" name="requested_out_time">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="empRegNote">Reason <span class="req">*</span></label>
                            <textarea id="empRegNote" name="employee_note" rows="4" required placeholder="e.g. Forgot to punch out after client visit"></textarea>
                        </div>
                    </div>
                    <div class="emp-request-submit">
                        <button type="submit" class="btn btn-block">Send to branch admin</button>
                        <p class="emp-request-footnote">Approved corrections update your punch record for that day.</p>
                    </div>
                </form>
            </div>
        </aside>
    </div>
</div>
<script>
(function () {
    var form = document.getElementById('empRegForm');
    var inTime = document.getElementById('empRegIn');
    var outTime = document.getElementById('empRegOut');
    if (!form || !inTime || !outTime) return;
    form.addEventListener('submit', function (e) {
        if (!inTime.value && !outTime.value) {
            e.preventDefault();
            alert('Please enter at least in-time or out-time.');
        }
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
