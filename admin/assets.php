<?php
require_once 'includes/admin_page_init.php';
admin_page_init('assets');
require_once 'includes/hrm_modules_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$write_branch = $branch_id ?? branch_id_for_write();
$filter = $_GET['status'] ?? '';
$all_assets = get_assets($conn, $branch_id, null);
$assets = $filter !== ''
    ? array_values(array_filter($all_assets, static fn($a) => ($a['status'] ?? '') === $filter))
    : $all_assets;
$branches = get_branches($conn);
$branch_map = [];
foreach ($branches as $b) {
    $branch_map[(int) $b['id']] = $b['name'];
}
$bf = branch_employees_sql('e');
$emp_stmt = $conn->prepare('SELECT emp_id, name FROM employees e WHERE e.is_active = 1' . $bf['sql'] . ' ORDER BY e.name');
bind_branch_stmt_params($emp_stmt, $bf['types'], $bf['params']);
$emp_stmt->execute();
$employees = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$available_count = 0;
$assigned_count = 0;
$categories = [];
foreach ($all_assets as $a) {
    if (($a['status'] ?? '') === 'available') {
        $available_count++;
    } elseif (($a['status'] ?? '') === 'assigned') {
        $assigned_count++;
    }
    $cat = trim($a['category'] ?? 'General');
    if ($cat !== '') {
        $categories[$cat] = true;
    }
}

