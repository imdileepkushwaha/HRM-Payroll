<?php

function get_branch_punch_settings($conn, int $branch_id, array $settings): array
{
    $merged = $settings;
    $stmt = $conn->prepare(
        'SELECT office_start_time, office_end_time, late_grace_minutes FROM branches WHERE id = ?'
    );
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $branch = $stmt->get_result()->fetch_assoc() ?: [];

    if (!empty($branch['office_start_time'])) {
        $merged['office_start_time'] = $branch['office_start_time'];
    }
    if (!empty($branch['office_end_time'])) {
        $merged['office_end_time'] = $branch['office_end_time'];
    }
    if ($branch['late_grace_minutes'] !== null && $branch['late_grace_minutes'] !== '') {
        $merged['late_grace_minutes'] = (string) (int) $branch['late_grace_minutes'];
    }

    return $merged;
}

function punch_policy_enabled(array $settings, string $key, string $default = '1'): bool
{
    return ($settings[$key] ?? $default) === '1';
}

function get_missing_punch_out_status(array $settings): string
{
    $value = strtolower(trim((string) ($settings['missing_punch_out_status'] ?? 'half_day')));

    return $value === 'absent' ? 'absent' : 'half_day';
}

function get_employee_punch_summaries_for_period($conn, string $emp_id, int $year, int $month): array
{
    $stmt = $conn->prepare('
        SELECT punch_date, punch_type, punctuality_status, punched_at, record_status
        FROM employee_punches
        WHERE emp_id = ?
          AND YEAR(punch_date) = ?
          AND MONTH(punch_date) = ?
        ORDER BY punch_date ASC, punched_at ASC, id ASC
    ');
    $stmt->bind_param('sii', $emp_id, $year, $month);
    $stmt->execute();
    $res = $stmt->get_result();

    $by_date = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (($row['record_status'] ?? 'ok') !== 'ok') {
                continue;
            }
            $date_key = substr((string) ($row['punch_date'] ?? ''), 0, 10);
            if ($date_key === '') {
                continue;
            }
            if (!isset($by_date[$date_key])) {
                $by_date[$date_key] = [
                    'in' => null,
                    'out' => null,
                    'has_in' => false,
                    'has_out' => false,
                    'in_at' => null,
                    'out_at' => null,
                ];
            }
            $type = $row['punch_type'] ?? '';
            if ($type === 'in') {
                $by_date[$date_key]['in'] = $row['punctuality_status'] ?? null;
                $by_date[$date_key]['has_in'] = true;
                $by_date[$date_key]['in_at'] = $row['punched_at'] ?? null;
            } elseif ($type === 'out') {
                $by_date[$date_key]['out'] = $row['punctuality_status'] ?? null;
                $by_date[$date_key]['has_out'] = true;
                $by_date[$date_key]['out_at'] = $row['punched_at'] ?? null;
            }
        }
    }

    return $by_date;
}

function resolve_punch_attendance_status(array $summary, array $settings, bool $day_closed): ?string
{
    $has_in = !empty($summary['has_in']);
    $has_out = !empty($summary['has_out']);
    $in = $summary['in'] ?? null;
    $out = $summary['out'] ?? null;

    if ($has_in && $has_out) {
        if ($in === 'late' && $out === 'early') {
            return 'Half day';
        }
        if (punch_policy_enabled($settings, 'half_day_on_late_in') && $in === 'late') {
            return 'Half day';
        }
        if (punch_policy_enabled($settings, 'half_day_on_early_out') && $out === 'early') {
            return 'Half day';
        }

        return 'Present';
    }

    if ($has_in && !$has_out && $day_closed) {
        return get_missing_punch_out_status($settings) === 'absent' ? 'Absent' : 'Half day';
    }

    if ($has_in && !$has_out && !$day_closed) {
        if (punch_policy_enabled($settings, 'half_day_on_late_in') && $in === 'late') {
            return 'Half day';
        }

        return 'Present';
    }

    return null;
}

