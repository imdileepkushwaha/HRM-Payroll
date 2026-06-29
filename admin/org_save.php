<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/audit_helper.php';
require_permission('org', 'org_chart.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: org_chart.php');
    exit;
}
require_csrf_or_redirect('org_chart.php');

$emp_ids = $_POST['emp_ids'] ?? [];
if (!is_array($emp_ids)) {
    $emp_ids = [];
}
$manager_emp_id = trim($_POST['manager_emp_id'] ?? '') ?: null;
$branch_id = get_active_branch_id();

$result = bulk_assign_managers($conn, $emp_ids, $manager_emp_id, $branch_id);

if ($result['ok']) {
    log_admin_action($conn, 'bulk_assign_manager', 'employee', implode(',', $emp_ids), $manager_emp_id ?? 'cleared');
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: org_chart.php');
exit;
