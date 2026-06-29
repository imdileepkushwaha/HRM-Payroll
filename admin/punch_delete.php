<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_permission('attendance');
require_once 'includes/punch_helper.php';
require_once 'includes/settings_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: punch_logs.php');
    exit;
}

$month = (int) ($_POST['month'] ?? date('n'));
$year = (int) ($_POST['year'] ?? date('Y'));
$punch_id = (int) ($_POST['punch_id'] ?? 0);

$redirect = 'punch_logs.php?month=' . max(1, min(12, $month)) . '&year=' . max(2000, min(2100, $year));
foreach (['punctuality', 'punch_type', 'status', 'emp_id'] as $key) {
    $value = trim((string) ($_POST[$key] ?? ''));
    if ($value !== '') {
        $redirect .= '&' . rawurlencode($key) . '=' . rawurlencode($value);
    }
}

require_csrf_or_redirect($redirect);

if ($punch_id < 1) {
    $_SESSION['flash_message'] = 'Invalid punch record.';
    $_SESSION['flash_success'] = false;
    header('Location: ' . $redirect);
    exit;
}

$branch_id = get_active_branch_id();
$settings = get_all_settings($conn);
$result = delete_employee_punch_by_id($conn, $punch_id, $branch_id, $settings);

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = !empty($result['ok']);

header('Location: ' . $redirect);
exit;
