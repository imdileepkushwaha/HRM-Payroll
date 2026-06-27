<?php
/**
 * Employee punch in/out card (Phase 1: web punch + geo).
 * Expects: $conn, $employee, $settings
 */
require_once __DIR__ . '/../../includes/punch_helper.php';

$punch_enabled = is_punch_enabled($settings);
$geo_enabled = is_geo_attendance_enabled($settings);
$today = date('Y-m-d');
$punch_state = get_employee_punch_state_for_date($conn, $employee['emp_id'], $today);
$branch_id = (int) ($employee['branch_id'] ?? 1);
$branch_settings = get_branch_punch_settings($conn, $branch_id, $settings);
$geofence = $geo_enabled ? get_branch_geofence($conn, $branch_id, $settings) : null;
$period_locked = is_payroll_period_locked($conn, (int) date('Y'), (int) date('n'), $branch_id);
$non_working_block = should_block_punch_on_holiday_weekoff($settings)
    ? get_employee_non_working_day_block($conn, $employee['emp_id'], $today, $employee)
    : ['blocked' => false, 'reason' => ''];
$punch_blocked_today = !empty($non_working_block['blocked']);
$can_punch = $punch_enabled && !$period_locked && !$punch_blocked_today && $punch_state['next_action'] !== null;
$next_action = $punch_state['next_action'];
$punch_card_id = 'empPunchCard_' . substr(md5($employee['emp_id'] . $today), 0, 8);

$office_start_label = date('g:i A', strtotime(get_office_start_time($branch_settings)));
$office_end_label = date('g:i A', strtotime(get_office_end_time($branch_settings)));
$grace_minutes = get_late_grace_minutes($branch_settings);

$last_in = $punch_state['last_in'] ?? null;
$last_out = $punch_state['last_out'] ?? null;
$in_punct = $last_in
    ? format_punctuality_badge($last_in['punctuality_status'] ?? null, $last_in['late_by_minutes'] ?? null, 'in')
    : null;
$out_punct = $last_out
    ? format_punctuality_badge($last_out['punctuality_status'] ?? null, $last_out['late_by_minutes'] ?? null, 'out')
    : null;
$in_time = $punch_state['has_in'] ? format_punch_time($last_in['punched_at'] ?? null) : '—';
$out_time = $punch_state['has_out'] ? format_punch_time($last_out['punched_at'] ?? null) : '—';

$track_progress = 0;
if ($punch_state['has_in'] && $punch_state['has_out']) {
    $track_progress = 100;
} elseif ($punch_state['has_in']) {
    $track_progress = 50;
}

$card_state = 'blocked';
$status_chip = 'Unavailable';
$status_chip_class = 'muted';
if (!$punch_enabled) {
    $status_message = 'Punch is turned off by admin.';
} elseif ($period_locked) {
    $status_message = 'Payroll period is locked — punch disabled.';
} elseif ($punch_blocked_today) {
    $status_message = $non_working_block['reason'] ?? 'Punch is not allowed today.';
} elseif ($punch_state['complete']) {
    $card_state = 'complete';
    $status_chip = 'Day complete';
    $status_chip_class = 'success';
    $status_message = 'You have completed punch in and punch out for today.';
} elseif ($next_action === 'in') {
    $card_state = 'await-in';
    $status_chip = 'Awaiting punch in';
    $status_chip_class = 'info';
    $status_message = 'Tap punch in when you arrive' . ($geo_enabled ? ' at the office' : '') . '.';
} elseif ($next_action === 'out') {
    $card_state = 'await-out';
    $status_chip = 'Awaiting punch out';
    $status_chip_class = 'warn';
    $status_message = 'Tap punch out when you leave for the day.';
} else {
    $status_message = 'Punch is not available right now.';
}

$in_step_state = $punch_state['has_in'] ? 'done' : ($next_action === 'in' ? 'active' : 'pending');
$out_step_state = $punch_state['has_out'] ? 'done' : ($next_action === 'out' ? 'active' : 'pending');
?>
<section
    class="emp-punch-card emp-punch-card--<?php echo htmlspecialchars($card_state); ?>"
    id="<?php echo htmlspecialchars($punch_card_id); ?>"
    aria-label="Today's attendance punch"
