<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/hrm_modules_helper.php';

$claims = get_expense_claims($conn, (int) $employee['branch_id'], null, 50);
$claims = array_values(array_filter($claims, static fn($c) => $c['emp_id'] === $employee['emp_id']));
$categories = ['Travel', 'Food', 'Fuel', 'Office supplies', 'Client entertainment', 'General'];
?>
<div class="emp-page">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <header class="emp-page-head">
        <h1>Expense claims</h1>
        <p>Submit reimbursement requests for admin approval.</p>
    </header>

    <div class="emp-panel">
        <h2>Submit claim</h2>
        <form method="POST" action="expense_save.php" enctype="multipart/form-data" class="emp-form">
            <?php echo csrf_field(); ?>
            <div class="emp-form-row">
                <label>Date<input type="date" name="claim_date" value="<?php echo date('Y-m-d'); ?>" required></label>
                <label>Category
                    <select name="category" required>
                        <?php foreach ($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Amount<input type="number" name="amount" step="0.01" min="0.01" required></label>
            </div>
            <label>Description<textarea name="description" rows="2"></textarea></label>
            <label>Receipt (optional)<input type="file" name="receipt" accept="image/*,.pdf"></label>
            <button type="submit" class="emp-btn emp-btn-primary">Submit for approval</button>
        </form>
    </div>

    <div class="emp-panel">
        <h2>My claims</h2>
        <?php if ($claims === []): ?>
            <p class="emp-muted">No expense claims yet.</p>
        <?php else: ?>
        <ul class="emp-claim-list">
            <?php foreach ($claims as $c): ?>
                <li>
                    <strong><?php echo format_money((float)$c['amount']); ?></strong> — <?php echo htmlspecialchars($c['category']); ?>
                    <span class="emp-muted"><?php echo date('d M Y', strtotime($c['claim_date'])); ?></span>
                    <span class="emp-badge emp-badge-<?php echo $c['request_status']; ?>"><?php echo htmlspecialchars($c['request_status']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
