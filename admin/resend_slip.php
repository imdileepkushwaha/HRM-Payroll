<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
$emp_id = trim($_GET['emp_id'] ?? $_POST['emp_id'] ?? '');
$redirect = 'employee_view.php';
if ($emp_id !== '') {
    $redirect .= '?emp_id=' . urlencode($emp_id);
    if (!empty($_GET['month']) || !empty($_POST['month'])) {
        $redirect .= '&month=' . (int) ($_GET['month'] ?? $_POST['month']);
    }
    if (!empty($_GET['year']) || !empty($_POST['year'])) {
        $redirect .= '&year=' . (int) ($_GET['year'] ?? $_POST['year']);
    }
}
$_SESSION['flash_message'] = 'Salary slips are available in the employee portal. Email sending has been removed.';
$_SESSION['flash_success'] = true;
header('Location: ' . $redirect);
exit;
