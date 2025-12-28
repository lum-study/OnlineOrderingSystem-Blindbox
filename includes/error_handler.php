<?php
/**
 * Error Handler Helper Functions
 * Provides utility functions to redirect to error pages
 */

/**
 * Redirect to a user-side error page
 * 
 * @param int $code HTTP error code (401, 403, 404, 500, 503, etc.)
 * @param string|null $title Optional custom error title
 * @param string|null $message Optional custom error message
 * @return void
 */
function redirectToError($code, $title = null, $message = null) {
    $errorPages = [
        401 => '/online_shopping_system/views/errors/401.php',
        403 => '/online_shopping_system/views/errors/403.php',
        404 => '/online_shopping_system/views/errors/404.php',
        500 => '/online_shopping_system/views/errors/500.php',
        503 => '/online_shopping_system/views/errors/503.php',
    ];

    // Set HTTP response code
    http_response_code($code);

    // If specific error page exists, use it
    if (isset($errorPages[$code])) {
        header("Location: " . $errorPages[$code]);
        exit;
    }

    // Otherwise, use generic error page with parameters
    $params = ['code' => $code];
    if ($title) $params['title'] = $title;
    if ($message) $params['message'] = $message;
    
    $url = '/online_shopping_system/views/errors/error.php?' . http_build_query($params);
    header("Location: " . $url);
    exit;
}

/**
 * Redirect to an admin-side error page
 * 
 * @param int $code HTTP error code (401, 403, 404, 500, 503, etc.)
 * @param string|null $title Optional custom error title
 * @param string|null $message Optional custom error message
 * @return void
 */
function redirectToAdminError($code, $title = null, $message = null) {
    $errorPages = [
        401 => '/online_shopping_system/views/errors/admin/401.php',
        403 => '/online_shopping_system/views/errors/admin/403.php',
        404 => '/online_shopping_system/views/errors/admin/404.php',
        500 => '/online_shopping_system/views/errors/admin/500.php',
        503 => '/online_shopping_system/views/errors/admin/503.php',
    ];

    // Set HTTP response code
    http_response_code($code);

    // If specific error page exists, use it
    if (isset($errorPages[$code])) {
        header("Location: " . $errorPages[$code]);
        exit;
    }

    // Otherwise, use generic admin error page with parameters
    $params = ['code' => $code];
    if ($title) $params['title'] = $title;
    if ($message) $params['message'] = $message;
    
    $url = '/online_shopping_system/views/errors/admin/error.php?' . http_build_query($params);
    header("Location: " . $url);
    exit;
}

/**
 * Display a user-side error page directly (without redirect)
 * 
 * @param int $code HTTP error code
 * @param string|null $title Optional custom error title
 * @param string|null $message Optional custom error message
 * @return void
 */
function showError($code, $title = null, $message = null) {
    $errorPages = [
        401 => __DIR__ . '/../views/errors/401.php',
        403 => __DIR__ . '/../views/errors/403.php',
        404 => __DIR__ . '/../views/errors/404.php',
        500 => __DIR__ . '/../views/errors/500.php',
        503 => __DIR__ . '/../views/errors/503.php',
    ];

    // Set HTTP response code
    http_response_code($code);

    // If specific error page exists, include it
    if (isset($errorPages[$code]) && file_exists($errorPages[$code])) {
        include $errorPages[$code];
        exit;
    }

    // Otherwise, use generic error page
    $_GET['code'] = $code;
    if ($title) $_GET['title'] = $title;
    if ($message) $_GET['message'] = $message;
    
    include __DIR__ . '/../views/errors/error.php';
    exit;
}

/**
 * Display an admin-side error page directly (without redirect)
 * 
 * @param int $code HTTP error code
 * @param string|null $title Optional custom error title
 * @param string|null $message Optional custom error message
 * @return void
 */
function showAdminError($code, $title = null, $message = null) {
    $errorPages = [
        401 => __DIR__ . '/../views/errors/admin/401.php',
        403 => __DIR__ . '/../views/errors/admin/403.php',
        404 => __DIR__ . '/../views/errors/admin/404.php',
        500 => __DIR__ . '/../views/errors/admin/500.php',
        503 => __DIR__ . '/../views/errors/admin/503.php',
    ];

    // Set HTTP response code
    http_response_code($code);

    // If specific error page exists, include it
    if (isset($errorPages[$code]) && file_exists($errorPages[$code])) {
        include $errorPages[$code];
        exit;
    }

    // Otherwise, use generic admin error page
    $_GET['code'] = $code;
    if ($title) $_GET['title'] = $title;
    if ($message) $_GET['message'] = $message;
    
    include __DIR__ . '/../views/errors/admin/error.php';
    exit;
}
