</div><!-- end .main-content -->
</div><!-- end .app-wrapper -->

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="<?= $base_path ?? '' ?>js/app.js"></script>
<?php if (isset($extra_js)) echo $extra_js; ?>

<!-- Footer Credits -->
<footer class="main-footer">
    <div class="footer-inner">
        <span><i class="bi bi-mortarboard-fill text-primary me-1"></i> Student Clearance Processing System &copy; <?= date('Y') ?></span>
        <span class="footer-credit">
            Developed by <strong>Gideon Agtas</strong>
        </span>
    </div>
</footer>

</body>
</html>