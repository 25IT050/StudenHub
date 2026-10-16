# Charusat University - Faculty of Technology & Engineering (FTE)
## Department of Information Technology
### Web Development Frameworks (ITUE203) - Semester 3rd (2026-27)
---

# StudentHub - Master Backend & Full-Stack Practical Report
**Student Project:** StudentHub Enterprise University Portal  
**Target Course:** ITUE203 Web Development Frameworks  
**Integration Hub:** [`php/p15_portal_home.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p15_portal_home.php)  
**Database Schema:** [`php/studenthub_schema.sql`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/studenthub_schema.sql)  
**Database Seeder:** [`php/seed_database.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/seed_database.php)  
**Automated Test Suite:** [`php/test_suite.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/test_suite.php)  

---

## 📑 Summary of Completed Practicals (7 through 15)

| Practical No. | Title | Core Technology & Implementation | File Deliverable | Status |
| :---: | :--- | :--- | :--- | :---: |
| **P7** | PHP Form Processing with Server-Side Validation & File Storage | POST sanitization, regex checks, CSV & JSON dual-storage, CSRF token | [`p7_form_processing.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p7_form_processing.php) | **Complete** |
| **P8** | MySQL Schema Design, ER Model, PDO Connectivity & Stored Procedures | 3NF normalized schema, PDO connection, prepared statements, stored procedure | [`p8_db_connectivity.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p8_db_connectivity.php) | **Complete** |
| **P9** | Secure User Registration with Password Hashing & Duplicate Checks | `password_hash()`, duplicate email verification, regex validation, audit log | [`p9_secure_registration.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p9_secure_registration.php) | **Complete** |
| **P10** | Secure Authentication, Sessions, RBAC & Inactivity Timeout | Session regeneration, `password_verify()`, 15-min timeout, remember-me token | [`p10_login.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p10_login.php) | **Complete** |
| **P11** | Student Management CRUD with Search, Filter & CSV Export | MySQLi/PDO CRUD, multi-criteria search/filter, soft delete, CSV export | [`p11_manage_students.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p11_manage_students.php) | **Complete** |
| **P12** | Event Management CRUD with Poster Upload & File Validation | MIME & size validation, randomized file renaming, folder protection, audit log | [`p12_manage_events.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p12_manage_events.php) | **Complete** |
| **P13** | PHP JSON API & AJAX/Fetch-Based CRUD (Zero Page Reload) | RESTful JSON API (GET, POST, PUT, DELETE), 300ms debounce, Fetch async/await | [`p13_ajax_crud.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p13_ajax_crud.php) | **Complete** |
| **P14** | Admin Dashboard with Role-Based Access, Status Toggle & Audit Log | RBAC protection, aggregated SQL statistics, user toggle, HTML5 Canvas chart | [`p14_admin_dashboard.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p14_admin_dashboard.php) | **Complete** |
| **P15** | Full Integration, Automated Diagnostics, Testing & Deployment | Master navigation portal, automated diagnostic suite, deployment guide | [`p15_portal_home.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p15_portal_home.php) | **Complete** |

*(Note: Practicals 1 to 6 in `pages/`, `css/`, `js/`, and `data/` remain strictly untouched and preserved).*

---

## 🔐 Pre-Seeded Default Accounts & Credentials

