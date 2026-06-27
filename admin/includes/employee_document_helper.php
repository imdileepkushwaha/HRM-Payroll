<?php

define('EMPLOYEE_DOCUMENT_MAX_BYTES', 5 * 1024 * 1024);

function employee_document_types(): array
{
    return [
        'aadhar' => 'Aadhar card',
        'pan' => 'PAN card',
        'marksheet' => 'Marksheet',
        'other' => 'Other document',
    ];
}

function employee_document_type_label(string $doc_type): string
{
    $types = employee_document_types();
    return $types[$doc_type] ?? ucfirst(str_replace('_', ' ', $doc_type));
}

function employee_documents_base_dir(): string
{
    $dir = dirname(__DIR__) . '/uploads/employee_docs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function employee_documents_emp_dir(string $emp_id, string $subdir = 'pending'): string
{
    $safe_emp = preg_replace('/[^A-Za-z0-9_-]/', '', strtoupper($emp_id));
    $dir = employee_documents_base_dir() . '/' . $safe_emp . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function employee_document_relative_path(string $absolute_path): string
{
    $base = realpath(dirname(__DIR__));
    $full = realpath($absolute_path);
    if ($base === false || $full === false || !str_starts_with($full, $base)) {
        return '';
    }
    return ltrim(str_replace('\\', '/', substr($full, strlen($base))), '/');
}

function validate_employee_document_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'message' => 'Please choose a file to upload.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload failed. Please try again.'];
    }
    if (($file['size'] ?? 0) > EMPLOYEE_DOCUMENT_MAX_BYTES) {
        return ['ok' => false, 'message' => 'File must be under 5 MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'message' => 'Only PDF, JPG or PNG files are allowed.'];
    }

    return [
        'ok' => true,
        'mime' => $mime,
        'ext' => $allowed[$mime],
        'original_filename' => basename((string) ($file['name'] ?? 'document')),
    ];
}

function normalize_employee_document_type(string $doc_type): ?string
{
    $doc_type = strtolower(trim($doc_type));
    return array_key_exists($doc_type, employee_document_types()) ? $doc_type : null;
}

