<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';
require_once __DIR__ . '/../includes/employee_document_helper.php';

$letter_types = [
    'office_experience' => 'Experience letter',
    'office_relieving' => 'Relieving letter',
    'office_noc' => 'NOC letter',
    'office_form16' => 'Form 16 request',
];
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>
    <header class="emp-page-head"><h1>HR letters</h1><p>Request official letters from HR.</p></header>
    <div class="emp-panel">
        <form method="POST" action="hr_letter_save.php" class="emp-form">
            <?php echo csrf_field(); ?>
            <label>Letter type
                <select name="doc_type" required><?php foreach ($letter_types as $k => $label): ?><option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select>
            </label>
            <label>Note <textarea name="employee_note" rows="3" placeholder="Purpose or any details HR should know"></textarea></label>
            <button type="submit" class="emp-btn emp-btn-primary">Request letter</button>
        </form>
        <p class="emp-muted" style="margin-top:1rem"><a href="documents.php">View document requests &amp; uploads →</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
