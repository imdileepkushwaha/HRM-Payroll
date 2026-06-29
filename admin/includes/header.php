<?php
require_once __DIR__ . '/session_auth.php';
enforce_admin_session();
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($conn)) {
    require_once __DIR__ . '/../config.php';
}
if (!empty($_SESSION['admin_logged_in'])) {
    require_once __DIR__ . '/auth_helper.php';
    sync_logged_in_admin_permissions($conn);
}
$people_active = in_array($current_page, ['employees.php', 'employee_view.php', 'departments.php', 'org_chart.php', 'recruitment.php', 'performance.php', 'employee_exits.php'], true);
$finance_active = in_array($current_page, ['expenses.php', 'assets.php'], true);
$calendar_active = in_array($current_page, ['holidays.php', 'weekoff_roster.php', 'team_calendar.php', 'announcements.php'], true);
$leave_active = in_array($current_page, ['approvals.php', 'leave_history.php', 'leave_balances.php'], true);
$payroll_active = in_array($current_page, ['payroll_center.php', 'slip_logs.php', 'send_slips.php'], true);
$reports_active = in_array($current_page, ['reports.php', 'report_attendance.php', 'report_payroll.php', 'punch_report.php'], true);
$admin_initial = strtoupper(substr($_SESSION['admin_username'], 0, 1));
$active_branch_label = get_branch_label($conn, get_active_branch_id());
$branch_switch_query = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
$all_branches = get_branches($conn);
$pending_approvals_count = count_pending_approvals_for_branch($conn, get_active_branch_id());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Payroll</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-panel">
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" hidden aria-hidden="true"></div>
    <aside class="sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="sidebar-header-row">
                <div class="sidebar-brand">
                    <div class="sidebar-logo">P</div>
                    <div>
                        <h2>Payroll</h2>
                        <span>Admin Panel</span>
                    </div>
                </div>
                <button type="button" class="admin-sidebar-close" id="adminSidebarClose" aria-label="Close menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
        <ul class="sidebar-menu">
            <?php if (has_permission('dashboard')): ?>
            <li>
                <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" title="Dashboard">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-nav-label" aria-hidden="true">People</li>
            <?php if (has_permission('employees')): ?>
            <li>
                <a href="employees.php" class="<?php echo in_array($current_page, ['employees.php', 'employee_view.php'], true) ? 'active' : ''; ?>" title="Employees">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Employees</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_permission('masters')): ?>
            <li><a href="departments.php" class="<?php echo $current_page === 'departments.php' ? 'active' : ''; ?>" title="Departments"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9h18v10H3z"/><path d="M9 9V5h6v4"/></svg><span>Departments</span></a></li>
            <?php endif; ?>
            <?php if (has_permission('org')): ?>
            <li><a href="org_chart.php" class="<?php echo $current_page === 'org_chart.php' ? 'active' : ''; ?>" title="Org chart"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/><path d="M12 7v4M12 11H6M12 11h6"/></svg><span>Org chart</span></a></li>
            <?php endif; ?>
            <?php if (has_permission('recruitment')): ?>
            <li><a href="recruitment.php" class="<?php echo $current_page === 'recruitment.php' ? 'active' : ''; ?>" title="Recruitment"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg><span>Recruitment</span></a></li>
            <?php endif; ?>
            <?php if (has_permission('performance')): ?>
            <li><a href="performance.php" class="<?php echo $current_page === 'performance.php' ? 'active' : ''; ?>" title="Performance"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg><span>Performance</span></a></li>
            <?php endif; ?>
            <?php if (has_permission('exits')): ?>
            <li><a href="employee_exits.php" class="<?php echo $current_page === 'employee_exits.php' ? 'active' : ''; ?>" title="Exit & F&F"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>Exit &amp; F&amp;F</span></a></li>
            <?php endif; ?>
            <?php if (has_permission('expenses') || has_permission('assets')): ?>
            <li class="sidebar-nav-label" aria-hidden="true">Finance</li>
            <?php endif; ?>
            <?php if (has_permission('expenses')): ?>
            <li><a href="expenses.php" class="<?php echo $current_page === 'expenses.php' ? 'active' : ''; ?>" title="Expenses"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg><span>Expenses</span></a></li>
            <?php endif; ?>
            <?php if (has_permission('assets')): ?>
            <li><a href="assets.php" class="<?php echo $current_page === 'assets.php' ? 'active' : ''; ?>" title="Assets"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg><span>Assets</span></a></li>
            <?php endif; ?>
            <?php if (has_permission('calendar')): ?>
            <li class="sidebar-nav-label" aria-hidden="true">Calendar</li>
            <li>
                <a href="holidays.php" class="<?php echo $current_page === 'holidays.php' ? 'active' : ''; ?>" title="Holidays">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
                    <span>Holidays</span>
                </a>
            </li>
            <li>
                <a href="weekoff_roster.php" class="<?php echo $current_page === 'weekoff_roster.php' ? 'active' : ''; ?>" title="Weekoff roster">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><path d="M9 16h6M12 13v6"/></svg>
                    <span>Weekoff roster</span>
                </a>
            </li>
            <li>
                <a href="team_calendar.php" class="<?php echo $current_page === 'team_calendar.php' ? 'active' : ''; ?>" title="Team calendar">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="8" cy="15" r="1"/><circle cx="12" cy="15" r="1"/><circle cx="16" cy="15" r="1"/></svg>
                    <span>Team calendar</span>
                </a>
            </li>
            <li>
                <a href="announcements.php" class="<?php echo $current_page === 'announcements.php' ? 'active' : ''; ?>" title="Announcements">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h20a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2z"/><path d="M6 9v6"/><path d="M18 9v6"/></svg>
                    <span>Announcements</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_permission('attendance')): ?>
            <li class="sidebar-nav-label" aria-hidden="true">Attendance</li>
            <li>
                <a href="upload_attendance.php" class="<?php echo $current_page === 'upload_attendance.php' ? 'active' : ''; ?>" title="Upload Attendance">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span>Upload Attendance</span>
                </a>
            </li>
            <li>
                <a href="punch_logs.php" class="<?php echo in_array($current_page, ['punch_logs.php', 'punch_report.php'], true) ? 'active' : ''; ?>" title="Punch Logs">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Punch Logs</span>
                </a>
            </li>
            <li>
                <a href="punch_report.php" class="<?php echo $current_page === 'punch_report.php' ? 'active' : ''; ?>" title="Punch Report">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                    <span>Punch Report</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_permission('leave') || has_permission('approvals')): ?>
            <li class="sidebar-nav-label" aria-hidden="true">Leave</li>
            <?php endif; ?>
            <?php if (has_permission('approvals')): ?>
            <li>
                <a href="approvals.php" class="<?php echo $current_page === 'approvals.php' ? 'active' : ''; ?>" title="Approvals">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Approvals<?php if ($pending_approvals_count > 0): ?> <em class="nav-badge"><?php echo (int) $pending_approvals_count; ?></em><?php endif; ?></span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_permission('leave')): ?>
            <li>
                <a href="leave_history.php" class="<?php echo $current_page === 'leave_history.php' ? 'active' : ''; ?>" title="Leave history">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/><line x1="8" y1="18" x2="12" y2="18"/></svg>
                    <span>Leave history</span>
                </a>
            </li>
            <li>
                <a href="leave_balances.php" class="<?php echo $current_page === 'leave_balances.php' ? 'active' : ''; ?>" title="Leave balances">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>Leave balances</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-nav-label" aria-hidden="true">Payroll</li>
            <?php if (has_permission('payroll')): ?>
            <li>
                <a href="payroll_center.php" class="<?php echo $current_page === 'payroll_center.php' ? 'active' : ''; ?>" title="Payroll Center">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span>Payroll Center</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_permission('slips')): ?>
            <li>
                <a href="slip_logs.php" class="<?php echo in_array($current_page, ['slip_logs.php', 'send_slips.php'], true) ? 'active' : ''; ?>" title="Slip logs">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Slip logs</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_permission('reports')): ?>
            <li class="sidebar-nav-label" aria-hidden="true">Reports</li>
            <li>
                <a href="reports.php" class="<?php echo $reports_active ? 'active' : ''; ?>" title="Reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                    <span>Reports</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-nav-label" aria-hidden="true">System</li>
            <?php if (has_permission('roles')): ?>
            <li>
                <a href="roles.php" class="<?php echo $current_page === 'roles.php' ? 'active' : ''; ?>" title="Roles">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Roles</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (has_permission('settings')): ?>
            <li>
                <a href="audit_log.php" class="<?php echo $current_page === 'audit_log.php' ? 'active' : ''; ?>" title="Audit log">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                    <span>Audit log</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" title="Settings">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-logout">
                <form method="POST" action="logout.php" class="nav-logout-form">
                    <?php require_once __DIR__ . '/csrf_helper.php'; echo csrf_field(); ?>
                    <button type="submit" class="nav-logout-btn">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </aside>
    <div class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <span class="topbar-title">Payroll Management System</span>
                <?php if (is_super_admin() && SHOW_BRANCH_SELECTOR): ?>
                    <form method="GET" action="branch_switch.php" class="branch-switcher">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($branch_switch_query); ?>">
                        <label class="sr-only" for="topbar-branch">Branch</label>
                        <select name="branch_id" id="topbar-branch" onchange="this.form.submit()">
                            <option value="0" <?php echo get_active_branch_id() === null ? 'selected' : ''; ?>>All Branches</option>
                            <?php foreach ($all_branches as $branch): ?>
                                <option value="<?php echo (int) $branch['id']; ?>" <?php echo get_active_branch_id() === (int) $branch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <span class="branch-pill"><?php echo htmlspecialchars($active_branch_label); ?></span>
                <?php endif; ?>
            </div>
            <div class="topbar-user">
                <div class="user-info">
                    <span class="name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <span class="role"><?php echo is_super_admin() ? 'Head Office' : htmlspecialchars($active_branch_label); ?></span>
                </div>
                <div class="user-avatar"><?php echo htmlspecialchars($admin_initial); ?></div>
            </div>
        </header>
        <main class="content">
