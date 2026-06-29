<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: team_approvals.php'); exit; }
require_csrf_or_redirect('team_approvals.php');
$employee = require_logged_in_employee($conn);
$type = $_POST['type'] ?? '';
$request_id = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$note = trim($_POST['review_note'] ?? '');

if (!in_array($action, ['approve', 'reject'], true) || $request_id <= 0) {
    $result = ['ok' => false, 'message' => 'Invalid request.'];
} elseif ($type === 'leave') {
    $result = manager_review_leave($conn, $employee['emp_id'], $request_id, $action, $note);
} elseif ($type === 'wfh') {
    $result = manager_review_wfh($conn, $employee['emp_id'], $request_id, $action, $note);
} elseif ($type === 'regularization') {
    $result = manager_review_regularization($conn, $employee['emp_id'], $request_id, $action, $note);
} else {
    $result = ['ok' => false, 'message' => 'Unknown request type.'];
}

$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
header('Location: team_approvals.php');
exit;
