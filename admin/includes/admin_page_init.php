<?php

/**
 * Load session, DB, enforce permission, then render admin header.
 */
function admin_page_init(string $permission, string $redirect = 'dashboard.php'): void
{
    require_once __DIR__ . '/session_auth.php';
    enforce_admin_session();
    require __DIR__ . '/../config.php';

    // config.php is loaded inside this function, so publish $conn to the page scope.
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        $conn = $GLOBALS['pk_db_conn'] ?? null;
    }

    require_permission($permission, $redirect);
    require __DIR__ . '/header.php';
}
