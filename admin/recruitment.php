<?php
require_once 'includes/admin_page_init.php';
admin_page_init('recruitment');
require_once 'includes/hrm_modules_helper.php';

$branch_id = get_active_branch_id();
$active_branch_label = get_branch_label($conn, $branch_id);
$write_branch = $branch_id ?? branch_id_for_write();
$jobs = get_job_openings($conn, $branch_id);
$departments = get_departments($conn, $branch_id);
$designations = get_designations($conn);
$branches = get_branches($conn);
$selected_job = (int) ($_GET['job_id'] ?? 0);
$candidates = $selected_job ? get_candidates_for_job($conn, $selected_job) : [];
$stages = ['applied' => 'Applied', 'screening' => 'Screening', 'interview' => 'Interview', 'offered' => 'Offered', 'hired' => 'Hired', 'rejected' => 'Rejected'];
$stage_class = ['applied' => 'stage-applied', 'screening' => 'stage-screening', 'interview' => 'stage-interview', 'offered' => 'stage-offered', 'hired' => 'stage-hired', 'rejected' => 'stage-rejected'];
$pipeline_stages = ['applied', 'screening', 'interview', 'offered', 'hired', 'rejected'];

$open_jobs = 0;
$closed_jobs = 0;
$total_candidates = 0;
$hired_count = 0;
$job_meta = [];
foreach ($jobs as $job) {
    $status = $job['status'] ?? 'open';
    if ($status === 'open') {
        $open_jobs++;
    } else {
        $closed_jobs++;
    }
    $cands = get_candidates_for_job($conn, (int) $job['id']);
    $job_meta[(int) $job['id']] = ['count' => count($cands), 'cands' => $cands];
    $total_candidates += count($cands);
    foreach ($cands as $c) {
        if (($c['stage'] ?? '') === 'hired') {
            $hired_count++;
        }
    }
}

$selected_job_row = null;
foreach ($jobs as $job) {
    if ((int) $job['id'] === $selected_job) {
        $selected_job_row = $job;
        break;
    }
}

$stage_counts = array_fill_keys($pipeline_stages, 0);
foreach ($candidates as $c) {
    $st = $c['stage'] ?? 'applied';
    if (isset($stage_counts[$st])) {
        $stage_counts[$st]++;
    }
}

