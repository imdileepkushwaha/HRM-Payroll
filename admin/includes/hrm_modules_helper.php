<?php

require_once __DIR__ . '/employee_helper.php';
require_once __DIR__ . '/branch_helper.php';

/* ---------- Upload helpers ---------- */

function hrm_upload_dir(string $subdir): string
{
    $dir = dirname(__DIR__) . '/uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function hrm_store_upload(array $file, string $subdir, array $allowed_mimes, int $max_bytes = 5242880): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'File upload failed.'];
    }
    if (($file['size'] ?? 0) > $max_bytes) {
        return ['ok' => false, 'message' => 'File is too large (max 5 MB).'];
    }
    $mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed_mimes, true)) {
        return ['ok' => false, 'message' => 'File type not allowed.'];
    }
    $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
    $safe = bin2hex(random_bytes(8)) . ($ext ? '.' . preg_replace('/[^a-z0-9]/i', '', $ext) : '');
    $rel = $subdir . '/' . $safe;
    $abs = hrm_upload_dir($subdir) . '/' . $safe;
    if (!move_uploaded_file($file['tmp_name'], $abs)) {
        return ['ok' => false, 'message' => 'Could not save uploaded file.'];
    }
    return ['ok' => true, 'path' => $rel, 'filename' => $file['name'] ?? $safe, 'mime' => $mime];
}

function hrm_upload_absolute_path(?string $relative): ?string
{
    if ($relative === null || trim($relative) === '') {
        return null;
    }
    $path = dirname(__DIR__) . '/uploads/' . ltrim($relative, '/');
    return is_file($path) ? $path : null;
}

/* ---------- Departments & designations ---------- */

