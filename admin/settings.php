<?php
require 'includes/header.php';
require 'config.php';
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
$admin_users = $conn->query('SELECT id, username, branch_id FROM admin_users ORDER BY username');
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
            <div class="settings-tip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <p>For <strong>Gmail</strong>: use <code>smtp.gmail.com</code>, port <code>587</code>, TLS, and an <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">App Password</a>.</p>
            </div>
            <form method="POST" action="settings_save.php" class="stack-form settings-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="section" value="smtp">
                <div class="settings-form-section">
                    <h4>Server</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_encryption">
                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (recommended)</option>
                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Authentication</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" placeholder="your@email.com" autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <?php
                            $smtp_placeholder = !empty($settings['smtp_password']) ? 'Saved — leave blank to keep' : 'SMTP password';
                            render_password_input('smtp_password', 'placeholder="' . htmlspecialchars($smtp_placeholder) . '" autocomplete="new-password"');
                            ?>
                        </div>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Sender identity</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>From Email</label>
                            <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>" required placeholder="noreply@company.com">
                        </div>
                        <div class="form-group">
                            <label>From Name</label>
                            <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? 'Payroll System'); ?>">
                        </div>
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save SMTP Settings
                    </button>
                </div>
            </form>
            <form method="POST" action="test_smtp.php" class="smtp-test-form">
                <?php echo csrf_field(); ?>
                <h4>Test connection</h4>
                <p class="form-hint">Save SMTP settings first, then send a test email (e.g. payroll@yopmail.com).</p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Send test to</label>
                        <input type="email" name="test_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? 'payroll@yopmail.com'); ?>" required>
                    </div>
                    <div class="form-group form-group-btn">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-outline">Send Test Email</button>
                    </div>
                </div>
            </form>
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
            <div class="settings-tip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <p><strong>Paid days</strong> = Present + (Half day × credit) + (Leave × credit). <strong>Net</strong> = gross split − PF, PT, ESI (if applicable).</p>
            </div>
            <form method="POST" action="settings_save.php" enctype="multipart/form-data" class="stack-form settings-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="section" value="payroll">
                <div class="settings-form-section">
                    <h4>Company &amp; calculation</h4>
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" placeholder="Shown on salary slips">
                    </div>
                    <div class="form-group">
                        <label>Working Days Per Month</label>
                        <input type="number" name="working_days_per_month" min="1" max="31" value="<?php echo htmlspecialchars($settings['working_days_per_month'] ?? '26'); ?>" required>
                        <span class="form-hint">Typically 22–26 for monthly payroll</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Half day credit</label>
                            <input type="number" name="half_day_credit" step="0.1" min="0" max="1" value="<?php echo htmlspecialchars($settings['half_day_credit'] ?? '0.5'); ?>">
                            <span class="form-hint">0.5 = half day counts as half paid day</span>
                        </div>
                        <div class="form-group">
                            <label>Leave day credit</label>
                            <input type="number" name="leave_day_credit" step="0.1" min="0" max="1" value="<?php echo htmlspecialchars($settings['leave_day_credit'] ?? '1'); ?>">
                            <span class="form-hint">1 = paid leave, 0 = unpaid</span>
                        </div>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Salary structure (% of gross)</h4>
                    <div class="form-row">
                        <div class="form-group"><label>Basic %</label><input type="number" name="pct_basic" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_basic'] ?? '50'); ?>"></div>
                        <div class="form-group"><label>HRA %</label><input type="number" name="pct_hra" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_hra'] ?? '20'); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Conveyance %</label><input type="number" name="pct_conveyance" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_conveyance'] ?? '5'); ?>"></div>
                        <div class="form-group"><label>Medical %</label><input type="number" name="pct_medical" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_medical'] ?? '5'); ?>"></div>
                        <div class="form-group"><label>Special %</label><input type="number" name="pct_special" step="0.1" min="0" max="100" value="<?php echo htmlspecialchars($settings['pct_special'] ?? '20'); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>PF % of Basic</label><input type="number" name="pf_percent" step="0.1" min="0" max="30" value="<?php echo htmlspecialchars($settings['pf_percent'] ?? '12'); ?>"></div>
                        <div class="form-group"><label>PF Basic Min Limit (₹)</label><input type="number" name="pf_min_limit" step="1" min="0" value="<?php echo htmlspecialchars($settings['pf_min_limit'] ?? '0'); ?>"><span class="form-hint">0 for no limit</span></div>
                        <div class="form-group"><label>PF Basic Max Limit (₹)</label><input type="number" name="pf_max_limit" step="1" min="0" value="<?php echo htmlspecialchars($settings['pf_max_limit'] ?? '15000'); ?>"><span class="form-hint">0 for no limit</span></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Professional tax (₹)</label><input type="number" name="professional_tax" step="1" min="0" value="<?php echo htmlspecialchars($settings['professional_tax'] ?? '200'); ?>"></div>
                        <div class="form-group"><label>ESI %</label><input type="number" name="esi_percent" step="0.01" min="0" max="5" value="<?php echo htmlspecialchars($settings['esi_percent'] ?? '0.75'); ?>"></div>
                        <div class="form-group"><label>ESI if gross ≤</label><input type="number" name="esi_gross_limit" step="1" min="0" value="<?php echo htmlspecialchars($settings['esi_gross_limit'] ?? '21000'); ?>"></div>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Payroll workflow</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="require_payroll_approval" value="1" <?php echo !isset($settings['require_payroll_approval']) || (int) $settings['require_payroll_approval'] === 1 ? 'checked' : ''; ?>>
                                Require payroll approval before showing slips in employee portal
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Overtime hours / day</label>
                            <input type="number" name="overtime_hours_per_day" step="0.5" min="1" max="24" value="<?php echo htmlspecialchars($settings['overtime_hours_per_day'] ?? '8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>OT pay multiplier</label>
                            <input type="number" name="overtime_multiplier" step="0.1" min="1" max="3" value="<?php echo htmlspecialchars($settings['overtime_multiplier'] ?? '1.5'); ?>">
                            <span class="form-hint">e.g. 1.5× hourly rate</span>
                        </div>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Payslip authorized signature</h4>
                    <p class="form-hint" style="margin-bottom:16px">Upload a PNG/JPG signature image. It will appear on the bottom-right of every salary slip PDF.</p>
                    <?php if ($signature_url): ?>
                        <div class="signature-preview-box">
                            <img src="<?php echo htmlspecialchars($signature_url); ?>" alt="Current signature">
                            <div class="signature-preview-meta">
                                <span class="badge badge-present">Signature active</span>
                                <label class="signature-remove-label">
                                    <input type="checkbox" name="remove_signature" value="1"> Remove signature
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="signature-preview-box signature-preview-empty">
                            <p>No signature uploaded yet</p>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Upload signature image</label>
                        <input type="file" name="payslip_signature" accept="image/png,image/jpeg,image/jpg,image/gif">
                        <span class="form-hint">Transparent PNG recommended. Max 2MB.</span>
                    </div>
                    <div class="form-group">
                        <label>Name below signature</label>
                        <input type="text" name="signature_authority_name" value="<?php echo htmlspecialchars($settings['signature_authority_name'] ?? 'Authorized Signatory'); ?>" placeholder="e.g. HR Manager / Director">
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Payroll Settings
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
                    <div class="form-row">
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
                    <div class="form-row">
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

            <div class="settings-form-section" style="margin-top: 32px;">
                <h4>Leave Types (Employee Portal)</h4>
                <p class="form-hint" style="margin-bottom: 16px;">Active types appear in the employee leave request form as <strong>CODE — Full Name</strong> (e.g. PL — Privilege Leave).</p>
                <?php if ($all_leave_types === []): ?>
                    <p class="form-hint">No leave types yet. Add one below.</p>
                <?php else: ?>
                    <div class="table-wrap" style="margin-bottom: 20px;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Full Name</th>
                                    <th>Paid Credit</th>
                                    <th>Status</th>
                                    <th>Preview</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_leave_types as $lt): ?>
                                    <tr>
                                        <form method="POST" action="settings_save.php">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="section" value="leave_type_save">
                                            <input type="hidden" name="code" value="<?php echo htmlspecialchars($lt['code']); ?>">
                                            <td><strong><?php echo htmlspecialchars($lt['code']); ?></strong></td>
                                            <td><input type="text" name="name" value="<?php echo htmlspecialchars($lt['name']); ?>" required maxlength="60" style="width:100%;"></td>
                                            <td><input type="number" name="paid_credit" value="<?php echo htmlspecialchars($lt['paid_credit']); ?>" step="0.01" min="0" max="1" style="width:80px;"></td>
                                            <td>
                                                <label style="display:flex; align-items:center; gap:6px; margin:0;">
                                                    <input type="checkbox" name="is_active" value="1" <?php echo !empty($lt['is_active']) ? 'checked' : ''; ?>>
                                                    Active
                                                </label>
                                            </td>
                                            <td><?php echo htmlspecialchars(format_leave_type_label($lt)); ?></td>
                                            <td><button type="submit" class="btn btn-sm">Save</button></td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <form method="POST" action="settings_save.php" class="settings-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="section" value="leave_type_add">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Code</label>
                            <input type="text" name="code" maxlength="10" pattern="[A-Za-z0-9]+" placeholder="PL" required style="text-transform: uppercase;">
                            <span class="form-hint">Short code shown to employees</span>
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" maxlength="60" placeholder="Privilege Leave" required>
                        </div>
                        <div class="form-group">
                            <label>Paid Credit</label>
                            <input type="number" name="paid_credit" step="0.01" min="0" max="1" value="1.00">
                        </div>
                        <div class="form-group" style="display:flex; align-items:flex-end;">
                            <label style="display:flex; align-items:center; gap:8px; margin:0;">
                                <input type="checkbox" name="is_active" value="1" checked>
                                Active in employee portal
                            </label>
                        </div>
                    </div>
                    <div class="settings-form-actions">
                        <button type="submit" class="btn">Add Leave Type</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($tab === 'punch'): ?>
            <?php require_once 'includes/punch_helper.php'; ?>
            <form method="POST" action="settings_save.php" class="settings-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="section" value="punch">
                <div class="settings-form-section">
                    <h4>Employee punch</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="punch_enabled" value="1" <?php echo ($settings['punch_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Enable punch in / punch out on employee portal
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="geo_attendance_enabled" value="1" <?php echo ($settings['geo_attendance_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Require GPS geo-fence for punch
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="employee_face_login_enabled" value="1" <?php echo ($settings['employee_face_login_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Allow face recognition login on employee portal
                            </label>
                            <span class="form-hint">Employees enroll face after password sign-in · password login always works</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Default office latitude</label>
                            <input type="text" name="office_latitude" placeholder="26.8467" value="<?php echo htmlspecialchars($settings['office_latitude'] ?? ''); ?>">
                            <span class="form-hint">Used when branch has no own coordinates (Google Maps → right-click → copy lat)</span>
                        </div>
                        <div class="form-group">
                            <label>Default office longitude</label>
                            <input type="text" name="office_longitude" placeholder="80.9462" value="<?php echo htmlspecialchars($settings['office_longitude'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Geofence radius (metres)</label>
                            <input type="number" name="geo_fence_radius_meters" min="50" max="5000" step="10" value="<?php echo htmlspecialchars($settings['geo_fence_radius_meters'] ?? '200'); ?>">
                        </div>
                    </div>
                </div>

                <div class="settings-form-section">
                    <h4>Office timing (late / on-time)</h4>
                    <p class="form-hint" style="margin-bottom:12px;">Punch in is checked against <strong>start time</strong>; punch out against <strong>end time</strong>. Grace minutes apply to both.</p>
                    <div class="form-row punch-timing-row">
                        <div class="form-group">
                            <label>Office start time</label>
                            <input type="time" class="form-input-time" name="office_start_time" step="60" value="<?php echo htmlspecialchars(get_office_start_time($settings)); ?>">
                            <span class="form-hint">Late if punch in after start + grace</span>
                        </div>
                        <div class="form-group">
                            <label>Office end time</label>
                            <input type="time" class="form-input-time" name="office_end_time" step="60" value="<?php echo htmlspecialchars(get_office_end_time($settings)); ?>">
                            <span class="form-hint">Early leave if punch out before end − grace</span>
                        </div>
                        <div class="form-group">
                            <label>Grace (minutes)</label>
                            <input type="number" name="late_grace_minutes" min="0" max="120" step="1" value="<?php echo htmlspecialchars((string) get_late_grace_minutes($settings)); ?>">
                            <span class="form-hint">Buffer for both punch in and punch out</span>
                        </div>
                    </div>
                </div>

                <div class="settings-form-section">
                    <h4>Punch attendance rules</h4>
                    <p class="form-hint" style="margin-bottom:12px;">How punch in/out affects attendance status. Late + early together always counts as half day.</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="half_day_on_late_in" value="1" <?php echo ($settings['half_day_on_late_in'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Half day on late punch in only
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="half_day_on_early_out" value="1" <?php echo ($settings['half_day_on_early_out'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Half day on early punch out only
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Missing punch out (end of day)</label>
                            <select name="missing_punch_out_status">
                                <option value="half_day" <?php echo ($settings['missing_punch_out_status'] ?? 'half_day') === 'half_day' ? 'selected' : ''; ?>>Half day</option>
                                <option value="absent" <?php echo ($settings['missing_punch_out_status'] ?? '') === 'absent' ? 'selected' : ''; ?>>Absent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;margin-top:28px;">
                                <input type="checkbox" name="auto_absent_no_punch" value="1" <?php echo ($settings['auto_absent_no_punch'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Auto-mark absent when no punch on working day
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Late count for payroll penalty</label>
                            <input type="number" name="late_count_for_half_day" min="0" max="31" step="1" value="<?php echo htmlspecialchars($settings['late_count_for_half_day'] ?? '3'); ?>">
                            <span class="form-hint">0 = disabled. Each N lates deducts one half-day credit from paid days.</span>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;margin-top:28px;">
                                <input type="checkbox" name="punch_sync_overtime" value="1" <?php echo ($settings['punch_sync_overtime'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Sync overtime from punch work hours
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;margin-top:28px;">
                                <input type="checkbox" name="block_punch_on_holiday_weekoff" value="1" <?php echo ($settings['block_punch_on_holiday_weekoff'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                Block punch on holidays and employee week off
                            </label>
                        </div>
                    </div>
                </div>

                <div class="settings-form-section">
                    <h4>Branch office locations (optional)</h4>
                    <p class="form-hint" style="margin-bottom:12px;">Override default coordinates per branch. Leave blank to use default office location above.</p>
                    <?php foreach ($all_branches as $branch): ?>
                        <?php
                        $bstmt = $conn->prepare('SELECT office_latitude, office_longitude, geo_fence_radius_meters, office_start_time, office_end_time, late_grace_minutes FROM branches WHERE id = ?');
                        $bid = (int) $branch['id'];
                        $bstmt->bind_param('i', $bid);
                        $bstmt->execute();
                        $bgeo = $bstmt->get_result()->fetch_assoc() ?: [];
                        ?>
                        <div class="form-row punch-branch-row">
                            <div class="form-group">
                                <label><?php echo htmlspecialchars($branch['name']); ?> — Latitude</label>
                                <input type="text" name="branch_lat[<?php echo $bid; ?>]" value="<?php echo htmlspecialchars($bgeo['office_latitude'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="branch_lng[<?php echo $bid; ?>]" value="<?php echo htmlspecialchars($bgeo['office_longitude'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Radius (m)</label>
                                <input type="number" name="branch_radius[<?php echo $bid; ?>]" min="50" max="5000" step="10" value="<?php echo htmlspecialchars($bgeo['geo_fence_radius_meters'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row punch-branch-row punch-timing-row">
                            <div class="form-group">
                                <label><?php echo htmlspecialchars($branch['name']); ?> — Start time</label>
                                <input type="time" class="form-input-time" name="branch_start[<?php echo $bid; ?>]" step="60" value="<?php echo htmlspecialchars($bgeo['office_start_time'] ?? ''); ?>">
                                <span class="form-hint">Blank = use global default</span>
                            </div>
                            <div class="form-group">
                                <label>End time</label>
                                <input type="time" class="form-input-time" name="branch_end[<?php echo $bid; ?>]" step="60" value="<?php echo htmlspecialchars($bgeo['office_end_time'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Grace (min)</label>
                                <input type="number" name="branch_grace[<?php echo $bid; ?>]" min="0" max="120" step="1" value="<?php echo htmlspecialchars($bgeo['late_grace_minutes'] ?? ''); ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="settings-form-actions">
                    <button type="submit" class="btn">Save Punch &amp; Geo Settings</button>
                    <a href="punch_logs.php" class="btn btn-outline">View punch logs</a>
                    <a href="punch_report.php" class="btn btn-outline">Monthly punch report</a>
                </div>
            </form>
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
                                            if ($is_self) {
                                                echo 'Signed in as you';
                                            } elseif ($au['branch_id'] === null) {
                                                echo 'Branch access: Head Office (all branches)';
                                            } else {
                                                echo 'Branch access: ' . htmlspecialchars(get_branch_label($conn, (int) $au['branch_id']));
                                            }
                                        ?></span>
                                    </div>
                                </div>
                                <div class="admin-user-card-actions">
                                    <?php if ($is_self): ?>
                                        <span class="badge badge-present admin-user-you-badge">You</span>
                                    <?php else: ?>
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
