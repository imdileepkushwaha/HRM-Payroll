<?php

/** Super admin session value — view all branches (no filter). */
if (!defined('BRANCH_FILTER_ALL')) {
    define('BRANCH_FILTER_ALL', 0);
}

/** Default branch when login branch picker is hidden (Indra Nagar). */
if (!defined('DEFAULT_BRANCH_ID')) {
    define('DEFAULT_BRANCH_ID', 1);
}

/** Set true later to show branch dropdown on admin login and topbar switcher. */
if (!defined('SHOW_BRANCH_SELECTOR')) {
    define('SHOW_BRANCH_SELECTOR', false);
}

function get_branches($conn)
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    $r = $conn->query('SELECT id, code, name, is_active FROM branches WHERE is_active = 1 ORDER BY id');
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $cache[] = $row;
        }
    }
    return $cache;
}

function get_all_branches_for_admin($conn): array
{
    $rows = [];
    $r = $conn->query('SELECT id, code, name, is_active FROM branches ORDER BY is_active DESC, id ASC');
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function count_branch_employees($conn, int $branch_id): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM employees WHERE branch_id = ?');
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) ($row['c'] ?? 0);
}

function count_branch_admin_users($conn, int $branch_id): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM admin_users WHERE branch_id = ?');
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) ($row['c'] ?? 0);
}

function count_active_branches($conn): int
{
    $r = $conn->query('SELECT COUNT(*) AS c FROM branches WHERE is_active = 1');
    $row = $r ? $r->fetch_assoc() : null;

    return (int) ($row['c'] ?? 0);
}

function add_branch($conn, string $code, string $name): array
{
    $code = strtoupper(trim($code));
    $name = trim($name);
    if ($code === '' || !preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
        return ['ok' => false, 'message' => 'Branch code must be 2–20 letters, numbers, dash or underscore.'];
    }
    if ($name === '' || strlen($name) > 100) {
        return ['ok' => false, 'message' => 'Branch name is required (max 100 characters).'];
    }

    $check = $conn->prepare('SELECT id FROM branches WHERE code = ?');
    $check->bind_param('s', $code);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
        return ['ok' => false, 'message' => 'Branch code already exists.'];
    }

    $stmt = $conn->prepare('INSERT INTO branches (code, name, is_active) VALUES (?, ?, 1)');
    $stmt->bind_param('ss', $code, $name);
    if (!$stmt->execute()) {
        return ['ok' => false, 'message' => 'Could not add branch.'];
    }

    return ['ok' => true, 'message' => 'Branch added.'];
}

