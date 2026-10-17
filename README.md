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
- [Architecture & Evolutionary Migration](#architecture--evolutionary-migration)
- [Key Features](#key-features)
- [System Architecture Diagram](#system-architecture-diagram)
- [Livewire 4 & Alpine Runtime Conventions](#livewire-4--alpine-runtime-conventions)
- [Directory Layout](#directory-layout)
- [Quickstart & Local Installation](#quickstart--local-installation)
- [Default Seed Credentials](#default-seed-credentials)
- [Livewire 4 Component Matrix](#livewire-4-component-matrix)
- [Financial Engine & Calculations](#financial-engine--calculations)
- [Production Deployment & Server Config](#production-deployment--server-config)
- [Troubleshooting & Runbook](#troubleshooting--runbook)
- [Defects & Cleanup Audit (`issues.md`)](#defects--cleanup-audit-issuesmd)
- [License](#license)

---

## Overview

**PJT CRM** is a single-tenant operations and financial management platform designed specifically for digital services agencies. It centralizes client onboarding, project delivery health monitoring, milestone tracking, multi-tier installment receivables, task checklists, client follow-ups, and native desktop notifications into a high-performance reactive full-stack web application.

The platform is engineered on **Laravel 13** and **Livewire 4**, providing single-page application (SPA) reactivity through `wire:navigate` and `Livewire.navigate()`, unified Alpine.js stores, dirty-state modal protection, real-time validation, native browser desktop alerts, and a dedicated Service Layer that enforces financial consistency, concurrency-safe code generation, and coordinator-scoped data security.

---

## Architecture & Evolutionary Migration

The project evolved through three foundational architectural stages:
1. **Procedural Core PHP & Core JS**: Initial flat scripts (`index.php`, `api.php`, `config.php`, `public/js/crm-app.js`) handling database operations and client rendering.
2. **Laravel REST API + Core JS**: An intermediate stage where database models and REST controllers (`app/Http/Controllers/Api/*`) were created while keeping the core JS (`public/js/crm-app.js`) intact to fetch from `/api.php` and `/v1/*`.
3. **Full Livewire 4 SPA**: The current architecture, where all UI, forms, tables, modals, and actions were re-engineered as native Livewire 4 components (`app/Livewire/*` and `resources/views/livewire/*`).

> [!NOTE]
> All legacy API controllers, legacy routes (`routes/api.php`), dead services, and `public/js/crm-app.js` have been removed from the repository. All application interactions and endpoints are powered by native Livewire 4 components. See [**`issues.md`**](file:///c:/wamp64/www/project-manager/issues.md) for the complete defect registry and audit history.

---

## Key Features

### 📊 1. Executive Dashboard & Real-Time KPIs

* **Receivables Strip:** Real-time aggregates for Total Scheduled Value, Collected Cash, Net Due, Overdue Installments, and Current Month Collections.
* **Aging Buckets:** Automatic breakdown of delayed receivables into `0–30`, `31–60`, `61–90`, and `90+` day intervals.
* **Collection Trend Analysis:** Trailing 6-month visual revenue performance sparkline.
* **Daily Triage Queue:** Direct access to overdue payments, approaching client follow-ups, and at-risk project deadlines.
* **Commercial Performance:** Sales-wise contracted volume, collected revenue, and collection progress bars.
* **Operational Alerts:** Proactive tracking of upcoming Annual Maintenance Contract (AMC) expirations and pending project handovers.
* **Live Event Reactivity:** Listens for `payment-saved`, `payment-deleted`, `project-saved`, and `customer-saved` events to automatically refresh cards without full browser reloads.

### 🔔 2. Multi-Channel Notification Engine & Desktop Alerts

* **Interactive Slide-Over Drawer:** Slide-over notification panel (`NotificationDrawer`) accessible from the topbar bell icon, featuring real-time unread badges, filter tabs (`All` vs `Unread`), single mark-as-read, and bulk `Mark all as read`.
* **Native Desktop OS Banners:** Built-in client poller (`browserNotifier` Alpine module in `app.blade.php`) querying `GET /api/notifications/poll` every 30 seconds. Dispatches native operating system desktop alerts (`new Notification(...)`) with direct deep-linking on click.
* **Dedicated Notification Detail Page:** Full-page view (`/notifications/{notification}`) for inspectable alert details, direct target entity navigation (linked project or customer), and toggle read/unread actions.
* **Reactive Topbar Bell:** Real-time unread counter badge synchronized globally via the Alpine store (`$store.notifications`).

### 🔒 3. Account Security & Forced Password Change

* **Mandatory Initial Password Change:** Middleware (`EnsurePasswordIsChanged`) intercepts authenticated sessions when `must_change = true`, redirecting users to the dedicated `/change-password` interface.
* **Password Complexity Enforcement:** Strict validation requiring minimum 8 characters, at least 1 uppercase letter, 1 lowercase letter, and 1 numeric digit.
* **Downline Hierarchy & Scoping:** Self-referencing user hierarchy (`reporting_to`) allowing managers to track coordinator team activity and project delivery downlines.

### 🎯 4. Today's Work Queue

* **Daily Action Command Center:** Prioritized view of client follow-ups due today, overdue installments requiring collection, and deliverable risks.
* **Interactive Triage:** Quick links to customer profiles and inline payment buttons pre-populating project and term parameters via `Livewire.dispatch('open-payment-modal', ...)`.

### 📁 5. Customer Lifecycle & Bulk Onboarding

* **Comprehensive Client Dossiers:** Centralized company profiles with tax IDs (GSTIN/PAN), multi-address billing records, and industry tags.
* **Sales Attribution:** Track which salesperson onboarded each client and monitor revenue vs. individual monthly targets.
* **Bulk CSV Import:** Built-in spreadsheet parser supporting bulk client insertion with automatic atomic code generation (`CUS-XXXX`).
* **Export Engine:** Filtered, UTF-8 BOM-encoded CSV data exports compatible with Microsoft Excel.

### 🚀 6. Project Delivery, Milestones & AMC Tracking

* **Delivery Health States:** Discrete confidence states (`On Track`, `At Risk`, `Delayed`, `Completed`) with mandatory delay reasoning.
* **Interactive Milestones:** Dynamic milestone checklist with completion checkboxes, due dates, sort ordering, and payment trigger linking.
* **Multi-Mode Installment Schedulers:** Fixed-date calendar milestones, delivery-milestone-linked triggers, and single-click monthly recurring retainer generation.
* **Annual Maintenance Contracts (AMC):** Dedicated contract tracking with configurable alert lead times and zero-default normalization (`cost`, `amc_amount`, `renew_alert_days`).
* **Handover Governance:** Post-delivery checklist and formal client handover documentation.

### 💳 7. Financial Receivables & Installment Engine

* **Automated Status Transitions:** Installments automatically transition between `Pending`, `Partial`, `Paid`, and `Overdue` based on actual receipts, TDS deductions, and grace periods.
* **Zero-Default TDS Handling:** Normalized numerical inputs prevent MySQL `1048 Column 'tds' cannot be null` errors on untaxed settlements.
* **Dual Currency Display:** Live in-memory toggle between INR (`₹`) and USD (`$`) via an Alpine store (`$store.currency`) initialized on `alpine:init`, fully preserved across SPA page transitions.

### 📋 8. Tasks, Follow-ups, Documents & Audit

* **Personal Checklist:** Coordinator task management with priority levels (`Low`, `Medium`, `High`, `Urgent`) and overdue indicators.
* **Touchpoint Logger:** Multi-channel client interaction logging (`Call`, `Email`, `Meeting`, `WhatsApp`, `Note`) with next action alarms and role-scoped project binding.
* **Document Management:** Secure storage of contracts, KYC documents, and delivery proofs up to 10 MB with public disk root isolation and in-browser PDF/image lightbox previews.
* **Activity Audit Trail:** Immutable log of system mutations (`ActivityLog` / `activity_logs` table) recording creates, updates, payments, uploads, and deletions.

---

## System Architecture Diagram

```mermaid
graph TD
    subgraph Client Layer [Frontend Client (SPA)]
        UI["Livewire 4 Components & Blade Templates"]
        ModalGuard["Alpine.js Modal Component & Unsaved Changes Guard (isDirty)"]
        AlpineStore["Alpine.js Stores ($store.currency, $store.notifications, Sidebar State)"]
        DesktopNotif["Native Desktop Browser Notifier (Alpine browserNotifier, 30s Poll)"]
        LocalStorage["LocalStorage (pjt_cur, pjt_sidebar_collapsed, crm_last_seen_notif)"]
        UI <--> ModalGuard
        UI <--> AlpineStore
        AlpineStore <--> LocalStorage
        DesktopNotif <--> LocalStorage
    end

    subgraph HTTP Routing & Middleware Layer
        Routes["routes/web.php (wire:navigate Enabled)"]
        AuthMiddleware["Middleware: auth, verified, EnsurePasswordIsChanged"]
        PollRoute["GET /api/notifications/poll (NotificationPollController)"]
        RoleScope["Eloquent Query Scope: scopeForUser()"]
        Routes --> AuthMiddleware --> RoleScope
        DesktopNotif -.->|Fetch 30s Poll| PollRoute
    end

    subgraph Livewire 4 Component Registry
        RoleScope --> M1["Overview: DashboardIndex, TodayWorkIndex"]
        RoleScope --> M2["Customers: CustomerIndex, CustomerDetail, CustomerModal, CustomerImportModal"]
        RoleScope --> M3["Projects: ProjectIndex, ProjectDetail, ProjectModal, ScheduleModal, RecurringModal, MilestoneModal"]
        RoleScope --> M4["Money: PaymentIndex, PaymentModal, ScheduleIndex, ReportIndex"]
        RoleScope --> M5["Records: DocumentIndex, DocumentUploadModal, DocumentPreviewModal, FollowupIndex, SalesIndex, SalesModal"]
        RoleScope --> M6["System: NotificationIndex, NotificationDetail, NotificationBell, NotificationDrawer, AuditIndex, SettingIndex, UserModal"]
        AuthMiddleware --> M7["Auth: ForcePasswordChange (/change-password)"]
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

To prevent DOM race conditions, detachment of form event listeners, or unexpected database exceptions, the following engineering standards are strictly enforced across the application:

1. **Bundled Alpine Runtime**:
   Livewire 4 bundles Alpine.js out of the box. `resources/js/app.js` must **not** import `alpinejs` or call `Alpine.start()`, as running dual Alpine engines breaks `wire:submit` and `wire:model` bindings.
2. **Global Event Broadcasting (`Livewire.dispatch`)**:
   Global layout-level modals (e.g. `<livewire:customers.customer-modal/>` or `<livewire:payments.payment-modal/>`) reside outside the DOM hierarchy of page tables. Triggers must use `Livewire.dispatch('event-name', payload)` instead of Alpine `$dispatch` to broadcast across independent component trees.
3. **SPA Transitions (`Livewire.navigate`)**:
   Navigating between views from clickable table rows must invoke `Livewire.navigate('url')` rather than legacy `window.location`. This avoids full browser reloads and maintains active Alpine store states (`$store.currency`, `$store.notifications`).
4. **Modal Unsaved Changes Guard (`isDirty`)**:
   The `<x-modal>` component listens for DOM `@input` and `@change` events to mark forms as dirty. Attempting to dismiss an unsubmitted form via outside click, `Escape`, or Cancel triggers a browser confirmation dialog (`confirm('You have unsaved changes...')`).
5. **Real-Time Blur Validation**:
   Inputs utilize `wire:model.blur` combined with `$this->validateOnly($propertyName)` inside the `updated()` lifecycle hook. Invalid inputs display an active red border (`border-red-500! bg-red-50/20!`) and inline error labels beneath the field without rendering disruptive alert banners.
6. **Schema Null vs. Default Synchronization**:
   For database columns defined as `NOT NULL DEFAULT 0.00` (e.g., `projects.cost`, `projects.amc_amount`, `payments.tds`, `sales_persons.target`), Livewire components explicitly convert empty strings (`""`) to `0.00` before persistence, preventing MySQL `1048 Column cannot be null` errors.

---

## Directory Layout

```text
├── .agents/                                # AI workspace configuration & project skills
│   ├── mcp_config.json                     # Laravel Boost MCP server configuration
│   └── skills/                             # Domain skills (codewise, laravel-best-practices, livewire, etc.)
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── NotificationPollController.php  # Lightweight 30s endpoint for desktop notification polling
│   │   │   ├── Auth/                               # Laravel Breeze authentication controllers
│   │   │   └── ProfileController.php               # User profile editing and password updates
│   │   └── Middleware/
│   │       └── EnsurePasswordIsChanged.php         # Intercepts logins with must_change flag
│   ├── Livewire/
│   │   ├── Audit/
│   │   │   └── AuditIndex.php                      # System activity log timeline viewer
│   │   ├── Auth/
│   │   │   └── ForcePasswordChange.php             # Dedicated password update screen for flagged users
│   │   ├── Components/
│   │   │   ├── GlobalSearch.php                    # Universal debounced search dropdown
│   │   │   ├── NotificationBell.php                # Topbar unread count polling badge
│   │   │   └── NotificationDrawer.php              # Slide-over flyout panel for notifications
│   │   ├── Customers/
│   │   │   ├── CustomerDetail.php                  # Tabbed customer profile (Overview, Projects, Payments, Docs, Followups)
│   │   │   ├── CustomerImportModal.php             # Bulk CSV upload and spreadsheet paste modal
│   │   │   ├── CustomerIndex.php                   # Customer directory, filtering, pagination, and CSV export
│   │   │   └── CustomerModal.php                   # Unified create and edit modal for customers
│   │   ├── Dashboard/
│   │   │   └── DashboardIndex.php                  # Executive KPI cards, health breakdown, triage, and trend analytics
│   │   ├── Documents/
│   │   │   ├── DocumentIndex.php                   # Global document repository with category filtering and deletion
│   │   │   ├── DocumentPreviewModal.php            # Lightbox preview modal for PDFs and images
│   │   │   └── DocumentUploadModal.php             # Multi-entity file upload modal with validation
│   │   ├── Followups/
│   │   │   └── FollowupIndex.php                   # Touchpoint feed, overdue alert banner, and interaction logger
│   │   ├── Notifications/
│   │   │   ├── NotificationDetail.php              # Dedicated notification view with target navigation
│   │   │   └── NotificationIndex.php               # Notification inbox with deep linking and mark-as-read actions
│   │   ├── Payments/
│   │   │   ├── PaymentIndex.php                    # Receipt ledger, mode filtering, balance tracking, and CSV export
│   │   │   └── PaymentModal.php                    # Receipt recorder with auto-balance calculation and term binding
│   │   ├── Projects/
│   │   │   ├── ProjectDetail.php                   # Project delivery matrix, timeline, schedule, and document tabs
│   │   │   ├── ProjectIndex.php                    # Work orders directory, health filtering, and overdue rollups
│   │   │   ├── ProjectMilestoneModal.php           # Dynamic milestone ordering and checklist manager
│   │   │   ├── ProjectModal.php                    # Project contract creator/editor with AMC and handover fields
│   │   │   ├── ProjectRecurringModal.php           # Monthly retainer recurring installment generator
│   │   │   └── ProjectScheduleModal.php            # Installment scheduler (fixed, milestone-linked, recurring)
│   │   ├── Reports/
│   │   │   └── ReportIndex.php                     # Collection rate, monthly sparkline, mode bars, aging, and variance
│   │   ├── Sales/
│   │   │   ├── SalesIndex.php                      # Salesperson revenue attribution, targets, and comparative table
│   │   │   └── SalesModal.php                      # Sales representative registration and quota manager
│   │   ├── Schedule/
│   │   │   └── ScheduleIndex.php                   # Master chronological installment timeline and CSV export
│   │   ├── Settings/
│   │   │   ├── SettingIndex.php                    # Operational threshold configuration, password manager, and user list
│   │   │   └── UserModal.php                       # Staff account creator/editor with temporary password generator
│   │   ├── Tasks/
│   │   │   └── TaskIndex.php                       # Coordinator checklist manager with priority flags and late indicators
│   │   └── Today/
│   │       └── TodayWorkIndex.php                  # Daily operational triage for overdue payments, calls, and risks
│   ├── Models/                                     # 14 Eloquent Models with relationships & casts
│   │   ├── ActivityLog.php                         # System activity log model (mapped to activity_logs)
│   │   └── ...
│   └── Services/
│       ├── CodeGeneratorService.php                # Concurrency-safe atomic counter sequence generator
│       ├── CurrencyRateService.php                 # Exchange rate configuration and conversion service
│       └── MoneyCalculationService.php             # Central financial calculation engine
├── database/
│   ├── migrations/                                 # 17 structured table migrations
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── UsersSeeder.php                         # Seeds default Administrator, Coordinator, and Manager accounts
├── public/
│   ├── assets/logo.png                             # Static brand identity asset
│   └── css/crm-style.css                           # Complete CSS layout & design palette
├── resources/
│   ├── css/app.css                                 # Tailwind CSS setup
│   ├── js/app.js                                   # Clean JS entry without manual Alpine imports
│   └── views/
│       ├── components/                             # Blade UI components (money, kpi-card, health-badge, modal)
│       ├── layouts/
│       │   ├── app.blade.php                       # Master responsive application layout with slide-over drawer & notifier
│       │   ├── guest.blade.php                     # Clean layout for login and password change
│       │   └── partials/sidebar.blade.php          # 14-module navigation sidebar with live counters
│       └── livewire/                               # Paired Blade templates for all Livewire components
├── boost.json                              # Laravel Boost agent and skill registry
├── issues.md                               # Local defect registry & audit scratchpad (gitignored)
└── routes/
    ├── auth.php                            # Breeze authentication routes
    └── web.php                             # Authenticated routes mapping all Livewire modules & polling API
```

---

## Quickstart & Local Installation

### 1. Prerequisites

* **PHP:** `^8.3` (running PHP 8.4) with `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `tokenizer`, `xml` extensions
* **Database:** MySQL `8.0+` or MariaDB `10.4+`
* **Composer:** `v2.x`
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
php artisan db:seed --class=UsersSeeder
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

Navigate to **`http://127.0.0.1:8000`** in your browser.

---

## Default Seed Credentials

Following database seeding, log in using any of the following accounts:

| Role | Email | Default Password | Permissions Scope |
|:---|:---|:---|:---|
| **Admin** | `admin@the10xagency.in` | `Sample@1123` | Unrestricted access: User Administration, System Settings, Customer Deletion |
| **Coordinator** | `coordinator@the10xagency.in` | `Sample@1123` | Account-scoped access: Customers, Assigned Projects, Payments, Uploads, Tasks |
| **Manager** | `manager@the10xagency.in` | `Sample@1123` | Supervisory access: Downline coordinator project oversight and activity tracking |

> **Security Notice:** Update default passwords immediately via **Settings (`/settings`)** upon initial deployment.

---

## Livewire 4 Component Matrix

All operational modules are built as dedicated Livewire 4 full-page components and modals:

| Module Group | Module Name | Route Name | Livewire Class | Key Sub-Components & Modals |
|:---|:---|:---|:---|:---|
| **Overview** | **1. Dashboard** | `dashboard` | `Dashboard\DashboardIndex` | `x-kpi-card`, `x-health-badge`, `spark` bar chart, reactive mutation listeners |
| | **2. Today's Work** | `today.index` | `Today\TodayWorkIndex` | Actionable queue, overdue alert badges, inline `+ Payment` modal trigger |
| **Customers** | **3. Customers** | `customers.index` | `Customers\CustomerIndex` | `CustomerDetail`, `CustomerModal`, `CustomerImportModal` |
| | **4. Projects** | `projects.index` | `Projects\ProjectIndex` | `ProjectDetail`, `ProjectModal`, `ProjectScheduleModal`, `ProjectRecurringModal`, `ProjectMilestoneModal` |
| | **5. My Tasks** | `tasks.index` | `Tasks\TaskIndex` | Priority checklist, due date late flags, sanitized selects |
| **Money** | **6. Payments** | `payments.index` | `Payments\PaymentIndex` | `PaymentModal` with auto-balance calculation, non-null TDS defaults |
| | **7. Payment Schedule** | `schedule.index` | `Schedule\ScheduleIndex` | Master timeline, trigger badges, CSV export, live recalculations |
| | **8. Reports** | `reports.index` | `Reports\ReportIndex` | Monthly sparkline, mode bars, aging bars, variance table, SPA routing |
| **Records** | **9. Documents** | `documents.index` | `Documents\DocumentIndex` | `DocumentUploadModal` (public disk root), `DocumentPreviewModal` (lightbox) |
| | **10. Follow-ups** | `followups.index` | `Followups\FollowupIndex` | Touchpoint feed, overdue alert banner, scoped project relations |
| | **11. Sales Team** | `sales.index` | `Sales\SalesIndex` | `SalesModal` with unique name validation and quota tracking |
| **System** | **12. Notifications** | `notifications.index`<br>`notifications.show` | `Notifications\NotificationIndex`<br>`Notifications\NotificationDetail` | `Components\NotificationBell`, `Components\NotificationDrawer`, deep linking |
| | **13. Activity Log** | `audit.index` | `Audit\AuditIndex` | Color-coded action timeline, user attribution (`ActivityLog`) |
| | **14. Settings** | `settings.index` | `Settings\SettingIndex` | `UserModal`, password complexity manager, threshold configurations |
| **Auth** | **Password Change** | `password.change` | `Auth\ForcePasswordChange` | Mandatory first-time password change screen triggered by `EnsurePasswordIsChanged` |

---

## Financial Engine & Calculations

Financial accounting rules are centralized within `App\Services\MoneyCalculationService` to ensure calculations match across dashboards, customer cards, project pages, and CSV exports:

### Core Formulations

* **Total Scheduled Value:**
  $$\text{Scheduled Gross} = \sum_{t \in \text{Terms}, \, t.\text{status} \neq \text{'Cancelled'}} t.\text{amount}$$

* **Net Cash Collected:**
  $$\text{Cash Received} = \sum_{p \in \text{Payments}} p.\text{amount}$$

* **Tax Deducted at Source (TDS):**
  $$\text{Withheld TDS} = \sum_{p \in \text{Payments}} p.\text{tds}$$

* **Remaining Balance Due:**
  $$\text{Balance Due} = \max\left(0.00, \; \text{Scheduled Gross} - \text{Cash Received} - \text{Withheld TDS}\right)$$

* **Overdue Balance:**
  $$\text{Overdue Amount} = \sum_{t \in \text{Terms}, \, t.\text{status} = \text{'Overdue'}} \max\left(0.00, \; t.\text{amount} - t.\text{received} - t.\text{tds}\right)$$

* **Collection Rate Percentage:**
  $$\text{Collection Rate (\%)} = \begin{cases} \text{round}\left(\frac{\text{Cash Received}}{\text{Scheduled Gross}} \times 100, 1\right) & \text{if } \text{Scheduled Gross} > 0 \\ 0.0 & \text{otherwise} \end{cases}$$

### Installment State Progression

1. **`Cancelled`:** Manually cancelled installment terms.
2. **`Paid`:** Cash received plus TDS equals or exceeds installment value ($(\text{Received} + \text{TDS}) \ge \text{Amount} - 0.01$).
3. **`Partial`:** Partial payment has been received, but an outstanding balance remains ($0 < (\text{Received} + \text{TDS}) < \text{Amount} - 0.01$).
4. **`Overdue`:** The current date exceeds the due date plus grace window ($\text{CurrentDate} > \text{DueDate} + \text{GraceDays}$) with an outstanding balance.
5. **`Pending`:** Future-dated installment with zero payments logged.

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
* **Cause:** Standalone Alpine was manually imported in `resources/js/app.js` (`import Alpine from 'alpinejs'; Alpine.start();`), creating two concurrent Alpine runtimes that detached Livewire's `wire:submit` and `wire:model` listeners.
* **Fix:** Remove the standalone import and startup invocation from `resources/js/app.js`. Livewire 4 boots Alpine automatically.

### 2. SQL Integrity Violation: `Column 'customer_code' (or 'project_code') doesn't have a default value`
* **Cause:** The form or service submitted `'code'` instead of `'customer_code'` or `'project_code'`.
* **Fix:** Ensure code generation assigns directly to `$validated['customer_code']` and `$validated['project_code']`. See [**`issues.md`**](file:///c:/wamp64/www/project-manager/issues.md) for affected files.

### 3. TypeError: `Cannot assign null to property of type float`
* **Cause:** Form fields bound to typed properties (e.g. `public float $cost = 0.0;`) receive empty strings `""` when cleared by users.
* **Fix:** Declare properties as nullable (`public ?float $cost = 0.0;`) and cast them within the component's `updated()` lifecycle hook.

### 4. Modals Fail to Open from Table Buttons
* **Cause:** The button used Alpine DOM `$dispatch('open-modal', 'modal-name')`, but the target modal component was registered globally in `resources/views/layouts/app.blade.php` outside the table's DOM hierarchy.
* **Fix:** Dispatch the event across Livewire components via `Livewire.dispatch('open-modal-name', { ... })`.

---

## Defects & Cleanup Audit (`issues.md`)

For a line-by-line defect register, column mismatch breakdown, and an audit of the purged legacy API controllers, unused services, and core JS files, consult:
📄 [**`issues.md` — Project Issues, Defect Registry & Unused Code Audit**](file:///c:/wamp64/www/project-manager/issues.md)

---

## License

This project is proprietary software belonging to **10X Agency**. Unauthorized copying, modification, or distribution is strictly prohibited.
