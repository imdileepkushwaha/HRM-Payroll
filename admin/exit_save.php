<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/notification_helper.php';
require_once 'includes/audit_helper.php';
require_permission('exits', 'employee_exits.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employee_exits.php');
    exit;
}
require_csrf_or_redirect('employee_exits.php');

$action = $_POST['exit_action'] ?? '';
$admin = $_SESSION['admin_username'] ?? 'admin';
$settings = get_all_settings($conn);

if ($action === 'initiate') {
    $result = initiate_employee_exit($conn, $_POST, $admin, $settings);
    if ($result['ok']) {
        $emp_id = trim($_POST['emp_id'] ?? '');
        $emp_row = ['emp_name' => $emp_id, 'last_working_day' => trim($_POST['last_working_day'] ?? '')];
        $name_stmt = $conn->prepare('SELECT name FROM employees WHERE emp_id = ?');
        $name_stmt->bind_param('s', $emp_id);
        $name_stmt->execute();
        if ($nr = $name_stmt->get_result()->fetch_assoc()) {
            $emp_row['emp_name'] = $nr['name'];
        }
        notify_exit_initiated($conn, $settings, $emp_row, $admin);
        log_admin_action($conn, 'initiate_exit', 'exit', (string) ($result['exit_id'] ?? ''), $emp_id);
    }
} elseif ($action === 'status') {
    $result = update_exit_status($conn, (int) ($_POST['exit_id'] ?? 0), $_POST['status'] ?? '', $admin, $settings);
    if ($result['ok']) {
        log_admin_action($conn, 'update_exit_status', 'exit', (string) ($_POST['exit_id'] ?? ''), $_POST['status'] ?? '');
    }
} elseif ($action === 'approve_fnf') {
    $result = approve_fnf_settlement($conn, (int) ($_POST['exit_id'] ?? 0), $admin);
    if ($result['ok']) {
        log_admin_action($conn, 'approve_fnf', 'exit', (string) ($_POST['exit_id'] ?? ''), '');
    }
} elseif ($action === 'recalc_fnf') {
    $exit_id = (int) ($_POST['exit_id'] ?? 0);
    $stmt = $conn->prepare('SELECT ex.*, e.* FROM employee_exits ex INNER JOIN employees e ON e.emp_id = ex.emp_id WHERE ex.id = ?');
    $stmt->bind_param('i', $exit_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $fnf = calculate_fnf_settlement($conn, $row, $exit_id, $settings, $_POST);
        save_fnf_settlement($conn, $exit_id, $fnf);
        log_admin_action($conn, 'recalc_fnf', 'exit', (string) $exit_id, '');
        $result = ['ok' => true, 'message' => 'F&F recalculated.'];
    } else {
        $result = ['ok' => false, 'message' => 'Exit not found.'];
    }
} else {
    $result = ['ok' => false, 'message' => 'Invalid action.'];
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: employee_exits.php' . (!empty($_POST['exit_id']) ? '?exit_id=' . (int) $_POST['exit_id'] : ''));
exit;
