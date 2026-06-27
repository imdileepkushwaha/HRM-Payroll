<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_document_helper.php';

$emp_id = $employee['emp_id'];
$doc_types = employee_document_types();
$approved_docs = get_employee_documents($conn, $emp_id, true);
$other_docs = array_values(array_filter($approved_docs, static fn($d) => ($d['doc_type'] ?? '') === 'other'));
$request_history = get_employee_document_requests($conn, $emp_id, 12);
$pending_count = 0;
foreach ($doc_types as $type_key => $type_label) {
    if ($type_key !== 'other' && employee_has_pending_document_request($conn, $emp_id, $type_key)) {
        $pending_count++;
    }
}
foreach ($request_history as $req) {
    if (($req['request_status'] ?? '') === 'pending' && ($req['doc_type'] ?? '') === 'other') {
        $pending_count++;
    }
}
?>
<div class="emp-page emp-page-documents">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero emp-page-hero-docs">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow">Documents</p>
            <h2 class="emp-page-hero-title">Upload Aadhar, PAN, marksheet & more</h2>
            <p>Upload clear PDF or photo scans. Admin will review and approve before they appear on your verified profile.</p>
        </div>
        <?php if ($pending_count > 0): ?>
            <span class="emp-doc-pending-chip"><?php echo (int) $pending_count; ?> pending review</span>
        <?php endif; ?>
    </div>

    <div class="emp-doc-stats">
        <div class="emp-doc-stat">
            <span class="emp-doc-stat-label">Approved</span>
            <strong><?php echo count($approved_docs); ?></strong>
        </div>
        <div class="emp-doc-stat">
            <span class="emp-doc-stat-label">Pending</span>
            <strong><?php echo (int) $pending_count; ?></strong>
        </div>
        <div class="emp-doc-stat">
            <span class="emp-doc-stat-label">Allowed formats</span>
            <strong>PDF · JPG · PNG</strong>
        </div>
    </div>

    <div class="emp-doc-grid">
        <?php foreach (['aadhar', 'pan', 'marksheet'] as $type_key):
            $type_label = employee_document_type_label($type_key);
            $status = employee_document_status_for_type($conn, $emp_id, $type_key);
            $approved = $status['document'] ?? null;
            $can_upload = $status['status'] !== 'pending';
            ?>
            <section class="emp-doc-card emp-doc-card-<?php echo htmlspecialchars($type_key); ?>">
                <div class="emp-doc-card-head">
                    <div>
                        <h3><?php echo htmlspecialchars($type_label); ?></h3>
                        <span class="emp-doc-status emp-doc-status-<?php echo htmlspecialchars($status['status']); ?>"><?php echo htmlspecialchars($status['label']); ?></span>
                    </div>
                    <?php if ($approved): ?>
                        <a href="document_download.php?doc_id=<?php echo (int) $approved['id']; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">View</a>
                    <?php endif; ?>
                </div>
                <?php if ($approved): ?>
                    <p class="emp-doc-card-meta">Approved <?php echo date('d M Y', strtotime($approved['approved_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $approved['file_size'])); ?></p>
                <?php endif; ?>
                <?php if ($can_upload): ?>
                    <form method="POST" action="document_upload_save.php" enctype="multipart/form-data" class="emp-doc-upload-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="doc_type" value="<?php echo htmlspecialchars($type_key); ?>">
                        <div class="form-group">
                            <label for="file_<?php echo htmlspecialchars($type_key); ?>">Upload <?php echo htmlspecialchars($type_label); ?></label>
                            <input type="file" name="document_file" id="file_<?php echo htmlspecialchars($type_key); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                            <span class="form-hint">Max 5 MB · PDF, JPG or PNG</span>
                        </div>
                        <div class="form-group">
                            <label for="note_<?php echo htmlspecialchars($type_key); ?>">Note for admin (optional)</label>
                            <input type="text" name="employee_note" id="note_<?php echo htmlspecialchars($type_key); ?>" maxlength="255" placeholder="e.g. Updated Aadhar after address change">
                        </div>
                        <button type="submit" class="btn btn-block"><?php echo $approved ? 'Upload replacement' : 'Submit for approval'; ?></button>
                    </form>
                <?php else: ?>
                    <p class="emp-doc-wait-msg">Your upload is with admin for review. You can upload again after approval or rejection.</p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <section class="emp-doc-card emp-doc-card-other emp-doc-card-wide">
            <div class="emp-doc-card-head">
                <div>
                    <h3>Other documents</h3>
                    <span class="emp-doc-status emp-doc-status-neutral">Degree, experience letter, etc.</span>
                </div>
            </div>
            <form method="POST" action="document_upload_save.php" enctype="multipart/form-data" class="emp-doc-upload-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="doc_type" value="other">
                <div class="emp-doc-upload-fields">
                    <div class="form-group">
                        <label for="other_label">Document name</label>
                        <input type="text" name="doc_label" id="other_label" maxlength="120" placeholder="e.g. Degree certificate" required>
                    </div>
                    <div class="form-group">
                        <label for="other_file">Choose file</label>
                        <input type="file" name="document_file" id="other_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                    </div>
                    <div class="form-group">
                        <label for="other_note">Note for admin (optional)</label>
                        <input type="text" name="employee_note" id="other_note" maxlength="255">
                    </div>
                </div>
                <button type="submit" class="btn">Upload other document</button>
            </form>

            <?php if ($other_docs !== []): ?>
                <div class="emp-doc-other-list">
                    <h4>Approved other documents</h4>
                    <ul>
                        <?php foreach ($other_docs as $doc): ?>
                            <li>
                                <div>
                                    <strong><?php echo htmlspecialchars($doc['doc_label'] ?: 'Other document'); ?></strong>
                                    <span><?php echo date('d M Y', strtotime($doc['approved_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $doc['file_size'])); ?></span>
                                </div>
                                <a href="document_download.php?doc_id=<?php echo (int) $doc['id']; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">View</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($request_history !== []): ?>
        <section class="emp-doc-history card">
            <h3>Upload history</h3>
            <div class="emp-doc-history-list">
                <?php foreach ($request_history as $req):
                    $status = $req['request_status'] ?? '';
                    $approved_doc = $status === 'approved' ? get_employee_document_by_request_id($conn, (int) $req['id']) : null;
                    $view_url = null;
                    if ($status === 'pending') {
                        $view_url = 'document_download.php?request_id=' . (int) $req['id'];
                    } elseif ($approved_doc) {
                        $view_url = 'document_download.php?doc_id=' . (int) $approved_doc['id'];
                    }
                    ?>
                    <article class="emp-doc-history-item emp-doc-history-<?php echo htmlspecialchars($status); ?>">
                        <div class="emp-doc-history-main">
                            <strong><?php echo htmlspecialchars($req['doc_label'] ?: employee_document_type_label($req['doc_type'])); ?></strong>
                            <span><?php echo date('d M Y, h:i A', strtotime($req['created_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $req['file_size'])); ?></span>
                            <?php if (!empty($req['review_note']) && $status !== 'pending'): ?>
                                <em>Admin: <?php echo htmlspecialchars($req['review_note']); ?></em>
                            <?php endif; ?>
                        </div>
                        <div class="emp-doc-history-actions">
                            <span class="emp-req-status emp-req-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                            <?php if ($view_url): ?>
                                <a href="<?php echo htmlspecialchars($view_url); ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">View</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
