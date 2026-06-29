<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/audit_helper.php';
require_permission('recruitment', 'recruitment.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recruitment.php');
    exit;
}
require_csrf_or_redirect('recruitment.php');

$action = $_POST['recruitment_action'] ?? '';
$admin = $_SESSION['admin_username'] ?? 'admin';
$settings = get_all_settings($conn);

if ($action === 'job') {
    $result = save_job_opening($conn, $_POST, $admin);
    if ($result['ok']) {
        log_admin_action($conn, !empty($_POST['id']) ? 'update_job' : 'create_job', 'job', (string) ($result['id'] ?? $_POST['id'] ?? ''), $_POST['title'] ?? '');
    }
    $redirect = 'recruitment.php';
} elseif ($action === 'candidate') {
    $resume = $_FILES['resume'] ?? null;
    $result = save_candidate($conn, $_POST, $resume);
    if ($result['ok']) {
        log_admin_action($conn, 'add_candidate', 'candidate', (string) ($result['id'] ?? ''), $_POST['name'] ?? '');
    }
    $redirect = 'recruitment.php?job_id=' . (int) ($_POST['job_opening_id'] ?? 0);
} elseif ($action === 'convert') {
    $result = convert_candidate_to_employee($conn, (int) ($_POST['candidate_id'] ?? 0), $_POST, $settings);
    $job_redirect = (int) ($_POST['job_opening_id'] ?? 0);
    $redirect = !empty($result['emp_id']) ? 'employee_view.php?emp_id=' . urlencode($result['emp_id']) : 'recruitment.php' . ($job_redirect ? '?job_id=' . $job_redirect : '');
} elseif ($action === 'job_status') {
    $result = update_job_opening_status($conn, (int) ($_POST['job_id'] ?? 0), $_POST['status'] ?? 'closed');
    $redirect = 'recruitment.php?job_id=' . (int) ($_POST['job_id'] ?? 0);
} elseif ($action === 'stage') {
    $result = update_candidate_stage($conn, (int) ($_POST['candidate_id'] ?? 0), $_POST['stage'] ?? 'applied');
    $redirect = 'recruitment.php?job_id=' . (int) ($_POST['job_opening_id'] ?? 0);
} else {
    $result = ['ok' => false, 'message' => 'Invalid action.'];
    $redirect = 'recruitment.php';
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: ' . $redirect);
exit;
