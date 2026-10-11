# ProManager: Graduation Project Management System

A bilingual (Arabic / English) web platform that moves the whole graduation-project workflow, from submission to supervision to grading, out of email threads and USB drives and into one secured system. Built with **PHP** and **MySQL** for the Faculty of Information Technology at Al al-Bayt University.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-InnoDB-4479A1?logo=mysql&logoColor=white)
![i18n](https://img.shields.io/badge/i18n-Arabic%20%7C%20English-green)
![Roles](https://img.shields.io/badge/roles-Student%20%7C%20Supervisor%20%7C%20Admin-blue)

> Add 2-3 screenshots here (student dashboard, supervisor grading view, admin panel) at `docs/screenshots/`.

---

## Why this exists

Graduation projects are usually managed through a mix of face-to-face meetings, emails, messaging apps and physical drives. That causes:

- **Slow feedback**: students wait days for supervisor notes.
- **Lost or duplicated files**: no single source of truth for report and source-code versions.
- **Logistics problems**: students in field training or summer sessions can't visit campus for every update.
- **Admin overhead**: supervisor loads and department affiliations are tracked by hand.

ProManager centralizes all of this behind role-based access.

## Features

### Students
- Register a project with title, description and file upload (report as `PDF`/`DOCX`, source code as `ZIP`/`RAR`)
- Two-stage project model: **Project 1** (documentation) and **Project 2** (implementation / source code)
- Edit and re-upload submissions
- See supervisor notes and the final grade (out of 100), with a "new note" indicator
- Read announcements

### Supervisors (faculty)
- Browse student projects scoped to **their own department**
- Adopt a project for supervision, or cancel supervision
- Grade (0-100) and leave written guidance, which the student is notified about
- Download student submissions securely

### Administrators
- Separate admin portal
- Add users individually or **bulk-import from CSV** (students: name, email, university ID; faculty: name, email, with auto-generated job IDs)
- Delete users with cascading cleanup of related project data
- Publish and manage announcements
- Overview of all projects, supervisors and grades

### Platform
- Full **Arabic (RTL) and English (LTR)** interface, with language state isolated per role
- Forced password change on first login for provisioned accounts
- Password recovery via time-limited OTP (10 minutes)
- Department-aware login (CS, IS, AI & Data Science)

## Security design

| Area | Implementation |
|---|---|
| SQL injection | Prepared statements with bound parameters across all data-access paths |
| Password storage | `password_hash()` / `password_verify()` (bcrypt via `PASSWORD_DEFAULT`) |
| Password policy | Min. 8 chars with letter, number and symbol, enforced server-side |
| Access control | Session-based RBAC; every dashboard and download endpoint checks role |
| File uploads | Extension allow-list per project type; server-generated filenames (timestamp + student ID) to prevent collisions and path abuse |
| Path traversal | `basename()` on all download parameters; files served through a PHP gateway, not by direct URL |
| First-login hardening | Bulk-created accounts get a default password and are forced to change it |
| Reset flow | OTP bound to the session, 10-minute expiry, verified before the reset form is reachable |

### Known limitations and roadmap
This started as an academic project, and I've audited it with a security mindset. These are the gaps I'd close next:

- [ ] Remove the legacy plaintext-password fallback in the login check
- [ ] Generate OTPs with `random_int()` and deliver them by email/SMS (currently shown on-screen for demo purposes)
- [ ] Add CSRF tokens to state-changing forms and call `session_regenerate_id()` on login
- [ ] Add per-file ownership checks in `download.php` and validate MIME type / size on upload
- [ ] Add login rate limiting / lockout
- [ ] Tighten `uploads/` permissions (currently created `0777`) and move it outside the web root
- [ ] Move DB credentials to environment config and use a least-privilege DB user

## Tech stack

- **Backend:** PHP (procedural, `mysqli`)
- **Database:** MySQL / MariaDB (InnoDB, `utf8mb4`)
- **Frontend:** HTML5, CSS3 (custom RTL/LTR styling), vanilla JavaScript, Font Awesome
- **Environment:** XAMPP / Apache

## Database overview

`students` · `supervisors` · `admins` · `colleges` · `project_1` (documentation) · `project_2` (source code) · `announcements`

Each project row links a student to an optional supervisor and stores the grade, notes and a "new note" flag.

## Getting started

```bash
# 1. Clone into your web root (e.g. XAMPP's htdocs)
git clone https://github.com/<your-username>/promanager.git

# 2. Create the database and import the schema
mysql -u root -e "CREATE DATABASE grad_db CHARACTER SET utf8mb4;"
mysql -u root grad_db < 127_0_0_1.sql

# 3. Configure credentials in connection_settings.php
#    ($sname, $uname, $password, $db_name)

# 4. Make sure the uploads/ directory exists and is writable by the web server

# 5. Open http://localhost/promanager/
#    Admin portal: http://localhost/promanager/admin_portal.php
```

> Use a dedicated, least-privilege MySQL user instead of `root` for anything beyond local testing.

## Project structure

```
├── index.php                  # Login (role, college, department)
├── process_login.php          # Authentication logic
├── student_dashboard.php      # Submit / edit projects, view grade & notes
├── supervisor_dashboard.php   # Adopt, grade and comment
├── admin_portal.php           # Admin login
├── admin_dashboard.php        # Users, bulk CSV import, announcements
├── forgot_password.php        # OTP reset flow (request → verify → reset)
├── change_password*.php       # First-login and voluntary password change
├── download.php               # Authenticated file gateway
├── lang_config.php            # AR/EN translations, per-role language state
├── connection_settings.php    # DB configuration
├── 127_0_0_1.sql              # Schema
└── style.css
```

## Team

Developed as a graduation project at Al al-Bayt University, Faculty of Information Technology (Computer Information Systems),

## License

Add a license of your choice (e.g. MIT) before publishing.
