<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/employee_document_helper.php';

$emp_id = get_logged_in_employee_id();
$doc_id = (int) ($_GET['doc_id'] ?? 0);
$request_id = (int) ($_GET['request_id'] ?? 0);

if ($doc_id > 0) {
    $doc = get_employee_document_by_id($conn, $doc_id);
    if (!$doc || $doc['emp_id'] !== $emp_id) {
        http_response_code(404);
        echo 'Document not found.';
        exit;
    }
    stream_employee_document_file($doc);
}

if ($request_id > 0) {
    $req = get_employee_document_request_by_id($conn, $request_id);
    if (!$req || $req['emp_id'] !== $emp_id) {
        http_response_code(404);
        echo 'Document not found.';
        exit;
    }
    stream_employee_document_file($req);
}

http_response_code(400);
echo 'Invalid request.';
