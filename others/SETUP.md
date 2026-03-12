# Clinic Management System — Setup Guide

Everything you need to get this project running locally from scratch, including
database setup, test accounts, PHPMailer, and SMTP configuration.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Project Installation](#2-project-installation)
3. [Database Setup](#3-database-setup)
4. [Verify the App Runs](#4-verify-the-app-runs)
5. [Test Accounts](#5-test-accounts)
6. [Seeding Test Data](#6-seeding-test-data)
7. [PHPMailer Setup](#7-phpmailer-setup)
8. [SMTP Configuration (Gmail)](#8-smtp-configuration-gmail)
9. [SMTP Configuration (Other Providers)](#9-smtp-configuration-other-providers)
10. [Running the Test Suite](#10-running-the-test-suite)
11. [Configuration Reference](#11-configuration-reference)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. Prerequisites

Install the following before starting:

| Software | Version | Download |
|---|---|---|
| XAMPP | 8.x+ | https://www.apachefriends.org |
| PHP (bundled with XAMPP) | 8.0+ | — |
| Node.js (for Playwright tests only) | 18+ | https://nodejs.org |

> **Windows users:** Install XAMPP to the default path `C:\Program Files\XAMPP\`
> or `C:\xampp\`. This guide assumes the default path.

### Required PHP Extensions

These are all enabled by default in XAMPP — verify in `php.ini` if needed:

```
extension=pdo_mysql
extension=curl
extension=mbstring
extension=openssl
```

To check, open **http://localhost/dashboard/phpinfo.php** after starting XAMPP
and search for each extension name.

---

## 2. Project Installation

### Step 1 — Copy project files

Place the entire project folder inside XAMPP's web root:

```
C:\Program Files\XAMPP\htdocs\clinic management system\
```

The folder name must match exactly (with the space) because the API base URL is
hardcoded as `http://localhost/clinic%20management%20system/api`.

### Step 2 — Start XAMPP services

1. Open **XAMPP Control Panel** (as Administrator).
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Both rows should turn green with port numbers (Apache: 80, MySQL: 3306).

### Step 3 — Install Composer dependencies (PHPMailer)

Open a terminal in the project root and run:

```powershell
cd "C:\Program Files\XAMPP\htdocs\clinic management system"
php composer.phar install
```

This installs PHPMailer from `composer.json`. Packages are installed into the
`api/vendor/` folder (configured by the `vendor-dir` key in `composer.json`).
This step is **required** for email notifications to work.

To confirm it worked:

```powershell
php composer.phar show phpmailer/phpmailer
```

You should see a line like `phpmailer/phpmailer  6.x.x  ...`.

Also verify the directory was created:

```powershell
Test-Path "api\vendor\phpmailer"
```

Should print `True`.

---

## 3. Database Setup

### Step 1 — Open phpMyAdmin

Navigate to **http://localhost/phpmyadmin** in your browser.

Default credentials (XAMPP default):
- **Username:** `root`
- **Password:** *(leave blank)*

### Step 2 — Import the schema

1. Click **New** in the left sidebar to create a database — **skip this**, the
   SQL file creates it automatically.
2. Click the **Import** tab at the top.
3. Click **Choose File** and select:
   ```
   C:\Program Files\XAMPP\htdocs\clinic management system\database_schema.sql
   ```
4. Leave all settings as default and click **Go**.

You should see: *"Import has been successfully finished."*

The import creates:
- Database: `clinic_cms`
- 13 tables: `users`, `patient_profiles`, `staff_profiles`, `doctors`,
  `medicines`, `appointments`, `medical_records`, `prescriptions`,
  `prescription_items`, `lab_tests`, `invoices`, `reviews`, `clinic_settings`
- Default clinic settings rows
- All 6 test user accounts (see Section 5)

### Step 3 — Verify

In phpMyAdmin, click **clinic_cms** in the left sidebar. You should see all
13 tables listed. Click `users` → **Browse** to confirm the test accounts exist.

---

## 4. Verify the App Runs

Open your browser and go to:

```
http://localhost/clinic%20management%20system/
```

You should see the clinic landing page. If you see a blank page or a 404:

- Confirm Apache is running in XAMPP Control Panel.
- Confirm the folder is named exactly `clinic management system` (with space).
- Check Apache error logs: XAMPP Control Panel → Apache → **Logs**.

---

## 5. Test Accounts

All test accounts use the password: **`password`**

| Role | Email | Notes |
|---|---|---|
| Admin | test.admin@clinic.com | Full system access |
| Doctor | test.doctor@clinic.com | General Medicine, LIC-TEST-001 |
| Doctor 2 | test.doctor2@clinic.com | Used for cross-doctor isolation tests |
| Patient | test.patient@clinic.com | Patient Alpha |
| Receptionist | test.reception@clinic.com | Front desk access |
| Lab Technician | test.lab@clinic.com | Lab queue access |
| Pharmacist | test.pharm@clinic.com | Prescription & billing access |

> These accounts are inserted by `database_schema.sql` and use Laravel's default
> bcrypt hash for `password`. Do **not** use these credentials in production.

---

## 6. Seeding Test Data

The schema creates user accounts but no appointment/record/invoice data.
Run the seeder to populate realistic test data:

```powershell
cd "C:\Program Files\XAMPP\htdocs\clinic management system"
php seed_data.php
```

This creates:
- Appointments (scheduled, completed, cancelled) for the test patient & doctors
- Medical records with prescriptions and prescription items
- Lab test orders (pending and completed)
- Invoices (pending and paid)
- Doctor reviews

> Re-running the seeder is safe — it uses `INSERT IGNORE` to avoid duplicates.

---

## 7. PHPMailer Setup

PHPMailer is used by the system for email notifications (appointment reminders,
etc.). It is installed via Composer (Step 2 above).

### How it works

All email sending goes through a single shared utility:

```
api/config/mailer.php  →  function sendClinicEmail($pdo, $to, $subject, $body)
```

This function:
1. Reads SMTP settings from the `clinic_settings` DB table.
2. Checks the `email_notifications` toggle — returns `'disabled'` if off.
3. If `smtp_host` is set: sends via PHPMailer over real SMTP.
4. If `smtp_host` is **blank**: falls back to PHP's built-in `mail()` function
   (works with MailHog on local dev — see Section 9).
5. Returns `true` on success, `false` on failure (error is written to the PHP
   error log).

### The autoloader path

Composer installs packages to `api/vendor/` (configured in `composer.json`).
The mailer loads it with:

```php
require_once __DIR__ . '/../vendor/autoload.php';  // resolves to api/vendor/
```

Do not move or rename `api/vendor/` — this path is fixed.

### Where SMTP credentials are stored

SMTP settings are stored in the `clinic_settings` database table, **not** in
any `.env` file or config file. You configure them through the Admin UI:

**Admin login → Settings → Email / SMTP**

Or you can insert them directly into the database (see Section 8 below).

---

## 8. SMTP Configuration (Gmail)

Gmail is the easiest option to test with. Follow these exact steps:

### Step 1 — Enable 2-Step Verification on your Google account

1. Go to **https://myaccount.google.com/security**
2. Under *How you sign in to Google*, click **2-Step Verification** and turn it on.

> This is required before you can generate an App Password.

### Step 2 — Generate a Gmail App Password

1. Go to **https://myaccount.google.com/apppasswords**
2. In the *App name* field, type: `Clinic CMS`
3. Click **Create**.
4. Google shows a **16-character password** (e.g., `abcd efgh ijkl mnop`).
5. **Copy it immediately** — it is only shown once. Remove the spaces when using it.

### Step 3 — Enter settings in the Admin dashboard

Log in as `test.admin@clinic.com` / `password`, go to **Settings**, and fill in:

| Field | DB key | Value |
|---|---|---|
| SMTP Host | `smtp_host` | `smtp.gmail.com` |
| SMTP Port | `smtp_port` | `587` |
| Encryption | `smtp_encryption` | `tls` |
| SMTP Username | `smtp_user` | `your.gmail.address@gmail.com` |
| SMTP Password | `smtp_pass` | *(the 16-character App Password, no spaces)* |
| From Name | `smtp_from_name` | `Clinic CMS` *(or any display name)* |
| Clinic Email | `clinic_email` | `your.gmail.address@gmail.com` *(used as the From address)* |
| Enable Notifications | `email_notifications` | `1` *(must be 1 or emails are silently skipped)* |

Click **Save Settings**.

> **Important:** The password field shows `••••••••` when you reload the
> Settings page. This is intentional masking — the real password is stored
> in the DB. If you save without changing the password field, the existing
> password is preserved automatically.

### Step 4 — Test it

Send a test email or trigger an action that sends a notification. Check your
inbox and the **Spam** folder.

### Alternative: Insert directly into the database

If you prefer, run this in phpMyAdmin's SQL tab (replace values with your own):

```sql
USE clinic_cms;

INSERT INTO clinic_settings (setting_key, setting_value)
VALUES
  ('smtp_host',           'smtp.gmail.com'),
  ('smtp_port',           '587'),
  ('smtp_encryption',     'tls'),
  ('smtp_user',           'your.gmail@gmail.com'),
  ('smtp_pass',           'abcdefghijklmnop'),
  ('smtp_from_name',      'Clinic CMS'),
  ('clinic_email',        'your.gmail@gmail.com'),
  ('email_notifications', '1')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  updated_at = CURRENT_TIMESTAMP;
```

---

## 9. SMTP Configuration (Other Providers)

Use this table to find the correct values for your email provider:

| Provider | SMTP Host | Port | Encryption | Notes |
|---|---|---|---|---|
| **Gmail** | smtp.gmail.com | 587 | tls | Requires App Password (see Section 8) |
| **Outlook / Hotmail** | smtp.office365.com | 587 | tls | Use your full email + account password |
| **Yahoo Mail** | smtp.mail.yahoo.com | 587 | tls | Requires App Password from Yahoo security settings |
| **Mailtrap** (testing) | sandbox.smtp.mailtrap.io | 2525 | tls | Free; emails go to inbox.mailtrap.io, never real recipients |
| **SendGrid** | smtp.sendgrid.net | 587 | tls | Use `apikey` as username, API key as password |
| **Mailgun** | smtp.mailgun.org | 587 | tls | Use your Mailgun SMTP credentials |
| **MailHog** (local) | localhost | 1025 | *(none)* | No auth needed; requires MailHog running locally |

### Option A — Mailtrap (Recommended for online testing)

Mailtrap captures all outgoing emails so they never reach real inboxes — ideal
for testing without risk.

1. Sign up free at **https://mailtrap.io**
2. Go to **Email Testing → Inboxes → your inbox → SMTP Settings**
3. Select **PHPMailer** from the integration dropdown — it shows the exact
   values to copy.
4. Enter those values in the Admin Settings page.

### Option B — MailHog (no account needed, fully local)

MailHog is a local SMTP server that catches all outgoing mail in a web UI.
The mailer automatically falls back to it when `smtp_host` is left **blank**.

1. Download MailHog for Windows:
   **https://github.com/mailhog/MailHog/releases** → `MailHog_windows_amd64.exe`
2. Double-click the `.exe` to start it (no install needed).
3. Leave all SMTP fields **blank** in Admin Settings (or set `smtp_host` to
   `localhost` and `smtp_port` to `1025` with no encryption and no auth).
4. Open **http://localhost:8025** in your browser to see all captured emails.

---

## 10. Running the Test Suite

### PHP API Tests (302 tests, all roles)

```powershell
cd "C:\Program Files\XAMPP\htdocs\clinic management system"
php tests/user_flow_tests.php
```

Run a single role only:

```powershell
php tests/user_flow_tests.php --role=doctor
php tests/user_flow_tests.php --role=patient
php tests/user_flow_tests.php --role=receptionist
php tests/user_flow_tests.php --role=lab_tech
php tests/user_flow_tests.php --role=pharmacist
php tests/user_flow_tests.php --role=admin
```

Show raw API responses for debugging:

```powershell
php tests/user_flow_tests.php --verbose
```

### Playwright E2E Tests (requires Node.js)

Install Node dependencies first (one-time):

```powershell
npm install
npx playwright install
```

Run all E2E tests:

```powershell
npx playwright test
```

> Playwright tests require a browser and a running XAMPP server. Apache and
> MySQL must be started before running them.

---

## 11. Configuration Reference

### api/config.php — Database connection

```php
define('DB_HOST', 'localhost');   // Change if MySQL runs on a different host
define('DB_NAME', 'clinic_cms'); // Must match the database name created above
define('DB_USER', 'root');       // Default XAMPP MySQL user
define('DB_PASS', '');           // Default XAMPP MySQL password (blank)
```

If you have set a MySQL root password in XAMPP, update `DB_PASS` accordingly.

### Session security flags

```php
define('SESSION_SECURE_COOKIE', false); // Set to true when using HTTPS
define('SESSION_SAMESITE',       'Strict');
define('SESSION_HTTPONLY',        true);
```

Set `SESSION_SECURE_COOKIE` to `true` only if your server runs HTTPS (not
needed for local development).

### CORS / API base URL

The API base URL used throughout the frontend and test suite:

```
http://localhost/clinic%20management%20system/api
```

If you rename the project folder, update:
1. `js/api.js` — `BASE_URL` constant
2. `tests/user_flow_tests.php` — `BASE_URL` constant

---

## 12. Troubleshooting

### "Database Connection Failed"

- Confirm MySQL is running (green in XAMPP Control Panel).
- Confirm `DB_NAME` is `clinic_cms` in `api/config.php`.
- If you set a MySQL password, update `DB_PASS` in `api/config.php`.

### 404 on any API endpoint

- Verify Apache is running.
- Verify the URL path is `clinic%20management%20system` (URL-encoded space).
- Check `C:\Program Files\XAMPP\logs\error.log` for PHP errors.

### "Call to undefined function curl_init()"

The `curl` extension is not enabled. Open `php.ini`:

```
C:\Program Files\XAMPP\php\php.ini
```

Find `;extension=curl` and remove the leading semicolon:

```ini
extension=curl
```

Restart Apache in XAMPP Control Panel.

### PHPMailer: "SMTP connect() failed"

Check these in order:

1. **App Password** — Confirm you are using an App Password, not your Gmail
   account password. Regular passwords are blocked by Google.
2. **2FA** — App Passwords only work if 2-Step Verification is ON.
3. **Port blocked** — Some ISPs block port 587. Try port 465 with
   `smtp_encryption = ssl` instead.
4. **Firewall** — Temporarily disable Windows Defender Firewall and retry to
   rule out a local block.
5. **Less secure apps** — This setting no longer exists in Google. You must use
   App Passwords.

### PHPMailer: "SMTP Error: Could not authenticate"

- The App Password was entered with spaces — remove all spaces.
- The App Password was regenerated — update the stored value in Admin Settings.
- The Gmail account has 2FA disabled — re-enable it and regenerate the App Password.

### Tests fail with "401 Unauthorized" on everything

XAMPP is not running or the session is not persisting across cURL requests.
Confirm Apache is started, then re-run:

```powershell
php tests/user_flow_tests.php --role=patient --verbose
```

The `--verbose` flag shows the raw response body for each request, making it
easy to spot the exact error message.

### Port 80 already in use (Apache won't start)

Another program (IIS, Skype, etc.) is using port 80. Options:

- Stop the conflicting program, or
- Change Apache's port: XAMPP Control Panel → Apache → **Config** →
  `httpd.conf` → change `Listen 80` to `Listen 8080`, then access the app
  at `http://localhost:8080/clinic%20management%20system/`.

  If you change the port, update `BASE_URL` in `js/api.js` and
  `tests/user_flow_tests.php`.
