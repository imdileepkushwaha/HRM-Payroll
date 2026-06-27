<?php

function payroll_setup_render_error(int $code, string $title, string $message, array $hints = []): void
{
    http_response_code($code);
    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> — Payroll</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-auth">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">P</div>
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <div class="alert alert-error"><?php echo nl2br(htmlspecialchars($message)); ?></div>
            <?php if ($hints !== []): ?>
                <div class="setup-log" style="margin-top:16px;text-align:left;">
                    <?php foreach ($hints as $hint): ?>
                        <div class="ok"><?php echo htmlspecialchars($hint); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a href="index.php" class="btn btn-block" style="margin-top:16px;">Back to login</a>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}

function payroll_setup_time_diagnostics($conn = null): array
{
    $php_now = payroll_app_now();
    $diag = [
        'app_timezone' => PAYROLL_APP_TIMEZONE,
        'app_tz_offset' => PAYROLL_APP_TZ_OFFSET,
        'php_now' => $php_now->format('Y-m-d H:i:s'),
        'php_date' => $php_now->format('l, j M Y'),
        'php_ini_timezone' => ini_get('date.timezone') ?: '(not set)',
        'env' => defined('PAYROLL_ENV') ? PAYROLL_ENV : 'unknown',
        'mysql_now' => null,
        'mysql_session_tz' => null,
        'mysql_global_tz' => null,
        'punched_at_column' => null,
        'db_error' => null,
    ];

    if ($conn === null || !empty($conn->connect_error)) {
        $diag['db_error'] = $conn && $conn->connect_error ? $conn->connect_error : 'No database connection';

        return $diag;
    }

    if (defined('PAYROLL_DB_DRIVER') && PAYROLL_DB_DRIVER === 'mssql') {
        $res = $conn->query("SELECT CONVERT(VARCHAR(19), GETDATE(), 120) AS db_now");
        if ($res && ($row = $res->fetch_assoc())) {
            $diag['mysql_now'] = $row['db_now'] ?? null;
        }

        return $diag;
    }

    $res = $conn->query(
        "SELECT DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s') AS db_now,
                @@session.time_zone AS session_tz,
                @@global.time_zone AS global_tz"
    );
    if ($res && ($row = $res->fetch_assoc())) {
        $diag['mysql_now'] = $row['db_now'] ?? null;
        $diag['mysql_session_tz'] = $row['session_tz'] ?? null;
        $diag['mysql_global_tz'] = $row['global_tz'] ?? null;
    }

    $type_res = $conn->query("
        SELECT DATA_TYPE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'employee_punches'
          AND COLUMN_NAME = 'punched_at'
        LIMIT 1
    ");
    if ($type_res && ($type_row = $type_res->fetch_assoc())) {
        $diag['punched_at_column'] = $type_row['DATA_TYPE'] ?? null;
    }

    return $diag;
}

function payroll_setup_bootstrap(): array
{
    require_once __DIR__ . '/includes/app_timezone.php';
    require_once __DIR__ . '/includes/config_env.php';

    $env = payroll_load_environment_config(__DIR__);
    payroll_apply_config_defaults();

    $config_file = __DIR__ . '/config/' . $env . '.php';
    if (!is_file($config_file)) {
        payroll_setup_render_error(
            500,
            'Missing server config',
            'Database config file not found: admin/config/' . $env . '.php',
            [
                'On live server: copy admin/config/production.example.php to admin/config/production.php',
                'Fill in MySQL host, database, user and password.',
            ]
        );
    }

    if (!defined('PAYROLL_ALLOW_SETUP_TOOLS') || !PAYROLL_ALLOW_SETUP_TOOLS) {
        payroll_setup_render_error(
            403,
            'Setup is disabled',
            'Setup tools are turned off on this server.',
            [
                'Edit admin/config/production.php on the server (via FTP / file manager).',
                'Add or change this line: define(\'PAYROLL_ALLOW_SETUP_TOOLS\', true);',
                'Save the file, reload this page, then set it back to false when finished.',
                'Tip: production.php is not in git — you must upload/edit it on the server directly.',
            ]
        );
    }

    return ['env' => $env, 'config_file' => $config_file];
}
