<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_once 'includes/hrm_modules_helper.php';
require_permission('masters', 'departments.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: departments.php');
    exit;
}
require_csrf_or_redirect('departments.php');

$action = $_POST['master_action'] ?? '';
if ($action === 'department') {
    $result = save_department($conn, $_POST);
} elseif ($action === 'designation') {
    $result = save_designation($conn, $_POST);
} else {
    $result = ['ok' => false, 'message' => 'Invalid action.'];
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: departments.php');
exit;
