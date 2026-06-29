<?php
require_once __DIR__ . '/../../includes/employee_portal_auth.php';
enforce_employee_session();
if (!isset($conn)) {
    require __DIR__ . '/../../config.php';
}
require_once __DIR__ . '/../../includes/settings_helper.php';
require_once __DIR__ . '/../../includes/face_biometric_helper.php';
require_once __DIR__ . '/../../includes/employee_portal_features_helper.php';

$employee = require_logged_in_employee($conn);
$branch_label = get_branch_label($conn, (int) $employee['branch_id']);
$portal_company = trim(get_all_settings($conn)['company_name'] ?? '') ?: 'Payroll Company';
$portal_prefs = get_employee_portal_prefs($conn, $employee['emp_id']);
$emp_notify_count = count_employee_portal_notifications($conn, $employee['emp_id'], get_all_settings($conn), (int) $employee['branch_id']);
$emp_is_manager = employee_is_manager($conn, $employee['emp_id']);
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
    'assets.php' => 'My assets',
    'team.php' => 'My team',
    'team_approvals.php' => 'Team approvals',
    'calendar.php' => 'Team calendar',
    'announcements.php' => 'Announcements',
    'notifications.php' => 'Notifications',
    'ytd.php' => 'YTD summary',
    'exit_request.php' => 'Resignation',
    'wfh.php' => 'Work from home',
    'regularization.php' => 'Punch regularization',
    'helpdesk.php' => 'HR helpdesk',
    'policies.php' => 'Policies',
    'hr_letters.php' => 'HR letters',
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
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4f46e5">
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
                <li>
                    <a href="regularization.php" class="<?php echo $current_page === 'regularization.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>Punch regularization</span>
                    </a>
                </li>
                <li>
                    <a href="wfh.php" class="<?php echo $current_page === 'wfh.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <span>Work from home</span>
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
                <li>
                    <a href="exit_request.php" class="<?php echo $current_page === 'exit_request.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Resignation</span>
                    </a>
                </li>
                <?php if ($emp_is_manager): ?>
                <li class="emp-sidebar-label" aria-hidden="true">My team</li>
                <li>
                    <a href="team.php" class="<?php echo $current_page === 'team.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>My team</span>
                    </a>
                </li>
                <li>
                    <a href="team_approvals.php" class="<?php echo $current_page === 'team_approvals.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>Team approvals</span>
                    </a>
                </li>
                <li>
                    <a href="calendar.php" class="<?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Team calendar</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="emp-sidebar-label" aria-hidden="true">Company</li>
                <li>
                    <a href="announcements.php" class="<?php echo $current_page === 'announcements.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span>Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="notifications.php" class="<?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span>Notifications<?php if ($emp_notify_count > 0): ?> <span class="emp-nav-badge"><?php echo (int) $emp_notify_count; ?></span><?php endif; ?></span>
                    </a>
                </li>
                <li>
                    <a href="policies.php" class="<?php echo $current_page === 'policies.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <span>Policies</span>
                    </a>
                </li>
                <li>
                    <a href="helpdesk.php" class="<?php echo $current_page === 'helpdesk.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span>HR helpdesk</span>
                    </a>
                </li>
                <li>
                    <a href="hr_letters.php" class="<?php echo $current_page === 'hr_letters.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <span>HR letters</span>
                    </a>
                </li>
                <li class="emp-sidebar-label" aria-hidden="true">Payroll</li>
                <li>
                    <a href="salary_slips.php" class="<?php echo $current_page === 'salary_slips.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>Salary slips</span>
                    </a>
                </li>
                <li>
                    <a href="ytd.php" class="<?php echo $current_page === 'ytd.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                        <span>YTD summary</span>
                    </a>
                </li>
                <li>
                    <a href="assets.php" class="<?php echo $current_page === 'assets.php' ? 'active' : ''; ?>">
                        <svg class="emp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        <span>My assets</span>
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
            <?php if ($emp_notify_count > 0): ?>
            <a href="notifications.php" class="emp-topbar-notify" aria-label="<?php echo (int) $emp_notify_count; ?> notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span class="emp-topbar-notify-count"><?php echo (int) $emp_notify_count; ?></span>
            </a>
            <?php endif; ?>
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
