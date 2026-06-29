<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require 'config.php';
require_once 'includes/hrm_modules_helper.php';
require_once 'includes/audit_helper.php';
require_permission('performance', 'performance.php');

$branch_id = get_active_branch_id();
$cycle_id = (int) ($_GET['cycle_id'] ?? 0);
$cycles = get_review_cycles($conn);

header('Content-Type: text/csv; charset=utf-8');
$filename = $cycle_id > 0 ? 'performance_cycle_' . $cycle_id . '.csv' : 'performance_all_cycles.csv';
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Cycle', 'Period start', 'Period end', 'Employee', 'Department', 'Reviewer', 'Status', 'Overall rating', 'KRA summary', 'Manager notes', 'Self-review notes']);

$export_cycles = $cycle_id > 0 ? array_filter($cycles, static fn($c) => (int) $c['id'] === $cycle_id) : $cycles;

foreach ($export_cycles as $cycle) {
    $reviews = get_performance_reviews_for_cycle($conn, (int) $cycle['id'], $branch_id);
    foreach ($reviews as $r) {
        fputcsv($out, [
            $cycle['name'],
            $cycle['period_start'],
            $cycle['period_end'],
            $r['emp_name'] ?? $r['emp_id'],
            $r['department'] ?? '',
            $r['reviewer_name'] ?? $r['reviewer_emp_id'] ?? '',
            $r['status'] ?? '',
            $r['overall_rating'] ?? '',
            $r['kra_summary'] ?? '',
            $r['manager_notes'] ?? '',
            $r['employee_self_notes'] ?? '',
        ]);
    }
}

fclose($out);
log_admin_action($conn, 'export_csv', 'performance', (string) $cycle_id, 'reviews');
exit;
