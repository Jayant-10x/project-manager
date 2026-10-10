# 10X Agency — Project Coordinator CRM (`PJT CRM`)

<p align="center">
  <img src="public/assets/logo.png" alt="10X Agency Logo" width="180">
  <br>
  <strong>Unified Project Operations, Milestone Tracking & Financial Receivables CRM</strong>
  <br>
  <em>Engineered for agency coordinators, delivery leads, and finance directors.</em>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8.0+">
  <img src="https://img.shields.io/badge/Frontend-Vanilla%20JS%20SPA-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="Vanilla JS">
  <img src="https://img.shields.io/badge/License-Proprietary-0B3D91?style=for-the-badge" alt="Proprietary License">
</p>

---

## Table of Contents
- [Overview](#overview)
- [Key Features](#key-features)
- [System Architecture](#system-architecture)
- [Directory Layout](#directory-layout)
- [Quickstart & Local Installation](#quickstart--local-installation)
- [Default Seed Credentials](#default-seed-credentials)
- [API Architecture & Dispatching](#api-architecture--dispatching)
- [Financial Engine & Core Rules](#financial-engine--core-rules)
- [Production Deployment & Server Config](#production-deployment--server-config)
- [Troubleshooting & Runbook](#troubleshooting--runbook)
- [License](#license)

---

## Overview

**PJT CRM** is a single-tenant operations and financial management platform tailored specifically for digital services agencies[cite: 2, 4]. It centralizes client onboarding, project health monitoring, milestone delivery, installment receivables, and client follow-ups into a high-performance single-page application[cite: 2, 4].

The backend has been migrated from Core PHP into **Laravel** following strict PSR-12 and Clean Architecture principles, backed by a dedicated Service Layer that prevents logic duplication between views, API consumers, and automated jobs[cite: 2, 3].

---

## Key Features

### 📊 Executive Dashboard & Real-Time KPIs
* **Receivables Overview:** Real-time tracking of scheduled project value, collections, TDS deductions, and net pending balances[cite: 2, 4].
* **Aging Buckets:** Automatic categorization of overdue payments into 0–30, 31–60, 61–90, and 90+ day intervals[cite: 2, 4].
* **Collection Trend Analysis:** Trailing 6-month visual revenue performance breakdown[cite: 2, 4].
* **Today's Work Queue:** Direct surface of overdue installments, pending client follow-ups, at-risk deadlines, and open tasks[cite: 2, 4].

### 📁 Customer Lifecycle & Onboarding
* **Client Dossiers:** Centralized company profiles with tax credentials (GSTIN/PAN), multi-address billing records, and industry tags[cite: 2, 4].
* **Sales Attribution:** Track which salesperson onboarded each client and monitor revenue vs. individual monthly targets[cite: 2, 4].
* **Bulk CSV Onboarding:** Built-in spreadsheet parser supporting bulk client insertion with automatic code generation[cite: 2, 4].

### 🚀 Project Delivery & AMC Tracking
* **Health Monitoring:** Discrete delivery confidence states (`On Track`, `At Risk`, `Delayed`, `Completed`) with mandatory delay reasoning[cite: 2, 4].
* **Delivery Checkpoints:** Interactive milestone timelines linked directly to payment triggers[cite: 2, 4].
* **AMC Management:** Dedicated Annual Maintenance Contract tracking with configurable alert lead times[cite: 2, 4].
* **Handover Governance:** Formal post-launch sign-off and delivery note records[cite: 2, 4].

### 💳 Milestone & Recurring Financial Engine
* **Flexible Installment Scheduling:**
    * **Fixed Date:** Scheduled against explicit milestone calendar dates[cite: 2, 4].
    * **Milestone Linked:** Triggered automatically upon completion of delivery milestones[cite: 2, 4].
    * **Monthly Recurring:** Single-click generator for retainers and multi-month service plans[cite: 2, 4].
* **Automated Status Recalibration:** Terms transition between `Pending`, `Partial`, `Paid`, and `Overdue` based on actual receipts, TDS deductions, and grace periods[cite: 2].
* **Dual Currency Display:** Live in-memory toggle between INR (`₹`) and USD (`$`) without mutating database values[cite: 4].

### 🔐 Security & Audit Trails
* **Lockout Protection:** Automatically locks accounts for 30 minutes after 5 consecutive failed login attempts[cite: 2, 3].
* **Role-Based Authorization:** Separate permission boundaries for Administrators and Project Coordinators[cite: 2].
* **Tamper-Evident Audit Logs:** Append-only activity ledger recording every entity mutation with before/after state diffs[cite: 2].

---

## System Architecture

```
┌────────────────────────────────────────────────────────┐
│               Browser Frontend (SPA)                   │
│   Vanilla JS (crm-app.js) · Responsive CSS Variables   │
└───────────────────────────┬────────────────────────────┘
                            │
               HTTP POST /api.php (Legacy)
               HTTP /api/v1/*     (RESTful)
                            │
┌───────────────────────────▼────────────────────────────┐
│                  Laravel HTTP Kernel                   │
│       CrmTokenAuth Middleware · CSRF Exception         │
└─────────────┬────────────────────────────┬─────────────┘
              │                            │
┌─────────────▼──────────────┐ ┌───────────▼─────────────┐
│   LegacyGatewayController  │ │ REST Domain Controllers │
└─────────────┬──────────────┘ └───────────┬─────────────┘
              │                            │
              └─────────────┬──────────────┘
                            │
┌───────────────────────────▼────────────────────────────┐
│                  Domain Services Layer                 │
│  • MoneyCalculationService   • CodeGeneratorService    │
│  • DocumentService           • AuditNotificationService│
│  • ReportExportService                                 │
└─────────────┬────────────────────────────┬─────────────┘
              │                            │
┌─────────────▼──────────────┐ ┌───────────▼─────────────┐
│     Eloquent ORM Layer     │ │ Storage Engine (Public) │
│       MySQL Database       │ │ Symbolic Link Storage   │
└────────────────────────────┘ └─────────────────────────┘
```

---

## Directory Layout

```text
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php          # Session, login/logout, password resets[cite: 2]
│   │   │   │   ├── CustomerController.php      # Customer lifecycle & bulk import[cite: 2]
│   │   │   │   ├── DashboardController.php     # Load bootstrapping & analytics aggregation[cite: 2]
│   │   │   │   ├── DocumentController.php      # Base64 binary ingest & deletion[cite: 2]
│   │   │   │   ├── ExportController.php        # Streamed CSV exports[cite: 2]
│   │   │   │   ├── FollowupController.php      # Client interaction records[cite: 2]
│   │   │   │   ├── LegacyGatewayController.php # Dispatches /api.php action requests[cite: 2]
│   │   │   │   ├── MilestoneController.php     # Project delivery milestones[cite: 2]
│   │   │   │   ├── NotificationController.php  # Staff inbox notifications[cite: 2]
│   │   │   │   ├── PaymentController.php       # Receipts, terms, installment scheduling[cite: 2]
│   │   │   │   ├── ProjectController.php       # Project CRUD, health states, handovers[cite: 2]
│   │   │   │   ├── SalesPersonController.php   # Sales team performance registry[cite: 2]
│   │   │   │   ├── SettingController.php       # Global alert thresholds & audit feed[cite: 2]
│   │   │   │   ├── TaskController.php          # Coordinator personal checklists[cite: 2]
│   │   │   │   └── UserController.php          # User administration (Admin only)[cite: 2]
│   │   │   └── Web/
│   │   │       └── AppViewController.php       # Serves single-page Blade shell[cite: 4]
│   │   └── Middleware/
│   │       └── CrmTokenAuth.php                # Token authorization layer[cite: 2]
│   ├── Models/                                 # 14 Eloquent Models
│   └── Services/
│       ├── AuditNotificationService.php        # Safe audit writes & notifications[cite: 2]
│       ├── CodeGeneratorService.php            # Concurrency-safe prefix counter[cite: 2]
│       ├── DocumentService.php                 # Filesystem base64 decoder & storage[cite: 2]
│       ├── MoneyCalculationService.php         # Arithmetic engine & aging formulas[cite: 2]
│       └── ReportExportService.php             # Streaming CSV exports with UTF-8 BOM[cite: 2]
├── config/
│   ├── crm.php                                 # System-wide operational constants[cite: 3]
│   └── filesystems.php
├── database/
│   ├── migrations/                             # 14 structured table migrations
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── UserSeeder.php                      # Seeds default Admin & Coordinator
├── public/
│   ├── assets/logo.png                         # Static brand identity asset[cite: 1]
│   ├── css/crm-style.css                       # Complete CSS layout & design palette[cite: 4]
│   └── js/crm-app.js                           # SPA client state store & UI renderer[cite: 4]
├── resources/views/
│   └── app.blade.php                           # Root SPA container template[cite: 4]
└── routes/
    ├── api.php                                 # RESTful v1 endpoints
    └── web.php                                 # Root route & legacy /api.php endpoint[cite: 2]
```

---

## Quickstart & Local Installation

Follow these steps to set up the project locally:

### 1. Prerequisites
* **PHP:** `^8.2` with `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `tokenizer`, `xml` extensions
* **Database:** MySQL `8.0+` or MariaDB `10.4+`
* **Composer:** `v2.x`

### 2. Clone and Install Dependencies
```bash
git clone <repository-url> pjt-crm
cd pjt-crm

composer install
```

### 3. Environment Setup
Copy the example environment configuration and generate the application encryption key:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Configuration
Open `.env` and enter your database connection details:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

### 5. Run Migrations & Seed Users
Run the migrations to create all 14 database tables, then seed the default accounts:
```bash
php artisan migrate
php artisan db:seed --class=UserSeeder
```

### 6. Create Public Storage Symlink
Link the `storage/app/public` directory to `public/storage` for document uploads:
```bash
php artisan storage:link
```

### 7. Run the Application
Start the local PHP development server:
```bash
php artisan serve
```

Visit **`http://127.0.0.1:8000`** in your browser.

---

## Default Seed Credentials

After running `UserSeeder`, the following accounts are available[cite: 2]:

| Role | Email | Password | Privileges |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@the10xagency.in` | `Sample@1123` | Full Access: User Administration, System Settings, Customer Deletion[cite: 2] |
| **Coordinator** | `coordinator@the10xagency.in` | `Sample@1123` | Operations Access: Customers, Projects, Milestones, Payments, Uploads[cite: 2] |

> **Note:** Change these passwords immediately before deploying to a public-facing server[cite: 2].

---

## API Architecture & Dispatching

The CRM supports two entry points to the same underlying service layer:

### 1. Legacy Gateway (`POST /api.php`)
Used by the frontend SPA. Requests pass an action identifier and authentication token[cite: 2]:
```json
{
  "action": "savePayment",
  "token": "48_character_hex_session_token",
  "data": {
    "projectId": 12,
    "termId": 34,
    "amount": 25000,
    "tds": 500,
    "paidOn": "2026-10-10",
    "mode": "NEFT",
    "reference": "UTR99481230",
    "bank": "HDFC Bank"
  }
}
```

### 2. RESTful V1 (`POST /api/v1/*`)
Standard endpoints for integrations. Send the token via the `X-CRM-Token` header[cite: 2]:
```bash
curl -X POST [http://127.0.0.1:8000/api/v1/payments](http://127.0.0.1:8000/api/v1/payments) \
  -H "Content-Type: application/json" \
  -H "X-CRM-Token: your_session_token_here" \
  -d '{"projectId": 12, "amount": 25000, "paidOn": "2026-10-10", "mode": "NEFT"}'
```

### Standard Response Envelopes
* **Success:**
  ```json
  {
    "ok": true,
    "id": 105,
    "code": "RCP-0042",
    "money": { "scheduled": 100000, "received": 25000, "tds": 500, "due": 74500 }
  }
  ```
* **Failure:**
  ```json
  {
    "ok": false,
    "error": "The installment does not belong to the selected project."
  }
  ```

---

## Financial Engine & Core Rules

### Calculations (`MoneyCalculationService`)
* **Scheduled Total:**
  $$\text{Scheduled} = \sum_{\text{status} \neq \text{'Cancelled'}} \text{payment\_terms.amount}$$
  [cite: 2]
* **Collections & Tax:**
  $$\text{Received} = \sum \text{payments.amount}, \quad \text{TDS} = \sum \text{payments.tds}$$
  [cite: 2]
* **Outstanding Balance:**
  $$\text{Due} = \max(0, \; \text{Scheduled} - \text{Received} - \text{TDS})$$
  [cite: 2]

### Installment Status Progression
1. **`Cancelled`**: Manually marked as cancelled[cite: 2].
2. **`Paid`**: Cash received plus TDS equals or exceeds the installment amount ($(\text{Received} + \text{TDS}) \ge \text{Amount} - 0.01$)[cite: 2].
3. **`Partial`**: Any positive receipt has been logged, but the full amount is not yet cleared[cite: 2].
4. **`Overdue`**: Today's date exceeds $\text{Due Date} + \text{Grace Days}$ with an outstanding balance[cite: 2].
5. **`Pending`**: Future-dated installment with zero payments logged[cite: 2].

---

## Production Deployment & Server Config

### Optimization Commands
Before going live, compile and cache all routes, configurations, and templates:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Nginx Virtual Host Configuration
```nginx
server {
    listen 80;
    server_name project.the10xagency.in;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name project.the10xagency.in;
    root /var/www/pjt-crm/public;

    ssl_certificate /etc/letsencrypt/live/project.the10xagency.in/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/project.the10xagency.in/privkey.pem;

    client_max_body_size 15M;
    index index.php;
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "same-origin";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Pass legacy /api.php requests to Laravel index.php
    location = /api.php {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Troubleshooting & Runbook

### 1. Stuck on "Loading CRM..."
* **Cause:** An invalid or expired token is stored in the browser's `localStorage`[cite: 2].
* **Fix:** Open browser DevTools (`F12`), switch to the **Console** tab, run:
  ```javascript
  localStorage.clear();
  location.reload();
  ```

### 2. 404 Not Found on `/api.php`
* **Cause:** The legacy dispatch route was defined in `routes/api.php` (which prefixes routes with `/api`) instead of `routes/web.php`[cite: 2].
* **Fix:** Ensure the route is defined in `routes/web.php`:
  ```php
  Route::match(['GET', 'POST'], '/api.php', [LegacyGatewayController::class, 'dispatch'])
      ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
  ```

### 3. File Uploads Fail (HTTP 500 or Size Error)
* **Cause:** PHP's `upload_max_filesize` or `post_max_size` is lower than the CRM's 10 MB limit[cite: 3].
* **Fix:** In your `php.ini`, set:
  ```ini
  upload_max_filesize = 15M
  post_max_size = 15M
  ```
  Then restart PHP-FPM (`sudo systemctl restart php8.2-fpm`).

### 4. Storage Assets Return 404
* **Cause:** The public storage symbolic link is broken or missing.
* **Fix:** Run:
  ```bash
  rm public/storage
  php artisan storage:link
  ```

---

## License

This project is proprietary software belonging to **10X Agency**. Unauthorized copying, distribution, or modification of these files is strictly prohibited.
