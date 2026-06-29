<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_once 'includes/hrm_modules_helper.php';
require_permission('expenses', 'expenses.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expenses.php');
    exit;
}
require_csrf_or_redirect('expenses.php');

$action = $_POST['expense_action'] ?? '';
$reviewer = $_SESSION['admin_username'] ?? 'admin';
$branch_id = get_active_branch_id();

if ($action === 'review') {
    $result = review_expense_claim($conn, (int) ($_POST['claim_id'] ?? 0), $_POST['decision'] ?? 'reject', $reviewer, trim($_POST['review_note'] ?? ''), $branch_id);
} else {
    $result = ['ok' => false, 'message' => 'Invalid action.'];
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
$redirect = trim($_POST['redirect'] ?? 'expenses.php');
if ($redirect === '' || strpos($redirect, '://') !== false) {
    $redirect = 'expenses.php';
}
header('Location: ' . $redirect);
exit;
