<?php
require_once 'includes/admin_page_init.php';
admin_page_init('settings');
require_once 'includes/settings_helper.php';
require 'includes/signature_helper.php';
require_once 'includes/payroll_extensions.php';

$settings = get_all_settings($conn);
$signature_url = payslip_signature_url($settings);
$tab = $_GET['tab'] ?? 'smtp';
$smtp_ready = is_smtp_configured($settings);

function render_password_input($name, $attrs = '')
{
    $id = 'pwd_' . preg_replace('/[^a-z0-9]/i', '_', $name);
    ?>
    <div class="password-field">
        <input type="password" name="<?php echo htmlspecialchars($name); ?>" id="<?php echo $id; ?>" <?php echo $attrs; ?>>
        <button type="button" class="password-toggle" data-target="<?php echo $id; ?>" aria-label="Show password">
            <svg class="icon-eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
    </div>
    <?php
}

function render_settings_switch(string $name, string $label, bool $checked = false, array $options = []): void
{
    $id = $options['id'] ?? ('sw_' . preg_replace('/[^a-z0-9_]/i', '_', $name) . '_' . substr(md5($name . $label), 0, 6));
    $value = $options['value'] ?? '1';
    $extra_class = trim($options['class'] ?? '');
    $title = $options['title'] ?? '';
    $attrs = $options['attrs'] ?? '';
    $classes = trim('settings-switch' . ($extra_class !== '' ? ' ' . $extra_class : '') . ($checked ? ' is-on' : ''));
    ?>
    <label class="<?php echo htmlspecialchars($classes); ?>"<?php echo $title !== '' ? ' title="' . htmlspecialchars($title) . '"' : ''; ?>>
        <input type="checkbox" name="<?php echo htmlspecialchars($name); ?>" id="<?php echo htmlspecialchars($id); ?>" value="<?php echo htmlspecialchars($value); ?>"<?php echo $checked ? ' checked' : ''; ?><?php echo $attrs !== '' ? ' ' . $attrs : ''; ?>>
        <span class="settings-switch-ui" aria-hidden="true"></span>
        <?php if ($label !== ''): ?>
            <span class="settings-switch-label"><?php echo htmlspecialchars($label); ?></span>
        <?php endif; ?>
    </label>
    <?php
}

