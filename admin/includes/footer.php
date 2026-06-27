        </main>
    </div>
    <script>
    (function () {
        var sidebar = document.getElementById('adminSidebar');
        var backdrop = document.getElementById('adminSidebarBackdrop');
        var toggle = document.getElementById('adminSidebarToggle');
        var closeBtn = document.getElementById('adminSidebarClose');
        if (!sidebar || !toggle) {
            return;
        }

        function isMobileNav() {
            return window.matchMedia('(max-width: 900px)').matches;
        }

        function setOpen(open) {
            document.body.classList.toggle('admin-sidebar-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            if (backdrop) {
                backdrop.hidden = !open;
                backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
            }
            document.body.style.overflow = open && isMobileNav() ? 'hidden' : '';
        }

        toggle.addEventListener('click', function () {
            setOpen(!document.body.classList.contains('admin-sidebar-open'));
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setOpen(false);
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setOpen(false);
            });
        }

        sidebar.querySelectorAll('.sidebar-menu a, .sidebar-menu button').forEach(function (el) {
            el.addEventListener('click', function () {
                if (isMobileNav()) {
                    setOpen(false);
                }
            });
        });

        window.addEventListener('resize', function () {
            if (!isMobileNav()) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });
    })();
    </script>
</body>
</html>
