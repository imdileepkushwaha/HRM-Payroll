<?php

require_once __DIR__ . '/hrm_helper.php';
require_once __DIR__ . '/hrm_modules_helper.php';
require_once __DIR__ . '/salary_helper.php';
require_once __DIR__ . '/payroll_extensions.php';
require_once __DIR__ . '/employee_portal_helper.php';
require_once __DIR__ . '/employee_document_helper.php';
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/mailer.php';

function get_employee_assigned_assets($conn, string $emp_id): array
{
    $sql = 'SELECT a.asset_tag, a.name, a.category, a.serial_no, aa.assigned_at, aa.condition_notes
            FROM asset_assignments aa
            INNER JOIN assets a ON a.id = aa.asset_id
            WHERE aa.emp_id = ? AND aa.returned_at IS NULL
            ORDER BY aa.assigned_at DESC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_employee_manager($conn, string $emp_id): ?array
{
    $stmt = $conn->prepare('SELECT e.manager_emp_id FROM employees e WHERE e.emp_id = ?');
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $mgr_id = trim($row['manager_emp_id'] ?? '');
    if ($mgr_id === '') {
        return null;
    }
    $m = $conn->prepare('SELECT emp_id, name, email, phone, department, designation FROM employees WHERE emp_id = ? AND is_active = 1');
    $m->bind_param('s', $mgr_id);
    $m->execute();
    return $m->get_result()->fetch_assoc() ?: null;
}

function get_employee_direct_reports($conn, string $manager_emp_id): array
{
    $stmt = $conn->prepare('SELECT emp_id, name, department, designation, email, phone FROM employees WHERE manager_emp_id = ? AND is_active = 1 ORDER BY name ASC');
    $stmt->bind_param('s', $manager_emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function employee_is_manager($conn, string $emp_id): bool
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM employees WHERE manager_emp_id = ? AND is_active = 1');
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
}

function get_employee_portal_prefs($conn, string $emp_id): array
{
    $stmt = $conn->prepare('SELECT slip_email_enabled FROM employee_payroll_profiles WHERE emp_id = ?');
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ['slip_email_enabled' => (int) ($row['slip_email_enabled'] ?? 1) === 1];
}

function save_employee_portal_prefs($conn, string $emp_id, array $post): array
{
    $enabled = !empty($post['slip_email_enabled']) ? 1 : 0;
    $chk = $conn->prepare('SELECT emp_id FROM employee_payroll_profiles WHERE emp_id = ?');
    $chk->bind_param('s', $emp_id);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) {
        $stmt = $conn->prepare('UPDATE employee_payroll_profiles SET slip_email_enabled = ? WHERE emp_id = ?');
        $stmt->bind_param('is', $enabled, $emp_id);
    } else {
        $stmt = $conn->prepare('INSERT INTO employee_payroll_profiles (emp_id, use_custom, slip_email_enabled) VALUES (?, 0, ?)');
        $stmt->bind_param('si', $emp_id, $enabled);
    }
    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'Preferences saved.'];
    }
    return ['ok' => false, 'message' => 'Could not save preferences.'];
}

function get_all_employee_portal_announcements($conn, int $branch_id, int $limit = 50): array
{
    return get_employee_portal_announcements($conn, $branch_id, $limit);
}

function get_employee_ytd_summary($conn, array $employee, array $settings, ?int $year = null): array
{
    $year = $year ?? (int) date('Y');
    $emp_id = $employee['emp_id'];
    $months = [];
    $total_net = 0.0;
    $slip_count = 0;
    for ($m = 1; $m <= 12; $m++) {
        $stats = get_attendance_stats_extended($conn, $emp_id, $year, $m, $settings);
        if ($stats['total_records'] <= 0) {
            continue;
        }
        $salary = calculate_employee_salary_full($conn, $employee, $year, $m, $settings);
        $net = (float) ($salary['net_salary'] ?? 0);
        $has_slip = employee_salary_slip_is_available($conn, $employee, $year, $m, $settings);
        $months[] = [
            'month' => $m,
            'label' => get_period_label($year, $m),
            'net_salary' => $net,
            'paid_days' => $stats['paid_days'] ?? 0,
            'has_slip' => $has_slip,
        ];
        $total_net += $net;
        if ($has_slip) {
            $slip_count++;
        }
    }
    return ['year' => $year, 'months' => $months, 'total_net' => $total_net, 'slip_count' => $slip_count];
}

