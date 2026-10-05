<script>
function initializeCardinalPreview() {
    var loginForm = document.getElementById('login_form');
    var resetForm = document.getElementById('reset_form');
    var loginFooter = document.getElementById('loginFooter');
    var toast;

    function showFeedback(message) {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'cardinal-feedback';
            document.querySelector('.cardinal-panel').appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toast.timeout);
        toast.timeout = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 1800);
    }

    document.querySelectorAll('.cardinal-btn').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (button.tagName === 'A') {
                event.preventDefault();
            }
            showFeedback(button.id === 'reset_send' ? 'Reset link preview' : 'Visual preview only');
        });
    });

        var forgot = document.getElementById('forgot_pass');
        if (forgot) {
            forgot.addEventListener('click', function () {
                showFeedback('Password reset is temporarily unavailable');
            });
        }

}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCardinalPreview);
} else {
    initializeCardinalPreview();
}
</script>