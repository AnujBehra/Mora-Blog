# Mora Blog - Full-Stack PHP & MySQL Web Application
Modern Digital Publishing & Content Administration System

---

## 🌟 Project Overview
This project transforms the static Mora Blog HTML template into a fully dynamic, database-backed web application with dual-role authentication (Admin & User), comprehensive content management, dynamic categories, breaking news tickers, and interactive user commenting.

### Key Improvements Over Static Template:
1. **Unified Homepage**: Eliminated redundant demo variations (Home v1, Home v2, duplicate news pages), delivering a single polished, mobile-responsive layout.
2. **Dual-Role Authentication**: Secure login/registration separating standard Users from Administrators.
3. **Database Integration**: Powered by MySQL (PDO) with parameterized queries for SQL injection prevention.
4. **Full Admin Control Panel**:
   - Live Dashboard Analytics (Post counts, categories, comments, users)
   - Article Management (Create, Edit, Delete, Draft/Publish toggles)
   - Category Management (Add, Delete with cascade protection)
   - User Management (Role promotion: User &harr; Admin, account removal)
   - Comment Moderation (Review, Approve, Delete)
5. **Interactive Front-End**:
   - Live Breaking News ticker from database
   - Featured Homepage Hero Grid
   - Real-time article search filter
   - Article view tracking
   - User comments system with instant feedback

---

## 🚀 Quick Setup & Installation

### Option 1: Using XAMPP / WAMP / MAMP (Recommended for MySQL)
1. Copy the project folder into your web directory:
   - XAMPP: `htdocs/serene-franklin/`
   - WAMP: `www/serene-franklin/`
2. Start **Apache** and **MySQL** in XAMPP/WAMP control panel.
3. Open `http://localhost/phpmyadmin`.
4. Create a database named `mora_blog`.
5. Import `database.sql` into `mora_blog`.
6. Open your browser and navigate to: `http://localhost/serene-franklin/index.php`.

### Option 2: Using PHP Built-In Server
If MySQL is not currently configured, the application includes an automatic SQLite fallback initialized directly from the schema so you can test it immediately:
```bash
php -S localhost:8000
```
Then visit: `http://localhost:8000`

### Option 3: Zero-Dependency CLI Server (macOS / Linux / Windows)
Runs immediately without downloading PHP or MySQL:
```bash
python3 server.py
```
Then open: `http://localhost:8000`

---

## 🔑 Default Test Credentials

| Role | Username / Email | Password | Access Capabilities |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` or `admin@morablog.com` | `password123` | Full access to Admin Panel (`/admin`), manage posts, categories, comments, users |
| **Standard User** | `john_doe` or `john@example.com` | `password123` | Read stories, post comments, update personal profile |

---

## 📁 Directory Structure
```
├── config/
│   ├── db.php                # PDO connection & session authentication helpers
│   └── mora_blog.sqlite      # SQLite database file for local runtime
├── includes/
│   ├── header.php            # Unified dynamic header & navigation
│   └── footer.php            # Dynamic footer & newsletter
├── admin/
│   ├── includes/             # Admin header and footer components
│   ├── index.php             # Admin Dashboard metrics & activities
│   ├── posts.php             # Article CRUD management
│   ├── post-add.php          # Draft new article
│   ├── post-edit.php         # Edit existing article
│   ├── categories.php        # Category management
│   ├── comments.php          # Comment moderation
│   └── users.php             # Role assignment & user management
├── css/
│   ├── style.css             # Unified front-end styles
│   └── admin.css             # Admin dashboard styles
├── index.php                 # Dynamic homepage (single best version)
├── news.php                  # News directory with category filter & reading times
├── post-detail.php           # Single article view with comments & likes
├── category.php              # Filtered category feed
├── about.php                 # About Us page
├── contact.php               # Contact page with feedback handling
├── login.php                 # Dual-role authentication portal
├── register.php              # User registration portal
├── logout.php                # Session termination
├── profile.php               # User profile & comment history
├── database.sql              # MySQL schema & seed data
├── server.py                 # Zero-dependency local CLI development server
├── WORK_REPORTS.md           # Formatted hourly internship work reports
└── README.md                 # Project documentation
```
