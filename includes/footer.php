    </div><!-- /.main-container -->
</main><!-- /.site-main -->

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-left">
            <div class="footer-brand">
                <span class="footer-brand-name">EduEnroll Training System</span>
                <span class="footer-version">v2.4 &bull; Production</span>
            </div>
            <p class="footer-desc">Enterprise academic registration platform with transactional slot allocation.</p>
        </div>

        <div class="footer-nav">
            <a href="<?= $base ?>index.php">Dashboard</a>
            <a href="<?= $adminPath ?>courses.php">Courses</a>
            <a href="<?= $adminPath ?>classes.php">Classes</a>
            <a href="<?= $adminPath ?>students.php">Students</a>
            <a href="<?= $adminPath ?>enroll.php">Enroll</a>
            <a href="<?= $adminPath ?>enrollments.php">Enrollments</a>
            <a href="<?= $adminPath ?>reports.php">Reports</a>
        </div>

        <div class="footer-right">
            <div class="footer-meta">
                <span class="system-time">&copy; <?= date('Y') ?> IPT &bull; Training Enrollment System</span>
                <span class="system-badge">ACID Guaranteed</span>
            </div>
        </div>
    </div>
</footer>

<script>
// Mobile navigation toggle
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('mobileNavToggle');
    var nav = document.getElementById('mainNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !expanded);
            nav.classList.toggle('nav-open');
        });
    }

    // Auto fade alerts after 6 seconds if not closed
    var alerts = document.querySelectorAll('.flash-messages-container .alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.parentNode) {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                setTimeout(function() { alert.remove(); }, 400);
            }
        }, 6500);
    });
});
</script>
</body>
</html>
