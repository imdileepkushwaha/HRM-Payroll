<?php

if (!function_exists('is_payroll_period_locked')) {
    require_once __DIR__ . '/payroll_extensions.php';
}

function is_punch_enabled(array $settings): bool
{
    return ($settings['punch_enabled'] ?? '1') === '1';
}

function is_geo_attendance_enabled(array $settings): bool
{
    return ($settings['geo_attendance_enabled'] ?? '1') === '1';
}

function punch_default_geo_radius(array $settings): int
{
    return max(50, (int) ($settings['geo_fence_radius_meters'] ?? 200));
}

function payroll_normalize_office_time(string $value, string $default): string
{
    $value = trim($value);
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
        $hour = max(0, min(23, (int) $m[1]));
        $minute = max(0, min(59, (int) $m[2]));

        return sprintf('%02d:%02d', $hour, $minute);
    }

    if (preg_match('/^(\d{1,2}):(\d{2})$/', $default, $dm)) {
        return sprintf('%02d:%02d', max(0, min(23, (int) $dm[1])), max(0, min(59, (int) $dm[2])));
    }

    return '09:30';
}

function get_office_start_time(array $settings): string
{
    return payroll_normalize_office_time((string) ($settings['office_start_time'] ?? ''), '09:30');
}

function get_office_end_time(array $settings): string
{
    return payroll_normalize_office_time((string) ($settings['office_end_time'] ?? ''), '18:30');
}

function get_late_grace_minutes(array $settings): int
{
    return max(0, min(120, (int) ($settings['late_grace_minutes'] ?? 10)));
}

function payroll_current_clock($conn): array
{
    if (defined('PAYROLL_DB_DRIVER') && PAYROLL_DB_DRIVER === 'mssql') {
        $res = $conn->query("SELECT CONVERT(VARCHAR(19), GETDATE(), 120) AS ts, CONVERT(VARCHAR(10), GETDATE(), 120) AS d");
        if ($res && ($row = $res->fetch_assoc())) {
            return [
                'punched_at' => $row['ts'],
                'punch_date' => $row['d'],
            ];
        }
    }

    return [
        'punched_at' => date('Y-m-d H:i:s'),
        'punch_date' => date('Y-m-d'),
    ];
}

function evaluate_punch_in_punctuality(?string $punched_at, array $settings): array
{
    $parsed = payroll_parse_datetime($punched_at);
    if ($parsed === null) {
        return ['punctuality_status' => null, 'late_by_minutes' => null];
    }

    $tz = new DateTimeZone(date_default_timezone_get());
    $start_time = get_office_start_time($settings);
    $grace = get_late_grace_minutes($settings);
    $date = $parsed->format('Y-m-d');

    $office_start = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $start_time, $tz);
    if ($office_start === false) {
        return ['punctuality_status' => null, 'late_by_minutes' => null];
    }

    $deadline = $office_start->modify('+' . $grace . ' minutes');
    if ($parsed <= $deadline) {
        return ['punctuality_status' => 'on_time', 'late_by_minutes' => 0];
    }

    $late_seconds = $parsed->getTimestamp() - $office_start->getTimestamp();
    $late_minutes = (int) ceil($late_seconds / 60);
    if ($late_minutes < 1) {
        $late_minutes = 1;
    }

    return ['punctuality_status' => 'late', 'late_by_minutes' => $late_minutes];
}

function evaluate_punch_out_punctuality(?string $punched_at, array $settings): array
{
    $parsed = payroll_parse_datetime($punched_at);
    if ($parsed === null) {
        return ['punctuality_status' => null, 'late_by_minutes' => null];
    }

    $tz = new DateTimeZone(date_default_timezone_get());
    $end_time = get_office_end_time($settings);
    $grace = get_late_grace_minutes($settings);
    $date = $parsed->format('Y-m-d');

    $office_end = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $end_time, $tz);
    if ($office_end === false) {
        return ['punctuality_status' => null, 'late_by_minutes' => null];
    }

    if ($parsed >= $office_end) {
        return ['punctuality_status' => 'on_time', 'late_by_minutes' => 0];
    }

    $earliest_ok = $office_end->modify('-' . $grace . ' minutes');
    if ($parsed >= $earliest_ok) {
        return ['punctuality_status' => 'on_time', 'late_by_minutes' => 0];
    }

    $early_seconds = $office_end->getTimestamp() - $parsed->getTimestamp();
    $early_minutes = (int) ceil($early_seconds / 60);
    if ($early_minutes < 1) {
        $early_minutes = 1;
    }

    return ['punctuality_status' => 'early', 'late_by_minutes' => $early_minutes];
}

