<?php
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . "/../enum/SortOption.php";

$isLoggedIn = isset($_SESSION['user_id']);
$categoryModel = new Category();

try {
    $categories = $categoryModel->getAllCategories();

    // Create category lookup for faster access
    $categoryMap = [];
    foreach ($categories as $navCategory) {
        $categoryMap[$navCategory->getCategoryId()] = $navCategory->getCategoryName();
    }
} catch (Exception $e) {
    $categoryMap = [];
    error_log("Error fetching blindboxes: " . $e->getMessage());
}

?>

<!-- TOP HEADER BAR -->
<div class="top-header">
    <div class="top-header-content">
        <!-- Search Bar -->
        <div class="search-bar">
            <svg class="search-icon" viewBox="0 0 24 24" fill="currentColor" id="search-icon">
                <path
                    d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
            </svg>
            <input type="text" placeholder="Search products..." id="product-search">
        </div>
        <script>
            const searchInput = document.getElementById('product-search');
            const searchIcon = document.getElementById('search-icon');

            function redirectToSearch() {
                const query = encodeURIComponent(searchInput.value);
                window.location.href = `http://localhost/online_shopping_system/views/pages/products/product_list.php?search=${query}`;
            }

            // Redirect on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    redirectToSearch();
                }
            });

            // Redirect on clicking the search icon
            searchIcon.addEventListener('click', redirectToSearch);
        </script>

        <!-- Brand Logo -->
        <div class="brand-logo">
            <a href="<?= BASE_URL ?>" class="brand-font">BLINDE<span>DOOS</span></a>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <!-- Theme Toggle -->
            <button id="theme-toggle" class="icon-btn" title="Toggle theme">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z" />
                </svg>
            </button> <!-- Profile/Login Button -->
            <?php if ($isLoggedIn): ?>
                <div class="profile-dropdown">
                    <a href="<?= BASE_URL ?>views/pages/member/profile.php?section=edit-profile" class="icon-btn"
                        title="Profile">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                        </svg>
                    </a>
                    <div class="profile-menu">
                        <a href="<?= BASE_URL ?>views/pages/member/profile.php?section=my-orders">My Orders</a>
                        <a href="<?= BASE_URL ?>views/pages/member/profile.php?section=edit-profile">Edit Profile</a>
                        <a href="<?= BASE_URL ?>views/pages/member/profile.php?section=address-book">Address Book</a>
                        <a href="<?= BASE_URL ?>views/pages/member/profile.php?section=wishlist">Wishlist</a>
                        <a href="<?= BASE_URL ?>logout">Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="profile-dropdown">
                    <a class="icon-btn" title="Account" href="<?= BASE_URL . 'login' ?>">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                        </svg>
                    </a>
                    <div class="profile-menu">
                        <a href="<?= BASE_URL . 'login' ?>">Sign In</a>
                        <a href="<?= BASE_URL . 'register' ?>">Register</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cart Button -->
            <a href="<?= BASE_URL . 'cart' ?>" class="icon-btn" title="Cart">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" />
                </svg>
                <span id="cart-count" class="cart-count"><?= $_SESSION['cart_count'] ?? 0 ?></span>
            </a>
        </div>
    </div>
</div>

<!-- SECONDARY NAVIGATION -->
<nav class="secondary-nav">
    <div class="secondary-nav-content">
        <div class="nav-item">
            <a href="<?= BASE_URL ?>views/pages/products/product_list.php" class="nav-link">
                Browse
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 10l5 5 5-5z" />
                </svg>
            </a>
            <div class="dropdown-menu">
                <?php foreach (SortOption::cases() as $option): ?>
                    <a href="<?= BASE_URL ?>views/pages/products/product_list.php?sort=<?= $option->value ?>"><?= $option->title() ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>views/pages/products/product_list.php" class="nav-link">
                Category
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 10l5 5 5-5z" />
                </svg>
            </a>
            <div class="dropdown-menu">
                <?php foreach ($categories as $category): ?>
                    <a
                        href="<?= BASE_URL ?>views/pages/products/product_list.php?category=<?= urlencode($category->getCategoryId()) ?>">
                        <?= htmlspecialchars($category->getCategoryName()) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>views/pages/about.php" class="nav-link">
                About Us
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 10l5 5 5-5z" />
                </svg>
            </a>
            <div class="dropdown-menu">
                <a href="<?= BASE_URL ?>views/pages/about.php">Our Story</a>
                <a href="<?= BASE_URL ?>views/pages/contact.php">Contact</a>
                <a href="<?= BASE_URL ?>views/pages/faq.php">FAQ</a>
            </div>
        </div>
    </div>
</nav>