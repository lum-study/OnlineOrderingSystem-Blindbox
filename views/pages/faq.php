<?php
require_once __DIR__ . '/../../config/init.php';
include __DIR__ . '/../../includes/header.php';
?>

<style>
.faq-section {
    max-width: 1280px;
    margin: 0 auto;
    padding: 6rem 1.5rem;
    min-height: 70vh;
}

.faq-header {
    text-align: center;
    margin-bottom: 4rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 2rem;
}

.faq-header h1 {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.faq-header p {
    color: var(--accent-gray);
    font-size: 0.875rem;
}

.faq-list {
    max-width: 900px;
    margin: 0 auto;
}

.faq-item {
    border: 1px solid var(--border-color);
    margin-bottom: 1rem;
    transition: 0.3s;
}

.faq-item:hover {
    box-shadow: 4px 4px 0 0 var(--text-color);
}

.faq-question {
    width: 100%;
    padding: 1.5rem;
    background: var(--bg-color);
    color: var(--text-color);
    border: none;
    text-align: left;
    font-family: var(--font-display);
    font-size: 1.25rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.faq-icon {
    font-size: 1.5rem;
    transition: transform 0.3s;
}

.faq-item.active .faq-icon {
    transform: rotate(45deg);
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.faq-answer-content {
    padding: 0 1.5rem 1.5rem;
    color: var(--accent-gray);
    font-size: 0.875rem;
    line-height: 1.6;
}

.faq-item.active .faq-answer {
    max-height: 500px;
}
</style>

<section class="faq-section">
    <div class="faq-header">
        <h1 class="brand-font">FAQ</h1>
        <p>/// FREQUENTLY ASKED QUESTIONS ///</p>
    </div>

    <div class="faq-list">
        <div class="faq-item">
            <button class="faq-question">
                <span>What is a blind box?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    A blind box is a sealed package containing a collectible figure or item. You won't know which variant you'll receive until you open it. Each series has multiple designs with varying rarity levels, including secret chase variants.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                <span>What are the odds of getting a chase variant?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Chase variants typically have a 1:100 ratio. Secret variants may have different odds depending on the series. All odds are disclosed on individual product pages.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                <span>Can I return or exchange my blind box?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    No. All blind box sales are final. The mystery is part of the experience. We only accept returns for damaged or defective items within 7 days of delivery.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                <span>How long does shipping take?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Orders are dispatched within 24 hours. Domestic shipping takes 3-5 business days. International shipping takes 7-14 business days depending on location.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                <span>Are the products authentic?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Yes. We only sell 100% authentic licensed merchandise. All products come with official packaging and authenticity verification.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                <span>Do you restock sold-out items?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    Limited edition series are rarely restocked. Sign up for our newsletter to receive notifications about restocks and new releases.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                <span>Can I choose which variant I receive?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    No. Blind boxes are randomly selected. If you want a specific variant, check our marketplace section where collectors sell individual opened items.
                </div>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                <span>What payment methods do you accept?</span>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer">
                <div class="faq-answer-content">
                    We accept all major debit/credit cards, e-wallets, and cryptocurrency (BTC, ETH). All transactions are secured with SSL encryption.
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
        const item = button.parentElement;
        const isActive = item.classList.contains('active');
        
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        
        if (!isActive) {
            item.classList.add('active');
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
