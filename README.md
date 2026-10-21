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

> [!TIP]
> **Comprehensive Master Technical Reference Available**: For complete technical specifications, architectural evolution history, full database schema & ERD, deep component matrix, and consolidated historical defect registry (ISS-001 through ISS-053), see [**`project.md`**](project.md).

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [System Architecture Diagram](#system-architecture-diagram)
- [Livewire 4 & Alpine Runtime Conventions](#livewire-4--alpine-runtime-conventions)
- [Directory Layout](#directory-layout)
- [Quickstart & Local Installation](#quickstart--local-installation)
- [Default Seed Credentials](#default-seed-credentials)
- [Livewire 4 Component Matrix](#livewire-4-component-matrix)
- [Financial Engine & Calculations](#financial-engine--calculations)
- [Production Deployment & Server Config](#production-deployment--server-config)
- [Operational Maintenance & Runbook](#operational-maintenance--runbook)
- [License](#license)

---

## Overview

**PJT CRM** is a single-tenant operations and financial management platform designed specifically for digital services agencies. It centralizes client onboarding, project delivery health monitoring, milestone tracking, multi-tier installment receivables, task checklists, client follow-ups, and native desktop notifications into a high-performance reactive full-stack web application.

The platform is engineered on **Laravel 13** and **Livewire 4**, providing single-page application (SPA) reactivity through `wire:navigate` and `Livewire.navigate()`, unified Alpine.js stores, dirty-state modal protection, real-time validation, native browser desktop alerts, and a dedicated Service Layer that enforces financial consistency, concurrency-safe code generation, and coordinator-scoped data security.

---

## Key Features

### 📊 1. Executive Dashboard & Real-Time KPIs

* **Receivables Strip:** Real-time financial aggregates including Total Scheduled Value `(sum(terms) where status != 'Cancelled')`, Collected Cash `(sum(payments))`, Net Due `(max(0, scheduled - received - tds))`, Overdue Installments `(sum(balance) where status = 'Overdue')`, and Current Month Collections `(sum(payments) where month(paid_on) = current_month)`.
* **Aging Buckets:** Automatic breakdown of delayed receivables `(overdue terms grouped by days past due: 0–30, 31–60, 61–90, 90+)` to highlight aged receivables risk.
* **Collection Trend Analysis:** Trailing 6-month visual revenue performance sparkline `(monthly sum(payments) over trailing 6 calendar months)`.
* **Daily Triage Queue:** Direct access to overdue payments `(overdue terms sorted by due_date ASC)`, approaching client follow-ups `(next_date <= today + followup_alert_days)`, and at-risk project deadlines `(health in ['Delayed', 'At Risk'] sorted by delivery_date ASC)`.
* **Commercial Performance:** Sales-wise contracted volume `(sum(project_value))`, collected revenue `(sum(payments))`, and collection progress bars `(round((collected / contracted) * 100, 1))`.
* **Operational Alerts:** Proactive tracking of upcoming Annual Maintenance Contract (AMC) expirations `(amc_required = true AND amc_end <= today + renew_alert_days)` and pending project handovers `(status = 'Delivered' AND handover_done = false)`.
* **Live Event Reactivity:** Listens for `payment-saved`, `payment-deleted`, `project-saved`, and `customer-saved` events to automatically refresh cards without full browser reloads.

### 🔔 2. Multi-Channel Notification Engine & Desktop Alerts

* **Interactive Slide-Over Drawer:** Slide-over notification panel (`NotificationDrawer`) accessible from the topbar bell icon, featuring real-time unread badges, filter tabs (`All` vs `Unread`), single mark-as-read, and bulk `Mark all as read`.
* **Native Desktop OS Banners:** Built-in client poller (`browserNotifier` Alpine module in `app.blade.php`) querying `GET /api/notifications/poll` every 30 seconds. Dispatches native operating system desktop alerts (`new Notification(...)`) with direct deep-linking on click.
* **Dedicated Notification Detail Page:** Full-page view (`/notifications/{notification}`) for inspectable alert details, direct target entity navigation (linked project or customer), and toggle read/unread actions.
* **Reactive Topbar Bell:** Real-time unread counter badge synchronized globally via the Alpine store (`$store.notifications`).

### 🔒 3. Account Security & Forced Password Change

* **Mandatory Initial Password Change:** Middleware (`EnsurePasswordIsChanged`) intercepts authenticated sessions when `must_change = true`, redirecting users to the dedicated `/change-password` interface.
* **Password Complexity Enforcement:** Strict validation requiring minimum 8 characters, at least 1 uppercase letter, 1 lowercase letter, and 1 numeric digit.
* **Downline Hierarchy & RBAC Data Scoping:** Self-referencing user hierarchy (`reporting_to`) allowing managers to track coordinator team activity and project delivery downlines `(recursively resolved via getDownlineUserIds())`. Strict local query scopes (`Customer::forUser()` and `Project::forUser()`) enforce data privacy across roles: Sales users see only self-attributed customer records and basic project overviews; Coordinators see assigned projects and clients with financial reports hidden; Managers see departmental downlines; and Admins retain full global visibility.
* **Financial Ledger Access Protection:** Sensitive money views (Payments, Payment Schedules, Reports) and column ledgers (Contract Value, Received, Due) are strictly gated and hidden from unauthorized sales personnel and coordinator tiers.

### 🎯 4. Today's Work Queue

* **Daily Action Command Center:** Prioritized view of overdue installments `(today > due_date + grace_days AND balance > 0)`, upcoming installments `(due_date between today AND today + payment_alert_days)`, pending follow-ups `(next_date <= today + followup_alert_days)`, deliverable risks `(health in ['Delayed', 'At Risk'])`, and assigned tasks `(assigned_to = auth_id AND done = false)`.
* **Interactive Triage:** Quick links to customer profiles and inline payment buttons pre-populating project and term parameters via `Livewire.dispatch('open-payment-modal', ...)`.

### 📁 5. Customer Lifecycle, Identity & Fallback Display

* **Comprehensive Client Dossiers:** Centralized customer profiles with tax IDs (GSTIN/PAN), multi-address billing records, 2-letter state codes, and industry tags.
* **Nullable Company Support:** Individual clients and sole proprietorships can be onboarded without a registered company name (`company` is nullable with unique constraints).
* **Deterministic Display Fallback:** Whenever a company name is not provided, the CRM displays `"{contact_name} (Client)"` (or `customer_code` if both are empty) across all data tables, dropdown selects, global search, and audit logs via `$customer->display_name` `(company ?: (contact_name ? "$contact_name (Client)" : customer_code))`.
* **Sales Attribution & Quota Tracking:** Track which salesperson onboarded each client `(customers.sales_person_id)` and monitor revenue vs. individual monthly targets configured in the `users.target` column `(achieved_% = (collected / target) * 100)`.
* **Bulk CSV Import:** Built-in spreadsheet parser supporting bulk client insertion with automatic atomic code generation (`CUS-XXXX`).
* **Export Engine:** Filtered, UTF-8 BOM-encoded CSV data exports compatible with Microsoft Excel.

### 🚀 6. Project Delivery, Milestones & AMC Tracking

* **Delivery Health States:** Discrete confidence states (`On Track`, `At Risk`, `Delayed`, `Completed`) with mandatory delay reasoning.
* **Mandatory Coordinator & Start Date:** Projects strictly enforce assignment of an active Project Coordinator (`coordinator_id`) and project start date (`start_date`) for operational accountability.
* **Reactive Customer Selection:** The Project Modal dynamically enables customer selection when creating new projects, and locks customer assignment (`@disabled($projectId)`) when editing existing contracts to preserve financial relationships.
* **Auto-Split 30/40/30 Installment Wizard:** Single-click schedule calculation dividing contracted project value into Advance `(round(value * 0.30, 2))`, Mid-project `(round(value * 0.40, 2))`, and Final Delivery balance `(value - term1 - term2)`. Maps to delivery milestones when present, or staggers due dates (+0, +15, +30 days from start_date) if milestones have not yet been defined.
* **Interactive Milestones:** Dynamic milestone checklist with completion checkboxes, due dates, sort ordering, and payment trigger linking.
* **Annual Maintenance Contracts (AMC):** Dedicated contract tracking with configurable alert lead times and zero-default normalization (`cost`, `amc_amount`, `renew_alert_days`).
* **Handover Governance:** Post-delivery checklist and formal client handover documentation.

### 💳 7. Financial Receivables & Installment Engine

* **Automated Status Transitions:** Installments automatically transition between `Pending` `(payments = 0)`, `Partial` `(0 < received + tds < amount - 0.01)`, `Paid` `(received + tds >= amount - 0.01)`, and `Overdue` `(today > due_date + grace_days AND balance > 0)` based on actual receipts, TDS deductions, and grace periods.
* **Zero-Default TDS Handling:** Normalized numerical inputs prevent MySQL `1048 Column 'tds' cannot be null` errors on untaxed settlements.
* **Concurrency-Safe Sequence Generation:** `CodeGeneratorService` uses exclusive database row locks (`FOR UPDATE`) with automated counter self-healing `(next_code = max(counter, max(db_suffix)) + 1)` to prevent duplicate key collisions.
* **Dual Currency Display:** Live in-memory toggle between INR (`₹`) and USD (`$`) via an Alpine store (`$store.currency`) initialized on `alpine:init`, fully preserved across SPA page transitions.

### 📋 8. Tasks, Follow-ups, Documents, Audit & Password Administration

* **Personal Checklist:** Coordinator task management with priority levels (`Low`, `Medium`, `High`, `Urgent`) and overdue indicators `(due_date < today AND done = false)`.
* **Touchpoint Logger:** Multi-channel client interaction logging (`Call`, `Email`, `Meeting`, `WhatsApp`, `Note`) with next action alarms and role-scoped project binding.
* **Document Management:** Secure storage of contracts, KYC documents, and delivery proofs up to 10 MB with public disk root isolation and in-browser PDF/image lightbox previews.
* **Activity Audit Trail:** Immutable log of system mutations (`ActivityLog` / `activity_logs` table) recording creates, updates, payments, uploads, and deletions.
* **Admin-Mediated Password Reset Workflow:** 1-Click temporary credential generation `(Temp{Year}@{PIN})` from `/settings`, automatically clearing account lockouts `(fail_count = 0, locked_until = null)`, setting `must_change = true`, logging audit trails, and dispatching in-app alerts without third-party email dependencies.

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
        RoleScope --> M5["Records: DocumentIndex, DocumentUploadModal, DocumentPreviewModal, FollowupIndex, SalesIndex"]
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
   Navigating between views from clickable table rows must invoke `Livewire.navigate('url')` rather than full-page `window.location` browser reloads. This maintains active Alpine store states (`$store.currency`, `$store.notifications`) without flickering.
4. **In-App Confirmation Modal System (`<x-confirm-modal>`)**:
   Destructive actions (deletions, logouts, unlinking, mark-all-read) and form modal discards use the unified in-app confirmation component (`<x-confirm-modal/>`) rather than native browser `confirm()` or `wire:confirm`. The component provides hardware-accelerated frosted glass backdrop blur (`backdrop-filter: blur(8px)`), distinct contextual operation icons (danger, warning, logout, info), customizable actions, and outside-click collision guards (`data-confirm-modal`).
5. **Modal Unsaved Changes Guard (`isDirty`)**:
   The `<x-modal>` component listens for DOM `@input` and `@change` events to mark forms as dirty. Attempting to dismiss an unsubmitted form via outside click, `Escape`, or Cancel triggers the in-app confirmation modal (`<x-confirm-modal>`) asking the user to confirm discarding unsaved changes. The "Keep Editing" button cleanly dismisses the confirmation dialog without triggering event loop collisions or closing the form.
6. **Real-Time Blur Validation**:
   Inputs utilize `wire:model.blur` combined with `$this->validateOnly($propertyName)` inside the `updated()` lifecycle hook. Invalid inputs display an active red border (`border-red-500! bg-red-50/20!`) and inline error labels beneath the field without rendering disruptive alert banners.
7. **Filter Visibility & Dynamic Clear Action**:
   Filter action buttons (e.g. "Clear filters" or "Reset filters") are conditionally displayed across data index tables only when active filters, status scopes, or search strings are applied.
8. **Navigation & Dynamic Back History**:
   Secondary views and detail pages use `window.history.length > 1 ? window.history.back() : window.location.href = '...'` to navigate back to the user's previous filtered list state instead of hardcoded route jumps.
9. **Schema Null vs. Default Synchronization**:
   For database columns defined as `NOT NULL DEFAULT 0.00` (e.g., `projects.cost`, `projects.amc_amount`, `payments.tds`, `users.target`), Livewire components explicitly convert empty strings (`""`) to `0.00` before persistence, preventing MySQL `1048 Column cannot be null` errors.

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
│   │   │   └── SalesIndex.php                      # Salesperson revenue attribution, targets, and comparative table
│   │   ├── Schedule/
│   │   │   └── ScheduleIndex.php                   # Master chronological installment timeline and CSV export
│   │   ├── Settings/
│   │   │   ├── SettingIndex.php                    # Operational threshold configuration, password manager, and user list
│   │   │   └── UserModal.php                       # Staff and sales account creator/editor with quota and credential provisioning
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
| | **8. Reports** | `reports.index` | `Reports\ReportIndex` | Executive analytics: collection rate % `((received / scheduled) * 100)`, trailing 6-month sparkline `(monthly sum(payments))`, mode bars `(sum(payments) grouped by mode)`, aging bars `(overdue terms bucketed: 0–30, 31–60, 61–90, 90+ days)`, and delivery variance table `(milestone_% - collection_%)` |
| **Records** | **9. Documents** | `documents.index` | `Documents\DocumentIndex` | `DocumentUploadModal` (public disk root), `DocumentPreviewModal` (lightbox) |
| | **10. Follow-ups** | `followups.index` | `Followups\FollowupIndex` | Touchpoint feed, overdue alert banner, scoped project relations |
| | **11. Sales Team** | `sales.index` | `Sales\SalesIndex` | Shared `UserModal` for quota tracking and credential management |
| **System** | **12. Notifications** | `notifications.index`<br>`notifications.show` | `Notifications\NotificationIndex`<br>`Notifications\NotificationDetail` | `Components\NotificationBell`, `Components\NotificationDrawer`, deep linking |
| | **13. Activity Log** | `audit.index` | `Audit\AuditIndex` | Color-coded action timeline, user attribution (`ActivityLog`) |
| | **14. Settings** | `settings.index` | `Settings\SettingIndex` | `UserModal`, password complexity manager, threshold configurations |
| **Auth** | **Password Change** | `password.change` | `Auth\ForcePasswordChange` | Mandatory first-time password change screen triggered by `EnsurePasswordIsChanged` |

---

## Financial Engine, Business Logic & Calculations

Financial accounting rules and domain automations are centralized within `App\Services\MoneyCalculationService`, `App\Services\CodeGeneratorService`, and model accessors to ensure consistent, predictable business logic throughout the application:

### 1. Core Financial Formulations (`MoneyCalculationService`)

* **Total Scheduled Value**:
  * **Formula**: `scheduled = sum(term_amount) [where status != 'Cancelled']`
  * **Working**: Sums active contract payment terms. Cancelled terms are excluded so only live commitments are counted.

* **Net Cash Collected**:
  * **Formula**: `received = sum(payment_amount)`
  * **Working**: Accumulates all verified monetary receipts across all payment channels (NEFT, RTGS, UPI, Cash, Cheque, Card).

* **Tax Deducted at Source (TDS)**:
  * **Formula**: `tds = sum(tds_amount)`
  * **Working**: In statutory compliance with Indian tax regulations, clients withhold TDS prior to settlement. The CRM credits this withheld tax directly against the invoice term balance alongside received cash.

* **Remaining Balance Due**:
  * **Formula**: `balance_due = max(0, scheduled - received - tds)`
  * **Working**: Reflects outstanding receivables owed by clients. Clamped to `0.00` using `max(0, ...)` to prevent negative balance display in the event of minor rounding differences or overpayments.

* **Overdue Balance**:
  * **Formula**: `overdue = sum(term_balance) [where status = 'Overdue']`
  * **Working**: Identifies delinquent revenue requiring immediate coordinator follow-up.

* **Collection Rate Percentage**:
  * **Formula**: `collection_rate_% = (received / scheduled) * 100` (rounded to 1 decimal place; `0.0%` if scheduled == 0)
  * **Working**: Evaluates financial collection velocity across agency projects, customers, and salesperson portfolios.

### 2. Installment State Machine & Transition Rules

Installment statuses automatically refresh during read queries and payment entries based on the following deterministic criteria:
1. **`Cancelled`**: Explicitly voided installment terms.
2. **`Paid`**: `received + tds >= amount - 0.01` (fully cleared within 1-cent floating point tolerance).
3. **`Partial`**: `0 < received + tds < amount - 0.01` (settlement initiated, outstanding balance remains).
4. **`Overdue`**: `today > due_date + grace_days` AND `balance_due > 0`.
5. **`Pending`**: `today <= due_date + grace_days` AND `received + tds == 0`.

### 3. Auto-Split 30/40/30 Installment Wizard (`ProjectScheduleModal`)

* **Formula**:
  * **Advance (Term 1)**: `round(value * 0.30, 2)` (30% upfront retainer)
  * **Mid-Project (Term 2)**: `round(value * 0.40, 2)` (40% milestone billing)
  * **Final Delivery (Term 3)**: `value - term1 - term2` (30% remaining balance, guaranteeing exact penny settlement with zero floating-point drift)
* **Working**:
  * If project milestones exist: Links Term 1 to Milestone 1, Term 2 to the middle milestone, and Term 3 to the final milestone, synchronizing payment due dates with milestone target dates.
  * If no milestones exist: Gracefully staggers installment due dates (+0 days for Advance, +15 days for Mid-Project, and +30 days for Final Delivery) without throwing validation errors.

### 4. Concurrency-Safe Sequence Engine & Counter Auto-Healing (`CodeGeneratorService`)

* **Formula**: `next_counter = max(current_counter, max(db_suffix)) + 1`
* **Working**:
  1. Issues an exclusive row-level lock: `SELECT value FROM counters WHERE name = ? FOR UPDATE`.
  2. Queries the target table for the highest existing numerical suffix (`MAX(CAST(SUBSTRING(code, prefix_length + 1) AS UNSIGNED))`).
  3. If the counter value has fallen behind existing records (due to seeders or manual SQL inserts), it automatically updates the counter ceiling to match reality before generating the next formatted code (e.g. `CUS-0005`, `PJT-0008`, `RCP-0004`). This permanently prevents `500 UniqueConstraintViolationException` errors.

### 5. Customer Identity & Fallback Display Rule (`Customer` Model)

* **Formula**:
  * When `company` is provided: `Company Name` (e.g. *"Apex Global Logistics Pvt Ltd"*)
  * When `company` is absent: `Contact Person (Client)` (e.g. *"Rajesh Khanna (Client)"*)
  * When both are empty: `Customer Code` (e.g. *"CUS-0008"*)
* **Working**: Centralized in the `display_name` Eloquent accessor (`$customer->display_name`), ensuring clean, non-empty labels across all tables, modal dropdown selects, global search results, and activity audit logs.

### 6. Admin-Mediated Password Reset Workflow (`SettingIndex`)

* **Formula**: `Temporary Password = "Temp" . Current Year . "@" . 4-Digit Secure Random PIN` (e.g. `"Temp2026@4892"`)
* **Working**: Generates a compliant temporary password on-demand from the Settings user list, resets failed login counters (`fail_count = 0`), clears lockout timestamps (`locked_until = null`), flags `must_change = true`, records an audit log entry, and dispatches an in-app alert to the user.

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

## Operational Maintenance & Runbook

### 1. Application Cache & Optimization
After deploying code updates or modifying environment configurations, clear and rebuild application caches:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Frontend Asset Recompilation
When modifying Blade templates, Tailwind classes, or Alpine components:
```bash
# Production production build
npm run build

# Local live-reload development
npm run dev
```

### 3. Public File Storage Verification
Ensure user-uploaded KYC documents, contracts, and proposals are publicly accessible:
```bash
php artisan storage:link
```
Verify that `storage/app/public` exists and web server has read/write permissions on `storage/` and `bootstrap/cache/`:
```bash
chmod -R 775 storage bootstrap/cache
```

### 4. Sequence Generator & Counter Synchronization
Entity identifiers (`CUS-XXXX`, `PJT-XXXX`, `RCP-XXXX`) utilize row-locking with automated ceiling self-healing. If seeders or manual SQL records are inserted directly into the database, `CodeGeneratorService` automatically reconciles the counter table to `max(counter, highest existing suffix) + 1` upon the next create request without manual intervention.

### 5. Desktop Notification Polling Verification
Verify the lightweight 30-second polling endpoint responds with valid JSON for the authenticated user session:
```bash
# Inspect API route list
php artisan route:list --path=api/notifications/poll
```
In the browser network tab, verify `GET /api/notifications/poll` returns status `200 OK` with payload `{"unread_count": N, "notifications": [...]}`.

---

## License

This project is proprietary software belonging to **10X Agency**. Unauthorized copying, modification, or distribution is strictly prohibited.
