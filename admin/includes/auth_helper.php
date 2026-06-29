<?php

function admin_permission_keys(): array
{
    return [
        'dashboard' => 'Dashboard',
        'employees' => 'Employees',
        'masters' => 'Departments & designations',
        'org' => 'Org chart',
        'calendar' => 'Calendar & announcements',
        'attendance' => 'Attendance & punch',
        'leave' => 'Leave management',
        'payroll' => 'Payroll center',
        'slips' => 'Salary slips',
        'reports' => 'Reports',
        'recruitment' => 'Recruitment',
        'performance' => 'Performance reviews',
        'expenses' => 'Expenses',
        'assets' => 'Assets',
        'exits' => 'Exit & F&F',
        'announcements' => 'Announcements',
        'approvals' => 'Approvals',
        'settings' => 'System settings',
        'roles' => 'Admin roles',
    ];
}

function sync_logged_in_admin_permissions($conn): void
{
    if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_username'])) {
        return;
    }
    $stmt = $conn->prepare('SELECT role_id FROM admin_users WHERE username = ? LIMIT 1');
    $uname = $_SESSION['admin_username'];
    $stmt->bind_param('s', $uname);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return;
    }
    $role_id = $row['role_id'] !== null ? (int) $row['role_id'] : null;
    $session_role = isset($_SESSION['admin_role_id']) ? (int) $_SESSION['admin_role_id'] : null;
    if ($role_id !== $session_role || !array_key_exists('admin_permissions', $_SESSION)) {
        load_admin_role_into_session($conn, $role_id);
    }
}

function refresh_admin_permissions_for_role($conn, int $role_id): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        return;
    }
    $session_role = isset($_SESSION['admin_role_id']) ? (int) $_SESSION['admin_role_id'] : null;
    if ($session_role === $role_id) {
        load_admin_role_into_session($conn, $role_id);
    }
}

function load_admin_role_into_session($conn, ?int $role_id): void
{
    $_SESSION['admin_role_id'] = $role_id;
    $_SESSION['admin_role_code'] = null;
    $_SESSION['admin_permissions'] = [];

    if ($role_id === null || $role_id <= 0) {
        return;
    }

    $stmt = $conn->prepare('SELECT code FROM admin_roles WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return;
    }

    $_SESSION['admin_role_code'] = $row['code'];
    $perms_stmt = $conn->prepare('SELECT permission_key FROM admin_role_permissions WHERE role_id = ?');
    $perms_stmt->bind_param('i', $role_id);
    $perms_stmt->execute();
    $res = $perms_stmt->get_result();
    $perms = [];
    while ($p = $res->fetch_assoc()) {
        $perms[] = $p['permission_key'];
    }
    $_SESSION['admin_permissions'] = $perms;
}

function get_admin_role_code(): ?string
{
    return $_SESSION['admin_role_code'] ?? null;
}

function admin_has_wildcard_permission(): bool
{
    $perms = $_SESSION['admin_permissions'] ?? [];
    return in_array('*', $perms, true);
}

function has_permission(string $key): bool
{
    if (is_super_admin()) {
        return true;
    }

    $perms = $_SESSION['admin_permissions'] ?? [];
    if (in_array('*', $perms, true)) {
        return true;
    }

    return in_array($key, $perms, true);
}

function require_permission(string $key, string $redirect = 'dashboard.php'): void
{
    if (!has_permission($key)) {
        $_SESSION['flash_message'] = 'You do not have permission to access that section.';
        $_SESSION['flash_success'] = false;
        header('Location: ' . $redirect);
        exit;
    }
}

function get_admin_roles($conn): array
{
    $rows = [];
    $res = $conn->query('SELECT * FROM admin_roles ORDER BY is_system DESC, name ASC');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function get_role_permissions($conn, int $role_id): array
{
    $stmt = $conn->prepare('SELECT permission_key FROM admin_role_permissions WHERE role_id = ?');
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $keys = [];
    while ($row = $res->fetch_assoc()) {
        $keys[] = $row['permission_key'];
    }
    return $keys;
}

function save_role_permissions($conn, int $role_id, array $keys): array
{
    $stmt = $conn->prepare('SELECT is_system, code FROM admin_roles WHERE id = ?');
    $stmt->bind_param('i', $role_id);
    $stmt->execute();
    $role = $stmt->get_result()->fetch_assoc();
    if (!$role) {
        return ['ok' => false, 'message' => 'Role not found.'];
    }
    if ($role['code'] === 'super_admin') {
        return ['ok' => false, 'message' => 'Super Admin permissions cannot be changed.'];
    }

    $conn->query('DELETE FROM admin_role_permissions WHERE role_id = ' . (int) $role_id);
    $ins = $conn->prepare('INSERT INTO admin_role_permissions (role_id, permission_key) VALUES (?, ?)');
    foreach ($keys as $key) {
        $key = trim((string) $key);
        if ($key === '') {
            continue;
        }
        $ins->bind_param('is', $role_id, $key);
        $ins->execute();
    }

    return ['ok' => true, 'message' => 'Role permissions updated.'];
}
