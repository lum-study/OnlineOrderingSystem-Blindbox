<?php
require_once __DIR__ . '/../../config/init.php';

$pageTitle = 'Verify Email';
include __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <div class="bg-grid"></div>
    <div class="hero-content">
        <h1 class="hero-title">Verify Email</h1>
        <p class="hero-desc" id="verification-message">Verifying your email address...</p>
        <div id="verification-result" style="display: none; margin-top: 2rem;">
            <p id="result-message" style="margin-bottom: 1rem;"></p>
            <a href="<?= BASE_URL . 'login' ?>" class="cta-btn" style="font-size: 1rem; padding: 1rem 1rem;">Back to Login</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const token = new URLSearchParams(window.location.search).get('token');
        const resultDiv = document.getElementById('verification-result');
        const messageDiv = document.getElementById('verification-message');
        const resultMessage = document.getElementById('result-message');

        if (!token) {
            messageDiv.style.display = 'none';
            resultDiv.style.display = 'block';
            resultMessage.textContent = 'Invalid verification link';
            return;
        }

        // Make AJAX request to verify email
        fetch(`<?php echo BASE_URL; ?>controllers/AuthController.php?action=verify_email&token=${encodeURIComponent(token)}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                resultMessage.textContent = data.message;

                if (data.success) {
                    resultMessage.style.color = 'var(--info-color)';
                } else {
                    resultMessage.style.color = 'var(--red-color)';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                resultMessage.textContent = 'An error occurred during verification';
                resultMessage.style.color = 'var(--red-color)';
            });
    });
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>