<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exit_request.php');
    exit;
}
require_csrf_or_redirect('exit_request.php');

$employee = require_logged_in_employee($conn);
$settings = get_all_settings($conn);
$result = submit_employee_exit_request($conn, $employee['emp_id'], (int) $employee['branch_id'], $_POST, $settings);

if ($result['ok']) {
    notify_exit_initiated($conn, $settings, array_merge($employee, ['last_working_day' => $_POST['last_working_day'] ?? '']), $employee['emp_id']);
}

$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
header('Location: exit_request.php');
exit;
