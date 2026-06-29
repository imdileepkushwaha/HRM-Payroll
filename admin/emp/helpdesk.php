<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_portal_features_helper.php';

$tickets = get_employee_helpdesk_tickets($conn, $employee['emp_id']);
$categories = ['General', 'Payroll', 'Leave', 'IT support', 'HR policy', 'Other'];
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>
    <header class="emp-page-head"><h1>HR helpdesk</h1><p>Raise a support ticket for HR or IT.</p></header>
    <div class="emp-panel">
        <form method="POST" action="helpdesk_save.php" class="emp-form">
            <?php echo csrf_field(); ?>
            <label>Category <select name="category"><?php foreach ($categories as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></label>
            <label>Subject <input type="text" name="subject" required maxlength="200"></label>
            <label>Description <textarea name="body" rows="4" required></textarea></label>
            <button type="submit" class="emp-btn emp-btn-primary">Submit ticket</button>
        </form>
    </div>
    <div class="emp-panel">
        <h2>My tickets</h2>
        <?php if ($tickets === []): ?><p class="emp-muted">No tickets yet.</p><?php else: ?>
        <ul class="emp-ticket-list"><?php foreach ($tickets as $t): ?>
            <li class="emp-ticket-card">
                <div class="emp-ticket-head"><strong><?php echo htmlspecialchars($t['subject']); ?></strong> <span class="emp-badge"><?php echo htmlspecialchars($t['status']); ?></span></div>
                <p><?php echo nl2br(htmlspecialchars($t['body'])); ?></p>
                <?php if (!empty($t['admin_reply'])): ?><div class="emp-ticket-reply"><strong>HR reply:</strong> <?php echo nl2br(htmlspecialchars($t['admin_reply'])); ?></div><?php endif; ?>
                <span class="emp-muted"><?php echo date('d M Y H:i', strtotime($t['created_at'])); ?></span>
            </li>
        <?php endforeach; ?></ul>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