$workflow_step = 1;
if ($jobs !== []) {
    $workflow_step = 2;
}
if ($selected_job) {
    $workflow_step = 3;
}
if ($selected_job && $candidates !== []) {
    $workflow_step = 4;
}
?>
<div class="hrm-page recruitment-page">
    <div class="page-header page-header-row">
        <div class="page-header-main">
            <p class="page-eyebrow">People</p>
            <h2>Recruitment &amp; onboarding</h2>
            <p>Hire pipeline<?php echo $branch_id !== null ? ' at <strong>' . htmlspecialchars($active_branch_label) . '</strong>' : ''; ?> — post jobs, track candidates, convert to employees.</p>
        </div>
        <div class="page-header-actions">
            <a href="recruitment_export.php" class="btn btn-outline">Export CSV</a>
            <a href="careers.php" class="btn btn-outline" target="_blank" rel="noopener">Careers page</a>
            <a href="departments.php" class="btn btn-outline">Masters</a>
            <a href="employees.php" class="btn btn-header">Employees</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status hrm-status-row">
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo $open_jobs; ?></strong><span>Open positions</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo count($jobs); ?></strong><span>Total jobs</span></div></div>
        <div class="settings-status-chip neutral"><span class="status-dot"></span><div><strong><?php echo $total_candidates; ?></strong><span>Candidates</span></div></div>
        <div class="settings-status-chip ok"><span class="status-dot"></span><div><strong><?php echo $hired_count; ?></strong><span>Hired</span></div></div>
    </div>

    <nav class="recruitment-workflow" aria-label="Hiring workflow">
        <div class="recruitment-workflow-step<?php echo $workflow_step >= 1 ? ' is-done' : ''; ?><?php echo $workflow_step === 1 ? ' is-current' : ''; ?>">
            <span class="recruitment-workflow-num">1</span>
            <span class="recruitment-workflow-label">Post a job</span>
        </div>
        <span class="recruitment-workflow-arrow" aria-hidden="true">→</span>
        <div class="recruitment-workflow-step<?php echo $workflow_step >= 2 ? ' is-done' : ''; ?><?php echo $workflow_step === 2 ? ' is-current' : ''; ?>">
            <span class="recruitment-workflow-num">2</span>
            <span class="recruitment-workflow-label">Select position</span>
        </div>
        <span class="recruitment-workflow-arrow" aria-hidden="true">→</span>
        <div class="recruitment-workflow-step<?php echo $workflow_step >= 3 ? ' is-done' : ''; ?><?php echo $workflow_step === 3 ? ' is-current' : ''; ?>">
            <span class="recruitment-workflow-num">3</span>
            <span class="recruitment-workflow-label">Add candidates</span>
        </div>
        <span class="recruitment-workflow-arrow" aria-hidden="true">→</span>
        <div class="recruitment-workflow-step<?php echo $workflow_step >= 4 ? ' is-done' : ''; ?><?php echo $workflow_step === 4 ? ' is-current' : ''; ?>">
            <span class="recruitment-workflow-num">4</span>
            <span class="recruitment-workflow-label">Hire &amp; onboard</span>
        </div>
    </nav>

    <div class="recruitment-layout">
        <section class="panel panel-elevated recruitment-jobs-panel">
            <div class="panel-header masters-panel-head">
                <div class="panel-title-group">
                    <h3>Open positions</h3>
                    <span class="panel-badge" id="recruitJobCount"><?php echo count($jobs); ?> listed</span>
                </div>
                <div class="masters-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="recruitJobSearch" placeholder="Search jobs…" autocomplete="off" aria-label="Search jobs">
                    <button type="button" class="masters-search-clear" id="recruitJobClear" hidden aria-label="Clear">&times;</button>
                </div>
            </div>
            <div class="panel-body padded">
                <details class="recruitment-post-job-toggle settings-add-panel masters-add-panel recruitment-add-job"<?php echo $jobs === [] ? ' open' : ''; ?>>
                    <summary class="recruitment-post-job-summary">
                        <span class="settings-add-panel-icon recruitment-add-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        </span>
                        <span class="recruitment-post-job-summary-text">
                            <strong>Post a new job</strong>
                            <span>Title, branch, department and openings</span>
                        </span>
                        <span class="recruitment-post-job-chevron" aria-hidden="true"></span>
                    </summary>
                    <form method="POST" action="recruitment_save.php" class="settings-add-panel-body recruitment-post-job-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="recruitment_action" value="job">
                        <div class="recruitment-add-grid">
                            <div class="form-group recruitment-field-wide">
                                <label for="job_title">Job title</label>
                                <input type="text" name="title" id="job_title" required placeholder="Software Engineer" class="settings-add-input">
                            </div>
                            <div class="form-group">
                                <label for="job_branch">Branch</label>
                                <select name="branch_id" id="job_branch" class="settings-add-select" required>
                                    <?php foreach ($branches as $b): ?>
                                        <option value="<?php echo (int) $b['id']; ?>" <?php echo (int) $b['id'] === $write_branch ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="job_openings">Openings</label>
                                <input type="number" name="openings_count" id="job_openings" value="1" min="1" class="settings-add-input">
                            </div>
                            <div class="form-group">
                                <label for="job_dept">Department</label>
                                <select name="department_id" id="job_dept" class="settings-add-select">
                                    <option value="">—</option>
                                    <?php foreach ($departments as $d): ?><option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="job_desig">Designation</label>
                                <select name="designation_id" id="job_desig" class="settings-add-select">
                                    <option value="">—</option>
                                    <?php foreach ($designations as $d): ?><option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group recruitment-field-wide">
                                <label for="job_desc">Description</label>
                                <input type="text" name="description" id="job_desc" placeholder="Brief role summary" class="settings-add-input">
                            </div>
                        </div>
                        <div class="settings-add-panel-foot">
                            <button type="submit" class="btn btn-header settings-add-submit">Post job</button>
                        </div>
                    </form>
                </details>

                <?php if ($jobs !== []): ?>
                <div class="recruitment-filter-pills" role="group" aria-label="Filter jobs by status">
                    <button type="button" class="recruitment-filter-pill is-active" data-job-filter="all">All <span class="recruitment-filter-count"><?php echo count($jobs); ?></span></button>
                    <button type="button" class="recruitment-filter-pill" data-job-filter="open">Open <span class="recruitment-filter-count"><?php echo $open_jobs; ?></span></button>
                    <button type="button" class="recruitment-filter-pill" data-job-filter="closed">Closed <span class="recruitment-filter-count"><?php echo $closed_jobs; ?></span></button>
                </div>
                <?php endif; ?>

                <?php if ($jobs === []): ?>
                    <div class="masters-empty" id="recruitJobEmpty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        <h4>No job openings yet</h4>
                        <p>Expand <strong>Post a new job</strong> above and publish your first role.</p>
                    </div>
                <?php else: ?>
                    <ul class="recruitment-job-list" id="recruitJobList">
                        <?php foreach ($jobs as $job):
                            $jid = (int) $job['id'];
                            $cand_count = $job_meta[$jid]['count'] ?? 0;
                            $is_selected = $jid === $selected_job;
                            $job_status = $job['status'] ?? 'open';
                            $filter_status = $job_status === 'open' ? 'open' : 'closed';
                            $search = strtolower($job['title'] . ' ' . ($job['department_name'] ?? '') . ' ' . $job_status);
                        ?>
                        <li class="recruitment-job-card<?php echo $is_selected ? ' is-selected' : ''; ?><?php echo $job_status !== 'open' ? ' is-closed' : ''; ?>" data-search="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo htmlspecialchars($filter_status, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="recruitment-job-row">
                            <a href="recruitment.php?job_id=<?php echo $jid; ?>#pipeline" class="recruitment-job-link">
                                <div class="recruitment-job-main">
                                    <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                    <span class="recruitment-job-meta">
                                        <?php echo htmlspecialchars($job['department_name'] ?? 'No department'); ?>
                                        · <?php echo (int) ($job['openings_count'] ?? 1); ?> opening<?php echo (int) ($job['openings_count'] ?? 1) === 1 ? '' : 's'; ?>
                                    </span>
                                </div>
                                <div class="recruitment-job-side">
                                    <span class="recruitment-cand-count"><?php echo $cand_count; ?> candidate<?php echo $cand_count === 1 ? '' : 's'; ?></span>
                                    <span class="badge <?php echo $job_status === 'open' ? 'badge-present' : 'badge-neutral'; ?>"><?php echo htmlspecialchars($job_status); ?></span>
                                    <span class="recruitment-job-hint">View pipeline →</span>
                                </div>
                            </a>
                            <button type="button" class="masters-edit-btn recruitment-job-edit-btn" aria-expanded="false" aria-controls="recruit-edit-job-<?php echo $jid; ?>">
                                <svg class="masters-edit-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                <span>Edit</span>
                            </button>
                            </div>
                            <div class="masters-edit-panel-wrap recruitment-job-edit-panel" id="recruit-edit-job-<?php echo $jid; ?>" hidden>
                                <div class="masters-edit-panel">
                                    <div class="masters-edit-panel-head">
                                        <strong>Edit job opening</strong>
                                        <span>Update title, branch, department, status, and description</span>
                                    </div>
                                    <form method="POST" action="recruitment_save.php" class="masters-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="recruitment_action" value="job">
                                        <input type="hidden" name="id" value="<?php echo $jid; ?>">
                                        <div class="recruitment-add-grid">
                                            <div class="form-group recruitment-field-wide">
                                                <label>Job title</label>
                                                <input type="text" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required class="settings-add-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Branch</label>
                                                <select name="branch_id" class="settings-add-select" required>
                                                    <?php foreach ($branches as $b): ?>
                                                        <option value="<?php echo (int) $b['id']; ?>" <?php echo (int) $b['id'] === (int) $job['branch_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Openings</label>
                                                <input type="number" name="openings_count" value="<?php echo (int) ($job['openings_count'] ?? 1); ?>" min="1" class="settings-add-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Department</label>
                                                <select name="department_id" class="settings-add-select">
                                                    <option value="">—</option>
                                                    <?php foreach ($departments as $d): ?>
                                                        <option value="<?php echo (int) $d['id']; ?>" <?php echo (int) ($job['department_id'] ?? 0) === (int) $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Designation</label>
                                                <select name="designation_id" class="settings-add-select">
                                                    <option value="">—</option>
                                                    <?php foreach ($designations as $d): ?>
                                                        <option value="<?php echo (int) $d['id']; ?>" <?php echo (int) ($job['designation_id'] ?? 0) === (int) $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="settings-add-select">
                                                    <option value="open" <?php echo $job_status === 'open' ? 'selected' : ''; ?>>Open</option>
                                                    <option value="on_hold" <?php echo $job_status === 'on_hold' ? 'selected' : ''; ?>>On hold</option>
                                                    <option value="closed" <?php echo $job_status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                </select>
                                            </div>
                                            <div class="form-group recruitment-field-wide">
                                                <label>Description</label>
                                                <input type="text" name="description" value="<?php echo htmlspecialchars($job['description'] ?? ''); ?>" class="settings-add-input">
                                            </div>
                                        </div>
                                        <div class="masters-edit-panel-foot">
                                            <button type="submit" class="btn btn-header btn-sm">Save job</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="masters-empty masters-search-empty" id="recruitJobNoMatch" hidden><h4>No matches</h4><p>Try a different search or filter.</p></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel panel-elevated recruitment-pipeline-panel" id="pipeline">
            <?php if ($selected_job && $selected_job_row):
                $job_status = $selected_job_row['status'] ?? 'open';
            ?>
            <div class="panel-header masters-panel-head recruitment-pipeline-head">
                <div class="panel-title-group">
                    <h3><?php echo htmlspecialchars($selected_job_row['title']); ?></h3>
                    <span class="panel-badge"><?php echo count($candidates); ?> candidate<?php echo count($candidates) === 1 ? '' : 's'; ?></span>
                </div>
                <div class="recruitment-pipeline-actions">
                    <?php if ($job_status === 'open'): ?>
                    <form method="POST" action="recruitment_save.php" class="recruitment-inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="recruitment_action" value="job_status">
                        <input type="hidden" name="job_id" value="<?php echo $selected_job; ?>">
                        <input type="hidden" name="status" value="closed">
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Close this job? New candidates can still be added manually.');">Close job</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="recruitment_save.php" class="recruitment-inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="recruitment_action" value="job_status">
                        <input type="hidden" name="job_id" value="<?php echo $selected_job; ?>">
                        <input type="hidden" name="status" value="open">
                        <button type="submit" class="btn btn-outline btn-sm">Reopen job</button>
                    </form>
                    <?php endif; ?>
                    <a href="recruitment.php" class="btn btn-outline btn-sm">All jobs</a>
                </div>
            </div>
            <div class="panel-body padded">
                <div class="recruitment-pipeline-meta">
                    <span class="badge <?php echo $job_status === 'open' ? 'badge-present' : 'badge-neutral'; ?>"><?php echo htmlspecialchars($job_status); ?></span>
                    <?php if (!empty($selected_job_row['department_name'])): ?>
                        <span><?php echo htmlspecialchars($selected_job_row['department_name']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($selected_job_row['designation_name'])): ?>
                        <span><?php echo htmlspecialchars($selected_job_row['designation_name']); ?></span>
                    <?php endif; ?>
                    <span><?php echo (int) ($selected_job_row['openings_count'] ?? 1); ?> opening<?php echo (int) ($selected_job_row['openings_count'] ?? 1) === 1 ? '' : 's'; ?></span>
                </div>

                <div class="recruitment-stage-strip recruitment-stage-strip-interactive" role="group" aria-label="Filter by stage">
                    <button type="button" class="recruitment-stage-pill is-active" data-stage-filter="all">All <span class="recruitment-stage-count"><?php echo count($candidates); ?></span></button>
                    <?php foreach ($pipeline_stages as $st): ?>
                        <button type="button" class="recruitment-stage-pill <?php echo $stage_class[$st]; ?>" data-stage-filter="<?php echo $st; ?>">
                            <?php echo $stages[$st]; ?> <span class="recruitment-stage-count"><?php echo $stage_counts[$st]; ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <details class="recruitment-post-job-toggle settings-add-panel masters-add-panel recruitment-add-candidate"<?php echo $candidates === [] ? ' open' : ''; ?>>
                    <summary class="recruitment-post-job-summary">
                        <span class="settings-add-panel-icon recruitment-cand-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                        </span>
                        <span class="recruitment-post-job-summary-text">
                            <strong>Add candidate</strong>
                            <span>Name, contact, optional resume (PDF or Word)</span>
                        </span>
                        <span class="recruitment-post-job-chevron" aria-hidden="true"></span>
                    </summary>
                    <form method="POST" action="recruitment_save.php" enctype="multipart/form-data" class="settings-add-panel-body">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="recruitment_action" value="candidate">
                        <input type="hidden" name="job_opening_id" value="<?php echo $selected_job; ?>">
                        <div class="recruitment-cand-grid">
                            <div class="form-group"><label for="cand_name">Name</label><input type="text" name="name" id="cand_name" required class="settings-add-input" placeholder="Full name"></div>
                            <div class="form-group"><label for="cand_email">Email</label><input type="email" name="email" id="cand_email" class="settings-add-input" placeholder="email@example.com"></div>
                            <div class="form-group"><label for="cand_phone">Phone</label><input type="text" name="phone" id="cand_phone" class="settings-add-input" placeholder="Mobile number"></div>
                            <div class="form-group"><label for="cand_stage">Starting stage</label>
                                <select name="stage" id="cand_stage" class="settings-add-select"><?php foreach ($stages as $k => $v): ?><option value="<?php echo $k; ?>"<?php echo $k === 'applied' ? ' selected' : ''; ?>><?php echo $v; ?></option><?php endforeach; ?></select>
                            </div>
                            <div class="form-group recruitment-field-wide"><label for="cand_resume">Resume</label><input type="file" name="resume" id="cand_resume" accept=".pdf,.doc,.docx" class="settings-add-input"></div>
                            <div class="form-group recruitment-field-wide"><label for="cand_notes">Notes</label><input type="text" name="notes" id="cand_notes" class="settings-add-input" placeholder="Source, referral, interview notes…"></div>
                        </div>
                        <div class="settings-add-panel-foot">
                            <button type="submit" class="btn btn-header settings-add-submit">Add to pipeline</button>
                        </div>
                    </form>
                </details>

                <?php if ($candidates !== []): ?>
                <div class="masters-search-wrap recruitment-cand-search-wrap">
                    <svg class="masters-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="recruitCandSearch" placeholder="Search candidates…" autocomplete="off" aria-label="Search candidates">
                    <button type="button" class="masters-search-clear" id="recruitCandClear" hidden aria-label="Clear">&times;</button>
                </div>
                <?php endif; ?>

                <?php if ($candidates === []): ?>
                    <div class="masters-empty recruitment-cand-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <h4>No candidates yet</h4>
                        <p>Expand <strong>Add candidate</strong> above to add the first applicant.</p>
                    </div>
                <?php else: ?>
                    <ul class="recruitment-cand-list" id="recruitCandList">
                        <?php foreach ($candidates as $c):
                            $st = $c['stage'] ?? 'applied';
                            $cand_search = strtolower($c['name'] . ' ' . ($c['email'] ?? '') . ' ' . ($c['phone'] ?? '') . ' ' . $st);
                            $resume_url = !empty($c['resume_path']) ? 'uploads/' . $c['resume_path'] : null;
                        ?>
                        <li class="recruitment-cand-card" data-stage="<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars($cand_search, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="recruitment-cand-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($c['name'], 0, 1))); ?></div>
                            <div class="recruitment-cand-body">
                                <div class="recruitment-cand-top">
                                    <strong><?php echo htmlspecialchars($c['name']); ?></strong>
                                    <span class="recruitment-stage-badge <?php echo $stage_class[$st] ?? ''; ?>"><?php echo htmlspecialchars($stages[$st] ?? $st); ?></span>
                                </div>
                                <p class="recruitment-cand-contact">
                                    <?php if ($c['email']): ?><a href="mailto:<?php echo htmlspecialchars($c['email']); ?>"><?php echo htmlspecialchars($c['email']); ?></a><?php endif; ?>
                                    <?php if ($c['phone']): ?><?php echo $c['email'] ? ' · ' : ''; ?><a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $c['phone'])); ?>"><?php echo htmlspecialchars($c['phone']); ?></a><?php endif; ?>
                                </p>
                                <?php if (!empty($c['notes'])): ?>
                                    <p class="recruitment-cand-notes"><?php echo htmlspecialchars($c['notes']); ?></p>
                                <?php endif; ?>

                                <div class="recruitment-cand-actions">
                                    <?php if ($resume_url): ?>
                                        <a href="<?php echo htmlspecialchars($resume_url); ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">View resume</a>
                                    <?php endif; ?>

                                    <?php if (empty($c['hired_emp_id']) && $st !== 'rejected'): ?>
                                    <form method="POST" action="recruitment_save.php" class="recruitment-stage-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="recruitment_action" value="stage">
                                        <input type="hidden" name="candidate_id" value="<?php echo (int) $c['id']; ?>">
                                        <input type="hidden" name="job_opening_id" value="<?php echo $selected_job; ?>">
                                        <label class="sr-only" for="stage_<?php echo (int) $c['id']; ?>">Move to stage</label>
                                        <select name="stage" id="stage_<?php echo (int) $c['id']; ?>" class="settings-add-select recruitment-stage-select" onchange="this.form.submit()">
                                            <?php foreach ($stages as $k => $v): ?>
                                                <option value="<?php echo $k; ?>"<?php echo $k === $st ? ' selected' : ''; ?>><?php echo $v; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <form method="POST" action="recruitment_save.php" class="recruitment-inline-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="recruitment_action" value="stage">
                                        <input type="hidden" name="candidate_id" value="<?php echo (int) $c['id']; ?>">
                                        <input type="hidden" name="job_opening_id" value="<?php echo $selected_job; ?>">
                                        <input type="hidden" name="stage" value="rejected">
                                        <button type="submit" class="btn btn-outline btn-sm recruitment-reject-btn" onclick="return confirm('Mark this candidate as rejected?');">Reject</button>
                                    </form>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($c['hired_emp_id'])): ?>
                                    <p class="recruitment-cand-hired-msg">Hired as <a href="employee_view.php?emp_id=<?php echo urlencode($c['hired_emp_id']); ?>"><?php echo htmlspecialchars($c['hired_emp_id']); ?></a> — portal account created.</p>
                                <?php elseif ($st === 'offered' || $st === 'interview'): ?>
                                    <details class="recruitment-convert-panel">
                                        <summary>Convert to employee</summary>
                                        <form method="POST" action="recruitment_save.php" class="recruitment-convert-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="recruitment_action" value="convert">
                                            <input type="hidden" name="candidate_id" value="<?php echo (int) $c['id']; ?>">
                                            <input type="hidden" name="job_opening_id" value="<?php echo $selected_job; ?>">
                                            <div class="recruitment-convert-fields">
                                                <div class="form-group">
                                                    <label for="emp_id_<?php echo (int) $c['id']; ?>">Employee ID</label>
                                                    <input type="text" name="emp_id" id="emp_id_<?php echo (int) $c['id']; ?>" placeholder="Auto-generated if blank" class="settings-add-input input-compact">
                                                </div>
                                                <div class="form-group">
                                                    <label for="salary_<?php echo (int) $c['id']; ?>">Base salary</label>
                                                    <input type="number" name="base_salary" id="salary_<?php echo (int) $c['id']; ?>" placeholder="Monthly" step="0.01" class="settings-add-input input-compact">
                                                </div>
                                            </div>
                                            <p class="recruitment-convert-hint">Creates an employee record and portal login. Candidate moves to Hired.</p>
                                            <button type="submit" class="btn btn-sm btn-header">Create employee</button>
                                        </form>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="masters-empty masters-search-empty" id="recruitCandNoMatch" hidden><h4>No matches</h4><p>Try a different name or stage filter.</p></div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="panel-body padded recruitment-pipeline-placeholder">
                <div class="masters-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <h4>Select a job to view pipeline</h4>
                    <?php if ($jobs === []): ?>
                        <p>Post a job first, then click it to manage candidates.</p>
                    <?php else: ?>
                        <p>Click any position on the left — you'll see stages, resumes, and hire actions here.</p>
                        <a href="#recruitJobList" class="btn btn-outline btn-sm recruitment-placeholder-cta">Browse jobs ↑</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<script>
