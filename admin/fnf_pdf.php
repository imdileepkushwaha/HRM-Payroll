<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require 'config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/pdf_fnf.php';
require_once 'includes/audit_helper.php';
require_permission('exits', 'employee_exits.php');

$exit_id = (int) ($_GET['exit_id'] ?? 0);
if ($exit_id <= 0) {
    header('Location: employee_exits.php');
    exit;
}

$stmt = $conn->prepare('SELECT ex.*, e.name, e.emp_id, e.department, e.designation, f.salary_due, f.leave_encashment, f.notice_pay, f.deductions, f.net_payable, f.status AS fnf_status, f.notes
    FROM employee_exits ex
    INNER JOIN employees e ON e.emp_id = ex.emp_id
    LEFT JOIN fnf_settlements f ON f.exit_id = ex.id
    WHERE ex.id = ?');
$stmt->bind_param('i', $exit_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header('Location: employee_exits.php');
    exit;
}

$branch_id = get_active_branch_id();
if ($branch_id !== null && (int) $row['branch_id'] !== $branch_id) {
    header('Location: employee_exits.php');
    exit;
}

$settings = get_all_settings($conn);
$employee = ['name' => $row['name'], 'emp_id' => $row['emp_id'], 'department' => $row['department'] ?? ''];
$fnf = [
    'salary_due' => $row['salary_due'] ?? 0,
    'leave_encashment' => $row['leave_encashment'] ?? 0,
    'notice_pay' => $row['notice_pay'] ?? 0,
    'deductions' => $row['deductions'] ?? 0,
    'net_payable' => $row['net_payable'] ?? 0,
    'status' => $row['fnf_status'] ?? 'draft',
    'notes' => $row['notes'] ?? '',
];

$pdf = generate_fnf_settlement_pdf($row, $employee, $fnf, $settings);
$filename = 'FNF_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $row['emp_id']) . '.pdf';

log_admin_action($conn, 'export_fnf_pdf', 'exit', (string) $exit_id, $row['emp_id']);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
