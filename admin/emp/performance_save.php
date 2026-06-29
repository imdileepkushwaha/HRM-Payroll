<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/hrm_modules_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: performance.php');
    exit;
}
require_csrf_or_redirect('performance.php');

$employee = require_logged_in_employee($conn);
$result = save_employee_self_review($conn, $employee['emp_id'], $_POST);

$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
$redirect = 'performance.php';
if (!empty($_POST['review_id'])) {
    $redirect .= '?review_id=' . (int) $_POST['review_id'];
}
header('Location: ' . $redirect);
exit;