function employee_has_pending_document_request($conn, string $emp_id, string $doc_type): bool
{
    $stmt = $conn->prepare("SELECT id FROM employee_document_requests WHERE emp_id = ? AND doc_type = ? AND request_status = 'pending' LIMIT 1");
    $stmt->bind_param('ss', $emp_id, $doc_type);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function get_employee_active_document_by_type($conn, string $emp_id, string $doc_type): ?array
{
    if ($doc_type === 'other') {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM employee_documents WHERE emp_id = ? AND doc_type = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $emp_id, $doc_type);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function get_employee_documents($conn, string $emp_id, bool $active_only = true): array
{
    $sql = 'SELECT * FROM employee_documents WHERE emp_id = ?';
    if ($active_only) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY doc_type ASC, id DESC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return payroll_fetch_all_assoc($stmt->get_result());
}

function get_employee_document_requests($conn, string $emp_id, int $limit = 20): array
{
    $stmt = $conn->prepare('SELECT * FROM employee_document_requests WHERE emp_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bind_param('si', $emp_id, $limit);
    $stmt->execute();
    return payroll_fetch_all_assoc($stmt->get_result());
}

function get_pending_document_requests($conn, $branch_id = null): array
{
    $sql = "
        SELECT r.*, e.name AS employee_name
        FROM employee_document_requests r
        INNER JOIN employees e ON e.emp_id = r.emp_id
        WHERE r.request_status = 'pending'
    ";
    if ($branch_id !== null) {
        $sql .= ' AND r.branch_id = ?';
        $stmt = $conn->prepare($sql . ' ORDER BY r.created_at ASC');
        $stmt->bind_param('i', $branch_id);
    } else {
        $stmt = $conn->prepare($sql . ' ORDER BY r.created_at ASC');
    }
    $stmt->execute();
    return payroll_fetch_all_assoc($stmt->get_result());
}

function create_employee_document_request($conn, string $emp_id, int $branch_id, string $doc_type, array $file, string $doc_label = '', string $note = ''): array
{
    $doc_type = normalize_employee_document_type($doc_type);
    if ($doc_type === null) {
        return ['ok' => false, 'message' => 'Invalid document type.'];
    }

    $employee = get_employee_portal_profile($conn, $emp_id);
    if (!$employee) {
        return ['ok' => false, 'message' => 'Employee record not found.'];
    }

    $doc_label = trim($doc_label);
    if ($doc_type === 'other' && $doc_label === '') {
        return ['ok' => false, 'message' => 'Please enter a name for the other document.'];
    }
    if ($doc_type !== 'other') {
        $doc_label = employee_document_type_label($doc_type);
        if (employee_has_pending_document_request($conn, $emp_id, $doc_type)) {
            return ['ok' => false, 'message' => 'You already have a pending ' . employee_document_type_label($doc_type) . ' upload awaiting approval.'];
        }
    }

    $validation = validate_employee_document_upload($file);
    if (!$validation['ok']) {
        return ['ok' => false, 'message' => $validation['message']];
    }

    $stmt = $conn->prepare('
        INSERT INTO employee_document_requests
        (emp_id, branch_id, doc_type, doc_label, file_path, original_filename, mime_type, file_size, employee_note, request_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $pending = 'pending';
    $placeholder_path = '';
    $original = $validation['original_filename'];
    $mime = $validation['mime'];
    $size = (int) $file['size'];
    $note = trim($note);
    $stmt->bind_param('sisssssiss', $emp_id, $branch_id, $doc_type, $doc_label, $placeholder_path, $original, $mime, $size, $note, $pending);
    if (!$stmt->execute()) {
        return ['ok' => false, 'message' => 'Could not save document request.'];
    }

    $request_id = (int) $conn->insert_id;
    $dest_dir = employee_documents_emp_dir($emp_id, 'pending');
    $filename = $request_id . '_' . $doc_type . '.' . $validation['ext'];
    $dest_abs = $dest_dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest_abs)) {
        $del = $conn->prepare('DELETE FROM employee_document_requests WHERE id = ?');
        $del->bind_param('i', $request_id);
        $del->execute();
        return ['ok' => false, 'message' => 'Could not store uploaded file.'];
    }

    $relative = employee_document_relative_path($dest_abs);
    $upd = $conn->prepare('UPDATE employee_document_requests SET file_path = ? WHERE id = ?');
    $upd->bind_param('si', $relative, $request_id);
    $upd->execute();

    return [
        'ok' => true,
        'message' => employee_document_type_label($doc_type) . ' uploaded and sent to admin for approval.',
    ];
}

function employee_document_absolute_path(?string $relative_path): ?string
{
    $relative_path = trim((string) $relative_path);
    if ($relative_path === '') {
        return null;
    }
    $full = dirname(__DIR__) . '/' . str_replace(['../', '..\\'], '', $relative_path);
    return is_file($full) ? $full : null;
}

function deactivate_employee_documents_by_type($conn, string $emp_id, string $doc_type): void
{
    if ($doc_type === 'other') {
        return;
    }
    $stmt = $conn->prepare('UPDATE employee_documents SET is_active = 0 WHERE emp_id = ? AND doc_type = ? AND is_active = 1');
    $stmt->bind_param('ss', $emp_id, $doc_type);
    $stmt->execute();
}

function approve_document_request($conn, int $request_id, string $reviewer, string $note = ''): array
{
    $stmt = $conn->prepare("SELECT * FROM employee_document_requests WHERE id = ? AND request_status = 'pending'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    if (!$req) {
        return ['ok' => false, 'message' => 'Document request not found or already processed.'];
    }

    $src = employee_document_absolute_path($req['file_path']);
    if ($src === null) {
        return ['ok' => false, 'message' => 'Uploaded file is missing. Ask the employee to upload again.'];
    }

    $ext = pathinfo($src, PATHINFO_EXTENSION);
    $dest_dir = employee_documents_emp_dir($req['emp_id'], 'approved');
    $dest_filename = $request_id . '_' . $req['doc_type'] . '.' . $ext;
    $dest_abs = $dest_dir . '/' . $dest_filename;
    if (!copy($src, $dest_abs)) {
        return ['ok' => false, 'message' => 'Could not finalize document file.'];
    }

    deactivate_employee_documents_by_type($conn, $req['emp_id'], $req['doc_type']);

    $relative = employee_document_relative_path($dest_abs);
    $now = date('Y-m-d H:i:s');
    $ins = $conn->prepare('
        INSERT INTO employee_documents
        (emp_id, doc_type, doc_label, file_path, original_filename, mime_type, file_size, approved_by, approved_at, request_id, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ');
    $ins->bind_param(
        'ssssssissi',
        $req['emp_id'],
        $req['doc_type'],
        $req['doc_label'],
        $relative,
        $req['original_filename'],
        $req['mime_type'],
        $req['file_size'],
        $reviewer,
        $now,
        $request_id
    );
    if (!$ins->execute()) {
        @unlink($dest_abs);
        return ['ok' => false, 'message' => 'Could not save approved document record.'];
    }

    $note = trim($note);
    $approved = 'approved';
    $upd = $conn->prepare("UPDATE employee_document_requests SET request_status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE id = ?");
    $upd->bind_param('ssssi', $approved, $reviewer, $now, $note, $request_id);
    $upd->execute();

    @unlink($src);

    return ['ok' => true, 'message' => 'Document approved and saved to employee profile.'];
}

function reject_document_request($conn, int $request_id, string $reviewer, string $note = ''): array
{
    $stmt = $conn->prepare("SELECT * FROM employee_document_requests WHERE id = ? AND request_status = 'pending'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    if (!$req) {
        return ['ok' => false, 'message' => 'Document request not found or already processed.'];
    }

    $src = employee_document_absolute_path($req['file_path']);
    if ($src !== null) {
        @unlink($src);
    }

    $now = date('Y-m-d H:i:s');
    $note = trim($note);
    $rejected = 'rejected';
    $upd = $conn->prepare("UPDATE employee_document_requests SET request_status = ?, reviewed_by = ?, reviewed_at = ?, review_note = ? WHERE id = ?");
    $upd->bind_param('ssssi', $rejected, $reviewer, $now, $note, $request_id);
    $upd->execute();

    return ['ok' => true, 'message' => 'Document request rejected.'];
}

function get_employee_document_by_id($conn, int $doc_id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM employee_documents WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $doc_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function get_employee_document_by_request_id($conn, int $request_id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM employee_documents WHERE request_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function get_employee_document_request_by_id($conn, int $request_id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM employee_document_requests WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function format_employee_document_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

function employee_document_status_for_type($conn, string $emp_id, string $doc_type): array
{
    if (employee_has_pending_document_request($conn, $emp_id, $doc_type)) {
        return ['status' => 'pending', 'label' => 'Pending approval'];
    }
    $active = get_employee_active_document_by_type($conn, $emp_id, $doc_type);
    if ($active) {
        return ['status' => 'approved', 'label' => 'Approved', 'document' => $active];
    }
    return ['status' => 'missing', 'label' => 'Not uploaded'];
}

function stream_employee_document_file(array $record, string $download_name = ''): void
{
    $path = employee_document_absolute_path($record['file_path'] ?? '');
    if ($path === null) {
        http_response_code(404);
        echo 'File not found.';
        exit;
    }

    $download_name = $download_name !== '' ? $download_name : ($record['original_filename'] ?? basename($path));
    $mime = $record['mime_type'] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $download_name) . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}
