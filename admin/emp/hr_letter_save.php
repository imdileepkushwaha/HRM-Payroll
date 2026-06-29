<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: hr_letters.php'); exit; }
require_csrf_or_redirect('hr_letters.php');
$employee = require_logged_in_employee($conn);
$result = request_hr_letter($conn, $employee['emp_id'], (int) $employee['branch_id'], trim($_POST['doc_type'] ?? ''), trim($_POST['employee_note'] ?? ''));
$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
header('Location: hr_letters.php');
exit;
