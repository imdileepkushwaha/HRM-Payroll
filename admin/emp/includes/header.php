<?php
require_once __DIR__ . '/../../includes/employee_portal_auth.php';
enforce_employee_session();
if (!isset($conn)) {
    require __DIR__ . '/../../config.php';
}
require_once __DIR__ . '/../../includes/settings_helper.php';
require_once __DIR__ . '/../../includes/face_biometric_helper.php';

$employee = require_logged_in_employee($conn);
$branch_label = get_branch_label($conn, (int) $employee['branch_id']);
$portal_company = trim(get_all_settings($conn)['company_name'] ?? '') ?: 'Payroll Company';
$portal_logo_initial = strtoupper(substr($portal_company, 0, 1)) ?: 'P';
$initial = strtoupper(substr($employee['name'], 0, 1));
$current_page = basename($_SERVER['PHP_SELF']);
$face_login_enabled = employee_face_login_enabled($conn);

$emp_page_title = match ($current_page) {
    'attendance.php' => 'My Attendance',
    'punch_history.php' => 'Punch History',
    'leave.php' => 'Apply Leave',
    'expenses.php' => 'Expense claims',
    'performance.php' => 'Performance review',
    'salary_slips.php' => 'My Salary Slips',
    'documents.php' => 'My Documents',
    'details.php' => 'My Details',
    'face_enroll.php' => 'Face Login',
    'dashboard.php' => 'Dashboard',
    default => 'Employee Portal',
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($emp_page_title); ?> — Employee Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="page-emp-portal">
    <div class="emp-sidebar-backdrop" id="empSidebarBackdrop" hidden></div>
    <aside class="emp-sidebar" id="empSidebar" aria-label="Employee portal navigation">
        <div class="emp-sidebar-header">
            <a href="dashboard.php" class="emp-sidebar-brand">
                <span class="emp-brand-logo" aria-hidden="true"><?php echo htmlspecialchars($portal_logo_initial); ?></span>
                <div>
                    <strong><?php echo htmlspecialchars($portal_company); ?></strong>
                    <span>Employee Portal</span>
                </div>
            </a>
        </div>

        <div class="emp-sidebar-user">
            <span class="emp-user-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></span>
            <div class="emp-sidebar-user-text">
                <strong><?php echo htmlspecialchars($employee['name']); ?></strong>
                <span><?php echo htmlspecialchars($employee['emp_id']); ?> · <?php echo htmlspecialchars($branch_label); ?></span>
            </div>
        </div>

        <nav class="emp-sidebar-nav" aria-label="Main menu">
            <ul class="emp-sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="emp-sidebar-label" aria-hidden="true">Attendance</li>
                <li>
                    <a href="attendance.php" class="<?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>My attendance</span>
                    </a>
                </li>
                <li>
                    <a href="punch_history.php" class="<?php echo $current_page === 'punch_history.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Punch history</span>
                    </a>
                </li>
                <li class="emp-sidebar-label" aria-hidden="true">Requests</li>
                <li>
                    <a href="leave.php" class="<?php echo $current_page === 'leave.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                        <span>Apply leave</span>
                    </a>
                </li>
                <li>
                    <a href="expenses.php" class="<?php echo $current_page === 'expenses.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>Expense claims</span>
                    </a>
                </li>
                <li>
                    <a href="performance.php" class="<?php echo $current_page === 'performance.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                        <span>Performance review</span>
                    </a>
                </li>
                <li class="emp-sidebar-label" aria-hidden="true">Payroll</li>
                <li>
                    <a href="salary_slips.php" class="<?php echo $current_page === 'salary_slips.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>Salary slips</span>
                    </a>
                </li>
                <li class="emp-sidebar-label" aria-hidden="true">Account</li>
                <li>
                    <a href="documents.php" class="<?php echo $current_page === 'documents.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>My documents</span>
                    </a>
                </li>
                <li>
                    <a href="details.php" class="<?php echo $current_page === 'details.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>My details</span>
                    </a>
                </li>
                <?php if ($face_login_enabled): ?>
                <li>
                    <a href="face_enroll.php" class="<?php echo $current_page === 'face_enroll.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="10" r="3"/><path d="M7 20v-1a5 5 0 0 1 10 0v1"/></svg>
                        <span>Face login</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="emp-sidebar-footer">
            <form method="POST" action="logout.php" class="emp-sidebar-logout-form">
                <?php require_once __DIR__ . '/../../includes/csrf_helper.php'; echo csrf_field(); ?>
                <button type="submit" class="emp-sidebar-logout-btn">
                    <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="emp-main">
        <header class="emp-main-topbar">
            <button type="button" class="emp-sidebar-toggle" id="empSidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="empSidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="emp-main-topbar-title">
                <p class="emp-main-eyebrow">Employee portal</p>
                <h1><?php echo htmlspecialchars($emp_page_title); ?></h1>
            </div>
            <div class="emp-main-topbar-user">
                <span class="emp-user-avatar emp-user-avatar-sm" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></span>
                <div class="emp-main-topbar-user-text">
                    <strong><?php echo htmlspecialchars($employee['name']); ?></strong>
                    <span><?php echo htmlspecialchars($employee['emp_id']); ?></span>
                </div>
            </div>
            <form method="POST" action="logout.php" class="emp-main-topbar-logout-form">
                <?php require_once __DIR__ . '/../../includes/csrf_helper.php'; echo csrf_field(); ?>
                <button type="submit" class="emp-main-topbar-logout" aria-label="Logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Logout</span>
                </button>
            </form>
        </header>
        <main class="emp-content">
