<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_permission('roles');
require_once 'includes/auth_helper.php';

if (!is_super_admin()) {
    $_SESSION['flash_message'] = 'Only Head Office can manage roles.';
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: roles.php');
    exit;
}
require_csrf_or_redirect('roles.php');

$role_id = (int) ($_POST['role_id'] ?? 0);
$keys = $_POST['permissions'] ?? [];
$result = save_role_permissions($conn, $role_id, is_array($keys) ? $keys : []);

if ($result['ok']) {
    require_once 'includes/audit_helper.php';
    log_admin_action($conn, 'update_role_permissions', 'role', (string) $role_id, '');
    refresh_admin_permissions_for_role($conn, $role_id);
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: roles.php?role_id=' . $role_id);
exit;
