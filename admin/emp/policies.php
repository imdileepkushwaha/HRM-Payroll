<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = get_all_settings($conn);
$policies = trim($settings['company_policies_html'] ?? '');
$has_policies = $policies !== '';
?>
<div class="emp-page emp-page-policies">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Company</p>
            <h2 class="emp-page-hero-title">Company policies</h2>
            <p>Employee handbook, workplace guidelines, and official company policies published by HR.</p>
        </div>
        <span class="emp-hero-stat-pill"><?php echo $has_policies ? 'Published' : 'Not available'; ?></span>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
            <div><span class="emp-dash-stat-label">Handbook</span><strong class="emp-dash-stat-value"><?php echo $has_policies ? 'Live' : '—'; ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <div><span class="emp-dash-stat-label">Compliance</span><strong class="emp-dash-stat-value">HR</strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-paid">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
            <div><span class="emp-dash-stat-label">Questions?</span><strong class="emp-dash-stat-value emp-dash-stat-value-sm"><a href="helpdesk.php">Helpdesk</a></strong></div>
        </div>
    </div>

    <section class="emp-card emp-policies-card">
        <header class="emp-card-toolbar emp-reg-toolbar">
            <div>
                <h3 class="emp-card-title">Policy handbook</h3>
                <p class="emp-reg-toolbar-sub">Read the latest policies shared by your organisation.</p>
            </div>
        </header>
        <?php if (!$has_policies): ?>
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
                <strong>Policies not published yet</strong>
                <p>Contact HR for the employee handbook or raise a ticket on the <a href="helpdesk.php">helpdesk</a>.</p>
            </div>
        <?php else: ?>
            <div class="emp-policies-content"><?php echo $policies; ?></div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
