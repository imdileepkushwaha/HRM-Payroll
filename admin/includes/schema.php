<?php

function ensure_database_schema($conn)
{
    $messages = [];

    if (payroll_is_mssql()) {
        ensure_punch_schema($conn);
        seed_default_branches($conn);
        seed_default_leave_types($conn);
        seed_default_settings($conn);
        seed_employee_portal_passwords($conn);
        return $messages;
    }

    $tables = [
        "CREATE TABLE IF NOT EXISTS `branches` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(20) NOT NULL,
            `name` varchar(100) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employees` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(150) DEFAULT NULL,
            `phone` varchar(30) DEFAULT NULL,
            `department` varchar(100) DEFAULT NULL,
            `designation` varchar(100) DEFAULT NULL,
            `base_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
            `joined_date` date DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_id` (`emp_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `attendance` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `attendance_date` date NOT NULL,
            `status` varchar(20) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_date` (`emp_id`, `attendance_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `settings` (
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `salary_slip_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `period_month` tinyint NOT NULL,
            `period_year` smallint NOT NULL,
            `net_salary` decimal(12,2) NOT NULL,
            `sent_to` varchar(150) DEFAULT NULL,
            `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` varchar(20) NOT NULL DEFAULT 'sent',
            PRIMARY KEY (`id`),
            KEY `period` (`period_year`, `period_month`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `payroll_periods` (
            `period_year` smallint NOT NULL,
            `period_month` tinyint NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'open',
            `approved_by` varchar(50) DEFAULT NULL,
            `approved_at` datetime DEFAULT NULL,
            `locked_by` varchar(50) DEFAULT NULL,
            `locked_at` datetime DEFAULT NULL,
            `notes` text,
            PRIMARY KEY (`period_year`, `period_month`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `holidays` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `calendar_date` date NOT NULL,
            `name` varchar(120) NOT NULL,
            `kind` varchar(20) NOT NULL DEFAULT 'holiday',
            PRIMARY KEY (`id`),
            UNIQUE KEY `calendar_date` (`calendar_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `leave_types` (
            `code` varchar(10) NOT NULL,
            `name` varchar(60) NOT NULL,
            `paid_credit` decimal(3,2) NOT NULL DEFAULT 1.00,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_payroll_profiles` (
            `emp_id` varchar(50) NOT NULL,
            `use_custom` tinyint(1) NOT NULL DEFAULT 0,
            `pct_basic` decimal(5,2) DEFAULT NULL,
            `pct_hra` decimal(5,2) DEFAULT NULL,
            `pct_conveyance` decimal(5,2) DEFAULT NULL,
            `pct_medical` decimal(5,2) DEFAULT NULL,
            `pct_special` decimal(5,2) DEFAULT NULL,
            `pf_percent` decimal(5,2) DEFAULT NULL,
            `professional_tax` decimal(10,2) DEFAULT NULL,
            `portal_password_hash` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`emp_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_weekoff_days` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `off_date` date NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_off_date` (`emp_id`, `off_date`),
            KEY `branch_month` (`branch_id`, `off_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `payroll_adjustments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `period_year` smallint NOT NULL,
            `period_month` tinyint NOT NULL,
            `adj_type` varchar(20) NOT NULL,
            `label` varchar(100) NOT NULL,
            `amount` decimal(12,2) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `emp_period` (`emp_id`, `period_year`, `period_month`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_profile_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `proposed_email` varchar(150) DEFAULT NULL,
            `proposed_phone` varchar(30) DEFAULT NULL,
            `proposed_pan` varchar(20) DEFAULT NULL,
            `proposed_bank_account` varchar(40) DEFAULT NULL,
            `proposed_bank_ifsc` varchar(20) DEFAULT NULL,
            `proposed_bank_name` varchar(100) DEFAULT NULL,
            `employee_note` text,
            `request_status` varchar(20) NOT NULL DEFAULT 'pending',
            `reviewed_by` varchar(50) DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `review_note` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `branch_status` (`branch_id`, `request_status`),
            KEY `emp_status` (`emp_id`, `request_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_attendance_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `attendance_date` date NOT NULL,
            `status` varchar(20) NOT NULL,
            `leave_type` varchar(10) DEFAULT NULL,
            `employee_note` text,
            `request_status` varchar(20) NOT NULL DEFAULT 'pending',
            `reviewed_by` varchar(50) DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `review_note` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `branch_status` (`branch_id`, `request_status`),
            KEY `emp_month` (`emp_id`, `attendance_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_leave_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `from_date` date NOT NULL,
            `to_date` date NOT NULL,
            `leave_type` varchar(10) NOT NULL,
            `employee_note` text,
            `request_status` varchar(20) NOT NULL DEFAULT 'pending',
            `reviewed_by` varchar(50) DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `review_note` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `branch_status` (`branch_id`, `request_status`),
            KEY `emp_dates` (`emp_id`, `from_date`, `to_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_leave_balances` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `leave_type` varchar(10) NOT NULL,
            `balance` decimal(5,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_leave` (`emp_id`, `leave_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `leave_accruals_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `period_year` smallint NOT NULL,
            `period_month` tinyint NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `period` (`period_year`, `period_month`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_face_biometrics` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `face_descriptor` json NOT NULL,
            `enrolled_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_id` (`emp_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_document_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `doc_type` varchar(40) NOT NULL,
            `doc_label` varchar(120) DEFAULT NULL,
            `file_path` varchar(255) NOT NULL,
            `original_filename` varchar(255) NOT NULL,
            `mime_type` varchar(100) NOT NULL,
            `file_size` int(11) NOT NULL DEFAULT 0,
            `employee_note` text,
            `request_status` varchar(20) NOT NULL DEFAULT 'pending',
            `reviewed_by` varchar(50) DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `review_note` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `branch_status` (`branch_id`, `request_status`),
            KEY `emp_status` (`emp_id`, `request_status`),
            KEY `emp_type_status` (`emp_id`, `doc_type`, `request_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_documents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `doc_type` varchar(40) NOT NULL,
            `doc_label` varchar(120) DEFAULT NULL,
            `file_path` varchar(255) NOT NULL,
            `original_filename` varchar(255) NOT NULL,
            `mime_type` varchar(100) NOT NULL,
            `file_size` int(11) NOT NULL DEFAULT 0,
            `approved_by` varchar(50) DEFAULT NULL,
            `approved_at` datetime NOT NULL,
            `request_id` int(11) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `emp_active` (`emp_id`, `is_active`),
            KEY `emp_type_active` (`emp_id`, `doc_type`, `is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `announcements` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `branch_id` int(11) DEFAULT NULL,
            `title` varchar(200) NOT NULL,
            `body` text NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
            `expires_at` date DEFAULT NULL,
            `created_by` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `branch_active` (`branch_id`, `is_active`),
            KEY `expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `departments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(20) NOT NULL,
            `name` varchar(100) NOT NULL,
            `branch_id` int(11) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `dept_code_branch` (`code`, `branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `designations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `department_id` int(11) DEFAULT NULL,
            `code` varchar(20) NOT NULL,
            `name` varchar(100) NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `desig_code_dept` (`code`, `department_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `admin_roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(40) NOT NULL,
            `name` varchar(80) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `is_system` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `admin_role_permissions` (
            `role_id` int(11) NOT NULL,
            `permission_key` varchar(60) NOT NULL,
            PRIMARY KEY (`role_id`, `permission_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `job_openings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `branch_id` int(11) NOT NULL,
            `title` varchar(150) NOT NULL,
            `department_id` int(11) DEFAULT NULL,
            `designation_id` int(11) DEFAULT NULL,
            `description` text,
            `openings_count` int(11) NOT NULL DEFAULT 1,
            `status` varchar(20) NOT NULL DEFAULT 'open',
            `created_by` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `branch_status` (`branch_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `candidates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `job_opening_id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(150) DEFAULT NULL,
            `phone` varchar(30) DEFAULT NULL,
            `resume_path` varchar(255) DEFAULT NULL,
            `resume_filename` varchar(255) DEFAULT NULL,
            `stage` varchar(30) NOT NULL DEFAULT 'applied',
            `notes` text,
            `hired_emp_id` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `job_stage` (`job_opening_id`, `stage`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `review_cycles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(120) NOT NULL,
            `period_start` date NOT NULL,
            `period_end` date NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'draft',
            `created_by` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `performance_reviews` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cycle_id` int(11) NOT NULL,
            `emp_id` varchar(50) NOT NULL,
            `reviewer_emp_id` varchar(50) DEFAULT NULL,
            `kra_summary` text,
            `kpi_data` json DEFAULT NULL,
            `employee_self_notes` text,
            `manager_notes` text,
            `overall_rating` decimal(4,2) DEFAULT NULL,
            `status` varchar(30) NOT NULL DEFAULT 'pending',
            `reviewed_at` datetime DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `cycle_emp` (`cycle_id`, `emp_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `expense_claims` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `claim_date` date NOT NULL,
            `category` varchar(60) NOT NULL,
            `amount` decimal(12,2) NOT NULL,
            `description` text,
            `receipt_path` varchar(255) DEFAULT NULL,
            `receipt_filename` varchar(255) DEFAULT NULL,
            `request_status` varchar(20) NOT NULL DEFAULT 'pending',
            `reviewed_by` varchar(50) DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `review_note` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `branch_status` (`branch_id`, `request_status`),
            KEY `emp_status` (`emp_id`, `request_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `assets` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `asset_tag` varchar(40) NOT NULL,
            `name` varchar(120) NOT NULL,
            `category` varchar(60) NOT NULL DEFAULT 'General',
            `serial_no` varchar(80) DEFAULT NULL,
            `branch_id` int(11) NOT NULL,
            `purchase_date` date DEFAULT NULL,
            `asset_value` decimal(12,2) DEFAULT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'available',
            `notes` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `asset_tag` (`asset_tag`),
            KEY `branch_status` (`branch_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `asset_assignments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `asset_id` int(11) NOT NULL,
            `emp_id` varchar(50) NOT NULL,
            `assigned_at` datetime NOT NULL,
            `returned_at` datetime DEFAULT NULL,
            `condition_notes` text,
            `assigned_by` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `asset_active` (`asset_id`, `returned_at`),
            KEY `emp_active` (`emp_id`, `returned_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_exits` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `exit_type` varchar(30) NOT NULL DEFAULT 'resignation',
            `resignation_date` date DEFAULT NULL,
            `last_working_day` date NOT NULL,
            `reason` text,
            `status` varchar(30) NOT NULL DEFAULT 'initiated',
            `initiated_by` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `emp_status` (`emp_id`, `status`),
            KEY `branch_status` (`branch_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `fnf_settlements` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `exit_id` int(11) NOT NULL,
            `salary_due` decimal(12,2) NOT NULL DEFAULT 0.00,
            `leave_encashment` decimal(12,2) NOT NULL DEFAULT 0.00,
            `notice_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
            `deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
            `net_payable` decimal(12,2) NOT NULL DEFAULT 0.00,
            `status` varchar(20) NOT NULL DEFAULT 'draft',
            `notes` text,
            `approved_by` varchar(50) DEFAULT NULL,
            `approved_at` datetime DEFAULT NULL,
            `paid_at` datetime DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `exit_id` (`exit_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `admin_audit_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_username` varchar(50) NOT NULL,
            `action` varchar(80) NOT NULL,
            `entity_type` varchar(40) DEFAULT NULL,
            `entity_id` varchar(80) DEFAULT NULL,
            `detail` text,
            `ip_address` varchar(45) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `entity_type` (`entity_type`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_helpdesk_tickets` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `category` varchar(60) NOT NULL DEFAULT 'General',
            `subject` varchar(200) NOT NULL,
            `body` text NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'open',
            `admin_reply` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `emp_status` (`emp_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employee_wfh_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `wfh_date` date NOT NULL,
            `employee_note` text,
            `request_status` varchar(20) NOT NULL DEFAULT 'pending',
            `reviewed_by` varchar(50) DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `review_note` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `emp_status` (`emp_id`, `request_status`),
            KEY `branch_status` (`branch_id`, `request_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `punch_regularization_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `branch_id` int(11) NOT NULL,
            `punch_date` date NOT NULL,
            `requested_in_time` time DEFAULT NULL,
            `requested_out_time` time DEFAULT NULL,
            `employee_note` text,
            `request_status` varchar(20) NOT NULL DEFAULT 'pending',
            `reviewed_by` varchar(50) DEFAULT NULL,
            `reviewed_at` datetime DEFAULT NULL,
            `review_note` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `emp_status` (`emp_id`, `request_status`),
            KEY `branch_status` (`branch_id`, `request_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        $conn->query($sql);
    }

    $employee_columns = [
        'email' => "ALTER TABLE `employees` ADD COLUMN `email` varchar(150) DEFAULT NULL",
        'phone' => "ALTER TABLE `employees` ADD COLUMN `phone` varchar(30) DEFAULT NULL",
        'department' => "ALTER TABLE `employees` ADD COLUMN `department` varchar(100) DEFAULT NULL",
        'designation' => "ALTER TABLE `employees` ADD COLUMN `designation` varchar(100) DEFAULT NULL",
        'base_salary' => "ALTER TABLE `employees` ADD COLUMN `base_salary` decimal(12,2) NOT NULL DEFAULT 0.00",
        'joined_date' => "ALTER TABLE `employees` ADD COLUMN `joined_date` date DEFAULT NULL",
        'created_at' => "ALTER TABLE `employees` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'is_active' => "ALTER TABLE `employees` ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1",
        'pan' => "ALTER TABLE `employees` ADD COLUMN `pan` varchar(20) DEFAULT NULL",
        'bank_account' => "ALTER TABLE `employees` ADD COLUMN `bank_account` varchar(40) DEFAULT NULL",
        'bank_ifsc' => "ALTER TABLE `employees` ADD COLUMN `bank_ifsc` varchar(20) DEFAULT NULL",
        'bank_name' => "ALTER TABLE `employees` ADD COLUMN `bank_name` varchar(100) DEFAULT NULL",
        'grade' => "ALTER TABLE `employees` ADD COLUMN `grade` varchar(50) DEFAULT NULL",
        'esic_no' => "ALTER TABLE `employees` ADD COLUMN `esic_no` varchar(50) DEFAULT NULL",
        'uan_no' => "ALTER TABLE `employees` ADD COLUMN `uan_no` varchar(50) DEFAULT NULL",
        'pf_no' => "ALTER TABLE `employees` ADD COLUMN `pf_no` varchar(50) DEFAULT NULL",
        'department_id' => "ALTER TABLE `employees` ADD COLUMN `department_id` int(11) DEFAULT NULL",
        'designation_id' => "ALTER TABLE `employees` ADD COLUMN `designation_id` int(11) DEFAULT NULL",
        'manager_emp_id' => "ALTER TABLE `employees` ADD COLUMN `manager_emp_id` varchar(50) DEFAULT NULL",
    ];

    foreach ($employee_columns as $column => $sql) {
        if (!column_exists($conn, 'employees', $column)) {
            $conn->query($sql);
        }
    }

    $attendance_columns = [
        'leave_type' => "ALTER TABLE `attendance` ADD COLUMN `leave_type` varchar(10) DEFAULT NULL",
        'overtime_hours' => "ALTER TABLE `attendance` ADD COLUMN `overtime_hours` decimal(5,2) NOT NULL DEFAULT 0",
    ];
    foreach ($attendance_columns as $column => $sql) {
        if (!column_exists($conn, 'attendance', $column)) {
            $conn->query($sql);
        }
    }

    // Prevent repeated slip log rows per employee/period.
    // We keep only the latest entry and then enforce a unique key.
    if (!index_exists($conn, 'salary_slip_logs', 'emp_period_unique')) {
        // Delete older duplicates, keep the latest (highest sent_at; tie-breaker by id).
        $conn->query("
            DELETE l1 FROM salary_slip_logs l1
            INNER JOIN salary_slip_logs l2
                ON l1.emp_id = l2.emp_id
               AND l1.period_year = l2.period_year
               AND l1.period_month = l2.period_month
               AND (l1.sent_at < l2.sent_at OR (l1.sent_at = l2.sent_at AND l1.id < l2.id))
        ");
        $conn->query("ALTER TABLE `salary_slip_logs` ADD UNIQUE KEY `emp_period_unique` (`emp_id`, `period_year`, `period_month`)");
    }

    seed_default_branches($conn);
    migrate_branch_columns($conn);
    ensure_punch_schema($conn);

    seed_default_leave_types($conn);
    seed_default_settings($conn);
    seed_employee_portal_passwords($conn);

    $conn->query('ALTER TABLE `employee_document_requests` MODIFY COLUMN `doc_type` varchar(40) NOT NULL');
    $conn->query('ALTER TABLE `employee_documents` MODIFY COLUMN `doc_type` varchar(40) NOT NULL');

    if (!column_exists($conn, 'admin_users', 'role_id')) {
        $conn->query('ALTER TABLE `admin_users` ADD COLUMN `role_id` int(11) DEFAULT NULL');
    }

    seed_admin_roles_and_permissions($conn);
    migrate_employee_department_masters($conn);

    if (!column_exists($conn, 'employee_payroll_profiles', 'slip_email_enabled')) {
        $conn->query('ALTER TABLE `employee_payroll_profiles` ADD COLUMN `slip_email_enabled` tinyint(1) NOT NULL DEFAULT 1');
    }

    return $messages;
}

function seed_default_branches($conn)
{
    $branches = [
        ['INDRA', 'Indra Nagar'],
        ['ALAM', 'Alambagh'],
    ];
    foreach ($branches as $b) {
        $stmt = $conn->prepare('INSERT IGNORE INTO branches (code, name) VALUES (?, ?)');
        $stmt->bind_param('ss', $b[0], $b[1]);
        $stmt->execute();
    }
}

function ensure_punch_schema($conn)
{
    if (payroll_is_mssql()) {
        $conn->query("
            IF OBJECT_ID(N'dbo.employee_punches', N'U') IS NULL
            BEGIN
                CREATE TABLE dbo.employee_punches (
                    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
                    emp_id NVARCHAR(50) NOT NULL,
                    branch_id INT NOT NULL,
                    punch_type NVARCHAR(10) NOT NULL,
                    punch_date DATE NOT NULL,
                    punched_at DATETIME2 NOT NULL CONSTRAINT DF_employee_punches_punched_at DEFAULT GETDATE(),
                    latitude DECIMAL(10,7) NULL,
                    longitude DECIMAL(10,7) NULL,
                    location_accuracy DECIMAL(8,2) NULL,
                    distance_meters DECIMAL(10,2) NULL,
                    within_geofence BIT NOT NULL CONSTRAINT DF_employee_punches_within_geofence DEFAULT 0,
                    geo_required BIT NOT NULL CONSTRAINT DF_employee_punches_geo_required DEFAULT 0,
                    record_status NVARCHAR(30) NOT NULL CONSTRAINT DF_employee_punches_record_status DEFAULT 'ok'
                );
                CREATE INDEX IX_employee_punches_emp_date ON dbo.employee_punches (emp_id, punch_date);
                CREATE INDEX IX_employee_punches_branch_date ON dbo.employee_punches (branch_id, punch_date);
            END
        ");
    } else {
        $conn->query("
            CREATE TABLE IF NOT EXISTS `employee_punches` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `emp_id` varchar(50) NOT NULL,
                `branch_id` int(11) NOT NULL,
                `punch_type` varchar(10) NOT NULL,
                `punch_date` date NOT NULL,
                `punched_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `latitude` decimal(10,7) DEFAULT NULL,
                `longitude` decimal(10,7) DEFAULT NULL,
                `location_accuracy` decimal(8,2) DEFAULT NULL,
                `distance_meters` decimal(10,2) DEFAULT NULL,
                `within_geofence` tinyint(1) NOT NULL DEFAULT 0,
                `geo_required` tinyint(1) NOT NULL DEFAULT 0,
                `record_status` varchar(30) NOT NULL DEFAULT 'ok',
                PRIMARY KEY (`id`),
                KEY `emp_date` (`emp_id`, `punch_date`),
                KEY `branch_date` (`branch_id`, `punch_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    $branch_geo_columns = [
        'office_latitude' => 'ALTER TABLE branches ADD COLUMN office_latitude decimal(10,7) DEFAULT NULL',
        'office_longitude' => 'ALTER TABLE branches ADD COLUMN office_longitude decimal(10,7) DEFAULT NULL',
        'geo_fence_radius_meters' => 'ALTER TABLE branches ADD COLUMN geo_fence_radius_meters int(11) DEFAULT NULL',
    ];
    foreach ($branch_geo_columns as $column => $sql) {
        if (!column_exists($conn, 'branches', $column)) {
            $conn->query($sql);
        }
    }

    $branch_timing_columns = [
        'office_start_time' => payroll_is_mssql()
            ? 'ALTER TABLE branches ADD office_start_time NVARCHAR(5) NULL'
            : 'ALTER TABLE branches ADD COLUMN office_start_time varchar(5) DEFAULT NULL',
        'office_end_time' => payroll_is_mssql()
            ? 'ALTER TABLE branches ADD office_end_time NVARCHAR(5) NULL'
            : 'ALTER TABLE branches ADD COLUMN office_end_time varchar(5) DEFAULT NULL',
        'late_grace_minutes' => payroll_is_mssql()
            ? 'ALTER TABLE branches ADD late_grace_minutes INT NULL'
            : 'ALTER TABLE branches ADD COLUMN late_grace_minutes int(11) DEFAULT NULL',
    ];
    foreach ($branch_timing_columns as $column => $sql) {
        if (!column_exists($conn, 'branches', $column)) {
            $conn->query($sql);
        }
    }

    $punch_punctuality_columns = [
        'punctuality_status' => payroll_is_mssql()
            ? 'ALTER TABLE employee_punches ADD punctuality_status NVARCHAR(20) NULL'
            : 'ALTER TABLE `employee_punches` ADD COLUMN `punctuality_status` varchar(20) DEFAULT NULL',
        'late_by_minutes' => payroll_is_mssql()
            ? 'ALTER TABLE employee_punches ADD late_by_minutes INT NULL'
            : 'ALTER TABLE `employee_punches` ADD COLUMN `late_by_minutes` int(11) DEFAULT NULL',
    ];
    foreach ($punch_punctuality_columns as $column => $sql) {
        if (!column_exists($conn, 'employee_punches', $column)) {
            $conn->query($sql);
        }
    }

    backfill_punch_punctuality_once($conn);
    backfill_punch_out_punctuality_once($conn);
    correct_legacy_punch_timezone_once($conn);
}

function backfill_punch_out_punctuality_once($conn): void
{
    if (!function_exists('get_setting')) {
        require_once __DIR__ . '/settings_helper.php';
    }
    if (get_setting($conn, 'punch_out_punctuality_backfilled', '0') === '1') {
        return;
    }

    if (!function_exists('evaluate_punch_out_punctuality')) {
        require_once __DIR__ . '/punch_helper.php';
    }

    $settings = get_all_settings($conn);
    $res = $conn->query("
        SELECT id, punched_at
        FROM employee_punches
        WHERE punch_type = 'out'
          AND record_status = 'ok'
          AND punctuality_status IS NULL
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $eval = evaluate_punch_out_punctuality($row['punched_at'], $settings);
            $status = $eval['punctuality_status'];
            $mins = $eval['late_by_minutes'];
            $stmt = $conn->prepare('UPDATE employee_punches SET punctuality_status = ?, late_by_minutes = ? WHERE id = ?');
            $id = (int) $row['id'];
            $stmt->bind_param('sii', $status, $mins, $id);
            $stmt->execute();
        }
    }

    set_setting($conn, 'punch_out_punctuality_backfilled', '1');
}

function backfill_punch_punctuality_once($conn): void
{
    if (!function_exists('get_setting')) {
        require_once __DIR__ . '/settings_helper.php';
    }
    if (get_setting($conn, 'punch_punctuality_backfilled', '0') === '1') {
        return;
    }

    if (!function_exists('evaluate_punch_in_punctuality')) {
        require_once __DIR__ . '/punch_helper.php';
    }

    $settings = get_all_settings($conn);
    $res = $conn->query("
        SELECT id, punched_at
        FROM employee_punches
        WHERE punch_type = 'in'
          AND record_status = 'ok'
          AND punctuality_status IS NULL
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $eval = evaluate_punch_in_punctuality($row['punched_at'], $settings);
            $status = $eval['punctuality_status'];
            $late_mins = $eval['late_by_minutes'];
            $stmt = $conn->prepare('UPDATE employee_punches SET punctuality_status = ?, late_by_minutes = ? WHERE id = ?');
            $id = (int) $row['id'];
            $stmt->bind_param('sii', $status, $late_mins, $id);
            $stmt->execute();
        }
    }

    set_setting($conn, 'punch_punctuality_backfilled', '1');
}

function correct_legacy_punch_timezone_once($conn): void
{
    if (!function_exists('get_setting')) {
        require_once __DIR__ . '/settings_helper.php';
    }
    if (get_setting($conn, 'punch_ist_timezone_fixed', '0') === '1') {
        return;
    }

    if (payroll_is_mssql()) {
        // Older punches were saved with php.ini Europe/Berlin while the office PC runs India time.
        $conn->query('
            UPDATE employee_punches
            SET punched_at = DATEADD(MINUTE, 210, punched_at),
                punch_date = CAST(DATEADD(MINUTE, 210, punched_at) AS DATE)
        ');
    }

    set_setting($conn, 'punch_ist_timezone_fixed', '1');
}

function migrate_branch_columns($conn)
{
    if (!column_exists($conn, 'admin_users', 'branch_id')) {
        $conn->query('ALTER TABLE `admin_users` ADD COLUMN `branch_id` int(11) DEFAULT NULL AFTER `password`');
    }

    if (!column_exists($conn, 'employees', 'branch_id')) {
        $conn->query('ALTER TABLE `employees` ADD COLUMN `branch_id` int(11) NOT NULL DEFAULT 1 AFTER `emp_id`');
        $conn->query('UPDATE employees SET branch_id = 1 WHERE branch_id = 0 OR branch_id IS NULL');
    }

    if (!column_exists($conn, 'holidays', 'branch_id')) {
        if (index_exists($conn, 'holidays', 'calendar_date')) {
            $conn->query('ALTER TABLE `holidays` DROP INDEX `calendar_date`');
        }
        $conn->query('ALTER TABLE `holidays` ADD COLUMN `branch_id` int(11) NOT NULL DEFAULT 1 AFTER `id`');
        $conn->query('UPDATE holidays SET branch_id = 1 WHERE branch_id = 0 OR branch_id IS NULL');
        if (!index_exists($conn, 'holidays', 'branch_date')) {
            $conn->query('ALTER TABLE `holidays` ADD UNIQUE KEY `branch_date` (`branch_id`, `calendar_date`)');
        }
    }

    if (!column_exists($conn, 'payroll_periods', 'branch_id')) {
        $conn->query('ALTER TABLE `payroll_periods` ADD COLUMN `branch_id` int(11) NOT NULL DEFAULT 1 FIRST');
        $conn->query('UPDATE payroll_periods SET branch_id = 1 WHERE branch_id = 0 OR branch_id IS NULL');
        $conn->query('ALTER TABLE `payroll_periods` DROP PRIMARY KEY');
        $conn->query('ALTER TABLE `payroll_periods` ADD PRIMARY KEY (`branch_id`, `period_year`, `period_month`)');
    }
}

function seed_default_leave_types($conn)
{
    $types = [
        ['PL', 'Privilege Leave', '1.00'],
        ['CL', 'Casual Leave', '1.00'],
        ['SL', 'Sick Leave', '1.00'],
        ['LOP', 'Loss of Pay', '0.00'],
    ];
    foreach ($types as $t) {
        $stmt = $conn->prepare('INSERT IGNORE INTO leave_types (code, name, paid_credit) VALUES (?, ?, ?)');
        $stmt->bind_param('ssd', $t[0], $t[1], $t[2]);
        $stmt->execute();
    }
}

function column_exists($conn, $table, $column)
{
    if (payroll_is_mssql()) {
        $stmt = $conn->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_assoc();
    }

    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function index_exists($conn, $table, $index_name)
{
    if (payroll_is_mssql()) {
        $stmt = $conn->prepare("
            SELECT 1
            FROM sys.indexes i
            INNER JOIN sys.tables t ON i.object_id = t.object_id
            WHERE t.name = ? AND i.name = ?
        ");
        $stmt->bind_param('ss', $table, $index_name);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_assoc();
    }

    $stmt = $conn->prepare("
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND index_name = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $table, $index_name);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res && $res->num_rows > 0;
}

function seed_employee_portal_passwords($conn)
{
    $default = 'Emp@123';
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'default_employee_portal_password' LIMIT 1");
    if ($stmt->execute()) {
        $row = $stmt->get_result()->fetch_assoc();
        if (!empty($row['setting_value'])) {
            $default = $row['setting_value'];
        }
    }
    $hash = password_hash($default, PASSWORD_DEFAULT);

    $emps = $conn->query('SELECT emp_id FROM employees WHERE is_active = 1');
    if (!$emps) {
        return;
    }
    while ($emp = $emps->fetch_assoc()) {
        $emp_id = $emp['emp_id'];
        $check = $conn->prepare('SELECT portal_password_hash FROM employee_payroll_profiles WHERE emp_id = ?');
        $check->bind_param('s', $emp_id);
        $check->execute();
        $profile = $check->get_result()->fetch_assoc();
        if ($profile && !empty($profile['portal_password_hash'])) {
            continue;
        }
        if ($profile) {
            $upd = $conn->prepare('UPDATE employee_payroll_profiles SET portal_password_hash = ? WHERE emp_id = ?');
            $upd->bind_param('ss', $hash, $emp_id);
            $upd->execute();
        } else {
            $ins = $conn->prepare('INSERT INTO employee_payroll_profiles (emp_id, use_custom, portal_password_hash) VALUES (?, 0, ?)');
            $ins->bind_param('ss', $emp_id, $hash);
            $ins->execute();
        }
    }
}

function seed_default_settings($conn)
{
    $defaults = [
        'company_name' => 'Teamora',
        'working_days_per_month' => '26',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => 'Payroll System',
        'payslip_signature' => '',
        'signature_authority_name' => 'Authorized Signatory',
        'pct_basic' => '50',
        'pct_hra' => '20',
        'pct_conveyance' => '5',
        'pct_medical' => '5',
        'pct_special' => '20',
        'pf_percent' => '12',
        'pf_min_limit' => '0',
        'pf_max_limit' => '15000',
        'professional_tax' => '200',
        'esi_percent' => '0.75',
        'esi_gross_limit' => '21000',
        'leave_day_credit' => '1',
        'half_day_credit' => '0.5',
        'overtime_hours_per_day' => '8',
        'overtime_multiplier' => '1.5',
        'require_payroll_approval' => '1',
        'weekoff_day_credit' => '1',
        'employee_attendance_requests_per_month' => '3',
        'employee_leave_requests_per_month' => '5',
        'default_employee_portal_password' => 'Emp@123',
        'leave_quota_pl' => '13',
        'leave_quota_sl' => '9',
        'leave_quota_cl' => '8',
        'max_leaves_per_month' => '4',
        'max_wo_per_month' => '4',
        'punch_enabled' => '1',
        'employee_face_login_enabled' => '1',
        'geo_attendance_enabled' => '1',
        'office_latitude' => '',
        'office_longitude' => '',
        'geo_fence_radius_meters' => '200',
        'office_start_time' => '09:30',
        'office_end_time' => '18:30',
        'late_grace_minutes' => '10',
        'half_day_on_late_in' => '1',
        'half_day_on_early_out' => '1',
        'missing_punch_out_status' => 'half_day',
        'auto_absent_no_punch' => '1',
        'late_count_for_half_day' => '3',
        'punch_sync_overtime' => '1',
        'block_punch_on_holiday_weekoff' => '1',
        'hr_notify_emails' => '',
        'company_email' => '',
        'careers_public_enabled' => '1',
        'company_policies_html' => '',
    ];

    foreach ($defaults as $key => $value) {
        $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
    }
}

function seed_admin_roles_and_permissions($conn)
{
    $roles = [
        ['super_admin', 'Super Admin', 'Full access to all modules', 1],
        ['branch_admin', 'Branch Admin', 'Full branch operations', 1],
        ['hr_manager', 'HR Manager', 'People, leave, recruitment, performance', 1],
        ['accounts', 'Accounts', 'Payroll, slips, expenses, reports', 1],
        ['view_only', 'View Only', 'Read-only access', 1],
    ];
    foreach ($roles as $r) {
        $stmt = $conn->prepare('INSERT IGNORE INTO admin_roles (code, name, description, is_system) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $r[0], $r[1], $r[2], $r[3]);
        $stmt->execute();
    }

    $perms = [
        'super_admin' => ['*'],
        'branch_admin' => [
            'dashboard', 'employees', 'masters', 'org', 'calendar', 'attendance', 'leave', 'payroll', 'slips',
            'reports', 'recruitment', 'performance', 'expenses', 'assets', 'exits', 'announcements', 'approvals',
        ],
        'hr_manager' => [
            'dashboard', 'employees', 'masters', 'org', 'calendar', 'leave', 'recruitment', 'performance',
            'expenses', 'assets', 'exits', 'announcements', 'approvals',
        ],
        'accounts' => [
            'dashboard', 'employees', 'attendance', 'payroll', 'slips', 'reports', 'expenses', 'exits',
        ],
        'view_only' => [
            'dashboard', 'employees', 'org', 'calendar', 'attendance', 'leave', 'reports', 'recruitment',
            'performance', 'expenses', 'assets', 'exits',
        ],
    ];

    foreach ($perms as $role_code => $keys) {
        $role_id = admin_role_id_by_code($conn, $role_code);
        if ($role_id === null) {
            continue;
        }
        foreach ($keys as $key) {
            $stmt = $conn->prepare('INSERT IGNORE INTO admin_role_permissions (role_id, permission_key) VALUES (?, ?)');
            $stmt->bind_param('is', $role_id, $key);
            $stmt->execute();
        }
    }

    $super_id = admin_role_id_by_code($conn, 'super_admin');
    $branch_id = admin_role_id_by_code($conn, 'branch_admin');
    if ($super_id !== null) {
        $conn->query('UPDATE admin_users SET role_id = ' . (int) $super_id . ' WHERE branch_id IS NULL AND (role_id IS NULL OR role_id = 0)');
    }
    if ($branch_id !== null) {
        $conn->query('UPDATE admin_users SET role_id = ' . (int) $branch_id . ' WHERE branch_id IS NOT NULL AND (role_id IS NULL OR role_id = 0)');
    }
}

function admin_role_id_by_code($conn, $code)
{
    $stmt = $conn->prepare('SELECT id FROM admin_roles WHERE code = ? LIMIT 1');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int) $row['id'] : null;
}

function migrate_employee_department_masters($conn)
{
    $flag = 'hrm_masters_migrated';
    $chk = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $chk->bind_param('s', $flag);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if ($row && ($row['setting_value'] ?? '') === '1') {
        return;
    }

    $dept_map = [];
    $res = $conn->query("SELECT DISTINCT TRIM(department) AS dept FROM employees WHERE department IS NOT NULL AND TRIM(department) != ''");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $name = $row['dept'];
            $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', substr($name, 0, 18)));
            if ($code === '') {
                $code = 'DEPT';
            }
            $stmt = $conn->prepare('INSERT IGNORE INTO departments (code, name, branch_id, is_active) VALUES (?, ?, NULL, 1)');
            $stmt->bind_param('ss', $code, $name);
            $stmt->execute();
            $id_stmt = $conn->prepare('SELECT id FROM departments WHERE name = ? AND branch_id IS NULL LIMIT 1');
            $id_stmt->bind_param('s', $name);
            $id_stmt->execute();
            $id_row = $id_stmt->get_result()->fetch_assoc();
            if ($id_row) {
                $dept_map[$name] = (int) $id_row['id'];
            }
        }
    }

    foreach ($dept_map as $name => $dept_id) {
        $stmt = $conn->prepare('UPDATE employees SET department_id = ? WHERE TRIM(department) = ? AND (department_id IS NULL OR department_id = 0)');
        $stmt->bind_param('is', $dept_id, $name);
        $stmt->execute();
    }

    $desig_res = $conn->query("SELECT DISTINCT TRIM(designation) AS desig, TRIM(department) AS dept FROM employees WHERE designation IS NOT NULL AND TRIM(designation) != ''");
    if ($desig_res) {
        while ($row = $desig_res->fetch_assoc()) {
            $desig = $row['desig'];
            $dept_id = isset($dept_map[$row['dept'] ?? '']) ? $dept_map[$row['dept']] : null;
            $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', substr($desig, 0, 18)));
            if ($code === '') {
                $code = 'ROLE';
            }
            if ($dept_id === null) {
                $stmt = $conn->prepare('INSERT IGNORE INTO designations (department_id, code, name, is_active) VALUES (NULL, ?, ?, 1)');
                $stmt->bind_param('ss', $code, $desig);
            } else {
                $stmt = $conn->prepare('INSERT IGNORE INTO designations (department_id, code, name, is_active) VALUES (?, ?, ?, 1)');
                $stmt->bind_param('iss', $dept_id, $code, $desig);
            }
            $stmt->execute();
        }
    }

    $emp_res = $conn->query('SELECT emp_id, designation, department_id FROM employees WHERE designation IS NOT NULL AND TRIM(designation) != ""');
    if ($emp_res) {
        while ($emp = $emp_res->fetch_assoc()) {
            $desig = trim($emp['designation']);
            $dept_id = $emp['department_id'] ? (int) $emp['department_id'] : null;
            if ($dept_id) {
                $id_stmt = $conn->prepare('SELECT id FROM designations WHERE name = ? AND department_id = ? LIMIT 1');
                $id_stmt->bind_param('si', $desig, $dept_id);
            } else {
                $id_stmt = $conn->prepare('SELECT id FROM designations WHERE name = ? AND department_id IS NULL LIMIT 1');
                $id_stmt->bind_param('s', $desig);
            }
            $id_stmt->execute();
            $id_row = $id_stmt->get_result()->fetch_assoc();
            if ($id_row) {
                $desig_id = (int) $id_row['id'];
                $upd = $conn->prepare('UPDATE employees SET designation_id = ? WHERE emp_id = ?');
                $upd->bind_param('is', $desig_id, $emp['emp_id']);
                $upd->execute();
            }
        }
    }

    $flag_val = '1';
    $ins = $conn->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $ins->bind_param('ss', $flag, $flag_val);
    $ins->execute();
}
