<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
init_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/employee_document_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: documents.php');
    exit;
}

require_csrf_or_redirect('documents.php');

$emp_id = get_logged_in_employee_id();
$employee = require_logged_in_employee($conn);
$doc_type = (string) ($_POST['doc_type'] ?? '');
$doc_label = trim((string) ($_POST['doc_label'] ?? ''));
$note = trim((string) ($_POST['employee_note'] ?? ''));

if (!isset($_FILES['document_file'])) {
    $_SESSION['emp_flash_message'] = 'Please choose a file to upload.';
    $_SESSION['emp_flash_success'] = false;
    header('Location: documents.php');
    exit;
}

$result = create_employee_document_request(
    $conn,
    $emp_id,
    (int) $employee['branch_id'],
    $doc_type,
    $_FILES['document_file'],
    $doc_label,
    $note
);

$_SESSION['emp_flash_message'] = $result['message'];
$_SESSION['emp_flash_success'] = $result['ok'];
header('Location: documents.php');
exit;
