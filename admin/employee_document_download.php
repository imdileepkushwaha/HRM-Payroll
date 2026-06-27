<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require 'config.php';
require_once 'includes/branch_helper.php';
require_once 'includes/employee_document_helper.php';

$doc_id = (int) ($_GET['doc_id'] ?? 0);
$request_id = (int) ($_GET['request_id'] ?? 0);

if ($doc_id > 0) {
    $doc = get_employee_document_by_id($conn, $doc_id);
    if (!$doc) {
        http_response_code(404);
        echo 'Document not found.';
        exit;
    }
    require_employee_branch_access($conn, $doc['emp_id']);
    stream_employee_document_file($doc);
}

if ($request_id > 0) {
    $req = get_employee_document_request_by_id($conn, $request_id);
    if (!$req) {
        http_response_code(404);
        echo 'Document not found.';
        exit;
    }
    require_employee_branch_access($conn, $req['emp_id']);
    stream_employee_document_file($req);
}

http_response_code(400);
echo 'Invalid request.';
