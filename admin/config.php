<?php
if (!defined('PAYROLL_APP_TIMEZONE')) {
    define('PAYROLL_APP_TIMEZONE', 'Asia/Kolkata');
}
date_default_timezone_set(PAYROLL_APP_TIMEZONE);

if (isset($GLOBALS['pk_db_conn']) && $GLOBALS['pk_db_conn'] instanceof mysqli) {
    $conn = $GLOBALS['pk_db_conn'];
    return;
}

require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/config_env.php';

payroll_load_environment_config(__DIR__);
payroll_apply_config_defaults();

$conn = payroll_open_mysql_connection(
    PAYROLL_MYSQL_HOST,
    PAYROLL_MYSQL_DATABASE,
    PAYROLL_MYSQL_USER,
    PAYROLL_MYSQL_PASS
);

if ($conn->connect_error) {
    $env_label = defined('PAYROLL_ENV') ? PAYROLL_ENV : 'unknown';
    die(
        'Connection failed (' . $env_label . '): ' . $conn->connect_error
        . '. Check admin/config/' . $env_label . '.php'
    );
}

$conn->set_charset('utf8mb4');

require_once __DIR__ . '/includes/schema.php';
ensure_database_schema($conn);
require_once __DIR__ . '/includes/payroll_extensions.php';
require_once __DIR__ . '/includes/branch_helper.php';
require_once __DIR__ . '/includes/weekoff_roster_helper.php';
require_once __DIR__ . '/includes/employee_portal_helper.php';

$GLOBALS['pk_db_conn'] = $conn;
