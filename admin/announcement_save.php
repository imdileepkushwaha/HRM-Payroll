<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_permission('announcements');
require_once 'includes/hrm_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: announcements.php');
    exit;
}

require_csrf_or_redirect('announcements.php');
$action = $_POST['announcement_action'] ?? '';

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if (delete_announcement($conn, $id)) {
        $_SESSION['flash_message'] = 'Announcement removed.';
        $_SESSION['flash_success'] = true;
    } else {
        $_SESSION['flash_message'] = 'Could not remove announcement.';
        $_SESSION['flash_success'] = false;
    }
    header('Location: announcements.php');
    exit;
}

$username = $_SESSION['admin_username'] ?? 'admin';
$result = save_announcement($conn, $_POST, $username);
$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: announcements.php');
exit;
