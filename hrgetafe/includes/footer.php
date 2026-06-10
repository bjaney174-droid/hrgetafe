<?php
/**
 * FOOTER TEMPLATE
 * HRGotale - Human Resources Information System
 */
?>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">
                        <strong><?php echo APP_NAME; ?></strong> v<?php echo APP_VERSION; ?>
                    </p>
                    <small><?php echo APP_TITLE; ?></small>
                </div>
                <div class="col-md-6 text-end">
                    <small>
                        &copy; <?php echo date('Y'); ?> Gotale Local Government Unit. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>js/validation.js"></script>
    <script src="<?php echo BASE_URL; ?>js/ajax.js"></script>
    
</body>
</html>