function get_departments($conn, ?int $branch_id = null, bool $active_only = true): array
{
    $sql = 'SELECT * FROM departments WHERE 1=1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND (branch_id IS NULL OR branch_id = ?)';
        $types .= 'i';
        $params[] = $branch_id;
    }
    if ($active_only) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY name ASC';
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_designations($conn, ?int $department_id = null, bool $active_only = true): array
{
    $sql = 'SELECT d.*, dept.name AS department_name FROM designations d LEFT JOIN departments dept ON dept.id = d.department_id WHERE 1=1';
    if ($department_id !== null) {
        $sql .= ' AND d.department_id = ' . (int) $department_id;
    }
    if ($active_only) {
        $sql .= ' AND d.is_active = 1';
    }
    $sql .= ' ORDER BY d.name ASC';
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function save_department($conn, array $post): array
{
    $id = (int) ($post['id'] ?? 0);
    $code = strtoupper(trim($post['code'] ?? ''));
    $name = trim($post['name'] ?? '');
    $branch_raw = $post['branch_id'] ?? '';
    $branch_id = ($branch_raw === '' || $branch_raw === '0') ? null : (int) $branch_raw;
    $is_active = array_key_exists('is_active', $post) ? (!empty($post['is_active']) ? 1 : 0) : 1;

    if ($code === '' || $name === '') {
        return ['ok' => false, 'message' => 'Code and name are required.'];
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE departments SET code = ?, name = ?, branch_id = ?, is_active = ? WHERE id = ?');
        $stmt->bind_param('ssiii', $code, $name, $branch_id, $is_active, $id);
    } else {
        $stmt = $conn->prepare('INSERT INTO departments (code, name, branch_id, is_active) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssii', $code, $name, $branch_id, $is_active);
    }

    if ($stmt->execute()) {
        return ['ok' => true, 'message' => $id > 0 ? 'Department updated.' : 'Department added.'];
    }
    return ['ok' => false, 'message' => 'Could not save department. Code may already exist.'];
}

function save_designation($conn, array $post): array
{
    $id = (int) ($post['id'] ?? 0);
    $code = strtoupper(trim($post['code'] ?? ''));
    $name = trim($post['name'] ?? '');
    $dept_raw = $post['department_id'] ?? '';
    $department_id = ($dept_raw === '' || $dept_raw === '0') ? null : (int) $dept_raw;
    $is_active = array_key_exists('is_active', $post) ? (!empty($post['is_active']) ? 1 : 0) : 1;

    if ($code === '' || $name === '') {
        return ['ok' => false, 'message' => 'Code and name are required.'];
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE designations SET code = ?, name = ?, department_id = ?, is_active = ? WHERE id = ?');
        $stmt->bind_param('ssiii', $code, $name, $department_id, $is_active, $id);
    } else {
        $stmt = $conn->prepare('INSERT INTO designations (department_id, code, name, is_active) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('issi', $department_id, $code, $name, $is_active);
    }

    if ($stmt->execute()) {
        return ['ok' => true, 'message' => $id > 0 ? 'Designation updated.' : 'Designation added.'];
    }
    return ['ok' => false, 'message' => 'Could not save designation.'];
}

function resolve_employee_master_fields($conn, ?int $department_id, ?int $designation_id): array
{
    $department = '';
    $designation = '';
    if ($department_id) {
        $stmt = $conn->prepare('SELECT name FROM departments WHERE id = ?');
        $stmt->bind_param('i', $department_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $department = $row['name'] ?? '';
    }
    if ($designation_id) {
        $stmt = $conn->prepare('SELECT name FROM designations WHERE id = ?');
        $stmt->bind_param('i', $designation_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $designation = $row['name'] ?? '';
    }
    return ['department' => $department, 'designation' => $designation];
}

function get_manager_candidates($conn, ?int $branch_id, ?string $exclude_emp_id = null): array
{
    $sql = 'SELECT emp_id, name, department, designation FROM employees WHERE is_active = 1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    if ($exclude_emp_id) {
        $sql .= ' AND emp_id != ?';
        $types .= 's';
        $params[] = $exclude_emp_id;
    }
    $sql .= ' ORDER BY name ASC';
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_org_chart_tree($conn, ?int $branch_id): array
{
    $bf = branch_employees_sql('e');
    $sql = 'SELECT e.emp_id, e.name, e.department, e.designation, e.manager_emp_id, e.is_active FROM employees e WHERE 1=1' . $bf['sql'] . ' ORDER BY e.name ASC';
    $stmt = $conn->prepare($sql);
    bind_branch_stmt_params($stmt, $bf['types'], $bf['params']);
    $stmt->execute();
    $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $by_id = [];
    foreach ($employees as $emp) {
        $emp['reports'] = [];
        $by_id[$emp['emp_id']] = $emp;
    }

    $roots = [];
    foreach ($by_id as $emp_id => $emp) {
        $mgr = $emp['manager_emp_id'] ?? '';
        if ($mgr && isset($by_id[$mgr])) {
            $by_id[$mgr]['reports'][] = &$by_id[$emp_id];
        } else {
            $roots[] = &$by_id[$emp_id];
        }
    }
    return $roots;
}

/* ---------- Salary slip logs & email ---------- */

function log_salary_slip_event($conn, string $emp_id, int $year, int $month, float $net_salary, ?string $sent_to, string $status): void
{
    $stmt = $conn->prepare('
        INSERT INTO salary_slip_logs (emp_id, period_year, period_month, net_salary, sent_to, status, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE net_salary = VALUES(net_salary), sent_to = VALUES(sent_to), status = VALUES(status), sent_at = NOW()
    ');
    $stmt->bind_param('siidss', $emp_id, $year, $month, $net_salary, $sent_to, $status);
    $stmt->execute();
}

function get_admin_slip_logs($conn, int $year, int $month, ?int $branch_id = null): array
{
    $sql = '
        SELECT l.*, e.name, e.email, e.department, e.branch_id
        FROM salary_slip_logs l
        INNER JOIN employees e ON e.emp_id = l.emp_id
        WHERE l.period_year = ? AND l.period_month = ?
    ';
    $types = 'ii';
    $params = [$year, $month];
    if ($branch_id !== null) {
        $sql .= ' AND e.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    $sql .= ' ORDER BY l.sent_at DESC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function send_salary_slips_for_period($conn, int $year, int $month, array $settings, ?int $branch_id, string $admin_user): array
{
    require_once __DIR__ . '/salary_helper.php';
    require_once __DIR__ . '/pdf_slip.php';
    require_once __DIR__ . '/mailer.php';
    require_once __DIR__ . '/payroll_extensions.php';

    if (!can_release_salary_slips_for_period($conn, $year, $month, $branch_id)) {
        return ['ok' => false, 'message' => 'Payroll must be approved before sending slips.', 'sent' => 0, 'skipped' => 0];
    }

    $bf = branch_employees_sql('e');
    $sql = 'SELECT e.* FROM employees e WHERE e.is_active = 1' . $bf['sql'];
    $stmt = $conn->prepare($sql);
    bind_branch_stmt_params($stmt, $bf['types'], $bf['params']);
    $stmt->execute();
    $res = $stmt->get_result();

    $sent = 0;
    $skipped = 0;
    $errors = [];

    while ($emp = $res->fetch_assoc()) {
        if (!employee_salary_slip_is_available($conn, $emp, $year, $month, $settings)) {
            $skipped++;
            continue;
        }
        $email = trim($emp['email'] ?? '');
        $salary = calculate_employee_salary_full($conn, $emp, $year, $month, $settings);
        $net = (float) ($salary['net_salary'] ?? 0);

        if ($email === '') {
            log_salary_slip_event($conn, $emp['emp_id'], $year, $month, $net, null, 'portal_only');
            $skipped++;
            continue;
        }

        $pdf = generate_salary_slip_pdf($conn, $emp, $salary, $settings, $year, $month);
        $filename = salary_slip_pdf_filename($emp, $year, $month);
        $subject = 'Salary slip — ' . get_period_label($year, $month);
        $html = render_salary_slip_email_html($emp, $salary, $settings, $year, $month);
        $mail_ok = send_email_smtp($settings, $email, $emp['name'], $subject, $html, $pdf, $filename);

        if ($mail_ok) {
            log_salary_slip_event($conn, $emp['emp_id'], $year, $month, $net, $email, 'sent');
            $sent++;
        } else {
            log_salary_slip_event($conn, $emp['emp_id'], $year, $month, $net, $email, 'failed');
            $errors[] = $emp['emp_id'];
        }
    }

    $msg = "Sent {$sent} slip(s) by email.";
    if ($skipped > 0) {
        $msg .= " {$skipped} skipped (no slip or no email).";
    }
    if ($errors !== []) {
        $msg .= ' Failed: ' . implode(', ', $errors) . '.';
    }

    return ['ok' => $sent > 0 || $skipped > 0, 'message' => $msg, 'sent' => $sent, 'skipped' => $skipped];
}

/* ---------- Recruitment ---------- */

function get_job_openings($conn, ?int $branch_id, ?string $status = null): array
{
    $sql = 'SELECT j.*, d.name AS department_name, des.name AS designation_name
            FROM job_openings j
            LEFT JOIN departments d ON d.id = j.department_id
            LEFT JOIN designations des ON des.id = j.designation_id
            WHERE 1=1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND j.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    if ($status !== null && $status !== '') {
        $sql .= ' AND j.status = ?';
        $types .= 's';
        $params[] = $status;
    }
    $sql .= ' ORDER BY j.created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_candidates_for_job($conn, int $job_id): array
{
    $stmt = $conn->prepare('SELECT * FROM candidates WHERE job_opening_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $job_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function save_job_opening($conn, array $post, string $created_by): array
{
    $id = (int) ($post['id'] ?? 0);
    $title = trim($post['title'] ?? '');
    $branch_id = (int) ($post['branch_id'] ?? branch_id_for_write());
    $department_id = ($post['department_id'] ?? '') !== '' ? (int) $post['department_id'] : null;
    $designation_id = ($post['designation_id'] ?? '') !== '' ? (int) $post['designation_id'] : null;
    $description = trim($post['description'] ?? '');
    $openings_count = max(1, (int) ($post['openings_count'] ?? 1));
    $status_raw = $post['status'] ?? 'open';
    $status = in_array($status_raw, ['open', 'closed', 'on_hold'], true) ? $status_raw : 'open';

    if ($title === '') {
        return ['ok' => false, 'message' => 'Job title is required.'];
    }

    if ($branch_id <= 0 || !get_branch_by_id($conn, $branch_id)) {
        return ['ok' => false, 'message' => 'Please select a valid branch for this job.'];
    }

    // mysqli cannot bind NULL to integer types reliably — use string binds for nullable FK columns.
    $dept_bind = $department_id === null ? null : (string) $department_id;
    $desig_bind = $designation_id === null ? null : (string) $designation_id;

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE job_openings SET title=?, branch_id=?, department_id=?, designation_id=?, description=?, openings_count=?, status=? WHERE id=?');
        if (!$stmt) {
            return ['ok' => false, 'message' => 'Could not save job opening.'];
        }
        $stmt->bind_param('sisssisi', $title, $branch_id, $dept_bind, $desig_bind, $description, $openings_count, $status, $id);
    } else {
        $stmt = $conn->prepare('INSERT INTO job_openings (title, branch_id, department_id, designation_id, description, openings_count, status, created_by) VALUES (?,?,?,?,?,?,?,?)');
        if (!$stmt) {
            return ['ok' => false, 'message' => 'Could not save job opening.'];
        }
        $stmt->bind_param('sisssiss', $title, $branch_id, $dept_bind, $desig_bind, $description, $openings_count, $status, $created_by);
    }

    if ($stmt->execute()) {
        return ['ok' => true, 'message' => $id > 0 ? 'Job opening updated.' : 'Job opening created.', 'id' => $id > 0 ? $id : (int) $conn->insert_id];
    }

    $detail = trim($stmt->error ?: $conn->error);
    return ['ok' => false, 'message' => 'Could not save job opening.' . ($detail !== '' ? ' (' . $detail . ')' : '')];
}

function update_job_opening_status($conn, int $job_id, string $status): array
{
    $status_raw = $status;
    if (!in_array($status_raw, ['open', 'closed', 'on_hold'], true)) {
        return ['ok' => false, 'message' => 'Invalid job status.'];
    }
    $stmt = $conn->prepare('UPDATE job_openings SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status_raw, $job_id);
    if ($stmt->execute()) {
        $labels = ['open' => 'reopened', 'closed' => 'closed', 'on_hold' => 'put on hold'];
        return ['ok' => true, 'message' => 'Job ' . ($labels[$status_raw] ?? 'updated') . '.'];
    }
    return ['ok' => false, 'message' => 'Could not update job status.'];
}

function update_candidate_stage($conn, int $candidate_id, string $stage): array
{
    $allowed = ['applied', 'screening', 'interview', 'offered', 'hired', 'rejected'];
    if (!in_array($stage, $allowed, true)) {
        return ['ok' => false, 'message' => 'Invalid stage.'];
    }
    $chk = $conn->prepare('SELECT hired_emp_id FROM candidates WHERE id = ?');
    $chk->bind_param('i', $candidate_id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if (!$row) {
        return ['ok' => false, 'message' => 'Candidate not found.'];
    }
    if (!empty($row['hired_emp_id']) && $stage !== 'hired') {
        return ['ok' => false, 'message' => 'Cannot change stage — candidate is already hired.'];
    }
    $stmt = $conn->prepare('UPDATE candidates SET stage = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $stage, $candidate_id);
    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'Candidate moved to ' . $stage . '.'];
    }
    return ['ok' => false, 'message' => 'Could not update candidate stage.'];
}

function save_candidate($conn, array $post, ?array $resume_file = null): array
{
    $id = (int) ($post['id'] ?? 0);
    $job_id = (int) ($post['job_opening_id'] ?? 0);
    $name = trim($post['name'] ?? '');
    $email = trim($post['email'] ?? '');
    $phone = trim($post['phone'] ?? '');
    $stage = trim($post['stage'] ?? 'applied');
    $notes = trim($post['notes'] ?? '');
    $allowed_stages = ['applied', 'screening', 'interview', 'offered', 'hired', 'rejected'];

    if ($job_id <= 0 || $name === '') {
        return ['ok' => false, 'message' => 'Job and candidate name are required.'];
    }
    if (!in_array($stage, $allowed_stages, true)) {
        $stage = 'applied';
    }

    $resume_path = null;
    $resume_filename = null;
    if ($resume_file && ($resume_file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $up = hrm_store_upload($resume_file, 'recruitment', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        if (!$up['ok']) {
            return $up;
        }
        $resume_path = $up['path'];
        $resume_filename = $up['filename'];
    }

    if ($id > 0) {
        if ($resume_path) {
            $stmt = $conn->prepare('UPDATE candidates SET name=?, email=?, phone=?, stage=?, notes=?, resume_path=?, resume_filename=?, updated_at=NOW() WHERE id=?');
            $stmt->bind_param('sssssssi', $name, $email, $phone, $stage, $notes, $resume_path, $resume_filename, $id);
        } else {
            $stmt = $conn->prepare('UPDATE candidates SET name=?, email=?, phone=?, stage=?, notes=?, updated_at=NOW() WHERE id=?');
            $stmt->bind_param('sssssi', $name, $email, $phone, $stage, $notes, $id);
        }
    } else {
        $stmt = $conn->prepare('INSERT INTO candidates (job_opening_id, name, email, phone, resume_path, resume_filename, stage, notes) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->bind_param('isssssss', $job_id, $name, $email, $phone, $resume_path, $resume_filename, $stage, $notes);
    }

    if ($stmt->execute()) {
        return ['ok' => true, 'message' => $id > 0 ? 'Candidate updated.' : 'Candidate added.', 'id' => $id > 0 ? $id : (int) $conn->insert_id];
    }
    return ['ok' => false, 'message' => 'Could not save candidate.'];
}

function convert_candidate_to_employee($conn, int $candidate_id, array $post, array $settings): array
{
    $stmt = $conn->prepare('SELECT c.*, j.branch_id, j.department_id, j.designation_id FROM candidates c INNER JOIN job_openings j ON j.id = c.job_opening_id WHERE c.id = ?');
    $stmt->bind_param('i', $candidate_id);
    $stmt->execute();
    $cand = $stmt->get_result()->fetch_assoc();
    if (!$cand) {
        return ['ok' => false, 'message' => 'Candidate not found.'];
    }
    if (!empty($cand['hired_emp_id'])) {
        return ['ok' => false, 'message' => 'Candidate already converted to employee ' . $cand['hired_emp_id'] . '.'];
    }

    $emp_id = trim($post['emp_id'] ?? '');
    if ($emp_id === '') {
        $emp_id = 'EMP' . str_pad((string) $candidate_id, 4, '0', STR_PAD_LEFT);
    }
    $name = trim($post['name'] ?? $cand['name']);
    $email = trim($post['email'] ?? $cand['email'] ?? '');
    $phone = trim($post['phone'] ?? $cand['phone'] ?? '');
    $branch_id = (int) ($cand['branch_id'] ?? branch_id_for_write());
    $department_id = $cand['department_id'] ? (int) $cand['department_id'] : null;
    $designation_id = $cand['designation_id'] ? (int) $cand['designation_id'] : null;
    $masters = resolve_employee_master_fields($conn, $department_id, $designation_id);
    $department = $masters['department'];
    $designation = $masters['designation'];
    $base_salary = (float) ($post['base_salary'] ?? 0);
    $joined_date = date('Y-m-d');
    $manager_emp_id = trim($post['manager_emp_id'] ?? '') ?: null;

    $ins = $conn->prepare('INSERT INTO employees (emp_id, branch_id, name, email, phone, department, designation, department_id, designation_id, manager_emp_id, base_salary, joined_date, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)');
    $ins->bind_param('sisssssiisds', $emp_id, $branch_id, $name, $email, $phone, $department, $designation, $department_id, $designation_id, $manager_emp_id, $base_salary, $joined_date);

    if (!$ins->execute()) {
        return ['ok' => false, 'message' => 'Could not create employee. ID may already exist.'];
    }

    require_once __DIR__ . '/settings_helper.php';
    $default_pw = $settings['default_employee_portal_password'] ?? 'Emp@123';
    set_employee_portal_password($conn, $emp_id, $default_pw);

    $upd = $conn->prepare("UPDATE candidates SET stage = 'hired', hired_emp_id = ?, updated_at = NOW() WHERE id = ?");
    $upd->bind_param('si', $emp_id, $candidate_id);
    $upd->execute();

    return ['ok' => true, 'message' => 'Employee ' . $emp_id . ' created from candidate. Portal password: ' . $default_pw, 'emp_id' => $emp_id];
}

/* ---------- Performance reviews ---------- */

function get_review_cycles($conn): array
{
    $res = $conn->query('SELECT * FROM review_cycles ORDER BY period_start DESC');
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function get_performance_reviews_for_cycle($conn, int $cycle_id, ?int $branch_id = null): array
{
    $sql = 'SELECT r.*, e.name AS emp_name, e.department, rev.name AS reviewer_name
            FROM performance_reviews r
            INNER JOIN employees e ON e.emp_id = r.emp_id
            LEFT JOIN employees rev ON rev.emp_id = r.reviewer_emp_id
            WHERE r.cycle_id = ?';
    $types = 'i';
    $params = [$cycle_id];
    if ($branch_id !== null) {
        $sql .= ' AND e.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    $sql .= ' ORDER BY e.name ASC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function save_review_cycle($conn, array $post, string $created_by): array
{
    $id = (int) ($post['id'] ?? 0);
    $name = trim($post['name'] ?? '');
    $start = trim($post['period_start'] ?? '');
    $end = trim($post['period_end'] ?? '');
    $status_raw = $post['status'] ?? 'draft';
    $status = in_array($status_raw, ['draft', 'active', 'closed'], true) ? $status_raw : 'draft';

    if ($name === '' || $start === '' || $end === '') {
        return ['ok' => false, 'message' => 'Cycle name and dates are required.'];
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE review_cycles SET name=?, period_start=?, period_end=?, status=? WHERE id=?');
        $stmt->bind_param('ssssi', $name, $start, $end, $status, $id);
    } else {
        $stmt = $conn->prepare('INSERT INTO review_cycles (name, period_start, period_end, status, created_by) VALUES (?,?,?,?,?)');
        $stmt->bind_param('sssss', $name, $start, $end, $status, $created_by);
    }

    if (!$stmt->execute()) {
        return ['ok' => false, 'message' => 'Could not save review cycle.'];
    }

    $cycle_id = $id > 0 ? $id : (int) $conn->insert_id;
    if ($id === 0 && $status === 'active') {
        generate_reviews_for_cycle($conn, $cycle_id);
    }

    return ['ok' => true, 'message' => $id > 0 ? 'Review cycle updated.' : 'Review cycle created.', 'id' => $cycle_id];
}

function generate_reviews_for_cycle($conn, int $cycle_id): int
{
    $bf = branch_employees_sql('e');
    $sql = 'SELECT e.emp_id, e.manager_emp_id FROM employees e WHERE e.is_active = 1' . $bf['sql'];
    $stmt = $conn->prepare($sql);
    bind_branch_stmt_params($stmt, $bf['types'], $bf['params']);
    $stmt->execute();
    $res = $stmt->get_result();
    $count = 0;
    $ins = $conn->prepare('INSERT IGNORE INTO performance_reviews (cycle_id, emp_id, reviewer_emp_id, status) VALUES (?, ?, ?, ?)');
    $status = 'pending';
    while ($row = $res->fetch_assoc()) {
        $reviewer = $row['manager_emp_id'] ?: null;
        $ins->bind_param('isss', $cycle_id, $row['emp_id'], $reviewer, $status);
        if ($ins->execute() && $conn->affected_rows > 0) {
            $count++;
        }
    }
    return $count;
}

function save_performance_review($conn, array $post): array
{
    $id = (int) ($post['review_id'] ?? 0);
    $kra = trim($post['kra_summary'] ?? '');
    $manager_notes = trim($post['manager_notes'] ?? '');
    $overall = ($post['overall_rating'] ?? '') !== '' ? (float) $post['overall_rating'] : null;
    $status_raw = $post['status'] ?? 'manager_review';
    $status = in_array($status_raw, ['pending', 'self_review', 'manager_review', 'completed'], true) ? $status_raw : 'manager_review';

    $kpis = [];
    $names = $post['kpi_name'] ?? [];
    $targets = $post['kpi_target'] ?? [];
    $actuals = $post['kpi_actual'] ?? [];
    $weights = $post['kpi_weight'] ?? [];
    foreach ($names as $i => $n) {
        $n = trim((string) $n);
        if ($n === '') {
            continue;
        }
        $target = (float) ($targets[$i] ?? 0);
        $actual = (float) ($actuals[$i] ?? 0);
        $weight = (float) ($weights[$i] ?? 1);
        $score = $target > 0 ? min(100, round(($actual / $target) * 100, 2)) : 0;
        $kpis[] = ['name' => $n, 'target' => $target, 'actual' => $actual, 'weight' => $weight, 'score' => $score];
    }
    $kpi_json = json_encode($kpis);

    $stmt = $conn->prepare('UPDATE performance_reviews SET kra_summary=?, kpi_data=?, manager_notes=?, overall_rating=?, status=?, reviewed_at=NOW() WHERE id=?');
    $stmt->bind_param('sssdsi', $kra, $kpi_json, $manager_notes, $overall, $status, $id);

    if ($stmt->execute()) {
        return ['ok' => true, 'message' => $id > 0 ? 'Performance review saved.' : 'Performance review saved.'];
    }
    return ['ok' => false, 'message' => 'Could not save review.'];
}

function get_employee_performance_reviews($conn, string $emp_id): array
{
    $stmt = $conn->prepare('SELECT r.*, c.name AS cycle_name, c.period_start, c.period_end, c.status AS cycle_status
        FROM performance_reviews r
        INNER JOIN review_cycles c ON c.id = r.cycle_id
        WHERE r.emp_id = ?
        ORDER BY c.period_start DESC');
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function save_employee_self_review($conn, string $emp_id, array $post): array
{
    $id = (int) ($post['review_id'] ?? 0);
    $notes = trim($post['employee_self_notes'] ?? '');
    $kra = trim($post['kra_summary'] ?? '');

    $chk = $conn->prepare('SELECT r.id, r.status, c.status AS cycle_status FROM performance_reviews r INNER JOIN review_cycles c ON c.id = r.cycle_id WHERE r.id = ? AND r.emp_id = ?');
    $chk->bind_param('is', $id, $emp_id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if (!$row) {
        return ['ok' => false, 'message' => 'Review not found.'];
    }
    if (($row['cycle_status'] ?? '') === 'closed') {
        return ['ok' => false, 'message' => 'This review cycle is closed.'];
    }
    if (!in_array($row['status'] ?? '', ['pending', 'self_review'], true)) {
        return ['ok' => false, 'message' => 'Self-review is no longer editable.'];
    }

    $stmt = $conn->prepare('UPDATE performance_reviews SET kra_summary=?, employee_self_notes=?, status=? WHERE id=? AND emp_id=?');
    $status = 'self_review';
    $stmt->bind_param('sssis', $kra, $notes, $status, $id, $emp_id);
    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'Self-review submitted. Your manager will complete the review.'];
    }
    return ['ok' => false, 'message' => 'Could not save self-review.'];
}

function bulk_assign_managers($conn, array $emp_ids, ?string $manager_emp_id, ?int $branch_id): array
{
    $emp_ids = array_values(array_unique(array_filter(array_map('trim', $emp_ids))));
    if ($emp_ids === []) {
        return ['ok' => false, 'message' => 'Select at least one employee.'];
    }

    if ($manager_emp_id !== null && $manager_emp_id !== '') {
        $mgr_chk = $conn->prepare('SELECT emp_id FROM employees WHERE emp_id = ? AND is_active = 1');
        $mgr_chk->bind_param('s', $manager_emp_id);
        $mgr_chk->execute();
        if (!$mgr_chk->get_result()->fetch_assoc()) {
            return ['ok' => false, 'message' => 'Selected manager not found.'];
        }
    } else {
        $manager_emp_id = null;
    }

    $updated = 0;
    $stmt = $conn->prepare('UPDATE employees SET manager_emp_id = ? WHERE emp_id = ? AND is_active = 1');
    foreach ($emp_ids as $emp_id) {
        if ($manager_emp_id !== null && $emp_id === $manager_emp_id) {
            continue;
        }
        if ($branch_id !== null) {
            $bf = $conn->prepare('SELECT emp_id FROM employees WHERE emp_id = ? AND branch_id = ?');
            $bf->bind_param('si', $emp_id, $branch_id);
            $bf->execute();
            if (!$bf->get_result()->fetch_assoc()) {
                continue;
            }
        }
        $stmt->bind_param('ss', $manager_emp_id, $emp_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $updated++;
        }
    }

    if ($updated === 0) {
        return ['ok' => false, 'message' => 'No employees were updated.'];
    }
    return ['ok' => true, 'message' => "Reporting manager updated for {$updated} employee(s)."];
}

function get_public_job_openings($conn): array
{
    $sql = 'SELECT j.*, d.name AS department_name, des.name AS designation_name, b.name AS branch_name
            FROM job_openings j
            LEFT JOIN departments d ON d.id = j.department_id
            LEFT JOIN designations des ON des.id = j.designation_id
            INNER JOIN branches b ON b.id = j.branch_id
            WHERE j.status = ?
            ORDER BY j.created_at DESC';
    $open = 'open';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $open);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ---------- Expenses ---------- */

function get_pending_expense_claims($conn, ?int $branch_id): array
{
    $sql = 'SELECT x.*, e.name AS emp_name FROM expense_claims x INNER JOIN employees e ON e.emp_id = x.emp_id WHERE x.request_status = ?';
    $pending = 'pending';
    $types = 's';
    $params = [$pending];
    if ($branch_id !== null) {
        $sql .= ' AND x.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    $sql .= ' ORDER BY x.created_at ASC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_expense_claims($conn, ?int $branch_id, ?string $status = null, int $limit = 200): array
{
    $sql = 'SELECT x.*, e.name AS emp_name FROM expense_claims x INNER JOIN employees e ON e.emp_id = x.emp_id WHERE 1=1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND x.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    if ($status) {
        $sql .= ' AND x.request_status = ?';
        $types .= 's';
        $params[] = $status;
    }
    $sql .= ' ORDER BY x.created_at DESC LIMIT ' . (int) $limit;
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function submit_expense_claim($conn, string $emp_id, int $branch_id, array $post, ?array $receipt = null): array
{
    $date = trim($post['claim_date'] ?? date('Y-m-d'));
    $category = trim($post['category'] ?? 'General');
    $amount = (float) ($post['amount'] ?? 0);
    $description = trim($post['description'] ?? '');

    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'Amount must be greater than zero.'];
    }

    $receipt_path = null;
    $receipt_filename = null;
    if ($receipt && ($receipt['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $up = hrm_store_upload($receipt, 'expenses', ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
        if (!$up['ok']) {
            return $up;
        }
        $receipt_path = $up['path'];
        $receipt_filename = $up['filename'];
    }

    $stmt = $conn->prepare('INSERT INTO expense_claims (emp_id, branch_id, claim_date, category, amount, description, receipt_path, receipt_filename) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('sissdsss', $emp_id, $branch_id, $date, $category, $amount, $description, $receipt_path, $receipt_filename);

    if ($stmt->execute()) {
        return ['ok' => true, 'message' => 'Expense claim submitted for approval.'];
    }
    return ['ok' => false, 'message' => 'Could not submit expense claim.'];
}

function review_expense_claim($conn, int $claim_id, string $action, string $reviewer, string $note, ?int $branch_id): array
{
    $stmt = $conn->prepare('SELECT * FROM expense_claims WHERE id = ? AND request_status = ?');
    $pending = 'pending';
    $stmt->bind_param('is', $claim_id, $pending);
    $stmt->execute();
    $claim = $stmt->get_result()->fetch_assoc();
    if (!$claim || ($branch_id !== null && (int) $claim['branch_id'] !== $branch_id)) {
        return ['ok' => false, 'message' => 'Expense claim not found.'];
    }

    $status = $action === 'approve' ? 'approved' : 'rejected';
    $upd = $conn->prepare('UPDATE expense_claims SET request_status=?, reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?');
    $upd->bind_param('sssi', $status, $reviewer, $note, $claim_id);
    if ($upd->execute()) {
        return ['ok' => true, 'message' => 'Expense claim ' . $status . '.'];
    }
    return ['ok' => false, 'message' => 'Could not update expense claim.'];
}

function count_pending_expense_claims($conn, ?int $branch_id): int
{
    return count(get_pending_expense_claims($conn, $branch_id));
}

/* ---------- Assets ---------- */

function get_assets($conn, ?int $branch_id, ?string $status = null): array
{
    $sql = 'SELECT a.*, (
        SELECT e.name FROM asset_assignments aa INNER JOIN employees e ON e.emp_id = aa.emp_id
        WHERE aa.asset_id = a.id AND aa.returned_at IS NULL LIMIT 1
    ) AS assigned_to_name FROM assets a WHERE 1=1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND a.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    if ($status) {
        $sql .= ' AND a.status = ?';
        $types .= 's';
        $params[] = $status;
    }
    $sql .= ' ORDER BY a.asset_tag ASC';
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function save_asset($conn, array $post): array
{
    $id = (int) ($post['id'] ?? 0);
    $tag = strtoupper(trim($post['asset_tag'] ?? ''));
    $name = trim($post['name'] ?? '');
    $category = trim($post['category'] ?? 'General');
    $serial = trim($post['serial_no'] ?? '');
    $branch_id = (int) ($post['branch_id'] ?? branch_id_for_write());
    $purchase_date = trim($post['purchase_date'] ?? '') ?: null;
    $value = ($post['asset_value'] ?? '') !== '' ? (float) $post['asset_value'] : null;
    $notes = trim($post['notes'] ?? '');

    if ($tag === '' || $name === '') {
        return ['ok' => false, 'message' => 'Asset tag and name are required.'];
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE assets SET asset_tag=?, name=?, category=?, serial_no=?, branch_id=?, purchase_date=?, asset_value=?, notes=? WHERE id=?');
        $stmt->bind_param('ssssisdsi', $tag, $name, $category, $serial, $branch_id, $purchase_date, $value, $notes, $id);
    } else {
        $stmt = $conn->prepare('INSERT INTO assets (asset_tag, name, category, serial_no, branch_id, purchase_date, asset_value, notes, status) VALUES (?,?,?,?,?,?,?,?,"available")');
        $stmt->bind_param('ssssisds', $tag, $name, $category, $serial, $branch_id, $purchase_date, $value, $notes);
    }

    if ($stmt->execute()) {
        return ['ok' => true, 'message' => $id > 0 ? 'Asset updated.' : 'Asset added.'];
    }
    return ['ok' => false, 'message' => 'Could not save asset. Tag may already exist.'];
}

function assign_asset($conn, int $asset_id, string $emp_id, string $assigned_by, string $notes = ''): array
{
    $stmt = $conn->prepare('SELECT * FROM assets WHERE id = ? AND status IN ("available","assigned")');
    $stmt->bind_param('i', $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();
    if (!$asset) {
        return ['ok' => false, 'message' => 'Asset not found.'];
    }

    $chk = $conn->prepare('SELECT id FROM asset_assignments WHERE asset_id = ? AND returned_at IS NULL');
    $chk->bind_param('i', $asset_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        return ['ok' => false, 'message' => 'Asset is already assigned. Return it first.'];
    }

    $ins = $conn->prepare('INSERT INTO asset_assignments (asset_id, emp_id, assigned_at, condition_notes, assigned_by) VALUES (?, ?, NOW(), ?, ?)');
    $ins->bind_param('isss', $asset_id, $emp_id, $notes, $assigned_by);
    if (!$ins->execute()) {
        return ['ok' => false, 'message' => 'Could not assign asset.'];
    }

    $conn->query('UPDATE assets SET status = "assigned" WHERE id = ' . (int) $asset_id);
    return ['ok' => true, 'message' => 'Asset assigned to ' . $emp_id . '.'];
}

function return_asset($conn, int $asset_id, string $notes = ''): array
{
    $stmt = $conn->prepare('SELECT id FROM asset_assignments WHERE asset_id = ? AND returned_at IS NULL ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $asset_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return ['ok' => false, 'message' => 'No active assignment found.'];
    }

    $upd = $conn->prepare('UPDATE asset_assignments SET returned_at = NOW(), condition_notes = CONCAT(IFNULL(condition_notes,""), ?) WHERE id = ?');
    $note_append = $notes !== '' ? "\nReturn: " . $notes : '';
    $upd->bind_param('si', $note_append, $row['id']);
    $upd->execute();
    $conn->query('UPDATE assets SET status = "available" WHERE id = ' . (int) $asset_id);
    return ['ok' => true, 'message' => 'Asset returned to inventory.'];
}

function retire_asset($conn, int $asset_id): array
{
    $stmt = $conn->prepare('SELECT status FROM assets WHERE id = ?');
    $stmt->bind_param('i', $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();
    if (!$asset) {
        return ['ok' => false, 'message' => 'Asset not found.'];
    }
    if (($asset['status'] ?? '') === 'assigned') {
        return ['ok' => false, 'message' => 'Return the asset before retiring it.'];
    }
    $upd = $conn->prepare('UPDATE assets SET status = "retired" WHERE id = ?');
    $upd->bind_param('i', $asset_id);
    if ($upd->execute()) {
        return ['ok' => true, 'message' => 'Asset marked as retired.'];
    }
    return ['ok' => false, 'message' => 'Could not retire asset.'];
}

/* ---------- Exit & F&F ---------- */

function get_employee_exits($conn, ?int $branch_id, ?string $status = null): array
{
    $sql = 'SELECT ex.*, e.name AS emp_name, e.department, f.salary_due, f.leave_encashment, f.notice_pay, f.deductions, f.net_payable, f.status AS fnf_status
            FROM employee_exits ex
            INNER JOIN employees e ON e.emp_id = ex.emp_id
            LEFT JOIN fnf_settlements f ON f.exit_id = ex.id
            WHERE 1=1';
    $types = '';
    $params = [];
    if ($branch_id !== null) {
        $sql .= ' AND ex.branch_id = ?';
        $types .= 'i';
        $params[] = $branch_id;
    }
    if ($status) {
        $sql .= ' AND ex.status = ?';
        $types .= 's';
        $params[] = $status;
    }
    $sql .= ' ORDER BY ex.created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function initiate_employee_exit($conn, array $post, string $initiated_by, array $settings): array
{
    $emp_id = trim($post['emp_id'] ?? '');
    $employee = $conn->prepare('SELECT * FROM employees WHERE emp_id = ?');
    $employee->bind_param('s', $emp_id);
    $employee->execute();
    $emp = $employee->get_result()->fetch_assoc();
    if (!$emp) {
        return ['ok' => false, 'message' => 'Employee not found.'];
    }
    $branch_filter = get_active_branch_id();
    if ($branch_filter !== null && (int) $emp['branch_id'] !== $branch_filter) {
        return ['ok' => false, 'message' => 'Employee is not in your branch.'];
    }

    $exit_type_raw = $post['exit_type'] ?? 'resignation';
    $exit_type = in_array($exit_type_raw, ['resignation', 'termination', 'retirement'], true) ? $exit_type_raw : 'resignation';
    $resignation_date = trim($post['resignation_date'] ?? '') ?: null;
    $lwd = trim($post['last_working_day'] ?? '');
    $reason = trim($post['reason'] ?? '');

    if ($lwd === '') {
        return ['ok' => false, 'message' => 'Last working day is required.'];
    }

    $chk = $conn->prepare("SELECT id FROM employee_exits WHERE emp_id = ? AND status NOT IN ('completed')");
    $chk->bind_param('s', $emp_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        return ['ok' => false, 'message' => 'An exit process is already in progress for this employee.'];
    }

    $branch_id = (int) $emp['branch_id'];
    $ins = $conn->prepare('INSERT INTO employee_exits (emp_id, branch_id, exit_type, resignation_date, last_working_day, reason, status, initiated_by) VALUES (?,?,?,?,?,?,"initiated",?)');
    $ins->bind_param('sisssss', $emp_id, $branch_id, $exit_type, $resignation_date, $lwd, $reason, $initiated_by);
    if (!$ins->execute()) {
        return ['ok' => false, 'message' => 'Could not initiate exit.'];
    }

    $exit_id = (int) $conn->insert_id;
    $fnf = calculate_fnf_settlement($conn, $emp, $exit_id, $settings, $post);
    save_fnf_settlement($conn, $exit_id, $fnf);

    return ['ok' => true, 'message' => 'Exit initiated and F&F draft calculated.', 'exit_id' => $exit_id];
}

function calculate_fnf_settlement($conn, array $employee, int $exit_id, array $settings, array $post): array
{
    require_once __DIR__ . '/salary_helper.php';
    require_once __DIR__ . '/payroll_extensions.php';

    $lwd = $post['last_working_day'] ?? date('Y-m-d');
    $year = (int) date('Y', strtotime($lwd));
    $month = (int) date('n', strtotime($lwd));
    $salary = calculate_employee_salary_full($conn, $employee, $year, $month, $settings);
    $salary_due = (float) ($post['salary_due'] ?? $salary['net_salary'] ?? 0);

    $balances = get_employee_leave_balances($conn, $employee['emp_id'], $settings);
    $daily_rate = (float) $employee['base_salary'] / max(1, (int) get_working_days_per_month($settings));
    $leave_encashment = 0.0;
    foreach ($balances as $code => $bal) {
        if (in_array($code, ['PL', 'CL'], true)) {
            $leave_encashment += (float) $bal * $daily_rate;
        }
    }
    $leave_encashment = (float) ($post['leave_encashment'] ?? round($leave_encashment, 2));
    $notice_pay = (float) ($post['notice_pay'] ?? 0);
    $deductions = (float) ($post['deductions'] ?? 0);
    $net = max(0, round($salary_due + $leave_encashment + $notice_pay - $deductions, 2));

    return [
        'salary_due' => $salary_due,
        'leave_encashment' => $leave_encashment,
        'notice_pay' => $notice_pay,
        'deductions' => $deductions,
        'net_payable' => $net,
        'notes' => trim($post['fnf_notes'] ?? ''),
    ];
}

function save_fnf_settlement($conn, int $exit_id, array $data): void
{
    $stmt = $conn->prepare('
        INSERT INTO fnf_settlements (exit_id, salary_due, leave_encashment, notice_pay, deductions, net_payable, notes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, "draft")
        ON DUPLICATE KEY UPDATE salary_due=VALUES(salary_due), leave_encashment=VALUES(leave_encashment),
        notice_pay=VALUES(notice_pay), deductions=VALUES(deductions), net_payable=VALUES(net_payable), notes=VALUES(notes)
    ');
    $stmt->bind_param('iddddds', $exit_id, $data['salary_due'], $data['leave_encashment'], $data['notice_pay'], $data['deductions'], $data['net_payable'], $data['notes']);
    $stmt->execute();
}

function update_exit_status($conn, int $exit_id, string $status, string $admin_user, array $settings): array
{
    $allowed = ['initiated', 'clearance', 'fnf_pending', 'completed'];
    if (!in_array($status, $allowed, true)) {
        return ['ok' => false, 'message' => 'Invalid status.'];
    }

    $stmt = $conn->prepare('SELECT ex.*, e.emp_id FROM employee_exits ex INNER JOIN employees e ON e.emp_id = ex.emp_id WHERE ex.id = ?');
    $stmt->bind_param('i', $exit_id);
    $stmt->execute();
    $exit = $stmt->get_result()->fetch_assoc();
    if (!$exit) {
        return ['ok' => false, 'message' => 'Exit record not found.'];
    }

    $upd = $conn->prepare('UPDATE employee_exits SET status = ? WHERE id = ?');
    $upd->bind_param('si', $status, $exit_id);
    $upd->execute();

    if ($status === 'completed') {
        $deact = $conn->prepare('UPDATE employees SET is_active = 0 WHERE emp_id = ?');
        $deact->bind_param('s', $exit['emp_id']);
        $deact->execute();

        $fnf = $conn->prepare('UPDATE fnf_settlements SET status = "paid", approved_by = ?, approved_at = NOW(), paid_at = NOW() WHERE exit_id = ?');
        $fnf->bind_param('si', $admin_user, $exit_id);
        $fnf->execute();

        return_all_employee_assets($conn, $exit['emp_id']);
    }

    return ['ok' => true, 'message' => 'Exit status updated to ' . $status . '.'];
}

function approve_fnf_settlement($conn, int $exit_id, string $admin_user): array
{
    $upd = $conn->prepare('UPDATE fnf_settlements SET status = "approved", approved_by = ?, approved_at = NOW() WHERE exit_id = ?');
    $upd->bind_param('si', $admin_user, $exit_id);
    if ($upd->execute()) {
        $conn->query('UPDATE employee_exits SET status = "fnf_pending" WHERE id = ' . (int) $exit_id);
        return ['ok' => true, 'message' => 'F&F settlement approved.'];
    }
    return ['ok' => false, 'message' => 'Could not approve F&F.'];
}

function return_all_employee_assets($conn, string $emp_id): void
{
    $stmt = $conn->prepare('SELECT asset_id FROM asset_assignments WHERE emp_id = ? AND returned_at IS NULL');
    $stmt->bind_param('s', $emp_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        return_asset($conn, (int) $row['asset_id'], 'Auto-return on employee exit');
    }
}
