<!-- Loading Overlay -->
<div id="loading-overlay" class="fixed inset-0 bg-black/70 flex items-center justify-center z-[9999]">
    <div class="text-center">
        <div class="inline-block w-12 h-12 border-4 border-gray-300 border-t-primary rounded-full animate-spin"></div>
        @if(isset($message))
            <p class="mt-4 text-white text-sm">{{ $message }}</p>
        @endif
    </div>
</div>

<script>
// Loading overlay functions
window.showLoading = function(message) {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        if (message) {
            const messageEl = overlay.querySelector('p');
            if (messageEl) {
                messageEl.textContent = message;
                messageEl.style.display = 'block';
            }
        }
        overlay.style.display = 'flex';
    }
};

window.hideLoading = function() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
};

// Auto-hide loading on page load
document.addEventListener('DOMContentLoaded', function() {
    window.hideLoading();
});

// Hide loading on pageshow (for back/forward navigation)
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.hideLoading();
    }
});

// Hide loading on visibility change (for tab switching)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        window.hideLoading();
    }
});
</script>
