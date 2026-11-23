<?php
$root_path = isset($is_admin_page) && $is_admin_page ? '../' : './';
?>
<footer class="bg-dark text-white-50 py-4 mt-auto border-top border-secondary">
    <div class="container text-center">
        <p class="mb-1 text-light fw-semibold">Online Voting System &copy; <?php echo date('Y'); ?></p>
        <p class="small text-secondary mb-0">Secure &bull; Transparent &bull; Automated</p>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom JS -->
<script src="<?php echo $root_path; ?>assets/js/main.js"></script>
</body>
</html>
