    <!-- Custom JavaScript -->
    <?php
    $formValidationPath = __DIR__ . '/../assets/js/form-validation.js';
    $formValidationVersion = file_exists($formValidationPath) ? filemtime($formValidationPath) : time();
    ?>
    <script src="assets/js/form-validation.js?v=<?php echo $formValidationVersion; ?>"></script>
    </body>

    </html>