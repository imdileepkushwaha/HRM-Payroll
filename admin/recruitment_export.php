<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require 'config.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/audit_helper.php';
require_permission('recruitment', 'recruitment.php');

$branch_id = get_active_branch_id();
$jobs = get_job_openings($conn, $branch_id);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="recruitment_export_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Job ID', 'Title', 'Branch', 'Department', 'Status', 'Openings', 'Candidate', 'Email', 'Phone', 'Stage', 'Applied']);

foreach ($jobs as $job) {
    $candidates = get_candidates_for_job($conn, (int) $job['id']);
    if ($candidates === []) {
        fputcsv($out, [
            $job['id'],
            $job['title'],
            $job['branch_id'],
            $job['department_name'] ?? '',
            $job['status'] ?? '',
            $job['openings_count'] ?? 1,
            '', '', '', '', '',
        ]);
        continue;
    }
    foreach ($candidates as $c) {
        fputcsv($out, [
            $job['id'],
            $job['title'],
            $job['branch_id'],
            $job['department_name'] ?? '',
            $job['status'] ?? '',
            $job['openings_count'] ?? 1,
            $c['name'] ?? '',
            $c['email'] ?? '',
            $c['phone'] ?? '',
            $c['stage'] ?? '',
            $c['created_at'] ?? '',
        ]);
    }
}

fclose($out);
log_admin_action($conn, 'export_csv', 'recruitment', '', 'jobs and candidates');
exit;
