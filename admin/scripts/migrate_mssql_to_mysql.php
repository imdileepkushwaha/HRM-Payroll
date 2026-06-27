<?php
/**
 * One-time copy: MSSQL payroll_db → MySQL hrm_db
 * Run: php scripts/migrate_mssql_to_mysql.php
 * Or open in browser when PAYROLL_ALLOW_SETUP_TOOLS is true.
 */
declare(strict_types=1);

$is_cli = PHP_SAPI === 'cli';

require_once dirname(__DIR__) . '/includes/db_connection.php';
require_once dirname(__DIR__) . '/includes/sql_dialect.php';

if ($is_cli) {
    if (!defined('PAYROLL_MYSQL_HOST')) {
        define('PAYROLL_MYSQL_HOST', '127.0.0.1');
        define('PAYROLL_MYSQL_DATABASE', 'hrm_db');
        define('PAYROLL_MYSQL_USER', 'root');
        define('PAYROLL_MYSQL_PASS', '');
        define('PAYROLL_MSSQL_SERVER', 'localhost');
        define('PAYROLL_MSSQL_DATABASE', 'payroll_db');
        define('PAYROLL_MSSQL_USER', '');
        define('PAYROLL_MSSQL_PASS', '');
    }
} else {
    require_once dirname(__DIR__) . '/config.php';
    if (!PAYROLL_ALLOW_SETUP_TOOLS) {
        http_response_code(403);
        die('Migration is disabled. Set PAYROLL_ALLOW_SETUP_TOOLS to true in config.php.');
    }
}

$mssql_server = defined('PAYROLL_MSSQL_SERVER') ? PAYROLL_MSSQL_SERVER : 'localhost';
$mssql_db = defined('PAYROLL_MSSQL_DATABASE') ? PAYROLL_MSSQL_DATABASE : 'payroll_db';
$mssql_user = defined('PAYROLL_MSSQL_USER') ? PAYROLL_MSSQL_USER : '';
$mssql_pass = defined('PAYROLL_MSSQL_PASS') ? PAYROLL_MSSQL_PASS : '';

$mysql_host = defined('PAYROLL_MYSQL_HOST') ? PAYROLL_MYSQL_HOST : '127.0.0.1';
$mysql_db = defined('PAYROLL_MYSQL_DATABASE') ? PAYROLL_MYSQL_DATABASE : 'hrm_db';
$mysql_user = defined('PAYROLL_MYSQL_USER') ? PAYROLL_MYSQL_USER : 'root';
$mysql_pass = defined('PAYROLL_MYSQL_PASS') ? PAYROLL_MYSQL_PASS : '';

$tables = [
    'branches',
    'leave_types',
    'admin_users',
    'employees',
    'settings',
    'attendance',
    'salary_slip_logs',
    'payroll_periods',
    'holidays',
    'employee_payroll_profiles',
    'employee_weekoff_days',
    'payroll_adjustments',
    'employee_profile_requests',
    'employee_attendance_requests',
    'employee_leave_requests',
    'employee_leave_balances',
    'leave_accruals_log',
    'employee_punches',
];

function migration_log(array &$log, string $level, string $message): void
{
    $log[] = ['level' => $level, 'message' => $message];
    if (PHP_SAPI === 'cli') {
        fwrite(STDOUT, '[' . strtoupper($level) . '] ' . $message . PHP_EOL);
    }
}

function migration_table_columns($conn, string $table, bool $is_mssql): array
{
    $columns = [];
    if ($is_mssql) {
        $stmt = $conn->prepare(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION'
        );
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $columns[] = $row['COLUMN_NAME'];
        }
        return $columns;
    }

    $res = $conn->query('SHOW COLUMNS FROM `' . $conn->real_escape_string($table) . '`');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function migration_normalize_value($value)
{
    if ($value === null) {
        return null;
    }
    if (is_string($value) && strcasecmp($value, '0000-00-00 00:00:00') === 0) {
        return null;
    }
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }
    return $value;
}

