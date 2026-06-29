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
    if ($result['ok']) {
        require_once 'includes/settings_helper.php';
        require_once 'includes/employee_portal_features_helper.php';
        require_once 'includes/salary_helper.php';
        $settings = get_all_settings($conn);
        $cid = (int) ($_POST['claim_id'] ?? 0);
        $cs = $conn->prepare('SELECT x.*, e.email FROM expense_claims x INNER JOIN employees e ON e.emp_id = x.emp_id WHERE x.id = ?');
        $cs->bind_param('i', $cid);
        $cs->execute();
        if ($crow = $cs->get_result()->fetch_assoc()) {
            $verb = ($_POST['decision'] ?? '') === 'approve' ? 'approved' : 'rejected';
            notify_employee_email($settings, $crow['email'] ?? null, 'Expense claim ' . $verb, '<p>Your expense claim of ' . htmlspecialchars(format_money((float) $crow['amount'])) . ' was ' . $verb . '.</p>');
        }
    }
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
