<?php

require_once __DIR__ . '/employee_portal_helper.php';

define('FACE_DESCRIPTOR_SIZE', 128);
define('FACE_MATCH_THRESHOLD', 0.55);
define('FACE_ENROLL_SAMPLE_MAX_DISTANCE', 0.45);
define('FACE_LOGIN_MAX_ATTEMPTS', 8);
define('FACE_LOGIN_WINDOW_SECONDS', 900);

function employee_face_login_enabled($conn): bool
{
    if (!function_exists('get_setting')) {
        require_once __DIR__ . '/settings_helper.php';
    }
    return get_setting($conn, 'employee_face_login_enabled', '1') === '1';
}

function face_descriptor_distance(array $a, array $b): float
{
    if (count($a) !== FACE_DESCRIPTOR_SIZE || count($b) !== FACE_DESCRIPTOR_SIZE) {
        return PHP_FLOAT_MAX;
    }
    $sum = 0.0;
    for ($i = 0; $i < FACE_DESCRIPTOR_SIZE; $i++) {
        $delta = (float) $a[$i] - (float) $b[$i];
        $sum += $delta * $delta;
    }
    return sqrt($sum);
}

function average_face_descriptors(array $descriptors): ?array
{
    if ($descriptors === []) {
        return null;
    }
    foreach ($descriptors as $descriptor) {
        if (!is_array($descriptor) || count($descriptor) !== FACE_DESCRIPTOR_SIZE) {
            return null;
        }
    }

    $avg = array_fill(0, FACE_DESCRIPTOR_SIZE, 0.0);
    $count = count($descriptors);
    foreach ($descriptors as $descriptor) {
        for ($i = 0; $i < FACE_DESCRIPTOR_SIZE; $i++) {
            $avg[$i] += (float) $descriptor[$i];
        }
    }
    for ($i = 0; $i < FACE_DESCRIPTOR_SIZE; $i++) {
        $avg[$i] /= $count;
    }

    return $avg;
}

function normalize_face_descriptor_input($raw): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $values = [];
    foreach ($raw as $value) {
        if (!is_numeric($value)) {
            return null;
        }
        $values[] = (float) $value;
    }
    if (count($values) !== FACE_DESCRIPTOR_SIZE) {
        return null;
    }
    return $values;
}

function validate_face_enroll_samples(array $samples): array
{
    if (count($samples) < 3) {
        return ['ok' => false, 'message' => 'Capture at least 3 face samples.'];
    }
    if (count($samples) > 5) {
        return ['ok' => false, 'message' => 'Too many face samples.'];
    }

    $normalized = [];
    foreach ($samples as $sample) {
        $descriptor = normalize_face_descriptor_input($sample);
        if ($descriptor === null) {
            return ['ok' => false, 'message' => 'Invalid face data. Please try again.'];
        }
        $normalized[] = $descriptor;
    }

    $count = count($normalized);
    for ($i = 0; $i < $count; $i++) {
        for ($j = $i + 1; $j < $count; $j++) {
            if (face_descriptor_distance($normalized[$i], $normalized[$j]) > FACE_ENROLL_SAMPLE_MAX_DISTANCE) {
                return ['ok' => false, 'message' => 'Face samples did not match each other. Hold still and try again in good light.'];
            }
        }
    }

    $avg = average_face_descriptors($normalized);
    if ($avg === null) {
        return ['ok' => false, 'message' => 'Could not process face samples.'];
    }

    return ['ok' => true, 'descriptor' => $avg];
}

function get_employee_face_descriptor($conn, string $emp_id): ?array
{
    $stmt = $conn->prepare(
        'SELECT face_descriptor FROM employee_face_biometrics WHERE emp_id = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return null;
    }

    $decoded = json_decode($row['face_descriptor'], true);
    return normalize_face_descriptor_input($decoded);
}

function employee_has_face_enrolled($conn, string $emp_id): bool
{
    return get_employee_face_descriptor($conn, $emp_id) !== null;
}

