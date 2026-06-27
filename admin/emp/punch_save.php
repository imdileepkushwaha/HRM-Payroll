<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/punch_helper.php';
init_employee_session();
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

enforce_employee_session();
require_csrf_or_redirect('dashboard.php');

$employee = require_logged_in_employee($conn);
$settings = get_all_settings($conn);

$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
$accuracy = trim($_POST['location_accuracy'] ?? '');
$punch_type = strtolower(trim($_POST['punch_type'] ?? ''));

if (!in_array($punch_type, ['in', 'out'], true)) {
    $_SESSION['emp_flash_message'] = 'Invalid punch action.';
    $_SESSION['emp_flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$result = record_employee_punch(
    $conn,
    $employee,
    $settings,
    $punch_type,
    $latitude !== '' ? (float) $latitude : null,
    $longitude !== '' ? (float) $longitude : null,
    $accuracy !== '' ? (float) $accuracy : null
);

$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];

$redirect = trim($_POST['redirect'] ?? 'dashboard.php');
if (!preg_match('/^(dashboard|attendance|leave|details)\.php(\?[\w=&.-]*)?$/', $redirect)) {
    $redirect = 'dashboard.php';
}
header('Location: ' . $redirect);
exit;
