<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: details.php'); exit; }
require_csrf_or_redirect('details.php');
$employee = require_logged_in_employee($conn);
$result = save_employee_portal_prefs($conn, $employee['emp_id'], $_POST);
$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
header('Location: details.php');
exit;
