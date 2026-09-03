# BlindeDoos — Mystery Blind Box E-Commerce & Ordering System

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Apache](https://img.shields.io/badge/Server-Apache%20%28XAMPP%29-D22128?style=flat&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![Stripe](https://img.shields.io/badge/Payment-Stripe%20API-635BFF?style=flat&logo=stripe&logoColor=white)](https://stripe.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**BlindeDoos** is an online ordering and inventory management web application tailored for mystery blind box collectibles and designer art toys. Built with vanilla PHP (structured MVC architecture), MySQL (PDO with prepared statements), jQuery, and vanilla CSS, it delivers a cyberpunk-inspired shopping experience complete with payment processing, dynamic order QR code generation, email notifications, and an administrative control panel.

---

## Table of Contents

- [System Architecture](#system-architecture)
- [Key Features](#key-features)
  - [Storefront & Catalog](#1-storefront--catalog)
  - [Cart & Multi-Step Checkout](#2-cart--multi-step-checkout)
  - [Member Portal](#3-member-portal)
  - [Administrative Control Panel](#4-administrative-control-panel)
  - [Security & Authentication Hardening](#5-security--authentication-hardening)
- [Database & Data Modeling](#database--data-modeling)
  - [Entity Relationship Overview](#entity-relationship-overview)
  - [Custom ID Generation Scheme](#custom-id-generation-scheme)
- [Pre-Seeded Accounts for Testing](#pre-seeded-accounts-for-testing)
- [Prerequisites & Requirements](#prerequisites--requirements)
- [Step-by-Step Installation](#step-by-step-installation)
- [Configuration Settings](#configuration-settings)
- [Project Directory Structure](#project-directory-structure)
- [Troubleshooting & FAQs](#troubleshooting--faqs)
- [License](#license)

---

## System Architecture

The application adopts a layered Model-View-Controller (MVC) and Front Controller architectural design pattern:

```
                      ┌────────────────────────────┐
                      │    HTTP / AJAX Request     │
                      └─────────────┬──────────────┘
                                    │
                                    ▼
                         ┌─────────────────────┐
                         │      .htaccess      │ (Apache mod_rewrite)
                         └──────────┬──────────┘
                                    │
             ┌──────────────────────┴──────────────────────┐
             │ Clean Route URL                             │ Direct Script URL
             ▼                                             ▼
  ┌──────────────────────┐                     ┌───────────────────────┐
  │      index.php       │                     │    views/pages/...    │
  │  (Front Controller)  │                     │  (Page Entry Point)   │
  └──────────┬───────────┘                     └───────────┬───────────┘
             │                                             │
             ▼                                             ▼
  ┌──────────────────────┐                     ┌───────────────────────┐
  │   core/Router.php    │                     │ includes/access_control│
  │  (Route Dispatcher)  │                     │  (Role Path Enforcer) │
  └──────────┬───────────┘                     └───────────┬───────────┘
             │                                             │
             └──────────────────────┬──────────────────────┘
                                    │
                                    ▼
                         ┌─────────────────────┐
                         │   config/init.php   │
                         │  • Session Start    │
                         │  • AccountGuard     │
                         │  • Security Headers │
                         │  • Autoloader       │
                         └──────────┬──────────┘
                                    │
       ┌────────────────────────────┼────────────────────────────┐
       ▼                            ▼                            ▼
┌──────────────┐             ┌──────────────┐             ┌──────────────┐
│ Controllers  │◄───────────►│  Entities &  │◄───────────►│  Views / UI  │
│ (App Logic)  │             │    Models    │             │  (Templates) │
└──────┬───────┘             └──────┬───────┘             └──────────────┘
       │                            │
       │                            ▼
       │                     ┌──────────────┐
       │                     │   Database   │
       │                     │ (MySQL/PDO)  │
       │                     └──────────────┘
       ▼
┌──────────────┐
│  Libraries   │ (Auth, StripeLib, Email, IDGenerator, Validator, Security)
└──────────────┘
```

- **Front Controller & Routing (`index.php`, `core/Router.php`)**: Handles clean URL paths (e.g., `/cart`, `/checkout`, `/admin/dashboard`) and routes requests to appropriate controller actions.
- **Dual Direct Access Security (`includes/access_control.php`, `lib/AccountGuard.php`)**: Protects direct file access within `views/pages/` using centralized access rules and checks on every request.
- **Separation of Concerns**:
  - `entity/`: Data transfer objects (DTOs) representing individual database records with typed getters and setters.
  - `models/`: Active record query handlers executing prepared PDO statements.
  - `controllers/` & `controllers/admin/`: Orchestrate business logic, perform input sanitization, enforce CSRF tokens, and render responses.
  - `views/`: Reusable presentation templates organized by role (`pages/`, `pages/member/`, `pages/admin/`, `views/errors/`).
  - `lib/`: Decoupled core infrastructure modules (Database singleton, Session manager, Mailer wrapper, ID generator, Stripe integration, etc.).

---

## Key Features

### 1. Storefront & Catalog

- **Cyberpunk / Pop Mart Visual Identity**: Neo-brutalist dark mode theme with neon accents, custom grid backgrounds, animated announcement marquees, and responsive CSS layouts.
- **Dynamic Catalog Filtering & Sorting**:
  - Live price range filter computed from database minimum and maximum prices.
  - Category filtering by blind box series.
  - Search query matching across product names and descriptions.
  - Sorting criteria: Price (Low to High / High to Low), Newest Releases, Name (A-Z / Z-A), and Popularity.
  - Pagination handled via `SimplePager` (`lib/Pagination.php`).
- **Product Detail Breakdown**:
  - Blind box series overview, price, stock status, and description.
  - Detailed showcase of individual secret/chase variants included in the set.
  - Front image presentation (`is_front` ordering via `product_images`).
  - Verified customer reviews with 1–5 star ratings, timestamped reviews, and customer-submitted photos.

### 2. Cart & Multi-Step Checkout

- **Real-Time Shopping Cart**:
  - Add to cart, adjust quantities, or remove items with stock validation.
  - Immediate subtotal and total calculations.
  - Integration with user Wishlist (move items back and forth).
- **Flexible Fulfillment Methods**:
  - **Home Delivery**: Standard courier dispatch (RM5.00 flat rate).
  - **Store Self-Pickup**: Free (RM0.00) pickup at 4 major Klang Valley retail locations:
    1. Taman Usahawan, Kepong
    2. Mid Valley Megamall
    3. Pavilion Kuala Lumpur
    4. 1 Utama Shopping Centre
- **Order Breakdown**: Itemized subtotal, delivery fee calculation, and 6% SST tax computation.
- **Address Book Integration**: Select an existing shipping address or add a new one inline with country phone prefix support.
- **Payment Options**:
  - **Credit/Debit Card via Stripe**: Uses Stripe's PaymentIntent API (`lib/StripeLib.php`) for card processing in Malaysian Ringgit (MYR).
  - **Cash on Delivery / In-Store Cash**: Manual payment reconciliation.
- **Automated Digital Invoices & QR Codes**:
  - Generates dynamic QR codes (`chillerlan/php-qrcode`) encoded with order identification.
  - Dispatches HTML order confirmation emails via PHPMailer with embedded logo branding and the verification QR code.

### 3. Member Portal

- **Profile Management**: Update full name, contact number, gender, date of birth, and change account username/password.
- **Profile Photo Cropper**: Interactive modal to upload and crop profile avatars.
- **Address Book**: Manage multiple delivery addresses with default selection.
- **Order History & Fulfillment Tracker**:
  - Tabbed overview by status: `to_pay`, `pending`, `shipped`, `completed`, and `cancelled`.
  - Self-service order completion upon receipt of goods.
  - Order cancellation for eligible pending orders.
- **Multi-Item Order Reviews**: Submit star ratings, written comments, and photo uploads for each item in a fulfilled order.
- **Wishlist**: Bookmark desired blind boxes for future purchases.
- **Theme Preferences**: Toggle between Dark Mode and Light Mode, with preferences stored in the user's database profile.

### 4. Administrative Control Panel

- **Executive Analytics Dashboard**:
  - Real-time customer acquisition metrics (total, today, this week, this month).
  - Product inventory status (active, inactive, out-of-stock, low-stock warnings ≤ 20 units).
  - Order fulfillment counters (pending, processing, delivered, completed).
  - Revenue analytics and trends.
- **Role-Based Access Control (RBAC)**:
  - Granular positions: `admin`, `manager`, `stock_keeper`, `support`, `marketing`, `security`, `intern`.
  - Sensitive administrative tasks (such as staff account provisioning) are restricted to `admin` and `manager` positions.
- **Customer User Management**:
  - Searchable, sortable, and paginated customer registry.
  - Detailed customer profiles, address history, and order count.
  - Instant account blocking and unblocking with immediate session invalidation.
  - Soft deletion protecting relational order history.
- **Staff Management**:
  - Provision employee accounts, assign positions, and track activity.
  - Self-protection guards preventing staff from blocking or deleting their own accounts.
- **Blind Box & Product Management**:
  - Create and edit blind box series (name, category, price, stock, active/inactive status).
  - Manage individual figure variants inside each blind box series.
  - Multiple image uploads with `is_front` front-cover selection.
  - Category management with foreign-key dependency verification (prevents deletion of categories with attached products unless reassigned).
- **Order Management**:
  - Centralized order board displaying customer, items, total amount, shipping method, and payment status.
  - Order status updates (`pending` → `shipped` → `completed` / `cancelled`).
  - Shipping address adjustment modal.
- **System Activity Logs**:
  - Audit logging (`activity_logs`) recording IP addresses, user agents, action types (login, failed login, lockouts, record modifications), and timestamps.

### 5. Security & Authentication Hardening

- **Isolated Sessions**: Segregated session storage (`$_SESSION['member']` and `$_SESSION['admin']`) to prevent privilege escalation between customer and administrator accounts.
- **Session Lifespans & Inactivity Timeouts**:
  - 30-minute inactivity timeout for customer sessions.
  - 2-hour inactivity timeout for administrative staff.
  - Session regeneration on privilege changes (`session_regenerate_id(true)`).
- **Brute-Force & Lockout Policy**:
  - Configurable threshold (`MAX_FAILED_ATTEMPTS = 3`).
  - Account lockout for 5 minutes (`LOCKOUT_DURATION_MINUTES = 5`).
  - Atomic expiration checking that clears expired locks upon subsequent login attempts.
- **Active Session Termination (`AccountGuard`)**:
  - Runs on every HTTP request and AJAX call.
  - Immediately logs out blocked users and invalidates remember-me tokens.
- **First-Time Login Protocol**: Newly provisioned accounts are required to change their temporary default passwords before accessing system features.
- **Email Verification & Token Expiry**:
  - Unverified accounts cannot authenticate.
  - One-time verification tokens expire after 24 hours.
  - Password reset tokens expire after 1 hour.
- **CSRF & XSS Defenses**:
  - Anti-CSRF token verification (`Security::verifyCSRF()`) on all state-changing `POST` requests.
  - Output encoding via `htmlspecialchars()` / `encode()` across form inputs.
  - Secure HTTP headers (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`).
- **Bot Mitigation**: Google reCAPTCHA v2 verification integrated on public registration, member login, and administrative login pages.

---

## Database & Data Modeling

The database schema is defined in [`queryTable - BlindeDoos.sql`] with 21 relational tables and full foreign-key cascading constraints.

### Entity Relationship Overview

| Table Name            | Primary Key     | Description                                                                                 |
| --------------------- | --------------- | ------------------------------------------------------------------------------------------- |
| `user_data`           | `user_id`       | Customer personal information, profile photos, preferences, block & soft-delete flags.      |
| `user_logins`         | `login_id`      | Customer authentication credentials (bcrypt hash), lock status, email verification tokens.  |
| `staff_data`          | `staff_id`      | Employee profile details and status.                                                        |
| `staff_logins`        | `login_id`      | Staff authentication credentials, administrative position/role, and lockout tracking.       |
| `verification_tokens` | `token_id`      | Expiring tokens for email verification and password reset workflows.                        |
| `user_addresses`      | `address_id`    | Customer shipping addresses with default address designations.                              |
| `orders`              | `order_id`      | Order transactions, subtotal, tax amount, total amount, order status, and delivery address. |
| `order_items`         | `order_item_id` | Line items belonging to an order referencing blind box products and purchased prices.       |
| `payments`            | `payment_id`    | Payment records, methods (`card`, `cash`, `ewallet`, `others`), and transaction status.     |
| `refunds`             | `refund_id`     | Refund requests linked to payment records.                                                  |
| `categories`          | `category_id`   | Product categories (e.g., Spy x Family, THE MONSTERS, CRYBABY, DIMOO, etc.).                |
| `blindbox`            | `blindbox_id`   | Blind box series records with prices, stock levels, and active/inactive status.             |
| `blindbox_images`     | `image_id`      | Image assets associated with blind box series.                                              |
| `products`            | `product_id`    | Individual character/figure variants contained within a blind box series.                   |
| `product_images`      | `image_id`      | Variant images, including front cover flags (`is_front`).                                   |
| `carts`               | `cart_id`       | Customer active shopping carts.                                                             |
| `cart_items`          | `cart_item_id`  | Items currently held in customer shopping carts with quantities.                            |
| `blindbox_reviews`    | `review_id`     | Customer reviews and star ratings (1–5) linked to purchased order items.                    |
| `review_images`       | `image_id`      | Image attachments uploaded by customers alongside product reviews.                          |
| `activity_logs`       | `activity_id`   | System-wide audit logs tracking user/staff actions, IP addresses, and user agents.          |
| `wishlist`            | `wishlist_id`   | Customer bookmarked blind box products.                                                     |

### Custom ID Generation Scheme

All table primary keys use formatted, human-readable IDs generated via [`lib/IDGenerator.php`]. IDs use a specific prefix followed by zero-padded 4-digit numbers (`0001`–`9999`):

- `UD`: `user_data`
- `UL`: `user_logins`
- `SD`: `staff_data`
- `SL`: `staff_logins`
- `VT`: `verification_tokens`
- `UA`: `user_addresses`
- `OR`: `orders`
- `OI`: `order_items`
- `PM`: `payments`
- `RF`: `refunds`
- `CT`: `categories`
- `BB`: `blindbox`
- `BI`: `blindbox_images`
- `PR`: `products`
- `PI`: `product_images`
- `CR`: `carts`
- `CI`: `cart_items`
- `BR`: `blindbox_reviews`
- `RI`: `review_images`
- `AL`: `activity_logs`
- `WL`: `wishlist`

---

## Pre-Seeded Accounts for Testing

All test accounts seeded in `queryTable - BlindeDoos.sql` share the universal password:

```text
Password123!
```

### Administrative & Staff Accounts (`/admin/login`)

| Username       | Role / Position | Email                          | Full Name       |
| -------------- | --------------- | ------------------------------ | --------------- |
| `admin`        | `admin`         | `admin@blindedoos.com`         | Admin User      |
| `alicegreen`   | `manager`       | `alice.green@blindedoos.com`   | Alice Green     |
| `bobwhite`     | `stock_keeper`  | `bob.white@blindedoos.com`     | Bob White       |
| `charlieblack` | `support`       | `charlie.black@blindedoos.com` | Charlie Black   |
| `ians`         | `marketing`     | `ian.s@blindedoos.com`         | Ian Somerhalder |
| `liamn`        | `security`      | `liam.n@blindedoos.com`        | Liam Neeson     |
| `peterp`       | `intern`        | `peter.p@blindedoos.com`       | Peter Parker    |

_(14 additional staff accounts from `SL0008` to `SL0021` are available in the SQL script)._

### Member / Customer Accounts (`/login`)

| Username    | Email                       | Full Name     | Verified |
| ----------- | --------------------------- | ------------- | -------- |
| `johndoe`   | `john@example.com`          | John Doe      | Yes      |
| `janesmith` | `jane@example.com`          | Jane Smith    | Yes      |
| `mikebrown` | `michael.brown@example.com` | Michael Brown | Yes      |
| `emilyd`    | `emily.davis@example.com`   | Emily Davis   | Yes      |
| `chrisw`    | `chris.wilson@example.com`  | Chris Wilson  | Yes      |

_(17 additional member accounts from `UL0006` to `UL0022` are available in the SQL script)._

---

## Prerequisites & Requirements

1. **Web Server**: Apache 2.4+ with `mod_rewrite` enabled.
2. **PHP**: Version 8.1 or higher.
   - Required extensions: `pdo`, `pdo_mysql`, `openssl`, `mbstring`, `curl`, `gd` (or `imagick`), `json`.
3. **Database Server**: MySQL 8.0+ or MariaDB 10.4+.
4. **Package Manager**: [Composer](https://getcomposer.org/) (for installing external libraries).
5. **Environment**: [XAMPP](https://www.apachefriends.org/) (recommended for local Windows setup) or native WAMP / LAMP.

---

## Step-by-Step Installation

### 1. Clone or Move Project to Web Server Root

Place the project inside your Apache document root:

- **XAMPP Windows**: `C:\xampp\htdocs\online_shopping_system`
- **Linux Apache**: `/var/www/html/online_shopping_system`

> [!IMPORTANT]
> The default routing configuration relies on the subfolder name `/online_shopping_system/`. Ensure the directory name matches `BASE_URL` in `config/config.php` and `RewriteBase` in `.htaccess`.

### 2. Install Composer Dependencies

Open your terminal, navigate to the project root, and install the vendor libraries:

```bash
composer install
```

This installs:

- `stripe/stripe-php` (^19.0)
- `phpmailer/phpmailer` (^7.0)
- `chillerlan/php-qrcode` (^5.0)

### 3. Initialize the Database

1. Open your MySQL management tool (e.g., phpMyAdmin at `http://localhost/phpmyadmin` or MySQL CLI).
2. Import the provided schema and seed file:
   ```bash
   mysql -u root -p < "queryTable - BlindeDoos.sql"
   ```
   _(Or copy and execute the SQL contents directly in phpMyAdmin)._
3. This creates the database `online_shopping_db` along with all required tables, constraints, categories, products, orders, and test accounts.

### 4. Verify Folder Permissions

Ensure the server has write permissions to the upload directories:

- `assets/images/uploads/`
- `assets/images/uploads/profile/`
- `assets/images/uploads/reviews/`
- `assets/images/uploads/BB/`
- `uploads/`

### 5. Load The Website

- `http://localhost/online_shopping_system/`
- `http://localhost/online_shopping_system/admin/login`

---

## Configuration Settings

### Application & Database Configuration (`config/config.php`)

Edit [`config/config.php`] to update database credentials, email dispatch settings, and security parameters:

```php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shopping_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default is empty
define('DB_CHARSET', 'utf8mb4');

// Base URL (Must match your Apache deployment path)
define('BASE_URL', '/online_shopping_system/');

// Security & Lockout Policy
define('MAX_FAILED_ATTEMPTS', 3);
define('LOCKOUT_DURATION_MINUTES', 5);

// Google reCAPTCHA v2 Keys
define('RECAPTCHA_SITE_KEY', 'YOUR_RECAPTCHA_SITE_KEY');
define('RECAPTCHA_SECRET_KEY', 'YOUR_RECAPTCHA_SECRET_KEY');

// SMTP Email Delivery (Gmail / Mailtrap / SendGrid)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_16_digit_app_password'); // Use Gmail App Password
define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
define('SMTP_FROM_NAME', 'BlindeDoos');
define('SMTP_DEBUG', 0); // 0=Off, 1=Client, 2=Server
```

### Stripe Payment Gateway (`config/stripe.php`)

Stripe API keys are located in [`config/stripe.php`]:

```php
return [
    'secret_key'      => "sk_test_...",
    'publishable_key' => "pk_test_..."
];
```

---

## Project Directory Structure

```text
OnlineOrderingSystem-Blindbox/
├── .htaccess                       # Apache rewrite rules & URL routing
├── composer.json                   # Project dependencies (Stripe, PHPMailer, QRCode)
├── composer.lock                   # Locked dependency versions
├── config/
│   ├── config.php                  # Database, SMTP, reCAPTCHA, and lockout settings
│   ├── init.php                    # Initialization, session startup, autoloader, and guards
│   └── stripe.php                  # Stripe secret and publishable API keys
├── core/
│   └── Router.php                  # Route dispatcher and custom error page resolver
├── controllers/
│   ├── AuthController.php          # Member registration, login, verification, and reset
│   ├── CartController.php          # Cart presentation and initialization
│   ├── CartItemsController.php     # Cart item mutations (add, update, remove)
│   ├── CheckoutController.php      # Order processing, delivery selection, and QR emailing
│   ├── FirstLoginController.php    # Member mandatory initial password reset
│   ├── HomeController.php          # Storefront landing page controller
│   ├── OrderController.php         # Member order retrieval, completion, and cancellation
│   ├── PaymentController.php       # Stripe PaymentIntent initialization and payment recording
│   ├── ProductController.php       # Product catalog retrieval, filtering, and price queries
│   ├── ProfileController.php       # Member profile, addresses, credentials, and preferences
│   ├── ReviewController.php        # Order item star ratings and multi-photo uploads
│   ├── WishlistController.php      # Wishlist addition and removal
│   └── admin/
│       ├── AdminAuthController.php # Staff authentication and login security
│       ├── AdminFirstLoginController.php # Staff initial password setup
│       ├── AdminForgotPasswordController.php # Staff password recovery workflow
│       ├── AdminOrderController.php# Staff order status and address modification
│       ├── AdminProductController.php # Blind box, variant, and category management
│       ├── AdminProfileController.php # Staff profile edits and avatar uploads
│       ├── DashboardController.php # Administrative statistics and KPI analytics
│       ├── StaffManagementController.php # Employee provisioning, blocking, and deletion
│       └── UserManagementController.php # Customer account management and blocking
├── entity/                         # Data Transfer Objects (DTOs)
│   ├── ActivityLog.php
│   ├── Blindbox.php
│   ├── BlindboxImage.php
│   ├── Cart.php
│   ├── CartItems.php
│   ├── Category.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Payment.php
│   ├── Product.php
│   ├── ProductImage.php
│   ├── Review.php
│   ├── ReviewImage.php
│   ├── StaffData.php
│   ├── StaffLogin.php
│   ├── UserAddress.php
│   ├── UserData.php
│   ├── UserLogin.php
│   ├── VerificationToken.php
│   └── Wishlist.php
├── enum/
│   └── SortOption.php              # Product catalog sort enumerations
├── includes/                       # Shared layout components and guards
│   ├── access_control.php          # Role path enforcement for view templates
│   ├── admin_guard.php             # Admin route authentication guard
│   ├── auth_required.php           # Member route authentication guard
│   ├── error_handler.php           # HTTP error redirection helper
│   ├── footer.php                  # Public site footer
│   ├── functions.php               # Common helper utilities
│   ├── guest_only.php              # Guard restricting logged-in users from guest pages
│   ├── header.php                  # Public HTML header, meta tags, and CSS links
│   ├── navbar.php                  # Navigation bar with live search and category menu
│   ├── photo_upload_modal.php      # Reusable image cropping and upload modal
│   ├── admin/
│   │   ├── admin_only.php          # Strict admin privileges validation
│   │   ├── auth_required.php       # Staff session presence validation
│   │   ├── footer.php              # Administrative footer
│   │   ├── header.php              # Administrative HTML header
│   │   ├── sidebar.php             # Admin navigation sidebar with live profile display
│   │   └── user_management_modal.php # Shared modal components for User/Staff CRUD
│   └── member/
│       ├── auth_required.php       # Member session validation
│       ├── profile_modal.php       # Profile modification modals
│       ├── review_modal.php        # Star rating and feedback submission modal
│       └── wishlist_section.php    # Wishlist presentation view
├── lib/                            # Core service libraries
│   ├── AccountGuard.php            # Active session termination for blocked accounts
│   ├── AdminAuth.php               # Administrative authentication engine
│   ├── Auth.php                    # Member authentication and remember-me handler
│   ├── Database.php                # PDO database connection and query helper
│   ├── Email.php                   # PHPMailer email dispatching service
│   ├── HtmlHelpers.php             # Form generation helpers with XSS protection
│   ├── IDGenerator.php             # Prefixed sequential primary key generator
│   ├── Pagination.php              # Custom SQL pagination engine (SimplePager)
│   ├── Recaptcha.php               # Google reCAPTCHA v2 API validation
│   ├── Security.php                # Password hashing, CSRF tokens, and sanitization
│   ├── Session.php                 # Dual-namespace session and timeout manager
│   ├── StripeLib.php               # Stripe PaymentIntent API integration wrapper
│   └── Validator.php               # Form input and password strength validators
├── models/                         # Active record database access layer
│   ├── ActivityLog.php
│   ├── Blindbox.php
│   ├── BlindboxImage.php
│   ├── Cart.php
│   ├── CartItems.php
│   ├── Category.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── Payment.php
│   ├── Product.php
│   ├── ProductImage.php
│   ├── StaffData.php
│   ├── StaffLogin.php
│   ├── UserAddress.php
│   ├── UserData.php
│   ├── UserLogin.php
│   ├── VerificationToken.php
│   └── Wishlist.php
├── views/
│   ├── errors/                     # HTTP error views (401, 403, 404, 500, 503)
│   │   └── admin/                  # Admin-themed HTTP error views
│   └── pages/
│       ├── about.php               # About Us page
│       ├── contact.php             # Contact support page
│       ├── faq.php                 # Frequently asked questions
│       ├── first_login.php         # Customer first-login password change page
│       ├── forgot_password.php     # Customer password reset request page
│       ├── home.php                # Storefront landing page
│       ├── login.php               # Customer login page
│       ├── register.php            # Customer registration page
│       ├── reset_password.php      # Customer password reset token entry page
│       ├── verify_email.php        # Email verification notification page
│       ├── admin/
│       │   ├── activity_logs.php   # System audit logs page
│       │   ├── admin_first_login.php # Staff first-login password change
│       │   ├── dashboard.php       # Administrative metrics dashboard
│       │   ├── forgot_password.php # Staff password reset request
│       │   ├── login.php           # Administrative login portal
│       │   ├── profile.php         # Staff profile management
│       │   ├── reset_password.php  # Staff password reset confirmation
│       │   ├── orders/
│       │   │   └── admin_list.php  # Order management list and status editor
│       │   ├── products/
│       │   │   └── admin_products.php # Product catalog and category manager
│       │   └── users/
│       │       ├── staff_list.php  # Staff management list
│       │       └── user_list.php   # Customer user management list
│       ├── member/
│       │   ├── profile.php         # Member profile and order history
│       │   ├── cart/
│       │   │   ├── checkout.php    # Multi-step checkout view
│       │   │   └── view.php        # Shopping cart view
│       │   └── orders/
│       │       └── history.php     # Order history presentation
│       └── products/
│           ├── product_detail.php  # Single blind box product details
│           └── product_list.php    # Filterable and sortable product catalog
├── assets/                         # Static assets (CSS, JS, Fonts, Images)
│   ├── css/                        # Custom vanilla CSS design system
│   ├── js/                         # Vanilla JS, jQuery 3.7.1, and application scripts
│   └── images/                     # Icons, product images, and upload repositories
├── index.php                       # Application entry point & route definitions
├── queryTable - BlindeDoos.sql     # Database schema DDL and seed records
├── Fix xampp error                 # Recovery guide for XAMPP MySQL errors
└── LICENSE                         # MIT License
```

---

## Troubleshooting & FAQs

### 1. XAMPP MySQL Fails to Start (InnoDB Corruption)

If MySQL in XAMPP crashes on startup or reports table corrupted errors, follow the steps outlined in [`Fix xampp error`]:

1. Stop MySQL from the XAMPP Control Panel.
2. Navigate to `C:\xampp\mysql`.
3. Rename the folder `data` to `data_old`.
4. Make a duplicate copy of the folder `backup` and rename it to `data`.
5. Copy all database folders from `data_old` into `data` (excluding `mysql`, `performance_schema`, `phpmyadmin`, and `test`).
6. Copy the file `ibdata1` from `data_old` into `data`.
7. Restart MySQL from the XAMPP Control Panel.

### 2. URL Rewriting / 404 on Sub-Routes

If visiting URLs like `/login`, `/cart`, or `/admin/dashboard` returns a 404 error:

- Ensure Apache `mod_rewrite` is enabled in `httpd.conf` (`LoadModule rewrite_module modules/mod_rewrite.so`).
- Verify `AllowOverride All` is configured in your Apache virtual host or directory configuration.
- Check that `RewriteBase /online_shopping_system/` in `.htaccess` matches your folder name.

### 3. Emails Not Sending (SMTP Issues)

- If using Gmail SMTP, you must use a **Google App Password** (16 characters), not your regular Gmail account password.
- Ensure 2-Step Verification is active on your Google account to create App Passwords.
- For local testing without an external SMTP server, tools such as [Mailtrap](https://mailtrap.io/) or [Testmail](https://testmail.app/) can be used by setting `SMTP_HOST` and `SMTP_PORT` in `config/config.php`.

### 4. reCAPTCHA Verification Fails Locally

- By default, placeholder keys are included in `config/config.php`.
- Obtain valid v2 "I'm not a robot" Checkbox keys from the [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin) and register `localhost` / `127.0.0.1` as authorized domains.

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
