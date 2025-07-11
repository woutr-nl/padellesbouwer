    </div> <!-- End of container -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <?php if (isset($custom_js)): ?>
        <?php foreach ($custom_js as $js_file): ?>
            <script src="<?= $js_file ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- CSRF Token voor AJAX requests -->
    <script>
        // CSRF token toevoegen aan alle AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</body>
<footer class="text-center text-muted py-3 fixed-bottom">
    <p>Powered by <a href="https://woutr.io">WOUTR</a></p>
</footer>
</html> 