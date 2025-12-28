<?php
require_once __DIR__ . '/../../config/init.php';
include __DIR__ . '/../../includes/header.php';
?>

<style>
.about-section {
    max-width: 1280px;
    margin: 0 auto;
    padding: 6rem 1.5rem;
    min-height: 70vh;
}

.about-hero {
    text-align: center;
    margin-bottom: 4rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 3rem;
}

.about-hero h1 {
    font-size: 5rem;
    margin-bottom: 1rem;
    line-height: 1;
}

.about-hero .tagline {
    font-size: 1rem;
    color: var(--accent-gray);
    margin-bottom: 2rem;
}

.about-content {
    display: grid;
    grid-template-columns: 1fr;
    gap: 3rem;
    margin-bottom: 4rem;
}

.about-box {
    border: 1px solid var(--border-color);
    padding: 2rem;
    transition: 0.3s;
}

.about-box:hover {
    background: var(--text-color);
    color: var(--bg-color);
    box-shadow: 8px 8px 0 0 rgba(255, 255, 255, 0.2);
}

.about-box h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.about-box p {
    font-size: 0.875rem;
    line-height: 1.8;
    color: var(--accent-gray);
}

.about-box:hover p {
    color: var(--bg-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 3rem;
}

.stat-card {
    border: 1px solid var(--border-color);
    padding: 2rem;
    text-align: center;
}

.stat-card h3 {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}

.stat-card p {
    font-size: 0.75rem;
    color: var(--accent-gray);
}

@media (min-width: 768px) {
    .about-content {
        grid-template-columns: 1fr 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>

<section class="about-section">
    <div class="about-hero">
        <p class="tagline">/// TRANSMISSION ORIGIN ///</p>
        <h1 class="brand-font">ABOUT<br>BLINDEDOOS</h1>
        <p style="max-width: 600px; margin: 0 auto; color: var(--accent-gray); font-size: 0.875rem;">
            We curate the unknown. Every box is a portal to possibility.
        </p>
    </div>

    <div class="about-content">
        <div class="about-box">
            <h2 class="brand-font">OUR STORY</h2>
            <p>
                Founded in 2024, BlindeDoos emerged from a collective obsession with mystery and collectibles. 
                We believe the best experiences come from the unexpected. Our mission is to deliver authentic, 
                high-quality blind boxes that transform collecting into an adventure.
            </p>
        </div>

        <div class="about-box">
            <h2 class="brand-font">THE VOID</h2>
            <p>
                The void represents infinite potential. Each sealed box contains a universe of possibilities. 
                We partner with top manufacturers worldwide to bring you exclusive series, limited editions, 
                and chase variants that define modern collecting culture.
            </p>
        </div>

        <div class="about-box">
            <h2 class="brand-font">AUTHENTICITY</h2>
            <p>
                Every product is 100% authentic and officially licensed. We work directly with manufacturers 
                and authorized distributors. No bootlegs. No compromises. Just genuine collectibles with 
                verified authenticity.
            </p>
        </div>

        <div class="about-box">
            <h2 class="brand-font">COMMUNITY</h2>
            <p>
                Join thousands of collectors worldwide. Share your pulls, trade variants, and connect with 
                fellow enthusiasts. Our community Discord and marketplace make it easy to complete your 
                collection and discover new series.
            </p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3 class="brand-font">50K+</h3>
            <p>BOXES SHIPPED</p>
        </div>
        <div class="stat-card">
            <h3 class="brand-font">120+</h3>
            <p>SERIES AVAILABLE</p>
        </div>
        <div class="stat-card">
            <h3 class="brand-font">15K+</h3>
            <p>COLLECTORS</p>
        </div>
        <div class="stat-card">
            <h3 class="brand-font">24H</h3>
            <p>DISPATCH TIME</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
