<?php
require 'includes/header.php';
require 'config.php';
require_once 'includes/attendance_helper.php';
require_once 'includes/employee_document_helper.php';

$branch_filter = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_filter);
$pending_profile = get_pending_profile_requests($conn, $branch_filter);
$pending_documents = get_pending_document_requests($conn, $branch_filter);
$pending_attendance = get_pending_attendance_requests($conn, $branch_filter);
$pending_leave = get_pending_leave_requests($conn, $branch_filter);
$pending_total = count($pending_profile) + count($pending_documents) + count($pending_attendance) + count($pending_leave);
$leave_types_map = get_leave_types($conn);

$employee_cache = [];
function approvals_employee($conn, $emp_id, &$cache)
{
    if (!isset($cache[$emp_id])) {
        $stmt = $conn->prepare('SELECT * FROM employees WHERE emp_id = ?');
        $stmt->bind_param('s', $emp_id);
        $stmt->execute();
        $cache[$emp_id] = $stmt->get_result()->fetch_assoc() ?: [];
    }
    return $cache[$emp_id];
}
?>
<div class="approvals-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Employee portal</p>
            <h2>Approval requests</h2>
            <p>Review profile updates, document uploads, leave applications and manual attendance submitted by employees<?php echo $branch_filter !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?>.</p>
        </div>
    </div>

    <?php if ($branch_filter === null): ?>
        <div class="alert alert-page approvals-branch-alert">
            <strong>Select a branch.</strong> Choose a branch from the top bar to review employee requests for that location.
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="approvals-stats">
        <div class="approvals-stat <?php echo $pending_total > 0 ? 'approvals-stat-warn' : 'approvals-stat-ok'; ?>">
            <span class="approvals-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <span class="approvals-stat-label">Total pending</span>
                <strong class="approvals-stat-value"><?php echo $pending_total; ?></strong>
                <span class="approvals-stat-hint"><?php echo $pending_total > 0 ? 'Awaiting your decision' : 'All caught up'; ?></span>
            </div>
        </div>
        <div class="approvals-stat approvals-stat-profile">
            <span class="approvals-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <div>
                <span class="approvals-stat-label">Profile updates</span>
                <strong class="approvals-stat-value"><?php echo count($pending_profile); ?></strong>
                <span class="approvals-stat-hint">Contact &amp; bank changes</span>
            </div>
        </div>
        <div class="approvals-stat approvals-stat-documents">
            <span class="approvals-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
            <div>
                <span class="approvals-stat-label">Documents</span>
                <strong class="approvals-stat-value"><?php echo count($pending_documents); ?></strong>
                <span class="approvals-stat-hint">Aadhar, PAN &amp; more</span>
            </div>
        </div>
        <div class="approvals-stat approvals-stat-leave">
            <span class="approvals-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
            </span>
            <div>
                <span class="approvals-stat-label">Leave</span>
                <strong class="approvals-stat-value"><?php echo count($pending_leave); ?></strong>
                <span class="approvals-stat-hint">Leave applications</span>
            </div>
        </div>
        <div class="approvals-stat approvals-stat-attendance">
            <span class="approvals-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div>
                <span class="approvals-stat-label">Attendance</span>
                <strong class="approvals-stat-value"><?php echo count($pending_attendance); ?></strong>
                <span class="approvals-stat-hint">Manual mark requests</span>
            </div>
        </div>
    </div>

    <div class="approvals-layout">
        <section class="panel panel-elevated approvals-panel">
            <div class="panel-header">
                <div class="panel-title-group approvals-panel-head">
                    <span class="approvals-panel-icon approvals-panel-icon-profile" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <div>
                        <h3>Profile update requests</h3>
                        <p class="approvals-panel-desc">Employees request changes to contact and bank details.</p>
                    </div>
                </div>
                <span class="panel-badge"><?php echo count($pending_profile); ?> pending</span>
            </div>
            <div class="panel-body approvals-panel-body">
                <?php if ($pending_profile === []): ?>
                    <div class="empty-state compact approvals-empty">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <h4>No profile requests</h4>
                        <p>When employees submit profile updates, they will appear here for review.</p>
                    </div>
                <?php else: ?>
                    <div class="approvals-card-list">
                        <?php foreach ($pending_profile as $req): ?>
                            <?php
                            $emp = approvals_employee($conn, $req['emp_id'], $employee_cache);
                            $diffs = profile_request_field_diffs($emp, $req);
                            $emp_initial = strtoupper(substr($req['employee_name'], 0, 1));
                            ?>
                            <article class="approval-card approval-card-profile">
                                <header class="approval-card-top">
                                    <div class="approval-card-employee">
                                        <span class="approval-card-avatar" aria-hidden="true"><?php echo htmlspecialchars($emp_initial); ?></span>
                                        <div>
                                            <a href="employee_view.php?emp_id=<?php echo urlencode($req['emp_id']); ?>" class="approval-card-name"><?php echo htmlspecialchars($req['employee_name']); ?></a>
                                            <span class="approval-card-meta"><?php echo htmlspecialchars($req['emp_id']); ?> · <?php echo htmlspecialchars(get_branch_label($conn, (int) $req['branch_id'])); ?></span>
                                        </div>
                                    </div>
                                    <time class="approval-card-time" datetime="<?php echo htmlspecialchars($req['created_at']); ?>"><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></time>
                                </header>

                                <?php if ($diffs !== []): ?>
                                    <div class="approval-diff-list">
                                        <?php foreach ($diffs as $diff): ?>
                                            <div class="approval-diff-item">
                                                <span class="approval-diff-label"><?php echo htmlspecialchars($diff['label']); ?></span>
                                                <div class="approval-diff-values">
                                                    <span class="approval-diff-old" title="Current value"><?php echo htmlspecialchars($diff['old']); ?></span>
                                                    <span class="approval-diff-arrow" aria-hidden="true">→</span>
                                                    <span class="approval-diff-new" title="Requested value"><?php echo htmlspecialchars($diff['new']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($req['employee_note'])): ?>
                                    <div class="approval-employee-note">
                                        <strong>Employee note</strong>
                                        <p><?php echo htmlspecialchars($req['employee_note']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <footer class="approval-card-footer">
                                    <form method="POST" action="approval_save.php" class="approval-actions">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="request_type" value="profile">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                                        <label class="approval-note-field">
                                            <span class="sr-only">Note to employee</span>
                                            <input type="text" name="review_note" placeholder="Optional note to employee" class="approval-note-input">
                                        </label>
                                        <div class="approval-action-buttons">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-outline btn-sm btn-danger-outline" onclick="return confirm('Reject this profile request?');">Reject</button>
                                        </div>
                                    </form>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel panel-elevated approvals-panel">
            <div class="panel-header">
                <div class="panel-title-group approvals-panel-head">
                    <span class="approvals-panel-icon approvals-panel-icon-documents" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </span>
                    <div>
                        <h3>Document uploads</h3>
                        <p class="approvals-panel-desc">Review ID proofs, marksheets &amp; office documents.</p>
                    </div>
                </div>
                <span class="panel-badge"><?php echo count($pending_documents); ?> pending</span>
            </div>
            <div class="panel-body approvals-panel-body">
                <?php if ($pending_documents === []): ?>
                    <div class="empty-state compact approvals-empty">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <h4>No document uploads</h4>
                        <p>When employees submit documents, they will appear here for review.</p>
                    </div>
                <?php else: ?>
                    <div class="approvals-card-list">
                        <?php foreach ($pending_documents as $req): ?>
                            <?php
                            $emp_initial = strtoupper(substr($req['employee_name'], 0, 1));
                            $doc_label = $req['doc_label'] ?: employee_document_type_label($req['doc_type']);
                            $doc_cat = employee_document_category_key($req['doc_type']);
                            $doc_cat_label = employee_document_category_label($req['doc_type']);
                            $file_ext = employee_document_file_extension($req['original_filename'], $req['mime_type'] ?? '');
                            ?>
                            <article class="approval-card approval-card-document approval-card-document-<?php echo htmlspecialchars($doc_cat); ?>">
                                <header class="approval-card-top">
                                    <div class="approval-card-employee">
                                        <span class="approval-card-avatar" aria-hidden="true"><?php echo htmlspecialchars($emp_initial); ?></span>
                                        <div>
                                            <a href="employee_view.php?emp_id=<?php echo urlencode($req['emp_id']); ?>" class="approval-card-name"><?php echo htmlspecialchars($req['employee_name']); ?></a>
                                            <span class="approval-card-meta"><?php echo htmlspecialchars($req['emp_id']); ?> · <?php echo htmlspecialchars(get_branch_label($conn, (int) $req['branch_id'])); ?></span>
                                        </div>
                                    </div>
                                    <time class="approval-card-time" datetime="<?php echo htmlspecialchars($req['created_at']); ?>"><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></time>
                                </header>

                                <div class="approval-doc-preview">
                                    <div class="approval-doc-file-icon approval-doc-file-icon-<?php echo htmlspecialchars(strtolower($file_ext)); ?>" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <span class="approval-doc-file-ext"><?php echo htmlspecialchars($file_ext); ?></span>
                                    </div>
                                    <div class="approval-doc-meta">
                                        <div class="approval-doc-meta-top">
                                            <span class="approval-doc-type approval-doc-type-<?php echo htmlspecialchars($doc_cat); ?>"><?php echo htmlspecialchars(employee_document_type_label($req['doc_type'])); ?></span>
                                            <span class="approval-doc-category"><?php echo htmlspecialchars($doc_cat_label); ?></span>
                                        </div>
                                        <strong><?php echo htmlspecialchars($doc_label); ?></strong>
                                        <span class="approval-doc-fileinfo"><?php echo htmlspecialchars($req['original_filename']); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $req['file_size'])); ?></span>
                                    </div>
                                    <a href="employee_document_download.php?request_id=<?php echo (int) $req['id']; ?>" class="btn btn-outline btn-sm approval-doc-preview-btn" target="_blank" rel="noopener">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        Preview file
                                    </a>
                                </div>

                                <?php if (!empty($req['employee_note'])): ?>
                                    <div class="approval-employee-note">
                                        <strong>Employee note</strong>
                                        <p><?php echo htmlspecialchars($req['employee_note']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <footer class="approval-card-footer">
                                    <form method="POST" action="approval_save.php" class="approval-actions">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="request_type" value="document">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                                        <label class="approval-note-field">
                                            <span class="sr-only">Note to employee</span>
                                            <input type="text" name="review_note" placeholder="Optional note to employee" class="approval-note-input">
                                        </label>
                                        <div class="approval-action-buttons">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-outline btn-sm btn-danger-outline" onclick="return confirm('Reject this document upload?');">Reject</button>
                                        </div>
                                    </form>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel panel-elevated approvals-panel">
            <div class="panel-header">
                <div class="panel-title-group approvals-panel-head">
                    <span class="approvals-panel-icon approvals-panel-icon-attendance" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <div>
                        <h3>Attendance requests</h3>
                        <p class="approvals-panel-desc">Manual attendance marks submitted by employees.</p>
                    </div>
                </div>
                <span class="panel-badge"><?php echo count($pending_attendance); ?> pending</span>
            </div>
            <div class="panel-body approvals-panel-body">
                <?php if ($pending_attendance === []): ?>
                    <div class="empty-state compact approvals-empty">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <h4>No attendance requests</h4>
                        <p>When employees request manual attendance, they will appear here for review.</p>
                    </div>
                <?php else: ?>
                    <div class="approvals-card-list">
                        <?php foreach ($pending_attendance as $req): ?>
                            <?php
                            $emp_initial = strtoupper(substr($req['employee_name'], 0, 1));
                            $att_code = normalize_attendance_status_code($req['status']);
                            $att_class = attendance_code_css_class($att_code);
                            $att_label = attendance_code_label($att_code);
                            if ($att_code === '') {
                                $att_label = ucfirst(trim((string) $req['status']));
                            }
                            ?>
                            <article class="approval-card approval-card-attendance">
                                <header class="approval-card-top">
                                    <div class="approval-card-employee">
                                        <span class="approval-card-avatar" aria-hidden="true"><?php echo htmlspecialchars($emp_initial); ?></span>
                                        <div>
                                            <a href="employee_view.php?emp_id=<?php echo urlencode($req['emp_id']); ?>" class="approval-card-name"><?php echo htmlspecialchars($req['employee_name']); ?></a>
                                            <span class="approval-card-meta"><?php echo htmlspecialchars($req['emp_id']); ?> · <?php echo htmlspecialchars(get_branch_label($conn, (int) $req['branch_id'])); ?></span>
                                        </div>
                                    </div>
                                    <time class="approval-card-time" datetime="<?php echo htmlspecialchars($req['created_at']); ?>"><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?></time>
                                </header>

                                <div class="approval-att-box">
                                    <div class="approval-att-date">
                                        <span class="approval-att-date-label">Attendance date</span>
                                        <strong><?php echo date('l, d M Y', strtotime($req['attendance_date'])); ?></strong>
                                    </div>
                                    <div class="approval-att-status-wrap">
                                        <span class="approval-att-status-label">Requested status</span>
                                        <span class="att-legend-item <?php echo htmlspecialchars($att_class); ?> approval-att-status"><?php echo htmlspecialchars($att_label); ?><?php if ($req['leave_type']): ?> <em>(<?php echo htmlspecialchars($req['leave_type']); ?>)</em><?php endif; ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($req['employee_note'])): ?>
                                    <div class="approval-employee-note">
                                        <strong>Employee note</strong>
                                        <p><?php echo htmlspecialchars($req['employee_note']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <footer class="approval-card-footer">
                                    <form method="POST" action="approval_save.php" class="approval-actions">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="request_type" value="attendance">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                                        <label class="approval-note-field">
                                            <span class="sr-only">Note to employee</span>
                                            <input type="text" name="review_note" placeholder="Optional note to employee" class="approval-note-input">
                                        </label>
                                        <div class="approval-action-buttons">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve &amp; save</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-outline btn-sm btn-danger-outline" onclick="return confirm('Reject this attendance request?');">Reject</button>
                                        </div>
                                    </form>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="panel panel-elevated approvals-panel approvals-panel-leave-full">
        <div class="panel-header">
            <div class="panel-title-group approvals-panel-head">
                <span class="approvals-panel-icon approvals-panel-icon-leave" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                </span>
                <div>
                    <h3>Leave applications</h3>
                    <p class="approvals-panel-desc">Pending leave requests — approve to mark attendance.</p>
                </div>
            </div>
            <span class="panel-badge"><?php echo count($pending_leave); ?> pending</span>
        </div>
        <div class="panel-body approvals-panel-body">
            <?php if ($pending_leave === []): ?>
                <div class="empty-state compact approvals-empty">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    </div>
                    <h4>No leave requests</h4>
                    <p>When employees apply for leave, they will appear here for review.</p>
                </div>
            <?php else: ?>
                <ul class="approvals-leave-list">
                    <?php foreach ($pending_leave as $req):
                        $emp_initial = strtoupper(substr($req['employee_name'], 0, 1));
                        $days = leave_request_day_count($req['from_date'], $req['to_date']);
                        $lt_label = $leave_types_map[$req['leave_type']]['name'] ?? $req['leave_type'];
                        $from_ts = strtotime($req['from_date']);
                        $to_ts = strtotime($req['to_date']);
                        $is_cancel = ($req['request_status'] ?? '') === 'cancellation_pending';
                        $period_label = $req['from_date'] === $req['to_date']
                            ? date('D, d M Y', $from_ts)
                            : date('d M', $from_ts) . ' – ' . date('d M Y', $to_ts);
                        $leave_dates = leave_request_dates_in_range($req['from_date'], $req['to_date']);
                        ?>
                    <li class="approval-leave-item<?php echo $is_cancel ? ' is-cancel-request' : ''; ?>">
                        <div class="approval-leave-item-main">
                            <div class="approval-leave-item-employee">
                                <span class="approval-leave-item-avatar" aria-hidden="true"><?php echo htmlspecialchars($emp_initial); ?></span>
                                <div class="approval-leave-item-employee-text">
                                    <a href="employee_view.php?emp_id=<?php echo urlencode($req['emp_id']); ?>" class="approval-leave-item-name"><?php echo htmlspecialchars($req['employee_name']); ?></a>
                                    <span class="approval-leave-item-meta"><?php echo htmlspecialchars($req['emp_id']); ?> · <?php echo htmlspecialchars(get_branch_label($conn, (int) $req['branch_id'])); ?></span>
                                </div>
                            </div>

                            <div class="approval-leave-item-period">
                                <span class="approval-leave-item-period-label">Leave period</span>
                                <strong class="approval-leave-item-period-value"><?php echo htmlspecialchars($period_label); ?></strong>
                                <span class="approval-leave-item-days"><?php echo (int) $days; ?> day<?php echo $days === 1 ? '' : 's'; ?></span>
                            </div>

                            <div class="approval-leave-item-type">
                                <?php if ($is_cancel): ?>
                                    <span class="approval-leave-item-flag">Cancel request</span>
                                <?php endif; ?>
                                <span class="approval-leave-item-type-code"><?php echo htmlspecialchars($req['leave_type']); ?></span>
                                <span class="approval-leave-item-type-name"><?php echo htmlspecialchars($lt_label); ?></span>
                            </div>

                            <time class="approval-leave-item-submitted" datetime="<?php echo htmlspecialchars($req['created_at']); ?>">
                                <?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?>
                            </time>
                        </div>

                        <?php if (!empty($req['employee_note']) || ($days <= 5 && count($leave_dates) > 1)): ?>
                            <div class="approval-leave-item-extra">
                                <?php if (!empty($req['employee_note'])): ?>
                                    <p class="approval-leave-item-note"><strong>Reason:</strong> <?php echo htmlspecialchars($req['employee_note']); ?></p>
                                <?php endif; ?>
                                <?php if ($days <= 5 && count($leave_dates) > 1): ?>
                                    <div class="approval-leave-item-date-chips">
                                        <?php foreach ($leave_dates as $ld): ?>
                                            <span><?php echo date('D d M', strtotime($ld)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <footer class="approval-leave-item-footer">
                            <form method="POST" action="approval_save.php" class="approval-leave-item-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="request_type" value="leave">
                                <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                                <?php if ($is_cancel): ?>
                                    <input type="hidden" name="is_cancellation" value="1">
                                <?php endif; ?>
                                <input type="text" name="review_note" placeholder="Note to employee (optional)" class="approval-leave-item-note-input" aria-label="Note to employee">
                                <div class="approval-leave-item-buttons">
                                    <?php if ($is_cancel): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-danger">Approve cancel</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-outline btn-sm" onclick="return confirm('Reject this cancellation? The leave will remain approved.');">Reject cancel</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-outline btn-sm btn-danger-outline" onclick="return confirm('Reject this leave request?');">Reject</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </footer>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require 'includes/footer.php'; ?>
