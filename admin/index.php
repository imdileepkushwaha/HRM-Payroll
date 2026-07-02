<?php
require_once 'includes/session_auth.php';
init_admin_session();
require_once __DIR__ . '/includes/employee_portal_auth.php';
init_employee_session();
require 'config.php';
require_once 'includes/settings_helper.php';

if (!empty($_SESSION['admin_logged_in']) && !is_admin_session_expired()) {
    header('Location: dashboard.php');
    exit;
}
if (!empty($_SESSION['employee_logged_in']) && !is_employee_session_expired()) {
    header('Location: emp/dashboard.php');
    exit;
}

$company = trim(get_all_settings($conn)['company_name'] ?? '') ?: 'Teamora';
$logo_initial = strtoupper(substr($company, 0, 1)) ?: 'P';
$year = (int) date('Y');
$contact_phone = '9696323296';
$contact_phone_display = '96963 23296';
$contact_phone_tel = '+919696323296';
$whatsapp_url = 'https://wa.me/919696323296';

$stats = [
    ['value' => '6+', 'label' => 'HR modules in one suite', 'icon' => 'chart', 'tone' => 'violet'],
    ['value' => '2', 'label' => 'Dedicated admin & employee portals', 'icon' => 'users', 'tone' => 'blue'],
    ['value' => '24/7', 'label' => 'Self-service for your workforce', 'icon' => 'bell', 'tone' => 'emerald'],
    ['value' => '100%', 'label' => 'Cloud-ready payroll workflow', 'icon' => 'shield', 'tone' => 'amber'],
];

$modules = [
    ['title' => 'Payroll & expenses', 'desc' => 'Process monthly payroll, generate salary slips, and manage expense claims with approval workflows.', 'icon' => 'payroll', 'tone' => 'violet'],
    ['title' => 'Time & attendance', 'desc' => 'Punch tracking, calendars, holidays, and attendance corrections unified with payroll.', 'icon' => 'calendar', 'tone' => 'blue'],
    ['title' => 'Leave management', 'desc' => 'Leave balances, apply & approve requests, WFH and regularization in one flow.', 'icon' => 'leave', 'tone' => 'emerald'],
    ['title' => 'Modern HR', 'desc' => 'Employee records, departments, org chart, documents and company policies.', 'icon' => 'users', 'tone' => 'indigo'],
    ['title' => 'Performance', 'desc' => 'Review cycles, self-assessments, manager feedback and goal tracking.', 'icon' => 'chart', 'tone' => 'amber'],
    ['title' => 'Hiring & exits', 'desc' => 'Recruitment pipeline, onboarding assets, resignations and F&amp;F settlement.', 'icon' => 'hr', 'tone' => 'rose'],
];

$admin_highlights = [
    'Employee & org management with branch-wise control',
    'Payroll center, slip distribution & YTD reports',
    'Leave, expense, attendance & team approvals',
    'Recruitment, performance reviews & exit formalities',
    'Announcements, helpdesk, assets & audit logs',
];

$emp_highlights = [
    'Attendance calendar, punch history & corrections',
    'Leave, WFH & regularization requests',
    'Salary slips, YTD summary & expense claims',
    'Documents, HR letters, policies & announcements',
    'Notifications, helpdesk & resignation requests',
];

$emp_features = [
    ['icon' => 'calendar', 'title' => 'Simplified leave & attendance', 'desc' => 'Calendar, punch history & correction requests', 'tone' => 'blue'],
    ['icon' => 'payroll', 'title' => 'Salary slips on demand', 'desc' => 'Download approved monthly payslips anytime', 'tone' => 'violet'],
    ['icon' => 'doc', 'title' => 'Documents & HR letters', 'desc' => 'Upload proofs and request official letters', 'tone' => 'indigo'],
    ['icon' => 'chart', 'title' => 'Expenses in a few clicks', 'desc' => 'Submit claims and track approval status', 'tone' => 'amber'],
    ['icon' => 'bell', 'title' => 'Announcements & alerts', 'desc' => 'Company news and pending action items', 'tone' => 'emerald'],
    ['icon' => 'shield', 'title' => 'HR helpdesk support', 'desc' => 'Raise tickets and get replies in one place', 'tone' => 'rose'],
];

