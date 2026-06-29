<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require_permission('employees');
require_once 'includes/employee_helper.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/audit_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employees.php');
    exit;
}

require_csrf_or_redirect('employees.php');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    if (SHOW_BRANCH_SELECTOR && get_active_branch_id() === null) {
        $_SESSION['flash_message'] = 'Select a branch from the top bar before adding an employee.';
        $_SESSION['flash_success'] = false;
        header('Location: employees.php');
        exit;
    }
    $write_branch_id = branch_id_for_write();
    $emp_id = trim($_POST['emp_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;
    $designation_id = ($_POST['designation_id'] ?? '') !== '' ? (int) $_POST['designation_id'] : null;
    $manager_emp_id = trim($_POST['manager_emp_id'] ?? '') ?: null;
    $masters = resolve_employee_master_fields($conn, $department_id, $designation_id);
    $department = $masters['department'] !== '' ? $masters['department'] : trim($_POST['department'] ?? '');
    $designation = $masters['designation'] !== '' ? $masters['designation'] : trim($_POST['designation'] ?? '');
    $base_salary = (float) ($_POST['base_salary'] ?? 0);
    $pan = trim($_POST['pan'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');
    $bank_ifsc = trim($_POST['bank_ifsc'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    $esic_no = trim($_POST['esic_no'] ?? '');
    $uan_no = trim($_POST['uan_no'] ?? '');
    $pf_no = trim($_POST['pf_no'] ?? '');
    $joined_date = parse_joined_date_from_post($_POST['joined_date'] ?? '');

    if ($emp_id === '' || $name === '') {
        $_SESSION['flash_message'] = 'Employee ID and name are required.';
        $_SESSION['flash_success'] = false;
        header('Location: employees.php');
        exit;
    }

    if ($joined_date === null) {
        $stmt = $conn->prepare("
            INSERT INTO employees (emp_id, branch_id, name, email, phone, department, designation, department_id, designation_id, manager_emp_id, base_salary, pan, bank_account, bank_ifsc, bank_name, grade, esic_no, uan_no, pf_no, joined_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
        ");
        $stmt->bind_param('sisssssiisdssssssss', $emp_id, $write_branch_id, $name, $email, $phone, $department, $designation, $department_id, $designation_id, $manager_emp_id, $base_salary, $pan, $bank_account, $bank_ifsc, $bank_name, $grade, $esic_no, $uan_no, $pf_no);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO employees (emp_id, branch_id, name, email, phone, department, designation, department_id, designation_id, manager_emp_id, base_salary, pan, bank_account, bank_ifsc, bank_name, grade, esic_no, uan_no, pf_no, joined_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sisssssiisdsssssssss', $emp_id, $write_branch_id, $name, $email, $phone, $department, $designation, $department_id, $designation_id, $manager_emp_id, $base_salary, $pan, $bank_account, $bank_ifsc, $bank_name, $grade, $esic_no, $uan_no, $pf_no, $joined_date);
    }

    if ($stmt->execute()) {
        require_once 'includes/settings_helper.php';
        $settings = get_all_settings($conn);
        $default_portal_password = $settings['default_employee_portal_password'] ?? 'Emp@123';
        set_employee_portal_password($conn, $emp_id, $default_portal_password);
        log_admin_action($conn, 'add_employee', 'employee', $emp_id, $name);
        $_SESSION['flash_message'] = 'Employee added successfully. Portal password: ' . $default_portal_password;
        $_SESSION['flash_success'] = true;
    } else {
        $_SESSION['flash_message'] = 'Could not add employee. ID may already exist.';
        $_SESSION['flash_success'] = false;
    }
    header('Location: employees.php');
    exit;
}

if ($action === 'update') {
    $emp_id = trim($_POST['emp_id'] ?? '');
    require_employee_branch_access($conn, $emp_id);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department_id = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;
    $designation_id = ($_POST['designation_id'] ?? '') !== '' ? (int) $_POST['designation_id'] : null;
    $manager_emp_id = trim($_POST['manager_emp_id'] ?? '') ?: null;
    $masters = resolve_employee_master_fields($conn, $department_id, $designation_id);
    $department = $masters['department'] !== '' ? $masters['department'] : trim($_POST['department'] ?? '');
    $designation = $masters['designation'] !== '' ? $masters['designation'] : trim($_POST['designation'] ?? '');
    $base_salary = (float) ($_POST['base_salary'] ?? 0);
    $pan = trim($_POST['pan'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');
    $bank_ifsc = trim($_POST['bank_ifsc'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    $esic_no = trim($_POST['esic_no'] ?? '');
    $uan_no = trim($_POST['uan_no'] ?? '');
    $pf_no = trim($_POST['pf_no'] ?? '');
    $joined_date = parse_joined_date_from_post($_POST['joined_date'] ?? '');

    if ($joined_date === null) {
        $stmt = $conn->prepare("
            UPDATE employees SET name=?, email=?, phone=?, department=?, designation=?, department_id=?, designation_id=?, manager_emp_id=?, base_salary=?, pan=?, bank_account=?, bank_ifsc=?, bank_name=?, grade=?, esic_no=?, uan_no=?, pf_no=?, joined_date=NULL
            WHERE emp_id=?
        ");
        $stmt->bind_param('sssssiisdsssssssss', $name, $email, $phone, $department, $designation, $department_id, $designation_id, $manager_emp_id, $base_salary, $pan, $bank_account, $bank_ifsc, $bank_name, $grade, $esic_no, $uan_no, $pf_no, $emp_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE employees SET name=?, email=?, phone=?, department=?, designation=?, department_id=?, designation_id=?, manager_emp_id=?, base_salary=?, pan=?, bank_account=?, bank_ifsc=?, bank_name=?, grade=?, esic_no=?, uan_no=?, pf_no=?, joined_date=?
            WHERE emp_id=?
        ");
        $stmt->bind_param('sssssiisdssssssssss', $name, $email, $phone, $department, $designation, $department_id, $designation_id, $manager_emp_id, $base_salary, $pan, $bank_account, $bank_ifsc, $bank_name, $grade, $esic_no, $uan_no, $pf_no, $joined_date, $emp_id);
    }

    if ($stmt->execute()) {
        log_admin_action($conn, 'update_employee', 'employee', $emp_id, $name);
        $_SESSION['flash_message'] = 'Employee updated successfully.';
        $_SESSION['flash_success'] = true;
    } else {
        $_SESSION['flash_message'] = 'Update failed: ' . $conn->error;
        $_SESSION['flash_success'] = false;
    }

    $redirect = 'employee_view.php?emp_id=' . urlencode($emp_id);
    if (!empty($_POST['return_month']) && !empty($_POST['return_year'])) {
        $redirect .= '&month=' . (int) $_POST['return_month'] . '&year=' . (int) $_POST['return_year'];
    }
    header('Location: ' . $redirect);
    exit;
}

header('Location: employees.php');
exit;