function migration_copy_table($mssql, mysqli $mysql, string $table, array &$log): int
{
    $source_cols = migration_table_columns($mssql, $table, true);
    $target_cols = migration_table_columns($mysql, $table, false);
    if ($source_cols === [] || $target_cols === []) {
        migration_log($log, 'warn', "Skip {$table}: missing on source or target.");
        return 0;
    }

    $cols = array_values(array_intersect($source_cols, $target_cols));
    if ($cols === []) {
        migration_log($log, 'warn', "Skip {$table}: no matching columns.");
        return 0;
    }

    $mysql->query('DELETE FROM `' . $table . '`');
    $col_list = implode(', ', array_map(static fn ($c) => '`' . $c . '`', $cols));
    $select_sql = 'SELECT ' . implode(', ', $cols) . ' FROM ' . $table;
    $res = $mssql->query($select_sql);
    if ($res === false) {
        migration_log($log, 'err', "Read failed for {$table}: " . ($mssql->error ?? 'unknown'));
        return 0;
    }

    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $insert_sql = "INSERT INTO `{$table}` ({$col_list}) VALUES ({$placeholders})";
    $stmt = $mysql->prepare($insert_sql);
    if (!$stmt) {
        migration_log($log, 'err', "Prepare failed for {$table}: " . $mysql->error);
        return 0;
    }

    $types = str_repeat('s', count($cols));
    $count = 0;
    $max_id = 0;

    while ($row = $res->fetch_assoc()) {
        $values = [];
        foreach ($cols as $col) {
            $val = migration_normalize_value($row[$col] ?? null);
            if ($col === 'id' && $val !== null) {
                $max_id = max($max_id, (int) $val);
            }
            $values[] = $val;
        }
        $stmt->bind_param($types, ...$values);
        if ($stmt->execute()) {
            $count++;
        } else {
            migration_log($log, 'err', "Insert failed for {$table}: " . $stmt->error);
        }
    }
    $stmt->close();

    if (in_array('id', $cols, true) && $max_id > 0) {
        $mysql->query('ALTER TABLE `' . $table . '` AUTO_INCREMENT = ' . ($max_id + 1));
    }

    migration_log($log, 'ok', "{$table}: {$count} row(s) copied.");
    return $count;
}

$log = [];

migration_log($log, 'info', 'Connecting to MSSQL (' . $mssql_db . ')...');
$mssql = payroll_open_mssql_connection(
    $mssql_server,
    $mssql_db,
    $mssql_user !== '' ? $mssql_user : null,
    $mssql_pass !== '' ? $mssql_pass : null
);
if ($mssql->connect_error) {
    migration_log($log, 'err', 'MSSQL connection failed: ' . $mssql->connect_error);
    if ($is_cli) {
        exit(1);
    }
    render_migration_page($log);
    exit;
}

migration_log($log, 'info', 'Connecting to MySQL (' . $mysql_db . ')...');
$mysql = payroll_open_mysql_connection($mysql_host, $mysql_db, $mysql_user, $mysql_pass);
if ($mysql->connect_error) {
    migration_log($log, 'err', 'MySQL connection failed: ' . $mysql->connect_error);
    if ($is_cli) {
        exit(1);
    }
    render_migration_page($log);
    exit;
}
$mysql->set_charset('utf8mb4');

define('PAYROLL_DB_DRIVER', 'mysql');
require_once dirname(__DIR__) . '/includes/schema.php';
migration_log($log, 'info', 'Ensuring MySQL schema in hrm_db...');
ensure_database_schema($mysql);

$mysql->query('SET FOREIGN_KEY_CHECKS=0');
$total = 0;
foreach ($tables as $table) {
    $total += migration_copy_table($mssql, $mysql, $table, $log);
}
$mysql->query('SET FOREIGN_KEY_CHECKS=1');

migration_log($log, 'info', "Done. {$total} total rows copied to MySQL database `{$mysql_db}`.");

if ($is_cli) {
    exit(0);
}

render_migration_page($log);

function render_migration_page(array $log): void
{
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSSQL → MySQL Migration</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="page-auth">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">P</div>
            <h2>Database migration</h2>
            <p class="login-subtitle">payroll_db (MSSQL) → hrm_db (MySQL)</p>
            <div class="setup-log">
                <?php foreach ($log as $entry): ?>
                    <div class="<?php echo $entry['level'] === 'ok' ? 'ok' : ($entry['level'] === 'err' ? 'err' : ''); ?>">
                        <?php echo htmlspecialchars($entry['message']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="../index.php" class="btn btn-block">Go to Login</a>
        </div>
    </div>
</body>
</html>
    <?php
}
