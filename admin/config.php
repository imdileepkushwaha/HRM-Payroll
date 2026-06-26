<?php
if (isset($GLOBALS['pk_db_conn']) && ($GLOBALS['pk_db_conn'] instanceof mysqli || $GLOBALS['pk_db_conn'] instanceof PayrollDbConnection)) {
    $conn = $GLOBALS['pk_db_conn'];
    return;
}

require_once __DIR__ . '/includes/db_connection.php';

define('PAYROLL_DB_DRIVER', 'mssql');
define('PAYROLL_MSSQL_SERVER', 'localhost');
define('PAYROLL_MSSQL_DATABASE', 'ONtime_Att');
define('PAYROLL_MSSQL_USER', '');
define('PAYROLL_MSSQL_PASS', '');

if (!defined('PAYROLL_ALLOW_SETUP_TOOLS')) {
    define('PAYROLL_ALLOW_SETUP_TOOLS', true);
}

$conn = payroll_open_mssql_connection(
    PAYROLL_MSSQL_SERVER,
    PAYROLL_MSSQL_DATABASE,
    PAYROLL_MSSQL_USER !== '' ? PAYROLL_MSSQL_USER : null,
    PAYROLL_MSSQL_PASS !== '' ? PAYROLL_MSSQL_PASS : null
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
