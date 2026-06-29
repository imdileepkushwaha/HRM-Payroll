<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_permission('settings');
require_once 'includes/settings_helper.php';
require_once 'includes/auth_helper.php';
require 'includes/signature_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

require_csrf_or_redirect('settings.php');

$section = $_POST['section'] ?? '';

if ($section === 'smtp') {
    set_setting($conn, 'smtp_host', trim($_POST['smtp_host'] ?? ''));
    set_setting($conn, 'smtp_port', trim($_POST['smtp_port'] ?? '587'));
    set_setting($conn, 'smtp_encryption', trim($_POST['smtp_encryption'] ?? 'tls'));
    set_setting($conn, 'smtp_username', trim($_POST['smtp_username'] ?? ''));
    set_setting($conn, 'smtp_from_email', trim($_POST['smtp_from_email'] ?? ''));
    set_setting($conn, 'smtp_from_name', trim($_POST['smtp_from_name'] ?? 'Payroll System'));

    $new_pass = $_POST['smtp_password'] ?? '';
    if ($new_pass !== '') {
        set_setting($conn, 'smtp_password', $new_pass);
    }

    $_SESSION['flash_message'] = 'SMTP settings saved.';
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=smtp');
    exit;
}

if ($section === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $_SESSION['flash_message'] = 'New passwords do not match.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=password');
        exit;
    }

    if (strlen($new) < 6) {
        $_SESSION['flash_message'] = 'Password must be at least 6 characters.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=password');
        exit;
    }

    $username = $_SESSION['admin_username'];
    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($current, $row['password'])) {
        $_SESSION['flash_message'] = 'Current password is incorrect.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=password');
        exit;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
    $stmt->bind_param('ss', $hash, $username);
    $stmt->execute();

    $_SESSION['flash_message'] = 'Password updated successfully.';
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=password');
    exit;
}

if ($section === 'payroll') {
    set_setting($conn, 'company_name', trim($_POST['company_name'] ?? ''));
    set_setting($conn, 'hr_notify_emails', trim($_POST['hr_notify_emails'] ?? ''));
    set_setting($conn, 'company_email', trim($_POST['company_email'] ?? ''));
    set_setting($conn, 'careers_public_enabled', !empty($_POST['careers_public_enabled']) ? '1' : '0');
    set_setting($conn, 'company_policies_html', trim($_POST['company_policies_html'] ?? ''));
    set_setting($conn, 'working_days_per_month', trim($_POST['working_days_per_month'] ?? '26'));
    set_setting($conn, 'signature_authority_name', trim($_POST['signature_authority_name'] ?? 'Authorized Signatory'));
    set_setting($conn, 'pct_basic', trim($_POST['pct_basic'] ?? '50'));
    set_setting($conn, 'pct_hra', trim($_POST['pct_hra'] ?? '20'));
    set_setting($conn, 'pct_conveyance', trim($_POST['pct_conveyance'] ?? '5'));
    set_setting($conn, 'pct_medical', trim($_POST['pct_medical'] ?? '5'));
    set_setting($conn, 'pct_special', trim($_POST['pct_special'] ?? '20'));
    set_setting($conn, 'pf_percent', trim($_POST['pf_percent'] ?? '12'));
    set_setting($conn, 'pf_min_limit', trim($_POST['pf_min_limit'] ?? '0'));
    set_setting($conn, 'pf_max_limit', trim($_POST['pf_max_limit'] ?? '15000'));
    set_setting($conn, 'professional_tax', trim($_POST['professional_tax'] ?? '200'));
    set_setting($conn, 'esi_percent', trim($_POST['esi_percent'] ?? '0.75'));
    set_setting($conn, 'esi_gross_limit', trim($_POST['esi_gross_limit'] ?? '21000'));
    set_setting($conn, 'leave_day_credit', trim($_POST['leave_day_credit'] ?? '1'));
    set_setting($conn, 'half_day_credit', trim($_POST['half_day_credit'] ?? '0.5'));
    set_setting($conn, 'overtime_hours_per_day', trim($_POST['overtime_hours_per_day'] ?? '8'));
    set_setting($conn, 'overtime_multiplier', trim($_POST['overtime_multiplier'] ?? '1.5'));
    set_setting($conn, 'require_payroll_approval', !empty($_POST['require_payroll_approval']) ? '1' : '0');

    $messages = ['Payroll settings saved.'];

    if (!empty($_POST['remove_signature'])) {
        remove_payslip_signature();
        set_setting($conn, 'payslip_signature', '');
        $messages[] = 'Signature removed.';
    } elseif (isset($_FILES['payslip_signature']) && $_FILES['payslip_signature']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = save_payslip_signature_upload($_FILES['payslip_signature']);
        if ($result['success']) {
            set_setting($conn, 'payslip_signature', $result['path']);
            $messages[] = 'Signature uploaded for payslip PDF.';
        } else {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_success'] = false;
            header('Location: settings.php?tab=payroll');
            exit;
        }
    }

    $_SESSION['flash_message'] = implode(' ', $messages);
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=payroll');
    exit;
}

