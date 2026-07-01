<?php
require_once __DIR__ . '/../includes/employee_portal_auth.php';
init_employee_session();
require __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';
require_once __DIR__ . '/../includes/face_biometric_helper.php';
$login_company = trim(get_all_settings($conn)['company_name'] ?? '') ?: 'Payroll Company';
$login_logo_initial = strtoupper(substr($login_company, 0, 1)) ?: 'E';
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
    <title>Employee Login — <?php echo htmlspecialchars($login_company); ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="page-auth page-emp-auth">
    <div class="emp-login-page">
        <div class="emp-login-shell">
            <div class="emp-login-card">
                <header class="emp-login-hero">
                    <span class="emp-login-logo" aria-hidden="true"><?php echo htmlspecialchars($login_logo_initial); ?></span>
                    <div class="emp-login-hero-text">
                        <p class="emp-login-eyebrow"><?php echo htmlspecialchars($login_company); ?></p>
                        <h1>Employee portal</h1>
                        <p>Attendance, salary slips, leave &amp; documents</p>
                    </div>
                </header>

                <div class="emp-login-body">
                    <?php
                    if (isset($_SESSION['employee_login_error'])) {
                        echo '<div class="alert alert-error emp-login-alert">' . htmlspecialchars($_SESSION['employee_login_error']) . '</div>';
                        unset($_SESSION['employee_login_error']);
                    }
                    ?>

                    <?php if ($face_login_enabled): ?>
                    <div class="emp-login-tabs" role="tablist" aria-label="Sign in method">
                        <button type="button" class="emp-login-tab is-active" role="tab" id="empLoginTabPassword" aria-selected="true" aria-controls="empLoginPanelPassword" data-emp-login-tab="password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>Password</span>
                        </button>
                        <button type="button" class="emp-login-tab" role="tab" id="empLoginTabFace" aria-selected="false" aria-controls="empLoginPanelFace" data-emp-login-tab="face">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="10" r="3"/><path d="M7 20v-1a5 5 0 0 1 10 0v1"/></svg>
                            <span>Face login</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="form-group emp-login-id-group">
                        <label for="emp_id">Employee ID</label>
                        <div class="emp-login-input-wrap">
                            <svg class="emp-login-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" name="emp_id" id="emp_id" form="empPasswordForm" placeholder="e.g. EMP001" required autocomplete="username">
                        </div>
                    </div>

                    <div class="emp-login-panels">
                        <section class="emp-login-panel is-active" id="empLoginPanelPassword" role="tabpanel" aria-labelledby="empLoginTabPassword">
                            <form action="authenticate.php" method="POST" id="empPasswordForm" class="emp-login-form">
                                <?php echo csrf_field(); ?>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <div class="emp-login-input-wrap">
                                        <svg class="emp-login-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        <input type="password" name="password" id="password" placeholder="Your portal password" required autocomplete="current-password">
                                    </div>
                                </div>
                                <button type="submit" class="emp-login-submit">
                                    <span>Sign in with password</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                </button>
                            </form>
                        </section>

                        <?php if ($face_login_enabled): ?>
                        <section class="emp-login-panel" id="empLoginPanelFace" role="tabpanel" aria-labelledby="empLoginTabFace" hidden>
                            <div id="faceLoginPanel" class="emp-login-face-panel">
                                <div class="emp-login-face-steps">
                                    <span class="emp-login-face-step is-done"><strong>1</strong> Employee ID</span>
                                    <span class="emp-login-face-step"><strong>2</strong> Blink &amp; verify</span>
                                </div>
                                <div class="face-camera-wrap face-camera-wrap-login emp-login-camera">
                                    <video id="faceVideo" class="face-camera-video" autoplay muted playsinline></video>
                                    <canvas id="faceOverlay" class="face-camera-overlay" aria-hidden="true"></canvas>
                                    <div id="faceCameraPlaceholder" class="face-camera-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="10" r="3"/><path d="M7 20v-1a5 5 0 0 1 10 0v1"/></svg>
                                        <span>Camera preview</span>
                                    </div>
                                    <span class="emp-login-camera-ring" aria-hidden="true"></span>
                                </div>
                                <p id="faceStatus" class="face-status emp-login-face-status" role="status">Loading face models…</p>
                                <div class="face-login-actions emp-login-face-actions">
                                    <button type="button" class="emp-login-submit emp-login-submit-face" id="faceLoginStartBtn" disabled>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="10" r="3"/><path d="M7 20v-1a5 5 0 0 1 10 0v1"/></svg>
                                        <span>Verify face &amp; sign in</span>
                                    </button>
                                </div>
                                <p class="face-login-hint emp-login-face-hint">First time? Sign in with password, then set up face login from <strong>My details</strong>.</p>
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
                        </section>
                        <?php endif; ?>
                    </div>
                </div>

                <footer class="emp-login-card-footer">
                    <div class="emp-login-footer-links">
                        <a href="../index.php" class="emp-login-admin-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Portal home
                        </a>
                        <a href="../login.php" class="emp-login-admin-link emp-login-admin-link-accent">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Admin login
                        </a>
                    </div>
                </footer>
            </div>

            <p class="emp-login-site-footer">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($login_company); ?></p>
        </div>
    </div>
    <?php if ($face_login_enabled): ?>
    <script>
    (function () {
        var tabs = document.querySelectorAll('[data-emp-login-tab]');
        var panels = {
            password: document.getElementById('empLoginPanelPassword'),
            face: document.getElementById('empLoginPanelFace')
        };
        var facePanel = document.getElementById('faceLoginPanel');

        function setTab(name, skipEvent) {
            tabs.forEach(function (tab) {
                var active = tab.getAttribute('data-emp-login-tab') === name;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            Object.keys(panels).forEach(function (key) {
                if (!panels[key]) {
                    return;
                }
                var active = key === name;
                panels[key].classList.toggle('is-active', active);
                panels[key].hidden = !active;
            });
            if (!skipEvent) {
                document.dispatchEvent(new CustomEvent('emp-login-tab-change', { detail: { tab: name, fromSetTab: true } }));
            }
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                setTab(tab.getAttribute('data-emp-login-tab'));
            });
        });

        document.addEventListener('emp-login-tab-change', function (event) {
            var tab = event.detail && event.detail.tab;
            if (tab && !event.detail.fromSetTab) {
                setTab(tab, true);
            }
        });

        var empIdInput = document.getElementById('emp_id');
        var faceStep = document.querySelector('.emp-login-face-step');
        function syncFaceStep() {
            if (faceStep && empIdInput) {
                faceStep.classList.toggle('is-done', empIdInput.value.trim() !== '');
            }
        }
        if (empIdInput) {
            empIdInput.addEventListener('input', syncFaceStep);
            syncFaceStep();
        }
    })();
    </script>
    <?php endif; ?>
</body>
</html>
