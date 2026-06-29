<?php

require_once __DIR__ . '/payroll_extensions.php';
require_once __DIR__ . '/employee_portal_helper.php';
require_once __DIR__ . '/branch_helper.php';
require_once __DIR__ . '/employee_helper.php';

function hrm_announcements_table_exists($conn): bool
{
    if (function_exists('payroll_ext_table_exists')) {
        return payroll_ext_table_exists($conn, 'announcements');
    }
    $r = $conn->query("SHOW TABLES LIKE 'announcements'");
    return $r && $r->num_rows > 0;
}

function get_admin_announcements($conn, ?int $branch_id = null): array
{
    if (!hrm_announcements_table_exists($conn)) {
        return [];
    }
    $sql = 'SELECT a.*, b.name AS branch_name FROM announcements a LEFT JOIN branches b ON b.id = a.branch_id';
    if ($branch_id !== null) {
        $sql .= ' WHERE a.branch_id IS NULL OR a.branch_id = ?';
        $sql .= ' ORDER BY a.is_pinned DESC, a.created_at DESC';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $branch_id);
    } else {
        $sql .= ' ORDER BY a.is_pinned DESC, a.created_at DESC';
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    return payroll_fetch_all_assoc($stmt->get_result());
}

function get_employee_portal_announcements($conn, int $branch_id, int $limit = 5): array
{
    if (!hrm_announcements_table_exists($conn)) {
        return [];
    }
    $today = date('Y-m-d');
    $limit = max(1, min(20, $limit));
    $stmt = $conn->prepare("
        SELECT id, title, body, is_pinned, created_at, expires_at
        FROM announcements
        WHERE is_active = 1
          AND (branch_id IS NULL OR branch_id = ?)
          AND (expires_at IS NULL OR expires_at >= ?)
        ORDER BY is_pinned DESC, created_at DESC
        LIMIT {$limit}
    ");
    $stmt->bind_param('is', $branch_id, $today);
    $stmt->execute();
    return payroll_fetch_all_assoc($stmt->get_result());
}

function save_announcement($conn, array $data, string $username): array
{
    if (!hrm_announcements_table_exists($conn)) {
        return ['ok' => false, 'message' => 'Announcements are not available yet. Run database setup.'];
    }

    $id = (int) ($data['id'] ?? 0);
    $title = trim((string) ($data['title'] ?? ''));
    $body = trim((string) ($data['body'] ?? ''));
    $branch_raw = $data['branch_id'] ?? '';
    $branch_id = ($branch_raw === '' || $branch_raw === '0' || $branch_raw === null) ? null : (int) $branch_raw;
    $is_active = !empty($data['is_active']) ? 1 : 0;
    $is_pinned = !empty($data['is_pinned']) ? 1 : 0;
    $expires_raw = trim((string) ($data['expires_at'] ?? ''));
    $expires_at = ($expires_raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires_raw)) ? $expires_raw : null;

    if ($title === '') {
        return ['ok' => false, 'message' => 'Title is required.'];
    }
    if ($body === '') {
        return ['ok' => false, 'message' => 'Message body is required.'];
    }
    if (strlen($title) > 200) {
        return ['ok' => false, 'message' => 'Title must be 200 characters or fewer.'];
    }

    if ($id > 0) {
        $stmt = $conn->prepare('
            UPDATE announcements
            SET branch_id = ?, title = ?, body = ?, is_active = ?, is_pinned = ?, expires_at = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->bind_param('issiisi', $branch_id, $title, $body, $is_active, $is_pinned, $expires_at, $id);
        $stmt->execute();
        return ['ok' => true, 'message' => 'Announcement updated.'];
    }

    $stmt = $conn->prepare('
        INSERT INTO announcements (branch_id, title, body, is_active, is_pinned, expires_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->bind_param('issiiss', $branch_id, $title, $body, $is_active, $is_pinned, $expires_at, $username);
    $stmt->execute();
    return ['ok' => true, 'message' => 'Announcement published.'];
}

function delete_announcement($conn, int $id): bool
{
    if ($id <= 0 || !hrm_announcements_table_exists($conn)) {
        return false;
    }
    $stmt = $conn->prepare('DELETE FROM announcements WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

function get_leave_balance_rows_for_branch($conn, array $settings, ?int $branch_id = null): array
{
    $sql = 'SELECT e.emp_id, e.name, e.department, e.designation, e.is_active, e.branch_id FROM employees e WHERE 1=1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND e.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    $sql .= ' ORDER BY e.is_active DESC, e.name ASC';
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $employees = payroll_fetch_all_assoc($stmt->get_result());

    $quota_codes = leave_type_codes_with_balance($conn, $settings);
    $leave_types = get_leave_types($conn);
    $rows = [];

    foreach ($employees as $emp) {
        $balances = get_employee_leave_balances($conn, $emp['emp_id'], $settings);
        $pending = [];
        foreach ($quota_codes as $code) {
            $pending[$code] = get_pending_leave_days_by_type($conn, $emp['emp_id'], $code);
        }
        $rows[] = [
            'employee' => $emp,
            'balances' => $balances,
            'pending' => $pending,
            'leave_types' => $leave_types,
        ];
    }

    return ['quota_codes' => $quota_codes, 'leave_types' => $leave_types, 'rows' => $rows];
}

function get_team_calendar_month_data($conn, int $year, int $month, ?int $branch_id, array $settings): array
{
    $start = sprintf('%d-%02d-01', $year, $month);
    $end = date('Y-m-t', strtotime($start));
    $days_in_month = (int) date('t', strtotime($start));

    $holidays = [];
    if ($branch_id !== null) {
        $holidays = get_holidays_for_month($conn, $year, $month, $branch_id);
    }

    $leave_by_date = [];
    $sql = "
        SELECT r.emp_id, r.leave_type, r.from_date, r.to_date, e.name AS employee_name
        FROM employee_leave_requests r
        INNER JOIN employees e ON e.emp_id = r.emp_id
        WHERE r.request_status = 'approved'
          AND r.to_date >= ?
          AND r.from_date <= ?
    ";
    $types = 'ss';
    $params = [$start, $end];
    if ($branch_id !== null) {
        $sql .= ' AND r.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $leave_requests = payroll_fetch_all_assoc($stmt->get_result());

    foreach ($leave_requests as $req) {
        foreach (leave_request_dates_in_range($req['from_date'], $req['to_date']) as $date) {
            if ($date < $start || $date > $end) {
                continue;
            }
            $leave_by_date[$date][] = [
                'emp_id' => $req['emp_id'],
                'name' => $req['employee_name'],
                'leave_type' => $req['leave_type'],
            ];
        }
    }

    $today_on_leave = [];
  $today = date('Y-m-d');
    if (isset($leave_by_date[$today])) {
        $today_on_leave = $leave_by_date[$today];
    }

    return [
        'year' => $year,
        'month' => $month,
        'days_in_month' => $days_in_month,
        'start' => $start,
        'holidays' => $holidays,
        'leave_by_date' => $leave_by_date,
        'today_on_leave' => $today_on_leave,
        'today_holiday' => $holidays[$today] ?? null,
    ];
}

function get_attendance_summary_report($conn, int $year, int $month, array $settings, ?int $branch_id = null): array
{
    $branch_filter = branch_employees_sql('e');
    $sql = 'SELECT e.* FROM employees e WHERE e.is_active = 1';
    if ($branch_id !== null) {
        $sql .= ' AND e.branch_id = ?';
    } elseif ($branch_filter['sql'] !== '') {
        $sql .= $branch_filter['sql'];
    }
    $sql .= ' ORDER BY e.name ASC';
    $stmt = $conn->prepare($sql);
    if ($branch_id !== null) {
        $stmt->bind_param('i', $branch_id);
    } elseif ($branch_filter['types'] !== '') {
        bind_branch_stmt_params($stmt, $branch_filter['types'], $branch_filter['params']);
    }
    $stmt->execute();
    $employees = payroll_fetch_all_assoc($stmt->get_result());

    $rows = [];
    $totals = ['present' => 0, 'absent' => 0, 'half' => 0, 'leave' => 0, 'paid_days' => 0.0];

    foreach ($employees as $emp) {
        $stats = get_attendance_stats_extended($conn, $emp['emp_id'], $year, $month, $settings);
        $working = (int) get_working_days_per_month($settings);
        $marked = (int) ($stats['total_records'] ?? 0);
        $attendance_pct = $working > 0 ? min(100, round(((float) $stats['paid_days'] / $working) * 100, 1)) : 0;

        $rows[] = [
            'employee' => $emp,
            'stats' => $stats,
            'attendance_pct' => $attendance_pct,
            'marked_days' => $marked,
        ];

        $totals['present'] += (int) $stats['present_days'];
        $totals['absent'] += (int) $stats['absent_days'];
        $totals['half'] += (int) $stats['half_days'];
        $totals['leave'] += (int) $stats['leave_days'];
        $totals['paid_days'] += (float) $stats['paid_days'];
    }

    return ['rows' => $rows, 'totals' => $totals, 'working_days' => (int) get_working_days_per_month($settings)];
}

function get_payroll_summary_by_department($conn, int $year, int $month, array $settings, ?int $branch_id = null): array
{
    $branch_filter = branch_employees_sql('e');
    $sql = 'SELECT e.* FROM employees e WHERE e.is_active = 1';
    if ($branch_id !== null) {
        $sql .= ' AND e.branch_id = ?';
    } elseif ($branch_filter['sql'] !== '') {
        $sql .= $branch_filter['sql'];
    }
    $sql .= ' ORDER BY e.department ASC, e.name ASC';
    $stmt = $conn->prepare($sql);
    if ($branch_id !== null) {
        $stmt->bind_param('i', $branch_id);
    } elseif ($branch_filter['types'] !== '') {
        bind_branch_stmt_params($stmt, $branch_filter['types'], $branch_filter['params']);
    }
    $stmt->execute();
    $employees = payroll_fetch_all_assoc($stmt->get_result());

    $by_dept = [];
    $grand_net = 0.0;
    $grand_gross = 0.0;
    $employee_count = 0;

    foreach ($employees as $emp) {
        $stats = get_attendance_stats_extended($conn, $emp['emp_id'], $year, $month, $settings);
        if ($stats['total_records'] === 0) {
            continue;
        }
        $salary = calculate_employee_salary_full($conn, $emp, $year, $month, $settings);
        $dept = trim((string) ($emp['department'] ?? '')) ?: 'General';
        if (!isset($by_dept[$dept])) {
            $by_dept[$dept] = [
                'department' => $dept,
                'employees' => 0,
                'gross' => 0.0,
                'net' => 0.0,
                'paid_days' => 0.0,
            ];
        }
        $by_dept[$dept]['employees']++;
        $by_dept[$dept]['gross'] += (float) ($salary['gross_salary'] ?? $salary['earned_salary'] ?? 0);
        $by_dept[$dept]['net'] += (float) $salary['net_salary'];
        $by_dept[$dept]['paid_days'] += (float) $salary['paid_days'];
        $grand_net += (float) $salary['net_salary'];
        $grand_gross += (float) ($salary['gross_salary'] ?? $salary['earned_salary'] ?? 0);
        $employee_count++;
    }

    ksort($by_dept);

    return [
        'departments' => array_values($by_dept),
        'grand_net' => $grand_net,
        'grand_gross' => $grand_gross,
        'employee_count' => $employee_count,
    ];
}

function build_payroll_center_rows($conn, int $year, int $month, array $settings): array
{
    $branch_filter = branch_employees_sql('e');
    $employees_sql = 'SELECT e.* FROM employees e WHERE 1=1' . $branch_filter['sql'] . ' ORDER BY e.is_active DESC, e.name ASC';
    $employees_stmt = $conn->prepare($employees_sql);
    bind_branch_stmt_params($employees_stmt, $branch_filter['types'], $branch_filter['params']);
    $employees_stmt->execute();
    $employees_result = $employees_stmt->get_result();

    $rows = [];
    $total_net = 0.0;
    $employees_with_attendance = 0;
    $active_count = 0;
    $slip_eligible = 0;
    $slips_in_portal = 0;
    $require_approval = !isset($settings['require_payroll_approval']) || (int) $settings['require_payroll_approval'] === 1;

    while ($emp = $employees_result->fetch_assoc()) {
        $is_active = employee_is_active($emp);
        if ($is_active) {
            $active_count++;
        }
        $stats = get_attendance_stats_extended($conn, $emp['emp_id'], $year, $month, $settings);
        $salary = calculate_employee_salary_full($conn, $emp, $year, $month, $settings);
        $has_attendance = $stats['total_records'] > 0;
        if ($has_attendance) {
            $employees_with_attendance++;
        }
        if ($is_active && $has_attendance) {
            $total_net += $salary['net_salary'];
        }
        $slip_available = false;
        if ($has_attendance && (float) $emp['base_salary'] > 0 && $is_active) {
            $slip_eligible++;
            if (employee_salary_slip_is_available($conn, $emp, $year, $month, $settings)) {
                $slips_in_portal++;
                $slip_available = true;
            }
        }
        $rows[] = [
            'employee' => $emp,
            'is_active' => $is_active,
            'stats' => $stats,
            'salary' => $salary,
            'has_attendance' => $has_attendance,
            'slip_available' => $slip_available,
        ];
    }

    return [
        'rows' => $rows,
        'total_net' => $total_net,
        'employees_with_attendance' => $employees_with_attendance,
        'active_count' => $active_count,
        'slip_eligible' => $slip_eligible,
        'slips_in_portal' => $slips_in_portal,
        'require_approval' => $require_approval,
    ];
}