function get_employee_face_enrollment_meta($conn, string $emp_id): ?array
{
    $stmt = $conn->prepare(
        'SELECT enrolled_at, updated_at FROM employee_face_biometrics WHERE emp_id = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function save_employee_face_descriptor($conn, string $emp_id, array $descriptor): bool
{
    $json = json_encode($descriptor, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        'INSERT INTO employee_face_biometrics (emp_id, face_descriptor, enrolled_at, updated_at, is_active)
         VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE face_descriptor = VALUES(face_descriptor), updated_at = VALUES(updated_at), is_active = 1'
    );
    $stmt->bind_param('ssss', $emp_id, $json, $now, $now);
    return $stmt->execute();
}

function remove_employee_face_enrollment($conn, string $emp_id): void
{
    $stmt = $conn->prepare('DELETE FROM employee_face_biometrics WHERE emp_id = ?');
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
}

function face_login_is_rate_limited(): bool
{
    require_once __DIR__ . '/employee_portal_auth.php';
    init_employee_session();

    $bucket = $_SESSION['face_login_attempts'] ?? ['count' => 0, 'started_at' => time()];
    if (time() - (int) $bucket['started_at'] > FACE_LOGIN_WINDOW_SECONDS) {
        $_SESSION['face_login_attempts'] = ['count' => 0, 'started_at' => time()];
        return false;
    }

    return (int) $bucket['count'] >= FACE_LOGIN_MAX_ATTEMPTS;
}

function record_face_login_failure(): void
{
    require_once __DIR__ . '/employee_portal_auth.php';
    init_employee_session();

    $bucket = $_SESSION['face_login_attempts'] ?? ['count' => 0, 'started_at' => time()];
    if (time() - (int) $bucket['started_at'] > FACE_LOGIN_WINDOW_SECONDS) {
        $bucket = ['count' => 0, 'started_at' => time()];
    }
    $bucket['count'] = (int) $bucket['count'] + 1;
    $_SESSION['face_login_attempts'] = $bucket;
}

function clear_face_login_attempts(): void
{
    require_once __DIR__ . '/employee_portal_auth.php';
    init_employee_session();
    unset($_SESSION['face_login_attempts']);
}

function match_employee_face_descriptor($conn, string $emp_id, array $probe): array
{
    $stored = get_employee_face_descriptor($conn, $emp_id);
    if ($stored === null) {
        return ['ok' => false, 'message' => 'Face login is not set up for this employee. Sign in with password and enroll your face first.'];
    }

    $distance = face_descriptor_distance($stored, $probe);
    if ($distance > FACE_MATCH_THRESHOLD) {
        return ['ok' => false, 'message' => 'Face did not match. Try again or use password login.'];
    }

    return ['ok' => true, 'distance' => $distance];
}

function authenticate_employee_with_face($conn, string $emp_id, array $probe): array
{
    $emp_id = strtoupper(trim($emp_id));
    if ($emp_id === '') {
        return ['ok' => false, 'message' => 'Employee ID is required.'];
    }

    if (!employee_face_login_enabled($conn)) {
        return ['ok' => false, 'message' => 'Face login is disabled by admin.'];
    }

    if (face_login_is_rate_limited()) {
        return ['ok' => false, 'message' => 'Too many failed face login attempts. Please wait 15 minutes or use password login.'];
    }

    $probe = normalize_face_descriptor_input($probe);
    if ($probe === null) {
        record_face_login_failure();
        return ['ok' => false, 'message' => 'Invalid face data. Please try again.'];
    }

    $employee = get_employee_portal_profile($conn, $emp_id);
    if (!$employee) {
        record_face_login_failure();
        return ['ok' => false, 'message' => 'Invalid Employee ID or account is inactive.'];
    }

    $match = match_employee_face_descriptor($conn, $emp_id, $probe);
    if (!$match['ok']) {
        record_face_login_failure();
        return $match;
    }

    clear_face_login_attempts();
    require_once __DIR__ . '/employee_portal_auth.php';
    set_employee_session_on_login($emp_id, (int) $employee['branch_id'], $employee['name']);

    return ['ok' => true, 'message' => 'Signed in successfully.', 'redirect' => 'dashboard.php'];
}