>
    <div class="emp-punch-card-accent" aria-hidden="true"></div>

    <div class="emp-punch-card-body">
        <header class="emp-punch-header">
            <div class="emp-punch-header-main">
                <span class="emp-punch-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <div>
                    <p class="emp-punch-eyebrow">Today · <?php echo htmlspecialchars(date('l, j M Y')); ?></p>
                    <h2 class="emp-punch-title">Attendance punch</h2>
                </div>
            </div>
            <div class="emp-punch-header-aside">
                <span class="emp-punch-live-time" data-punch-live-time aria-live="off"><?php echo htmlspecialchars(date('g:i:s A')); ?></span>
                <span class="emp-punch-chip emp-punch-chip--<?php echo htmlspecialchars($status_chip_class); ?>"><?php echo htmlspecialchars($status_chip); ?></span>
            </div>
        </header>

        <div class="emp-punch-track" style="--punch-progress: <?php echo (int) $track_progress; ?>%;">
            <div class="emp-punch-step emp-punch-step--in emp-punch-step--<?php echo htmlspecialchars($in_step_state); ?>">
                <span class="emp-punch-step-dot" aria-hidden="true">
                    <?php if ($punch_state['has_in']): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <?php endif; ?>
                </span>
                <div class="emp-punch-step-body">
                    <span class="emp-punch-step-label">Punch in</span>
                    <strong class="emp-punch-step-time"><?php echo htmlspecialchars($in_time); ?></strong>
                    <?php if ($in_punct && $in_punct['label'] !== '—'): ?>
                        <span class="emp-punch-step-tag <?php echo htmlspecialchars($in_punct['class']); ?>">
                            <?php echo htmlspecialchars($in_punct['label']); ?>
                            <?php if (!empty($in_punct['suffix'])): ?>
                                <span class="emp-punch-step-tag-meta"><?php echo htmlspecialchars($in_punct['suffix']); ?></span>
                            <?php endif; ?>
                        </span>
                    <?php elseif ($punch_state['has_in']): ?>
                        <span class="emp-punch-step-tag punch-punctuality-on-time">On time</span>
                    <?php else: ?>
                        <span class="emp-punch-step-tag emp-punch-step-tag-muted">Not yet</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="emp-punch-connector" aria-hidden="true">
                <span class="emp-punch-connector-fill"></span>
            </div>

            <div class="emp-punch-step emp-punch-step--out emp-punch-step--<?php echo htmlspecialchars($out_step_state); ?>">
                <span class="emp-punch-step-dot" aria-hidden="true">
                    <?php if ($punch_state['has_out']): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <?php endif; ?>
                </span>
                <div class="emp-punch-step-body">
                    <span class="emp-punch-step-label">Punch out</span>
                    <strong class="emp-punch-step-time"><?php echo htmlspecialchars($out_time); ?></strong>
                    <?php if ($out_punct && $out_punct['label'] !== '—'): ?>
                        <span class="emp-punch-step-tag <?php echo htmlspecialchars($out_punct['class']); ?>">
                            <?php echo htmlspecialchars($out_punct['label']); ?>
                            <?php if (!empty($out_punct['suffix'])): ?>
                                <span class="emp-punch-step-tag-meta"><?php echo htmlspecialchars($out_punct['suffix']); ?></span>
                            <?php endif; ?>
                        </span>
                    <?php elseif ($punch_state['has_out']): ?>
                        <span class="emp-punch-step-tag punch-punctuality-on-time">On time</span>
                    <?php else: ?>
                        <span class="emp-punch-step-tag emp-punch-step-tag-muted">Not yet</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <p class="emp-punch-message"><?php echo htmlspecialchars($status_message); ?></p>

        <div class="emp-punch-meta">
            <span class="emp-punch-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Office <?php echo htmlspecialchars($office_start_label); ?> – <?php echo htmlspecialchars($office_end_label); ?>
                <?php if ($grace_minutes > 0): ?>
                    <span class="emp-punch-meta-sub">· <?php echo (int) $grace_minutes; ?> min grace</span>
                <?php endif; ?>
            </span>
            <?php if ($punch_enabled && $geo_enabled): ?>
                <span class="emp-punch-meta-item <?php echo $geofence ? 'emp-punch-meta-geo' : 'emp-punch-meta-warn'; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?php if ($geofence): ?>
                        Within <?php echo (int) $geofence['radius_meters']; ?> m of office
                    <?php else: ?>
                        Office GPS not configured
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <footer class="emp-punch-footer">
        <form method="POST" action="punch_save.php" class="emp-punch-form" data-punch-form data-geo-required="<?php echo ($geo_enabled && $geofence) ? '1' : '0'; ?>">
            <?php require_once __DIR__ . '/../../includes/csrf_helper.php'; echo csrf_field(); ?>
            <input type="hidden" name="punch_type" value="<?php echo htmlspecialchars($next_action ?? 'in'); ?>">
            <input type="hidden" name="latitude" value="">
            <input type="hidden" name="longitude" value="">
            <input type="hidden" name="location_accuracy" value="">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars(basename($_SERVER['PHP_SELF']) . '?' . ($_SERVER['QUERY_STRING'] ?? '')); ?>">

            <button
                type="submit"
                class="emp-punch-btn <?php echo ($next_action === 'out') ? 'emp-punch-btn-out' : 'emp-punch-btn-in'; ?>"
                <?php echo $can_punch && (!$geo_enabled || $geofence) ? '' : 'disabled'; ?>
                data-punch-submit
            >
                <span class="emp-punch-btn-icon" aria-hidden="true">
                    <?php if ($next_action === 'out'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <?php elseif ($next_action === 'in'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php endif; ?>
                </span>
                <span class="emp-punch-btn-text">
                    <?php if ($next_action === 'out'): ?>
                        Punch out now
                    <?php elseif ($next_action === 'in'): ?>
                        Punch in now
                    <?php else: ?>
                        Done for today
                    <?php endif; ?>
                </span>
            </button>
            <p class="emp-punch-form-note" data-punch-status aria-live="polite"></p>
        </form>
        <a href="punch_history.php" class="emp-punch-history-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            View punch history
        </a>
    </footer>
</section>
<script>
(function () {
    var card = document.getElementById(<?php echo json_encode($punch_card_id); ?>);
    if (!card) return;

    var liveEl = card.querySelector('[data-punch-live-time]');
    if (liveEl) {
        function tickClock() {
            var now = new Date();
            liveEl.textContent = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', second: '2-digit' });
        }
        tickClock();
        window.setInterval(tickClock, 1000);
    }

    var form = card.querySelector('[data-punch-form]');
    var btn = card.querySelector('[data-punch-submit]');
    var statusEl = card.querySelector('[data-punch-status]');
    if (!form || !btn) return;

    var geoRequired = form.getAttribute('data-geo-required') === '1';

    form.addEventListener('submit', function (event) {
        if (btn.disabled) {
            event.preventDefault();
            return;
        }
        if (!geoRequired) {
            btn.classList.add('is-loading');
            return;
        }
        event.preventDefault();
        if (!navigator.geolocation) {
            statusEl.textContent = 'GPS is not supported on this device/browser.';
            return;
        }
        btn.disabled = true;
        btn.classList.add('is-loading');
        statusEl.textContent = 'Getting your location…';
        navigator.geolocation.getCurrentPosition(function (pos) {
            form.querySelector('[name="latitude"]').value = pos.coords.latitude;
            form.querySelector('[name="longitude"]').value = pos.coords.longitude;
            form.querySelector('[name="location_accuracy"]').value = pos.coords.accuracy || '';
            statusEl.textContent = '';
            form.submit();
        }, function () {
            btn.disabled = false;
            btn.classList.remove('is-loading');
            statusEl.textContent = 'Location denied or unavailable. Allow GPS and try again.';
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
})();
</script>
