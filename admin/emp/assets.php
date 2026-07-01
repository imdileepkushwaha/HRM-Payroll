<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$assets = get_employee_assigned_assets($conn, $employee['emp_id']);
$categories = [];
foreach ($assets as $a) {
    $cat = trim($a['category'] ?? '') ?: 'General';
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}
$asset_icon = static function (string $category): string {
    $c = strtolower($category);
    if (str_contains($c, 'laptop') || str_contains($c, 'computer')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/></svg>';
    }
    if (str_contains($c, 'phone') || str_contains($c, 'mobile')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>';
    }
    if (str_contains($c, 'vehicle') || str_contains($c, 'car')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17h10M5 11l1-4h12l1 4"/><rect x="3" y="11" width="18" height="8" rx="2"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>';
};
?>
<div class="emp-page emp-page-assets">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Resources</p>
            <h2 class="emp-page-hero-title">My assets</h2>
            <p>Company equipment currently assigned to you. Return items to IT or admin when you leave the organisation.</p>
        </div>
        <span class="emp-hero-stat-pill"><?php echo count($assets); ?> assigned</span>
    </div>

    <?php if ($assets !== []): ?>
    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/></svg></div>
            <div><span class="emp-dash-stat-label">Total assets</span><strong class="emp-dash-stat-value"><?php echo count($assets); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span class="emp-dash-stat-label">Categories</span><strong class="emp-dash-stat-value"><?php echo count($categories); ?></strong></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-paid">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><span class="emp-dash-stat-label">Latest assigned</span><strong class="emp-dash-stat-value emp-dash-stat-value-sm"><?php echo date('d M Y', strtotime($assets[0]['assigned_at'])); ?></strong></div>
        </div>
    </div>
    <?php endif; ?>

    <section class="emp-card emp-assets-card">
        <header class="emp-card-toolbar emp-reg-toolbar">
            <div><h3 class="emp-card-title">Assigned equipment</h3><p class="emp-reg-toolbar-sub">Tag, serial number and assignment date for each item.</p></div>
        </header>

        <?php if ($assets === []): ?>
            <div class="emp-reg-empty">
                <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></span>
                <strong>No assets assigned</strong>
                <p>When HR or admin assigns laptops, phones or other equipment, they will appear here.</p>
            </div>
        <?php else: ?>
            <div class="emp-assets-grid">
                <?php foreach ($assets as $a):
                    $cat = htmlspecialchars($a['category'] ?? 'General');
                ?>
                <article class="emp-asset-tile">
                    <span class="emp-asset-tile-icon" aria-hidden="true"><?php echo $asset_icon($a['category'] ?? 'General'); ?></span>
                    <div class="emp-asset-tile-body">
                        <h4><?php echo htmlspecialchars($a['name']); ?></h4>
                        <span class="emp-asset-tag"><?php echo htmlspecialchars($a['asset_tag']); ?></span>
                        <div class="emp-asset-meta">
                            <span class="emp-asset-cat"><?php echo $cat; ?></span>
                            <?php if (!empty($a['serial_no'])): ?>
                                <span>SN <?php echo htmlspecialchars($a['serial_no']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($a['condition_notes'])): ?>
                            <p class="emp-asset-note"><?php echo htmlspecialchars($a['condition_notes']); ?></p>
                        <?php endif; ?>
                        <time>Assigned <?php echo date('d M Y', strtotime($a['assigned_at'])); ?></time>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
