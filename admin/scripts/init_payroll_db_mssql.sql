IF DB_ID(N'payroll_db') IS NULL
BEGIN
    CREATE DATABASE payroll_db;
END
GO

USE payroll_db;
GO

IF OBJECT_ID(N'dbo.branches', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.branches (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        code NVARCHAR(20) NOT NULL,
        name NVARCHAR(100) NOT NULL,
        is_active BIT NOT NULL CONSTRAINT DF_branches_is_active DEFAULT 1,
        CONSTRAINT UQ_branches_code UNIQUE (code)
    );
END
GO

IF OBJECT_ID(N'dbo.admin_users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.admin_users (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        username NVARCHAR(50) NOT NULL,
        password NVARCHAR(255) NOT NULL,
        branch_id INT NULL,
        CONSTRAINT UQ_admin_users_username UNIQUE (username)
    );
END
GO

IF OBJECT_ID(N'dbo.employees', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.employees (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        branch_id INT NOT NULL CONSTRAINT DF_employees_branch_id DEFAULT 1,
        name NVARCHAR(100) NOT NULL,
        email NVARCHAR(150) NULL,
        phone NVARCHAR(30) NULL,
        department NVARCHAR(100) NULL,
        designation NVARCHAR(100) NULL,
        base_salary DECIMAL(12,2) NOT NULL CONSTRAINT DF_employees_base_salary DEFAULT 0.00,
        joined_date DATE NULL,
        is_active BIT NOT NULL CONSTRAINT DF_employees_is_active DEFAULT 1,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_employees_created_at DEFAULT GETDATE(),
        pan NVARCHAR(20) NULL,
        bank_account NVARCHAR(40) NULL,
        bank_ifsc NVARCHAR(20) NULL,
        bank_name NVARCHAR(100) NULL,
        grade NVARCHAR(50) NULL,
        esic_no NVARCHAR(50) NULL,
        uan_no NVARCHAR(50) NULL,
        pf_no NVARCHAR(50) NULL,
        CONSTRAINT UQ_employees_emp_id UNIQUE (emp_id)
    );
END
GO

IF OBJECT_ID(N'dbo.attendance', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.attendance (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        attendance_date DATE NOT NULL,
        status NVARCHAR(20) NOT NULL,
        leave_type NVARCHAR(10) NULL,
        overtime_hours DECIMAL(5,2) NOT NULL CONSTRAINT DF_attendance_overtime DEFAULT 0,
        CONSTRAINT UQ_attendance_emp_date UNIQUE (emp_id, attendance_date)
    );
END
GO

IF OBJECT_ID(N'dbo.settings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.settings (
        setting_key NVARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value NVARCHAR(MAX) NULL
    );
END
GO

IF OBJECT_ID(N'dbo.salary_slip_logs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.salary_slip_logs (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        period_month TINYINT NOT NULL,
        period_year SMALLINT NOT NULL,
        net_salary DECIMAL(12,2) NOT NULL,
        sent_to NVARCHAR(150) NULL,
        sent_at DATETIME2 NOT NULL CONSTRAINT DF_salary_slip_logs_sent_at DEFAULT GETDATE(),
        status NVARCHAR(20) NOT NULL CONSTRAINT DF_salary_slip_logs_status DEFAULT 'sent',
        CONSTRAINT UQ_salary_slip_logs_emp_period UNIQUE (emp_id, period_year, period_month)
    );
    CREATE INDEX IX_salary_slip_logs_period ON dbo.salary_slip_logs (period_year, period_month);
END
GO

IF OBJECT_ID(N'dbo.payroll_periods', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.payroll_periods (
        branch_id INT NOT NULL CONSTRAINT DF_payroll_periods_branch_id DEFAULT 1,
        period_year SMALLINT NOT NULL,
        period_month TINYINT NOT NULL,
        status NVARCHAR(20) NOT NULL CONSTRAINT DF_payroll_periods_status DEFAULT 'open',
        approved_by NVARCHAR(50) NULL,
        approved_at DATETIME2 NULL,
        locked_by NVARCHAR(50) NULL,
        locked_at DATETIME2 NULL,
        notes NVARCHAR(MAX) NULL,
        CONSTRAINT PK_payroll_periods PRIMARY KEY (branch_id, period_year, period_month)
    );
END
GO

IF OBJECT_ID(N'dbo.holidays', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.holidays (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        branch_id INT NOT NULL CONSTRAINT DF_holidays_branch_id DEFAULT 1,
        calendar_date DATE NOT NULL,
        name NVARCHAR(120) NOT NULL,
        kind NVARCHAR(20) NOT NULL CONSTRAINT DF_holidays_kind DEFAULT 'holiday',
        CONSTRAINT UQ_holidays_branch_date UNIQUE (branch_id, calendar_date)
    );
END
GO

IF OBJECT_ID(N'dbo.leave_types', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.leave_types (
        code NVARCHAR(10) NOT NULL PRIMARY KEY,
        name NVARCHAR(60) NOT NULL,
        paid_credit DECIMAL(3,2) NOT NULL CONSTRAINT DF_leave_types_paid_credit DEFAULT 1.00,
        is_active BIT NOT NULL CONSTRAINT DF_leave_types_is_active DEFAULT 1
    );
END
GO

IF OBJECT_ID(N'dbo.employee_payroll_profiles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.employee_payroll_profiles (
        emp_id NVARCHAR(50) NOT NULL PRIMARY KEY,
        use_custom BIT NOT NULL CONSTRAINT DF_employee_payroll_profiles_use_custom DEFAULT 0,
        pct_basic DECIMAL(5,2) NULL,
        pct_hra DECIMAL(5,2) NULL,
        pct_conveyance DECIMAL(5,2) NULL,
        pct_medical DECIMAL(5,2) NULL,
        pct_special DECIMAL(5,2) NULL,
        pf_percent DECIMAL(5,2) NULL,
        professional_tax DECIMAL(10,2) NULL,
        portal_password_hash NVARCHAR(255) NULL
    );
END
GO

IF OBJECT_ID(N'dbo.employee_weekoff_days', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.employee_weekoff_days (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        branch_id INT NOT NULL,
        off_date DATE NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_employee_weekoff_days_created_at DEFAULT GETDATE(),
        CONSTRAINT UQ_employee_weekoff_days_emp_off_date UNIQUE (emp_id, off_date)
    );
    CREATE INDEX IX_employee_weekoff_days_branch_month ON dbo.employee_weekoff_days (branch_id, off_date);
END
GO

IF OBJECT_ID(N'dbo.payroll_adjustments', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.payroll_adjustments (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        period_year SMALLINT NOT NULL,
        period_month TINYINT NOT NULL,
        adj_type NVARCHAR(20) NOT NULL,
        label NVARCHAR(100) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_payroll_adjustments_created_at DEFAULT GETDATE()
    );
    CREATE INDEX IX_payroll_adjustments_emp_period ON dbo.payroll_adjustments (emp_id, period_year, period_month);
END
GO

IF OBJECT_ID(N'dbo.employee_profile_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.employee_profile_requests (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        branch_id INT NOT NULL,
        proposed_email NVARCHAR(150) NULL,
        proposed_phone NVARCHAR(30) NULL,
        proposed_pan NVARCHAR(20) NULL,
        proposed_bank_account NVARCHAR(40) NULL,
        proposed_bank_ifsc NVARCHAR(20) NULL,
        proposed_bank_name NVARCHAR(100) NULL,
        employee_note NVARCHAR(MAX) NULL,
        request_status NVARCHAR(20) NOT NULL CONSTRAINT DF_employee_profile_requests_status DEFAULT 'pending',
        reviewed_by NVARCHAR(50) NULL,
        reviewed_at DATETIME2 NULL,
        review_note NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_employee_profile_requests_created_at DEFAULT GETDATE()
    );
    CREATE INDEX IX_employee_profile_requests_branch_status ON dbo.employee_profile_requests (branch_id, request_status);
    CREATE INDEX IX_employee_profile_requests_emp_status ON dbo.employee_profile_requests (emp_id, request_status);
END
GO

IF OBJECT_ID(N'dbo.employee_attendance_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.employee_attendance_requests (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        branch_id INT NOT NULL,
        attendance_date DATE NOT NULL,
        status NVARCHAR(20) NOT NULL,
        leave_type NVARCHAR(10) NULL,
        employee_note NVARCHAR(MAX) NULL,
        request_status NVARCHAR(20) NOT NULL CONSTRAINT DF_employee_attendance_requests_status DEFAULT 'pending',
        reviewed_by NVARCHAR(50) NULL,
        reviewed_at DATETIME2 NULL,
        review_note NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_employee_attendance_requests_created_at DEFAULT GETDATE()
    );
    CREATE INDEX IX_employee_attendance_requests_branch_status ON dbo.employee_attendance_requests (branch_id, request_status);
    CREATE INDEX IX_employee_attendance_requests_emp_month ON dbo.employee_attendance_requests (emp_id, attendance_date);
END
GO

IF OBJECT_ID(N'dbo.employee_leave_requests', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.employee_leave_requests (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        branch_id INT NOT NULL,
        from_date DATE NOT NULL,
        to_date DATE NOT NULL,
        leave_type NVARCHAR(10) NOT NULL,
        employee_note NVARCHAR(MAX) NULL,
        request_status NVARCHAR(20) NOT NULL CONSTRAINT DF_employee_leave_requests_status DEFAULT 'pending',
        reviewed_by NVARCHAR(50) NULL,
        reviewed_at DATETIME2 NULL,
        review_note NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_employee_leave_requests_created_at DEFAULT GETDATE()
    );
    CREATE INDEX IX_employee_leave_requests_branch_status ON dbo.employee_leave_requests (branch_id, request_status);
    CREATE INDEX IX_employee_leave_requests_emp_dates ON dbo.employee_leave_requests (emp_id, from_date, to_date);
END
GO

IF OBJECT_ID(N'dbo.employee_leave_balances', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.employee_leave_balances (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        emp_id NVARCHAR(50) NOT NULL,
        leave_type NVARCHAR(10) NOT NULL,
        balance DECIMAL(5,2) NOT NULL CONSTRAINT DF_employee_leave_balances_balance DEFAULT 0.00,
        CONSTRAINT UQ_employee_leave_balances_emp_leave UNIQUE (emp_id, leave_type)
    );
END
GO

IF OBJECT_ID(N'dbo.leave_accruals_log', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.leave_accruals_log (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        period_year SMALLINT NOT NULL,
        period_month TINYINT NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_leave_accruals_log_created_at DEFAULT GETDATE(),
        CONSTRAINT UQ_leave_accruals_log_period UNIQUE (period_year, period_month)
    );
END
GO

MERGE dbo.branches AS t
USING (VALUES (N'INDRA', N'Indra Nagar'), (N'ALAM', N'Alambagh')) AS s(code, name)
ON t.code = s.code
WHEN NOT MATCHED THEN INSERT (code, name) VALUES (s.code, s.name);
GO

MERGE dbo.leave_types AS t
USING (VALUES
    (N'PL', N'Privilege Leave', 1.00),
    (N'CL', N'Casual Leave', 1.00),
    (N'SL', N'Sick Leave', 1.00),
    (N'LOP', N'Loss of Pay', 0.00)
) AS s(code, name, paid_credit)
ON t.code = s.code
WHEN NOT MATCHED THEN INSERT (code, name, paid_credit) VALUES (s.code, s.name, s.paid_credit);
GO

PRINT 'payroll_db schema initialized.';
GO
