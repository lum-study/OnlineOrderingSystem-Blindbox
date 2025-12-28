<?php
// Guest only guard - Redirect logged-in users to home
// Used for login, register, forgot password pages
define('ACCESS_CONTROL_HANDLED', true);

if (Auth::check()) {
    header('Location: ' . BASE_URL);
    exit;
}
