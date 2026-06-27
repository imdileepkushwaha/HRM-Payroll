<?php
/**
 * Lightweight time diagnostic (no schema migration). Use when setup.php fails.
 * Requires PAYROLL_ALLOW_SETUP_TOOLS = true in admin/config/production.php
 */
require_once __DIR__ . '/includes/setup_helper.php';

payroll_setup_bootstrap();

$conn = null;
$db_ok = false;
$db_message = '';

try {
    require_once __DIR__ . '/includes/db_connection.php';
    $conn = payroll_open_mysql_connection(
        PAYROLL_MYSQL_HOST,
        PAYROLL_MYSQL_DATABASE,
        PAYROLL_MYSQL_USER,
        PAYROLL_MYSQL_PASS
    );
    if (!empty($conn->connect_error)) {
        $db_message = (string) $conn->connect_error;
    } else {
        $conn->set_charset('utf8mb4');
        payroll_configure_db_timezone($conn);
        $db_ok = true;
        $db_message = 'Connected';
    }
} catch (Throwable $e) {
    $db_message = $e->getMessage();
}

$time_diag = payroll_setup_time_diagnostics($db_ok ? $conn : null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time check — Payroll</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-auth">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">P</div>
            <h2>Server time check</h2>
            <p class="login-subtitle">India (IST) — compare with Windows taskbar clock</p>
            <div class="setup-log" style="text-align:left;">
                <div class="ok"><strong>PHP / app</strong></div>
                <div>Environment: <?php echo htmlspecialchars((string) $time_diag['env']); ?></div>
                <div>App TZ: <?php echo htmlspecialchars((string) $time_diag['app_timezone']); ?> (<?php echo htmlspecialchars((string) $time_diag['app_tz_offset']); ?>)</div>
                <div><strong>PHP now (IST): <?php echo htmlspecialchars((string) $time_diag['php_now']); ?></strong></div>
                <div><strong>Today label: <?php echo htmlspecialchars((string) $time_diag['php_date']); ?></strong></div>
                <div>php.ini date.timezone: <?php echo htmlspecialchars((string) $time_diag['php_ini_timezone']); ?></div>
                <div style="margin-top:12px;" class="<?php echo $db_ok ? 'ok' : 'err'; ?>"><strong>Database</strong></div>
                <div><?php echo htmlspecialchars($db_message); ?></div>
                <?php if ($time_diag['mysql_now'] !== null): ?>
                    <div>MySQL NOW(): <?php echo htmlspecialchars((string) $time_diag['mysql_now']); ?></div>
                    <div>MySQL session TZ: <?php echo htmlspecialchars((string) ($time_diag['mysql_session_tz'] ?? '—')); ?></div>
                <?php endif; ?>
                <?php if ($time_diag['punched_at_column'] !== null): ?>
                    <div>punched_at column: <?php echo htmlspecialchars((string) $time_diag['punched_at_column']); ?></div>
                <?php endif; ?>
            </div>
            <p class="form-hint" style="margin-top:14px;">If “Today label” does not match Windows server date, fix Windows date/time zone, then restart IIS.</p>
            <a href="setup.php" class="btn btn-block" style="margin-top:12px;">Run full setup</a>
            <a href="index.php" class="btn btn-outline btn-block" style="margin-top:8px;">Back to login</a>
        </div>
    </div>
</body>
</html>