(function () {
    var jobInput = document.getElementById('recruitJobSearch');
    var jobList = document.getElementById('recruitJobList');
    var jobFilter = 'all';

    function applyJobFilters() {
        if (!jobList) return;
        var q = jobInput ? jobInput.value.trim().toLowerCase() : '';
        var badge = document.getElementById('recruitJobCount');
        var noMatch = document.getElementById('recruitJobNoMatch');
        var clearBtn = document.getElementById('recruitJobClear');
        var items = jobList.querySelectorAll('.recruitment-job-card');
        var visible = 0;
        items.forEach(function (el) {
            var matchSearch = q === '' || (el.getAttribute('data-search') || '').indexOf(q) !== -1;
            var matchStatus = jobFilter === 'all' || el.getAttribute('data-status') === jobFilter;
            var show = matchSearch && matchStatus;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (badge) badge.textContent = visible + ' shown' + (q || jobFilter !== 'all' ? ' · filtered' : '');
        if (clearBtn) clearBtn.hidden = q === '';
        if (noMatch) noMatch.hidden = visible > 0;
        jobList.hidden = visible === 0 && items.length > 0;
    }

    if (jobInput) {
        jobInput.addEventListener('input', applyJobFilters);
        var jobClear = document.getElementById('recruitJobClear');
        if (jobClear) jobClear.addEventListener('click', function () { jobInput.value = ''; applyJobFilters(); jobInput.focus(); });
    }

    document.querySelectorAll('[data-job-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-job-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            jobFilter = btn.getAttribute('data-job-filter') || 'all';
            applyJobFilters();
        });
    });

    var stageFilter = 'all';
    var candInput = document.getElementById('recruitCandSearch');
    var candList = document.getElementById('recruitCandList');

    function applyCandFilters() {
        if (!candList) return;
        var q = candInput ? candInput.value.trim().toLowerCase() : '';
        var noMatch = document.getElementById('recruitCandNoMatch');
        var clearBtn = document.getElementById('recruitCandClear');
        var items = candList.querySelectorAll('.recruitment-cand-card');
        var visible = 0;
        items.forEach(function (el) {
            var matchSearch = q === '' || (el.getAttribute('data-search') || '').indexOf(q) !== -1;
            var matchStage = stageFilter === 'all' || el.getAttribute('data-stage') === stageFilter;
            var show = matchSearch && matchStage;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (clearBtn) clearBtn.hidden = q === '';
        if (noMatch) noMatch.hidden = visible > 0;
        candList.hidden = visible === 0 && items.length > 0;
    }

    if (candInput) {
        candInput.addEventListener('input', applyCandFilters);
        var candClear = document.getElementById('recruitCandClear');
        if (candClear) candClear.addEventListener('click', function () { candInput.value = ''; applyCandFilters(); candInput.focus(); });
    }

    document.querySelectorAll('[data-stage-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-stage-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            stageFilter = btn.getAttribute('data-stage-filter') || 'all';
            applyCandFilters();
        });
    });

    if (window.location.hash === '#pipeline') {
        var pipeline = document.getElementById('pipeline');
        if (pipeline) pipeline.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.querySelectorAll('.recruitment-job-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var panelId = btn.getAttribute('aria-controls');
            var panel = panelId ? document.getElementById(panelId) : null;
            if (!panel) return;
            var open = panel.hidden;
            document.querySelectorAll('.recruitment-job-edit-panel').forEach(function (p) {
                p.hidden = true;
                var ctrl = p.id ? document.querySelector('[aria-controls="' + p.id + '"]') : null;
                if (ctrl) ctrl.setAttribute('aria-expanded', 'false');
            });
            panel.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
</script>
<?php require 'includes/footer.php'; ?>
