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

$company = trim(get_all_settings($conn)['company_name'] ?? '') ?: 'Payroll Company';
$logo_initial = strtoupper(substr($company, 0, 1)) ?: 'P';
$year = (int) date('Y');

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
            <a href="emp/login.php">Employee login</a>
            <a href="login.php" class="ph-btn ph-btn-primary ph-btn-block">Admin login</a>
        </div>
    </header>

    <main>
        <section class="ph-hero">
            <div class="ph-container ph-hero-grid">
                <div class="ph-hero-copy">
                    <p class="ph-kicker">HR &amp; Payroll platform</p>
                    <h1>Everything you need to run a <span class="ph-gradient-text">great workplace</span></h1>
                    <p class="ph-hero-lead"><?php echo htmlspecialchars($company); ?> helps you automate people processes, payroll, and compliance — so HR teams focus on strategy and employees get a delightful self-service experience.</p>
                    <div class="ph-hero-ctas">
                        <a href="login.php" class="ph-btn ph-btn-primary ph-btn-lg">Admin login</a>
                        <a href="emp/login.php" class="ph-btn ph-btn-outline ph-btn-lg">Employee login</a>
                    </div>
                    <ul class="ph-hero-trust">
                        <li><?php echo ph_icon('shield'); ?> Secure role-based access</li>
                        <li><?php echo ph_icon('check'); ?> Attendance linked to payroll</li>
                        <li><?php echo ph_icon('check'); ?> Branch-wise operations</li>
                    </ul>
                </div>
                <div class="ph-hero-visual" aria-hidden="true">
                    <div class="ph-mockup">
                        <div class="ph-mockup-bar">
                            <span></span><span></span><span></span>
                            <em>HR Dashboard</em>
                        </div>
                        <div class="ph-mockup-body">
                            <div class="ph-mockup-stat ph-mockup-stat-1">
                                <small>Paid days</small>
                                <strong>26.0</strong>
                                <div class="ph-mockup-bar-chart"><i style="width:88%"></i></div>
                            </div>
                            <div class="ph-mockup-stat ph-mockup-stat-2">
                                <small>Payroll status</small>
                                <strong>Processed</strong>
                                <div class="ph-mockup-pills"><span class="ok">Slips sent</span><span>Mar 2026</span></div>
                            </div>
                            <div class="ph-mockup-stat ph-mockup-stat-3">
                                <small>Pending approvals</small>
                                <strong>12</strong>
                                <ul><li>Leave</li><li>Expense</li><li>Attendance</li></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="ph-stats-band">
            <div class="ph-container ph-stats-grid">
                <div class="ph-stat"><strong>6+</strong><span>HR modules in one suite</span></div>
                <div class="ph-stat"><strong>2</strong><span>Dedicated admin &amp; employee portals</span></div>
                <div class="ph-stat"><strong>24/7</strong><span>Self-service for your workforce</span></div>
                <div class="ph-stat"><strong>100%</strong><span>Cloud-ready payroll workflow</span></div>
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
                        <span class="ph-module-icon"><?php echo ph_icon($mod['icon']); ?></span>
                        <h3><?php echo htmlspecialchars($mod['title']); ?></h3>
                        <p><?php echo $mod['desc']; ?></p>
                        <span class="ph-module-link">Built-in module</span>
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
                    <div class="ph-emp-feature"><?php echo ph_icon('calendar'); ?><div><strong>Simplified leave &amp; attendance</strong><span>Calendar, punch history &amp; correction requests</span></div></div>
                    <div class="ph-emp-feature"><?php echo ph_icon('payroll'); ?><div><strong>Salary slips on demand</strong><span>Download approved monthly payslips anytime</span></div></div>
                    <div class="ph-emp-feature"><?php echo ph_icon('doc'); ?><div><strong>Documents &amp; HR letters</strong><span>Upload proofs and request official letters</span></div></div>
                    <div class="ph-emp-feature"><?php echo ph_icon('chart'); ?><div><strong>Expenses in a few clicks</strong><span>Submit claims and track approval status</span></div></div>
                    <div class="ph-emp-feature"><?php echo ph_icon('bell'); ?><div><strong>Announcements &amp; alerts</strong><span>Company news and pending action items</span></div></div>
                    <div class="ph-emp-feature"><?php echo ph_icon('shield'); ?><div><strong>HR helpdesk support</strong><span>Raise tickets and get replies in one place</span></div></div>
                </div>
            </div>
        </section>

        <section class="ph-cta">
            <div class="ph-container ph-cta-inner">
                <div>
                    <h2>Ready to connect your workforce?</h2>
                    <p>Sign in to the admin or employee portal and start managing HR the modern way.</p>
                </div>
                <div class="ph-cta-actions">
                    <a href="login.php" class="ph-btn ph-btn-white ph-btn-lg">Admin login</a>
                    <a href="emp/login.php" class="ph-btn ph-btn-ghost-white ph-btn-lg">Employee login</a>
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
                <div class="ph-promo-actions">
                    <a href="login.php" class="ph-btn ph-btn-primary ph-btn-block ph-btn-lg">Claim 50% off — Admin login</a>
                    <button type="button" class="ph-promo-skip" data-ph-promo-close>Maybe later</button>
                </div>
            </div>
        </div>
    </div>

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

        var storageKey = 'ph_promo_dismissed_v1';

        function openPromo() {
            promo.hidden = false;
            document.body.classList.add('ph-promo-open');
            requestAnimationFrame(function () {
                promo.classList.add('is-visible');
            });
        }

        function closePromo(remember) {
            promo.classList.remove('is-visible');
            document.body.classList.remove('ph-promo-open');
            setTimeout(function () {
                promo.hidden = true;
            }, 280);
            if (remember) {
                try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            }
        }

        if (!localStorage.getItem(storageKey)) {
            setTimeout(openPromo, 1200);
        }

        promo.querySelectorAll('[data-ph-promo-close]').forEach(function (el) {
            el.addEventListener('click', function () { closePromo(true); });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !promo.hidden) {
                closePromo(true);
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
