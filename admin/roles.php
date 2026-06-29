<?php
require_once 'includes/admin_page_init.php';
admin_page_init('roles');

$roles = get_admin_roles($conn);
$selected_id = (int) ($_GET['role_id'] ?? ($roles[0]['id'] ?? 0));
$selected_perms = $selected_id ? get_role_permissions($conn, $selected_id) : [];
$perm_labels = admin_permission_keys();
$selected_role = null;
foreach ($roles as $role) {
    if ((int) $role['id'] === $selected_id) {
        $selected_role = $role;
        break;
    }
}
?>
<div class="hrm-page roles-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">System</p>
            <h2>Admin roles &amp; permissions</h2>
            <p>Control what each role can access in the admin panel. Assign roles under <a href="settings.php?tab=admins">Settings → Admins</a>.</p>
        </div>
        <div class="page-header-actions"><a href="settings.php?tab=admins" class="btn btn-outline">Admin users</a></div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="roles-layout">
        <section class="panel panel-elevated roles-list-panel">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Roles</h3>
                    <span class="panel-badge"><?php echo count($roles); ?> defined</span>
                </div>
            </div>
            <div class="panel-body padded">
                <ul class="roles-card-list">
                    <?php foreach ($roles as $role):
                        $rid = (int) $role['id'];
                        $is_sel = $rid === $selected_id;
                    ?>
                    <li>
                        <a href="roles.php?role_id=<?php echo $rid; ?>" class="roles-card<?php echo $is_sel ? ' is-active' : ''; ?>">
                            <span class="roles-card-icon" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($role['name'], 0, 1))); ?></span>
                            <span class="roles-card-body">
                                <strong><?php echo htmlspecialchars($role['name']); ?></strong>
                                <span><?php echo htmlspecialchars($role['description'] ?? ''); ?></span>
                            </span>
                            <?php if ((int) ($role['is_system'] ?? 0) === 1): ?>
                                <span class="roles-system-badge">System</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="panel panel-elevated roles-perms-panel">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3><?php echo $selected_role ? htmlspecialchars($selected_role['name']) : 'Permissions'; ?></h3>
                    <?php if ($selected_role): ?>
                        <span class="panel-badge"><?php echo count($selected_perms); ?> granted</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-body padded">
                <?php if ($selected_id): ?>
                <form method="POST" action="roles_save.php" class="roles-perm-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="role_id" value="<?php echo $selected_id; ?>">
                    <div class="roles-perm-grid">
                        <?php foreach ($perm_labels as $key => $label): ?>
                            <label class="roles-perm-check">
                                <input type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($key); ?>" <?php echo in_array($key, $selected_perms, true) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-header">Save permissions</button>
                </form>
                <?php else: ?>
                    <div class="masters-empty"><h4>Select a role</h4><p>Choose a role from the list to edit its permissions.</p></div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