$tab_meta = [
    'smtp' => [
        'title' => 'SMTP & Email',
        'desc' => 'Optional outgoing mail settings (not used for salary slips).',
        'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
    ],
    'password' => [
        'title' => 'Change Password',
        'desc' => 'Update your admin account credentials.',
        'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    ],
    'payroll' => [
        'title' => 'Payroll Rules',
        'desc' => 'Company info and salary calculation settings.',
        'icon' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    ],
    'leave' => [
        'title' => 'Leave & Attendance',
        'desc' => 'Manage leave quotas and monthly limits.',
        'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    ],
    'punch' => [
        'title' => 'Punch & Geo',
        'desc' => 'Employee punch in/out and office geofence.',
        'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 0 20"/><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4"/>',
    ],
    'branches' => [
        'title' => 'Branches',
        'desc' => 'Add or remove office branches.',
        'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    ],
    'admins' => [
        'title' => 'Admin Users',
        'desc' => 'Manage who can access this panel.',
        'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    ],
];
$admin_users = $conn->query('SELECT au.id, au.username, au.branch_id, au.role_id, ar.name AS role_name, ar.code AS role_code FROM admin_users au LEFT JOIN admin_roles ar ON ar.id = au.role_id ORDER BY au.username');
$admin_roles_list = get_admin_roles($conn);
$all_branches = get_branches($conn);
$active_meta = $tab_meta[$tab] ?? $tab_meta['smtp'];
?>
<div class="page-header page-header-row">
    <div class="page-header-main">
        <p class="page-eyebrow">Configuration</p>
        <h2>Settings</h2>
        <p>SMTP, security, and payroll rules for your organization.</p>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<div class="settings-status">
    <div class="settings-status-chip <?php echo $smtp_ready ? 'ok' : 'warn'; ?>">
        <span class="status-dot"></span>
        <div>
            <strong><?php echo $smtp_ready ? 'SMTP configured' : 'SMTP not configured'; ?></strong>
            <span><?php echo $smtp_ready ? 'Configured' : 'Not configured'; ?></span>
        </div>
    </div>
    <div class="settings-status-chip neutral">
        <span class="status-dot"></span>
        <div>
            <strong><?php echo htmlspecialchars($settings['company_name'] ?? 'Company'); ?></strong>
            <span><?php echo (int) ($settings['working_days_per_month'] ?? 26); ?> working days / month</span>
        </div>
    </div>
</div>

<div class="settings-layout">
    <nav class="settings-tabs" aria-label="Settings sections">
        <p class="settings-nav-label">Sections</p>
        <?php foreach ($tab_meta as $key => $meta): ?>
            <?php if ($key === 'admins' && !is_super_admin()) { continue; } ?>
            <?php if ($key === 'branches' && !is_super_admin()) { continue; } ?>
            <a href="?tab=<?php echo $key; ?>" class="settings-tab <?php echo $tab === $key ? 'active' : ''; ?>">
                <span class="settings-tab-icon-wrap">
                    <svg class="settings-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $meta['icon']; ?></svg>
                </span>
                <span class="settings-tab-text">
                    <span class="settings-tab-title"><?php echo htmlspecialchars($meta['title']); ?></span>
                    <span class="settings-tab-desc"><?php echo htmlspecialchars($meta['desc']); ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="settings-content">
        <div class="settings-card panel-elevated">
            <div class="settings-card-head">
                <div class="settings-card-icon tab-<?php echo htmlspecialchars($tab); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $active_meta['icon']; ?></svg>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($active_meta['title']); ?></h3>
                    <p><?php echo htmlspecialchars($active_meta['desc']); ?></p>
                </div>
            </div>

            <?php if ($tab === 'smtp'): ?>
            <div class="settings-tip settings-tip-smtp">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <p>For <strong>Gmail</strong>: host <code>smtp.gmail.com</code>, port <code>587</code>, TLS, and a Google <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">App Password</a> (not your normal login password).</p>
            </div>
            <?php
            $smtp_from_email = trim((string) ($settings['smtp_from_email'] ?? ''));
            $smtp_host_label = trim((string) ($settings['smtp_host'] ?? ''));
            ?>
            <div class="settings-smtp-panel">
                <div class="settings-smtp-status-row">
                    <div class="settings-smtp-status-card<?php echo $smtp_ready ? ' is-ready' : ' is-pending'; ?>">
                        <span class="settings-smtp-status-dot" aria-hidden="true"></span>
                        <div class="settings-smtp-status-content">
                            <strong class="settings-smtp-status-title"><?php echo $smtp_ready ? 'SMTP ready to send' : 'SMTP not fully configured'; ?></strong>
                            <span class="settings-smtp-status-sub">
                                <?php if ($smtp_ready): ?>
                                    <?php if ($smtp_from_email !== ''): ?>
                                        Sending as <strong><?php echo htmlspecialchars($smtp_from_email); ?></strong>
                                    <?php else: ?>
                                        Host and credentials are saved
                                    <?php endif; ?>
                                <?php else: ?>
                                    Add mail server host, username and from email below
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if ($smtp_host_label !== ''): ?>
                            <span class="settings-smtp-status-host"><?php echo htmlspecialchars($smtp_host_label); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" action="settings_save.php" class="settings-form settings-smtp-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="smtp">

                    <section class="settings-smtp-block">
                        <header class="settings-smtp-block-head">
                            <div class="settings-smtp-block-icon settings-smtp-block-icon-server" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                            </div>
                            <div>
                                <h4>Mail server</h4>
                                <p class="settings-smtp-block-desc">Outgoing SMTP host, port and encryption used to deliver email.</p>
                            </div>
                        </header>
                        <div class="form-row settings-smtp-field-row">
                            <div class="form-group">
                                <label>SMTP host</label>
                                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com" class="settings-smtp-input">
                            </div>
                            <div class="form-group">
                                <label>SMTP port</label>
                                <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" class="settings-smtp-input" placeholder="587">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Encryption</label>
                            <select name="smtp_encryption" class="settings-smtp-input settings-smtp-select">
                                <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (recommended)</option>
                                <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        <div class="settings-smtp-presets">
                            <span class="settings-smtp-presets-label">Quick fill</span>
                            <button type="button" class="settings-smtp-preset-btn" data-smtp-preset="gmail">Gmail</button>
                            <button type="button" class="settings-smtp-preset-btn" data-smtp-preset="outlook">Outlook</button>
                        </div>
                    </section>

                    <section class="settings-smtp-block">
                        <header class="settings-smtp-block-head">
                            <div class="settings-smtp-block-icon settings-smtp-block-icon-auth" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </div>
                            <div>
                                <h4>Authentication</h4>
                                <p class="settings-smtp-block-desc">Login credentials your mail provider expects for SMTP.</p>
                            </div>
                        </header>
                        <div class="form-row settings-smtp-field-row">
                            <div class="form-group">
                                <label>SMTP username</label>
                                <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" placeholder="your@email.com" autocomplete="username" class="settings-smtp-input">
                            </div>
                            <div class="form-group">
                                <label>SMTP password</label>
                                <?php
                                $smtp_placeholder = !empty($settings['smtp_password']) ? 'Saved — leave blank to keep' : 'SMTP password';
                                ?>
                                <div class="settings-smtp-password-wrap">
                                    <?php render_password_input('smtp_password', 'class="settings-smtp-input" placeholder="' . htmlspecialchars($smtp_placeholder) . '" autocomplete="new-password"'); ?>
                                </div>
                                <span class="form-hint">Use an app password for Gmail / Microsoft 365</span>
                            </div>
                        </div>
                    </section>

                    <section class="settings-smtp-block">
                        <header class="settings-smtp-block-head">
                            <div class="settings-smtp-block-icon settings-smtp-block-icon-sender" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <h4>Sender identity</h4>
                                <p class="settings-smtp-block-desc">Name and address recipients see in the From field.</p>
                            </div>
                        </header>
                        <div class="form-row settings-smtp-field-row">
                            <div class="form-group">
                                <label>From email</label>
                                <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>" required placeholder="noreply@company.com" class="settings-smtp-input">
                            </div>
                            <div class="form-group">
                                <label>From name</label>
                                <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? 'Payroll System'); ?>" class="settings-smtp-input" placeholder="Payroll System">
                            </div>
                        </div>
                    </section>

                    <div class="settings-form-actions settings-smtp-actions">
                        <button type="submit" class="btn settings-smtp-save-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save SMTP settings
                        </button>
                    </div>
                </form>

                <form method="POST" action="test_smtp.php" class="settings-smtp-block settings-smtp-block-test">
                    <?php echo csrf_field(); ?>
                    <header class="settings-smtp-block-head">
                        <div class="settings-smtp-block-icon settings-smtp-block-icon-test" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        </div>
                        <div>
                            <h4>Test connection</h4>
                            <p class="settings-smtp-block-desc">Save settings first, then send a test message to confirm delivery.</p>
                        </div>
                    </header>
                    <div class="settings-smtp-test-row">
                        <div class="form-group">
                            <label for="smtpTestEmail">Send test to</label>
                            <input type="email" id="smtpTestEmail" name="test_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? 'payroll@yopmail.com'); ?>" required class="settings-smtp-input" placeholder="payroll@yopmail.com">
                        </div>
                        <button type="submit" class="btn btn-outline settings-smtp-test-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            Send test email
                        </button>
                    </div>
                </form>
            </div>
            <script>
            (function () {
                var presets = {
                    gmail: { host: 'smtp.gmail.com', port: '587', encryption: 'tls' },
                    outlook: { host: 'smtp.office365.com', port: '587', encryption: 'tls' }
                };
                document.querySelectorAll('[data-smtp-preset]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var key = btn.getAttribute('data-smtp-preset');
                        var preset = presets[key];
                        if (!preset) return;
                        var host = document.querySelector('input[name="smtp_host"]');
                        var port = document.querySelector('input[name="smtp_port"]');
                        var enc = document.querySelector('select[name="smtp_encryption"]');
                        if (host) host.value = preset.host;
                        if (port) port.value = preset.port;
                        if (enc) enc.value = preset.encryption;
                    });
                });
            })();
            </script>
            <?php endif; ?>

            <?php if ($tab === 'password'): ?>
            <div class="settings-tip settings-tip-security">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <p>Use at least 6 characters. Avoid sharing your admin password.</p>
            </div>
            <form method="POST" action="settings_save.php" class="stack-form settings-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="section" value="password">
                <div class="settings-form-section">
                    <div class="form-group">
                        <label>Current Password</label>
                        <?php render_password_input('current_password', 'required autocomplete="current-password"'); ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password</label>
                            <?php render_password_input('new_password', 'required minlength="6" autocomplete="new-password"'); ?>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <?php render_password_input('confirm_password', 'required minlength="6" autocomplete="new-password"'); ?>
                        </div>
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Update Password
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($tab === 'payroll'): ?>
            <?php
            $pct_basic = (float) ($settings['pct_basic'] ?? 50);
            $pct_hra = (float) ($settings['pct_hra'] ?? 20);
            $pct_conveyance = (float) ($settings['pct_conveyance'] ?? 5);
            $pct_medical = (float) ($settings['pct_medical'] ?? 5);
            $pct_special = (float) ($settings['pct_special'] ?? 20);
            $pct_total = round($pct_basic + $pct_hra + $pct_conveyance + $pct_medical + $pct_special, 1);
            $pct_balanced = abs($pct_total - 100) < 0.05;
            $require_payroll_approval = !isset($settings['require_payroll_approval']) || (int) $settings['require_payroll_approval'] === 1;
            ?>
            <div class="settings-tip settings-tip-payroll">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <p><strong>Paid days</strong> = Present + (Half day × credit) + (Leave × credit). <strong>Net pay</strong> = gross split − PF, professional tax, and ESI when applicable.</p>
            </div>
            <form method="POST" action="settings_save.php" enctype="multipart/form-data" class="settings-form settings-payroll-panel">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="section" value="payroll">

                <section class="settings-payroll-block">
                    <header class="settings-payroll-block-head">
                        <div class="settings-payroll-block-icon settings-payroll-block-icon-company" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div>
                            <h4>Company &amp; calculation</h4>
                            <p class="settings-payroll-block-desc">Name on salary slips and how attendance credits convert to paid days.</p>
                        </div>
                    </header>
                    <div class="form-group">
                        <label>Company name</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" placeholder="Shown on salary slips" class="settings-payroll-input">
                    </div>
                    <div class="form-row settings-payroll-field-row">
                        <div class="form-group">
                            <label>HR notification emails</label>
                            <input type="text" name="hr_notify_emails" value="<?php echo htmlspecialchars($settings['hr_notify_emails'] ?? ''); ?>" placeholder="hr@company.com, admin@company.com" class="settings-payroll-input">
                            <span class="form-hint">Comma-separated — recruitment, exit, and expense alerts (requires SMTP)</span>
                        </div>
                        <div class="form-group">
                            <label>Company email</label>
                            <input type="email" name="company_email" value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>" placeholder="contact@company.com" class="settings-payroll-input">
                        </div>
                    </div>
                    <label class="settings-checkbox-row">
                        <input type="checkbox" name="careers_public_enabled" value="1" <?php echo ($settings['careers_public_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <span>Enable public careers page (<a href="careers.php" target="_blank" rel="noopener">careers.php</a>)</span>
                    </label>
                    <div class="form-row form-row-3 settings-payroll-field-row">
                        <div class="form-group">
                            <label>Working days / month</label>
                            <input type="number" name="working_days_per_month" min="1" max="31" value="<?php echo htmlspecialchars($settings['working_days_per_month'] ?? '26'); ?>" required class="settings-payroll-input">
                            <span class="form-hint">Typically 22–26</span>
                        </div>
                        <div class="form-group">
                            <label>Half day credit</label>
                            <input type="number" name="half_day_credit" step="0.1" min="0" max="1" value="<?php echo htmlspecialchars($settings['half_day_credit'] ?? '0.5'); ?>" class="settings-payroll-input">
                            <span class="form-hint">0.5 = half paid day</span>
                        </div>
                        <div class="form-group">
                            <label>Leave day credit</label>
                            <input type="number" name="leave_day_credit" step="0.1" min="0" max="1" value="<?php echo htmlspecialchars($settings['leave_day_credit'] ?? '1'); ?>" class="settings-payroll-input">
                            <span class="form-hint">1 = paid · 0 = unpaid</span>
                        </div>
                    </div>
                </section>

                <section class="settings-payroll-block">
                    <header class="settings-payroll-block-head">
                        <div class="settings-payroll-block-icon settings-payroll-block-icon-split" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        </div>
                        <div>
                            <h4>Salary structure</h4>
                            <p class="settings-payroll-block-desc">Percentage split of monthly gross across earnings heads.</p>
                        </div>
                        <span class="settings-payroll-total-badge<?php echo $pct_balanced ? '' : ' is-warn'; ?>"><?php echo htmlspecialchars((string) $pct_total); ?>%</span>
                    </header>
                    <div class="settings-payroll-split-bar" aria-hidden="true">
                        <?php if ($pct_basic > 0): ?><span class="settings-payroll-split-seg is-basic" style="width: <?php echo max(0, $pct_basic); ?>%;"></span><?php endif; ?>
                        <?php if ($pct_hra > 0): ?><span class="settings-payroll-split-seg is-hra" style="width: <?php echo max(0, $pct_hra); ?>%;"></span><?php endif; ?>
                        <?php if ($pct_conveyance > 0): ?><span class="settings-payroll-split-seg is-conveyance" style="width: <?php echo max(0, $pct_conveyance); ?>%;"></span><?php endif; ?>
                        <?php if ($pct_medical > 0): ?><span class="settings-payroll-split-seg is-medical" style="width: <?php echo max(0, $pct_medical); ?>%;"></span><?php endif; ?>
                        <?php if ($pct_special > 0): ?><span class="settings-payroll-split-seg is-special" style="width: <?php echo max(0, $pct_special); ?>%;"></span><?php endif; ?>
                    </div>
                    <div class="settings-payroll-split-legend">
                        <span><i class="is-basic"></i> Basic</span>
                        <span><i class="is-hra"></i> HRA</span>
                        <span><i class="is-conveyance"></i> Conveyance</span>
                        <span><i class="is-medical"></i> Medical</span>
                        <span><i class="is-special"></i> Special</span>
                    </div>
                    <?php if (!$pct_balanced): ?>
                        <p class="settings-payroll-split-note is-warn">Split should total 100%. Current total: <?php echo htmlspecialchars((string) $pct_total); ?>%.</p>
                    <?php endif; ?>
                    <div class="form-row form-row-3 settings-payroll-field-row">
                        <div class="form-group">
                            <label>Basic %</label>
                            <input type="number" name="pct_basic" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_basic'] ?? '50'); ?>" class="settings-payroll-input">
                        </div>
                        <div class="form-group">
                            <label>HRA %</label>
                            <input type="number" name="pct_hra" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_hra'] ?? '20'); ?>" class="settings-payroll-input">
                        </div>
                        <div class="form-group">
                            <label>Conveyance %</label>
                            <input type="number" name="pct_conveyance" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_conveyance'] ?? '5'); ?>" class="settings-payroll-input">
                        </div>
                    </div>
                    <div class="form-row form-row-3 settings-payroll-field-row">
                        <div class="form-group">
                            <label>Medical %</label>
                            <input type="number" name="pct_medical" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_medical'] ?? '5'); ?>" class="settings-payroll-input">
                        </div>
                        <div class="form-group">
                            <label>Special %</label>
                            <input type="number" name="pct_special" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_special'] ?? '20'); ?>" class="settings-payroll-input">
                        </div>
                    </div>
                </section>

                <section class="settings-payroll-block">
                    <header class="settings-payroll-block-head">
                        <div class="settings-payroll-block-icon settings-payroll-block-icon-deduct" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <h4>Statutory deductions</h4>
                            <p class="settings-payroll-block-desc">PF, professional tax and ESI rules applied during payroll run.</p>
                        </div>
                    </header>
                    <div class="form-row form-row-3 settings-payroll-field-row">
                        <div class="form-group">
                            <label>PF % of basic</label>
                            <input type="number" name="pf_percent" step="0.1" min="0" max="30" value="<?php echo htmlspecialchars($settings['pf_percent'] ?? '12'); ?>" class="settings-payroll-input">
                        </div>
                        <div class="form-group">
                            <label>PF basic min (₹)</label>
                            <input type="number" name="pf_min_limit" step="1" min="0" value="<?php echo htmlspecialchars($settings['pf_min_limit'] ?? '0'); ?>" class="settings-payroll-input">
                            <span class="form-hint">0 = no limit</span>
                        </div>
                        <div class="form-group">
                            <label>PF basic max (₹)</label>
                            <input type="number" name="pf_max_limit" step="1" min="0" value="<?php echo htmlspecialchars($settings['pf_max_limit'] ?? '15000'); ?>" class="settings-payroll-input">
                            <span class="form-hint">0 = no limit</span>
                        </div>
                    </div>
                    <div class="form-row form-row-3 settings-payroll-field-row">
                        <div class="form-group">
                            <label>Professional tax (₹)</label>
                            <input type="number" name="professional_tax" step="1" min="0" value="<?php echo htmlspecialchars($settings['professional_tax'] ?? '200'); ?>" class="settings-payroll-input">
                        </div>
                        <div class="form-group">
                            <label>ESI %</label>
                            <input type="number" name="esi_percent" step="0.01" min="0" max="5" value="<?php echo htmlspecialchars($settings['esi_percent'] ?? '0.75'); ?>" class="settings-payroll-input">
                        </div>
                        <div class="form-group">
                            <label>ESI if gross ≤ (₹)</label>
                            <input type="number" name="esi_gross_limit" step="1" min="0" value="<?php echo htmlspecialchars($settings['esi_gross_limit'] ?? '21000'); ?>" class="settings-payroll-input">
                        </div>
                    </div>
                </section>

                <section class="settings-payroll-block">
                    <header class="settings-payroll-block-head">
                        <div class="settings-payroll-block-icon settings-payroll-block-icon-workflow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                        <div>
                            <h4>Payroll workflow</h4>
                            <p class="settings-payroll-block-desc">Approval gate for employee slips and overtime calculation defaults.</p>
                        </div>
                    </header>
                    <?php render_settings_switch(
                        'require_payroll_approval',
                        'Require payroll approval before showing slips in employee portal',
                        $require_payroll_approval,
                        ['class' => 'settings-switch-block settings-payroll-switch']
                    ); ?>
                    <div class="form-row settings-payroll-field-row settings-payroll-ot-row">
                        <div class="form-group">
                            <label>Overtime hours / day</label>
                            <div class="settings-payroll-input-suffix">
                                <input type="number" name="overtime_hours_per_day" step="0.5" min="1" max="24" value="<?php echo htmlspecialchars($settings['overtime_hours_per_day'] ?? '8'); ?>" class="settings-payroll-input">
                                <span>hrs</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>OT pay multiplier</label>
                            <div class="settings-payroll-input-suffix">
                                <input type="number" name="overtime_multiplier" step="0.1" min="1" max="3" value="<?php echo htmlspecialchars($settings['overtime_multiplier'] ?? '1.5'); ?>" class="settings-payroll-input">
                                <span>×</span>
                            </div>
                            <span class="form-hint">e.g. 1.5× hourly rate</span>
                        </div>
                    </div>
                </section>

                <section class="settings-payroll-block settings-payroll-block-signature">
                    <header class="settings-payroll-block-head">
                        <div class="settings-payroll-block-icon settings-payroll-block-icon-sign" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </div>
                        <div>
                            <h4>Payslip signature</h4>
                            <p class="settings-payroll-block-desc">Image and authority name printed bottom-right on every salary slip PDF.</p>
                        </div>
                    </header>
                    <div class="settings-payroll-signature-layout">
                        <div class="settings-payroll-signature-preview">
                            <?php if ($signature_url): ?>
                                <div class="signature-preview-box settings-payroll-signature-box">
                                    <img src="<?php echo htmlspecialchars($signature_url); ?>" alt="Current signature">
                                    <div class="signature-preview-meta">
                                        <span class="badge badge-present">Active</span>
                                        <?php render_settings_switch('remove_signature', 'Remove', false, ['class' => 'settings-switch-inline settings-switch-compact']); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="signature-preview-box signature-preview-empty settings-payroll-signature-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    <p>No signature uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="settings-payroll-signature-fields">
                            <div class="form-group">
                                <label>Upload signature image</label>
                                <input type="file" name="payslip_signature" accept="image/png,image/jpeg,image/jpg,image/gif" class="settings-payroll-file-input">
                                <span class="form-hint">Transparent PNG recommended · max 2MB</span>
                            </div>
                            <div class="form-group">
                                <label>Name below signature</label>
                                <input type="text" name="signature_authority_name" value="<?php echo htmlspecialchars($settings['signature_authority_name'] ?? 'Authorized Signatory'); ?>" placeholder="e.g. HR Manager / Director" class="settings-payroll-input">
                            </div>
                        </div>
                    </div>
                </section>

                <div class="settings-form-actions settings-payroll-actions">
                    <button type="submit" class="btn settings-payroll-save-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save payroll settings
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($tab === 'leave'): ?>
            <?php $all_leave_types = get_all_leave_types($conn); ?>
            <form method="POST" action="settings_save.php" class="settings-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="section" value="leave">
                <div class="settings-form-section">
                    <h4>Leave &amp; Attendance Quotas</h4>
                    <div class="form-row form-row-3">
                        <div class="form-group">
                            <label>Yearly PL Quota</label>
                            <input type="number" name="leave_quota_pl" step="1" min="0" value="<?php echo htmlspecialchars($settings['leave_quota_pl'] ?? '13'); ?>">
                            <span class="form-hint">Accrues <?php echo number_format(round((float)($settings['leave_quota_pl'] ?? 13) / 12, 2), 2); ?> per month</span>
                        </div>
                        <div class="form-group">
                            <label>Yearly SL Quota</label>
                            <input type="number" name="leave_quota_sl" step="1" min="0" value="<?php echo htmlspecialchars($settings['leave_quota_sl'] ?? '9'); ?>">
                            <span class="form-hint">Accrues <?php echo number_format(round((float)($settings['leave_quota_sl'] ?? 9) / 12, 2), 2); ?> per month</span>
                        </div>
                        <div class="form-group">
                            <label>Yearly CL Quota</label>
                            <input type="number" name="leave_quota_cl" step="1" min="0" value="<?php echo htmlspecialchars($settings['leave_quota_cl'] ?? '8'); ?>">
                            <span class="form-hint">Accrues <?php echo number_format(round((float)($settings['leave_quota_cl'] ?? 8) / 12, 2), 2); ?> per month</span>
                        </div>
                    </div>
                    <div class="form-row form-row-3 leave-quota-limits-row">
                        <div class="form-group">
                            <label>Max Leaves Per Month</label>
                            <input type="number" name="max_leaves_per_month" step="1" min="0" value="<?php echo htmlspecialchars($settings['max_leaves_per_month'] ?? '4'); ?>">
                            <span class="form-hint">Leaves beyond this are unpaid</span>
                        </div>
                        <div class="form-group">
                            <label>Max Week Offs Per Month</label>
                            <input type="number" name="max_wo_per_month" step="1" min="0" value="<?php echo htmlspecialchars($settings['max_wo_per_month'] ?? '4'); ?>">
                            <span class="form-hint">WOs beyond this are unpaid</span>
                        </div>
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Leave Settings
                    </button>
                </div>
            </form>

            <?php
            $leave_type_count = count($all_leave_types);
            $active_leave_count = 0;
            foreach ($all_leave_types as $lt_row) {
                if (!empty($lt_row['is_active'])) {
                    $active_leave_count++;
                }
            }
            ?>
            <div class="settings-tip settings-tip-leave">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <p>Active types appear on the employee leave form as <strong>CODE — Full Name</strong> (e.g. <code>PL — Privilege Leave</code>). Inactive types stay hidden from employees.</p>
            </div>
            <div class="settings-form settings-leave-types-panel">
                <div class="settings-form-section">
                    <div class="leave-type-section-head">
                        <div>
                            <h4>Leave Types (Employee Portal)</h4>
                            <p class="form-hint leave-type-section-hint">
                                <?php echo (int) $active_leave_count; ?> active
                                <?php if ($leave_type_count > $active_leave_count): ?>
                                    · <?php echo $leave_type_count - $active_leave_count; ?> hidden
                                <?php endif; ?>
                                <?php if ($leave_type_count > 0): ?>
                                    · <?php echo $leave_type_count === 1 ? '1 type' : $leave_type_count . ' types'; ?> total
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="leave-type-count-badge" aria-hidden="true"><?php echo (int) $leave_type_count; ?></span>
                    </div>

                    <?php if ($all_leave_types === []): ?>
                        <div class="leave-type-list-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/></svg>
                            <p>No leave types yet. Add PL, SL, CL or custom types below — employees will pick from this list when applying for leave.</p>
                        </div>
                    <?php else: ?>
                        <ul class="leave-type-card-list">
                            <?php foreach ($all_leave_types as $lt):
                                $is_active = !empty($lt['is_active']);
                                $preview_label = format_leave_type_label($lt);
                                ?>
                                <li class="leave-type-card<?php echo $is_active ? '' : ' is-inactive'; ?>">
                                    <form method="POST" action="settings_save.php" class="leave-type-card-form" data-leave-type-form>
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="section" value="leave_type_save">
                                        <input type="hidden" name="code" value="<?php echo htmlspecialchars($lt['code']); ?>">

                                        <span class="leave-type-code-icon<?php echo $is_active ? '' : ' is-hidden'; ?>" title="<?php echo $is_active ? 'Active in portal' : 'Hidden from portal'; ?>"><?php echo htmlspecialchars($lt['code']); ?></span>

                                        <div class="leave-type-card-fields">
                                            <div class="leave-type-field-name">
                                                <input
                                                    type="text"
                                                    name="name"
                                                    value="<?php echo htmlspecialchars($lt['name']); ?>"
                                                    required
                                                    maxlength="60"
                                                    class="leave-type-input"
                                                    placeholder="Full name"
                                                    aria-label="Full name for <?php echo htmlspecialchars($lt['code']); ?>"
                                                    data-leave-name-input
                                                    data-leave-code="<?php echo htmlspecialchars($lt['code']); ?>"
                                                >
                                                <span class="leave-type-preview-line" data-leave-preview>
                                                    Employee sees: <span data-leave-preview-text><?php echo htmlspecialchars($preview_label); ?></span>
                                                </span>
                                            </div>

                                            <div class="leave-type-field-credit">
                                                <label class="leave-type-inline-label" for="leaveCredit_<?php echo htmlspecialchars($lt['code']); ?>">Credit</label>
                                                <input
                                                    type="number"
                                                    id="leaveCredit_<?php echo htmlspecialchars($lt['code']); ?>"
                                                    name="paid_credit"
                                                    value="<?php echo htmlspecialchars($lt['paid_credit']); ?>"
                                                    step="0.01"
                                                    min="0"
                                                    max="1"
                                                    class="leave-type-input leave-type-input-credit"
                                                    title="1 = paid day · 0 = unpaid"
                                                >
                                            </div>

                                            <?php render_settings_switch(
                                                'is_active',
                                                'Portal',
                                                $is_active,
                                                ['class' => 'settings-switch-compact', 'title' => 'Show in employee portal']
                                            ); ?>
                                        </div>

                                        <button type="submit" class="btn btn-sm leave-type-save-btn" title="Save <?php echo htmlspecialchars($lt['code']); ?>">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                            <span class="leave-type-save-label">Save</span>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <form method="POST" action="settings_save.php" class="stack-form leave-type-add-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="leave_type_add">
                    <div class="settings-add-panel settings-add-panel-leave">
                        <div class="settings-add-panel-head">
                            <span class="settings-add-panel-icon settings-add-panel-icon-leave" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                            </span>
                            <div class="settings-add-panel-head-text">
                                <h4>Add leave type</h4>
                                <p>Create a new option for the employee leave request dropdown — use a short code and clear full name.</p>
                            </div>
                        </div>
                        <div class="settings-add-panel-body">
                            <div class="settings-add-fields settings-add-fields-2 leave-type-add-fields-top">
                                <div class="settings-add-field">
                                    <label for="leaveTypeCodeInput">Code</label>
                                    <input type="text" id="leaveTypeCodeInput" name="code" maxlength="10" pattern="[A-Za-z0-9]+" placeholder="PL" required class="settings-add-input leave-type-code-input" style="text-transform: uppercase;">
                                    <span class="settings-add-field-hint">2–10 letters or numbers · shown first in dropdown</span>
                                </div>
                                <div class="settings-add-field">
                                    <label for="leaveTypeNameInput">Full name</label>
                                    <input type="text" id="leaveTypeNameInput" name="name" maxlength="60" placeholder="Privilege Leave" required class="settings-add-input">
                                    <span class="settings-add-field-hint">Descriptive label employees will recognise</span>
                                </div>
                            </div>
                            <div class="settings-add-fields settings-add-fields-2 leave-type-add-fields-bottom">
                                <div class="settings-add-field">
                                    <label for="leaveTypeCreditInput">Paid credit</label>
                                    <input type="number" id="leaveTypeCreditInput" name="paid_credit" step="0.01" min="0" max="1" value="1.00" class="settings-add-input">
                                    <span class="settings-add-field-hint">1.00 = counts as paid leave day</span>
                                </div>
                                <div class="settings-add-field leave-type-add-active-field">
                                    <label>Portal visibility</label>
                                    <?php render_settings_switch(
                                        'is_active',
                                        'Active in employee portal',
                                        true,
                                        ['class' => 'settings-switch-block']
                                    ); ?>
                                </div>
                            </div>
                            <ul class="settings-add-tips">
                                <li>Standard codes: <strong>PL</strong> (Privilege), <strong>SL</strong> (Sick), <strong>CL</strong> (Casual), <strong>LOP</strong> (Loss of Pay).</li>
                                <li>Yearly quotas for PL / SL / CL are configured in the section above.</li>
                            </ul>
                        </div>
                        <div class="settings-add-panel-foot">
                            <button type="submit" class="btn settings-add-submit settings-add-submit-leave">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                                Add leave type
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <script>
            (function () {
                document.querySelectorAll('[data-leave-type-form]').forEach(function (form) {
                    var nameInput = form.querySelector('[data-leave-name-input]');
                    var previewText = form.querySelector('[data-leave-preview-text]');
                    if (!nameInput || !previewText) return;
                    var code = nameInput.getAttribute('data-leave-code') || '';
                    function syncPreview() {
                        var name = (nameInput.value || '').trim();
                        previewText.textContent = name ? code + ' — ' + name : code;
                    }
                    nameInput.addEventListener('input', syncPreview);
                });
            })();
            </script>
            <?php endif; ?>

            <?php if ($tab === 'punch'): ?>
            <?php
            require_once 'includes/punch_helper.php';
            $punch_on = ($settings['punch_enabled'] ?? '1') === '1';
            $geo_on = ($settings['geo_attendance_enabled'] ?? '1') === '1';
            $face_on = ($settings['employee_face_login_enabled'] ?? '1') === '1';
            $office_start = get_office_start_time($settings);
            $office_end = get_office_end_time($settings);
            $grace_minutes = get_late_grace_minutes($settings);
            $office_start_label = date('g:i A', strtotime($office_start));
            $office_end_label = date('g:i A', strtotime($office_end));
            $geo_radius = (int) ($settings['geo_fence_radius_meters'] ?? 200);
            $branch_punch_rows = [];
            foreach ($all_branches as $branch) {
                $bstmt = $conn->prepare('SELECT office_latitude, office_longitude, geo_fence_radius_meters, office_start_time, office_end_time, late_grace_minutes FROM branches WHERE id = ?');
                $bid = (int) $branch['id'];
                $bstmt->bind_param('i', $bid);
                $bstmt->execute();
                $branch_punch_rows[] = [
                    'branch' => $branch,
                    'geo' => $bstmt->get_result()->fetch_assoc() ?: [],
                ];
            }
            ?>
            <div class="settings-tip settings-tip-punch">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <p>Employees punch from the portal using these defaults. <strong>Branch overrides</strong> below replace global geo and timing for that office only.</p>
            </div>
            <form method="POST" action="settings_save.php" class="settings-form settings-punch-panel">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="section" value="punch">

                <section class="settings-punch-block">
                    <header class="settings-punch-block-head">
                        <div class="settings-punch-block-icon settings-punch-block-icon-features" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        </div>
                        <div>
                            <h4>Portal features</h4>
                            <p class="settings-punch-block-desc">Turn punch, GPS and face login on or off for all employees.</p>
                        </div>
                    </header>
                    <div class="settings-punch-toggle-grid">
                        <label class="settings-punch-toggle-card<?php echo $punch_on ? ' is-on' : ''; ?>">
                            <input type="checkbox" name="punch_enabled" value="1" <?php echo $punch_on ? 'checked' : ''; ?>>
                            <span class="settings-punch-toggle-card-body">
                                <span class="settings-punch-toggle-card-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </span>
                                <span class="settings-punch-toggle-card-text">
                                    <strong>Punch in / out</strong>
                                    <span>Attendance punch on employee portal</span>
                                </span>
                                <span class="settings-switch-ui" aria-hidden="true"></span>
                            </span>
                        </label>
                        <label class="settings-punch-toggle-card<?php echo $geo_on ? ' is-on' : ''; ?>">
                            <input type="checkbox" name="geo_attendance_enabled" value="1" <?php echo $geo_on ? 'checked' : ''; ?>>
                            <span class="settings-punch-toggle-card-body">
                                <span class="settings-punch-toggle-card-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </span>
                                <span class="settings-punch-toggle-card-text">
                                    <strong>GPS geo-fence</strong>
                                    <span>Require location within office radius</span>
                                </span>
                                <span class="settings-switch-ui" aria-hidden="true"></span>
                            </span>
                        </label>
                        <label class="settings-punch-toggle-card<?php echo $face_on ? ' is-on' : ''; ?>">
                            <input type="checkbox" name="employee_face_login_enabled" value="1" <?php echo $face_on ? 'checked' : ''; ?>>
                            <span class="settings-punch-toggle-card-body">
                                <span class="settings-punch-toggle-card-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <span class="settings-punch-toggle-card-text">
                                    <strong>Face login</strong>
                                    <span>Optional after password sign-in</span>
                                </span>
                                <span class="settings-switch-ui" aria-hidden="true"></span>
                            </span>
                        </label>
                    </div>
                </section>

                <section class="settings-punch-block">
                    <header class="settings-punch-block-head">
                        <div class="settings-punch-block-icon settings-punch-block-icon-geo" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <h4>Default office location</h4>
                            <p class="settings-punch-block-desc">Used when a branch has no own coordinates. Copy from Google Maps → right-click → lat, lng.</p>
                        </div>
                    </header>
                    <div class="form-row form-row-3 settings-punch-field-row">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="office_latitude" placeholder="26.8467" value="<?php echo htmlspecialchars($settings['office_latitude'] ?? ''); ?>" class="settings-punch-input">
                        </div>
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="office_longitude" placeholder="80.9462" value="<?php echo htmlspecialchars($settings['office_longitude'] ?? ''); ?>" class="settings-punch-input">
                        </div>
                        <div class="form-group">
                            <label>Geofence radius</label>
                            <div class="settings-punch-input-suffix">
                                <input type="number" name="geo_fence_radius_meters" min="50" max="5000" step="10" value="<?php echo htmlspecialchars((string) $geo_radius); ?>" class="settings-punch-input">
                                <span>metres</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="settings-punch-block">
                    <header class="settings-punch-block-head">
                        <div class="settings-punch-block-icon settings-punch-block-icon-time" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <h4>Office timing</h4>
                            <p class="settings-punch-block-desc">Punch in vs start + grace · punch out vs end − grace.</p>
                        </div>
                    </header>
                    <div class="settings-punch-hours-preview" aria-hidden="true">
                        <span class="settings-punch-hours-chip settings-punch-hours-start"><?php echo htmlspecialchars($office_start_label); ?> start</span>
                        <span class="settings-punch-hours-line"></span>
                        <span class="settings-punch-hours-chip settings-punch-hours-grace"><?php echo (int) $grace_minutes; ?> min grace</span>
                        <span class="settings-punch-hours-line"></span>
                        <span class="settings-punch-hours-chip settings-punch-hours-end"><?php echo htmlspecialchars($office_end_label); ?> end</span>
                    </div>
                    <div class="form-row form-row-3 punch-timing-row settings-punch-field-row">
                        <div class="form-group">
                            <label>Office start</label>
                            <input type="time" class="form-input-time settings-punch-input" name="office_start_time" step="60" value="<?php echo htmlspecialchars($office_start); ?>">
                            <span class="form-hint">Late after start + grace</span>
                        </div>
                        <div class="form-group">
                            <label>Office end</label>
                            <input type="time" class="form-input-time settings-punch-input" name="office_end_time" step="60" value="<?php echo htmlspecialchars($office_end); ?>">
                            <span class="form-hint">Early before end − grace</span>
                        </div>
                        <div class="form-group">
                            <label>Grace period</label>
                            <div class="settings-punch-input-suffix">
                                <input type="number" name="late_grace_minutes" min="0" max="120" step="1" value="<?php echo htmlspecialchars((string) $grace_minutes); ?>" class="settings-punch-input">
                                <span>min</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="settings-punch-block">
                    <header class="settings-punch-block-head">
                        <div class="settings-punch-block-icon settings-punch-block-icon-rules" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div>
                            <h4>Attendance rules</h4>
                            <p class="settings-punch-block-desc">How punches affect attendance and payroll. Late + early together = half day.</p>
                        </div>
                    </header>
                    <div class="settings-punch-rules-grid">
                        <?php render_settings_switch(
                            'half_day_on_late_in',
                            'Half day on late punch in',
                            ($settings['half_day_on_late_in'] ?? '1') === '1',
                            ['class' => 'settings-switch-chip settings-punch-rule-chip']
                        ); ?>
                        <?php render_settings_switch(
                            'half_day_on_early_out',
                            'Half day on early punch out',
                            ($settings['half_day_on_early_out'] ?? '1') === '1',
                            ['class' => 'settings-switch-chip settings-punch-rule-chip']
                        ); ?>
                        <?php render_settings_switch(
                            'auto_absent_no_punch',
                            'Auto absent if no punch',
                            ($settings['auto_absent_no_punch'] ?? '1') === '1',
                            ['class' => 'settings-switch-chip settings-punch-rule-chip']
                        ); ?>
                        <?php render_settings_switch(
                            'punch_sync_overtime',
                            'Sync overtime from hours',
                            ($settings['punch_sync_overtime'] ?? '1') === '1',
                            ['class' => 'settings-switch-chip settings-punch-rule-chip']
                        ); ?>
                        <?php render_settings_switch(
                            'block_punch_on_holiday_weekoff',
                            'Block punch on holiday / week off',
                            ($settings['block_punch_on_holiday_weekoff'] ?? '1') === '1',
                            ['class' => 'settings-switch-chip settings-punch-rule-chip']
                        ); ?>
                    </div>
                    <div class="form-row form-row-3 settings-punch-field-row settings-punch-rules-fields">
                        <div class="form-group">
                            <label>Missing punch out</label>
                            <select name="missing_punch_out_status" class="settings-punch-input">
                                <option value="half_day" <?php echo ($settings['missing_punch_out_status'] ?? 'half_day') === 'half_day' ? 'selected' : ''; ?>>Half day</option>
                                <option value="absent" <?php echo ($settings['missing_punch_out_status'] ?? '') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                            </select>
                            <span class="form-hint">End of day with no punch out</span>
                        </div>
                        <div class="form-group">
                            <label>Late count penalty</label>
                            <div class="settings-punch-input-suffix">
                                <input type="number" name="late_count_for_half_day" min="0" max="31" step="1" value="<?php echo htmlspecialchars($settings['late_count_for_half_day'] ?? '3'); ?>" class="settings-punch-input">
                                <span>lates</span>
                            </div>
                            <span class="form-hint">0 = off · N lates = −½ day paid</span>
                        </div>
                    </div>
                </section>

                <section class="settings-punch-block settings-punch-block-branches">
                    <header class="settings-punch-block-head settings-punch-branch-head">
                        <div class="settings-punch-block-icon settings-punch-block-icon-branch" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </div>
                        <div>
                            <h4>Branch overrides</h4>
                            <p class="settings-punch-block-desc">Optional per-branch GPS and timing. Leave blank to use global defaults.</p>
                        </div>
                        <span class="settings-punch-branch-count"><?php echo count($branch_punch_rows); ?></span>
                    </header>
                    <?php if ($branch_punch_rows === []): ?>
                        <div class="settings-punch-branch-empty">
                            <p>No branches yet. Add offices under <a href="settings.php?tab=branches">Settings → Branches</a>.</p>
                        </div>
                    <?php else: ?>
                        <ul class="settings-punch-branch-list">
                            <?php foreach ($branch_punch_rows as $branch_row):
                                $branch = $branch_row['branch'];
                                $bgeo = $branch_row['geo'];
                                $bid = (int) $branch['id'];
                                $binitial = strtoupper(substr((string) ($branch['code'] ?? 'B'), 0, 2));
                                $has_override = trim((string) ($bgeo['office_latitude'] ?? '')) !== ''
                                    || trim((string) ($bgeo['office_longitude'] ?? '')) !== ''
                                    || trim((string) ($bgeo['office_start_time'] ?? '')) !== '';
                                ?>
                                <li class="settings-punch-branch-card">
                                    <div class="settings-punch-branch-card-head">
                                        <span class="settings-punch-branch-avatar" aria-hidden="true"><?php echo htmlspecialchars($binitial); ?></span>
                                        <div>
                                            <strong><?php echo htmlspecialchars($branch['name']); ?></strong>
                                            <span><?php echo htmlspecialchars($branch['code']); ?></span>
                                        </div>
                                        <?php if ($has_override): ?>
                                            <span class="settings-punch-branch-badge">Custom</span>
                                        <?php else: ?>
                                            <span class="settings-punch-branch-badge is-default">Defaults</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-row form-row-3 settings-punch-field-row">
                                        <div class="form-group">
                                            <label>Latitude</label>
                                            <input type="text" name="branch_lat[<?php echo $bid; ?>]" value="<?php echo htmlspecialchars($bgeo['office_latitude'] ?? ''); ?>" class="settings-punch-input" placeholder="Use default">
                                        </div>
                                        <div class="form-group">
                                            <label>Longitude</label>
                                            <input type="text" name="branch_lng[<?php echo $bid; ?>]" value="<?php echo htmlspecialchars($bgeo['office_longitude'] ?? ''); ?>" class="settings-punch-input" placeholder="Use default">
                                        </div>
                                        <div class="form-group">
                                            <label>Radius</label>
                                            <div class="settings-punch-input-suffix">
                                                <input type="number" name="branch_radius[<?php echo $bid; ?>]" min="50" max="5000" step="10" value="<?php echo htmlspecialchars($bgeo['geo_fence_radius_meters'] ?? ''); ?>" class="settings-punch-input" placeholder="<?php echo (int) $geo_radius; ?>">
                                                <span>m</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row form-row-3 punch-timing-row settings-punch-field-row">
                                        <div class="form-group">
                                            <label>Start time</label>
                                            <input type="time" class="form-input-time settings-punch-input" name="branch_start[<?php echo $bid; ?>]" step="60" value="<?php echo htmlspecialchars($bgeo['office_start_time'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>End time</label>
                                            <input type="time" class="form-input-time settings-punch-input" name="branch_end[<?php echo $bid; ?>]" step="60" value="<?php echo htmlspecialchars($bgeo['office_end_time'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Grace</label>
                                            <div class="settings-punch-input-suffix">
                                                <input type="number" name="branch_grace[<?php echo $bid; ?>]" min="0" max="120" step="1" value="<?php echo htmlspecialchars($bgeo['late_grace_minutes'] ?? ''); ?>" class="settings-punch-input" placeholder="<?php echo (int) $grace_minutes; ?>">
                                                <span>min</span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <div class="settings-form-actions settings-punch-actions">
                    <button type="submit" class="btn settings-punch-save-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save punch settings
                    </button>
                    <a href="punch_logs.php" class="btn btn-outline btn-sm">Punch logs</a>
                    <a href="punch_report.php" class="btn btn-outline btn-sm">Monthly report</a>
                </div>
            </form>
            <script>
            (function () {
                document.querySelectorAll('.settings-punch-toggle-card input').forEach(function (input) {
                    function syncCard() {
                        input.closest('.settings-punch-toggle-card').classList.toggle('is-on', input.checked);
                    }
                    input.addEventListener('change', syncCard);
                });
            })();
            </script>
            <?php endif; ?>

            <?php if ($tab === 'branches' && !is_super_admin()): ?>
                <div class="alert alert-error alert-page">Only Head Office (super admin) can manage branches.</div>
            <?php elseif ($tab === 'branches'): ?>
            <?php
            $branch_rows = get_all_branches_for_admin($conn);
            $branch_count = count($branch_rows);
            $active_branch_count = 0;
            foreach ($branch_rows as $branch_row) {
                if ((int) ($branch_row['is_active'] ?? 0) === 1) {
                    $active_branch_count++;
                }
            }
            ?>
            <div class="settings-tip settings-tip-branches">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <p>Branches are <strong>deactivated</strong>, not permanently deleted. Remove is blocked while employees or branch admins are still assigned.</p>
            </div>
            <div class="settings-form settings-branches-panel">
                <div class="settings-form-section">
                    <div class="branch-list-section-head">
                        <div>
                            <h4>Branches</h4>
                            <p class="form-hint branch-list-section-hint">
                                <?php echo $active_branch_count; ?> active
                                <?php if ($branch_count > $active_branch_count): ?>
                                    · <?php echo $branch_count - $active_branch_count; ?> inactive
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="branch-list-count-badge" aria-hidden="true"><?php echo (int) $branch_count; ?></span>
                    </div>
                    <?php if ($branch_rows === []): ?>
                        <div class="branch-list-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <p>No branches found. Add your first office location below.</p>
                        </div>
                    <?php else: ?>
                        <ul class="branch-card-list">
                            <?php foreach ($branch_rows as $branch):
                                $bid = (int) $branch['id'];
                                $emp_count = count_branch_employees($conn, $bid);
                                $admin_count = count_branch_admin_users($conn, $bid);
                                $is_active = (int) ($branch['is_active'] ?? 0) === 1;
                                $can_remove = $is_active && $emp_count === 0 && $admin_count === 0;
                                $initial = strtoupper(substr((string) ($branch['code'] ?? 'B'), 0, 2));
                                ?>
                                <li class="branch-card<?php echo $is_active ? '' : ' is-inactive'; ?>">
                                    <div class="branch-card-main">
                                        <span class="branch-card-icon" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></span>
                                        <div class="branch-card-text">
                                            <div class="branch-card-title-row">
                                                <span class="branch-card-name"><?php echo htmlspecialchars($branch['name']); ?></span>
                                                <?php if ($is_active): ?>
                                                    <span class="branch-status-badge branch-status-active">Active</span>
                                                <?php else: ?>
                                                    <span class="branch-status-badge branch-status-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="branch-card-code"><?php echo htmlspecialchars($branch['code']); ?></span>
                                            <div class="branch-card-stats">
                                                <span class="branch-stat-chip<?php echo $emp_count > 0 ? ' has-value' : ''; ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                                    <?php echo (int) $emp_count; ?> employee<?php echo $emp_count === 1 ? '' : 's'; ?>
                                                </span>
                                                <span class="branch-stat-chip<?php echo $admin_count > 0 ? ' has-value' : ''; ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                    <?php echo (int) $admin_count; ?> admin<?php echo $admin_count === 1 ? '' : 's'; ?>
                                                </span>
                                            </div>
                                            <?php if ($is_active && !$can_remove): ?>
                                                <p class="branch-card-lock-hint">Reassign employees and admins before removing this branch.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="branch-card-actions">
                                        <?php if ($is_active): ?>
                                            <form method="POST" action="settings_save.php" class="inline-delete-form" onsubmit="return confirm('Remove branch <?php echo htmlspecialchars($branch['name'], ENT_QUOTES); ?>?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="section" value="branch_delete">
                                                <input type="hidden" name="branch_id" value="<?php echo $bid; ?>">
                                                <button type="submit" class="btn btn-outline btn-sm btn-danger-outline" <?php echo $can_remove ? '' : 'disabled'; ?>>Remove</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="branch-card-removed-label">Removed</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <form method="POST" action="settings_save.php" class="stack-form branch-add-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="branch_add">
                    <div class="settings-add-panel settings-add-panel-branch">
                        <div class="settings-add-panel-head">
                            <span class="settings-add-panel-icon settings-add-panel-icon-branch" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                            </span>
                            <div class="settings-add-panel-head-text">
                                <h4>Add branch</h4>
                                <p>Create a new office location for employees, holidays, and punch settings.</p>
                            </div>
                        </div>
                        <div class="settings-add-panel-body">
                            <div class="settings-add-fields settings-add-fields-2">
                                <div class="settings-add-field">
                                    <label for="branchCodeInput">Branch code</label>
                                    <input type="text" id="branchCodeInput" name="branch_code" maxlength="20" pattern="[A-Za-z0-9_-]{2,20}" placeholder="NOIDA" required class="branch-code-input settings-add-input">
                                    <span class="settings-add-field-hint">2–20 characters · letters, numbers, dash</span>
                                </div>
                                <div class="settings-add-field">
                                    <label for="branchNameInput">Branch name</label>
                                    <input type="text" id="branchNameInput" name="branch_name" maxlength="100" placeholder="Noida Office" required class="settings-add-input">
                                    <span class="settings-add-field-hint">Full name shown across the panel</span>
                                </div>
                            </div>
                            <ul class="settings-add-tips">
                                <li>Employees can be assigned to this branch after it is created.</li>
                                <li>Configure geo-fence and office timing under <strong>Punch &amp; Geo</strong>.</li>
                            </ul>
                        </div>
                        <div class="settings-add-panel-foot">
                            <button type="submit" class="btn settings-add-submit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                                Create branch
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($tab === 'admins' && !is_super_admin()): ?>
                <div class="alert alert-error alert-page">Only Head Office (super admin) can manage administrator accounts.</div>
            <?php elseif ($tab === 'admins'): ?>
            <?php
            $admin_rows = [];
            if ($admin_users) {
                while ($row = $admin_users->fetch_assoc()) {
                    $admin_rows[] = $row;
                }
            }
            $admin_count = count($admin_rows);
            ?>
            <div class="settings-tip settings-tip-security settings-tip-admins">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <p>These are <strong>login accounts</strong> for the admin panel — not office branches. To add or remove a branch (Indra Nagar, Alambagh, etc.), use <a href="settings.php?tab=branches">Settings → Branches</a>.</p>
            </div>
            <div class="settings-form settings-admins-panel">
                <div class="settings-form-section">
                    <div class="admin-users-section-head">
                        <div>
                            <h4>Administrator login accounts</h4>
                            <p class="form-hint admin-users-section-hint">Who can sign in to this panel · <?php echo $admin_count === 1 ? '1 account' : $admin_count . ' accounts'; ?></p>
                        </div>
                        <span class="admin-users-count-badge" aria-hidden="true"><?php echo (int) $admin_count; ?></span>
                    </div>
                    <?php if ($admin_count === 0): ?>
                        <div class="admin-users-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <p>No admin users found. Add one below to restore access.</p>
                        </div>
                    <?php else: ?>
                        <ul class="admin-user-list">
                            <?php foreach ($admin_rows as $au):
                                $uname = $au['username'];
                                $is_self = $uname === $_SESSION['admin_username'];
                                $initial = strtoupper(substr($uname, 0, 1));
                                ?>
                            <li class="admin-user-card<?php echo $is_self ? ' is-self' : ''; ?>">
                                <div class="admin-user-card-main">
                                    <span class="admin-user-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></span>
                                    <div class="admin-user-card-text">
                                        <span class="admin-user-name"><?php echo htmlspecialchars($uname); ?></span>
                                        <span class="admin-user-meta"><?php
                                            $role_label = !empty($au['role_name']) ? $au['role_name'] : 'No role';
                                            if ($is_self) {
                                                echo 'Signed in as you · ' . htmlspecialchars($role_label);
                                            } elseif ($au['branch_id'] === null) {
                                                echo 'Head Office (all branches) · ' . htmlspecialchars($role_label);
                                            } else {
                                                echo htmlspecialchars(get_branch_label($conn, (int) $au['branch_id'])) . ' · ' . htmlspecialchars($role_label);
                                            }
                                        ?></span>
                                    </div>
                                </div>
                                <div class="admin-user-card-actions">
                                    <?php if ($is_self): ?>
                                        <span class="badge badge-present admin-user-you-badge">You</span>
                                    <?php else: ?>
                                        <form method="POST" action="settings_save.php" class="admin-user-role-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="section" value="admins">
                                            <input type="hidden" name="admin_action" value="set_role">
                                            <input type="hidden" name="admin_id" value="<?php echo (int) $au['id']; ?>">
                                            <select name="role_id" class="settings-add-input admin-role-select" aria-label="Role for <?php echo htmlspecialchars($uname); ?>" onchange="this.form.submit()">
                                                <option value="">— Role —</option>
                                                <?php foreach ($admin_roles_list as $role): ?>
                                                    <option value="<?php echo (int) $role['id']; ?>" <?php echo (int) ($au['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <form method="POST" action="settings_save.php" class="inline-delete-form" onsubmit="return confirm('Remove admin <?php echo htmlspecialchars($uname, ENT_QUOTES); ?>?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="section" value="admins">
                                            <input type="hidden" name="admin_action" value="delete">
                                            <input type="hidden" name="delete_username" value="<?php echo htmlspecialchars($uname); ?>">
                                            <button type="submit" class="btn btn-outline btn-sm btn-danger-outline">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <form method="POST" action="settings_save.php" class="stack-form admin-add-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="admins">
                    <input type="hidden" name="admin_action" value="add">
                    <div class="settings-add-panel settings-add-panel-admin">
                        <div class="settings-add-panel-head">
                            <span class="settings-add-panel-icon settings-add-panel-icon-admin" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                            </span>
                            <div class="settings-add-panel-head-text">
                                <h4>Add administrator</h4>
                                <p>Create a login for someone who will access this admin panel — not an employee portal account.</p>
                            </div>
                        </div>
                        <div class="settings-add-panel-body">
                            <div class="settings-add-fields settings-add-fields-3">
                                <div class="settings-add-field">
                                    <label for="newAdminUsername">Username</label>
                                    <input type="text" id="newAdminUsername" name="new_username" required autocomplete="off" placeholder="e.g. indranagar" class="settings-add-input">
                                    <span class="settings-add-field-hint">Used at the admin login screen</span>
                                </div>
                                <div class="settings-add-field">
                                    <label for="newAdminBranch">Branch access</label>
                                    <select id="newAdminBranch" name="new_branch_id" required class="settings-add-input settings-add-select">
                                        <option value="">Select branch access</option>
                                        <option value="0">Head Office — all branches</option>
                                        <?php foreach ($all_branches as $branch): ?>
                                            <option value="<?php echo (int) $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?> only</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="settings-add-field-hint">Limits which branch data they can see</span>
                                </div>
                                <div class="settings-add-field">
                                    <label for="newAdminRole">Role</label>
                                    <select id="newAdminRole" name="role_id" required class="settings-add-input settings-add-select">
                                        <?php foreach ($admin_roles_list as $role): ?>
                                            <option value="<?php echo (int) $role['id']; ?>" <?php echo ($role['code'] ?? '') === 'branch_admin' ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="settings-add-field-hint">Controls menu access via Roles page</span>
                                </div>
                                <div class="settings-add-field">
                                    <label for="pwd_new_password">Password</label>
                                    <?php render_password_input('new_password', 'required minlength="6" autocomplete="new-password" placeholder="Min. 6 characters"'); ?>
                                    <span class="settings-add-field-hint">Share securely after creating the account</span>
                                </div>
                            </div>
                            <ul class="settings-add-tips">
                                <li>This is a <strong>panel login</strong>, separate from employee portal (EMP001) accounts.</li>
                                <li>Branch admins see only their branch; Head Office sees everything.</li>
                            </ul>
                        </div>
                        <div class="settings-add-panel-foot">
                            <button type="submit" class="btn settings-add-submit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                                Create administrator
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.settings-layout .settings-switch input[type="checkbox"]').forEach(function (input) {
    function syncSwitch() {
        var wrap = input.closest('.settings-switch');
        if (wrap) {
            wrap.classList.toggle('is-on', input.checked);
        }
    }
    syncSwitch();
    input.addEventListener('change', syncSwitch);
});

document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-visible', show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>

<?php require 'includes/footer.php'; ?>