function get_employee_active_exit($conn, string $emp_id): ?array
{
    $stmt = $conn->prepare("SELECT ex.*, f.net_payable, f.status AS fnf_status FROM employee_exits ex LEFT JOIN fnf_settlements f ON f.exit_id = ex.id WHERE ex.emp_id = ? AND ex.status != 'completed' ORDER BY ex.id DESC LIMIT 1");
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function submit_employee_exit_request($conn, string $emp_id, int $branch_id, array $post, array $settings): array
{
    if (get_employee_active_exit($conn, $emp_id)) {
        return ['ok' => false, 'message' => 'An exit request is already in progress.'];
    }
    $post['emp_id'] = $emp_id;
    $post['exit_type'] = 'resignation';
    return initiate_employee_exit($conn, $post, $emp_id, $settings);
}

function create_wfh_request($conn, string $emp_id, int $branch_id, array $post): array
{
    $date = trim($post['wfh_date'] ?? '');
    $note = trim($post['employee_note'] ?? '');
    if ($date === '') {
        return ['ok' => false, 'message' => 'WFH date is required.'];
    }
    $dup = $conn->prepare("SELECT id FROM employee_wfh_requests WHERE emp_id = ? AND wfh_date = ? AND request_status IN ('pending','approved')");
    $dup->bind_param('ss', $emp_id, $date);
    $dup->execute();
    if ($dup->get_result()->fetch_assoc()) {
        return ['ok' => false, 'message' => 'You already have a WFH request for this date.'];
    }
    $stmt = $conn->prepare('INSERT INTO employee_wfh_requests (emp_id, branch_id, wfh_date, employee_note) VALUES (?,?,?,?)');
    $stmt->bind_param('siss', $emp_id, $branch_id, $date, $note);
    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'Work from home request submitted.'];
    }
    return ['ok' => false, 'message' => 'Could not submit WFH request.'];
}

function get_employee_wfh_requests($conn, string $emp_id, int $limit = 30): array
{
    $stmt = $conn->prepare('SELECT * FROM employee_wfh_requests WHERE emp_id = ? ORDER BY wfh_date DESC LIMIT ' . max(1, min(100, $limit)));
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function create_punch_regularization_request($conn, string $emp_id, int $branch_id, array $post): array
{
    $date = trim($post['punch_date'] ?? '');
    $in_time = trim($post['requested_in_time'] ?? '') ?: null;
    $out_time = trim($post['requested_out_time'] ?? '') ?: null;
    $note = trim($post['employee_note'] ?? '');
    if ($date === '') {
        return ['ok' => false, 'message' => 'Date is required.'];
    }
    if ($in_time === null && $out_time === null) {
        return ['ok' => false, 'message' => 'Enter at least in-time or out-time.'];
    }
    $stmt = $conn->prepare('INSERT INTO punch_regularization_requests (emp_id, branch_id, punch_date, requested_in_time, requested_out_time, employee_note) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('sissss', $emp_id, $branch_id, $date, $in_time, $out_time, $note);
    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'Punch regularization request submitted.'];
    }
    return ['ok' => false, 'message' => 'Could not submit request.'];
}

function get_employee_punch_regularization_requests($conn, string $emp_id, int $limit = 30): array
{
    $stmt = $conn->prepare('SELECT * FROM punch_regularization_requests WHERE emp_id = ? ORDER BY punch_date DESC LIMIT ' . max(1, min(100, $limit)));
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function create_helpdesk_ticket($conn, string $emp_id, int $branch_id, array $post): array
{
    $subject = trim($post['subject'] ?? '');
    $body = trim($post['body'] ?? '');
    $category = trim($post['category'] ?? 'General');
    if ($subject === '' || $body === '') {
        return ['ok' => false, 'message' => 'Subject and description are required.'];
    }
    $stmt = $conn->prepare('INSERT INTO employee_helpdesk_tickets (emp_id, branch_id, category, subject, body) VALUES (?,?,?,?,?)');
    $stmt->bind_param('sisss', $emp_id, $branch_id, $category, $subject, $body);
    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'Support ticket submitted. HR will respond soon.'];
    }
    return ['ok' => false, 'message' => 'Could not create ticket.'];
}