function is_punch_half_day_summary(array $summary, array $settings): bool
{
    $status = resolve_punch_attendance_status($summary, $settings, true);

    return $status === 'Half day';
}

function punch_attendance_status_to_bucket(string $status): string
{
    require_once __DIR__ . '/attendance_helper.php';

    return normalize_status_bucket($status);
}

function punch_upsert_attendance_status($conn, string $emp_id, string $date, string $status): bool
{
    require_once __DIR__ . '/attendance_helper.php';

    $stmt = $conn->prepare('SELECT status FROM attendance WHERE emp_id = ? AND attendance_date = ?');
    $stmt->bind_param('ss', $emp_id, $date);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $bucket = normalize_status_bucket($existing['status']);
        if (in_array($bucket, ['leave', 'weekoff'], true)) {
            return false;
        }
    }

    $upsert = $conn->prepare('
        INSERT INTO attendance (emp_id, attendance_date, status)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ');
    $upsert->bind_param('sss', $emp_id, $date, $status);

    return $upsert->execute();
}

function is_punch_day_closed(string $date, array $settings): bool
{
    if ($date < date('Y-m-d')) {
        return true;
    }
    if ($date > date('Y-m-d')) {
        return false;
    }

    $end_time = get_office_end_time($settings);

    return date('H:i') >= $end_time;
}

function sync_punch_overtime_for_date($conn, string $emp_id, string $date, array $summary, array $settings): void
{
    if (!punch_policy_enabled($settings, 'punch_sync_overtime')) {
        return;
    }
    if (empty($summary['has_in']) || empty($summary['has_out'])) {
        return;
    }

    $in_dt = payroll_parse_datetime($summary['in_at'] ?? null);
    $out_dt = payroll_parse_datetime($summary['out_at'] ?? null);
    if ($in_dt === null || $out_dt === null || $out_dt <= $in_dt) {
        return;
    }

    $work_hours = ($out_dt->getTimestamp() - $in_dt->getTimestamp()) / 3600;
    $standard = (float) ($settings['overtime_hours_per_day'] ?? 8);
    $overtime = max(0, round($work_hours - $standard, 2));
    if ($overtime <= 0) {
        return;
    }

    $stmt = $conn->prepare('
        UPDATE attendance SET overtime_hours = ?
        WHERE emp_id = ? AND attendance_date = ?
    ');
    $stmt->bind_param('dss', $overtime, $emp_id, $date);
    $stmt->execute();
}

function sync_punch_attendance_for_date(
    $conn,
    string $emp_id,
    string $date,
    array $settings,
    array $summary,
    bool $is_holiday,
    bool $is_weekoff
): void {
    $day_closed = is_punch_day_closed($date, $settings);
    $has_punch = !empty($summary['has_in']) || !empty($summary['has_out']);

    if ($has_punch) {
        $status = resolve_punch_attendance_status($summary, $settings, $day_closed);
        if ($status !== null) {
            punch_upsert_attendance_status($conn, $emp_id, $date, $status);
            if ($day_closed && !empty($summary['has_in']) && !empty($summary['has_out'])) {
                sync_punch_overtime_for_date($conn, $emp_id, $date, $summary, $settings);
            }
        }

        return;
    }

    if (
        $day_closed
        && punch_policy_enabled($settings, 'auto_absent_no_punch')
        && !$is_holiday
        && !$is_weekoff
    ) {
        require_once __DIR__ . '/attendance_helper.php';
        $stmt = $conn->prepare('SELECT status FROM attendance WHERE emp_id = ? AND attendance_date = ?');
        $stmt->bind_param('ss', $emp_id, $date);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            $bucket = normalize_status_bucket($existing['status']);
            if (!in_array($bucket, ['leave', 'weekoff', 'present', 'half'], true)) {
                return;
            }
            if (in_array($bucket, ['present', 'half', 'leave', 'weekoff'], true)) {
                return;
            }
        }
        punch_upsert_attendance_status($conn, $emp_id, $date, 'Absent');
    }
}

