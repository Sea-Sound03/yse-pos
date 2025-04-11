
    </main>
    
    <footer class="bg-gray-800 text-white py-4 mt-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_setting('site_name', APP_NAME)); ?>. All rights reserved.</p>
                </div>
                <div>
                    <p>営業時間: <?php echo htmlspecialchars(get_setting('business_hours', '9:00-21:00')); ?></p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="/assets/js/app.js"></script>
    
    <?php if (isset($extraScripts)): ?>
        <?php echo $extraScripts; ?>
    <?php endif; ?>
    
    <script>
        // フラッシュメッセージの自動非表示
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');
            
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.display = 'none';
                }, 5000);
            }
            
            if (errorAlert) {
                setTimeout(function() {
                    errorAlert.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>