if ($section === 'leave') {
    set_setting($conn, 'leave_quota_pl', trim($_POST['leave_quota_pl'] ?? '13'));
    set_setting($conn, 'leave_quota_sl', trim($_POST['leave_quota_sl'] ?? '9'));
    set_setting($conn, 'leave_quota_cl', trim($_POST['leave_quota_cl'] ?? '8'));
    set_setting($conn, 'max_leaves_per_month', trim($_POST['max_leaves_per_month'] ?? '4'));
    set_setting($conn, 'max_wo_per_month', trim($_POST['max_wo_per_month'] ?? '4'));

    $_SESSION['flash_message'] = 'Leave and attendance rules saved.';
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=leave');
    exit;
}

if ($section === 'punch') {
    set_setting($conn, 'punch_enabled', !empty($_POST['punch_enabled']) ? '1' : '0');
    set_setting($conn, 'geo_attendance_enabled', !empty($_POST['geo_attendance_enabled']) ? '1' : '0');
    set_setting($conn, 'employee_face_login_enabled', !empty($_POST['employee_face_login_enabled']) ? '1' : '0');
    set_setting($conn, 'office_latitude', trim($_POST['office_latitude'] ?? ''));
    set_setting($conn, 'office_longitude', trim($_POST['office_longitude'] ?? ''));
    set_setting($conn, 'geo_fence_radius_meters', trim($_POST['geo_fence_radius_meters'] ?? '200'));
    $office_start = trim($_POST['office_start_time'] ?? '09:30');
    $office_end = trim($_POST['office_end_time'] ?? '18:30');
    if (!preg_match('/^\d{2}:\d{2}$/', $office_start)) {
        $office_start = '09:30';
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $office_end)) {
        $office_end = '18:30';
    }
    if (strtotime($office_end) <= strtotime($office_start)) {
        $_SESSION['flash_message'] = 'Office end time must be after start time.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=punch');
        exit;
    }
    set_setting($conn, 'office_start_time', $office_start);
    set_setting($conn, 'office_end_time', $office_end);
    set_setting($conn, 'late_grace_minutes', (string) max(0, min(120, (int) ($_POST['late_grace_minutes'] ?? 10))));
    set_setting($conn, 'half_day_on_late_in', !empty($_POST['half_day_on_late_in']) ? '1' : '0');
    set_setting($conn, 'half_day_on_early_out', !empty($_POST['half_day_on_early_out']) ? '1' : '0');
    $missing_out = strtolower(trim($_POST['missing_punch_out_status'] ?? 'half_day'));
    set_setting($conn, 'missing_punch_out_status', $missing_out === 'absent' ? 'absent' : 'half_day');
    set_setting($conn, 'auto_absent_no_punch', !empty($_POST['auto_absent_no_punch']) ? '1' : '0');
    set_setting($conn, 'late_count_for_half_day', (string) max(0, min(31, (int) ($_POST['late_count_for_half_day'] ?? 3))));
    set_setting($conn, 'punch_sync_overtime', !empty($_POST['punch_sync_overtime']) ? '1' : '0');
    set_setting($conn, 'block_punch_on_holiday_weekoff', !empty($_POST['block_punch_on_holiday_weekoff']) ? '1' : '0');

    $branch_lats = $_POST['branch_lat'] ?? [];
    $branch_lngs = $_POST['branch_lng'] ?? [];
    $branch_radii = $_POST['branch_radius'] ?? [];
    $branch_starts = $_POST['branch_start'] ?? [];
    $branch_ends = $_POST['branch_end'] ?? [];
    $branch_graces = $_POST['branch_grace'] ?? [];

    if (is_array($branch_lats)) {
        foreach ($branch_lats as $branch_id => $lat) {
            $branch_id = (int) $branch_id;
            if ($branch_id < 1) {
                continue;
            }
            $lng = trim($branch_lngs[$branch_id] ?? '');
            $radius = trim($branch_radii[$branch_id] ?? '');
            $lat = trim((string) $lat);
            $lat_val = $lat === '' ? null : $lat;
            $lng_val = $lng === '' ? null : $lng;
            $radius_val = $radius === '' ? null : (string) (int) $radius;
            $start_val = trim($branch_starts[$branch_id] ?? '');
            $end_val = trim($branch_ends[$branch_id] ?? '');
            $grace_val = trim($branch_graces[$branch_id] ?? '');
            $start_param = ($start_val !== '' && preg_match('/^\d{2}:\d{2}$/', $start_val)) ? $start_val : null;
            $end_param = ($end_val !== '' && preg_match('/^\d{2}:\d{2}$/', $end_val)) ? $end_val : null;
            $grace_param = ($grace_val !== '' && is_numeric($grace_val)) ? (int) $grace_val : null;

            $stmt = $conn->prepare('UPDATE branches SET office_latitude = ?, office_longitude = ?, geo_fence_radius_meters = ?, office_start_time = ?, office_end_time = ?, late_grace_minutes = ? WHERE id = ?');
            $stmt->bind_param('ssssssi', $lat_val, $lng_val, $radius_val, $start_param, $end_param, $grace_param, $branch_id);
            $stmt->execute();
        }
    }

    $_SESSION['flash_message'] = 'Punch and geo settings saved.';
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=punch');
    exit;
}