function sync_employee_punch_attendance_for_period(
    $conn,
    string $emp_id,
    int $year,
    int $month,
    array $settings,
    array $employee = []
): void {
    if (!is_punch_enabled($settings)) {
        return;
    }

    require_once __DIR__ . '/weekoff_roster_helper.php';

    $branch_id = (int) ($employee['branch_id'] ?? 1);
    $branch_settings = get_branch_punch_settings($conn, $branch_id, $settings);
    $holidays_map = get_holidays_for_month($conn, $year, $month, $branch_id);
    $weekoff_dates = get_employee_weekoff_dates($conn, $emp_id, $year, $month);
    $weekoff_lookup = array_fill_keys($weekoff_dates, true);
    $summaries = get_employee_punch_summaries_for_period($conn, $emp_id, $year, $month);

    $days_in_month = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    $today = date('Y-m-d');

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf('%d-%02d-%02d', $year, $month, $day);
        if ($date > $today) {
            continue;
        }

        $summary = $summaries[$date] ?? [
            'in' => null,
            'out' => null,
            'has_in' => false,
            'has_out' => false,
            'in_at' => null,
            'out_at' => null,
        ];
        $is_holiday = isset($holidays_map[$date]);
        $is_weekoff = isset($weekoff_lookup[$date]);

        sync_punch_attendance_for_date($conn, $emp_id, $date, $branch_settings, $summary, $is_holiday, $is_weekoff);
    }
}

function get_employee_punch_half_day_dates_for_period($conn, string $emp_id, int $year, int $month, array $settings = [], array $employee = []): array
{
    if ($settings === []) {
        require_once __DIR__ . '/settings_helper.php';
        $settings = get_all_settings($conn);
    }

    $branch_id = (int) ($employee['branch_id'] ?? 0);
    if ($branch_id > 0) {
        $settings = get_branch_punch_settings($conn, $branch_id, $settings);
    }

    $summaries = get_employee_punch_summaries_for_period($conn, $emp_id, $year, $month);
    $half_days = [];

    foreach ($summaries as $date_key => $summary) {
        if (is_punch_half_day_summary($summary, $settings)) {
            $half_days[] = $date_key;
            continue;
        }
        if (is_punch_day_closed($date_key, $settings)) {
            $status = resolve_punch_attendance_status($summary, $settings, true);
            if ($status === 'Half day') {
                $half_days[] = $date_key;
            }
        }
    }

    $stmt = $conn->prepare("
        SELECT attendance_date FROM attendance
        WHERE emp_id = ? AND YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?
          AND LOWER(status) IN ('half day', 'halfday', 'half-day', 'hd', 'h')
    ");
    $stmt->bind_param('sii', $emp_id, $year, $month);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $date_key = substr((string) ($row['attendance_date'] ?? ''), 0, 10);
            if ($date_key !== '' && !in_array($date_key, $half_days, true)) {
                $half_days[] = $date_key;
            }
        }
    }

    sort($half_days);

    return $half_days;
}

function count_employee_late_punches_for_period($conn, string $emp_id, int $year, int $month): int
{
    $summaries = get_employee_punch_summaries_for_period($conn, $emp_id, $year, $month);
    $count = 0;
    foreach ($summaries as $summary) {
        if (($summary['in'] ?? '') === 'late') {
            $count++;
        }
    }

    return $count;
}

function calculate_late_punch_penalty_days(int $late_count, array $settings): float
{
    $threshold = (int) ($settings['late_count_for_half_day'] ?? 0);
    if ($threshold < 1 || $late_count < $threshold) {
        return 0.0;
    }

    $half_credit = (float) ($settings['half_day_credit'] ?? 0.5);

    return floor($late_count / $threshold) * $half_credit;
}