To test authentication and role-based access control (RBAC), run [`php/seed_database.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/seed_database.php):

| Role | Email Address | Plaintext Password | Access Permissions |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@studenthub.com` | `Admin@123` | Full access to Admin Dashboard, Events CRUD, User Status Toggle, and Audit Logs |
| **Student** | `student@studenthub.com` | `Student@123` | Student Dashboard, Enrolled Events View, Session Inactivity Countdown |

---

## 🛠️ Practical-by-Practical Implementation Details

### Practical 7: PHP Form Processing with Server-Side Validation and File Storage
- **File:** [`php/p7_form_processing.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p7_form_processing.php)
- **Features Implemented:**
  - POST-method form handling with `filter_var()` and `strip_tags()` sanitization.
  - Server-side validation enforcing alphabetic name (3-50 chars), valid email format, 10-digit Indian phone number (`/^[6-9]\d{9}$/`), and year/course requirements.
  - Dual storage: Safe file appending to CSV (`data/registrations.csv`) and structured JSON (`data/registrations.json`).
  - **Intermediate Extension:** Real-time on-page table displaying all stored CSV/JSON records.
  - **Advanced Extension:** Cryptographic CSRF token verification.

### Practical 8: MySQL Schema Design, ER Model, PDO Connectivity, and Prepared Statements
- **Files:** [`php/db_connect.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/db_connect.php), [`php/p8_db_connectivity.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p8_db_connectivity.php), [`php/studenthub_schema.sql`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/studenthub_schema.sql)
- **Features Implemented:**
  - 3NF normalized schema featuring tables: `courses`, `users`, `students`, `events`, `registrations`, and `audit_logs`.
  - Primary keys with `AUTO_INCREMENT` and relational integrity enforced with Foreign Keys (`ON DELETE RESTRICT/CASCADE`, `ON UPDATE CASCADE`).
  - Robust PDO connection with `PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`, and disabled emulated prepares.
  - **Intermediate Extension:** Prepared statement interactive demo filtering students by course code.
  - **Advanced Extension:** Stored Procedure `GetEventRegistrationStats` aggregating event registrations and executed via PDO `CALL`.

### Practical 9: Secure User Registration with Duplicate Checks and Password Hashing
- **File:** [`php/p9_secure_registration.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p9_secure_registration.php)
- **Features Implemented:**
  - Secure registration inserting into both `users` and `students` tables via transactional prepared statements.
  - Duplicate check querying existing email in database prior to insert.
  - Password strength validation (min 8 chars, uppercase letter, number, and special character `@$!%*?&`).
  - Cryptographic hashing with `password_hash($password, PASSWORD_BCRYPT)`.
  - **Intermediate Extension:** 100% Parameterized prepared statements.
  - **Advanced Extension:** CSRF token verification and audit log entry created upon every registration.

### Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout & Remember-Me
- **Files:** [`php/p10_login.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p10_login.php), [`php/p10_logout.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p10_logout.php), [`php/p10_student_dashboard.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p10_student_dashboard.php), [`php/auth_helper.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/auth_helper.php)
- **Features Implemented:**
  - Secure authentication verifying password hash using `password_verify()`.
  - Session fixation mitigation via `session_regenerate_id(true)`.
  - Role-Based Redirection: Admins redirected to `p14_admin_dashboard.php`, students redirected to `p10_student_dashboard.php`.
  - 15-Minute session inactivity timeout enforced in `auth_helper.php`.
  - **Intermediate Extension:** Last-login timestamp update stored in `users.last_login`.
  - **Advanced Extension:** Secure Remember-Me implementation using cryptographic SHA-256 token stored in database and cookie.

### Practical 11: Student Management CRUD with Search and Filter
- **Files:** [`php/p11_manage_students.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p11_manage_students.php), [`php/p11_export_csv.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p11_export_csv.php)
- **Features Implemented:**
  - Full CRUD: Add student (INSERT), view student list with pagination, edit student (UPDATE), and delete with JS confirmation.
  - Dynamic multi-column search (name, email, mobile) and dropdown filters (course, year, status).
  - Minimum 20 seeded student records pre-loaded.
  - **Intermediate Extension:** One-click CSV export preserving active search/filter criteria.
  - **Advanced Extension:** Soft delete mechanism using `status = 'inactive'` with option to restore or permanently purge.

### Practical 12: Event Management CRUD with Poster Upload and File Validation
- **File:** [`php/p12_manage_events.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p12_manage_events.php)
- **Features Implemented:**
  - Complete Event CRUD operations linked to `events` table.
  - File upload validation: Enforces authentic MIME types (`image/jpeg`, `image/png`, `image/webp`), valid extensions, and 2MB file size cap.
  - Automatic replacement and safe unlinking of old poster files upon edit/delete.
  - **Intermediate Extension:** Filtering by Category (Workshop, Seminar, Competition, Exhibition) and Status (Open, Closed).
  - **Advanced Extension:** Randomized safe filename generation (`poster_TIMESTAMP_RANDOM.ext`), uploads directory script protection (`uploads/.htaccess`), and centralized audit log tracking.

### Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD (Zero Page Reload)
- **Files:** [`php/api_events.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/api_events.php), [`php/p13_ajax_crud.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p13_ajax_crud.php)
- **Features Implemented:**
  - RESTful JSON API endpoint supporting `GET`, `POST`, `PUT`, `DELETE`.
  - Frontend built entirely using JavaScript ES6 `fetch()` API (`async`/`await`) with zero full-page refreshes.
  - Modal form for creating and editing events with real-time DOM updates.
  - **Intermediate Extension:** Debounced search input (300ms delay) preventing excessive API calls.
  - **Advanced Extension:** Standard HTTP status codes (200, 201, 400, 404, 500), centralized JSON response formatting, live REST monitor, and auto-dismissing toast notifications.

### Practical 14: Admin Dashboard with RBAC, Dynamic Menu, and Audit Log
- **File:** [`php/p14_admin_dashboard.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p14_admin_dashboard.php)
- **Features Implemented:**
  - Access restricted strictly to admin users via `require_admin()`; unauthorized users redirected.
  - Aggregated statistical KPI cards (Total Students, Active Students, Campus Events, Open Events, Total Registrations, System Users).
  - Latest event enrollments table loaded dynamically via SQL JOIN.
  - Traceable Audit Logs table showing timestamp, user ID, action, details, and client IP.
  - **Intermediate Extension:** Interactive User Active/Inactive toggle button with instant database update.
  - **Advanced Extension:** Responsive monthly registration trend bar chart rendered using HTML5 Canvas.

### Practical 15: Final Integration, Testing, Documentation, and Local Deployment
- **Files:** [`php/p15_portal_home.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/p15_portal_home.php), [`php/test_suite.php`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/php/test_suite.php), [`docs/PRACTICALS_COMPLETION_REPORT.md`](file:///c:/Users/RUTVI/OneDrive/Desktop/Bhargavi/StudenHub/docs/PRACTICALS_COMPLETION_REPORT.md)
- **Features Implemented:**
  - Central portal homepage providing unified navigation across all 15 practicals.
  - Live system diagnostics and 1-click database reset & seeder link.
  - Automated diagnostic test suite running 20+ assertions across PHP version, PDO drivers, database tables, upload directory shields, and deliverables.
  - Complete, structured academic report and deployment documentation.

---

## 🚀 Local Deployment Guide (XAMPP / Apache)

### Step 1: Start XAMPP Services
1. Open **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 2: Access the Application
In your web browser, navigate to:
```
http://localhost/Bhargavi/StudenHub/php/p15_portal_home.php
```
*(Or the appropriate relative path corresponding to your `htdocs` directory configuration).*

### Step 3: Initialize Database (1-Click)
1. Open:
   ```
   http://localhost/Bhargavi/StudenHub/php/seed_database.php
   ```
2. The seeder script will automatically:
   - Create the `studenthub` database if missing.
   - Execute the complete schema (6 normalized tables).
   - Seed default courses, 21 student records, 6 events, sample registrations, and stored procedure `GetEventRegistrationStats`.

### Step 4: Run Diagnostic Verification
Navigate to:
```
http://localhost/Bhargavi/StudenHub/php/test_suite.php
```
Verify that all 20+ automated assertions pass successfully.
