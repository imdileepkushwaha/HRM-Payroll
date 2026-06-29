<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/hrm_modules_helper.php';
require_once __DIR__ . '/../includes/salary_helper.php';

$claims = get_expense_claims($conn, (int) $employee['branch_id'], null, 50);
$claims = array_values(array_filter($claims, static fn($c) => $c['emp_id'] === $employee['emp_id']));
$categories = ['Travel', 'Food', 'Fuel', 'Office supplies', 'Client entertainment', 'General'];

$pending_count = 0;
$approved_count = 0;
$pending_amount = 0.0;
$approved_amount = 0.0;
foreach ($claims as $c) {
    $st = $c['request_status'] ?? '';
    $amt = (float) ($c['amount'] ?? 0);
    if ($st === 'pending') {
        $pending_count++;
        $pending_amount += $amt;
    } elseif ($st === 'approved') {
        $approved_count++;
        $approved_amount += $amt;
    }
}
?>
<div class="emp-page emp-page-expenses">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Finance</p>
            <h2 class="emp-page-hero-title">Expense claims</h2>
            <p>Submit reimbursement requests with receipts. Branch admin reviews and approves payouts.</p>
        </div>
        <?php if ($pending_count > 0): ?>
            <span class="emp-page-lock-badge"><?php echo $pending_count; ?> pending</span>
        <?php endif; ?>
    </div>

    <div class="emp-page-stats emp-page-stats-3">
        <div class="emp-dash-stat emp-dash-stat-quota">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
            <div><span class="emp-dash-stat-label">Pending</span><strong class="emp-dash-stat-value"><?php echo format_money($pending_amount); ?></strong><span class="emp-dash-stat-hint"><?php echo $pending_count; ?> claim<?php echo $pending_count === 1 ? '' : 's'; ?></span></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-present">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><span class="emp-dash-stat-label">Approved</span><strong class="emp-dash-stat-value"><?php echo format_money($approved_amount); ?></strong><span class="emp-dash-stat-hint"><?php echo $approved_count; ?> claim<?php echo $approved_count === 1 ? '' : 's'; ?></span></div>
        </div>
        <div class="emp-dash-stat emp-dash-stat-paid">
            <div class="emp-dash-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
            <div><span class="emp-dash-stat-label">Total submitted</span><strong class="emp-dash-stat-value"><?php echo count($claims); ?></strong><span class="emp-dash-stat-hint">All time</span></div>
        </div>
    </div>

    <div class="emp-section-grid emp-section-grid-reg">
        <div class="emp-reg-main">
            <section class="emp-card emp-reg-history-card">
                <header class="emp-card-toolbar emp-reg-toolbar">
                    <div><h3 class="emp-card-title">My claims</h3><p class="emp-reg-toolbar-sub">Status, admin notes and receipt downloads.</p></div>
                </header>
                <?php if ($claims === []): ?>
                    <div class="emp-reg-empty">
                        <span class="emp-reg-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                        <strong>No expense claims yet</strong>
                        <p>Submit your first reimbursement request using the form on the right.</p>
                    </div>
                <?php else: ?>
                    <ul class="emp-claim-list-detail emp-exp-claim-list">
                        <?php foreach ($claims as $c): ?>
                        <li class="emp-claim-card">
                            <div class="emp-claim-card-head">
                                <strong><?php echo format_money((float) $c['amount']); ?></strong>
                                <span class="emp-req-status emp-req-<?php echo htmlspecialchars($c['request_status']); ?>"><?php echo htmlspecialchars(ucfirst($c['request_status'])); ?></span>
                            </div>
                            <div class="emp-claim-card-meta">
                                <span><?php echo htmlspecialchars($c['category']); ?></span>
                                <span class="emp-muted"><?php echo date('d M Y', strtotime($c['claim_date'])); ?></span>
                            </div>
                            <?php if (!empty($c['description'])): ?><p class="emp-claim-desc"><?php echo nl2br(htmlspecialchars($c['description'])); ?></p><?php endif; ?>
                            <?php if (!empty($c['review_note'])): ?><p class="emp-claim-review"><strong>Admin:</strong> <?php echo nl2br(htmlspecialchars($c['review_note'])); ?></p><?php endif; ?>
                            <?php if (!empty($c['receipt_path'])): ?>
                                <a href="expense_receipt.php?id=<?php echo (int) $c['id']; ?>" class="emp-claim-receipt-link" target="_blank" rel="noopener">View receipt</a>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <aside class="emp-request-panel emp-request-panel-att">
            <div class="emp-request-panel-header">
                <span class="emp-request-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                <div><h3>Submit claim</h3><p>Attach a receipt when possible — it speeds up approval.</p></div>
            </div>
            <div class="emp-request-panel-body">
                <form method="POST" action="expense_save.php" enctype="multipart/form-data" class="emp-request-form">
                    <?php echo csrf_field(); ?>
                    <div class="emp-request-fields">
                        <div class="form-group">
                            <label for="empExpDate">Claim date <span class="req">*</span></label>
                            <input type="date" id="empExpDate" name="claim_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="empExpCat">Category <span class="req">*</span></label>
                            <select id="empExpCat" name="category" required>
                                <?php foreach ($categories as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="empExpAmt">Amount (₹) <span class="req">*</span></label>
                            <input type="number" id="empExpAmt" name="amount" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="empExpDesc">Description</label>
                            <textarea id="empExpDesc" name="description" rows="3" placeholder="What was this expense for?"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="empExpReceipt">Receipt (optional)</label>
                            <input type="file" id="empExpReceipt" name="receipt" accept="image/*,.pdf">
                            <span class="form-hint">JPEG, PNG, WebP or PDF — max 5 MB</span>
                        </div>
                    </div>
                    <div class="emp-request-submit">
                        <button type="submit" class="btn btn-block">Submit for approval</button>
                    </div>
                </form>
            </div>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