function get_employee_helpdesk_tickets($conn, string $emp_id, int $limit = 30): array
{
    $stmt = $conn->prepare('SELECT * FROM employee_helpdesk_tickets WHERE emp_id = ? ORDER BY created_at DESC LIMIT ' . max(1, min(100, $limit)));
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function request_hr_letter($conn, string $emp_id, int $branch_id, string $doc_type, string $note): array
{
    $allowed = ['office_experience', 'office_relieving', 'office_noc', 'office_form16'];
    if (!in_array($doc_type, $allowed, true)) {
        return ['ok' => false, 'message' => 'Invalid letter type.'];
    }
    if (employee_has_pending_document_request($conn, $emp_id, $doc_type)) {
        return ['ok' => false, 'message' => 'You already have a pending request for this letter.'];
    }
    $doc_label = employee_document_type_label($doc_type);
    $pending = 'pending';
    $path = 'hr-letter-request';
    $orig = 'hr-letter-request.txt';
    $mime = 'text/plain';
    $size = 0;
    $note = trim($note);
    $stmt = $conn->prepare('INSERT INTO employee_document_requests (emp_id, branch_id, doc_type, doc_label, file_path, original_filename, mime_type, file_size, employee_note, request_status) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('sisssssiss', $emp_id, $branch_id, $doc_type, $doc_label, $path, $orig, $mime, $size, $note, $pending);
    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'HR letter request submitted for approval.'];
    }
    return ['ok' => false, 'message' => 'Could not submit letter request.'];
}

function get_manager_team_pending_items($conn, string $manager_emp_id): array
{
    $reports = get_employee_direct_reports($conn, $manager_emp_id);
    $emp_ids = array_column($reports, 'emp_id');
    if ($emp_ids === []) {
        return ['leave' => [], 'wfh' => [], 'regularization' => []];
    }
    $placeholders = implode(',', array_fill(0, count($emp_ids), '?'));
    $types = str_repeat('s', count($emp_ids));

    $leave_sql = "SELECT r.*, e.name AS employee_name FROM employee_leave_requests r INNER JOIN employees e ON e.emp_id = r.emp_id WHERE r.request_status = 'pending' AND r.emp_id IN ($placeholders) ORDER BY r.created_at ASC";
    $leave_stmt = $conn->prepare($leave_sql);
    $leave_stmt->bind_param($types, ...$emp_ids);
    $leave_stmt->execute();
    $leave = $leave_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $wfh_sql = "SELECT w.*, e.name AS employee_name FROM employee_wfh_requests w INNER JOIN employees e ON e.emp_id = w.emp_id WHERE w.request_status = 'pending' AND w.emp_id IN ($placeholders) ORDER BY w.wfh_date ASC";
    $wfh_stmt = $conn->prepare($wfh_sql);
    $wfh_stmt->bind_param($types, ...$emp_ids);
    $wfh_stmt->execute();
    $wfh = $wfh_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $reg_sql = "SELECT p.*, e.name AS employee_name FROM punch_regularization_requests p INNER JOIN employees e ON e.emp_id = p.emp_id WHERE p.request_status = 'pending' AND p.emp_id IN ($placeholders) ORDER BY p.punch_date DESC";
    $reg_stmt = $conn->prepare($reg_sql);
    $reg_stmt->bind_param($types, ...$emp_ids);
    $reg_stmt->execute();
    $reg = $reg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return ['leave' => $leave, 'wfh' => $wfh, 'regularization' => $reg];
}

