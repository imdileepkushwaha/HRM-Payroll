<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
header('Location: dashboard.php');
exit;
