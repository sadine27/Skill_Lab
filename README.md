# Smart Waste Aggregation System
> A role-based web application for citizen waste reporting, collector dispatch, and admin oversight — built with PHP, MySQL, HTML/CSS/JS.

---

## Overview

Citizens report waste accumulation with a photo and location. Collectors receive assigned jobs and update collection status. Admins monitor the full pipeline through a live dashboard.

```
Citizen submits report → Collector updates status → Admin sees it on dashboard
```

---

## Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Frontend   | HTML5, CSS3, Vanilla JavaScript   |
| Backend    | PHP (PDO, no frameworks)          |
| Database   | MySQL                             |
| Local Dev  | XAMPP (Apache + MySQL + PHP)      |
| Deployment | Shared hosting / college server   |

---

## System Roles

| Role             | What they do                                               |
|------------------|------------------------------------------------------------|
| Citizen          | Register, submit waste reports, track report status        |
| Waste Collector  | View assigned reports, mark collections as done            |
| Admin            | View all reports, manage users, see system-wide analytics  |

---

## Features by Module

### Module 1 — Auth (Member 1, Phase 1 Blocker)
- User registration with role selection (citizen / collector)
- Login with PHP sessions
- Role-based redirects on login
- Session guard on every protected page (`auth_check.php`)

### Module 2 — Citizen (Member 2)
- Submit waste report: category, description, GPS/text location, photo upload
- View own report history with live status badges
- Edit or cancel pending reports

### Module 3 — Waste Collector (Member 3)
- Dashboard listing reports assigned to them
- Update report status: Pending → In Progress → Collected
- Add collection notes and timestamp

### Module 4 — Admin (Member 4)
- Overview dashboard: total reports, open/closed counts, waste by category chart
- User management: list, activate/deactivate accounts
- Assign reports to specific collectors
- Export report data as CSV

---

## Database Schema Overview

```
users
  id, name, email, password_hash, role (citizen|collector|admin), created_at, is_active

waste_categories
  id, name, description

waste_reports
  id, citizen_id (FK users), category_id (FK waste_categories),
  description, location_text, photo_path,
  status (pending|in_progress|collected|rejected),
  assigned_to (FK users), created_at, updated_at

collection_logs
  id, report_id (FK waste_reports), collector_id (FK users),
  action, notes, logged_at
```

---

## Development Phases

### Phase 1 — Foundation *(Member 1 only, finish by Day 2–3)*
- Write `schema.sql` and import it into MySQL
- Build `db.php` (PDO singleton)
- Implement register / login / logout / `auth_check.php`
- **All other members are blocked until this is done.**

### Phase 2 — Parallel Module Development *(M2, M3, M4 simultaneously)*
- Each member builds their module end-to-end: HTML UI + PHP backend + DB queries
- Use dummy sessions (`$_SESSION['user_id'] = 1`) and test data until Phase 1 merges
- No shared files — each module lives in its own folder

### Phase 3 — Integration
- Merge all branches; resolve conflicts carefully
- Replace dummy sessions with real auth from Phase 1
- Build shared `navbar.php` with role-based links
- Create a single `assets/style.css` with CSS variables for consistent theming

### Phase 4 — Testing
Each member tests their own module against three failure points:
1. **SQL injection** — verify every query uses PDO prepared statements, no string concatenation
2. **Auth bypass** — try accessing `/admin/`, `/collector/` directly without login; must redirect
3. **File upload edge cases** — upload a `.php` file disguised as an image; must be rejected

### Phase 5 — Deployment
1. Export `schema.sql` and run it on the production server
2. Update `db.php` credentials for the live server
3. Upload all files via FTP or Git
4. Verify PHP version compatibility (target PHP 7.4+)
5. Test end-to-end on a real browser (not just localhost)

---

## Team Responsibilities

| Member   | Module            | Key Deliverables                                          | Dependency         |
|----------|-------------------|-----------------------------------------------------------|--------------------|
| Member 1 | Auth + Foundation | `schema.sql`, `db.php`, register/login, `auth_check.php` | **Blocks everyone**|
| Member 2 | Citizen           | Report submission form, photo upload, status tracker      | Needs Phase 1      |
| Member 3 | Collector         | Job list, status update flow, collection log              | Needs Phase 1      |
| Member 4 | Admin             | Dashboard stats, user management, report assignment       | Needs Phase 1      |

---

## Local Setup

1. **Install XAMPP** and start Apache + MySQL
2. Clone or copy this project into `C:/xampp/htdocs/waste_system/`
3. Open `http://localhost/phpmyadmin` → **Import** → choose `schema.sql`
   (it creates the `waste_system` database, all tables, seed categories, and a
   default admin account)
4. `db.php` is already configured for XAMPP defaults; adjust only if yours differ:
   ```php
   $host = 'localhost';
   $db   = 'waste_system';
   $user = 'root';
   $pass = '';  // XAMPP default
   ```
5. Open `http://localhost/waste_system/` and log in, or register a new account.

**Default admin login** (created by `schema.sql` — change the password after first login):

| Email               | Password   |
|---------------------|------------|
| `admin@waste.local` | `admin123` |

---

## Suggested Folder Structure

```
waste_system/
├── db.php                  # PDO connection (shared)
├── auth_check.php          # Session guard (shared)
├── schema.sql              # Full DB schema
├── auth/
│   ├── register.php
│   ├── login.php
│   └── logout.php
├── citizen/
│   ├── index.php           # Report list
│   ├── submit.php          # New report form
│   └── view.php            # Single report detail
├── collector/
│   ├── index.php           # Assigned jobs list
│   └── update.php          # Update status
├── admin/
│   ├── index.php           # Dashboard
│   ├── users.php           # User management
│   └── reports.php         # All reports + assign
└── assets/
    ├── style.css           # Shared styles (CSS variables)
    ├── app.js              # Shared JS utilities
    └── uploads/            # Citizen-uploaded photos
```

---

## Security Checklist

- [ ] All SQL queries use **PDO prepared statements** — no `$_GET`/`$_POST` directly in queries
- [ ] Every protected page starts with `require '../auth_check.php'`
- [ ] File uploads: validate MIME type, reject executable extensions (`.php`, `.exe`, `.sh`)
- [ ] Store uploaded files **outside** webroot or in a folder with `.htaccess` blocking script execution
- [ ] Passwords hashed with `password_hash()` / verified with `password_verify()`
- [ ] Admin and Collector pages check role in session, not just login status

---

## License

MIT License — free to use for academic and educational purposes.
