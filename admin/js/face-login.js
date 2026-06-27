(function () {
    'use strict';

    var config = window.PAYROLL_FACE || {};
    var mode = config.mode || 'login';
    var csrfToken = config.csrfToken || '';
    var modelBase = config.modelBase || 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.14/model';
    var requiredSamples = config.requiredSamples || 3;

    var video = document.getElementById('faceVideo');
    var overlay = document.getElementById('faceOverlay');
    var placeholder = document.getElementById('faceCameraPlaceholder');
    var statusEl = document.getElementById('faceStatus');
    var captureBtn = document.getElementById('faceCaptureBtn');
    var resetBtn = document.getElementById('faceResetBtn');
    var removeBtn = document.getElementById('faceRemoveBtn');
    var progressWrap = document.getElementById('faceEnrollProgress');
    var progressFill = document.getElementById('faceEnrollProgressFill');
    var progressText = document.getElementById('faceEnrollProgressText');
    var facePanel = document.getElementById('faceLoginPanel');
    var faceStartBtn = document.getElementById('faceLoginStartBtn');
    var faceCancelBtn = document.getElementById('faceLoginCancelBtn');
    var empIdInput = document.getElementById('emp_id');

    var modelsReady = false;
    var stream = null;
    var samples = [];
    var busy = false;
    var blinkChallengeDone = false;
    var lastEyeRatio = null;

    function setStatus(message, isError) {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = message;
        statusEl.classList.toggle('is-error', !!isError);
    }

    function detectorOptions() {
        return new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
    }

    function eyeAspectRatio(landmarks) {
        var left = landmarks.getLeftEye();
        var right = landmarks.getRightEye();
        function ratio(eye) {
            var v1 = distance(eye[1], eye[5]);
            var v2 = distance(eye[2], eye[4]);
            var h = distance(eye[0], eye[3]);
            return h === 0 ? 0 : (v1 + v2) / (2 * h);
        }
        return (ratio(left) + ratio(right)) / 2;
    }

    function distance(a, b) {
        var dx = a.x - b.x;
        var dy = a.y - b.y;
        return Math.sqrt(dx * dx + dy * dy);
    }

    async function loadModels() {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(modelBase),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri(modelBase),
            faceapi.nets.faceRecognitionNet.loadFromUri(modelBase),
        ]);
        modelsReady = true;
    }

    async function startCamera() {
        if (!video) {
            return;
        }
        if (stream) {
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
            if (placeholder) {
                placeholder.hidden = true;
            }
            resizeOverlay();
        } catch (err) {
            setStatus('Camera access denied or unavailable. Use password login instead.', true);
            if (captureBtn) {
                captureBtn.disabled = true;
            }
            if (faceStartBtn) {
                faceStartBtn.disabled = true;
            }
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        if (video) {
            video.srcObject = null;
        }
        if (placeholder) {
            placeholder.hidden = false;
        }
    }

    function resizeOverlay() {
        if (!video || !overlay || !video.videoWidth) {
            return;
        }
        overlay.width = video.videoWidth;
        overlay.height = video.videoHeight;
    }

    async function detectFace() {
        if (!video || !modelsReady) {
            return null;
        }
        return faceapi
            .detectSingleFace(video, detectorOptions())
            .withFaceLandmarks(true)
            .withFaceDescriptor();
    }

    function drawOverlay(detection) {
        if (!overlay || !video) {
            return;
        }
        var ctx = overlay.getContext('2d');
        if (!ctx) {
            return;
        }
        ctx.clearRect(0, 0, overlay.width, overlay.height);
        if (!detection) {
            return;
        }
        var dims = faceapi.matchDimensions(overlay, video, true);
        var resized = faceapi.resizeResults(detection, dims);
        faceapi.draw.drawDetections(overlay, resized);
    }

    async function waitForBlink() {
        blinkChallengeDone = false;
        lastEyeRatio = null;
        setStatus('Blink once to verify you are live…');
        var started = Date.now();
        while (Date.now() - started < 8000) {
            var detection = await detectFace();
            drawOverlay(detection);
            if (detection && detection.landmarks) {
                var ratio = eyeAspectRatio(detection.landmarks);
                if (lastEyeRatio !== null && ratio < lastEyeRatio - 0.04) {
                    blinkChallengeDone = true;
                    return true;
                }
                lastEyeRatio = ratio;
            }
            await sleep(120);
        }
        return false;
    }

    function sleep(ms) {
        return new Promise(function (resolve) { setTimeout(resolve, ms); });
    }

    function updateEnrollProgress() {
        if (!progressWrap || !progressFill || !progressText) {
            return;
        }
        progressWrap.hidden = false;
        var pct = Math.min(100, Math.round((samples.length / requiredSamples) * 100));
        progressFill.style.width = pct + '%';
        progressText.textContent = 'Sample ' + samples.length + ' of ' + requiredSamples;
    }

    function resetEnrollSamples() {
        samples = [];
        blinkChallengeDone = false;
        if (progressWrap) {
            progressWrap.hidden = true;
        }
        if (resetBtn) {
            resetBtn.disabled = true;
        }
        setStatus('Ready. Capture ' + requiredSamples + ' face samples.');
    }

    async function postJson(url, body) {
        var response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify(Object.assign({ csrf_token: csrfToken }, body)),
        });
        return response.json();
    }

    async function captureEnrollSample() {
        if (busy || !captureBtn) {
            return;
        }
        busy = true;
        captureBtn.disabled = true;
        try {
            var blinked = await waitForBlink();
            if (!blinked) {
                setStatus('Blink not detected. Try again with your eyes visible.', true);
                return;
            }
            var detection = await detectFace();
            drawOverlay(detection);
            if (!detection || !detection.descriptor) {
                setStatus('No face detected. Center your face and try again.', true);
                return;
            }
            samples.push(Array.from(detection.descriptor));
            updateEnrollProgress();
            if (resetBtn) {
                resetBtn.disabled = false;
            }
            if (samples.length < requiredSamples) {
                setStatus('Good. Capture sample ' + (samples.length + 1) + ' of ' + requiredSamples + '.');
                return;
            }
            setStatus('Saving face data…');
            var result = await postJson(config.enrollUrl, { samples: samples });
            if (!result.ok) {
                setStatus(result.message || 'Enrollment failed.', true);
                samples = [];
                updateEnrollProgress();
                return;
            }
            setStatus(result.message || 'Face enrolled.');
            window.setTimeout(function () {
                window.location.href = result.redirect || 'face_enroll.php';
            }, 900);
        } finally {
            busy = false;
            if (captureBtn) {
                captureBtn.disabled = !modelsReady;
            }
        }
    }

    async function loginWithFace() {
        if (busy) {
            return;
        }
        var empId = (empIdInput && empIdInput.value ? empIdInput.value : config.empId || '').trim();
        if (!empId) {
            setStatus('Enter your Employee ID first.', true);
            return;
        }
        busy = true;
        if (faceStartBtn) {
            faceStartBtn.disabled = true;
        }
        try {
            await startCamera();
            var blinked = await waitForBlink();
            if (!blinked) {
                setStatus('Blink not detected. Try again.', true);
                return;
            }
            setStatus('Matching face…');
            var detection = await detectFace();
            drawOverlay(detection);
            if (!detection || !detection.descriptor) {
                setStatus('Face not detected. Try again.', true);
                return;
            }
            var result = await postJson(config.authUrl || 'face_authenticate.php', {
                emp_id: empId,
                descriptor: Array.from(detection.descriptor),
            });
            if (!result.ok) {
                setStatus(result.message || 'Face login failed.', true);
                return;
            }
            setStatus('Success! Redirecting…');
            window.location.href = result.redirect || 'dashboard.php';
        } finally {
            busy = false;
            if (faceStartBtn) {
                faceStartBtn.disabled = !modelsReady;
            }
        }
    }

    async function removeEnrollment() {
        if (!removeBtn || !window.confirm('Remove face login for your account?')) {
            return;
        }
        removeBtn.disabled = true;
        var result = await postJson(config.removeUrl, {});
        if (!result.ok) {
            setStatus(result.message || 'Could not remove face login.', true);
            removeBtn.disabled = false;
            return;
        }
        window.location.reload();
    }

    async function init() {
        if (!window.faceapi) {
            setStatus('Face library failed to load. Check internet connection.', true);
            return;
        }
        try {
            await loadModels();
            if (captureBtn) {
                captureBtn.disabled = false;
            }
            if (faceStartBtn) {
                faceStartBtn.disabled = false;
            }
            if (mode === 'enroll') {
                await startCamera();
                setStatus('Ready. Capture ' + requiredSamples + ' face samples.');
            } else {
                setStatus('Enter Employee ID above, then verify your face.');
            }
        } catch (err) {
            setStatus('Could not load face models. Check internet and refresh.', true);
        }

        if (captureBtn) {
            captureBtn.addEventListener('click', captureEnrollSample);
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', resetEnrollSamples);
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', removeEnrollment);
        }
        if (faceStartBtn) {
            faceStartBtn.addEventListener('click', loginWithFace);
        }
        if (faceCancelBtn) {
            faceCancelBtn.addEventListener('click', function () {
                stopCamera();
                setStatus('Enter Employee ID above, then verify your face.');
                document.dispatchEvent(new CustomEvent('emp-login-tab-change', { detail: { tab: 'password' } }));
            });
        }

        document.addEventListener('emp-login-tab-change', function (event) {
            var tab = event.detail && event.detail.tab;
            if (tab === 'face' && facePanel) {
                startCamera();
            } else if (tab === 'password') {
                stopCamera();
            }
        });

        var toggleFaceBtn = document.getElementById('faceLoginToggleBtn');
        if (toggleFaceBtn && facePanel) {
            toggleFaceBtn.addEventListener('click', function () {
                facePanel.hidden = !facePanel.hidden;
                if (!facePanel.hidden) {
                    startCamera();
                } else {
                    stopCamera();
                }
            });
        }

        window.addEventListener('beforeunload', stopCamera);
        if (video) {
            video.addEventListener('loadedmetadata', resizeOverlay);
        }
    }

    init();
})();
