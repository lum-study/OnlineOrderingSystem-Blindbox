# BlindeDoos — Blind Box E‑commerce

Last updated: 2025-12-23

BlindeDoos is a full-featured blind-box e‑commerce application implemented with PHP 8.1+, MySQL (PDO), jQuery and Stripe. It is designed for local development on XAMPP and includes role-based authentication, first-login password flows, CSRF protection, session hardening, activity logging, a product catalog with blind-box variants, a persistent cart, wishlist, and an admin backend.

---

## Table of contents

- Features
- Tech stack & requirements
- Quick setup
- Configuration
- Database
- Running the app
- Demo accounts (local testing)
- Security & production notes
- Project structure
- Manual testing & troubleshooting
- Contributing
- License
- Changelog (highlights)

---

## Features (high level)

- Authentication: registration, email verification, login, password reset, first-login password setup, remember-me (30-day), failed-login lockout
- User profile: edit profile, addresses, profile photo upload, username/password changes, wishlist
- Product catalog: products, blind-box variants, categories, search, sorting, pagination, image galleries
- Shopping cart & checkout: persistent cart, wishlist integration, shipping selection, Stripe PaymentIntent integration
- Orders: order creation, order history, status tracking, pending-order edits/cancellations
- Admin backend: dashboard, member/staff management, product and order management, role-based access control
- Security: CSRF protection, prepared statements (PDO), XSS mitigations, secure session handling, activity logging

---

## Tech stack & requirements

- PHP 8.1 or later (enums and typing)
- MySQL 5.7+ (PDO)
- Composer
- XAMPP (Apache + MySQL) for local development
- Client: jQuery, HTML5, CSS3
- Composer dependencies: stripe/stripe-php, phpmailer/phpmailer, chillerlan/php-qrcode (optional)

---

## Quick setup

1. Install XAMPP (enable Apache and MySQL).
2. Place the project folder in your web root, for example:

   C:\xampp\htdocs\online_shopping_system

3. Install PHP dependencies:

   composer install

4. Import the database schema (via phpMyAdmin or MySQL CLI):

   - phpMyAdmin: Import `queryTable - BlindeDoos.sql` (creates `online_shopping_db`) or
   - CLI: mysql -u root -p < "path\to\queryTable - BlindeDoos.sql"

5. Configure the application (see next section).

6. Start Apache & MySQL (XAMPP control panel) and open:

   http://localhost/online_shopping_system

---

## Configuration

Edit `config/config.php` and `config/stripe.php` to set environment-specific values:

- Database: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- BASE_URL: set to your installation URL (default: `/online_shopping_system`)
- reCAPTCHA: `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` (replace sample keys)
- SMTP: set `SMTP_HOST`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_PORT`, `SMTP_FROM` for email flows
- Stripe: set test/live keys in `config/stripe.php`
- Ensure `UPLOAD_DIR` exists and is writable (default assets/images/uploads/)

Security note: do not commit secrets. For production, move sensitive values to environment variables or a secure config mechanism.

---

## Demo accounts (local testing only)

The SQL seed includes sample accounts for convenience. These are for local testing only and must be removed or changed before any public release.

- Admin
  - Username: `admin`
  - Password: `Password123!`

- Member
  - Username: `johndoe`
  - Password: `Password123!`

---

## Security & production notes

This project implements multiple security controls appropriate for development and QA. Before deploying to a public environment, ensure the following:

- Use HTTPS and valid TLS certificates.
- Replace Stripe test keys with live keys when ready.
- Replace sample SMTP credentials and disable debug/email previews.
- Remove seeded/test accounts and sample credentials.
- Store secret keys outside the repository (environment variables or secret manager).
- Harden file and directory permissions; ensure upload folders are not directly executable.
- Review session and cookie settings (secure, HttpOnly, SameSite) and adjust for your domain.

---

## Project structure (overview)

- `index.php` — application entry & route registration
- `config/` — configuration and bootstrap
- `core/` — router and core services
- `lib/` — shared utilities (Database, Auth, Session, Security, Stripe, Email)
- `models/` & `entity/` — data access objects and entities
- `controllers/` — request handlers (incl. `admin/` controllers)
- `views/` — HTML templates and page views
- `assets/` — CSS, JS, fonts, images
- `vendor/` — composer dependencies

Refer to inline documentation and `core/Router.php` for routing conventions.

---

## Manual testing & troubleshooting

A manual test checklist is included in the project. Common troubleshooting tips:

- PHP version: ensure PHP 8.1+ (enum usage)
- DB connection: verify MySQL is running and `config/config.php` credentials
- Sessions: clear browser cookies if session problems occur; check `config/init.php`
- SMTP: ensure correct SMTP host/port and credentials
- Stripe: use test keys during local testing; switch to live keys only when validated
- Uploads: ensure `UPLOAD_DIR` exists and is writable by the web server

Paths

- App: http://localhost/online_shopping_system
- phpMyAdmin: http://localhost/phpmyadmin
- PHP error log (XAMPP): C:\xampp\apache\logs\error.log

---

## Contributing

Contributions are welcome. Follow these guidelines:

- Respect the MVC separation (entities/models/controllers/views)
- Use prepared statements for DB queries (PDO)
- Use `htmlspecialchars()` to escape output where appropriate
- Prefer strong typing and enums where applicable (PHP 8.1+)
- Document complex logic and add tests for new functionality
- Remove any sample credentials or sensitive data before publishing

If you plan to contribute, open an issue or submit a pull request describing the change.

---

## License

This project is licensed under the MIT License — see the LICENSE file for details.

---

## Changelog (highlights)

Latest: 2025-12-23

- Minimum PHP requirement raised to 8.1 (enum support)
- First-login password flows enforced for new or seeded accounts
- Implemented secure "Remember me" (HttpOnly cookie, 30-day)
- Wishlist feature and reCAPTCHA server-side verification
- QR code utilities available via composer (optional)
- Session hardening with separate inactivity timeouts (members 30m, staff 2h)
- Theme toggle persists via localStorage and syncs with Stripe UI on checkout

For full details, review in-repo documentation and commit history.

---

Thank you for reviewing this project. For questions or issues, please open an issue in this repository or contact the project maintainers.

