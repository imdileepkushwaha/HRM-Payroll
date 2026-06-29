<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_once 'includes/hrm_modules_helper.php';
require_permission('assets', 'assets.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assets.php');
    exit;
}
require_csrf_or_redirect('assets.php');

$action = $_POST['asset_action'] ?? '';
$admin = $_SESSION['admin_username'] ?? 'admin';

if ($action === 'save') {
    $result = save_asset($conn, $_POST);
} elseif ($action === 'update') {
    $result = save_asset($conn, $_POST);
} elseif ($action === 'assign') {
    $result = assign_asset($conn, (int) ($_POST['asset_id'] ?? 0), trim($_POST['emp_id'] ?? ''), $admin, trim($_POST['notes'] ?? ''));
} elseif ($action === 'return') {
    $result = return_asset($conn, (int) ($_POST['asset_id'] ?? 0), trim($_POST['notes'] ?? ''));
} elseif ($action === 'retire') {
    $result = retire_asset($conn, (int) ($_POST['asset_id'] ?? 0));
} else {
    $result = ['ok' => false, 'message' => 'Invalid action.'];
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: assets.php');
exit;
