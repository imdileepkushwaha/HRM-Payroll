<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
$_SESSION['flash_message'] = 'Salary slips are now shown in the employee portal. Email sending has been removed.';
$_SESSION['flash_success'] = true;
header('Location: dashboard.php');
exit;
