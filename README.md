# 10X Agency — Project Coordinator CRM (`PJT CRM`)

<p>
  <img src="public/assets/logo.png" alt="10X Agency Logo" width="180">
  <br>
  <strong>Unified Project Operations, Milestone Tracking & Financial Receivables CRM</strong>
  <br>
  <em>Engineered for agency coordinators, delivery leads, and finance directors.</em>
</p>

<p>
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
- [Livewire 4 & Alpine Runtime Conventions](#livewire-4--alpine-runtime-conventions)
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
agencies[cite: 72]. It centralizes client onboarding, project delivery health monitoring, milestone tracking, multi-tier
installment receivables, task checklists, and client follow-ups into a high-performance reactive full-stack web
application[cite: 72].

The platform is engineered on **Laravel 13** and **Livewire 4**, providing single-page application (SPA) reactivity
through `wire:navigate` and `Livewire.navigate()`, unified Alpine.js stores, dirty-state modal protection, real-time
validation, and a dedicated Service Layer that enforces financial consistency, concurrency-safe code generation, and
coordinator-scoped data security[cite: 72, 73].

---

## Key Features

### 📊 1. Executive Dashboard & Real-Time KPIs

* **Receivables Strip:** Real-time aggregates for Total Scheduled Value, Collected Cash, Net Due, Overdue Installments,
  and Current Month Collections[cite: 72].
* **Aging Buckets:** Automatic breakdown of delayed receivables into `0–30`, `31–60`, `61–90`, and `90+` day
  intervals[cite: 72].
* **Collection Trend Analysis:** Trailing 6-month visual revenue performance sparkline[cite: 72].
* **Daily Triage Queue:** Direct access to overdue payments, approaching client follow-ups, and at-risk project
  deadlines[cite: 72].
* **Commercial Performance:** Sales-wise contracted volume, collected revenue, and collection progress bars[cite: 72].
* **Operational Alerts:** Proactive tracking of upcoming Annual Maintenance Contract (AMC) expirations and pending
  project handovers[cite: 72].
* **Live Event Reactivity:** Listens for `payment-saved`, `payment-deleted`, `project-saved`, and `customer-saved`
  events to automatically refresh cards without full browser reloads.

### 🎯 2. Today's Work Queue

* **Daily Action Command Center:** Prioritized view of client follow-ups due today, overdue installments requiring
  collection, and deliverable risks[cite: 72].
* **Interactive Triage:** Quick links to customer profiles and inline payment buttons pre-populating project and term
  parameters via `Livewire.dispatch('open-payment-modal', ...)`[cite: 72].

### 📁 3. Customer Lifecycle & Bulk Onboarding

* **Comprehensive Client Dossiers:** Centralized company profiles with tax IDs (GSTIN/PAN), multi-address billing
  records, and industry tags[cite: 72].
* **Sales Attribution:** Track which salesperson onboarded each client and monitor revenue vs. individual monthly
  targets[cite: 72].
* **Bulk CSV Import:** Built-in spreadsheet parser supporting bulk client insertion with automatic atomic code
  generation (`CUS-XXXX`)[cite: 72].
* **Export Engine:** Filtered, UTF-8 BOM-encoded CSV data exports compatible with Microsoft Excel[cite: 72].
* **Validation & Data Integrity:** Uses `Rule::unique` on company names and converts empty select choices into null
  foreign keys to prevent constraint errors.

### 🚀 4. Project Delivery, Milestones & AMC Tracking

* **Delivery Health States:** Discrete confidence states (`On Track`, `At Risk`, `Delayed`, `Completed`) with mandatory
  delay reasoning[cite: 72].
* **Interactive Milestones:** Dynamic milestone checklist with completion checkboxes, due dates, sort ordering, and
  payment trigger linking[cite: 72].
* **Multi-Mode Installment Schedulers:** Fixed-date calendar milestones, delivery-milestone-linked triggers, and
  single-click monthly recurring retainer generation[cite: 72].
* **Annual Maintenance Contracts (AMC):** Dedicated contract tracking with configurable alert lead times and
  schema-level zero-default normalization (`cost`, `amc_amount`, `renew_alert_days`)[cite: 72].
* **Handover Governance:** Post-delivery checklist and formal client handover documentation[cite: 72].

### 💳 5. Financial Receivables & Installment Engine

* **Automated Status Transitions:** Installments automatically transition between `Pending`, `Partial`, `Paid`, and
  `Overdue` based on actual receipts, TDS deductions, and grace periods[cite: 72].
* **Zero-Default TDS Handling:** Normalized numerical inputs prevent MySQL `1048 Column 'tds' cannot be null` errors on
  untaxed settlements.
* **Dual Currency Display:** Live in-memory toggle between INR (`₹`) and USD (`$`) via an Alpine store (
  `$store.currency`) initialized on `alpine:init`, fully preserved across SPA page transitions[cite: 72].

### 📋 6. Tasks, Follow-ups, Documents & Audit

* **Personal Checklist:** Coordinator task management with priority levels (`Low`, `Medium`, `High`, `Urgent`) and
  overdue indicators[cite: 72].
* **Touchpoint Logger:** Multi-channel client interaction logging (`Call`, `Email`, `Meeting`, `WhatsApp`, `Note`) with
  next action alarms and role-scoped project binding[cite: 72].
* **Document Management:** Secure storage of contracts, KYC documents, and delivery proofs up to 10 MB with public disk
  root isolation and in-browser PDF/image lightbox previews[cite: 72].
* **Activity Audit Trail:** Immutable log of system mutations (creates, updates, payments, uploads, and
  deletions)[cite: 72].

---

## System Architecture

```mermaid
graph TD
    subgraph Client Layer [Frontend Client (SPA)]
        UI["Livewire 4 Components & Blade Templates"]
        ModalGuard["Alpine.js Modal Component & Unsaved Changes Guard (isDirty)"]
        AlpineStore["Alpine.js Stores ($store.currency, Sidebar State)"]
        LocalStorage["LocalStorage (pjt_cur, pjt_sidebar_collapsed)"]
        UI <--> ModalGuard
        UI <--> AlpineStore
        AlpineStore <--> LocalStorage
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
        RoleScope --> M3["Projects: ProjectIndex, ProjectDetail, ProjectModal, ScheduleModal, RecurringModal, MilestoneModal"]
        RoleScope --> M4["Money: PaymentIndex, PaymentModal, ScheduleIndex, ReportIndex"]
        RoleScope --> M5["Records: DocumentIndex, DocumentUploadModal, DocumentPreviewModal, FollowupIndex, SalesIndex, SalesModal"]
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
        Eloquent["Eloquent ORM Models (with Casts & Relationships)"]
        MySQL[("MySQL 8.0+ Database")]
        DiskStorage[("Public Disk Storage (storage/app/public)")]

        MoneyService & CodeService --> Eloquent
        Eloquent --> MySQL
        M5 --> DiskStorage
    end
```

---

## Livewire 4 & Alpine Runtime Conventions

To prevent DOM race conditions, detachment of form event listeners, or unexpected database exceptions, the following
engineering standards are strictly enforced across the application:

1. **Bundled Alpine Runtime**:
   Livewire 4 bundles Alpine.js out of the box[cite: 4]. `resources/js/app.js` must **not** import `alpinejs` or call
   `Alpine.start()`, as running dual Alpine engines breaks `wire:submit` and `wire:model` bindings[cite: 4].
2. **Global Event Broadcasting (`Livewire.dispatch`)**:
   Global layout-level modals (e.g. `<livewire:customers.customer-modal/>` or `<livewire:payments.payment-modal/>`)
   reside outside the DOM hierarchy of page tables[cite: 3]. Triggers must use
   `Livewire.dispatch('event-name', payload)` instead of Alpine `$dispatch` to broadcast across independent component
   trees[cite: 10, 12].
3. **SPA Transitions (`Livewire.navigate`)**:
   Navigating between views from clickable table rows must invoke `Livewire.navigate('url')` rather than legacy
   `window.location`. This avoids full browser reloads and maintains active Alpine store states (
   `$store.currency`)[cite: 72].
4. **Modal Unsaved Changes Guard (`isDirty`)**:
   The `<x-modal>` component listens for DOM `@input` and `@change` events to mark forms as dirty. Attempting to dismiss
   an unsubmitted form via outside click, `Escape`, or Cancel triggers a browser confirmation dialog (
   `confirm('You have unsaved changes...')`)[cite: 10].
5. **Real-Time Blur Validation**:
   Inputs utilize `wire:model.blur` combined with `$this->validateOnly($propertyName)` inside the `updated()` lifecycle
   hook. Invalid inputs display an active red border (`border-red-500! bg-red-50/20!`) and inline error labels beneath
   the field without rendering disruptive alert banners.
6. **Schema Null vs. Default Synchronization**:
   For database columns defined as `NOT NULL DEFAULT 0.00` (e.g., `projects.cost`, `projects.amc_amount`,
   `payments.tds`, `sales_persons.target`), Livewire components explicitly convert empty strings (`""`) to `0.00` before
   persistence, preventing MySQL `1048 Column cannot be null` errors[cite: 20].

---

## Directory Layout

```text
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── ProfileController.php           # User profile editing and password updates[cite: 72]
│   ├── Livewire/
│   │   ├── Audit/
│   │   │   └── AuditIndex.php                  # System audit log timeline viewer[cite: 72]
│   │   ├── Components/
│   │   │   ├── GlobalSearch.php                # Universal debounced search dropdown[cite: 72]
│   │   │   └── NotificationBell.php            # Topbar unread count polling badge[cite: 72]
│   │   ├── Customers/
│   │   │   ├── CustomerDetail.php              # Tabbed customer profile (Overview, Projects, Payments, Docs, Followups)[cite: 72]
│   │   │   ├── CustomerImportModal.php         # Bulk CSV upload and spreadsheet paste modal[cite: 72]
│   │   │   ├── CustomerIndex.php               # Customer directory, filtering, pagination, and CSV export[cite: 72]
│   │   │   └── CustomerModal.php               # Unified create and edit modal for customers[cite: 72]
│   │   ├── Dashboard/
│   │   │   └── DashboardIndex.php              # Executive KPI cards, health breakdown, triage, and trend analytics[cite: 72]
│   │   ├── Documents/
│   │   │   ├── DocumentIndex.php               # Global document repository with category filtering and deletion[cite: 72]
│   │   │   ├── DocumentPreviewModal.php        # Lightbox preview modal for PDFs and images[cite: 72]
│   │   │   └── DocumentUploadModal.php         # Multi-entity file upload modal with validation[cite: 72]
│   │   ├── Followups/
│   │   │   └── FollowupIndex.php               # Touchpoint feed, overdue alert banner, and interaction logger[cite: 72]
│   │   ├── Notifications/
│   │   │   └── NotificationIndex.php           # Notification inbox with deep linking and mark-as-read actions[cite: 72]
│   │   ├── Payments/
│   │   │   ├── PaymentIndex.php                # Receipt ledger, mode filtering, balance tracking, and CSV export[cite: 72]
│   │   │   └── PaymentModal.php                # Receipt recorder with auto-balance calculation and term binding[cite: 72]
│   │   ├── Projects/
│   │   │   ├── ProjectDetail.php               # Project delivery matrix, timeline, schedule, and document tabs[cite: 72]
│   │   │   ├── ProjectIndex.php                # Work orders directory, health filtering, and overdue rollups[cite: 72]
│   │   │   ├── ProjectMilestoneModal.php       # Dynamic milestone ordering and checklist manager[cite: 72]
│   │   │   ├── ProjectModal.php                # Project contract creator/editor with AMC and handover fields[cite: 72]
│   │   │   ├── ProjectRecurringModal.php       # Monthly retainer recurring installment generator[cite: 72]
│   │   │   └── ProjectScheduleModal.php        # Installment scheduler (fixed, milestone-linked, recurring)[cite: 72]
│   │   ├── Reports/
│   │   │   └── ReportIndex.php                 # Collection rate, monthly sparkline, mode bars, aging, and variance[cite: 72]
│   │   ├── Sales/
│   │   │   ├── SalesIndex.php                  # Salesperson revenue attribution, targets, and comparative table[cite: 72]
│   │   │   └── SalesModal.php                  # Sales representative registration and quota manager[cite: 72]
│   │   ├── Schedule/
│   │   │   └── ScheduleIndex.php               # Master chronological installment timeline and CSV export[cite: 72]
│   │   ├── Settings/
│   │   │   ├── SettingIndex.php                # Operational threshold configuration, password manager, and user list[cite: 72]
│   │   │   └── UserModal.php                   # Staff account creator/editor with temporary password generator[cite: 72]
│   │   ├── Tasks/
│   │   │   └── TaskIndex.php                   # Coordinator checklist manager with priority flags and late indicators[cite: 72]
│   │   └── Today/
│   │       └── TodayWorkIndex.php              # Daily operational triage for overdue payments, calls, and risks[cite: 72]
│   ├── Models/                                 # 14 Eloquent Models with relationships & casts[cite: 72]
│   └── Services/
│       ├── CodeGeneratorService.php            # Concurrency-safe atomic counter sequence generator[cite: 72]
│       ├── CurrencyRateService.php             # Exchange rate configuration and conversion service[cite: 72]
│       └── MoneyCalculationService.php         # Central financial calculation engine[cite: 72]
├── database/
│   ├── migrations/                             # 14 structured table migrations[cite: 72]
│   └── seeders/
│       ├── DatabaseSeeder.php[cite: 72]
│       └── UserSeeder.php                      # Seeds default Administrator and Coordinator[cite: 72]
├── public/
│   ├── assets/logo.png                         # Static brand identity asset[cite: 72]
│   └── css/crm-style.css                       # Complete CSS layout & design palette[cite: 72]
├── resources/
│   ├── css/app.css                             # Tailwind CSS setup[cite: 3]
│   ├── js/app.js                               # Clean JS entry without manual Alpine imports[cite: 3]
│   └── views/
│       ├── components/                         # Blade UI components (money, kpi-card, health-badge, modal)[cite: 72]
│       ├── layouts/
│       │   ├── app.blade.php                   # Master responsive application layout[cite: 72]
│       │   └── partials/sidebar.blade.php      # 14-module navigation sidebar with live counters[cite: 72]
│       └── livewire/                           # Paired Blade templates for all Livewire components[cite: 72]
└── routes/
    ├── auth.php                                # Breeze authentication routes[cite: 72]
    └── web.php                                 # Authenticated routes mapping all 14 Livewire modules[cite: 72]
```

---

## Quickstart & Local Installation

### 1. Prerequisites

* **PHP:** `^8.2` with `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `tokenizer`, `xml` extensions[cite: 72]
* **Database:** MySQL `8.0+` or MariaDB `10.4+`[cite: 72]
* **Composer:** `v2.x`[cite: 72]
* **Node.js:** `v18.x` or higher (with NPM)[cite: 72]

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

Create the public storage symlink for uploaded contracts, KYC, and invoice documents:

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

Navigate to **`http://127.0.0.1:8000`** in your browser[cite: 72].

---

## Default Seed Credentials

Following database seeding, log in using either of the following accounts:

| Role                      | Email                                   | Default Password        | Permissions Scope                                                                           |
|:--------------------------|:----------------------------------------|:------------------------|:--------------------------------------------------------------------------------------------|
| **Admin**[cite: 72]       | `admin@the10xagency.in`[cite: 72]       | `Sample@1123`[cite: 72] | Unrestricted access: User Administration, System Settings, Customer Deletion[cite: 72]      |
| **Coordinator**[cite: 72] | `coordinator@the10xagency.in`[cite: 72] | `Sample@1123`[cite: 72] | Account-scoped access: Customers, Assigned Projects, Payments, Uploads, Tasks[cite: 72, 73] |

> **Security Notice:** Update default passwords immediately via **Settings (`/settings`)** upon initial deployment.

---

## Livewire 4 Component Matrix

All 14 operational modules are built as dedicated Livewire 4 full-page components and modals[cite: 72]:

| Module Group            | Module Name                       | Route Name            | Livewire Class                    | Key Sub-Components & Modals                                                                                         |
|:------------------------|:----------------------------------|:----------------------|:----------------------------------|:--------------------------------------------------------------------------------------------------------------------|
| **Overview**[cite: 72]  | **1. Dashboard**[cite: 72]        | `dashboard`           | `Dashboard\DashboardIndex`        | `x-kpi-card`, `x-health-badge`, `spark` bar chart, reactive mutation listeners[cite: 72]                            |
|                         | **2. Today's Work**[cite: 72]     | `today.index`         | `Today\TodayWorkIndex`            | Actionable queue, overdue alert badges, inline `+ Payment` modal trigger[cite: 72]                                  |
| **Customers**[cite: 72] | **3. Customers**[cite: 72]        | `customers.index`     | `Customers\CustomerIndex`         | `CustomerDetail`, `CustomerModal` (with `Rule::unique`), `CustomerImportModal`[cite: 72]                            |
|                         | **4. Projects**[cite: 72]         | `projects.index`      | `Projects\ProjectIndex`           | `ProjectDetail`, `ProjectModal`, `ProjectScheduleModal`, `ProjectRecurringModal`, `ProjectMilestoneModal`[cite: 72] |
|                         | **5. My Tasks**[cite: 72]         | `tasks.index`         | `Tasks\TaskIndex`                 | Priority checklist, due date late flags, sanitized selects[cite: 72]                                                |
| **Money**[cite: 72]     | **6. Payments**[cite: 72]         | `payments.index`      | `Payments\PaymentIndex`           | `PaymentModal` with auto-balance calculation, non-null TDS defaults[cite: 72]                                       |
|                         | **7. Payment Schedule**[cite: 72] | `schedule.index`      | `Schedule\ScheduleIndex`          | Master timeline, trigger badges, CSV export, live recalculations[cite: 72]                                          |
|                         | **8. Reports**[cite: 72]          | `reports.index`       | `Reports\ReportIndex`             | Monthly sparkline, mode bars, aging bars, variance table, SPA routing[cite: 72]                                     |
| **Records**[cite: 72]   | **9. Documents**[cite: 72]        | `documents.index`     | `Documents\DocumentIndex`         | `DocumentUploadModal` (public disk root), `DocumentPreviewModal` (lightbox)[cite: 72]                               |
|                         | **10. Follow-ups**[cite: 72]      | `followups.index`     | `Followups\FollowupIndex`         | Touchpoint feed, overdue alert banner, scoped project relations[cite: 72]                                           |
|                         | **11. Sales Team**[cite: 72]      | `sales.index`         | `Sales\SalesIndex`                | `SalesModal` with unique name validation and quota tracking[cite: 72]                                               |
| **System**[cite: 72]    | **12. Notifications**[cite: 72]   | `notifications.index` | `Notifications\NotificationIndex` | `Components\NotificationBell`, deep linking routing[cite: 72]                                                       |
|                         | **13. Activity Log**[cite: 72]    | `audit.index`         | `Audit\AuditIndex`                | Color-coded action timeline, user attribution[cite: 72]                                                             |
|                         | **14. Settings**[cite: 72]        | `settings.index`      | `Settings\SettingIndex`           | `UserModal`, password complexity manager, threshold configurations[cite: 72]                                        |

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
across dashboards, customer cards, project pages, and CSV exports[cite: 72]:

### Core Formulations

* **Total Scheduled Value:**
  $$\text{Scheduled Gross} = \sum_{t \in \text{Terms}, \, t.\text{status} \neq \text{'Cancelled'}} t.\text{amount}$$
  [cite: 72]

* **Net Cash Collected:**
  $$\text{Cash Received} = \sum_{p \in \text{Payments}} p.\text{amount}$$
  [cite: 72]

* **Tax Deducted at Source (TDS):**
  $$\text{Withheld TDS} = \sum_{p \in \text{Payments}} p.\text{tds}$$
  [cite: 72]

* **Remaining Balance Due:**
  $$\text{Balance Due} = \max\left(0.00, \; \text{Scheduled Gross} - \text{Cash Received} - \text{Withheld TDS}\right)$$
  [cite: 72]

* **Overdue Balance:**
  $$\text{Overdue Amount} = \sum_{t \in \text{Terms}, \, t.\text{status} = \text{'Overdue'}} \max\left(0.00, \; t.\text{amount} - t.\text{received} - t.\text{tds}\right)$$
  [cite: 72]

* **Collection Rate Percentage:**
  $$\text{Collection Rate (\%)} = \begin{cases} \text{round}\left(\frac{\text{Cash Received}}{\text{Scheduled Gross}} \times 100, 1\right) & \text{if } \text{Scheduled Gross} > 0 \\ 0.0 & \text{otherwise} \end{cases}$$
  [cite: 72]

### Installment State Progression

1. **`Cancelled`:** Manually cancelled installment terms[cite: 72].
2. **`Paid`:** Cash received plus TDS equals or exceeds installment
   value ($(\text{Received} + \text{TDS}) \ge \text{Amount} - 0.01$)[cite: 72].
3. **`Partial`:** Partial payment has been received, but an outstanding balance
   remains ($0 < (\text{Received} + \text{TDS}) < \text{Amount} - 0.01$)[cite: 72].
4. **`Overdue`:** The current date exceeds the due date plus grace
   window ($\text{CurrentDate} > \text{DueDate} + \text{GraceDays}$) with an outstanding balance[cite: 72].
5. **`Pending`:** Future-dated installment with zero payments logged[cite: 72].

---

## Production Deployment & Server Config

### Optimization Commands

Before going live, compile frontend assets and cache all configuration, views, and routes:

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

### 1. Form Submissions Fail Silently or Event Listeners Do Not Respond

* **Cause:** Standalone Alpine was manually imported in `resources/js/app.js` (
  `import Alpine from 'alpinejs'; Alpine.start();`), creating two concurrent Alpine runtimes that detached Livewire's
  `wire:submit` and `wire:model` listeners[cite: 4].
* **Fix:** Remove the standalone import and startup invocation from `resources/js/app.js`. Livewire 4 boots Alpine
  automatically[cite: 4].

### 2. SQL Integrity Violation: `Column 'amc_amount' (or 'tds') cannot be null`

* **Cause:** The database column was configured with a default (`default(0.00)`) without an explicit `nullable()`
  modifier, but the form assigned an empty string or null when AMC or TDS was unused[cite: 20].
* **Fix:** In the respective component (`ProjectModal.php`, `PaymentModal.php`), normalize cleared numeric properties to
  `0.00` before invoking `$this->validate()`[cite: 22].

### 3. TypeError: `Cannot assign null to property of type float`

* **Cause:** Form fields bound to typed properties (e.g. `public float $cost = 0.0;`) receive empty strings `""` when
  cleared by users.
* **Fix:** Declare properties as nullable (`public ?float $cost = 0.0;`) and cast them within the component's
  `updated()` lifecycle hook.

### 4. Modals Fail to Open from Table Buttons

* **Cause:** The button used Alpine DOM `$dispatch('open-modal', 'modal-name')`, but the target modal component was
  registered globally in `resources/views/layouts/app.blade.php` outside the table's DOM hierarchy[cite: 3].
* **Fix:** Dispatch the event across Livewire components via
  `Livewire.dispatch('open-modal-name', { ... })`[cite: 10, 12].

### 5. `Call to a member function toDateString() on string`

* **Cause:** Eloquent model dates were returned as raw strings from the database because explicit date casting was
  missing in the model definition.
* **Fix:** Add protected `$casts = ['start_date' => 'date', 'delivery_date' => 'date', ...]` in the corresponding
  Eloquent model class.

### 6. Missing Storage Attachments (404 on Uploads)

* **Cause:** The symbolic link between `storage/app/public` and `public/storage` is missing or pointing to an invalid
  directory[cite: 72].
* **Fix:** Recreate the storage symbolic link:
  ```bash
  rm public/storage
  php artisan storage:link
  ```

---

## License

This project is proprietary software belonging to **10X Agency**[cite: 72]. Unauthorized copying, modification, or
distribution is strictly prohibited[cite: 72].
