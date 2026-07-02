<?php
if (!isset($conn)) {
    require __DIR__ . '/../../config.php';
}
require_once __DIR__ . '/../../includes/settings_helper.php';

$footer_settings = get_all_settings($conn);
$footer_company = trim($footer_settings['company_name'] ?? '') ?: 'Teamora';
$footer_year = (int) date('Y');
?>
        </main>
        <footer class="emp-site-footer emp-site-footer-compact">
            <div class="emp-site-footer-bottom">
                <span>&copy; <?php echo $footer_year; ?> <?php echo htmlspecialchars($footer_company); ?>. All rights reserved.</span>
                <a href="../index.php">Portal home</a>
                <a href="../login.php">Admin login</a>
            </div>
        </footer>
    </div>
    <script>
    (function () {
        var sidebar = document.getElementById('empSidebar');
        var backdrop = document.getElementById('empSidebarBackdrop');
        var toggle = document.getElementById('empSidebarToggle');
        if (!sidebar || !toggle) {
            return;
        }
        function setOpen(open) {
            document.body.classList.toggle('emp-sidebar-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            if (backdrop) {
                backdrop.hidden = !open;
            }
        }
        toggle.addEventListener('click', function () {
            setOpen(!document.body.classList.contains('emp-sidebar-open'));
        });
        if (backdrop) {
            backdrop.addEventListener('click', function () { setOpen(false); });
        }
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 900px)').matches) {
                    setOpen(false);
                }
            });
        });
    })();
    </script>
</body>
</html>