$status_labels = ['available' => 'Available', 'assigned' => 'Assigned', 'retired' => 'Retired'];
$status_class = ['available' => 'asset-status-available', 'assigned' => 'asset-status-assigned', 'retired' => 'asset-status-retired'];
?>
<div class="hrm-page assets-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">Operations</p>
            <h2>Asset allocation</h2>
            <p>Track company assets and assign them to employees<?php echo $branch_id !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?>.</p>
        </div>
        <div class="page-header-actions">
            <a href="employees.php" class="btn btn-header">Employees</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div><strong><?php echo count($all_assets); ?></strong><span>Total assets</span></div>
        </div>
        <div class="settings-status-chip ok">
            <span class="status-dot"></span>
            <div><strong><?php echo $available_count; ?></strong><span>Available</span></div>
        </div>
        <div class="settings-status-chip warn">
            <span class="status-dot"></span>
            <div><strong><?php echo $assigned_count; ?></strong><span>Assigned</span></div>
        </div>
        <div class="settings-status-chip neutral">
            <span class="status-dot"></span>
            <div><strong><?php echo count($categories); ?></strong><span>Categories</span></div>
        </div>
    </div>

    <div class="assets-layout">
        <section class="panel panel-elevated assets-add-panel">
            <div class="panel-header">
                <div class="panel-title-group">
                    <h3>Add asset</h3>
                </div>
            </div>
            <div class="panel-body padded">
                <div class="settings-add-panel masters-add-panel assets-add-form-panel">
                    <div class="settings-add-panel-head">
                        <span class="settings-add-panel-icon assets-add-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg>
                        </span>
                        <div class="settings-add-panel-head-text">
                            <h4>Register new asset</h4>
                            <p>Unique tag, name, category and branch location.</p>
                        </div>
                    </div>
                    <form method="POST" action="asset_save.php" class="settings-add-panel-body">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="asset_action" value="save">
                        <div class="assets-add-grid">
                            <div class="form-group">
                                <label for="asset_tag">Asset tag</label>
                                <input type="text" name="asset_tag" id="asset_tag" required placeholder="LT-001" class="settings-add-input">
                            </div>
                            <div class="form-group assets-field-wide">
                                <label for="asset_name">Name</label>
                                <input type="text" name="name" id="asset_name" required placeholder="Dell Laptop" class="settings-add-input">
                            </div>
                            <div class="form-group">
                                <label for="asset_category">Category</label>
                                <input type="text" name="category" id="asset_category" value="IT Equipment" class="settings-add-input">
                            </div>
                            <div class="form-group">
                                <label for="asset_branch">Branch</label>
                                <select name="branch_id" id="asset_branch" class="settings-add-input">
                                    <?php foreach ($branches as $b): ?>
                                        <option value="<?php echo (int) $b['id']; ?>" <?php echo (int) $b['id'] === $write_branch ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-header assets-add-submit">Add asset</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="panel panel-elevated assets-inventory-panel">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Inventory</h3>
                    <span class="panel-badge" id="assetInventoryCount"><?php echo count($assets); ?> listed</span>
                </div>
                <div class="assets-filter-pills announce-filter-pills">
                    <a href="assets.php" class="announce-filter-pill <?php echo $filter === '' ? 'is-active' : ''; ?>">All</a>
                    <a href="assets.php?status=available" class="announce-filter-pill <?php echo $filter === 'available' ? 'is-active' : ''; ?>">Available</a>
                    <a href="assets.php?status=assigned" class="announce-filter-pill <?php echo $filter === 'assigned' ? 'is-active' : ''; ?>">Assigned</a>
                </div>
                <?php if ($assets !== []): ?>
                <div class="masters-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="assetInventorySearch" placeholder="Search tag, name, assignee…" autocomplete="off" aria-label="Search assets">
                    <button type="button" class="masters-search-clear" id="assetInventoryClear" hidden aria-label="Clear">&times;</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="panel-body padded">
                <?php if ($assets === []): ?>
                    <div class="masters-empty assets-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h8M8 14h4"/></svg>
                        <h4><?php echo $filter !== '' ? 'No ' . htmlspecialchars($status_labels[$filter] ?? $filter) . ' assets' : 'No assets registered'; ?></h4>
                        <p><?php echo $filter !== '' ? 'Try another filter or add a new asset.' : 'Use the form on the left to register your first company asset.'; ?></p>
                    </div>
                <?php else: ?>
                    <ul class="asset-card-list" id="assetCardList">
                        <?php foreach ($assets as $a):
                            $st = $a['status'] ?? 'available';
                            $tag_short = strtoupper(substr($a['asset_tag'], 0, 2));
                            $branch_name = $branch_map[(int) ($a['branch_id'] ?? 0)] ?? '';
                            $assignee = $a['assigned_to_name'] ?? '';
                            $search = strtolower(
                                ($a['asset_tag'] ?? '') . ' ' . ($a['name'] ?? '') . ' ' . ($a['category'] ?? '') . ' ' . $assignee . ' ' . $branch_name
                            );
                            ?>
                        <li class="asset-card asset-card-<?php echo htmlspecialchars($st); ?>" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="asset-card-icon" aria-hidden="true"><?php echo htmlspecialchars($tag_short); ?></div>
                            <div class="asset-card-body">
                                <div class="asset-card-top">
                                    <strong class="asset-card-tag"><?php echo htmlspecialchars($a['asset_tag']); ?></strong>
                                    <span class="asset-status-badge <?php echo $status_class[$st] ?? ''; ?>"><?php echo htmlspecialchars($status_labels[$st] ?? $st); ?></span>
                                </div>
                                <span class="asset-card-name"><?php echo htmlspecialchars($a['name']); ?></span>
                                <span class="asset-card-meta">
                                    <?php echo htmlspecialchars($a['category']); ?>
                                    <?php if ($branch_name !== ''): ?> · <?php echo htmlspecialchars($branch_name); ?><?php endif; ?>
                                    <?php if ($assignee !== ''): ?> · Assigned to <strong><?php echo htmlspecialchars($assignee); ?></strong><?php endif; ?>
                                </span>
                            </div>
                            <div class="asset-card-actions">
                                <?php if ($st === 'available'): ?>
                                <form method="POST" action="asset_save.php" class="asset-assign-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="asset_action" value="assign">
                                    <input type="hidden" name="asset_id" value="<?php echo (int) $a['id']; ?>">
                                    <select name="emp_id" required class="asset-assign-select" aria-label="Assign to employee">
                                        <option value="">Select employee</option>
                                        <?php foreach ($employees as $e): ?>
                                            <option value="<?php echo htmlspecialchars($e['emp_id']); ?>"><?php echo htmlspecialchars($e['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-header">Assign</button>
                                </form>
                                <?php elseif ($st === 'assigned'): ?>
                                <form method="POST" action="asset_save.php" class="asset-return-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="asset_action" value="return">
                                    <input type="hidden" name="asset_id" value="<?php echo (int) $a['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">Return</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($st !== 'retired'): ?>
                                <details class="asset-card-edit">
                                    <summary class="btn btn-sm btn-outline">Edit</summary>
                                    <form method="POST" action="asset_save.php" class="asset-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="asset_action" value="update">
                                        <input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                                        <input type="hidden" name="branch_id" value="<?php echo (int) $a['branch_id']; ?>">
                                        <div class="asset-inline-grid">
                                            <input type="text" name="asset_tag" value="<?php echo htmlspecialchars($a['asset_tag']); ?>" required class="settings-add-input" placeholder="Tag">
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($a['name']); ?>" required class="settings-add-input" placeholder="Name">
                                            <input type="text" name="category" value="<?php echo htmlspecialchars($a['category']); ?>" class="settings-add-input" placeholder="Category">
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-header">Save</button>
                                    </form>
                                    <?php if ($st === 'available'): ?>
                                    <form method="POST" action="asset_save.php" class="asset-retire-form" onsubmit="return confirm('Retire this asset?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="asset_action" value="retire">
                                        <input type="hidden" name="asset_id" value="<?php echo (int) $a['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline btn-danger-outline">Retire</button>
                                    </form>
                                    <?php endif; ?>
                                </details>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="masters-empty masters-search-empty" id="assetInventoryNoMatch" hidden>
                        <h4>No matches</h4>
                        <p>Try a different tag, name or assignee.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php if ($assets !== []): ?>
<script>
(function () {
    var input = document.getElementById('assetInventorySearch');
    var clearBtn = document.getElementById('assetInventoryClear');
    var badge = document.getElementById('assetInventoryCount');
    var noMatch = document.getElementById('assetInventoryNoMatch');
    var list = document.getElementById('assetCardList');
    if (!input || !list) return;

    var cards = list.querySelectorAll('.asset-card');
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
