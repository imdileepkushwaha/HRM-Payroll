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

define('PAYROLL_DB_DRIVER', 'mysql');
define('PAYROLL_MYSQL_HOST', '127.0.0.1');
define('PAYROLL_MYSQL_DATABASE', 'hrm_db');
define('PAYROLL_MYSQL_USER', 'root');
define('PAYROLL_MYSQL_PASS', '');

/** Legacy MSSQL source — used only by scripts/migrate_mssql_to_mysql.php */
define('PAYROLL_MSSQL_SERVER', 'localhost');
define('PAYROLL_MSSQL_DATABASE', 'payroll_db');
define('PAYROLL_MSSQL_USER', 'payroll_db');
define('PAYROLL_MSSQL_PASS', 'OfWwvvtwsF66%*3h');

if (!defined('PAYROLL_ALLOW_SETUP_TOOLS')) {
    define('PAYROLL_ALLOW_SETUP_TOOLS', true);
}

$conn = payroll_open_mysql_connection(
    PAYROLL_MYSQL_HOST,
    PAYROLL_MYSQL_DATABASE,
    PAYROLL_MYSQL_USER,
    PAYROLL_MYSQL_PASS
);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

require_once __DIR__ . '/includes/schema.php';
ensure_database_schema($conn);
require_once __DIR__ . '/includes/payroll_extensions.php';
require_once __DIR__ . '/includes/branch_helper.php';
require_once __DIR__ . '/includes/weekoff_roster_helper.php';
require_once __DIR__ . '/includes/employee_portal_helper.php';

$GLOBALS['pk_db_conn'] = $conn;
