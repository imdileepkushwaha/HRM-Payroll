<?php
require 'config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: careers.php');
    exit;
}

$settings = get_all_settings($conn);
if (($settings['careers_public_enabled'] ?? '1') !== '1') {
    $_SESSION['careers_flash'] = 'Applications are not accepted at this time.';
    $_SESSION['careers_flash_ok'] = false;
    header('Location: careers.php');
    exit;
}

if (trim($_POST['website'] ?? '') !== '') {
    header('Location: careers.php');
    exit;
}

$job_id = (int) ($_POST['job_opening_id'] ?? 0);
$job_stmt = $conn->prepare('SELECT * FROM job_openings WHERE id = ? AND status = ?');
$open = 'open';
$job_stmt->bind_param('is', $job_id, $open);
$job_stmt->execute();
$job = $job_stmt->get_result()->fetch_assoc();

if (!$job) {
    $_SESSION['careers_flash'] = 'This position is no longer open.';
    $_SESSION['careers_flash_ok'] = false;
    header('Location: careers.php');
    exit;
}

$post = $_POST;
$post['stage'] = 'applied';
$post['job_opening_id'] = $job_id;
$resume = $_FILES['resume'] ?? null;
$result = save_candidate($conn, $post, $resume);

if ($result['ok']) {
    notify_recruitment_application($settings, $job, trim($post['name'] ?? ''), trim($post['email'] ?? ''));
}

$_SESSION['careers_flash'] = $result['ok']
    ? 'Thank you! Your application for ' . ($job['title'] ?? 'this role') . ' has been submitted.'
    : $result['message'];
$_SESSION['careers_flash_ok'] = $result['ok'];
header('Location: careers.php#job-' . $job_id);
exit;
