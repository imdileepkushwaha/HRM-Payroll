<?php

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/settings_helper.php';
require_once __DIR__ . '/salary_helper.php';

function payroll_hr_notify_emails(array $settings): array
{
    $emails = [];
    foreach (['hr_notify_emails', 'company_email', 'smtp_from_email'] as $key) {
        $raw = trim($settings[$key] ?? '');
        if ($raw === '') {
            continue;
        }
        foreach (preg_split('/[\s,;]+/', $raw) as $part) {
            $part = trim($part);
            if ($part !== '' && filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower($part);
            }
        }
    }
    return array_values(array_unique($emails));
}

function payroll_send_hr_notification(array $settings, string $subject, string $html_body): void
{
    if (!smtp_is_configured($settings)) {
        return;
    }
    $company = trim($settings['company_name'] ?? '') ?: 'Payroll';
    foreach (payroll_hr_notify_emails($settings) as $email) {
        @send_email_smtp($settings, $email, $company, $subject, $html_body);
    }
}

function notify_exit_initiated($conn, array $settings, array $exit_row, string $admin): void
{
    $name = $exit_row['emp_name'] ?? $exit_row['name'] ?? $exit_row['emp_id'] ?? 'Employee';
    $subject = 'Exit initiated: ' . $name;
    $body = '<p><strong>' . htmlspecialchars($name) . '</strong> exit process started by ' . htmlspecialchars($admin) . '.</p>'
        . '<p>Last working day: ' . htmlspecialchars($exit_row['last_working_day'] ?? '—') . '</p>'
        . '<p>Review F&amp;F in the admin portal.</p>';
    payroll_send_hr_notification($settings, $subject, $body);
}

function notify_new_expense_claim($conn, array $settings, array $claim): void
{
    $subject = 'New expense claim: ' . ($claim['emp_name'] ?? $claim['emp_id'] ?? '');
    $body = '<p>Expense claim pending approval.</p>'
        . '<p>Employee: <strong>' . htmlspecialchars($claim['emp_name'] ?? '') . '</strong></p>'
        . '<p>Amount: ' . htmlspecialchars(format_money((float) ($claim['amount'] ?? 0))) . '</p>'
        . '<p>Category: ' . htmlspecialchars($claim['category'] ?? '') . '</p>';
    payroll_send_hr_notification($settings, $subject, $body);
}

function notify_recruitment_application(array $settings, array $job, string $candidate_name, string $email): void
{
    $subject = 'New job application: ' . ($job['title'] ?? 'Position');
    $body = '<p>New candidate applied via careers page.</p>'
        . '<p>Job: <strong>' . htmlspecialchars($job['title'] ?? '') . '</strong></p>'
        . '<p>Name: ' . htmlspecialchars($candidate_name) . '</p>'
        . '<p>Email: ' . htmlspecialchars($email) . '</p>';
    payroll_send_hr_notification($settings, $subject, $body);
}

function notify_approval_pending_reminder(array $settings, int $count, string $section): void
{
    if ($count <= 0) {
        return;
    }
    $subject = $count . ' pending ' . $section . ' approval(s)';
    $body = '<p>You have <strong>' . (int) $count . '</strong> pending ' . htmlspecialchars($section) . ' item(s) awaiting review in the admin portal.</p>';
    payroll_send_hr_notification($settings, $subject, $body);
}
