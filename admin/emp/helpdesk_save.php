<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: helpdesk.php'); exit; }
require_csrf_or_redirect('helpdesk.php');
$employee = require_logged_in_employee($conn);
$result = create_helpdesk_ticket($conn, $employee['emp_id'], (int) $employee['branch_id'], $_POST);
if ($result['ok']) {
    $settings = get_all_settings($conn);
    payroll_send_hr_notification($settings, 'New helpdesk ticket: ' . trim($_POST['subject'] ?? ''), '<p>From: ' . htmlspecialchars($employee['name']) . ' (' . htmlspecialchars($employee['emp_id']) . ')</p>');
}
$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
header('Location: helpdesk.php');
exit;
