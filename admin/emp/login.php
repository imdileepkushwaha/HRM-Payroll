<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
init_employee_session();
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/face_biometric_helper.php';
$login_company = trim(get_all_settings($conn)['company_name'] ?? '') ?: 'Payroll Company';
$face_login_enabled = employee_face_login_enabled($conn);

if (!empty($_SESSION['employee_logged_in'])) {
    if (is_employee_session_expired()) {
        expire_employee_session();
    } else {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="page-auth page-emp-auth">
    <div class="login-wrapper">
        <div class="login-card login-card-emp">
            <div class="login-brand login-brand-emp">E</div>
            <h2>Employee portal</h2>
            <p class="login-subtitle">View profile, attendance & submit requests</p>
            <?php
            if (isset($_SESSION['employee_login_error'])) {
                echo "<div class='alert alert-error'>" . htmlspecialchars($_SESSION['employee_login_error']) . "</div>";
                unset($_SESSION['employee_login_error']);
            }
            ?>
            <form action="authenticate.php" method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="emp_id">Employee ID</label>
                    <input type="text" name="emp_id" id="emp_id" placeholder="e.g. EMP001" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Portal password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-block">Sign in</button>
            </form>

            <?php if ($face_login_enabled): ?>
            <div class="face-login-divider"><span>or</span></div>
            <button type="button" class="btn btn-outline btn-block face-login-toggle" id="faceLoginToggleBtn">
                Sign in with face
            </button>
            <div id="faceLoginPanel" class="face-login-panel" hidden>
                <div class="face-camera-wrap face-camera-wrap-login">
                    <video id="faceVideo" class="face-camera-video" autoplay muted playsinline></video>
                    <canvas id="faceOverlay" class="face-camera-overlay" aria-hidden="true"></canvas>
                    <div id="faceCameraPlaceholder" class="face-camera-placeholder">Camera preview</div>
                </div>
                <p id="faceStatus" class="face-status" role="status">Loading face models…</p>
                <div class="face-login-actions">
                    <button type="button" class="btn btn-block" id="faceLoginStartBtn" disabled>Verify face & sign in</button>
                    <button type="button" class="btn btn-outline btn-block" id="faceLoginCancelBtn">Cancel</button>
                </div>
                <p class="face-login-hint">Use the Employee ID above. First time? Sign in with password, then set up face login from My details.</p>
            </div>
            <script>
                window.PAYROLL_FACE = {
                    mode: 'login',
                    csrfToken: <?php echo json_encode(csrf_token()); ?>,
                    modelBase: 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/model',
                    authUrl: 'face_authenticate.php'
                };
            </script>
            <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/dist/face-api.js"></script>
            <script src="../js/face-login.js"></script>
            <?php endif; ?>

            <p class="login-footer"><a href="../index.php">Admin login</a></p>
        </div>
    </div>
    <p class="emp-login-site-footer">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($login_company); ?> · <a href="../index.php">Admin login</a></p>
</body>
</html>
