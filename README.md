# CvSU ERB Online Submission System (EthicsFlow)

A full-stack PHP + MySQL web application for the Cavite State University
Ethics Review Board (ERB) protocol submission process.

---

## 📁 Project Structure

```
cvsu-erb/
├── index.php              # Landing page
├── register.php           # Registration (restricted to @cvsu.edu.ph)
├── login.php              # Login page (blocks unverified accounts)
├── logout.php             # Session destroyer
├── verify.php             # Email verification handler  ← NEW
├── submit.php             # Multi-step submission form
├── success.php            # Post-submission confirmation
├── my-submissions.php     # Researcher: track own submissions + download certificate
├── dashboard.php          # Reviewer/Admin: manage all submissions
├── submission-detail.php  # Reviewer/Admin: review + assign reviewer
├── reports.php            # Reviewer/Admin: analytics & reports  ← NEW
├── certificate.php        # Approval certificate (printable/saveable as PDF)  ← NEW
├── admin.php              # Admin-only: user management
├── profile.php            # User profile & password change
│
├── includes/
│   ├── config.php         # DB config, helpers, session utils
│   ├── mailer.php         # Email sending & templates (incl. verification email)
│   ├── header.php         # Shared navbar partial
│   └── footer.php         # Shared footer partial
│
├── assets/
│   ├── css/style.css      # Full design system stylesheet
│   └── js/main.js         # Navigation, form steps, file uploads
│
├── uploads/               # Uploaded submission files (auto-created)
└── database.sql           # MySQL schema + seed data (incl. assigned_reviewer_id)
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.4+
- Apache or Nginx with mod_rewrite (or PHP built-in server for dev)

### 2. Database Setup
```sql
SOURCE /path/to/cvsu-erb/database.sql;
```

### 3. Configure the App
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cvsu_erb');
define('DB_USER', 'your_mysql_user');
define('DB_PASS', 'your_mysql_password');
define('APP_URL',  'http://localhost/cvsu-erb');
```

### 4. Deploy & Run
Place the folder in your web root (e.g. `C:/xampp/htdocs/cvsu-erb/`).
Open: `http://localhost/cvsu-erb/`

---

## 👤 Default Admin Account

| Field    | Value                  |
|----------|------------------------|
| Email    | admin@cvsu.edu.ph      |
| Password | password               |
| Role     | admin                  |

> ⚠️ Change the admin password after first login!

---

## 🔒 Features

| Feature | Details |
|---------|---------|
| Email restriction | Only `@cvsu.edu.ph` addresses can register |
| **Email verification** | Account activation link sent on registration ← NEW |
| Role-based access | Researcher / Reviewer / Admin with different views |
| Multi-step form | Privacy → Personal Info → Document Upload |
| Adaptive uploads | Shows only required files per submission type |
| Email confirmation | Sends HTML email with tracking number on submit |
| Status tracking | Researchers see live status updates |
| **Reviewer assignment** | Admin assigns specific reviewers to submissions ← NEW |
| **Approval certificate** | Printable/saveable PDF certificate for approved submissions ← NEW |
| **Reports & Analytics** | Submission stats, trends, reviewer performance ← NEW |
| Admin panel | Manage users and roles |

---

## 📋 Modules

| Module | Files | Description |
|--------|-------|-------------|
| User Management | register.php, login.php, verify.php, admin.php, profile.php | Registration, email verification, RBAC |
| Submission Management | submit.php, success.php, my-submissions.php | Multi-step form, 4 submission types |
| Review Process | dashboard.php, submission-detail.php | Reviewer assignment, status updates, notes |
| Tracking & Notification | my-submissions.php, mailer.php | Status tracking, automated email alerts |
| Document & Report | certificate.php, reports.php, download.php | Certificates, analytics, file downloads |
