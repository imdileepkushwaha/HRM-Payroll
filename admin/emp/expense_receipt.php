<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/hrm_modules_helper.php';

$employee = require_logged_in_employee($conn);
$claim_id = (int) ($_GET['id'] ?? 0);
$claim = get_expense_claim_for_employee($conn, $claim_id, $employee['emp_id']);
$receipt_abs = $claim ? hrm_upload_absolute_path($claim['receipt_path'] ?? null) : null;
if (!$claim || $receipt_abs === null) {
    http_response_code(404);
    echo 'Receipt not found.';
    exit;
}
$filename = $claim['receipt_filename'] ?: basename($receipt_abs);
$mime = mime_content_type($receipt_abs) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) . '"');
readfile($receipt_abs);
exit;