function format_punctuality_badge(?string $status, $late_minutes, string $punch_type = 'in'): array
{
    if ($status === null || $status === '') {
        return ['label' => '—', 'class' => 'punch-punctuality-na', 'title' => ''];
    }

    if ($punch_type === 'in') {
        if ($status === 'on_time') {
            return ['label' => 'On time', 'class' => 'punch-punctuality-on-time', 'title' => 'Within office start + grace period'];
        }

        if ($status === 'late') {
            $mins = max(1, (int) $late_minutes);

            return [
                'label' => 'Late',
                'class' => 'punch-punctuality-late',
                'title' => 'Late by ' . $mins . ' min',
                'suffix' => $mins . ' min',
            ];
        }
    }

    if ($punch_type === 'out') {
        if ($status === 'on_time') {
            return ['label' => 'On time', 'class' => 'punch-punctuality-on-time', 'title' => 'Left on or after office end minus grace'];
        }

        if ($status === 'early') {
            $mins = max(1, (int) $late_minutes);

            return [
                'label' => 'Early',
                'class' => 'punch-punctuality-early',
                'title' => 'Left early by ' . $mins . ' min',
                'suffix' => $mins . ' min',
            ];
        }
    }

    return ['label' => '—', 'class' => 'punch-punctuality-na', 'title' => ''];
}

function format_punctuality_message(?string $status, $late_minutes, string $punch_type = 'in'): string
{
    if ($status === 'on_time') {
        return ' On time.';
    }

    if ($punch_type === 'in' && $status === 'late') {
        return ' Late by ' . max(1, (int) $late_minutes) . ' min.';
    }

    if ($punch_type === 'out' && $status === 'early') {
        return ' Left early by ' . max(1, (int) $late_minutes) . ' min.';
    }

    return '';
}

function get_branch_geofence($conn, int $branch_id, array $settings): ?array
{
    $stmt = $conn->prepare(
        'SELECT office_latitude, office_longitude, geo_fence_radius_meters FROM branches WHERE id = ?'
    );
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $branch = $stmt->get_result()->fetch_assoc();

    $lat = $branch['office_latitude'] ?? null;
    $lng = $branch['office_longitude'] ?? null;
    $radius = $branch['geo_fence_radius_meters'] ?? null;

    if ($lat === null || $lat === '' || $lng === null || $lng === '') {
        $lat = $settings['office_latitude'] ?? null;
        $lng = $settings['office_longitude'] ?? null;
    }

    if ($lat === null || $lat === '' || $lng === null || $lng === '') {
        return null;
    }

    if ($radius === null || $radius === '') {
        $radius = punch_default_geo_radius($settings);
    }

    return [
        'latitude' => (float) $lat,
        'longitude' => (float) $lng,
        'radius_meters' => max(50, (int) $radius),
    ];
}

function punch_haversine_meters(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earth = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

    return 2 * $earth * asin(min(1, sqrt($a)));
}

function punch_validate_geofence(?float $latitude, ?float $longitude, ?array $geofence): array
{
    if ($geofence === null) {
        return ['ok' => false, 'message' => 'Office location is not configured. Ask your admin to set it in Settings → Punch & Geo.'];
    }

    if ($latitude === null || $longitude === null) {
        return ['ok' => false, 'message' => 'Location is required. Allow GPS access in your browser and try again.'];
    }

    $distance = punch_haversine_meters(
        $latitude,
        $longitude,
        $geofence['latitude'],
        $geofence['longitude']
    );

    $within = $distance <= (float) $geofence['radius_meters'];

    return [
        'ok' => $within,
        'message' => $within
            ? 'Within office geofence.'
            : sprintf(
                'You are %.0f m from the office (allowed: %d m). Move closer to punch.',
                $distance,
                $geofence['radius_meters']
            ),
        'distance_meters' => round($distance, 2),
        'within_geofence' => $within,
    ];
}

function get_employee_punches_for_date($conn, string $emp_id, string $date): array
{
    $stmt = $conn->prepare('
        SELECT * FROM employee_punches
        WHERE emp_id = ? AND punch_date = ?
        ORDER BY punched_at ASC, id ASC
    ');
    $stmt->bind_param('ss', $emp_id, $date);
    $stmt->execute();
    $rows = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function get_employee_punch_state_for_date($conn, string $emp_id, string $date): array
{
    $punches = get_employee_punches_for_date($conn, $emp_id, $date);
    $has_in = false;
    $has_out = false;
    $last_in = null;
    $last_out = null;

    foreach ($punches as $punch) {
        if (($punch['punch_type'] ?? '') === 'in' && ($punch['record_status'] ?? 'ok') === 'ok') {
            $has_in = true;
            $last_in = $punch;
        }
        if (($punch['punch_type'] ?? '') === 'out' && ($punch['record_status'] ?? 'ok') === 'ok') {
            $has_out = true;
            $last_out = $punch;
        }
    }

    $next_action = null;
    if (!$has_in) {
        $next_action = 'in';
    } elseif (!$has_out) {
        $next_action = 'out';
    }

    return [
        'punches' => $punches,
        'has_in' => $has_in,
        'has_out' => $has_out,
        'last_in' => $last_in,
        'last_out' => $last_out,
        'next_action' => $next_action,
        'complete' => $has_in && $has_out,
    ];
}

function payroll_parse_datetime(?string $datetime): ?DateTimeImmutable
{
    if ($datetime === null || trim($datetime) === '') {
        return null;
    }

    $value = trim($datetime);
    if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})/', $value, $m)) {
        $value = $m[1] . ' ' . $m[2];
    }

    $tz = new DateTimeZone(date_default_timezone_get());
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $tz);

    return $parsed ?: null;
}

