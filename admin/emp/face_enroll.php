<?php
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/face_biometric_helper.php';

$emp_id = $employee['emp_id'];
$face_enabled = employee_face_login_enabled($conn);
$has_face = employee_has_face_enrolled($conn, $emp_id);
?>
<div class="emp-page emp-page-face-enroll">
    <?php require __DIR__ . '/includes/flash.php'; ?>

    <div class="emp-page-hero emp-page-hero-compact">
        <div class="emp-page-hero-main">
            <p class="page-eyebrow emp-page-eyebrow">Security</p>
            <h2 class="emp-page-hero-title">Capture samples in good lighting</h2>
            <p class="emp-page-hero-sub">Password login always remains available as backup.</p>
        </div>
    </div>

    <?php if (!$face_enabled): ?>
        <div class="emp-inline-alert emp-inline-alert-info">
            <strong>Face login is off</strong>
            <span>Your company admin has disabled face login. Contact HR if you need this feature.</span>
        </div>
    <?php else: ?>
        <div class="face-enroll-layout">
            <section class="face-enroll-panel card">
                <div class="face-enroll-status">
                    <span class="face-enroll-status-dot <?php echo $has_face ? 'is-on' : 'is-off'; ?>" aria-hidden="true"></span>
                    <div>
                        <strong><?php echo $has_face ? 'Face login is active' : 'Face login not set up'; ?></strong>
                        <p><?php echo $has_face ? 'You can sign in from the employee login page using your face.' : 'Capture 3 samples in good lighting while looking at the camera.'; ?></p>
                    </div>
                </div>

                <div class="face-camera-wrap">
                    <video id="faceVideo" class="face-camera-video" autoplay muted playsinline></video>
                    <canvas id="faceOverlay" class="face-camera-overlay" aria-hidden="true"></canvas>
                    <div id="faceCameraPlaceholder" class="face-camera-placeholder">Allow camera access to continue</div>
                </div>

                <div class="face-enroll-progress" id="faceEnrollProgress" hidden>
                    <div class="face-enroll-progress-bar"><span id="faceEnrollProgressFill"></span></div>
                    <p id="faceEnrollProgressText">Sample 0 of 3</p>
                </div>

                <p id="faceStatus" class="face-status" role="status">Loading face models…</p>

                <div class="face-enroll-actions">
                    <button type="button" class="btn" id="faceCaptureBtn" disabled>Capture face sample</button>
                    <button type="button" class="btn btn-outline" id="faceResetBtn" disabled>Start over</button>
                    <?php if ($has_face): ?>
                        <button type="button" class="btn btn-outline btn-danger-outline" id="faceRemoveBtn">Remove face login</button>
                    <?php endif; ?>
                </div>

                <ul class="face-enroll-tips">
                    <li>Use a well-lit room and remove sunglasses or mask.</li>
                    <li>Keep your face centered in the frame.</li>
                    <li>Blink naturally when prompted — this helps prevent photo spoofing.</li>
                </ul>
            </section>
        </div>

        <script>
            window.PAYROLL_FACE = {
                mode: 'enroll',
                csrfToken: <?php echo json_encode(csrf_token()); ?>,
                empId: <?php echo json_encode($emp_id); ?>,
                modelBase: 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/model',
                enrollUrl: 'face_enroll_save.php',
                removeUrl: 'face_enroll_remove.php',
                requiredSamples: 3
            };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/dist/face-api.js"></script>
        <script src="../js/face-login.js"></script>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