function calculate_punch_work_hours(?string $in_at, ?string $out_at): ?float
{
    $in_dt = payroll_parse_datetime($in_at);
    $out_dt = payroll_parse_datetime($out_at);
    if ($in_dt === null || $out_dt === null || $out_dt <= $in_dt) {
        return null;
    }

    return round(($out_dt->getTimestamp() - $in_dt->getTimestamp()) / 3600, 2);
}

function format_work_hours_label(?float $hours): string
{
    if ($hours === null) {
        return '—';
    }

    $whole_hours = (int) floor($hours);
    $minutes = (int) round(($hours - $whole_hours) * 60);
    if ($minutes === 60) {
        $whole_hours++;
        $minutes = 0;
    }

    return $whole_hours . 'h ' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . 'm';
}

function get_employee_punch_history_for_period($conn, string $emp_id, int $year, int $month, array $settings = [], array $employee = []): array
{
    $branch_id = (int) ($employee['branch_id'] ?? 0);
    if ($branch_id > 0) {
        $settings = get_branch_punch_settings($conn, $branch_id, $settings);
    }

    $summaries = get_employee_punch_summaries_for_period($conn, $emp_id, $year, $month);
    $rows = [];

    foreach ($summaries as $date => $summary) {
        $work_hours = calculate_punch_work_hours($summary['in_at'] ?? null, $summary['out_at'] ?? null);
        $attendance_status = resolve_punch_attendance_status($summary, $settings, is_punch_day_closed($date, $settings));
        $rows[] = [
            'date' => $date,
            'in_at' => $summary['in_at'] ?? null,
            'out_at' => $summary['out_at'] ?? null,
            'in_status' => $summary['in'] ?? null,
            'out_status' => $summary['out'] ?? null,
            'work_hours' => $work_hours,
            'attendance_status' => $attendance_status,
        ];
    }

    usort($rows, static function ($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    return $rows;
}

function get_branch_punch_report_for_period($conn, int $year, int $month, ?int $branch_id = null): array
{
    require_once __DIR__ . '/branch_helper.php';

    $sql = "
        SELECT e.emp_id, e.name, e.branch_id,
            SUM(CASE WHEN p.punch_type = 'in' AND p.punctuality_status = 'late' AND p.record_status = 'ok' THEN 1 ELSE 0 END) AS late_count,
            SUM(CASE WHEN p.punch_type = 'out' AND p.punctuality_status = 'early' AND p.record_status = 'ok' THEN 1 ELSE 0 END) AS early_count,
            SUM(CASE WHEN p.punch_type = 'in' AND p.record_status = 'ok' THEN 1 ELSE 0 END) AS punch_in_count,
            SUM(CASE WHEN p.punch_type = 'out' AND p.record_status = 'ok' THEN 1 ELSE 0 END) AS punch_out_count
        FROM employees e
        LEFT JOIN employee_punches p
            ON p.emp_id = e.emp_id
           AND YEAR(p.punch_date) = ?
           AND MONTH(p.punch_date) = ?
        WHERE e.is_active = 1
    ";
    $types = 'ii';
    $params = [$year, $month];

    if ($branch_id !== null) {
        $sql .= ' AND e.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }

    $sql .= ' GROUP BY e.emp_id, e.name, e.branch_id ORDER BY e.name ASC';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function sync_employee_punch_attendance_for_date(
    $conn,
    string $emp_id,
    string $date,
    array $settings,
    array $employee = []
): void {
    if (!function_exists('is_punch_enabled') || !is_punch_enabled($settings)) {
        return;
    }

    require_once __DIR__ . '/weekoff_roster_helper.php';

    $branch_id = (int) ($employee['branch_id'] ?? 1);
    $branch_settings = get_branch_punch_settings($conn, $branch_id, $settings);
    $year = (int) date('Y', strtotime($date));
    $month = (int) date('n', strtotime($date));
    $holidays_map = get_holidays_for_month($conn, $year, $month, $branch_id);
    $weekoff_dates = get_employee_weekoff_dates($conn, $emp_id, $year, $month);
    $weekoff_lookup = array_fill_keys($weekoff_dates, true);
    $summaries = get_employee_punch_summaries_for_period($conn, $emp_id, $year, $month);
    $summary = $summaries[$date] ?? [
        'in' => null,
        'out' => null,
        'has_in' => false,
        'has_out' => false,
        'in_at' => null,
        'out_at' => null,
    ];

    sync_punch_attendance_for_date(
        $conn,
        $emp_id,
        $date,
        $branch_settings,
        $summary,
        isset($holidays_map[$date]),
        isset($weekoff_lookup[$date])
    );
}

function should_block_punch_on_holiday_weekoff(array $settings): bool
{
    return ($settings['block_punch_on_holiday_weekoff'] ?? '1') === '1';
}

function get_employee_non_working_day_block($conn, string $emp_id, string $date, array $employee): array
{
    require_once __DIR__ . '/payroll_extensions.php';
    require_once __DIR__ . '/weekoff_roster_helper.php';

    $branch_id = (int) ($employee['branch_id'] ?? 1);
    $year = (int) date('Y', strtotime($date));
    $month = (int) date('n', strtotime($date));
    $holidays_map = get_holidays_for_month($conn, $year, $month, $branch_id);

    if (isset($holidays_map[$date])) {
        $holiday_name = is_array($holidays_map[$date])
            ? ($holidays_map[$date]['name'] ?? 'Holiday')
            : (string) $holidays_map[$date];

        return [
            'blocked' => true,
            'kind' => 'holiday',
            'reason' => 'Today is a holiday (' . $holiday_name . '). Punch is not allowed.',
        ];
    }

    $weekoff_dates = get_employee_weekoff_dates($conn, $emp_id, $year, $month);
    if (in_array($date, $weekoff_dates, true)) {
        return [
            'blocked' => true,
            'kind' => 'weekoff',
            'reason' => 'Today is your week off. Punch is not allowed.',
        ];
    }

    return ['blocked' => false, 'kind' => '', 'reason' => ''];
}

function get_branch_punch_day_stats($conn, ?int $branch_id, string $date): array
{
    $sql = "
        SELECT
            SUM(CASE WHEN p.punch_type = 'in' AND p.record_status = 'ok' THEN 1 ELSE 0 END) AS punch_in_count,
            SUM(CASE WHEN p.punch_type = 'out' AND p.record_status = 'ok' THEN 1 ELSE 0 END) AS punch_out_count,
            SUM(CASE WHEN p.punch_type = 'in' AND p.record_status = 'ok' AND p.punctuality_status = 'late' THEN 1 ELSE 0 END) AS late_in_count,
            SUM(CASE WHEN p.punch_type = 'out' AND p.record_status = 'ok' AND p.punctuality_status = 'early' THEN 1 ELSE 0 END) AS early_out_count,
            SUM(CASE WHEN p.record_status <> 'ok' THEN 1 ELSE 0 END) AS rejected_count,
            COUNT(DISTINCT CASE WHEN p.record_status = 'ok' THEN p.emp_id END) AS employee_count
        FROM employee_punches p
        INNER JOIN employees e ON e.emp_id = p.emp_id
        WHERE p.punch_date = ?
    ";
    $types = 's';
    $params = [$date];

    if ($branch_id !== null) {
        $sql .= ' AND p.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];

    return [
        'punch_in_count' => (int) ($row['punch_in_count'] ?? 0),
        'punch_out_count' => (int) ($row['punch_out_count'] ?? 0),
        'late_in_count' => (int) ($row['late_in_count'] ?? 0),
        'early_out_count' => (int) ($row['early_out_count'] ?? 0),
        'rejected_count' => (int) ($row['rejected_count'] ?? 0),
        'employee_count' => (int) ($row['employee_count'] ?? 0),
    ];
}
