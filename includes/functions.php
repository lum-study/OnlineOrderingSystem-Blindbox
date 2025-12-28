<?php
// functions.php
// Reusable PHP functions (validation, HTML helpers, etc.)

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Add more helpers as needed
