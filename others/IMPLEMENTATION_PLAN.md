# Role & Objective
Act as an expert Full-Stack PHP Developer and UI/UX Engineer. Your task is to build a Clinic Management System (CMS) based on the exact specifications provided below. 

You must adhere strictly to the technology stack, security constraints, and architectural guidelines. Do not hallucinate external libraries or use deprecated PHP functions.

---

## 1. Project Overview & Tech Stack
* **Project Name:** Clinic Management System (CMS)
* **Backend:** Core PHP (8.x+) using PDO for secure database interactions.
* **Database:** MySQL (Strictly normalized relational schema).
* **Frontend:** HTML5, Tailwind CSS (via CDN or precompiled), and Vanilla JavaScript (ES6+).
* **Architecture:** Hierarchical API structure separating frontend views from backend logic.

---

## 2. UI/UX & Design Architecture constraints
* **Visuals:** Clean, modern, and highly intuitive. Use Tailwind's utility classes for soft gradients (e.g., `bg-gradient-to-r from-gray-50 to-gray-100`) and precise spacing to deliver a high-end look.
* **Assets:** * **Strictly Prohibited:** Heavy external icon libraries (e.g., FontAwesome) and generic AI-generated icons.
  * **Allowed:** Exclusively use inline SVG icons styled via Tailwind classes (e.g., `w-5 h-5 text-gray-500`). AI-generated placeholder images are permitted for structural layout testing only.
* **Interactivity:** * Use Tailwind’s transition/pseudo-class utilities (`hover:bg-blue-50`, `transition-all`, `duration-300`) for fluid state changes.
  * Use Vanilla JS for custom DOM manipulation (modals, dropdowns). 
  * **No Page Reloads:** All forms, dashboards, and data tables MUST submit and refresh asynchronously using the JS Fetch API.

---

## 3. Role-Based Access Control (RBAC)
Enforce strict principle-of-least-privilege access:

* **Admin:** Read-only access to all entities (auditing). CRUD for system staff accounts. Full access to settings, analytics, and automated email engine.
* **Doctor:** Read-only access to Patient demographics (cannot edit/delete). Create/Update medical records, clinical notes, and prescriptions. View appointment queue. Order lab tests.
* **Patient:** Create, View, and Cancel (not delete) personal appointments. View doctor profiles and leave reviews.
* **Receptionist:** Register new patients and edit demographics. Full CRUD over the appointment schedule and live physical queue. Push queue alerts to doctors.
* **Lab Technician:** View test orders. Upload lab report files and update status. Cannot delete test requests. (System auto-attaches uploaded reports to patient records).
* **Pharmacist:** View-only access strictly to the prescription/medication list (no access to clinical notes). Calculate totals and process billing.

---

## 4. Normalized Database Schema
Implement the following structure precisely to avoid messy joins:

* `users` (id, email, password_hash, role, is_email_verified, created_at)
* `patient_profiles` (id, user_id, full_name, dob, gender, phone, created_at)
* `staff_profiles` (id, user_id, name, role_title)
* `doctors` (id, user_id, specialization, license_number)
* `medicines` (id, name, generic_name, price, stock_quantity)
* `appointments` (id, patient_id, doctor_id, appointment_date, start_time, end_time, status, created_at)
* `medical_records` (id, patient_id, doctor_id, diagnosis, clinical_notes, created_at)
* `prescriptions` (id, record_id, created_at)
* `prescription_items` (id, prescription_id, medicine_id, dosage, frequency)
* `lab_tests` (id, patient_id, doctor_id, test_type, status, report_file_path, report_notes, created_at)
* `invoices` (id, patient_id, reference_type [prescription, consultation, lab], reference_id, amount, status, created_at)

---

## 5. Security & Technical Architecture Guidelines
* **API Routing:** No flat files. Route hierarchically (e.g., `/api/auth/login.php`, `/api/appointments/create.php`).
* **Database:** PDO Prepared Statements are MANDATORY. No raw variables in SQL queries.
* **Sessions:** Trigger `session_regenerate_id(true)` upon login. Use `SameSite=Strict`, `HttpOnly`, and `Secure` cookie parameters.
* **CSRF:** Every POST/PUT/DELETE request must validate a CSRF token.
* **Validation:** Strict server-side validation is mandatory. Never trust frontend validation.

---

## INITIAL TASK:
Do not generate the entire application at once. For your first response, please provide ONLY the following:

1. A standard directory structure for the project (separating frontend views, API endpoints, config, and assets).
2. The complete `init.sql` script to generate the normalized database schema defined in section 4, including necessary foreign key constraints.
3. The core `db_connect.php` file using secure PDO configurations.