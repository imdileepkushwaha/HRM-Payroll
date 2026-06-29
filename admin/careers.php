<?php
require 'config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/hrm_modules_helper.php';

$settings = get_all_settings($conn);
$company = trim($settings['company_name'] ?? '') ?: 'Careers';
$enabled = ($settings['careers_public_enabled'] ?? '1') === '1';
$jobs = $enabled ? get_public_job_openings($conn) : [];
$flash = $_SESSION['careers_flash'] ?? null;
$flash_ok = $_SESSION['careers_flash_ok'] ?? false;
unset($_SESSION['careers_flash'], $_SESSION['careers_flash_ok']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers — <?php echo htmlspecialchars($company); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-careers-public">
    <div class="careers-public-wrap">
        <header class="careers-public-header">
            <h1><?php echo htmlspecialchars($company); ?></h1>
            <p>Open positions — apply online</p>
        </header>

        <?php if ($flash): ?>
            <div class="alert <?php echo $flash_ok ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <?php if (!$enabled): ?>
            <div class="careers-public-empty">
                <h2>Careers page is not available</h2>
                <p>Online applications are currently disabled. Please contact HR directly.</p>
            </div>
        <?php elseif ($jobs === []): ?>
            <div class="careers-public-empty">
                <h2>No open positions right now</h2>
                <p>Check back later for new opportunities.</p>
            </div>
        <?php else: ?>
            <ul class="careers-job-list">
                <?php foreach ($jobs as $job): ?>
                <li class="careers-job-card" id="job-<?php echo (int) $job['id']; ?>">
                    <div class="careers-job-head">
                        <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                        <span class="careers-job-meta">
                            <?php echo htmlspecialchars($job['branch_name'] ?? ''); ?>
                            <?php if (!empty($job['department_name'])): ?> · <?php echo htmlspecialchars($job['department_name']); ?><?php endif; ?>
                            · <?php echo (int) ($job['openings_count'] ?? 1); ?> opening<?php echo (int) ($job['openings_count'] ?? 1) === 1 ? '' : 's'; ?>
                        </span>
                    </div>
                    <?php if (!empty($job['description'])): ?>
                        <p class="careers-job-desc"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                    <?php endif; ?>
                    <form method="POST" action="careers_apply.php" enctype="multipart/form-data" class="careers-apply-form">
                        <input type="hidden" name="job_opening_id" value="<?php echo (int) $job['id']; ?>">
                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="careers-honeypot" aria-hidden="true">
                        <div class="careers-apply-grid">
                            <div class="form-group">
                                <label>Full name <span class="req">*</span></label>
                                <input type="text" name="name" required placeholder="Your name">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" placeholder="you@email.com">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" name="phone" placeholder="+91 …">
                            </div>
                            <div class="form-group">
                                <label>Resume (PDF or Word)</label>
                                <input type="file" name="resume" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Cover note</label>
                            <textarea name="notes" rows="3" placeholder="Brief introduction (optional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-header">Submit application</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
