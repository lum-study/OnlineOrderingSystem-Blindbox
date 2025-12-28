<?php
require_once __DIR__ . '/../../config/init.php';
include __DIR__ . '/../../includes/header.php';

$GLOBALS['name'] = '';
$GLOBALS['email'] = '';
$GLOBALS['subject'] = '';
$GLOBALS['message'] = '';
?>

<style>
.contact-section {
    max-width: 1280px;
    margin: 0 auto;
    padding: 6rem 1.5rem;
    min-height: 70vh;
}

.contact-header {
    text-align: center;
    margin-bottom: 4rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 2rem;
}

.contact-header h1 {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.contact-header p {
    color: var(--accent-gray);
    font-size: 0.875rem;
}

.contact-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 3rem;
    max-width: 1000px;
    margin: 0 auto;
}

.contact-info {
    border: 1px solid var(--border-color);
    padding: 2rem;
}

.contact-info h2 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
}

.info-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-dim);
}

.info-item:last-child {
    border-bottom: none;
}

.info-item h3 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.info-item p {
    color: var(--accent-gray);
    font-size: 0.875rem;
}

.contact-form-wrapper {
    border: 1px solid var(--border-color);
    padding: 2rem;
}

.contact-form-wrapper h2 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
}

.alert {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border: 2px solid var(--text-color);
    background: var(--text-color);
    color: var(--bg-color);
    font-size: 0.875rem;
    display: none;
}

.alert.show {
    display: block;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 700;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    background: var(--bg-color);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    font-family: var(--font-main);
    font-size: 0.875rem;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--text-color);
    box-shadow: 2px 2px 0 0 var(--text-color);
}

.form-group textarea {
    resize: vertical;
    min-height: 150px;
}

.submit-btn {
    width: 100%;
    padding: 1rem;
    background: var(--bg-color);
    color: var(--text-color);
    border: 2px solid var(--text-color);
    font-family: var(--font-display);
    font-size: 1.25rem;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 4px 4px 0 0 var(--text-color);
}

.submit-btn:hover {
    background: var(--text-color);
    color: var(--bg-color);
    box-shadow: 2px 2px 0 0 var(--text-color);
}

@media (min-width: 768px) {
    .contact-container {
        grid-template-columns: 1fr 2fr;
    }
}
</style>

<section class="contact-section">
    <div class="contact-header">
        <h1 class="brand-font">CONTACT US</h1>
        <p>/// ESTABLISH CONNECTION ///</p>
    </div>

    <div class="contact-container">
        <div class="contact-info">
            <h2 class="brand-font">GET IN TOUCH</h2>
            
            <div class="info-item">
                <h3 class="brand-font">EMAIL</h3>
                <p>support@blindedoos.com</p>
            </div>
            
            <div class="info-item">
                <h3 class="brand-font">PHONE</h3>
                <p>+60-331236933</p>
            </div>
            
            <div class="info-item">
                <h3 class="brand-font">HOURS</h3>
                <p>Mon-Fri: 9AM - 9PM<br>Sat-Sun: 10AM - 6PM</p>
            </div>
            
            <div class="info-item">
                <h3 class="brand-font">ADDRESS</h3>
                <p>Block B-69<br>Jalan Doos 5, Taman Lakes<br>69322, Kuala Lumpur</p>
            </div>
        </div>

        <div class="contact-form-wrapper">
            <h2 class="brand-font">SEND MESSAGE</h2>
            
            <div id="successAlert" class="alert">
                ✓ Message sent successfully! We'll respond within 24 hours.
            </div>
            
            <form id="contactForm">
                <div class="form-group">
                    <label for="name">NAME *</label>
                    <?php html_text('name', 'required'); ?>
                </div>
                
                <div class="form-group">
                    <label for="email">EMAIL *</label>
                    <?php html_email('email', 'required'); ?>
                </div>
                
                <div class="form-group">
                    <label for="subject">SUBJECT *</label>
                    <?php html_text('subject', 'required'); ?>
                </div>
                
                <div class="form-group">
                    <label for="message">MESSAGE *</label>
                    <textarea id="message" name="message" autocomplete="off" required><?= encode($GLOBALS['message'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn">TRANSMIT</button>
            </form>
        </div>
    </div>
</section>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const alert = document.getElementById('successAlert');
    alert.classList.add('show');
    
    this.reset();
    
    window.scrollTo({
        top: document.querySelector('.contact-section').offsetTop,
        behavior: 'smooth'
    });
    
    setTimeout(() => {
        alert.classList.remove('show');
    }, 5000);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
