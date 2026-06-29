<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_permission('employees');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: helpdesk.php');
    exit;
}

require_csrf_or_redirect('helpdesk.php');

require_once 'includes/employee_portal_features_helper.php';

$ticket_id = (int) ($_POST['ticket_id'] ?? 0);
$reply = trim($_POST['admin_reply'] ?? '');
$status = $_POST['status'] ?? 'answered';
$admin_user = $_SESSION['admin_username'] ?? 'admin';

$result = reply_helpdesk_ticket($conn, $ticket_id, get_active_branch_id(), $reply, $status, $admin_user);

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: helpdesk.php');
exit;
