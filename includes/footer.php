</div><!-- .app-container -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (needed for Select2) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<!-- App JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<script>
    // Global config for JS
    window.APP_CONFIG = {
        BASE_URL: '<?= BASE_URL ?>',
        CSRF_TOKEN: '<?= generateCSRFToken() ?>',
        USER_ID: <?= (int)($currentUser['id'] ?? 0) ?>,
        USER_NAME: '<?= htmlspecialchars($currentUser['name'] ?? '', ENT_QUOTES) ?>',
        USER_ROLE: '<?= htmlspecialchars($currentUser['role'] ?? '', ENT_QUOTES) ?>'
    };
    
    // Update system time
    function updateSystemTime() {
        const now = new Date();
        const timeEl = document.getElementById('systemTime');
        if (timeEl) {
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            timeEl.textContent = `${h}:${m}:${s} WIB`;
        }
    }
    setInterval(updateSystemTime, 1000);
    
    // Sidebar Toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebarBackdrop').classList.toggle('show');
    });
    
    document.getElementById('sidebarBackdrop')?.addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('show');
        this.classList.remove('show');
    });
    
    // Auto-hide flash alerts
    setTimeout(() => {
        document.querySelectorAll('.flash-alert').forEach(alert => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Init Select2 for all .select2 elements
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        
        // Init Flatpickr
        flatpickr('.datepicker', {
            dateFormat: 'Y-m-d',
            locale: 'id',
            allowInput: true
        });
    });
</script>

<?php if (isset($extraJs)): ?>
    <?= $extraJs ?>
<?php endif; ?>

</body>
</html>