if ($section === 'branch_add') {
    require_once 'includes/branch_helper.php';
    if (!is_super_admin()) {
        $_SESSION['flash_message'] = 'Only Head Office can manage branches.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=branches');
        exit;
    }

    $result = add_branch($conn, (string) ($_POST['branch_code'] ?? ''), (string) ($_POST['branch_name'] ?? ''));
    $_SESSION['flash_message'] = $result['message'];
    $_SESSION['flash_success'] = $result['ok'];
    header('Location: settings.php?tab=branches');
    exit;
}

if ($section === 'branch_delete') {
    require_once 'includes/branch_helper.php';
    if (!is_super_admin()) {
        $_SESSION['flash_message'] = 'Only Head Office can manage branches.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=branches');
        exit;
    }

    $branch_id = (int) ($_POST['branch_id'] ?? 0);
    $result = deactivate_branch($conn, $branch_id);
    $_SESSION['flash_message'] = $result['message'];
    $_SESSION['flash_success'] = $result['ok'];
    header('Location: settings.php?tab=branches');
    exit;
}

if ($section === 'leave_type_add' || $section === 'leave_type_save') {
    require_once 'includes/payroll_extensions.php';
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $paid_credit = trim($_POST['paid_credit'] ?? '1');
    $is_active = !empty($_POST['is_active']) ? 1 : 0;
    $result = save_leave_type($conn, $code, $name, $paid_credit, $is_active);
    $_SESSION['flash_message'] = $result['message'];
    $_SESSION['flash_success'] = $result['ok'];
    header('Location: settings.php?tab=leave');
    exit;
}