function ph_icon(string $type): string
{
    return match ($type) {
        'payroll' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'leave' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M19 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>',
        'hr' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'doc' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/></svg>',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($company); ?> — HR &amp; Payroll Software</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-portal-home">
    <header class="ph-nav" id="phNav">
        <div class="ph-container ph-nav-inner">
            <a href="index.php" class="ph-brand">
                <span class="ph-brand-logo" aria-hidden="true"><?php echo htmlspecialchars($logo_initial); ?></span>
                <span class="ph-brand-name"><?php echo htmlspecialchars($company); ?></span>
            </a>
            <nav class="ph-nav-links" aria-label="Page sections">
                <a href="#features">Features</a>
                <a href="#portals">Portals</a>
                <a href="#employee">Employees</a>
            </nav>
            <a href="tel:<?php echo htmlspecialchars($contact_phone_tel); ?>" class="ph-nav-phone" title="Call us">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <span><?php echo htmlspecialchars($contact_phone_display); ?></span>
            </a>
            <div class="ph-nav-actions">
                <a href="emp/login.php" class="ph-btn ph-btn-ghost">Employee login</a>
                <a href="login.php" class="ph-btn ph-btn-primary">Admin login</a>
            </div>
            <button type="button" class="ph-nav-toggle" id="phNavToggle" aria-label="Open menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
        <div class="ph-mobile-menu" id="phMobileMenu">
            <a href="#features">Features</a>
            <a href="#portals">Portals</a>
            <a href="#employee">Employees</a>
            <a href="tel:<?php echo htmlspecialchars($contact_phone_tel); ?>" class="ph-mobile-phone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Call <?php echo htmlspecialchars($contact_phone_display); ?>
            </a>
            <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" class="ph-mobile-phone ph-mobile-whatsapp" target="_blank" rel="noopener noreferrer">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                WhatsApp <?php echo htmlspecialchars($contact_phone); ?>
            </a>
            <a href="emp/login.php">Employee login</a>
            <a href="login.php" class="ph-btn ph-btn-primary ph-btn-block">Admin login</a>
        </div>
    </header>

    <main>
        <section class="ph-hero">
            <div class="ph-hero-bg" aria-hidden="true">
                <div class="ph-hero-orb ph-hero-orb-1"></div>
                <div class="ph-hero-orb ph-hero-orb-2"></div>
                <div class="ph-hero-orb ph-hero-orb-3"></div>
                <div class="ph-hero-mesh"></div>
            </div>
            <div class="ph-container ph-hero-grid">
                <div class="ph-hero-copy">
                    <div class="ph-hero-badge">
                        <span class="ph-hero-badge-pulse" aria-hidden="true"></span>
                        HR &amp; Payroll platform
                        <span class="ph-hero-badge-tag">All-in-one</span>
                    </div>
                    <h1>Everything you need to run a <span class="ph-gradient-text">great workplace</span></h1>
                    <p class="ph-hero-lead"><?php echo htmlspecialchars($company); ?> automates payroll, attendance, leave and HR — so your team spends less time on admin and more on people.</p>
                    <div class="ph-hero-chips">
                        <span><?php echo ph_icon('payroll'); ?> Payroll</span>
                        <span><?php echo ph_icon('calendar'); ?> Attendance</span>
                        <span><?php echo ph_icon('leave'); ?> Leave</span>
                        <span><?php echo ph_icon('users'); ?> HR</span>
                    </div>
                    <div class="ph-hero-ctas">
                        <a href="login.php" class="ph-btn ph-btn-primary ph-btn-lg ph-btn-glow">
                            <span>Admin login</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                        <a href="emp/login.php" class="ph-btn ph-btn-outline ph-btn-lg">Employee login</a>
                    </div>
                    <div class="ph-hero-trust-row">
                        <div class="ph-hero-rating">
                            <div class="ph-hero-stars" aria-hidden="true">★★★★★</div>
                            <span>Built for modern HR teams</span>
                        </div>
                        <ul class="ph-hero-trust">
                            <li><?php echo ph_icon('shield'); ?> Secure access</li>
                            <li><?php echo ph_icon('check'); ?> Payroll linked</li>
                            <li><?php echo ph_icon('check'); ?> Multi-branch</li>
                        </ul>
                    </div>
                </div>
                <div class="ph-hero-visual" aria-hidden="true">
                    <div class="ph-hero-visual-glow"></div>
                    <div class="ph-hero-float ph-hero-float-1">
                        <span class="ph-hero-float-icon ok"><?php echo ph_icon('check'); ?></span>
                        <div>
                            <strong>Payroll processed</strong>
                            <span>Salary slips sent</span>
                        </div>
                    </div>
                    <div class="ph-hero-float ph-hero-float-2">
                        <span class="ph-hero-float-icon"><?php echo ph_icon('leave'); ?></span>
                        <div>
                            <strong>3 leave requests</strong>
                            <span>Awaiting approval</span>
                        </div>
                    </div>
                    <div class="ph-mockup">
                        <div class="ph-mockup-shell">
                            <aside class="ph-mockup-side">
                                <span class="active"></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </aside>
                            <div class="ph-mockup-main">
                                <div class="ph-mockup-bar">
                                    <span></span><span></span><span></span>
                                    <em>HR Dashboard</em>
                                    <div class="ph-mockup-search"></div>
                                </div>
                                <div class="ph-mockup-body">
                                    <div class="ph-mockup-stat ph-mockup-stat-1">
                                        <div class="ph-mockup-stat-head">
                                            <small>Net payroll</small>
                                            <span class="ph-mockup-trend up">+12%</span>
                                        </div>
                                        <strong>₹24.8L</strong>
                                        <div class="ph-mockup-chart">
                                            <i style="height:42%"></i>
                                            <i style="height:58%"></i>
                                            <i style="height:48%"></i>
                                            <i style="height:72%"></i>
                                            <i style="height:65%"></i>
                                            <i style="height:88%"></i>
                                        </div>
                                    </div>
                                    <div class="ph-mockup-stat ph-mockup-stat-2">
                                        <small>Attendance</small>
                                        <strong>96%</strong>
                                        <div class="ph-mockup-ring" style="--p:96"></div>
                                    </div>
                                    <div class="ph-mockup-stat ph-mockup-stat-3">
                                        <small>Pending</small>
                                        <strong>12</strong>
                                        <ul><li>Leave</li><li>Expense</li><li>WFH</li></ul>
                                    </div>
                                    <div class="ph-mockup-stat ph-mockup-stat-4">
                                        <small>Team online</small>
                                        <strong>48</strong>
                                        <div class="ph-mockup-avatars">
                                            <span></span><span></span><span></span><span class="more">+45</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="ph-stats-band">
            <div class="ph-container">
                <div class="ph-stats-grid">
                    <?php foreach ($stats as $stat): ?>
                    <article class="ph-stat ph-stat-<?php echo htmlspecialchars($stat['tone']); ?>">
                        <span class="ph-stat-icon" aria-hidden="true"><?php echo ph_icon($stat['icon']); ?></span>
                        <div class="ph-stat-body">
                            <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
                            <span><?php echo htmlspecialchars($stat['label']); ?></span>
                        </div>
                        <span class="ph-stat-shine" aria-hidden="true"></span>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="ph-section" id="features">
            <div class="ph-container">
                <div class="ph-section-head ph-section-head-center">
                    <p class="ph-kicker">Smart HR to outsmart change</p>
                    <h2>Everything you need to create a high-performance culture</h2>
                    <p>From automation of people processes to engaged employees — manage attendance, payroll, leave, and HR operations from one connected platform.</p>
                </div>
                <div class="ph-modules">
                    <?php foreach ($modules as $mod): ?>
                    <article class="ph-module ph-module-<?php echo htmlspecialchars($mod['tone']); ?>">
                        <span class="ph-module-glow" aria-hidden="true"></span>
                        <span class="ph-module-icon"><?php echo ph_icon($mod['icon']); ?></span>
                        <h3><?php echo htmlspecialchars($mod['title']); ?></h3>
                        <p><?php echo $mod['desc']; ?></p>
                        <span class="ph-module-link">
                            Built-in module
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </span>
                        <span class="ph-module-shine" aria-hidden="true"></span>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="ph-section ph-section-alt" id="portals">
            <div class="ph-container">
                <div class="ph-section-head ph-section-head-center">
                    <p class="ph-kicker">Two portals, one platform</p>
                    <h2>Choose the experience that fits your role</h2>
                    <p>Admins run operations from a powerful control center. Employees get a simple, mobile-friendly portal for everyday HR needs.</p>
                </div>
                <div class="ph-portals">
                    <article class="ph-portal-card ph-portal-admin">
                        <div class="ph-portal-card-top">
                            <span class="ph-portal-badge">Admin portal</span>
                            <h3>For HR teams &amp; administrators</h3>
                            <p>Full control over payroll runs, employee data, approvals, reports, and company settings.</p>
                        </div>
                        <ul class="ph-portal-list">
                            <?php foreach ($admin_highlights as $item): ?>
                            <li><?php echo ph_icon('check'); ?><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="login.php" class="ph-btn ph-btn-primary ph-btn-block">Sign in as admin</a>
                    </article>
                    <article class="ph-portal-card ph-portal-emp">
                        <div class="ph-portal-card-top">
                            <span class="ph-portal-badge ph-portal-badge-emp">Employee portal</span>
                            <h3>For every team member</h3>
                            <p>Clock in, apply leave, download payslips, upload documents, and reach HR — without email chains.</p>
                        </div>
                        <ul class="ph-portal-list">
                            <?php foreach ($emp_highlights as $item): ?>
                            <li><?php echo ph_icon('check'); ?><?php echo htmlspecialchars($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="emp/login.php" class="ph-btn ph-btn-outline ph-btn-block ph-btn-emp">Sign in as employee</a>
                    </article>
                </div>
            </div>
        </section>

        <section class="ph-section" id="employee">
            <div class="ph-container ph-emp-split">
                <div class="ph-emp-copy">
                    <p class="ph-kicker">Loved by employees</p>
                    <h2>One HR app your people will actually use</h2>
                    <p>Give employees a single window for leave, attendance, payslips, documents, and support — designed for clarity and speed.</p>
                    <a href="emp/login.php" class="ph-btn ph-btn-primary ph-btn-lg">Open employee portal</a>
                </div>
                <div class="ph-emp-features">
                    <?php foreach ($emp_features as $feature): ?>
                    <article class="ph-emp-feature ph-emp-feature-<?php echo htmlspecialchars($feature['tone']); ?>">
                        <span class="ph-emp-feature-icon" aria-hidden="true"><?php echo ph_icon($feature['icon']); ?></span>
                        <div class="ph-emp-feature-body">
                            <strong><?php echo htmlspecialchars($feature['title']); ?></strong>
                            <span><?php echo htmlspecialchars($feature['desc']); ?></span>
                        </div>
                        <span class="ph-emp-feature-shine" aria-hidden="true"></span>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="ph-cta">
            <div class="ph-cta-bg" aria-hidden="true">
                <div class="ph-cta-orb ph-cta-orb-1"></div>
                <div class="ph-cta-orb ph-cta-orb-2"></div>
                <div class="ph-cta-mesh"></div>
            </div>
            <div class="ph-container">
                <div class="ph-cta-card">
                    <div class="ph-cta-copy">
                        <span class="ph-cta-badge">Get started today</span>
                        <h2>Ready to connect your workforce?</h2>
                        <p>Sign in to the admin or employee portal and start managing HR the modern way — payroll, attendance, leave and more in one place.</p>
                        <div class="ph-cta-perks">
                            <span><?php echo ph_icon('check'); ?> Free onboarding support</span>
                            <span><?php echo ph_icon('check'); ?> Admin + employee portals</span>
                            <span><?php echo ph_icon('check'); ?> 50% launch offer</span>
                        </div>
                    </div>
                    <div class="ph-cta-panel">
                        <div class="ph-cta-actions">
                            <a href="login.php" class="ph-btn ph-btn-white ph-btn-lg ph-cta-btn">
                                <span>Admin login</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                            <a href="emp/login.php" class="ph-btn ph-btn-ghost-white ph-btn-lg ph-cta-btn">Employee login</a>
                        </div>
                        <div class="ph-cta-contact-card">
                            <p>Need help choosing a plan?</p>
                            <div class="ph-cta-contact-links">
                                <a href="tel:<?php echo htmlspecialchars($contact_phone_tel); ?>" class="ph-cta-contact-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <?php echo htmlspecialchars($contact_phone_display); ?>
                                </a>
                                <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" class="ph-cta-contact-link ph-cta-contact-wa" target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                                    WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="ph-footer">
        <div class="ph-container ph-footer-grid">
            <div class="ph-footer-brand">
                <a href="index.php" class="ph-brand">
                    <span class="ph-brand-logo" aria-hidden="true"><?php echo htmlspecialchars($logo_initial); ?></span>
                    <span class="ph-brand-name"><?php echo htmlspecialchars($company); ?></span>
                </a>
                <p>HR, attendance &amp; payroll software for growing organizations.</p>
            </div>
            <div class="ph-footer-col">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#portals">Admin portal</a>
                <a href="#employee">Employee portal</a>
            </div>
            <div class="ph-footer-col">
                <h4>Contact</h4>
                <a href="tel:<?php echo htmlspecialchars($contact_phone_tel); ?>"><?php echo htmlspecialchars($contact_phone_display); ?></a>
                <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">WhatsApp chat</a>
            </div>
            <div class="ph-footer-col">
                <h4>Sign in</h4>
                <a href="login.php">Admin login</a>
                <a href="emp/login.php">Employee login</a>
                <a href="setup.php">Database setup</a>
            </div>
        </div>
        <div class="ph-container ph-footer-bottom">
            <span>&copy; <?php echo $year; ?> <?php echo htmlspecialchars($company); ?>. All rights reserved.</span>
        </div>
    </footer>

    <div class="ph-promo" id="phPromo" role="dialog" aria-modal="true" aria-labelledby="phPromoTitle" hidden>
        <div class="ph-promo-backdrop" data-ph-promo-close></div>
        <div class="ph-promo-card">
            <button type="button" class="ph-promo-close" data-ph-promo-close aria-label="Close offer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="ph-promo-glow" aria-hidden="true"></div>
            <div class="ph-promo-top">
                <span class="ph-promo-tag">Limited launch offer</span>
                <div class="ph-promo-discount">
                    <span class="ph-promo-percent">50</span>
                    <span class="ph-promo-off">
                        <strong>%</strong>
                        <em>OFF</em>
                    </span>
                </div>
                <p class="ph-promo-sub">On your first year of <?php echo htmlspecialchars($company); ?> HR &amp; Payroll suite</p>
            </div>
            <div class="ph-promo-body">
                <h2 id="phPromoTitle">Upgrade your workplace today</h2>
                <p>Automate payroll, attendance, leave &amp; HR — and save big while you scale your team.</p>
                <ul class="ph-promo-perks">
                    <li>Full admin + employee portals</li>
                    <li>Payroll, slips &amp; compliance tools</li>
                    <li>Priority onboarding support</li>
                </ul>
                <div class="ph-promo-code">
                    <span>Use code</span>
                    <strong>LAUNCH50</strong>
                    <button type="button" class="ph-promo-copy" id="phPromoCopy" aria-label="Copy promo code">Copy</button>
                </div>
                <div class="ph-promo-contact">
                    <p>Need help? Talk to our team</p>
                    <div class="ph-promo-contact-actions">
                        <a href="tel:<?php echo htmlspecialchars($contact_phone_tel); ?>" class="ph-promo-contact-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <?php echo htmlspecialchars($contact_phone_display); ?>
                        </a>
                        <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" class="ph-promo-contact-btn ph-promo-contact-wa" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                    </div>
                </div>
                <div class="ph-promo-actions">
                    <a href="login.php" class="ph-btn ph-btn-primary ph-btn-block ph-btn-lg">
                        <span class="ph-promo-cta-full">Claim 50% off — Admin login</span>
                        <span class="ph-promo-cta-short">Claim 50% off</span>
                    </a>
                    <button type="button" class="ph-promo-skip" data-ph-promo-close>Maybe later</button>
                </div>
            </div>
        </div>
    </div>

    <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" class="ph-whatsapp-float" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp at <?php echo htmlspecialchars($contact_phone); ?>">
        <span class="ph-whatsapp-pulse" aria-hidden="true"></span>
        <span class="ph-whatsapp-pulse ph-whatsapp-pulse-2" aria-hidden="true"></span>
        <span class="ph-whatsapp-icon">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
        </span>
    </a>

    <script>
    (function () {
        var nav = document.getElementById('phNav');
        var toggle = document.getElementById('phNavToggle');
        var menu = document.getElementById('phMobileMenu');
        if (!nav || !toggle || !menu) return;

        function setMenu(open) {
            menu.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('ph-menu-open', open);
        }

        toggle.addEventListener('click', function () {
            setMenu(!menu.classList.contains('is-open'));
        });

        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { setMenu(false); });
        });

        window.addEventListener('scroll', function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 8);
        }, { passive: true });
    })();

    (function () {
        var promo = document.getElementById('phPromo');
        var copyBtn = document.getElementById('phPromoCopy');
        if (!promo) return;

        function openPromo() {
            promo.removeAttribute('hidden');
            document.body.classList.add('ph-promo-open');
            requestAnimationFrame(function () {
                promo.classList.add('is-visible');
            });
        }

        function closePromo() {
            promo.classList.remove('is-visible');
            document.body.classList.remove('ph-promo-open');
            setTimeout(function () {
                promo.setAttribute('hidden', '');
            }, 280);
        }

        setTimeout(openPromo, 800);

        promo.querySelectorAll('[data-ph-promo-close]').forEach(function (el) {
            el.addEventListener('click', function () { closePromo(); });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && promo.classList.contains('is-visible')) {
                closePromo();
            }
        });

        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var code = 'LAUNCH50';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(function () {
                        copyBtn.textContent = 'Copied!';
                        setTimeout(function () { copyBtn.textContent = 'Copy'; }, 2000);
                    });
                }
            });
        }
    })();
    </script>
</body>
</html>
