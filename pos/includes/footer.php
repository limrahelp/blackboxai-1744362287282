</div>
    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; <?php echo date('Y'); ?> Restaurant POS System</p>
        </div>
    </footer>
    <script>
    // Global AJAX error handler
    window.addEventListener('error', function(e) {
        console.error('Global error:', e.message);
        alert('An error occurred. Please try again.');
    });
    </script>
</body>
</html>
