<?php

/**
 * Resolve runtime environment: local (XAMPP/dev) or production (live server).
 */
function payroll_resolve_environment(): string
{
    if (defined('PAYROLL_ENV')) {
        return PAYROLL_ENV === 'production' ? 'production' : 'local';
    }

    $forced = getenv('PAYROLL_ENV');
    if ($forced === 'production' || $forced === 'local') {
        return $forced;
    }

    if (PHP_SAPI === 'cli') {
        return 'local';
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    if ($host === '') {
        return 'production';
    }

    $hostWithoutPort = preg_replace('/:\d+$/', '', $host) ?? $host;

    $localHosts = ['localhost', '127.0.0.1', '[::1]'];
    if (in_array($hostWithoutPort, $localHosts, true)) {
        return 'local';
    }

    if (preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2\d|3[01])\.)/', $hostWithoutPort)) {
        return 'local';
    }

    return 'production';
}

/**
 * Load environment-specific DB settings from admin/config/{local|production}.php
 */
function payroll_load_environment_config(string $base_dir): string
{
    $env = payroll_resolve_environment();

    if (!defined('PAYROLL_ENV')) {
        define('PAYROLL_ENV', $env);
    }

    $env_file = $base_dir . '/config/' . $env . '.php';
    if (is_file($env_file)) {
        require $env_file;
        return $env;
    }

    // Legacy single-file override (older installs).
    $legacy = $base_dir . '/config.local.php';
    if (is_file($legacy)) {
        require $legacy;
        return $env;
    }

    if ($env === 'production') {
        http_response_code(500);
        die(
            'Missing database config: create admin/config/production.php on the server '
            . '(copy from admin/config/production.example.php).'
        );
    }

    return $env;
}

function payroll_apply_config_defaults(): void
{
    if (!defined('PAYROLL_DB_DRIVER')) {
        define('PAYROLL_DB_DRIVER', 'mysql');
    }

    if (!defined('PAYROLL_MYSQL_HOST')) {
        define('PAYROLL_MYSQL_HOST', PAYROLL_ENV === 'local' ? '127.0.0.1' : 'localhost');
    }
    if (!defined('PAYROLL_MYSQL_DATABASE')) {
        define('PAYROLL_MYSQL_DATABASE', PAYROLL_ENV === 'local' ? 'hrm_db' : 'payroll_db');
    }
    if (!defined('PAYROLL_MYSQL_USER')) {
        define('PAYROLL_MYSQL_USER', PAYROLL_ENV === 'local' ? 'root' : '');
    }
    if (!defined('PAYROLL_MYSQL_PASS')) {
        define('PAYROLL_MYSQL_PASS', '');
    }

    if (!defined('PAYROLL_MSSQL_SERVER')) {
        define('PAYROLL_MSSQL_SERVER', 'localhost');
    }
    if (!defined('PAYROLL_MSSQL_DATABASE')) {
        define('PAYROLL_MSSQL_DATABASE', 'payroll_db');
    }
    if (!defined('PAYROLL_MSSQL_USER')) {
        define('PAYROLL_MSSQL_USER', '');
    }
    if (!defined('PAYROLL_MSSQL_PASS')) {
        define('PAYROLL_MSSQL_PASS', '');
    }

    if (!defined('PAYROLL_ALLOW_SETUP_TOOLS')) {
        define('PAYROLL_ALLOW_SETUP_TOOLS', PAYROLL_ENV === 'local');
    }
}
