<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_permission('approvals');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: approvals.php');
    exit;
}

require_csrf_or_redirect('approvals.php');

$type = $_POST['request_type'] ?? '';
$request_id = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$note = $_POST['review_note'] ?? '';
$reviewer = $_SESSION['admin_username'] ?? 'admin';

if ($request_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    $_SESSION['flash_message'] = 'Invalid approval request.';
    $_SESSION['flash_success'] = false;
    header('Location: approvals.php');
    exit;
}

$branch_filter = get_active_branch_id();

if ($type === 'profile') {
    $stmt = $conn->prepare('SELECT branch_id FROM employee_profile_requests WHERE id = ? AND request_status = ?');
    $pending = 'pending';
    $stmt->bind_param('is', $request_id, $pending);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || ($branch_filter !== null && (int) $row['branch_id'] !== $branch_filter)) {
        $_SESSION['flash_message'] = 'Request not found or not in your branch.';
        $_SESSION['flash_success'] = false;
        header('Location: approvals.php');
        exit;
    }
    $result = $action === 'approve'
        ? approve_profile_request($conn, $request_id, $reviewer, $note)
        : reject_profile_request($conn, $request_id, $reviewer, $note);
} elseif ($type === 'attendance') {
    $stmt = $conn->prepare('SELECT branch_id FROM employee_attendance_requests WHERE id = ? AND request_status = ?');
    $pending = 'pending';
    $stmt->bind_param('is', $request_id, $pending);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || ($branch_filter !== null && (int) $row['branch_id'] !== $branch_filter)) {
        $_SESSION['flash_message'] = 'Request not found or not in your branch.';
        $_SESSION['flash_success'] = false;
        header('Location: approvals.php');
        exit;
    }
    $result = $action === 'approve'
        ? approve_attendance_request($conn, $request_id, $reviewer, $note)
        : reject_attendance_request($conn, $request_id, $reviewer, $note);
} elseif ($type === 'leave') {
    $is_cancellation = !empty($_POST['is_cancellation']);
    $stmt = $conn->prepare('SELECT branch_id FROM employee_leave_requests WHERE id = ? AND request_status = ?');
    $status_expected = $is_cancellation ? 'cancellation_pending' : 'pending';
    $stmt->bind_param('is', $request_id, $status_expected);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || ($branch_filter !== null && (int) $row['branch_id'] !== $branch_filter)) {
        $_SESSION['flash_message'] = 'Request not found or not in your branch.';
        $_SESSION['flash_success'] = false;
        header('Location: approvals.php');
        exit;
    }
    if ($is_cancellation) {
        $result = $action === 'approve'
            ? approve_leave_cancellation($conn, $request_id, $reviewer, $note)
            : reject_leave_cancellation($conn, $request_id, $reviewer, $note);
    } else {
        $result = $action === 'approve'
            ? approve_leave_request($conn, $request_id, $reviewer, $note)
            : reject_leave_request($conn, $request_id, $reviewer, $note);
    }
} elseif ($type === 'document') {
    require_once 'includes/employee_document_helper.php';
    $stmt = $conn->prepare('SELECT branch_id FROM employee_document_requests WHERE id = ? AND request_status = ?');
    $pending = 'pending';
    $stmt->bind_param('is', $request_id, $pending);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || ($branch_filter !== null && (int) $row['branch_id'] !== $branch_filter)) {
        $_SESSION['flash_message'] = 'Request not found or not in your branch.';
        $_SESSION['flash_success'] = false;
        header('Location: approvals.php');
        exit;
    }
    $result = $action === 'approve'
        ? approve_document_request($conn, $request_id, $reviewer, $note)
        : reject_document_request($conn, $request_id, $reviewer, $note);
} else {
    $_SESSION['flash_message'] = 'Unknown request type.';
    $_SESSION['flash_success'] = false;
    header('Location: approvals.php');
    exit;
}

if ($result['ok'] && in_array($type, ['leave', 'document'], true)) {
    require_once 'includes/settings_helper.php';
    require_once 'includes/employee_portal_features_helper.php';
    $settings = get_all_settings($conn);
    $verb = $action === 'approve' ? 'approved' : 'rejected';
    if ($type === 'leave') {
        $ls = $conn->prepare('SELECT r.*, e.email, e.name FROM employee_leave_requests r INNER JOIN employees e ON e.emp_id = r.emp_id WHERE r.id = ?');
        $ls->bind_param('i', $request_id);
        $ls->execute();
        if ($emp_row = $ls->get_result()->fetch_assoc()) {
            notify_employee_email($settings, $emp_row['email'] ?? null, 'Leave request ' . $verb, '<p>Your leave request (' . htmlspecialchars($emp_row['leave_type']) . ') was ' . $verb . '.</p>');
        }
    } elseif ($type === 'document') {
        $ds = $conn->prepare('SELECT r.*, e.email FROM employee_document_requests r INNER JOIN employees e ON e.emp_id = r.emp_id WHERE r.id = ?');
        $ds->bind_param('i', $request_id);
        $ds->execute();
        if ($drow = $ds->get_result()->fetch_assoc()) {
            require_once 'includes/employee_document_helper.php';
            $label = employee_document_type_label($drow['doc_type'] ?? '');
            notify_employee_email($settings, $drow['email'] ?? null, 'Document request ' . $verb, '<p>Your ' . htmlspecialchars($label) . ' request was ' . $verb . '.</p>');
        }
    }
}

$_SESSION['flash_message'] = $result['message'];
$_SESSION['flash_success'] = $result['ok'];
header('Location: approvals.php');
exit;
