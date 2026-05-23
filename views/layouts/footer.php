<?php if (isLoggedIn()): ?>
    </main><!-- /.app-main -->

    <!-- Footer (inside app-wrapper) -->
    <footer class="app-footer">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <p class="mb-0 small">
                <i class="fas fa-building me-1 text-primary"></i>
                <strong>Pearls of Wisdom Hostel</strong> &mdash; Kasindikwa Village, Fort Portal, Uganda
                &nbsp;|&nbsp; <a href="tel:+256765536881">+256 765 536 881</a>
            </p>
            <p class="mb-0 small text-muted">
                &copy; 2026 &mdash; Developed by Wasswa Atibu &amp; Karim Abdul
            </p>
        </div>
    </footer>

</div><!-- /.app-wrapper -->

<?php else: ?>
</main><!-- /.public-main -->
<footer class="public-footer">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <h5><i class="fas fa-building me-2"></i>Pearls of Wisdom</h5>
                <p class="small text-muted">Your trusted accommodation provider for students at Mountains of the Moon University.</p>
                <p class="small text-muted mb-0"><i class="fas fa-map-marker-alt me-1"></i> Kasindikwa Village, Fort Portal</p>
                <p class="small text-muted mb-0"><i class="fas fa-phone me-1"></i> +256 765 536 881</p>
                <p class="small text-muted"><i class="fas fa-envelope me-1"></i> info@pearlswisdom.com</p>
            </div>
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="<?php echo APP_URL; ?>">Home</a></li>
                    <li class="mb-1"><a href="<?php echo APP_URL; ?>auth/login.php">Login</a></li>
                    <li class="mb-1"><a href="<?php echo APP_URL; ?>auth/register.php">Register</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Information</h5>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="#">Terms &amp; Conditions</a></li>
                    <li class="mb-1"><a href="#">Privacy Policy</a></li>
                    <li class="mb-1"><a href="#">FAQs</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="text-center text-muted small mb-0">&copy; 2026 Pearls of Wisdom Hostel. All rights reserved.</p>
    </div>
</footer>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo APP_URL; ?>assets/js/main.js"></script>
<?php if (getCurrentUserRole() === 'admin'): ?>
<script src="<?php echo APP_URL; ?>assets/js/admin.js"></script>
<?php endif; ?>

<script>
function openSidebar()  {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
</script>
</body>
</html>
