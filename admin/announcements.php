<?php
require_once 'includes/admin_page_init.php';
admin_page_init('announcements');
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_helper.php';
require_once 'includes/csrf_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$all_branches = get_branches($conn);
$announcements = get_admin_announcements($conn, $branch_id);
$edit_id = (int) ($_GET['edit'] ?? 0);
$filter = strtolower(trim($_GET['filter'] ?? 'all'));
$allowed_filters = ['all', 'live', 'pinned', 'inactive', 'expired'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}

$edit_row = null;
foreach ($announcements as $row) {
    if ((int) $row['id'] === $edit_id) {
        $edit_row = $row;
        break;
    }
}
if ($edit_id > 0 && $edit_row === null) {
    $stmt = $conn->prepare('SELECT a.*, b.name AS branch_name FROM announcements a LEFT JOIN branches b ON b.id = a.branch_id WHERE a.id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc() ?: null;
}

$today = date('Y-m-d');
$stats = ['total' => count($announcements), 'active' => 0, 'pinned' => 0, 'expired' => 0];
foreach ($announcements as $ann) {
    if ((int) ($ann['is_active'] ?? 0) === 1) {
        $stats['active']++;
    }
    if ((int) ($ann['is_pinned'] ?? 0) === 1) {
        $stats['pinned']++;
    }
    if (!empty($ann['expires_at']) && $ann['expires_at'] < $today) {
        $stats['expired']++;
    }
}

function announce_matches_filter(array $ann, string $filter, string $today): bool
{
    $is_expired = !empty($ann['expires_at']) && $ann['expires_at'] < $today;
    $is_active = (int) ($ann['is_active'] ?? 0) === 1;
    $is_pinned = (int) ($ann['is_pinned'] ?? 0) === 1;

    return match ($filter) {
        'live' => $is_active && !$is_expired,
        'pinned' => $is_pinned,
        'inactive' => !$is_active,
        'expired' => $is_expired,
        default => true,
    };
}

$filtered_announcements = array_values(array_filter(
    $announcements,
    static fn($ann) => announce_matches_filter($ann, $filter, $today)
));

$company_name = trim(get_all_settings($conn)['company_name'] ?? '') ?: 'Company';
$preview_title = $edit_row['title'] ?? '';
$preview_body = $edit_row['body'] ?? '';
$preview_pinned = isset($edit_row) && (int) ($edit_row['is_pinned'] ?? 0) === 1;
$preview_branch_label = 'All branches';
if (isset($edit_row['branch_id']) && (int) $edit_row['branch_id'] > 0) {
    foreach ($all_branches as $b) {
        if ((int) $b['id'] === (int) $edit_row['branch_id']) {
            $preview_branch_label = $b['name'];
            break;
        }
    }
}
?>
<div class="announce-page">
    <div class="announce-hero">
        <div class="announce-hero-main">
            <p class="page-eyebrow">Employee portal</p>
            <h2>Announcements</h2>
            <p>Broadcast notices to every employee dashboard<?php echo $branch_id !== null ? ' · <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?>.</p>
        </div>
        <div class="announce-hero-stats">
            <div class="announce-stat">
                <span class="announce-stat-value"><?php echo $stats['total']; ?></span>
                <span class="announce-stat-label">Total</span>
            </div>
            <div class="announce-stat announce-stat-live">
                <span class="announce-stat-value"><?php echo $stats['active']; ?></span>
                <span class="announce-stat-label">Live</span>
            </div>
            <div class="announce-stat announce-stat-pin">
                <span class="announce-stat-value"><?php echo $stats['pinned']; ?></span>
                <span class="announce-stat-label">Pinned</span>
            </div>
            <div class="announce-stat announce-stat-exp <?php echo $stats['expired'] > 0 ? 'has-warn' : ''; ?>">
                <span class="announce-stat-value"><?php echo $stats['expired']; ?></span>
                <span class="announce-stat-label">Expired</span>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="announce-shell">
        <div class="announce-compose-row" aria-label="Compose announcement">
            <div class="announce-editor panel panel-elevated">
                <div class="announce-editor-head">
                    <span class="announce-editor-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </span>
                    <div>
                        <h3><?php echo $edit_row ? 'Edit notice' : 'New notice'; ?></h3>
                        <p><?php echo $edit_row ? 'Update and save changes' : 'Publish to employee dashboard'; ?></p>
                    </div>
                    <?php if ($edit_row): ?>
                        <a href="announcements.php?filter=<?php echo urlencode($filter); ?>" class="announce-cancel-edit" title="Cancel edit">&times;</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="announcement_save.php" class="announce-form" id="announceForm">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit_row): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $edit_row['id']; ?>">
                    <?php endif; ?>

                    <div class="announce-form-block">
                        <span class="announce-form-block-label">Content</span>
                        <div class="form-group">
                            <label for="ann-title">Title</label>
                            <input type="text" name="title" id="ann-title" maxlength="200" required value="<?php echo htmlspecialchars($edit_row['title'] ?? ''); ?>" placeholder="Holiday notice, policy update…">
                        </div>
                        <div class="form-group announce-body-group">
                            <div class="announce-body-label-row">
                                <label for="ann-body">Message</label>
                                <span class="announce-char-count" id="annCharCount">0 / 2000</span>
                            </div>
                            <textarea name="body" id="ann-body" rows="5" maxlength="2000" required placeholder="Write what employees need to know…"><?php echo htmlspecialchars($edit_row['body'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="announce-form-row-2">
                        <div class="announce-form-block">
                            <span class="announce-form-block-label">Audience &amp; schedule</span>
                            <div class="form-group">
                                <label for="ann-branch">Who sees this</label>
                                <select name="branch_id" id="ann-branch">
                                    <option value="">All branches</option>
                                    <?php foreach ($all_branches as $branch): ?>
                                        <option value="<?php echo (int) $branch['id']; ?>" <?php echo isset($edit_row['branch_id']) && (int) $edit_row['branch_id'] === (int) $branch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ann-expires">Auto-hide after (optional)</label>
                                <input type="date" name="expires_at" id="ann-expires" value="<?php echo htmlspecialchars($edit_row['expires_at'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="announce-form-block announce-form-block-options">
                            <span class="announce-form-block-label">Visibility</span>
                            <div class="announce-option-cards">
                                <label class="announce-option-card settings-switch <?php echo !isset($edit_row) || (int) ($edit_row['is_active'] ?? 1) === 1 ? 'is-on' : ''; ?>">
                                    <input type="checkbox" name="is_active" id="ann-active" value="1" <?php echo !isset($edit_row) || (int) ($edit_row['is_active'] ?? 1) === 1 ? 'checked' : ''; ?>>
                                    <span class="settings-switch-ui" aria-hidden="true"></span>
                                    <span class="announce-option-text">
                                        <strong>Active</strong>
                                        <span>Show on employee portal</span>
                                    </span>
                                </label>
                                <label class="announce-option-card settings-switch <?php echo isset($edit_row) && (int) ($edit_row['is_pinned'] ?? 0) === 1 ? 'is-on' : ''; ?>">
                                    <input type="checkbox" name="is_pinned" id="ann-pinned" value="1" <?php echo isset($edit_row) && (int) ($edit_row['is_pinned'] ?? 0) === 1 ? 'checked' : ''; ?>>
                                    <span class="settings-switch-ui" aria-hidden="true"></span>
                                    <span class="announce-option-text">
                                        <strong>Pin to top</strong>
                                        <span>Stay above other notices</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="announce-form-submit">
                        <button type="submit" class="btn btn-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            <?php echo $edit_row ? 'Save changes' : 'Publish notice'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="announce-phone-preview panel panel-elevated">
                <div class="announce-phone-preview-head">
                    <h3>Live preview</h3>
                    <p>Employee portal dashboard</p>
                </div>
                <div class="announce-phone-preview-body">
                    <div class="announce-phone-frame">
                        <div class="announce-phone-notch" aria-hidden="true"></div>
                        <div class="announce-phone-screen">
                            <div class="announce-phone-topbar">
                                <span class="announce-phone-logo"><?php echo htmlspecialchars(strtoupper(substr($company_name, 0, 1))); ?></span>
                                <span><?php echo htmlspecialchars($company_name); ?></span>
                            </div>
                            <div class="announce-phone-section-label">Announcements</div>
                            <article class="announce-phone-card <?php echo $preview_pinned ? 'is-pinned' : ''; ?>" id="announcePreview">
                                <span class="announce-phone-pin" id="previewPin" <?php echo $preview_pinned ? '' : 'hidden'; ?>>Pinned</span>
                                <h4 id="previewTitle"><?php echo htmlspecialchars($preview_title !== '' ? $preview_title : 'Notice title'); ?></h4>
                                <p id="previewBody"><?php echo $preview_body !== '' ? nl2br(htmlspecialchars($preview_body)) : 'Your message appears here on the employee dashboard.'; ?></p>
                                <div class="announce-phone-meta">
                                    <time id="previewDate"><?php echo date('j M Y'); ?></time>
                                    <span id="previewAudience"><?php echo htmlspecialchars($preview_branch_label); ?></span>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="announce-feed-section panel panel-elevated" aria-label="Published announcements">
            <div class="announce-feed-toolbar">
                <div>
                    <h3>Notice board</h3>
                    <p><?php echo count($filtered_announcements); ?> shown · newest first</p>
                </div>
                <div class="announce-filter-pills" role="tablist" aria-label="Filter announcements">
                    <?php
                    $filters = [
                        'all' => 'All',
                        'live' => 'Live',
                        'pinned' => 'Pinned',
                        'inactive' => 'Inactive',
                        'expired' => 'Expired',
                    ];
                    foreach ($filters as $key => $label):
                        $url = 'announcements.php?filter=' . $key . ($edit_id > 0 ? '&edit=' . $edit_id : '');
                    ?>
                    <a href="<?php echo htmlspecialchars($url); ?>" class="announce-filter-pill <?php echo $filter === $key ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo $filter === $key ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($label); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (count($filtered_announcements) > 0): ?>
                <div class="announce-timeline">
                    <?php foreach ($filtered_announcements as $ann):
                        $is_expired = !empty($ann['expires_at']) && $ann['expires_at'] < $today;
                        $is_active = (int) ($ann['is_active'] ?? 0) === 1;
                        $is_pinned = (int) ($ann['is_pinned'] ?? 0) === 1;
                        $is_editing = $edit_id === (int) $ann['id'];
                        $status_class = $is_pinned ? 'status-pinned' : ($is_active && !$is_expired ? 'status-live' : ($is_expired ? 'status-expired' : 'status-off'));
                    ?>
                    <article class="announce-card <?php echo $status_class; ?> <?php echo $is_editing ? 'is-editing' : ''; ?> <?php echo !$is_active ? 'is-inactive' : ''; ?>">
                        <div class="announce-card-accent" aria-hidden="true"></div>
                        <div class="announce-card-inner">
                            <header class="announce-card-head">
                                <div class="announce-card-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h20a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2z"/></svg>
                                </div>
                                <div class="announce-card-head-text">
                                    <div class="announce-card-badges">
                                        <?php if ($is_pinned): ?><span class="announce-tag tag-pin">Pinned</span><?php endif; ?>
                                        <?php if ($is_active && !$is_expired): ?><span class="announce-tag tag-live">Live</span><?php endif; ?>
                                        <?php if (!$is_active): ?><span class="announce-tag tag-off">Hidden</span><?php endif; ?>
                                        <?php if ($is_expired): ?><span class="announce-tag tag-exp">Expired</span><?php endif; ?>
                                        <span class="announce-tag tag-audience"><?php echo $ann['branch_id'] ? htmlspecialchars($ann['branch_name'] ?? 'Branch') : 'All branches'; ?></span>
                                    </div>
                                    <h4><?php echo htmlspecialchars($ann['title']); ?></h4>
                                </div>
                                <div class="announce-card-actions">
                                    <a href="announcements.php?edit=<?php echo (int) $ann['id']; ?>&filter=<?php echo urlencode($filter); ?>" class="announce-icon-btn" title="Edit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <form method="POST" action="announcement_save.php" class="inline-form" onsubmit="return confirm('Delete this announcement permanently?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="announcement_action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $ann['id']; ?>">
                                        <button type="submit" class="announce-icon-btn announce-icon-btn-danger" title="Delete">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </header>
                            <div class="announce-card-body"><?php echo nl2br(htmlspecialchars($ann['body'])); ?></div>
                            <footer class="announce-card-foot">
                                <span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Posted <?php echo date('j M Y', strtotime($ann['created_at'])); ?>
                                </span>
                                <?php if (!empty($ann['expires_at'])): ?>
                                <span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                                    Expires <?php echo date('j M Y', strtotime($ann['expires_at'])); ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($ann['created_by'])): ?>
                                <span>By <?php echo htmlspecialchars($ann['created_by']); ?></span>
                                <?php endif; ?>
                            </footer>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="announce-empty">
                    <div class="announce-empty-art" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25"><path d="M22 17H2a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h20a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2z"/><path d="M6 9v6"/><path d="M18 9v6"/></svg>
                    </div>
                    <h4><?php echo $filter === 'all' ? 'No announcements yet' : 'Nothing in this filter'; ?></h4>
                    <p><?php echo $filter === 'all' ? 'Use the composer above to publish your first notice.' : 'Try another filter or publish a new notice.'; ?></p>
                    <?php if ($filter !== 'all'): ?>
                        <a href="announcements.php" class="btn btn-outline btn-sm">Show all</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<script>
(function () {
    var titleEl = document.getElementById('ann-title');
    var bodyEl = document.getElementById('ann-body');
    var branchEl = document.getElementById('ann-branch');
    var pinEl = document.getElementById('ann-pinned');
    var previewTitle = document.getElementById('previewTitle');
    var previewBody = document.getElementById('previewBody');
    var previewPin = document.getElementById('previewPin');
    var previewCard = document.getElementById('announcePreview');
    var previewAudience = document.getElementById('previewAudience');
    var charCount = document.getElementById('annCharCount');

    function syncPreview() {
        if (previewTitle && titleEl) {
            previewTitle.textContent = titleEl.value.trim() || 'Notice title';
        }
        if (previewBody && bodyEl) {
            var text = bodyEl.value.trim() || 'Your message appears here on the employee dashboard.';
            previewBody.textContent = text;
        }
        if (charCount && bodyEl) {
            charCount.textContent = bodyEl.value.length + ' / 2000';
        }
        if (previewPin && pinEl && previewCard) {
            var pinned = pinEl.checked;
            previewPin.hidden = !pinned;
            previewCard.classList.toggle('is-pinned', pinned);
        }
        if (previewAudience && branchEl) {
            var opt = branchEl.options[branchEl.selectedIndex];
            previewAudience.textContent = opt ? opt.text : 'All branches';
        }
    }

    if (titleEl) titleEl.addEventListener('input', syncPreview);
    if (bodyEl) bodyEl.addEventListener('input', syncPreview);
    if (branchEl) branchEl.addEventListener('change', syncPreview);
    if (pinEl) pinEl.addEventListener('change', syncPreview);

    document.querySelectorAll('.announce-form .settings-switch input[type="checkbox"]').forEach(function (input) {
        input.addEventListener('change', function () {
            input.closest('.settings-switch').classList.toggle('is-on', input.checked);
            syncPreview();
        });
    });

    syncPreview();
})();
</script>
<?php require 'includes/footer.php'; ?>
