# LifeLink – Smart Blood Donor & Emergency Assistance System

> **College Mini-Project Deliverable**  
> Built, executed, and presented entirely from **Visual Studio Code** using PHP's built-in development server and standalone MySQL.  
> **Zero external control panels required (No XAMPP, No WAMP, No Apache configuration).**

---

## 🌟 Project Overview

**LifeLink** is a full-stack, responsive healthcare and emergency response platform designed to eliminate life-threatening delays in procuring blood during accidents, critical surgeries, and medical emergencies.

### Key Capabilities
- 🩸 **Live Donor Directory**: Instant filter by blood group (`A+`, `O+`, `B-`, `AB+`, etc.) and city.
- 🔒 **Privacy Shield**: Donor phone numbers are masked for guests and accessible only by authenticated users.
- 🚨 **Emergency Blood Broadcasting**: Patients/relatives can post critical blood requests specifying hospital and units needed.
- 📲 **WhatsApp SOS Share**: One-click instant sharing of emergency blood requirements to WhatsApp groups.
- 📍 **Google Maps Navigation**: Direct "Directions to Hospital" button on every emergency request card.
- 🧬 **Smart Medical Compatibility Matching**: Filters medically compatible donors (e.g. A+ recipient matches with A+, A-, O+, O-).
- 🧑‍⚕️ **Donor Portal**: Availability switch (`Available` $\leftrightarrow$ `Unavailable`), 90-day medical donation cooldown calculator, and request response/pledging.
- 📜 **Official Donor Certificate**: Printable & downloadable Certificate of Appreciation with unique ID and verification seal.
- 📊 **Admin CSV Report Export**: 1-click export of registered donors and blood requests for hospital records.
- 🛡️ **Admin Verification Queue**: Medical board screening to approve or reject pending donor applications before public listing.
- 🔐 **Hardened Security**: 100% PDO prepared statements, Bcrypt password hashing (`password_hash()`), CSRF token verification, and strict XSS escaping.

---

## 🛠️ Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Frontend** | HTML5, CSS3, Bootstrap 5.3 (CDN), Bootstrap Icons (CDN), Vanilla JavaScript |
| **Backend** | PHP 8.x (Procedural / Modular OOP, easy to explain in viva) |
| **Database** | MySQL 8.x (managed via standalone MySQL or VS Code SQLTools) |
| **Server** | PHP Built-in Server (`php -S localhost:8000`) |
| **Authentication** | PHP Session Management, Role-Based Access Control (`admin`, `donor`, `user`) |
| **Editor** | Visual Studio Code (with task runner, debugger & DB explorer) |

---

## 💻 VS Code Setup & Recommended Extensions

To achieve a modern developer workflow completely within VS Code (without phpMyAdmin or XAMPP):

1. **PHP Intelephense** (`bmewburn.vscode-intelephense-client`) — Smart autocomplete, code navigation, and syntax diagnostics.
2. **PHP Debug** (`xdebug.php-debug`) — Breakpoint debugging directly in `.php` files via Xdebug.
3. **SQLTools** (`mtxr.sqltools`) + **SQLTools MySQL/MariaDB** (`mtxr.sqltools-driver-mysql`) — Browse tables, view schema, and run queries directly inside VS Code's sidebar.
4. **Prettier - Code formatter** (`esbenp.prettier-vscode`) — Clean code styling.

The repository includes ready-to-use configuration files in `.vscode/`:
- `.vscode/settings.json` — Pre-configured formatting rules and PHP validation.
- `.vscode/launch.json` — Ready for live Xdebug execution.
- `.vscode/tasks.json` — One-click task to boot `php -S localhost:8000`.

---

## 📦 Standalone Prerequisites (No XAMPP Needed)

