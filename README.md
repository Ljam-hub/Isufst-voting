# Online Voting System (iVote)

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%20%7C%208.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](#license)

A modern, responsive, and secure web-based Student Council Election and Voting System built with native **PHP**, **MySQL (PDO)**, and **Bootstrap 5**. Designed for seamless deployment on both local environments (**XAMPP**) and free cloud hosting (**InfinityFree**).

---

## 📌 Features

### 🎓 For Students (Voters)
* **Self-Registration:** Quick registration with Student ID, course, year level, and encrypted credentials.
* **Platform & Candidate Explorer:** Review candidates, political parties, and campaign platforms before voting.
* **Smart Ballot Interface:** 
  * Single-seat positions (e.g., President) enforce single choice via radio buttons.
  * Multi-seat positions (e.g., Representatives) enforce selection quotas dynamically via checkboxes.
* **Strict Single-Vote Rule:** Database-level transaction locking (`FOR UPDATE`) guarantees voters can only cast their ballot once.
* **Digital Ballot Receipt:** Generates a verifiable digital receipt with a unique audit verification code upon ballot submission.

### 🛡️ For Election Administrators (COMELEC)
* **Real-Time Dashboard:** Overview of total registered voters, ballots cast, active positions, and live turnout percentage.
* **Poll Controls:** 1-click toggle to open, pause, or close voting across the entire system.
* **Full CRUD Management:**
  * **Positions:** Add, edit, prioritize, and delete electoral positions.
  * **Candidates:** Add and update candidate profiles with platforms and photo uploads.
  * **Voters:** Filter voters by course or status, update records, and reset individual test votes.
* **Live Results & Canvass:** Real-time percentage bars, candidate rankings, leading indicators, and an official printable **Certificate of Canvass**.

---

## 🔑 Default Login Credentials

### Administrator
* **URL:** `admin_login.php`
* **Username:** `admin`
* **Password:** `admin123`

### Sample Student Voters
* **URL:** `login.php`
* **Student ID:** `2026-00101` | **Password:** `voter123`
* **Student ID:** `2026-00102` | **Password:** `voter123`
*(New voters can also register via `register.php`)*

---

## 🚀 Quick Setup Guide

### Local Setup (XAMPP)
1. Clone or download this repository into your XAMPP `htdocs` directory:
   ```bash
   git clone https://github.com/Ljam-hub/Isufst-voting.git
   ```
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open phpMyAdmin at [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Create a database named `online_voting_db` and import `database.sql`.
5. Access the application in your browser:
   * **Voter Portal:** [http://localhost/Isufst-voting/](http://localhost/Isufst-voting/)
   * **Admin Portal:** [http://localhost/Isufst-voting/admin_login.php](http://localhost/Isufst-voting/admin_login.php)

---

### Cloud Setup (InfinityFree)
1. Create a MySQL database in your InfinityFree control panel.
2. In InfinityFree phpMyAdmin, select your assigned database on the left sidebar and import `database.sql`.
3. In `db_connect.php`, update lines 54–57 with your InfinityFree MySQL credentials:
   ```php
   $db_host = 'sqlXXX.infinityfree.com';
   $db_user = 'if0_XXXXXXXX';
   $db_pass = 'YOUR_PASSWORD';
   $db_name = 'if0_XXXXXXXX_voting';
   ```
4. Upload all project files into the remote `htdocs/` folder via FileZilla (FTP) or the Online File Manager.

---

## 📁 Project Structure

```
├── admin/                  # Administrative management panel
│   ├── auth_check.php      # Admin session security middleware
│   ├── candidates.php      # Candidate CRUD & photo uploads
│   ├── index.php           # Admin dashboard & poll toggle
│   ├── logout.php          # Admin logout handler
│   ├── positions.php       # Position CRUD & quota configuration
│   ├── print_results.php   # Printable official Certificate of Canvass
│   ├── results.php         # Live tally & emergency ballot reset
│   ├── settings.php        # System metadata & admin password manager
│   └── voters.php          # Voter registry, filtering, & vote resets
├── assets/                 # Custom CSS, JS scripts, and candidate images
├── includes/               # Reusable components
│   ├── alerts.php          # Bootstrap 5 dismissible flash alerts
│   ├── crud_functions.php  # Centralized PDO CRUD functions library
│   ├── footer.php          # Global footer and scripts
│   ├── header.php          # HTML head, Bootstrap 5 CDN & Font Awesome
│   └── navbar.php          # Dynamic, role-aware navigation bar
├── ballot.php              # Official student voting ballot
├── candidates.php          # Public candidate platform gallery & search
├── database.sql            # Complete database schema and seed records
├── db_connect.php          # Dual-environment PDO connection manager
├── index.php               # Homepage with election overview and live counters
├── login.php               # Student voter login
├── logout.php              # Voter logout handler
├── register.php            # Voter self-registration portal
├── results.php             # Real-time public election results
├── vote_submit.php         # Transaction-secured ballot verification & storage
└── vote_success.php        # Digital ballot receipt & audit code display
```

---

## 🛡️ Security Features
* **SQL Injection Prevention:** 100% of database queries execute through PDO Prepared Statements with bound parameters.
* **Password Hashing:** Passwords are encrypted using PHP's native `password_hash()` with the Bcrypt algorithm (`PASSWORD_DEFAULT`).
* **Double-Voting Prevention:** Database transactions with row-level locks (`SELECT ... FOR UPDATE`) stop race conditions and duplicate voting attempts.
* **XSS Protection:** Dynamic user content is sanitized and escaped with `htmlspecialchars()` before output.
* **Session Protection:** Role separation between voter and administrator sessions with dedicated authorization guards.

---

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).
