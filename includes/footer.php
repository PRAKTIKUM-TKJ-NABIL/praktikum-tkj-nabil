    </div>
    <!-- End Main Content -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    
    <!-- Service Worker for PWA (optional) -->
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
        .then(function(registration) {
            console.log('Service Worker registered successfully');
        })
        .catch(function(err) {
            console.log('Service Worker registration failed: ', err);
        });
    }
    </script>
</body>
</html>