function deactivate_branch($conn, int $branch_id): array
{
    if ($branch_id < 1) {
        return ['ok' => false, 'message' => 'Invalid branch.'];
    }

    $branch = $conn->prepare('SELECT id, code, name, is_active FROM branches WHERE id = ?');
    $branch->bind_param('i', $branch_id);
    $branch->execute();
    $row = $branch->get_result()->fetch_assoc();
    if (!$row) {
        return ['ok' => false, 'message' => 'Branch not found.'];
    }
    if ((int) ($row['is_active'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'Branch is already inactive.'];
    }

    if (count_active_branches($conn) <= 1) {
        return ['ok' => false, 'message' => 'Cannot remove the last active branch.'];
    }

    $emp_count = count_branch_employees($conn, $branch_id);
    if ($emp_count > 0) {
        return [
            'ok' => false,
            'message' => 'Cannot remove branch with ' . $emp_count . ' employee(s). Reassign or move them first.',
        ];
    }

    $admin_count = count_branch_admin_users($conn, $branch_id);
    if ($admin_count > 0) {
        return [
            'ok' => false,
            'message' => 'Cannot remove branch with ' . $admin_count . ' admin user(s). Change their branch access first.',
        ];
    }

    $stmt = $conn->prepare('UPDATE branches SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $branch_id);
    if (!$stmt->execute()) {
        return ['ok' => false, 'message' => 'Could not deactivate branch.'];
    }

    return ['ok' => true, 'message' => 'Branch "' . ($row['name'] ?? '') . '" removed.'];
}

function get_branch_by_id($conn, $branch_id)
{
    foreach (get_branches($conn) as $branch) {
        if ((int) $branch['id'] === (int) $branch_id) {
            return $branch;
        }
    }
    return null;
}

function get_branch_label($conn, $branch_id)
{
    if (!$branch_id) {
        return 'All Branches';
    }
    $branch = get_branch_by_id($conn, $branch_id);
    return $branch ? $branch['name'] : 'Branch';
}

function is_super_admin()
{
    return !isset($_SESSION['admin_branch_id']) || $_SESSION['admin_branch_id'] === null || $_SESSION['admin_branch_id'] === '';
}

function get_admin_branch_id()
{
    if (is_super_admin()) {
        return null;
    }
    return (int) $_SESSION['admin_branch_id'];
}

function get_active_branch_id()
{
    if (!is_super_admin()) {
        return get_admin_branch_id();
    }
    $active = $_SESSION['admin_active_branch_id'] ?? BRANCH_FILTER_ALL;
    if ((int) $active === BRANCH_FILTER_ALL) {
        return null;
    }
    return (int) $active;
}

function set_active_branch_id($branch_id)
{
    if (!is_super_admin()) {
        return false;
    }
    if ($branch_id === null || (int) $branch_id === BRANCH_FILTER_ALL) {
        $_SESSION['admin_active_branch_id'] = BRANCH_FILTER_ALL;
        return true;
    }
    $_SESSION['admin_active_branch_id'] = (int) $branch_id;
    return true;
}

function branch_employees_sql($alias = 'employees')
{
    $branch_id = get_active_branch_id();
    if ($branch_id === null) {
        return ['sql' => '', 'types' => '', 'params' => []];
    }
    $col = $alias === '' ? 'branch_id' : $alias . '.branch_id';
    return ['sql' => " AND $col = ?", 'types' => 'i', 'params' => [$branch_id]];
}

function branch_id_for_write()
{
    $branch_id = get_active_branch_id();
    if ($branch_id !== null) {
        return $branch_id;
    }
    return 1;
}

function require_branch_context_for_write()
{
    if (get_active_branch_id() !== null) {
        return get_active_branch_id();
    }
    $_SESSION['flash_message'] = 'Select a branch from the top bar before performing this action.';
    $_SESSION['flash_success'] = false;
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit;
}

function employee_belongs_to_active_branch($employee)
{
    $branch_id = get_active_branch_id();
    if ($branch_id === null) {
        return true;
    }
    return (int) ($employee['branch_id'] ?? 0) === $branch_id;
}

function require_employee_branch_access($conn, $emp_id, $redirect = 'employees.php')
{
    $stmt = $conn->prepare('SELECT * FROM employees WHERE emp_id = ?');
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    if (!$employee) {
        $_SESSION['flash_message'] = 'Employee not found.';
        $_SESSION['flash_success'] = false;
        header('Location: ' . $redirect);
        exit;
    }
    if (!employee_belongs_to_active_branch($employee)) {
        $_SESSION['flash_message'] = 'You do not have access to this employee\'s branch.';
        $_SESSION['flash_success'] = false;
        header('Location: ' . $redirect);
        exit;
    }
    return $employee;
}

function validate_login_branch($user_branch_id, $selected_branch_id)
{
    $selected = (int) $selected_branch_id;
    if ($user_branch_id === null || $user_branch_id === '') {
        return $selected === BRANCH_FILTER_ALL || $selected === 1 || $selected === 2;
    }
    return (int) $user_branch_id === $selected;
}

function bind_branch_stmt_params($stmt, $types, $params)
{
    if ($types === '') {
        return;
    }
    $stmt->bind_param($types, ...$params);
}
