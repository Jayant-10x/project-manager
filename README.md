# 10X Agency — Project Coordinator CRM (`PJT CRM`)

<p align="center">
  <img src="public/assets/logo.png" alt="10X Agency Logo" width="180">
  <br>
  <strong>Unified Project Operations, Milestone Tracking & Financial Receivables CRM</strong>
  <br>
  <em>Engineered for agency coordinators, delivery leads, and finance directors.</em>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Livewire-4.x-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire 4">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8.0+">
  <img src="https://img.shields.io/badge/Frontend-Alpine.js%20%26%20Tailwind-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
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
- [Livewire 4 Component Matrix](#livewire-4-component-matrix)
- [Financial Engine & Calculations](#financial-engine--calculations)
- [Production Deployment & Server Config](#production-deployment--server-config)
- [Troubleshooting & Runbook](#troubleshooting--runbook)
- [License](#license)

---

## Overview

**PJT CRM** is a single-tenant operations and financial management platform designed specifically for digital services
agencies[cite: 44]. It centralizes client onboarding, project health monitoring, milestone delivery, installment
receivables, task checklists, and client follow-ups into a high-performance reactive full-stack web
application[cite: 44].

The platform is built on **Laravel 13** and **Livewire 4**, providing SPA reactivity through `wire:navigate`, unified
Alpine.js stores, and a dedicated Service Layer that enforces financial consistency, concurrency-safe code generation,
and coordinator-scoped data security[cite: 43, 44].

---

## Key Features

### 📊 1. Executive Dashboard & Real-Time KPIs

* **Receivables Strip:** Real-time metrics for Total Scheduled Value, Collected Cash, Net Due, Overdue Installments, and
  Current Month Collections[cite: 43, 44].
* **Aging Buckets:** Automatic breakdown of delayed receivables into `0–30`, `31–60`, `61–90`, and `90+` day
  intervals[cite: 43, 44].
* **Collection Trend Analysis:** Trailing 6-month visual revenue performance sparkline[cite: 43, 44].
* **Daily Triage Queue:** Direct access to overdue payments, approaching client follow-ups, and at-risk project
  deadlines[cite: 43, 44].
* **Commercial Performance:** Sales-wise contracted volume, collected revenue, and collection progress bars[cite: 43].
* **Operational Alerts:** Proactive tracking of upcoming Annual Maintenance Contract (AMC) expirations and pending
  project handovers[cite: 43, 44].

### 🎯 2. Today's Work Queue

* **Daily Action Command Center:** Prioritized view of client follow-ups due today, overdue installments requiring
  collection, and deliverable risks[cite: 43, 44].
* **Interactive Triage:** Quick links to customer details and direct payment recording buttons[cite: 43].

### 📁 3. Customer Lifecycle & Bulk Onboarding

* **Comprehensive Client Dossiers:** Centralized company profiles with tax IDs (GSTIN/PAN), multi-address billing
  records, and industry tags[cite: 43, 44].
* **Sales Attribution:** Track which salesperson onboarded each client and monitor revenue vs. individual monthly
  targets[cite: 43, 44].
* **Bulk CSV Import:** Built-in spreadsheet parser supporting bulk client insertion with automatic code generation (
  `CUS-XXXX`)[cite: 43, 44].
* **Export Engine:** Filtered, UTF-8 BOM-encoded CSV data exports compatible with Microsoft Excel[cite: 43].

### 🚀 4. Project Delivery, Milestones & AMC Tracking

* **Delivery Health States:** Discrete confidence states (`On Track`, `At Risk`, `Delayed`, `Completed`) with mandatory
  delay reasoning[cite: 43, 44].
* **Interactive Milestones:** Milestone tracking with completion dates, sort ordering, and payment trigger
  linking[cite: 43, 44].
* **Annual Maintenance Contracts (AMC):** Dedicated maintenance contract tracking with configurable alert lead
  times[cite: 43, 44].
* **Handover Governance:** Post-delivery checklist and formal client handover documentation[cite: 43, 44].

### 💳 5. Financial Receivables & Installment Engine

* **Multi-Mode Installment Scheduling:**
    * **Fixed Date:** Scheduled against explicit calendar milestone dates[cite: 43, 44].
    * **Milestone Linked:** Triggered automatically upon completion of delivery milestones[cite: 43, 44].
    * **Monthly Recurring:** Single-click generator for retainers and multi-month service plans[cite: 43, 44].
* **Automated Status Transitions:** Installments automatically transition between `Pending`, `Partial`, `Paid`, and
  `Overdue` based on actual receipts, TDS deductions, and grace periods[cite: 43, 44].
* **Dual Currency Display:** Live in-memory toggle between INR (`₹`) and USD (`$`) via an Alpine store without altering
  stored database values[cite: 43, 44].

### 📋 6. Tasks, Follow-ups, Documents & Audit

* **Personal Checklist:** Coordinator task management with priority levels (`Low`, `Medium`, `High`, `Urgent`) and
  overdue indicators[cite: 43].
* **Touchpoint Logger:** Multi-channel client interaction logging (`Call`, `Email`, `Meeting`, `WhatsApp`, `Note`) with
  next action alarms[cite: 43].
* **Document Management:** Secure storage of contracts, KYC documents, and delivery proofs up to 10 MB with in-browser
  PDF/image lightbox previews[cite: 43].
* **Activity Audit Trail:** Immutable log of system mutations (creates, updates, payments, uploads, and
  deletions)[cite: 43, 44].

---

## System Architecture

```mermaid
graph TD
    subgraph Client Layer [Frontend Client (SPA)]
        UI["Livewire 4 Components & Blade Templates"]
        Alpine["Alpine.js Stores ($store.currency, Sidebar State)"]
        LocalStorage["LocalStorage (pjt_cur, pjt_sidebar_collapsed)"]
        UI <--> Alpine
        Alpine <--> LocalStorage
    end

    subgraph HTTP Routing & Middleware Layer
        Routes["routes/web.php (wire:navigate Enabled)"]
        AuthMiddleware["Middleware: auth, verified"]
        RoleScope["Eloquent Query Scope: scopeForUser()"]
        Routes --> AuthMiddleware --> RoleScope
    end

    subgraph Livewire 4 Component Registry
        RoleScope --> M1["Overview: DashboardIndex, TodayWorkIndex"]
        RoleScope --> M2["Customers: CustomerIndex, CustomerDetail, CustomerModal, CustomerImportModal"]
        RoleScope --> M3["Projects: ProjectIndex, ProjectDetail, ProjectModal, ScheduleModal, MilestoneModal"]
        RoleScope --> M4["Money: PaymentIndex, PaymentModal, ScheduleIndex, ReportIndex"]
        RoleScope --> M5["Records: DocumentIndex, DocumentUploadModal, DocumentPreviewModal, FollowupIndex, SalesIndex"]
        RoleScope --> M6["System: NotificationIndex, NotificationBell, AuditIndex, SettingIndex, UserModal"]
    end

    subgraph Domain Services Layer
        MoneyService["MoneyCalculationService (Financial Math & Auto-Overdue)"]
        CodeService["CodeGeneratorService (Atomic Row-Locking Sequence)"]
        CurrencyService["CurrencyRateService (USD/INR Exchange Rates)"]
        
        M1 & M2 & M3 & M4 --> MoneyService
        M2 & M3 & M4 --> CodeService
        M1 & M4 --> CurrencyService
    end

    subgraph Data & Storage Layer
        Eloquent["Eloquent ORM Models"]
        MySQL[("MySQL 8.0+ Database")]
        DiskStorage[("Public Disk Storage (storage/app/public)")]

        MoneyService & CodeService --> Eloquent
        Eloquent --> MySQL
        M5 --> DiskStorage
    end
```

---

## Directory Layout

```text
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── ProfileController.php           # User profile editing and password updates
│   ├── Livewire/
│   │   ├── Audit/
│   │   │   └── AuditIndex.php                  # System audit log timeline viewer
│   │   ├── Components/
│   │   │   ├── GlobalSearch.php                # Universal debounced search dropdown
│   │   │   └── NotificationBell.php            # Topbar unread count polling badge
│   │   ├── Customers/
│   │   │   ├── CustomerDetail.php              # Tabbed customer profile (Overview, Projects, Payments, Docs, Followups)
│   │   │   ├── CustomerImportModal.php         # Bulk CSV upload and spreadsheet paste modal
│   │   │   ├── CustomerIndex.php               # Customer directory, filtering, pagination, and CSV export
│   │   │   └── CustomerModal.php               # Unified create and edit modal for customers
│   │   ├── Dashboard/
│   │   │   └── DashboardIndex.php              # Executive KPI cards, health breakdown, triage, and trend analytics
│   │   ├── Documents/
│   │   │   ├── DocumentIndex.php               # Global document repository with category filtering and deletion
│   │   │   ├── DocumentPreviewModal.php        # Lightbox preview modal for PDFs and images
│   │   │   └── DocumentUploadModal.php         # Multi-entity file upload modal with validation
│   │   ├── Followups/
│   │   │   └── FollowupIndex.php               # Touchpoint feed, overdue alert banner, and interaction logger
│   │   ├── Notifications/
│   │   │   └── NotificationIndex.php           # Notification inbox with deep linking and mark-as-read actions
│   │   ├── Payments/
│   │   │   ├── PaymentIndex.php                # Receipt ledger, mode filtering, balance tracking, and CSV export
│   │   │   └── PaymentModal.php                # Receipt recorder with auto-balance calculation and term binding
│   │   ├── Projects/
│   │   │   ├── ProjectDetail.php               # Project delivery matrix, timeline, schedule, and document tabs
│   │   │   ├── ProjectIndex.php                # Work orders directory, health filtering, and overdue rollups
│   │   │   ├── ProjectMilestoneModal.php       # Dynamic milestone ordering and checklist manager
│   │   │   ├── ProjectModal.php                # Project contract creator/editor with AMC and handover fields
│   │   │   ├── ProjectRecurringModal.php       # Monthly retainer recurring installment generator
│   │   │   └── ProjectScheduleModal.php        # Installment scheduler (fixed, milestone-linked, recurring)
│   │   ├── Reports/
│   │   │   └── ReportIndex.php                 # Collection rate, monthly sparkline, mode bars, aging, and variance
│   │   ├── Sales/
│   │   │   ├── SalesIndex.php                  # Salesperson revenue attribution, targets, and comparative table
│   │   │   └── SalesModal.php                  # Sales representative registration and quota manager
│   │   ├── Schedule/
│   │   │   └── ScheduleIndex.php               # Master chronological installment timeline and CSV export
│   │   ├── Settings/
│   │   │   ├── SettingIndex.php                # Operational threshold configuration, password manager, and user list
│   │   │   └── UserModal.php                   # Staff account creator/editor with temporary password generator
│   │   ├── Tasks/
│   │   │   └── TaskIndex.php                   # Coordinator checklist manager with priority flags and late indicators
│   │   └── Today/
│   │       └── TodayWorkIndex.php              # Daily operational triage for overdue payments, calls, and risks
│   ├── Models/                                 # 14 Eloquent Models with relationships & casts
│   └── Services/
│       ├── CodeGeneratorService.php            # Concurrency-safe atomic counter sequence generator[cite: 44]
│       ├── CurrencyRateService.php             # Exchange rate configuration and conversion service
│       └── MoneyCalculationService.php         # Central financial calculation engine[cite: 44]
├── database/
│   ├── migrations/                             # 14 structured table migrations[cite: 44]
│   └── seeders/
│       ├── DatabaseSeeder.php[cite: 44]
│       └── UserSeeder.php                      # Seeds default Administrator and Coordinator[cite: 44]
├── public/
│   ├── assets/logo.png                         # Static brand identity asset[cite: 44]
│   └── css/crm-style.css                       # Complete CSS layout & design palette[cite: 44]
├── resources/
│   └── views/
│       ├── components/                         # Blade UI components (money, kpi-card, health-badge, modal)
│       ├── layouts/
│       │   ├── app.blade.php                   # Master responsive application layout
│       │   └── partials/sidebar.blade.php      # 14-module navigation sidebar with live counters
│       └── livewire/                           # Paired Blade templates for all Livewire components
└── routes/
    ├── auth.php                                # Breeze authentication routes
    └── web.php                                 # Authenticated routes mapping all 14 Livewire modules
```

---

## Quickstart & Local Installation

### 1. Prerequisites

* **PHP:** `^8.2` with `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `tokenizer`, `xml` extensions[cite: 44]
* **Database:** MySQL `8.0+` or MariaDB `10.4+`[cite: 44]
* **Composer:** `v2.x`[cite: 44]
* **Node.js:** `v18.x` or higher (with NPM)

### 2. Clone and Install Dependencies

```bash
git clone <repository-url> pjt-crm
cd pjt-crm

composer install
npm install
```

### 3. Environment Setup

Create the environment configuration file and generate the application encryption key:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Configuration

Open `.env` and configure your database connection parameters:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pjt_crm
DB_USERNAME=root
DB_PASSWORD=your_secure_password
```

### 5. Run Migrations & Seed Default Accounts

Execute all database migrations and seed the initial administrative accounts:

```bash
php artisan migrate:fresh
php artisan db:seed --class=UserSeeder
```

### 6. Establish Public Storage Symlink

Create the public storage symlink for uploaded contracts and documents:

```bash
php artisan storage:link
```

### 7. Run Local Development Servers

Start the Laravel development server:

```bash
php artisan serve
```

In a separate terminal window, start the Vite asset compiler:

```bash
npm run dev
```

Navigate to **`http://127.0.0.1:8000`** in your browser.

---

## Default Seed Credentials

Following database seeding, log in using either of the following accounts:

| Role                      | Email                                   | Default Password        | Permissions Scope                                                                           |
|:--------------------------|:----------------------------------------|:------------------------|:--------------------------------------------------------------------------------------------|
| **Admin**[cite: 44]       | `admin@the10xagency.in`[cite: 44]       | `Sample@1123`[cite: 44] | Unrestricted access: User Administration, System Settings, Customer Deletion[cite: 44]      |
| **Coordinator**[cite: 44] | `coordinator@the10xagency.in`[cite: 44] | `Sample@1123`[cite: 44] | Account-scoped access: Customers, Assigned Projects, Payments, Uploads, Tasks[cite: 43, 44] |

> **Security Notice:** Update default passwords immediately via **Settings (`/settings`)** upon initial deployment.

---

## Livewire 4 Component Matrix

All 14 operational modules are built as dedicated Livewire 4 full-page components and modals:

| Module Group           | Module Name                      | Route Name            | Livewire Class                    | Key Sub-Components & Modals                                                                                        |
|:-----------------------|:---------------------------------|:----------------------|:----------------------------------|:-------------------------------------------------------------------------------------------------------------------|
| **Overview**[cite: 3]  | **1. Dashboard**[cite: 3]        | `dashboard`           | `Dashboard\DashboardIndex`        | `x-kpi-card`, `x-health-badge`, `spark` bar chart[cite: 3, 5]                                                      |
|                        | **2. Today's Work**[cite: 3]     | `today.index`         | `Today\TodayWorkIndex`            | Actionable queue, overdue alert badges[cite: 3, 5]                                                                 |
| **Customers**[cite: 3] | **3. Customers**[cite: 3]        | `customers.index`     | `Customers\CustomerIndex`         | `CustomerDetail`, `CustomerModal`, `CustomerImportModal`[cite: 3]                                                  |
|                        | **4. Projects**[cite: 3]         | `projects.index`      | `Projects\ProjectIndex`           | `ProjectDetail`, `ProjectModal`, `ProjectScheduleModal`, `ProjectRecurringModal`, `ProjectMilestoneModal`[cite: 3] |
|                        | **5. My Tasks**[cite: 3]         | `tasks.index`         | `Tasks\TaskIndex`                 | Priority checklist, due date late flags[cite: 3, 5]                                                                |
| **Money**[cite: 3]     | **6. Payments**[cite: 3]         | `payments.index`      | `Payments\PaymentIndex`           | `PaymentModal` with auto-balance calculation[cite: 3, 5]                                                           |
|                        | **7. Payment Schedule**[cite: 3] | `schedule.index`      | `Schedule\ScheduleIndex`          | Master timeline, trigger badges, CSV export[cite: 3, 5]                                                            |
|                        | **8. Reports**[cite: 3]          | `reports.index`       | `Reports\ReportIndex`             | Monthly sparkline, mode bars, aging bars, variance table[cite: 3, 5]                                               |
| **Records**[cite: 3]   | **9. Documents**[cite: 3]        | `documents.index`     | `Documents\DocumentIndex`         | `DocumentUploadModal`, `DocumentPreviewModal` (lightbox)[cite: 3, 5]                                               |
|                        | **10. Follow-ups**[cite: 3]      | `followups.index`     | `Followups\FollowupIndex`         | Touchpoint feed, overdue alert banner[cite: 3, 5]                                                                  |
|                        | **11. Sales Team**[cite: 3]      | `sales.index`         | `Sales\SalesIndex`                | `SalesModal`, target tracker, revenue attribution[cite: 3, 5]                                                      |
| **System**[cite: 3]    | **12. Notifications**[cite: 3]   | `notifications.index` | `Notifications\NotificationIndex` | `Components\NotificationBell`, deep linking routing[cite: 3, 5]                                                    |
|                        | **13. Activity Log**[cite: 3]    | `audit.index`         | `Audit\AuditIndex`                | Color-coded action timeline, user attribution[cite: 3, 5]                                                          |
|                        | **14. Settings**[cite: 3]        | `settings.index`      | `Settings\SettingIndex`           | `UserModal`, password manager, alert thresholds[cite: 3, 5]                                                        |

### Artisan Component Scaffolding Commands

To regenerate components or create new domain extensions:

```bash
php artisan make:livewire Components.GlobalSearch
php artisan make:livewire Components.NotificationBell
php artisan make:livewire Dashboard.DashboardIndex
php artisan make:livewire Today.TodayWorkIndex
php artisan make:livewire Customers.CustomerIndex
php artisan make:livewire Customers.CustomerDetail
php artisan make:livewire Customers.CustomerModal
php artisan make:livewire Customers.CustomerImportModal
php artisan make:livewire Projects.ProjectIndex
php artisan make:livewire Projects.ProjectDetail
php artisan make:livewire Projects.ProjectModal
php artisan make:livewire Projects.ProjectMilestoneModal
php artisan make:livewire Projects.ProjectScheduleModal
php artisan make:livewire Projects.ProjectRecurringModal
php artisan make:livewire Tasks.TaskIndex
php artisan make:livewire Payments.PaymentIndex
php artisan make:livewire Payments.PaymentModal
php artisan make:livewire Schedule.ScheduleIndex
php artisan make:livewire Reports.ReportIndex
php artisan make:livewire Documents.DocumentIndex
php artisan make:livewire Documents.DocumentUploadModal
php artisan make:livewire Documents.DocumentPreviewModal
php artisan make:livewire Followups.FollowupIndex
php artisan make:livewire Sales.SalesIndex
php artisan make:livewire Sales.SalesModal
php artisan make:livewire Notifications.NotificationIndex
php artisan make:livewire Audit.AuditIndex
php artisan make:livewire Settings.SettingIndex
php artisan make:livewire Settings.UserModal
```

---

## Financial Engine & Calculations

Financial accounting rules are centralized within `App\Services\MoneyCalculationService` to ensure calculations match
across dashboards, customer cards, project pages, and CSV exports[cite: 43]:

### Core Formulations

* **Total Scheduled Value:**
  $$\text{Scheduled Gross} = \sum_{t \in \text{Terms}, \, t.\text{status} \neq \text{'Cancelled'}} t.\text{amount}$$
  [cite: 43]

* **Net Cash Collected:**
  $$\text{Cash Received} = \sum_{p \in \text{Payments}} p.\text{amount}$$
  [cite: 43]

* **Tax Deducted at Source (TDS):**
  $$\text{Withheld TDS} = \sum_{p \in \text{Payments}} p.\text{tds}$$
  [cite: 43]

* **Remaining Balance Due:**
  $$\text{Balance Due} = \max\left(0.00, \; \text{Scheduled Gross} - \text{Cash Received} - \text{Withheld TDS}\right)$$
  [cite: 43]

* **Overdue Balance:**
  $$\text{Overdue Amount} = \sum_{t \in \text{Terms}, \, t.\text{status} = \text{'Overdue'}} \max\left(0.00, \; t.\text{amount} - t.\text{received} - t.\text{tds}\right)$$
  [cite: 43]

* **Collection Rate Percentage:**
  $$\text{Collection Rate (\%)} = \begin{cases} \text{round}\left(\frac{\text{Cash Received}}{\text{Scheduled Gross}} \times 100, 1\right) & \text{if } \text{Scheduled Gross} > 0 \\ 0.0 & \text{otherwise} \end{cases}$$
  [cite: 43]

### Installment State Progression

1. **`Cancelled`:** Manually cancelled installment terms[cite: 44].
2. **`Paid`:** Cash received plus TDS equals or exceeds installment
   value ($(\text{Received} + \text{TDS}) \ge \text{Amount} - 0.01$)[cite: 43, 44].
3. **`Partial`:** Partial payment has been received, but an outstanding balance
   remains ($0 < (\text{Received} + \text{TDS}) < \text{Amount} - 0.01$)[cite: 43, 44].
4. **`Overdue`:** The current date exceeds the due date plus grace
   window ($\text{CurrentDate} > \text{DueDate} + \text{GraceDays}$) with an outstanding balance[cite: 43, 44].
5. **`Pending`:** Future-dated installment with zero payments logged[cite: 44].

---

## Production Deployment & Server Config

### Optimization Commands

Before going live, compile frontend assets and cache all configuration and routes:

```bash
npm run build
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

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

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

### 1. Fatal Error: `Cannot redeclare class App\Livewire\...`

* **Cause:** A PHP component class definition was accidentally saved inside a `.blade.php` template file.
* **Fix:** Ensure the `.blade.php` template contains only HTML and Blade directives, and clear the view cache:
  ```bash
  php artisan view:clear
  php artisan optimize:clear
  ```

### 2. Livewire Action Error: `ArgumentCountError` on Save

* **Cause:** A dependency-injected service (such as `CodeGeneratorService`) was declared as a parameter in a component's
  `save()` method triggered via `wire:submit="save"`.
* **Fix:** Resolve services within the method using Laravel's container instead:
  ```php
  $codeService = app(CodeGeneratorService::class);
  ```

### 3. Missing Storage Attachments (404 on Uploads)

* **Cause:** The symbolic link between `storage/app/public` and `public/storage` is missing or broken.
* **Fix:** Recreate the storage link:
  ```bash
  rm public/storage
  php artisan storage:link
  ```

### 4. Database Foreign Key Errors on Alter Table

* **Cause:** Adding non-nullable foreign keys to tables containing legacy data without matching parent records[cite: 1].
* **Fix:** In migration files, declare foreign key columns as `nullable()` and use `nullOnDelete()`[cite: 1]:
  ```php
  \$table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
  ```

---

## License

This project is proprietary software belonging to **10X Agency**[cite: 44]. Unauthorized copying, modification, or
distribution is strictly prohibited[cite: 44].
