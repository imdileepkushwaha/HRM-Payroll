<?php
require_once 'includes/admin_page_init.php';
admin_page_init('employees');
require_once 'includes/employee_portal_features_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$filter = $_GET['status'] ?? '';
$tickets = get_admin_helpdesk_tickets($conn, $branch_id, $filter !== '' ? $filter : null);

$open_count = 0;
foreach (get_admin_helpdesk_tickets($conn, $branch_id, 'open', 500) as $_t) {
    $open_count++;
}
?>
<div class="hrm-page helpdesk-admin-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">People</p>
            <h2>Employee helpdesk</h2>
            <p>Reply to support tickets from the employee portal<?php echo $branch_id !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?>.</p>
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
            <div><strong><?php echo $open_count; ?></strong><span>Open tickets</span></div>
        </div>
    </div>

    <div class="helpdesk-admin-filters">
        <a href="helpdesk.php" class="btn btn-sm <?php echo $filter === '' ? 'btn-primary' : 'btn-outline'; ?>">All</a>
        <a href="helpdesk.php?status=open" class="btn btn-sm <?php echo $filter === 'open' ? 'btn-primary' : 'btn-outline'; ?>">Open</a>
        <a href="helpdesk.php?status=answered" class="btn btn-sm <?php echo $filter === 'answered' ? 'btn-primary' : 'btn-outline'; ?>">Answered</a>
        <a href="helpdesk.php?status=closed" class="btn btn-sm <?php echo $filter === 'closed' ? 'btn-primary' : 'btn-outline'; ?>">Closed</a>
    </div>

    <?php if ($tickets === []): ?>
        <p class="text-muted">No helpdesk tickets yet.</p>
    <?php else: ?>
        <div class="helpdesk-admin-list">
            <?php foreach ($tickets as $t): ?>
            <article class="helpdesk-admin-card emp-ticket-card">
                <header class="helpdesk-admin-card-head">
                    <div>
                        <strong><?php echo htmlspecialchars($t['subject']); ?></strong>
                        <span class="emp-muted"><?php echo htmlspecialchars($t['emp_name']); ?> · <?php echo htmlspecialchars($t['emp_id']); ?> · <?php echo htmlspecialchars($t['category']); ?></span>
                    </div>
                    <span class="emp-badge"><?php echo htmlspecialchars($t['status']); ?></span>
                </header>
                <p><?php echo nl2br(htmlspecialchars($t['body'])); ?></p>
                <time class="emp-muted"><?php echo date('d M Y, h:i A', strtotime($t['created_at'])); ?></time>
                <?php if (!empty($t['admin_reply'])): ?>
                    <div class="emp-ticket-reply"><strong>Previous reply:</strong> <?php echo nl2br(htmlspecialchars($t['admin_reply'])); ?></div>
                <?php endif; ?>
                <?php if ($t['status'] !== 'closed'): ?>
                <form method="POST" action="helpdesk_save.php" class="helpdesk-admin-reply-form stack-form">
                    <?php require_once 'includes/csrf_helper.php'; echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo (int) $t['id']; ?>">
                    <div class="form-group">
                        <label>Reply to employee</label>
                        <textarea name="admin_reply" rows="3" required placeholder="Your response…"></textarea>
                    </div>
                    <div class="form-row">
                        <label>Status after reply
                            <select name="status">
                                <option value="answered">Answered</option>
                                <option value="closed">Closed</option>
                                <option value="open">Keep open</option>
                            </select>
                        </label>
                        <button type="submit" class="btn btn-primary">Send reply</button>
                    </div>
                </form>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
