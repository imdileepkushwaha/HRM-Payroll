<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_once 'includes/hrm_modules_helper.php';
require_permission('performance', 'performance.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: performance.php');
    exit;
}
require_csrf_or_redirect('performance.php');

$action = $_POST['performance_action'] ?? '';
$admin = $_SESSION['admin_username'] ?? 'admin';

if ($action === 'cycle') {
    $result = save_review_cycle($conn, $_POST, $admin);
    $redirect = 'performance.php?cycle_id=' . (int) ($result['id'] ?? $_POST['id'] ?? 0);
} elseif ($action === 'review') {
    $result = save_performance_review($conn, $_POST);
    $redirect = 'performance.php?cycle_id=' . (int) ($_POST['cycle_id'] ?? 0) . '&review_id=' . (int) ($_POST['review_id'] ?? 0);
} elseif ($action === 'generate') {
    $cycle_id = (int) ($_POST['cycle_id'] ?? 0);
    $count = generate_reviews_for_cycle($conn, $cycle_id);
    $result = ['ok' => true, 'message' => "Generated {$count} review record(s)."];
    $redirect = 'performance.php?cycle_id=' . $cycle_id;
} else {
    $result = ['ok' => false, 'message' => 'Invalid action.'];
    $redirect = 'performance.php';
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: ' . $redirect);
exit;
