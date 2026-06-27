<?php

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrf_uses_employee_flash(): bool
{
    return str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/emp/');
}

function verify_csrf()
{
    $sent = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        $message = 'Security check failed. Please refresh the page and try again.';
        if (csrf_uses_employee_flash()) {
            $_SESSION['emp_flash_message'] = $message;
            $_SESSION['emp_flash_success'] = false;
        } else {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_success'] = false;
        }
        return false;
    }
    return true;
}

function require_csrf_or_redirect($redirect = 'dashboard.php')
{
    if (!verify_csrf()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function read_json_request_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function verify_csrf_from_request(array $payload = []): bool
{
    $sent = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $payload['csrf_token'] ?? '');
    $expected = (string) ($_SESSION['csrf_token'] ?? '');
    if ($sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        return false;
    }
    return true;
}

function require_csrf_json_or_fail(array $payload = []): void
{
    if (!verify_csrf_from_request($payload)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Security check failed. Refresh the page and try again.']);
        exit;
    }
}
