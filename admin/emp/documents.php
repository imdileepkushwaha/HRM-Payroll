<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/employee_document_helper.php';

$emp_id = $employee['emp_id'];
$categories = employee_document_categories();
$approved_docs = get_employee_documents($conn, $emp_id, true);
$request_history = get_employee_document_requests($conn, $emp_id, 24);

$uploadable_types = employee_uploadable_document_types();
$pending_count = 0;
$approved_slots = 0;
foreach (array_keys($uploadable_types) as $type_key) {
    if (employee_has_pending_document_request($conn, $emp_id, $type_key)) {
        $pending_count++;
    }
    if (get_employee_active_document_by_type($conn, $emp_id, $type_key)) {
        $approved_slots++;
    }
}

$total_slots = count($uploadable_types);
$progress_pct = $total_slots > 0 ? (int) round(($approved_slots / $total_slots) * 100) : 0;

$office_type_keys = employee_document_category_keys('office');
$office_docs = array_values(array_filter($approved_docs, static function ($d) use ($office_type_keys) {
    $type = $d['doc_type'] ?? '';
    return in_array($type, $office_type_keys, true) || $type === 'other';
}));

function emp_doc_slot_status($conn, $emp_id, $type_key): array
{
    return employee_document_status_for_type($conn, $emp_id, $type_key);
}
?>
<div class="emp-page emp-page-documents">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <header class="emp-docs-banner">
        <div class="emp-docs-banner-decor" aria-hidden="true">
            <span class="emp-docs-banner-orb emp-docs-banner-orb-1"></span>
            <span class="emp-docs-banner-orb emp-docs-banner-orb-2"></span>
        </div>
        <div class="emp-docs-banner-grid">
            <div class="emp-docs-banner-main">
                <p class="emp-docs-eyebrow">My Documents</p>
                <h1>Upload &amp; manage your documents</h1>
                <p>Keep your profile complete with verified ID proofs, marksheets and office documents. Each upload is reviewed by admin before approval.</p>
                <div class="emp-docs-banner-tags">
                    <span class="emp-docs-banner-tag">PDF · JPG · PNG</span>
                    <span class="emp-docs-banner-tag">Max 5 MB per file</span>
                </div>
            </div>
            <div class="emp-docs-progress-card">
                <div class="emp-docs-progress-ring" style="--progress: <?php echo $progress_pct; ?>">
                    <svg viewBox="0 0 36 36" aria-hidden="true">
                        <path class="emp-docs-progress-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="emp-docs-progress-fill" stroke-dasharray="<?php echo $progress_pct; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    </svg>
                    <strong><?php echo $progress_pct; ?>%</strong>
                </div>
                <div class="emp-docs-progress-text">
                    <span>Profile completion</span>
                    <strong><?php echo $approved_slots; ?> / <?php echo $total_slots; ?> verified</strong>
                    <?php if ($pending_count > 0): ?>
                        <em><?php echo (int) $pending_count; ?> awaiting review</em>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="emp-docs-stats">
        <div class="emp-docs-stat emp-docs-stat-approved">
            <span class="emp-docs-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </span>
            <div>
                <span class="emp-docs-stat-label">Approved</span>
                <strong class="emp-docs-stat-value"><?php echo count($approved_docs); ?></strong>
            </div>
        </div>
        <div class="emp-docs-stat emp-docs-stat-pending">
            <span class="emp-docs-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div>
                <span class="emp-docs-stat-label">Pending review</span>
                <strong class="emp-docs-stat-value"><?php echo (int) $pending_count; ?></strong>
            </div>
        </div>
        <div class="emp-docs-stat emp-docs-stat-identity">
            <span class="emp-docs-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/></svg>
            </span>
            <div>
                <span class="emp-docs-stat-label">Identity docs</span>
                <strong class="emp-docs-stat-value">4 types</strong>
            </div>
        </div>
        <div class="emp-docs-stat emp-docs-stat-total">
            <span class="emp-docs-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </span>
            <div>
                <span class="emp-docs-stat-label">Total categories</span>
                <strong class="emp-docs-stat-value"><?php echo $total_slots; ?></strong>
            </div>
        </div>
    </div>

    <div class="emp-docs-layout">
        <div class="emp-docs-main">
            <nav class="emp-docs-tabs" role="tablist" aria-label="Document categories">
                <button type="button" class="emp-docs-tab is-active" role="tab" aria-selected="true" data-docs-tab="identity">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/></svg>
                    Identity
                </button>
                <button type="button" class="emp-docs-tab" role="tab" aria-selected="false" data-docs-tab="marksheet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
                    Education
                </button>
                <button type="button" class="emp-docs-tab" role="tab" aria-selected="false" data-docs-tab="office">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                    Office
                </button>
            </nav>

            <!-- Identity -->
            <section class="emp-docs-panel is-active" role="tabpanel" data-docs-panel="identity">
                <div class="emp-docs-panel-head">
                    <div>
                        <h2><?php echo htmlspecialchars($categories['identity']['label']); ?></h2>
                        <p><?php echo htmlspecialchars($categories['identity']['description']); ?></p>
                    </div>
                </div>

                <div class="emp-doc-identity-grid">
                    <article class="emp-doc-card emp-doc-card-accent emp-doc-card-aadhar">
                        <div class="emp-doc-card-top">
                            <span class="emp-doc-card-badge emp-doc-card-badge-id">ID</span>
                            <div class="emp-doc-card-head">
                                <div>
                                    <h3>Aadhar card</h3>
                                    <span class="emp-doc-status emp-doc-status-neutral">Upload front &amp; back</span>
                                </div>
                            </div>
                        </div>
                        <div class="emp-doc-aadhar-slots">
                            <?php foreach (['aadhar_front' => 'Front side', 'aadhar_back' => 'Back side'] as $aadhar_key => $aadhar_side):
                                $status = emp_doc_slot_status($conn, $emp_id, $aadhar_key);
                                $approved = $status['document'] ?? null;
                                $can_upload = $status['status'] !== 'pending';
                                ?>
                                <div class="emp-doc-aadhar-slot emp-doc-aadhar-slot-<?php echo htmlspecialchars($status['status']); ?>">
                                    <div class="emp-doc-aadhar-slot-head">
                                        <strong><?php echo htmlspecialchars($aadhar_side); ?></strong>
                                        <?php if ($status['status'] !== 'approved'): ?>
                                            <span class="emp-doc-status emp-doc-status-<?php echo htmlspecialchars($status['status']); ?>"><?php echo htmlspecialchars($status['label']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($approved): ?>
                                        <div class="emp-doc-aadhar-approved-strip">
                                            <span class="emp-doc-aadhar-approved-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                            </span>
                                            <div class="emp-doc-aadhar-approved-details">
                                                <span class="emp-doc-aadhar-approved-label">Verified &amp; approved</span>
                                                <span class="emp-doc-aadhar-approved-meta"><?php echo date('d M Y', strtotime($approved['approved_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $approved['file_size'])); ?></span>
                                            </div>
                                            <a href="document_download.php?doc_id=<?php echo (int) $approved['id']; ?>" class="btn btn-outline btn-sm emp-doc-aadhar-view-btn" target="_blank" rel="noopener">View file</a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($can_upload): ?>
                                        <?php if ($approved): ?>
                                            <div class="emp-doc-aadhar-replace">
                                                <span class="emp-doc-aadhar-replace-label">Replace document</span>
                                        <?php endif; ?>
                                        <form method="POST" action="document_upload_save.php" enctype="multipart/form-data" class="emp-doc-upload-form<?php echo $approved ? ' emp-doc-upload-form-compact' : ''; ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="doc_type" value="<?php echo htmlspecialchars($aadhar_key); ?>">
                                            <div class="emp-doc-file-zone">
                                                <input type="file" name="document_file" id="file_<?php echo htmlspecialchars($aadhar_key); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                                <label for="file_<?php echo htmlspecialchars($aadhar_key); ?>">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                                    <span>Drop or browse</span>
                                                    <em>Max 5 MB</em>
                                                </label>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-block"><?php echo $approved ? 'Replace' : 'Upload'; ?></button>
                                        </form>
                                        <?php if ($approved): ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="emp-doc-wait-msg">Pending admin review.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <?php foreach (['pan' => 'PAN card', 'passport' => 'Passport'] as $id_key => $id_label):
                        $status = emp_doc_slot_status($conn, $emp_id, $id_key);
                        $approved = $status['document'] ?? null;
                        $can_upload = $status['status'] !== 'pending';
                        ?>
                        <article class="emp-doc-card emp-doc-card-accent emp-doc-card-<?php echo htmlspecialchars($id_key); ?> emp-doc-card-status-<?php echo htmlspecialchars($status['status']); ?>">
                            <div class="emp-doc-card-top">
                                <span class="emp-doc-card-badge emp-doc-card-badge-<?php echo htmlspecialchars($id_key); ?>"><?php echo $id_key === 'pan' ? 'PAN' : 'PP'; ?></span>
                                <div class="emp-doc-card-head">
                                    <div>
                                        <h3><?php echo htmlspecialchars($id_label); ?></h3>
                                        <span class="emp-doc-status emp-doc-status-<?php echo htmlspecialchars($status['status']); ?>"><?php echo htmlspecialchars($status['label']); ?></span>
                                    </div>
                                    <?php if ($approved): ?>
                                        <a href="document_download.php?doc_id=<?php echo (int) $approved['id']; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">View</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($approved): ?>
                                <p class="emp-doc-card-meta">Approved <?php echo date('d M Y', strtotime($approved['approved_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $approved['file_size'])); ?></p>
                            <?php endif; ?>
                            <?php if ($can_upload): ?>
                                <form method="POST" action="document_upload_save.php" enctype="multipart/form-data" class="emp-doc-upload-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="doc_type" value="<?php echo htmlspecialchars($id_key); ?>">
                                    <div class="emp-doc-file-zone">
                                        <input type="file" name="document_file" id="file_<?php echo htmlspecialchars($id_key); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                        <label for="file_<?php echo htmlspecialchars($id_key); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                            <span>Drop or browse</span>
                                            <em>PDF, JPG or PNG</em>
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label for="note_<?php echo htmlspecialchars($id_key); ?>">Note (optional)</label>
                                        <input type="text" name="employee_note" id="note_<?php echo htmlspecialchars($id_key); ?>" maxlength="255" placeholder="Message for admin">
                                    </div>
                                    <button type="submit" class="btn btn-block"><?php echo $approved ? 'Upload replacement' : 'Submit for approval'; ?></button>
                                </form>
                            <?php else: ?>
                                <p class="emp-doc-wait-msg">Awaiting admin review — you can upload again after approval or rejection.</p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Marksheet -->
            <section class="emp-docs-panel" role="tabpanel" data-docs-panel="marksheet" hidden>
                <div class="emp-docs-panel-head">
                    <div>
                        <h2><?php echo htmlspecialchars($categories['marksheet']['label']); ?></h2>
                        <p><?php echo htmlspecialchars($categories['marksheet']['description']); ?></p>
                    </div>
                </div>

                <article class="emp-doc-card emp-doc-card-picker" data-doc-picker="marksheet">
                    <div class="emp-doc-type-chips emp-doc-type-chips-interactive" data-doc-picker-chips>
                        <?php foreach ($categories['marksheet']['types'] as $ms_key => $ms_label):
                            $chip_status = emp_doc_slot_status($conn, $emp_id, $ms_key);
                            ?>
                            <button type="button" class="emp-doc-type-chip emp-doc-type-chip-<?php echo htmlspecialchars($chip_status['status']); ?>" data-doc-chip="<?php echo htmlspecialchars($ms_key); ?>" title="<?php echo htmlspecialchars($ms_label); ?>">
                                <?php echo htmlspecialchars(preg_replace('/\s*Marksheet$/', '', $ms_label)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="emp-doc-picker-toolbar">
                        <div class="form-group emp-doc-picker-select-wrap">
                            <label for="marksheet_type">Or select from list</label>
                            <select id="marksheet_type" class="emp-doc-type-select" data-doc-picker-select>
                                <?php foreach ($categories['marksheet']['types'] as $ms_key => $ms_label): ?>
                                    <option value="<?php echo htmlspecialchars($ms_key); ?>"><?php echo htmlspecialchars($ms_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="emp-doc-picker-summary" data-doc-picker-summary></div>
                    </div>

                    <?php foreach ($categories['marksheet']['types'] as $ms_key => $ms_label):
                        $status = emp_doc_slot_status($conn, $emp_id, $ms_key);
                        $approved = $status['document'] ?? null;
                        $can_upload = $status['status'] !== 'pending';
                        ?>
                        <div class="emp-doc-picker-panel" data-doc-picker-panel="<?php echo htmlspecialchars($ms_key); ?>" hidden>
                            <div class="emp-doc-picker-panel-head">
                                <h3><?php echo htmlspecialchars($ms_label); ?></h3>
                                <div class="emp-doc-picker-panel-actions">
                                    <span class="emp-doc-status emp-doc-status-<?php echo htmlspecialchars($status['status']); ?>"><?php echo htmlspecialchars($status['label']); ?></span>
                                    <?php if ($approved): ?>
                                        <a href="document_download.php?doc_id=<?php echo (int) $approved['id']; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">View file</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($approved): ?>
                                <p class="emp-doc-card-meta">Approved <?php echo date('d M Y', strtotime($approved['approved_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $approved['file_size'])); ?></p>
                            <?php endif; ?>
                            <?php if ($can_upload): ?>
                                <form method="POST" action="document_upload_save.php" enctype="multipart/form-data" class="emp-doc-upload-form emp-doc-upload-form-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="doc_type" value="<?php echo htmlspecialchars($ms_key); ?>">
                                    <div class="emp-doc-file-zone emp-doc-file-zone-lg">
                                        <input type="file" name="document_file" id="file_<?php echo htmlspecialchars($ms_key); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                        <label for="file_<?php echo htmlspecialchars($ms_key); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                            <span>Drop your marksheet here or click to browse</span>
                                            <em>PDF, JPG or PNG · Max 5 MB</em>
                                        </label>
                                    </div>
                                    <div class="emp-doc-form-row">
                                        <div class="form-group">
                                            <label for="note_<?php echo htmlspecialchars($ms_key); ?>">Note for admin (optional)</label>
                                            <input type="text" name="employee_note" id="note_<?php echo htmlspecialchars($ms_key); ?>" maxlength="255" placeholder="e.g. Final year marksheet">
                                        </div>
                                        <button type="submit" class="btn"><?php echo $approved ? 'Replace document' : 'Submit for approval'; ?></button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="emp-doc-wait-msg">This marksheet is pending admin review.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </article>
            </section>

            <!-- Office -->
            <section class="emp-docs-panel" role="tabpanel" data-docs-panel="office" hidden>
                <div class="emp-docs-panel-head">
                    <div>
                        <h2><?php echo htmlspecialchars($categories['office']['label']); ?></h2>
                        <p><?php echo htmlspecialchars($categories['office']['description']); ?></p>
                    </div>
                </div>

                <article class="emp-doc-card emp-doc-card-picker" data-doc-picker="office">
                    <div class="emp-doc-type-chips emp-doc-type-chips-interactive emp-doc-type-chips-wrap" data-doc-picker-chips>
                        <?php foreach ($categories['office']['types'] as $off_key => $off_label):
                            $chip_status = emp_doc_slot_status($conn, $emp_id, $off_key);
                            ?>
                            <button type="button" class="emp-doc-type-chip emp-doc-type-chip-<?php echo htmlspecialchars($chip_status['status']); ?>" data-doc-chip="<?php echo htmlspecialchars($off_key); ?>" title="<?php echo htmlspecialchars($off_label); ?>">
                                <?php echo htmlspecialchars($off_label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="emp-doc-picker-toolbar">
                        <div class="form-group emp-doc-picker-select-wrap">
                            <label for="office_type">Or select from list</label>
                            <select id="office_type" class="emp-doc-type-select" data-doc-picker-select>
                                <?php foreach ($categories['office']['types'] as $off_key => $off_label): ?>
                                    <option value="<?php echo htmlspecialchars($off_key); ?>"><?php echo htmlspecialchars($off_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="emp-doc-picker-summary" data-doc-picker-summary></div>
                    </div>

                    <?php foreach ($categories['office']['types'] as $off_key => $off_label):
                        $status = emp_doc_slot_status($conn, $emp_id, $off_key);
                        $approved = $status['document'] ?? null;
                        $can_upload = $status['status'] !== 'pending';
                        ?>
                        <div class="emp-doc-picker-panel" data-doc-picker-panel="<?php echo htmlspecialchars($off_key); ?>" hidden>
                            <div class="emp-doc-picker-panel-head">
                                <h3><?php echo htmlspecialchars($off_label); ?></h3>
                                <div class="emp-doc-picker-panel-actions">
                                    <span class="emp-doc-status emp-doc-status-<?php echo htmlspecialchars($status['status']); ?>"><?php echo htmlspecialchars($status['label']); ?></span>
                                    <?php if ($approved): ?>
                                        <a href="document_download.php?doc_id=<?php echo (int) $approved['id']; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">View file</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($approved): ?>
                                <p class="emp-doc-card-meta">Approved <?php echo date('d M Y', strtotime($approved['approved_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $approved['file_size'])); ?></p>
                            <?php endif; ?>
                            <?php if ($can_upload): ?>
                                <form method="POST" action="document_upload_save.php" enctype="multipart/form-data" class="emp-doc-upload-form emp-doc-upload-form-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="doc_type" value="<?php echo htmlspecialchars($off_key); ?>">
                                    <div class="emp-doc-file-zone emp-doc-file-zone-lg">
                                        <input type="file" name="document_file" id="file_<?php echo htmlspecialchars($off_key); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                        <label for="file_<?php echo htmlspecialchars($off_key); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                            <span>Drop your document here or click to browse</span>
                                            <em>PDF, JPG or PNG · Max 5 MB</em>
                                        </label>
                                    </div>
                                    <div class="emp-doc-form-row">
                                        <div class="form-group">
                                            <label for="note_<?php echo htmlspecialchars($off_key); ?>">Note for admin (optional)</label>
                                            <input type="text" name="employee_note" id="note_<?php echo htmlspecialchars($off_key); ?>" maxlength="255" placeholder="e.g. March 2026 salary slip">
                                        </div>
                                        <button type="submit" class="btn"><?php echo $approved ? 'Replace document' : 'Submit for approval'; ?></button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="emp-doc-wait-msg">This document is pending admin review.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($office_docs !== []): ?>
                        <div class="emp-doc-approved-grid">
                            <h4>Approved office documents</h4>
                            <div class="emp-doc-approved-cards">
                                <?php foreach ($office_docs as $doc): ?>
                                    <article class="emp-doc-approved-item">
                                        <span class="emp-doc-approved-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        </span>
                                        <div>
                                            <strong><?php echo htmlspecialchars($doc['doc_label'] ?: employee_document_type_label($doc['doc_type'])); ?></strong>
                                            <span><?php echo date('d M Y', strtotime($doc['approved_at'])); ?> · <?php echo htmlspecialchars(format_employee_document_size((int) $doc['file_size'])); ?></span>
                                        </div>
                                        <a href="document_download.php?doc_id=<?php echo (int) $doc['id']; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Open</a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            </section>
        </div>

        <aside class="emp-docs-aside">
            <div class="emp-docs-guide card">
                <h3>Upload tips</h3>
                <ul>
                    <li>Use clear scans — all text must be readable.</li>
                    <li>Upload front and back separately for Aadhar.</li>
                    <li>One active document per type; replacements need re-approval.</li>
                    <li>Pending uploads cannot be changed until reviewed.</li>
                </ul>
            </div>

            <?php if ($request_history !== []): ?>
                <div class="emp-docs-recent card">
                    <h3>Recent uploads</h3>
                    <div class="emp-docs-recent-list">
                        <?php foreach (array_slice($request_history, 0, 6) as $req):
                            $status = $req['request_status'] ?? '';
                            ?>
                            <div class="emp-docs-recent-item emp-docs-recent-<?php echo htmlspecialchars($status); ?>">
                                <strong><?php echo htmlspecialchars($req['doc_label'] ?: employee_document_type_label($req['doc_type'])); ?></strong>
                                <span><?php echo date('d M Y', strtotime($req['created_at'])); ?></span>
                                <em class="emp-req-status emp-req-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></em>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="#emp-doc-history-full" class="emp-docs-recent-link">View full history ↓</a>
                </div>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($request_history !== []): ?>
        <section class="emp-doc-history card" id="emp-doc-history-full">
            <div class="emp-doc-history-head">
                <h3>Upload history</h3>
                <span class="emp-doc-history-count"><?php echo count($request_history); ?> records</span>
            </div>
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
                        <span class="emp-doc-history-dot" aria-hidden="true"></span>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.querySelectorAll('[data-docs-tab]');
    var panels = document.querySelectorAll('[data-docs-panel]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-docs-tab');
            tabs.forEach(function (t) {
                var active = t === tab;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach(function (panel) {
                var show = panel.getAttribute('data-docs-panel') === target;
                panel.classList.toggle('is-active', show);
                panel.hidden = !show;
            });
        });
    });

    document.querySelectorAll('[data-doc-picker]').forEach(function (picker) {
        var select = picker.querySelector('[data-doc-picker-select]');
        var panels = picker.querySelectorAll('[data-doc-picker-panel]');
        var summary = picker.querySelector('[data-doc-picker-summary]');
        var chips = picker.querySelectorAll('[data-doc-chip]');

        function showPanel(typeKey) {
            panels.forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-doc-picker-panel') !== typeKey;
            });
            if (select && select.value !== typeKey) {
                select.value = typeKey;
            }
            chips.forEach(function (chip) {
                chip.classList.toggle('is-selected', chip.getAttribute('data-doc-chip') === typeKey);
            });
            if (summary) {
                var panel = picker.querySelector('[data-doc-picker-panel="' + typeKey + '"]');
                var statusEl = panel ? panel.querySelector('.emp-doc-status') : null;
                summary.textContent = statusEl ? statusEl.textContent.trim() : '';
                var statusMatch = statusEl ? statusEl.className.match(/emp-doc-status-(\w+)/) : null;
                summary.className = 'emp-doc-picker-summary' + (statusMatch ? ' ' + statusMatch[1] : '');
            }
        }

        if (select) {
            showPanel(select.value);
            select.addEventListener('change', function () {
                showPanel(select.value);
            });
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                showPanel(chip.getAttribute('data-doc-chip'));
            });
        });

        picker.querySelectorAll('.emp-doc-file-zone input[type="file"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var label = input.closest('.emp-doc-file-zone').querySelector('label span');
                if (label) {
                    label.textContent = input.files && input.files[0] ? input.files[0].name : 'Drop or browse';
                }
            });
        });
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
