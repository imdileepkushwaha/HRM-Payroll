<?php

function payroll_db_driver()
{
    return defined('PAYROLL_DB_DRIVER') ? PAYROLL_DB_DRIVER : 'mysql';
}

function payroll_is_mssql()
{
    return payroll_db_driver() === 'mssql';
}

function payroll_table_unique_keys()
{
    return [
        'branches' => ['code'],
        'admin_users' => ['username'],
        'employees' => ['emp_id'],
        'attendance' => ['emp_id', 'attendance_date'],
        'settings' => ['setting_key'],
        'salary_slip_logs' => ['emp_id', 'period_year', 'period_month'],
        'payroll_periods' => ['branch_id', 'period_year', 'period_month'],
        'holidays' => ['branch_id', 'calendar_date'],
        'leave_types' => ['code'],
        'employee_payroll_profiles' => ['emp_id'],
        'employee_weekoff_days' => ['emp_id', 'off_date'],
        'employee_leave_balances' => ['emp_id', 'leave_type'],
        'leave_accruals_log' => ['period_year', 'period_month'],
    ];
}

function payroll_translate_sql($sql)
{
    if (!payroll_is_mssql()) {
        return $sql;
    }

    $sql = str_replace('`', '', $sql);
    $sql = preg_replace('/\bNOW\s*\(\s*\)/i', 'GETDATE()', $sql);
    $sql = preg_replace('/\bDATABASE\s*\(\s*\)/i', 'DB_NAME()', $sql);

    if (preg_match('/^SHOW\s+COLUMNS\s+FROM\s+([^\s]+)\s+LIKE\s+[\'"]([^\'"]+)[\'"]/i', trim($sql), $m)) {
        $table = trim($m[1], '[]');
        $column = $m[2];
        return "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$table}' AND COLUMN_NAME = '{$column}'";
    }

    if (preg_match('/^SHOW\s+TABLES\s+LIKE\s+[\'"]([^\'"]+)[\'"]/i', trim($sql), $m)) {
        $table = $m[1];
        return "SELECT TABLE_NAME AS [Tables_in_db] FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '{$table}'";
    }

    if (preg_match('/\bINSERT\s+IGNORE\s+INTO\s+/i', $sql)) {
        return payroll_convert_insert_ignore_to_merge($sql);
    }

    if (preg_match('/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/i', $sql)) {
        return payroll_convert_upsert_to_merge($sql);
    }

    if (preg_match('/\s+LIMIT\s+(\d+)\s*$/i', $sql, $m)) {
        $sql = preg_replace('/\s+LIMIT\s+\d+\s*$/i', '', $sql);
        $sql .= ' OFFSET 0 ROWS FETCH NEXT ' . (int) $m[1] . ' ROWS ONLY';
    }

    return $sql;
}

function payroll_convert_insert_ignore_to_merge($sql)
{
    if (!preg_match(
        '/INSERT\s+IGNORE\s+INTO\s+([^\s(]+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/is',
        $sql,
        $m
    )) {
        return $sql;
    }

    $table = trim($m[1], '[]');
    $cols = payroll_parse_sql_list($m[2]);
    $vals = payroll_parse_sql_list($m[3]);
    $keys = payroll_table_unique_keys()[$table] ?? [$cols[0]];

    $source_cols = [];
    foreach ($cols as $idx => $col) {
        $source_cols[] = trim($vals[$idx]) . ' AS ' . $col;
    }

    $on = [];
    foreach ($keys as $key) {
        $on[] = 't.' . $key . ' = s.' . $key;
    }

    $insert_cols = implode(', ', $cols);
    $insert_vals = implode(', ', array_map(function ($col) {
        return 's.' . $col;
    }, $cols));

    return 'MERGE ' . $table . ' AS t USING (SELECT ' . implode(', ', $source_cols) . ') AS s ON '
        . implode(' AND ', $on)
        . ' WHEN NOT MATCHED THEN INSERT (' . $insert_cols . ') VALUES (' . $insert_vals . ')';
}

function payroll_convert_upsert_to_merge($sql)
{
    if (!preg_match(
        '/INSERT\s+INTO\s+([^\s(]+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)\s*ON\s+DUPLICATE\s+KEY\s+UPDATE\s*(.+)$/is',
        $sql,
        $m
    )) {
        return $sql;
    }

    $table = trim($m[1], '[]');
    $cols = payroll_parse_sql_list($m[2]);
    $vals = payroll_parse_sql_list($m[3]);
    $updates = trim($m[4]);
    $keys = payroll_table_unique_keys()[$table] ?? [$cols[0]];

    $source_cols = [];
    foreach ($cols as $idx => $col) {
        $source_cols[] = trim($vals[$idx]) . ' AS ' . $col;
    }

    $on = [];
    foreach ($keys as $key) {
        $on[] = 't.' . $key . ' = s.' . $key;
    }

    $set_parts = [];
    foreach (explode(',', $updates) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        if (preg_match('/^([a-zA-Z0-9_]+)\s*=\s*VALUES\s*\(\s*([a-zA-Z0-9_]+)\s*\)/i', $part, $um)) {
            $set_parts[] = 't.' . $um[1] . ' = s.' . $um[2];
            continue;
        }
        if (preg_match('/^([a-zA-Z0-9_]+)\s*=\s*([a-zA-Z0-9_]+)\s*\+\s*(.+)$/i', $part, $um)) {
            $set_parts[] = 't.' . $um[1] . ' = t.' . $um[2] . ' + ' . trim($um[3]);
            continue;
        }
        if (preg_match('/^([a-zA-Z0-9_]+)\s*=\s*(.+)$/i', $part, $um)) {
            $rhs = trim($um[2]);
            if (strcasecmp($rhs, 'NULL') === 0) {
                $set_parts[] = 't.' . $um[1] . ' = NULL';
            } elseif (preg_match('/^[\'"].*[\'"]$/', $rhs) || preg_match('/^[0-9.]+$/', $rhs) || strcasecmp($rhs, 'CURRENT_TIMESTAMP') === 0 || strcasecmp($rhs, 'GETDATE()') === 0) {
                $set_parts[] = 't.' . $um[1] . ' = ' . $rhs;
            } else {
                $set_parts[] = 't.' . $um[1] . ' = s.' . $rhs;
            }
        }
    }

    $insert_cols = implode(', ', $cols);
    $insert_vals = implode(', ', array_map(function ($col) {
        return 's.' . $col;
    }, $cols));

    return 'MERGE ' . $table . ' AS t USING (SELECT ' . implode(', ', $source_cols) . ') AS s ON '
        . implode(' AND ', $on)
        . ' WHEN MATCHED THEN UPDATE SET ' . implode(', ', $set_parts)
        . ' WHEN NOT MATCHED THEN INSERT (' . $insert_cols . ') VALUES (' . $insert_vals . ')';
}

function payroll_parse_sql_list($list)
{
    $items = [];
    foreach (explode(',', $list) as $item) {
        $items[] = trim($item);
    }
    return $items;
}
