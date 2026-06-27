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
$geofence = $geo_enabled ? get_branch_geofence($conn, $branch_id, $settings) : null;
$period_locked = is_payroll_period_locked($conn, (int) date('Y'), (int) date('n'), $branch_id);
$non_working_block = should_block_punch_on_holiday_weekoff($settings)
    ? get_employee_non_working_day_block($conn, $employee['emp_id'], $today, $employee)
    : ['blocked' => false, 'reason' => ''];
$punch_blocked_today = !empty($non_working_block['blocked']);
$can_punch = $punch_enabled && !$period_locked && !$punch_blocked_today && $punch_state['next_action'] !== null;
$next_action = $punch_state['next_action'];
$punch_card_id = 'empPunchCard_' . substr(md5($employee['emp_id'] . $today), 0, 8);
?>
<section class="emp-punch-card" id="<?php echo htmlspecialchars($punch_card_id); ?>" aria-label="Today's attendance punch">
    <div class="emp-punch-card-head">
        <div>
            <p class="emp-punch-eyebrow">Today · <?php echo htmlspecialchars(date('l, j M Y')); ?></p>
            <h2 class="emp-punch-title">Attendance punch</h2>
            <p class="emp-punch-sub">
                <?php if (!$punch_enabled): ?>
                    Punch is turned off by admin.
                <?php elseif ($period_locked): ?>
                    Payroll period is locked — punch disabled.
                <?php elseif ($punch_blocked_today): ?>
                    <?php echo htmlspecialchars($non_working_block['reason'] ?? 'Punch is not allowed today.'); ?>
                <?php elseif ($punch_state['complete']): ?>
                    You have completed punch in and punch out for today.
                <?php elseif ($next_action === 'in'): ?>
                    Tap punch in when you arrive<?php echo $geo_enabled ? ' at the office' : ''; ?>.
                <?php else: ?>
                    Tap punch out when you leave.
                <?php endif; ?>
            </p>
        </div>
        <div class="emp-punch-status-badges">
            <?php
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
            ?>
            <div class="emp-punch-badge <?php echo $punch_state['has_in'] ? 'done' : 'pending'; ?>">
                <span class="emp-punch-badge-label">Punch in</span>
                <strong class="emp-punch-badge-time"><?php echo htmlspecialchars($in_time); ?></strong>
                <?php if ($in_punct && $in_punct['label'] !== '—'): ?>
                    <span class="emp-punch-badge-tag <?php echo htmlspecialchars($in_punct['class']); ?>">
                        <?php echo htmlspecialchars($in_punct['label']); ?>
                        <?php if (!empty($in_punct['suffix'])): ?>
                            <span class="emp-punch-badge-tag-meta"><?php echo htmlspecialchars($in_punct['suffix']); ?></span>
                        <?php endif; ?>
                    </span>
                <?php elseif ($punch_state['has_in']): ?>
                    <span class="emp-punch-badge-tag punch-punctuality-on-time">On time</span>
                <?php else: ?>
                    <span class="emp-punch-badge-tag emp-punch-badge-tag-muted">Not punched</span>
                <?php endif; ?>
            </div>
            <div class="emp-punch-badge <?php echo $punch_state['has_out'] ? 'done' : 'pending'; ?>">
                <span class="emp-punch-badge-label">Punch out</span>
                <strong class="emp-punch-badge-time"><?php echo htmlspecialchars($out_time); ?></strong>
                <?php if ($out_punct && $out_punct['label'] !== '—'): ?>
                    <span class="emp-punch-badge-tag <?php echo htmlspecialchars($out_punct['class']); ?>">
                        <?php echo htmlspecialchars($out_punct['label']); ?>
                        <?php if (!empty($out_punct['suffix'])): ?>
                            <span class="emp-punch-badge-tag-meta"><?php echo htmlspecialchars($out_punct['suffix']); ?></span>
                        <?php endif; ?>
                    </span>
                <?php elseif ($punch_state['has_out']): ?>
                    <span class="emp-punch-badge-tag punch-punctuality-on-time">On time</span>
                <?php else: ?>
                    <span class="emp-punch-badge-tag emp-punch-badge-tag-muted">Not punched</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($punch_enabled && $geo_enabled): ?>
        <p class="emp-punch-geo-hint">
            <?php if ($geofence): ?>
                Geo attendance is on. Allowed within <strong><?php echo (int) $geofence['radius_meters']; ?> m</strong> of your branch office.
            <?php else: ?>
                <span class="emp-punch-warn">Office GPS not configured — ask admin to set latitude/longitude in Settings → Punch &amp; Geo.</span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

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
            <?php if ($next_action === 'out'): ?>
                Punch out
            <?php elseif ($next_action === 'in'): ?>
                Punch in
            <?php else: ?>
                Done for today
            <?php endif; ?>
        </button>
        <p class="emp-punch-form-note" data-punch-status aria-live="polite"></p>
    </form>
</section>
<script>
(function () {
    var card = document.getElementById(<?php echo json_encode($punch_card_id); ?>);
    if (!card) return;
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
            return;
        }
        event.preventDefault();
        if (!navigator.geolocation) {
            statusEl.textContent = 'GPS is not supported on this device/browser.';
            return;
        }
        btn.disabled = true;
        statusEl.textContent = 'Getting your location…';
        navigator.geolocation.getCurrentPosition(function (pos) {
            form.querySelector('[name="latitude"]').value = pos.coords.latitude;
            form.querySelector('[name="longitude"]').value = pos.coords.longitude;
            form.querySelector('[name="location_accuracy"]').value = pos.coords.accuracy || '';
            statusEl.textContent = '';
            form.submit();
        }, function (err) {
            btn.disabled = false;
            statusEl.textContent = 'Location denied or unavailable. Allow GPS and try again.';
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
})();
</script>