### 1. Install Standalone PHP (Windows via Winget or Zip)
Run PowerShell as Administrator:
```powershell
winget install PHP.PHP
```
*Or download the zip from [windows.php.net](https://windows.php.net/download/), extract to `C:\php`, and add `C:\php` to your System Environment `PATH`.*

Ensure `pdo_mysql` is enabled in your `php.ini`:
```ini
extension_dir = "ext"
extension=pdo_mysql
extension=openssl
extension=curl
```

### 2. Install Standalone MySQL
Run in PowerShell:
```powershell
winget install Oracle.MySQL
```
*Or download MySQL Community Server MSI installer from [dev.mysql.com](https://dev.mysql.com/downloads/mysql/).*

---

## 🚀 Quick Start Guide (Run in 3 Minutes)

### Step 1: Clone or Open in VS Code
Open the `LIFE LINK` project folder in Visual Studio Code:
```powershell
code "c:\Users\Admin\OneDrive\Documents\LIFE LINK"
```

### Step 2: Import the Database Schema & Seed Data
In VS Code's integrated terminal (PowerShell), run:
```powershell
Get-Content database/schema.sql | mysql -u root -p
```
*(Or if using Classic Command Prompt `cmd.exe`: `mysql -u root -p < database\schema.sql`)*
*(Enter your MySQL root password if prompted, or press Enter if blank).*

### Step 3: Check Database Connection Credentials
Open `config/db.php` and ensure your database user and password match your local MySQL configuration:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'lifelink');
define('DB_USER', 'root');
define('DB_PASS', ''); // Set your MySQL password here if not blank
```

### Step 4: Launch the Application
You can start the server in either of two ways:
- **Option A (VS Code Task)**: Press `Ctrl + Shift + B` (or menu: *Terminal* $\rightarrow$ *Run Build Task*).
- **Option B (Terminal)**: Run:
  ```powershell
  php -S localhost:8000
  ```

### Step 5: Open in Browser
Visit **[http://localhost:8000/](http://localhost:8000/)** in your browser!

---

## 🔑 Pre-Seeded Demo Accounts (Viva Demo Ready)

The database schema pre-loads realistic accounts and emergency requests:

| Role | Email Address | Password | Access & Capabilities |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@lifelink.com` | `Admin@123` | Full dashboard, verify/reject donors, manage requests, user management |
| **Verified Donor** | `john@lifelink.com` | `Donor@123` | A+ donor, toggle availability, respond to emergencies, cooldown tracker |
| **Verified Donor 2** | `sarah@lifelink.com` | `Donor@123` | O+ donor, Pune |
| **Pending Donor** | `mike@lifelink.com` | `Donor@123` | O- donor in Mumbai, shows pending status in admin review queue |
| **Public User** | `user@lifelink.com` | `User@123` | Regular requester, posts emergency blood requirements |

---

## 📂 Project Architecture & Directory Layout

```
LIFE LINK/
├── .vscode/
│   ├── settings.json          # VS Code formatting & tab settings
│   ├── launch.json            # Xdebug step-through launch config
│   └── tasks.json             # Build task to launch PHP server (Ctrl+Shift+B)
├── config/
│   └── db.php                 # PDO database connector with interactive diagnostic screen
├── database/
│   └── schema.sql             # Relational DDL tables + realistic demo seed records
├── includes/
│   ├── functions.php          # Security sanitizers (e()), CSRF token logic, flash alerts
│   ├── auth-check.php         # Role-based authorization middleware
│   ├── header.php             # Unified responsive navbar & branding
│   └── footer.php             # Medical helpline, links, scripts
├── auth/
│   ├── login.php              # Email/password authentication & role redirector
│   ├── register.php           # Unified registration for donors and requesters
│   └── logout.php             # Secure session termination
├── public/
│   ├── index.php              # Main homepage (stats, urgent requests carousel, workflow)
│   ├── about.php              # Compatibility matrix table & eligibility rules
│   ├── search.php             # Real-time donor search directory with privacy masking
│   └── request-blood.php      # Emergency request submission form
├── donor/
│   ├── dashboard.php          # Donor cockpit: toggle availability, matching requests, pledges
│   ├── profile.php            # Edit medical criteria, last donation date, and address
│   └── respond.php            # Pledge assistance to an open emergency request
├── admin/
│   ├── dashboard.php          # Metrics, summary cards, and runtime diagnostics
│   ├── verify-donors.php      # Approve/reject donor applications queue
│   ├── manage-requests.php    # Emergency blood status & urgency manager
│   └── users.php              # System user and role manager
├── assets/
│   ├── css/style.css          # Burgundy medical theme (#800020), pulse animations
│   └── js/main.js             # Client validation, password toggles, instant filter
├── index.php                  # Root router forwarding to public/index.php
├── .gitignore                 # Excludes temp files and local configs
└── README.md                  # Complete documentation and viva notes
```

---

## 🎓 Viva Voce Preparation & Examiner Q&A

### 1. What architecture does LifeLink follow?
LifeLink uses a **Modular Procedural / Clean Controller Architecture** with centralized configuration (`config/db.php`) and shared components (`includes/`). Views and controllers are structured by role (`public/`, `auth/`, `donor/`, `admin/`) ensuring separation of concerns and clear code traceability for academic examination.

### 2. How are SQL Injection vulnerabilities prevented?
By using **PDO (PHP Data Objects) Prepared Statements** with parameterized queries exclusively. User inputs are never directly concatenated into SQL strings:
```php
$stmt = $pdo->prepare("SELECT * FROM donors WHERE blood_group = ? AND city = ?");
$stmt->execute([$bloodGroup, $city]);
```
Parameter markers (`?` or named parameters) ensure data is treated strictly as literals, eliminating any possibility of SQL injection.

### 3. How does LifeLink prevent Cross-Site Scripting (XSS)?
All user-generated inputs rendered into HTML pages pass through the custom helper `e()`:
```php
function e(?string $string): string {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}
```
This safely encodes characters like `<`, `>`, `"`, and `'` into their corresponding HTML entities.

### 4. How is Cross-Site Request Forgery (CSRF) mitigated?
Every state-modifying `POST` form contains a cryptographically secure random token generated via `random_bytes(32)` stored in the PHP session. On submission, `verify_csrf()` checks the token using timing-attack-safe `hash_equals()` before processing.

### 5. Why is a standalone PHP server used instead of XAMPP/WAMP?
Modern web development prioritizes lightweight, reproducible environments. PHP's built-in web server (`php -S localhost:8000`) is self-contained, launches in milliseconds directly from the VS Code terminal, and eliminates the bloat and background overhead of Apache and WAMP control panels.

### 6. How does the Donor Medical Cooldown system work?
Whole blood donors require at least **90 days (3 months)** between donations for red blood cells to replenish. LifeLink tracks `last_donation_date` in the `donors` table and calculates days elapsed. If less than 90 days have passed, a countdown progress bar is displayed and their listing is marked accordingly.
