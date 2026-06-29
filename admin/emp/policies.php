<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = get_all_settings($conn);
$policies = trim($settings['company_policies_html'] ?? '');
?>
<div class="emp-page">
    <header class="emp-page-head"><h1>Company policies</h1><p>Handbook and workplace policies.</p></header>
    <div class="emp-panel emp-policies-body">
        <?php if ($policies === ''): ?>
            <p class="emp-muted">Policies have not been published yet. Contact HR for the employee handbook.</p>
        <?php else: ?>
            <div class="emp-policies-content"><?php echo $policies; ?></div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
