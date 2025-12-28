<?php

// Front controller for the application at project root
// All requests should be routed here via .htaccess/Apache config.

require __DIR__ . '/config/init.php';
require __DIR__ . '/core/Router.php';
require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/CartController.php';
require __DIR__ . '/controllers/CartItemsController.php';
require __DIR__ . '/controllers/CheckoutController.php';
require __DIR__ . '/controllers/HomeController.php';
require __DIR__ . '/controllers/ProfileController.php';
require __DIR__ . '/controllers/PaymentController.php';
require __DIR__ . '/controllers/OrderController.php';
require __DIR__ . '/controllers/WishlistController.php';
require __DIR__ . '/controllers/FirstLoginController.php';
require __DIR__ . '/controllers/admin/UserManagementController.php';
require __DIR__ . '/controllers/admin/StaffManagementController.php';
require __DIR__ . '/controllers/admin/AdminAuthController.php';
require __DIR__ . '/controllers/admin/AdminProfileController.php';
require __DIR__ . '/controllers/admin/DashboardController.php';
require __DIR__ . '/controllers/admin/AdminFirstLoginController.php';
require __DIR__ . '/controllers/admin/AdminForgotPasswordController.php';
require __DIR__ . '/controllers/admin/AdminProductController.php';

$router = new Router();

// ============================================================================
// PUBLIC ROUTES
// ============================================================================
$router->get('/', [HomeController::class, 'index']);

// ============================================================================
// MEMBER AUTH ROUTES
// ============================================================================
$router->get('/login', [AuthController::class, 'showLogin']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->get('/forgot', [AuthController::class, 'showForgotPassword']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/first-login', [FirstLoginController::class, 'show']);
$router->post('/first-login/reset-password', [FirstLoginController::class, 'resetPassword']);


// ============================================================================
// ADMIN ROUTES
// ============================================================================
// Admin Auth
$router->get('/admin/login', [AdminAuthController::class, 'showAdminLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/logout', [AdminAuthController::class, 'logout']);
$router->get('/admin/first-login', [AdminFirstLoginController::class, 'show']);
$router->post('/admin/first-login/reset-password', [AdminFirstLoginController::class, 'resetPassword']);
$router->get('/admin/forgot-password', [AdminForgotPasswordController::class, 'showForgotPassword']);
$router->post('/admin/forgot-password/request', [AdminForgotPasswordController::class, 'requestReset']);
$router->get('/admin/reset-password', [AdminForgotPasswordController::class, 'showResetPassword']);
$router->post('/admin/reset-password/process', [AdminForgotPasswordController::class, 'resetPassword']);

//Admin Dashboard
$router->get('/admin/dashboard', [DashboardController::class, 'show']);

// Admin Profile
$router->get('/admin/profile', [AdminProfileController::class, 'show']);
$router->post('/admin/profile/update', [AdminProfileController::class, 'update']);
$router->post('/admin/profile/change-password', [AdminProfileController::class, 'changePassword']);
$router->post('/admin/profile/upload-photo', [AdminProfileController::class, 'uploadPhoto']);

// User Management
$router->get('/admin/users', [UserManagementController::class, 'show']);
$router->get('/admin/users/get', [UserManagementController::class, 'get']);
$router->post('/admin/users/create', [UserManagementController::class, 'create']);
$router->post('/admin/users/update', [UserManagementController::class, 'update']);
$router->post('/admin/users/toggle-block', [UserManagementController::class, 'toggleBlock']);
$router->post('/admin/users/delete', [UserManagementController::class, 'delete']);

// Staff Management
$router->get('/admin/staff', [StaffManagementController::class, 'show']);
$router->get('/admin/staff/get', [StaffManagementController::class, 'get']);
$router->post('/admin/staff/create', [StaffManagementController::class, 'create']);
$router->post('/admin/staff/update', [StaffManagementController::class, 'update']);
$router->post('/admin/staff/toggle-block', [StaffManagementController::class, 'toggleBlock']);
$router->post('/admin/staff/delete', [StaffManagementController::class, 'delete']);

// Product Management
$router->get('/admin/products', [AdminProductController::class, 'index']);

//Cart
$router->get('/cart', [CartController::class, 'show']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cartitem/add', [CartItemsController::class, 'addCartItem']);
$router->post('/cartitem/get/count', [CartItemsController::class, 'getCartCount']);
$router->post('/cartitem/update/quantity', [CartItemsController::class, 'updateCartItemQuantity']);
$router->post('/cartitem/remove', [CartItemsController::class, 'removeCartItem']);

//Checkout
$router->post('/checkout', [CheckoutController::class, 'view']);
$router->post('/checkout/process', [CheckoutController::class, 'process']);
$router->post('/checkout/sendmail', callback: [CheckoutController::class, 'sendConfirmationEmail']);

//Payment
$router->post('/payment/add', [PaymentController::class, 'addPayment']);
$router->post('/payment/initiate', [PaymentController::class, 'initiatePayment']);

//Wishlist
$router->post('/wishlist/add', callback: [WishlistController::class, 'add']);
$router->post('/wishlist/remove', callback: [WishlistController::class, 'remove']);

// Profile routes
$router->post('/profile/update', [ProfileController::class, 'update']);
$router->post('/profile/change-username', [ProfileController::class, 'changeUsername']);
$router->post('/profile/change-password', [ProfileController::class, 'changePassword']);
$router->post('/profile/upload-photo', [ProfileController::class, 'uploadPhoto']);
$router->post('/profile/address/add', [ProfileController::class, 'addAddress']);
$router->post('/profile/address/edit', [ProfileController::class, 'editAddress']);
$router->post('/profile/address/delete', [ProfileController::class, 'deleteAddress']);
$router->post('/profile/address/set-default', [ProfileController::class, 'setDefaultAddress']);
$router->post('/profile/delete-account', [ProfileController::class, 'deleteAccount']);
$router->post('/profile/update-theme', [ProfileController::class, 'updateTheme']);

$router->resolve();