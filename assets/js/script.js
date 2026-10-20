// Auto close alert after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 3000);
    });
});

// Konfirmasi sebelum hapus
function confirmDelete(url) {
    if (confirm('Yakin ingin menghapus data ini?')) {
        window.location.href = url;
    }
    return false;
}