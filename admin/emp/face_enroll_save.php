<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/employee_portal_auth.php';
enforce_employee_session();
require_once __DIR__ . '/../includes/csrf_helper.php';
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/face_biometric_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$payload = read_json_request_body();
require_csrf_json_or_fail($payload);

if (!employee_face_login_enabled($conn)) {
    echo json_encode(['ok' => false, 'message' => 'Face login is disabled by admin.']);
    exit;
}

$emp_id = get_logged_in_employee_id();
if ($emp_id === '') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Please sign in first.']);
    exit;
}

$samples = $payload['samples'] ?? [];
if (!is_array($samples)) {
    $samples = [];
}

$validation = validate_face_enroll_samples($samples);
if (!$validation['ok']) {
    echo json_encode($validation);
    exit;
}

if (!save_employee_face_descriptor($conn, $emp_id, $validation['descriptor'])) {
    echo json_encode(['ok' => false, 'message' => 'Could not save face data. Please try again.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Face enrolled successfully. You can now sign in with your face.',
    'redirect' => 'face_enroll.php',
]);
