<?php

/**
 * Random gradient per portal page — each page gets its own color for the session.
 */
function emp_portal_gradient_index(?string $page = null): int
{
    $page = $page ?: basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');

    if (!isset($_SESSION['emp_portal_gradients']) || !is_array($_SESSION['emp_portal_gradients'])) {
        $_SESSION['emp_portal_gradients'] = [];
    }

    if (!isset($_SESSION['emp_portal_gradients'][$page])) {
        $_SESSION['emp_portal_gradients'][$page] = random_int(0, 7);
    }

    return (int) $_SESSION['emp_portal_gradients'][$page];
}