function format_punch_time(?string $datetime): string
{
    $parsed = payroll_parse_datetime($datetime);
    if ($parsed === null) {
        return '—';
    }

    return $parsed->format('g:i A');
}

function format_punch_datetime(?string $datetime, string $date_format = 'j M Y', string $time_format = 'g:i A'): array
{
    $parsed = payroll_parse_datetime($datetime);
    if ($parsed === null) {
        return ['date' => '—', 'time' => ''];
    }

    return [
        'date' => $parsed->format($date_format),
        'time' => $parsed->format($time_format),
    ];
}

function record_employee_punch($conn, array $employee, array $settings, string $punch_type, ?float $latitude, ?float $longitude, ?float $accuracy): array
{
    if (!is_punch_enabled($settings)) {
        return ['ok' => false, 'message' => 'Attendance punch is disabled by admin.'];
    }

    $emp_id = $employee['emp_id'];
    $branch_id = (int) ($employee['branch_id'] ?? 1);

    if (is_payroll_period_locked($conn, (int) date('Y'), (int) date('n'), $branch_id)) {
        return ['ok' => false, 'message' => 'Payroll period is locked. Punch is not allowed.'];
    }

    $clock = payroll_current_clock($conn);
    $today = $clock['punch_date'];

    if (should_block_punch_on_holiday_weekoff($settings)) {
        $non_working = get_employee_non_working_day_block($conn, $emp_id, $today, $employee);
        if (!empty($non_working['blocked'])) {
            return ['ok' => false, 'message' => $non_working['reason']];
        }
    }

    $state = get_employee_punch_state_for_date($conn, $emp_id, $today);
    $expected = $state['next_action'];

    if ($expected === null) {
        return ['ok' => false, 'message' => 'You have already completed punch in and punch out for today.'];
    }

    if ($punch_type !== $expected) {
        return [
            'ok' => false,
            'message' => $expected === 'in'
                ? 'Please punch in first.'
                : 'Please punch out before punching in again.',
        ];
    }

    $geo_required = is_geo_attendance_enabled($settings);
    $geofence = $geo_required ? get_branch_geofence($conn, $branch_id, $settings) : null;
    $distance = null;
    $within = 1;
    $record_status = 'ok';
    $geo_message = '';

    if ($geo_required) {
        $geo_check = punch_validate_geofence($latitude, $longitude, $geofence);
        $distance = $geo_check['distance_meters'] ?? null;
        $within = !empty($geo_check['within_geofence']) ? 1 : 0;
        $geo_message = $geo_check['message'];
        if (!$geo_check['ok']) {
            $record_status = 'rejected_geofence';
        }
    }

    $clock = payroll_current_clock($conn);
    $punched_at = $clock['punched_at'];
    $today = $clock['punch_date'];
    $branch_settings = get_branch_punch_settings($conn, $branch_id, $settings);
    $punctuality_status = null;
    $late_by_minutes = null;
    if ($record_status === 'ok') {
        if ($punch_type === 'in') {
            $punct_eval = evaluate_punch_in_punctuality($punched_at, $branch_settings);
        } else {
            $punct_eval = evaluate_punch_out_punctuality($punched_at, $branch_settings);
        }
        $punctuality_status = $punct_eval['punctuality_status'];
        $late_by_minutes = $punct_eval['late_by_minutes'];
    }

    $stmt = $conn->prepare('
        INSERT INTO employee_punches (
            emp_id, branch_id, punch_type, punch_date, punched_at,
            latitude, longitude, location_accuracy, distance_meters,
            within_geofence, geo_required, record_status,
            punctuality_status, late_by_minutes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $lat_param = $latitude;
    $lng_param = $longitude;
    $acc_param = $accuracy;
    $dist_param = $distance;
    $geo_req_int = $geo_required ? 1 : 0;
    $late_by_param = $late_by_minutes;

    $stmt->bind_param(
        'sisssddddiissd',
        $emp_id,
        $branch_id,
        $punch_type,
        $today,
        $punched_at,
        $lat_param,
        $lng_param,
        $acc_param,
        $dist_param,
        $within,
        $geo_req_int,
        $record_status,
        $punctuality_status,
        $late_by_param
    );

    if (!$stmt->execute()) {
        return ['ok' => false, 'message' => 'Could not save punch. Please try again.'];
    }

    if ($record_status !== 'ok') {
        return ['ok' => false, 'message' => $geo_message ?: 'Punch rejected.'];
    }

    sync_employee_punch_attendance_for_date($conn, $emp_id, $today, $settings, $employee);

    $label = $punch_type === 'in' ? 'Punch in' : 'Punch out';
    $extra = $geo_required && $distance !== null
        ? sprintf(' (%.0f m from office)', $distance)
        : '';
    $display_time = format_punch_time($punched_at);
    $punct_msg = format_punctuality_message($punctuality_status, $late_by_minutes, $punch_type);

    return [
        'ok' => true,
        'message' => $label . ' recorded at ' . $display_time . $punct_msg . $extra . '.',
        'punctuality_status' => $punctuality_status,
        'late_by_minutes' => $late_by_minutes,
    ];
}

function sync_punch_in_to_attendance($conn, string $emp_id, string $date): void
{
    require_once __DIR__ . '/attendance_helper.php';

    $stmt = $conn->prepare('SELECT status FROM attendance WHERE emp_id = ? AND attendance_date = ?');
    $stmt->bind_param('ss', $emp_id, $date);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $bucket = normalize_status_bucket($existing['status']);
        if (in_array($bucket, ['leave', 'weekoff'], true)) {
            return;
        }
    }

    $status = 'Present';
    $upsert = $conn->prepare('
        INSERT INTO attendance (emp_id, attendance_date, status)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ');
    $upsert->bind_param('sss', $emp_id, $date, $status);
    $upsert->execute();
}

function get_employee_punch_day_punctuality($conn, string $emp_id, string $date): array
{
    $punches = get_employee_punches_for_date($conn, $emp_id, $date);
    $in_status = null;
    $out_status = null;

    foreach ($punches as $punch) {
        if (($punch['record_status'] ?? 'ok') !== 'ok') {
            continue;
        }
        if (($punch['punch_type'] ?? '') === 'in') {
            $in_status = $punch['punctuality_status'] ?? null;
        }
        if (($punch['punch_type'] ?? '') === 'out') {
            $out_status = $punch['punctuality_status'] ?? null;
        }
    }

    return ['in' => $in_status, 'out' => $out_status];
}

function get_recent_employee_punches(
    $conn,
    ?int $branch_id = null,
    int $limit = 100,
    ?int $month = null,
    ?int $year = null,
    array $filters = []
): array {
    $limit = max(1, min(500, $limit));
    $sql = '
        SELECT p.*, e.name AS employee_name
        FROM employee_punches p
        INNER JOIN employees e ON e.emp_id = p.emp_id
        WHERE 1=1
    ';
    $types = '';
    $params = [];

    if ($branch_id !== null) {
        $sql .= ' AND p.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }

    if ($month !== null && $year !== null) {
        $sql .= ' AND YEAR(p.punched_at) = ? AND MONTH(p.punched_at) = ?';
        $types .= 'ii';
        $params[] = $year;
        $params[] = $month;
    }

    $punch_type = strtolower(trim((string) ($filters['punch_type'] ?? '')));
    if ($punch_type === 'in' || $punch_type === 'out') {
        $sql .= ' AND p.punch_type = ?';
        $types .= 's';
        $params[] = $punch_type;
    }

    $punctuality = strtolower(trim((string) ($filters['punctuality'] ?? '')));
    if (in_array($punctuality, ['late', 'early', 'on_time'], true)) {
        $sql .= ' AND p.punctuality_status = ?';
        $types .= 's';
        $params[] = $punctuality;
    }

    $record_status = strtolower(trim((string) ($filters['record_status'] ?? '')));
    if ($record_status === 'ok') {
        $sql .= " AND p.record_status = 'ok'";
    } elseif ($record_status === 'rejected') {
        $sql .= " AND p.record_status <> 'ok'";
    }

    $emp_id = trim((string) ($filters['emp_id'] ?? ''));
    if ($emp_id !== '') {
        $sql .= ' AND p.emp_id = ?';
        $types .= 's';
        $params[] = $emp_id;
    }

    $sql .= ' ORDER BY p.punched_at DESC, p.id DESC LIMIT ' . $limit;

    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }

    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

require_once __DIR__ . '/punch_policy_helper.php';