function manager_owns_employee($conn, string $manager_emp_id, string $emp_id): bool
{
    $stmt = $conn->prepare('SELECT emp_id FROM employees WHERE emp_id = ? AND manager_emp_id = ? AND is_active = 1');
    $stmt->bind_param('ss', $emp_id, $manager_emp_id);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function manager_review_leave($conn, string $manager_emp_id, int $request_id, string $action, string $note): array
{
    $stmt = $conn->prepare("SELECT * FROM employee_leave_requests WHERE id = ? AND request_status = 'pending'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    if (!$req || !manager_owns_employee($conn, $manager_emp_id, $req['emp_id'])) {
        return ['ok' => false, 'message' => 'Request not found or not in your team.'];
    }
    $reviewer = 'Manager:' . $manager_emp_id;
    return $action === 'approve'
        ? approve_leave_request($conn, $request_id, $reviewer, $note)
        : reject_leave_request($conn, $request_id, $reviewer, $note);
}

function manager_review_wfh($conn, string $manager_emp_id, int $request_id, string $action, string $note): array
{
    $stmt = $conn->prepare("SELECT * FROM employee_wfh_requests WHERE id = ? AND request_status = 'pending'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    if (!$req || !manager_owns_employee($conn, $manager_emp_id, $req['emp_id'])) {
        return ['ok' => false, 'message' => 'Request not found or not in your team.'];
    }
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $reviewer = 'Manager:' . $manager_emp_id;
    $upd = $conn->prepare("UPDATE employee_wfh_requests SET request_status=?, reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?");
    $upd->bind_param('sssi', $status, $reviewer, $note, $request_id);
    if ($upd->execute()) {
        return ['ok' => true, 'message' => 'WFH request ' . $status . '.'];
    }
    return ['ok' => false, 'message' => 'Could not update WFH request.'];
}

function manager_review_regularization($conn, string $manager_emp_id, int $request_id, string $action, string $note): array
{
    $stmt = $conn->prepare("SELECT * FROM punch_regularization_requests WHERE id = ? AND request_status = 'pending'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    if (!$req || !manager_owns_employee($conn, $manager_emp_id, $req['emp_id'])) {
        return ['ok' => false, 'message' => 'Request not found or not in your team.'];
    }
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $reviewer = 'Manager:' . $manager_emp_id;
    $upd = $conn->prepare("UPDATE punch_regularization_requests SET request_status=?, reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?");
    $upd->bind_param('sssi', $status, $reviewer, $note, $request_id);
    if ($upd->execute()) {
        return ['ok' => true, 'message' => 'Regularization request ' . $status . '.'];
    }
    return ['ok' => false, 'message' => 'Could not update request.'];
}

function get_employee_portal_notification_items($conn, string $emp_id, array $settings, int $branch_id): array
{
    $items = [];
    if (employee_has_pending_profile_request($conn, $emp_id)) {
        $items[] = ['type' => 'profile', 'label' => 'Profile update pending approval', 'href' => 'details.php', 'priority' => 'info'];
    }
    foreach (get_employee_leave_requests($conn, $emp_id, 10) as $r) {
        if (($r['request_status'] ?? '') === 'pending') {
            $items[] = ['type' => 'leave', 'label' => 'Leave request pending', 'href' => 'leave.php', 'priority' => 'warn'];
            break;
        }
    }
    foreach (get_employee_attendance_requests($conn, $emp_id, 10) as $r) {
        if (($r['request_status'] ?? '') === 'pending') {
            $items[] = ['type' => 'attendance', 'label' => 'Attendance correction pending', 'href' => 'attendance.php', 'priority' => 'warn'];
            break;
        }
    }
    $claims = array_filter(get_expense_claims($conn, $branch_id, 'pending', 20), static fn($c) => $c['emp_id'] === $emp_id);
    if ($claims !== []) {
        $items[] = ['type' => 'expense', 'label' => count($claims) . ' expense claim(s) pending', 'href' => 'expenses.php', 'priority' => 'warn'];
    }
    foreach (get_employee_performance_reviews($conn, $emp_id) as $rev) {
        if (in_array($rev['status'] ?? '', ['pending', 'self_review'], true) && ($rev['cycle_status'] ?? '') !== 'closed') {
            $items[] = ['type' => 'performance', 'label' => 'Performance self-review due', 'href' => 'performance.php', 'priority' => 'info'];
            break;
        }
    }
    $exit = get_employee_active_exit($conn, $emp_id);
    if ($exit) {
        $items[] = ['type' => 'exit', 'label' => 'Exit in progress: ' . ($exit['status'] ?? ''), 'href' => 'exit_request.php', 'priority' => 'info'];
    }
    foreach (get_employee_wfh_requests($conn, $emp_id, 5) as $w) {
        if (($w['request_status'] ?? '') === 'pending') {
            $items[] = ['type' => 'wfh', 'label' => 'WFH request pending', 'href' => 'wfh.php', 'priority' => 'warn'];
            break;
        }
    }
    foreach (get_employee_helpdesk_tickets($conn, $emp_id, 5) as $t) {
        if (($t['status'] ?? '') === 'open' && !empty($t['admin_reply'])) {
            $items[] = ['type' => 'helpdesk', 'label' => 'HR replied to your ticket', 'href' => 'helpdesk.php', 'priority' => 'info'];
            break;
        }
    }
    if (employee_is_manager($conn, $emp_id)) {
        $pending = get_manager_team_pending_items($conn, $emp_id);
        $team_pending = count($pending['leave']) + count($pending['wfh']) + count($pending['regularization']);
        if ($team_pending > 0) {
            $items[] = ['type' => 'team', 'label' => $team_pending . ' team approval(s) waiting', 'href' => 'team_approvals.php', 'priority' => 'warn'];
        }
    }
    return $items;
}

function count_employee_portal_notifications($conn, string $emp_id, array $settings, int $branch_id): int
{
    return count(get_employee_portal_notification_items($conn, $emp_id, $settings, $branch_id));
}

function notify_employee_email(array $settings, ?string $email, string $subject, string $html_body): void
{
    if ($email === null || trim($email) === '' || !smtp_is_configured($settings)) {
        return;
    }
    $company = trim($settings['company_name'] ?? '') ?: 'Payroll';
    @send_email_smtp($settings, trim($email), $company, $subject, $html_body);
}

function notify_employee_request_status(array $settings, array $employee, string $subject, string $message): void
{
    $prefs = null;
    notify_employee_email($settings, $employee['email'] ?? null, $subject, '<p>' . htmlspecialchars($message) . '</p>');
}

function get_expense_claim_for_employee($conn, int $claim_id, string $emp_id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM expense_claims WHERE id = ? AND emp_id = ?');
    $stmt->bind_param('is', $claim_id, $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function get_admin_helpdesk_tickets($conn, ?int $branch_id, ?string $status = null, int $limit = 100): array
{
    $sql = 'SELECT t.*, e.name AS emp_name FROM employee_helpdesk_tickets t INNER JOIN employees e ON e.emp_id = t.emp_id WHERE 1=1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND t.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    if ($status !== null && $status !== '') {
        $sql .= ' AND t.status = ?';
        $types .= 's';
        $params[] = $status;
    }
    $sql .= ' ORDER BY t.created_at DESC LIMIT ' . max(1, min(200, $limit));
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function reply_helpdesk_ticket($conn, int $ticket_id, ?int $branch_id, string $reply, string $status, string $admin_user): array
{
    $reply = trim($reply);
    if ($reply === '') {
        return ['ok' => false, 'message' => 'Reply text is required.'];
    }
    $allowed_status = ['open', 'answered', 'closed'];
    if (!in_array($status, $allowed_status, true)) {
        $status = 'answered';
    }
    $chk = $conn->prepare('SELECT t.*, e.email FROM employee_helpdesk_tickets t INNER JOIN employees e ON e.emp_id = t.emp_id WHERE t.id = ?');
    $chk->bind_param('i', $ticket_id);
    $chk->execute();
    $ticket = $chk->get_result()->fetch_assoc();
    if (!$ticket) {
        return ['ok' => false, 'message' => 'Ticket not found.'];
    }
    if ($branch_id !== null && (int) $ticket['branch_id'] !== $branch_id) {
        return ['ok' => false, 'message' => 'Ticket is not in your branch.'];
    }
    $stmt = $conn->prepare('UPDATE employee_helpdesk_tickets SET admin_reply = ?, status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('ssi', $reply, $status, $ticket_id);
    if (!$stmt->execute()) {
        return ['ok' => false, 'message' => 'Could not save reply.'];
    }
    require_once __DIR__ . '/settings_helper.php';
    $settings = get_all_settings($conn);
    notify_employee_email($settings, $ticket['email'] ?? null, 'HR replied to your ticket', '<p><strong>' . htmlspecialchars($ticket['subject']) . '</strong></p><p>' . nl2br(htmlspecialchars($reply)) . '</p>');
    return ['ok' => true, 'message' => 'Reply sent to employee.'];
}