if ($section === 'admins' || $section === 'admin_add') {
    if (!is_super_admin()) {
        $_SESSION['flash_message'] = 'Only Head Office can manage administrator accounts.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=admins');
        exit;
    }

    $action = $_POST['admin_action'] ?? '';

    if ($action === 'add') {
        $new_user = trim($_POST['new_username'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';
        $new_branch_raw = $_POST['new_branch_id'] ?? '';
        $new_branch_id = $new_branch_raw === '0' || $new_branch_raw === '' ? null : (int) $new_branch_raw;
        if ($new_branch_id !== null && !get_branch_by_id($conn, $new_branch_id)) {
            $new_branch_id = null;
        }
        if ($new_user === '' || strlen($new_pass) < 6 || $new_branch_raw === '') {
            $_SESSION['flash_message'] = 'Username, branch, and password (min 6 characters) are required.';
            $_SESSION['flash_success'] = false;
        } else {
            $role_id = (int) ($_POST['role_id'] ?? 0);
            if ($role_id <= 0) {
                $role_id = admin_role_id_by_code($conn, $new_branch_id === null ? 'super_admin' : 'branch_admin') ?? 0;
            }
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            if ($new_branch_id === null) {
                $stmt = $conn->prepare('INSERT INTO admin_users (username, password, branch_id, role_id) VALUES (?, ?, NULL, ?)');
                $stmt->bind_param('ssi', $new_user, $hash, $role_id);
            } else {
                $stmt = $conn->prepare('INSERT INTO admin_users (username, password, branch_id, role_id) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssii', $new_user, $hash, $new_branch_id, $role_id);
            }
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = 'Admin user added.';
                $_SESSION['flash_success'] = true;
            } else {
                $_SESSION['flash_message'] = 'Could not add user (username may exist).';
                $_SESSION['flash_success'] = false;
            }
        }
        header('Location: settings.php?tab=admins');
        exit;
    }

    if ($action === 'set_role') {
        $admin_id = (int) ($_POST['admin_id'] ?? 0);
        $role_id = (int) ($_POST['role_id'] ?? 0);
        if ($admin_id > 0 && $role_id > 0) {
            $stmt = $conn->prepare('UPDATE admin_users SET role_id = ? WHERE id = ?');
            $stmt->bind_param('ii', $role_id, $admin_id);
            if ($stmt->execute()) {
                require_once 'includes/audit_helper.php';
                log_admin_action($conn, 'set_admin_role', 'admin_user', (string) $admin_id, (string) $role_id);
                $cur_stmt = $conn->prepare('SELECT username FROM admin_users WHERE id = ?');
                $cur_stmt->bind_param('i', $admin_id);
                $cur_stmt->execute();
                $cur_row = $cur_stmt->get_result()->fetch_assoc();
                if ($cur_row && ($cur_row['username'] ?? '') === ($_SESSION['admin_username'] ?? '')) {
                    load_admin_role_into_session($conn, $role_id);
                }
                $_SESSION['flash_message'] = 'Administrator role updated. Permissions refresh on next page load.';
                $_SESSION['flash_success'] = true;
            } else {
                $_SESSION['flash_message'] = 'Could not update role.';
                $_SESSION['flash_success'] = false;
            }
        }
        header('Location: settings.php?tab=admins');
        exit;
    }

    if ($action === 'delete') {
        $del_user = trim($_POST['delete_username'] ?? '');
        if ($del_user === $_SESSION['admin_username']) {
            $_SESSION['flash_message'] = 'You cannot delete your own account while logged in.';
            $_SESSION['flash_success'] = false;
        } elseif ($del_user !== '') {
            $stmt = $conn->prepare('DELETE FROM admin_users WHERE username = ?');
            $stmt->bind_param('s', $del_user);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['flash_message'] = 'Administrator "' . $del_user . '" removed.';
                $_SESSION['flash_success'] = true;
            } else {
                $_SESSION['flash_message'] = 'Could not remove administrator. Try again.';
                $_SESSION['flash_success'] = false;
            }
        }
        header('Location: settings.php?tab=admins');
        exit;
    }
}

header('Location: settings.php');
exit;
