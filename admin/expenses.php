<?php
require_once 'includes/admin_page_init.php';
admin_page_init('expenses');
require_once 'includes/hrm_modules_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$filter = $_GET['status'] ?? '';
$all_claims = get_expense_claims($conn, $branch_id, null);
$claims = $filter !== ''
    ? array_values(array_filter($all_claims, static fn($c) => ($c['request_status'] ?? '') === $filter))
    : $all_claims;

$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$pending_amount = 0.0;
$approved_amount = 0.0;
foreach ($all_claims as $c) {
    $st = $c['request_status'] ?? '';
    $amt = (float) ($c['amount'] ?? 0);
    if ($st === 'pending') {
        $pending_count++;
        $pending_amount += $amt;
    } elseif ($st === 'approved') {
        $approved_count++;
        $approved_amount += $amt;
    } elseif ($st === 'rejected') {
        $rejected_count++;
    }
}

$status_labels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
$status_class = ['pending' => 'expense-status-pending', 'approved' => 'expense-status-approved', 'rejected' => 'expense-status-rejected'];
?>
<div class="hrm-page expenses-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Finance</p>
            <h2>Expense claims</h2>
            <p>Review and approve employee reimbursement requests<?php echo $branch_id !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?>.</p>
        </div>
        <div class="page-header-actions">
            <a href="approvals.php" class="btn btn-outline">All approvals</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip warn">
            <span class="status-dot"></span>
            <div><strong><?php echo $pending_count; ?></strong><span>Pending approval</span></div>
        </div>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div><strong><?php echo format_money($pending_amount); ?></strong><span>Pending amount</span></div>
        </div>
        <div class="settings-status-chip ok">
            <span class="status-dot"></span>
            <div><strong><?php echo $approved_count; ?></strong><span>Approved</span></div>
        </div>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div><strong><?php echo count($all_claims); ?></strong><span>Total claims</span></div>
        </div>
    </div>

    <?php if ($pending_count > 0): ?>
        <div class="hrm-callout hrm-callout-warn expenses-pending-callout">
            <strong><?php echo $pending_count; ?> claim<?php echo $pending_count === 1 ? '' : 's'; ?> awaiting review</strong>
            <span>Total <?php echo format_money($pending_amount); ?> in pending reimbursements.</span>
        </div>
    <?php endif; ?>

    <section class="panel panel-elevated expenses-panel">
        <div class="panel-header masters-panel-head">
            <div class="panel-title-group">
                <h3>Claims</h3>
                <span class="panel-badge" id="expenseClaimCount"><?php echo count($claims); ?> listed</span>
            </div>
            <div class="expenses-filter-pills announce-filter-pills">
                <a href="expenses.php" class="announce-filter-pill <?php echo $filter === '' ? 'is-active' : ''; ?>">All</a>
                <a href="expenses.php?status=pending" class="announce-filter-pill <?php echo $filter === 'pending' ? 'is-active' : ''; ?>">Pending</a>
                <a href="expenses.php?status=approved" class="announce-filter-pill <?php echo $filter === 'approved' ? 'is-active' : ''; ?>">Approved</a>
                <a href="expenses.php?status=rejected" class="announce-filter-pill <?php echo $filter === 'rejected' ? 'is-active' : ''; ?>">Rejected</a>
            </div>
            <?php if ($claims !== []): ?>
            <div class="masters-search-wrap">
                <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" id="expenseClaimSearch" placeholder="Search employee, category…" autocomplete="off" aria-label="Search claims">
                <button type="button" class="masters-search-clear" id="expenseClaimClear" hidden aria-label="Clear">&times;</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="panel-body padded">
            <?php if ($claims === []): ?>
                <div class="masters-empty expenses-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="15" x2="10" y2="15"/></svg>
                    <h4><?php echo $filter !== '' ? 'No ' . htmlspecialchars($status_labels[$filter] ?? $filter) . ' claims' : 'No expense claims yet'; ?></h4>
                    <p><?php echo $filter !== '' ? 'Try another filter or check back when employees submit claims.' : 'Employees can submit reimbursement requests from the employee portal.'; ?></p>
                </div>
            <?php else: ?>
                <ul class="expense-claim-list" id="expenseClaimList">
                    <?php foreach ($claims as $c):
                        $st = $c['request_status'] ?? 'pending';
                        $initial = strtoupper(substr($c['emp_name'] ?? 'E', 0, 1));
                        $desc = trim($c['description'] ?? '');
                        $search = strtolower(
                            ($c['emp_name'] ?? '') . ' ' . ($c['emp_id'] ?? '') . ' ' . ($c['category'] ?? '') . ' ' . $desc
                        );
                        ?>
                    <li class="expense-claim-card" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="expense-claim-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></div>
                        <div class="expense-claim-body">
                            <div class="expense-claim-top">
                                <div class="expense-claim-identity">
                                    <a href="employee_view.php?emp_id=<?php echo rawurlencode($c['emp_id']); ?>" class="expense-claim-name"><?php echo htmlspecialchars($c['emp_name']); ?></a>
                                    <span class="expense-claim-emp-id"><?php echo htmlspecialchars($c['emp_id']); ?></span>
                                </div>
                                <span class="expense-status-badge <?php echo $status_class[$st] ?? ''; ?>"><?php echo htmlspecialchars($status_labels[$st] ?? $st); ?></span>
                            </div>
                            <div class="expense-claim-meta">
                                <span class="expense-category-pill"><?php echo htmlspecialchars($c['category']); ?></span>
                                <span class="expense-claim-date"><?php echo date('d M Y', strtotime($c['claim_date'])); ?></span>
                                <?php if (!empty($c['receipt_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($c['receipt_path']); ?>" class="expense-receipt-link" target="_blank" rel="noopener">
                                        <?php echo htmlspecialchars($c['receipt_filename'] ?: 'View receipt'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if ($desc !== ''): ?>
                                <p class="expense-claim-desc"><?php echo htmlspecialchars($desc); ?></p>
                            <?php endif; ?>
                            <?php if ($st !== 'pending' && !empty($c['reviewed_at'])): ?>
                                <p class="expense-claim-reviewed">
                                    Reviewed <?php echo date('d M Y', strtotime($c['reviewed_at'])); ?>
                                    <?php if (!empty($c['reviewed_by'])): ?> by <?php echo htmlspecialchars($c['reviewed_by']); ?><?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="expense-claim-side">
                            <strong class="expense-claim-amount"><?php echo format_money((float) $c['amount']); ?></strong>
                            <?php if ($st === 'pending'): ?>
                            <form method="POST" action="expense_save.php" class="expense-claim-actions">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="expense_action" value="review">
                                <input type="hidden" name="claim_id" value="<?php echo (int) $c['id']; ?>">
                                <input type="text" name="review_note" placeholder="Note (optional)" class="expense-review-note" aria-label="Review note">
                                <button type="submit" name="decision" value="approve" class="btn btn-sm btn-header">Approve</button>
                                <button type="submit" name="decision" value="reject" class="btn btn-sm btn-outline">Reject</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="masters-empty masters-search-empty" id="expenseClaimNoMatch" hidden>
                    <h4>No matches</h4>
                    <p>Try a different employee name or category.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php if ($claims !== []): ?>
<script>
(function () {
    var input = document.getElementById('expenseClaimSearch');
    var clearBtn = document.getElementById('expenseClaimClear');
    var badge = document.getElementById('expenseClaimCount');
    var noMatch = document.getElementById('expenseClaimNoMatch');
    var list = document.getElementById('expenseClaimList');
    if (!input || !list) return;

    var cards = list.querySelectorAll('.expense-claim-card');
    var total = cards.length;

    function apply() {
        var q = input.value.trim().toLowerCase();
        var visible = 0;
        cards.forEach(function (card) {
            var hay = card.getAttribute('data-search') || '';
            var match = q === '' || hay.indexOf(q) !== -1;
            card.hidden = !match;
            if (match) visible++;
        });
        if (badge) badge.textContent = visible + ' listed';
        if (noMatch) noMatch.hidden = visible > 0;
        list.hidden = visible === 0 && q !== '';
        if (clearBtn) clearBtn.hidden = q === '';
    }

    input.addEventListener('input', apply);
    if (clearBtn) clearBtn.addEventListener('click', function () { input.value = ''; input.focus(); apply(); });
})();
</script>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
