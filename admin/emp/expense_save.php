<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/hrm_modules_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expenses.php');
    exit;
}
require_csrf_or_redirect('expenses.php');

$employee = require_logged_in_employee($conn);
$receipt = $_FILES['receipt'] ?? null;
$result = submit_expense_claim($conn, $employee['emp_id'], (int) $employee['branch_id'], $_POST, $receipt);

if ($result['ok']) {
    $settings = get_all_settings($conn);
    notify_new_expense_claim($conn, $settings, [
        'emp_name' => $employee['name'],
        'emp_id' => $employee['emp_id'],
        'amount' => (float) ($_POST['amount'] ?? 0),
        'category' => trim($_POST['category'] ?? ''),
    ]);
}

$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
header('Location: expenses.php');
exit;
