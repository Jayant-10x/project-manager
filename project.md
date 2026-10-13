# 10X Agency — Project Coordinator CRM (`PJT CRM`)

**System Architecture, Database Specification, Livewire 4 Component Matrix & Implementation Guide**

---

## 1. System Executive Summary & Architecture

### 1.1 Platform Purpose & Scope

**PJT CRM** is an operations and financial management system designed specifically for digital agencies[cite: 1]. It
bridges the gap between sales handoff, delivery coordination, milestone execution, and receivables collection[cite: 1].
The system provides role-based access for two user tiers[cite: 1]:

* **Administrators (`Admin`)**: Possess unrestricted governance over system settings, global alert thresholds, user
  management, salesperson commission targets, and destructive entity cascades (customer and project deletion)[cite: 1].
* **Project Coordinators (`Coordinator`)**: Manage day-to-day client onboarding, project health and delivery milestones,
  multi-tier installment schedules, incoming payment receipts, document uploads, personal task checklists, and scheduled
  follow-ups[cite: 1]. Access is strictly scoped to assigned accounts via query scopes (`scopeForUser()`)[cite: 1].

### 1.2 System Architecture Diagram

The platform is built on **Laravel 13** and **Livewire 4**, providing a reactive full-stack single-page application (
SPA)
architecture powered by `wire:navigate`, Alpine.js stores, and Tailwind CSS alongside `crm-style.css`[cite: 1].

```mermaid
graph TD
    subgraph Client Layer [Client Layer (Browser)]
        UI["Livewire 4 Reactive Views & Blade Components"]
        ModalGuard["Alpine.js Modal Component & Unsaved Changes Guard (isDirty)"]
        AlpineStore["Alpine.js Stores ($store.currency, Sidebar State)"]
        LocalStorage["LocalStorage (pjt_cur, pjt_sidebar_collapsed)"]
        UI <--> ModalGuard
        UI <--> AlpineStore
        AlpineStore <--> LocalStorage
    end

    subgraph Entry Points [HTTP Routing & Middleware]
        WebRoute["routes/web.php<br/>(Livewire Full-Page Component Routing with wire:navigate)"]
        AuthMiddleware["Middleware: auth, verified"]
        RoleScope["Eloquent Query Scope: scopeForUser()"]
    end

    subgraph Livewire Component Layer
        NavComponents["Navigation Components<br/>(GlobalSearch, NotificationBell)"]
        OverviewModules["Overview Modules<br/>(DashboardIndex, TodayWorkIndex)"]
        CustomerModules["Customer Modules<br/>(CustomerIndex, CustomerDetail, CustomerModal, CustomerImportModal)"]
        ProjectModules["Project Modules<br/>(ProjectIndex, ProjectDetail, ProjectModal, ScheduleModal, RecurringModal, MilestoneModal)"]
        MoneyModules["Money Modules<br/>(PaymentIndex, PaymentModal, ScheduleIndex, ReportIndex)"]
        RecordModules["Record Modules<br/>(DocumentIndex, DocumentUploadModal, DocumentPreviewModal, FollowupIndex, SalesIndex, SalesModal)"]
        SystemModules["System Modules<br/>(NotificationIndex, AuditIndex, SettingIndex, UserModal, ProfileController)"]
    end

    subgraph Domain Services Layer
        MoneyService["MoneyCalculationService<br/>(Financial Aggregations & Status Automations)"]
        CodeService["CodeGeneratorService<br/>(Atomic Row-Locking Sequence Engine)"]
        CurrencyService["CurrencyRateService<br/>(USD / INR Conversion Rates)"]
    end

    subgraph Persistence Layer
        Eloquent["Eloquent ORM Models (Relationships & Scopes)"]
        MySQL[("MySQL 8.0+ Database")]
        DiskStorage[("Local Public Disk Storage (storage/app/public)")]
    end

    UI -->|User Interactions / Livewire.dispatch / wire:navigate| WebRoute
    WebRoute --> AuthMiddleware --> RoleScope
    RoleScope --> NavComponents & OverviewModules & CustomerModules & ProjectModules & MoneyModules & RecordModules & SystemModules

    CustomerModules & ProjectModules & MoneyModules & OverviewModules --> MoneyService
    CustomerModules & ProjectModules & MoneyModules --> CodeService
    NavComponents --> CurrencyService

    MoneyService & CodeService --> Eloquent
    RecordModules --> DiskStorage
    Eloquent --> MySQL
```

### 1.3 Architectural Migration, Modernization & Livewire 4 Standards

The platform was migrated from a procedural, flat-file script (`index.php`, `api.php`, `config.php`) to **Laravel 13
with Livewire 4** while preserving feature parity, visual design, and relational integrity[cite: 1]:

* **Bundled Alpine Runtime**: Livewire 4 bundles and boots Alpine.js automatically[cite: 4]. Standalone imports (
  `import Alpine from 'alpinejs'; Alpine.start();`) are completely omitted from `resources/js/app.js` to eliminate race
  conditions and event listener detachment[cite: 4].
* **CSRF Token & Store Bootstrapping**: A global `<meta name="csrf-token" content="{{ csrf_token() }}">` is present in
  the layout header, and Alpine stores (such as `$store.currency`) initialize on the `alpine:init` event hook to
  guarantee initialization prior to component mounting[cite: 3].
* **Real-Time Client Navigation**: Livewire 4 `wire:navigate` and `Livewire.navigate('...')` replace legacy
  `window.location` browser redirects[cite: 1, 19], preserving in-memory Alpine currency states and layout shell states
  across page transitions[cite: 1].
* **Decoupled Cross-Component Communication**: Components invoke `Livewire.dispatch('event-name', payload)` for
  cross-DOM triggers instead of Alpine `$dispatch`[cite: 10, 12], ensuring layout-level modal components listen and
  respond reliably across distinct DOM trees[cite: 3].
* **Event-Driven UI Refreshing**: Data tables and index components listen for mutation events (
  `#[On('customer-saved')]`, `#[On('project-saved')]`, `#[On('payment-saved')]`, `#[On('document-saved')]`,
  `#[On('sales-saved')]`, `#[On('user-saved')]`)[cite: 11] to reload views and KPIs without hard page reloads.
* **Unsaved Changes Safeguard**: The modal container (`<x-modal>`) monitors user modification states (`isDirty`),
  prompting a confirmation warning before closing on outside clicks, `Escape` key presses, or Cancel clicks[cite: 10].
* **Real-Time Blur Validation**: Livewire properties bind with `wire:model.blur`[cite: 20] and invoke
  `$this->validateOnly($propertyName)` within the `updated()` lifecycle hook, providing immediate inline validation with
  distinct red error borders without noisy top alert banners.
* **Database Type Synchronization**: Optional strings submitted through form selects are normalized to `null` or
  explicit database defaults (e.g. `0.00` for numeric columns defined as `NOT NULL DEFAULT 0.00`) to prevent MySQL
  `1048 Column cannot be null` constraint violations[cite: 20].

---

## 2. Directory Structure & File Map

```text
pjt-crm/
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
│   ├── Models/
│   │   ├── AuditLog.php                        # Immutable system activity log
│   │   ├── Counter.php                         # Atomic sequence counters for entity codes
│   │   ├── Customer.php                        # Customer model with project, document, and sales relationships
│   │   ├── Document.php                        # Document metadata records linked to storage
│   │   ├── Followup.php                        # Client communication history and follow-up dates
│   │   ├── Milestone.php                       # Project milestones and completion stamps
│   │   ├── Notification.php                    # System notifications indexed by user ID
│   │   ├── Payment.php                         # Payment receipts linked to projects and terms
│   │   ├── PaymentTerm.php                     # Payment installment schedules and status tracking
│   │   ├── Project.php                         # Project entity with monetary accessors and role scopes
│   │   ├── SalesPerson.php                     # Sales registry for onboarding attribution
│   │   ├── Setting.php                         # Key-value global configuration store
│   │   ├── Task.php                            # Actionable checklist items
│   │   └── User.php                            # Authenticatable staff accounts with role helpers
│   └── Services/
│       ├── CodeGeneratorService.php            # Transaction-safe sequential identifier generator
│       ├── CurrencyRateService.php             # Exchange rate configuration and conversion service
│       └── MoneyCalculationService.php         # Financial calculation engine and overdue status updater
├── database/
│   ├── migrations/                             # Database migrations defining tables and foreign keys
│   └── seeders/
│       ├── DatabaseSeeder.php                  # Master database seeder
│       └── UserSeeder.php                      # Seeds default Administrator and Coordinator accounts
├── public/
│   ├── assets/
│   │   └── logo.png                            # Static brand identity asset
│   └── css/
│       └── crm-style.css                       # Complete application design and responsive layout rules
├── resources/
│   ├── css/
│   │   └── app.css                             # Tailwind CSS input styles
│   ├── js/
│   │   └── app.js                              # Client application entry point (Livewire-managed Alpine)
│   └── views/
│       ├── components/
│       │   ├── health-badge.blade.php          # Reusable project health indicator badge
│       │   ├── kpi-card.blade.php              # Metric card with icons and trends
│       │   ├── modal.blade.php                 # Alpine-driven modal dialog container with isDirty confirmation
│       │   └── money.blade.php                 # Reactive currency formatting component
│       ├── layouts/
│       │   ├── app.blade.php                   # Master layout with mini-sidebar toggle and global modals
│       │   └── partials/
│       │       └── sidebar.blade.php           # 14-module navigation sidebar with live count badges
│       └── livewire/                           # Paired Blade templates for all Livewire components
└── routes/
    ├── auth.php                                # Laravel Breeze authentication routes
    └── web.php                                 # Authenticated routes mapping all 14 Livewire modules
```

---

## 3. Database Schema & Data Modeling

### 3.1 Entity-Relationship Diagram (ERD)

```mermaid
erDiagram
    USERS {
        bigint id PK
        string email UK
        string name
        string role
        string password
        string pass_hash
        string designation
        string city
        string phone
        string status
        boolean must_change
        timestamp last_seen
        date joined
    }

    COUNTERS {
        string name PK
        bigint value
    }

    SETTINGS {
        string k PK
        text v
    }

    SALES_PERSONS {
        bigint id PK
        string name UK
        string phone
        string email
        decimal target
        string status
    }

    CUSTOMERS {
        bigint id PK
        string code UK
        string company UK
        string contact_name
        string email
        string phone
        string alt_phone
        string city
        string state
        text address
        string gstin
        string pan
        string billing_name
        text billing_addr
        string source
        string industry
        bigint sales_person_id FK
        string status
        text internal_notes
        bigint created_by_id FK
    }

    PROJECTS {
        bigint id PK
        string code UK
        bigint customer_id FK
        string name
        text description
        string project_type
        bigint coordinator_id FK
        decimal value
        decimal cost
        date start_date
        date delivery_date
        date actual_end
        string status
        string health
        string delay_reason
        boolean amc_required
        date amc_start
        date amc_end
        decimal amc_amount
        smallint renew_alert_days
        boolean handover_done
        text handover_notes
        text notes
        bigint created_by_id FK
    }

    MILESTONES {
        bigint id PK
        bigint project_id FK
        string name
        date due_date
        boolean done
        date done_on
        string status
        smallint sort_order
        text notes
    }

    PAYMENT_TERMS {
        bigint id PK
        bigint project_id FK
        smallint seq
        string label
        decimal amount
        decimal percent
        string trigger_type
        bigint milestone_id FK
        date due_date
        smallint grace_days
        string status
        decimal received
        decimal tds
        text notes
    }

    PAYMENTS {
        bigint id PK
        string code UK
        bigint project_id FK
        bigint customer_id FK
        bigint term_id FK
        decimal amount
        decimal tds
        date paid_on
        string mode
        string reference
        string bank
        text note
        bigint entered_by_id FK
    }

    DOCUMENTS {
        bigint id PK
        bigint customer_id FK
        bigint project_id FK
        string category
        string title
        string file_name
        string stored_as
        string mime
        int size_kb
        bigint uploaded_by_id FK
    }

    FOLLOWUPS {
        bigint id PK
        bigint customer_id FK
        bigint project_id FK
        string kind
        string summary
        text detail
        timestamp happened_on
        date next_date
        string next_note
        bigint created_by_id FK
    }

    TASKS {
        bigint id PK
        bigint project_id FK
        bigint customer_id FK
        string title
        date due_date
        boolean done
        string priority
        bigint assigned_to_id FK
        bigint created_by_id FK
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        string kind
        string title
        string body
        string link_type
        bigint link_id
        boolean is_read
    }

    AUDIT_LOG {
        bigint id PK
        bigint user_id FK
        string action
        string entity
        bigint entity_id
        string entity_code
        text detail
        timestamp at_time
    }

    SALES_PERSONS ||--o{ CUSTOMERS : "attributes"
    USERS ||--o{ CUSTOMERS : "onboards"
    USERS ||--o{ PROJECTS : "coordinates"
    USERS ||--o{ PAYMENTS : "enters"
    USERS ||--o{ DOCUMENTS : "uploads"
    USERS ||--o{ FOLLOWUPS : "logs"
    USERS ||--o{ TASKS : "executes"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ AUDIT_LOG : "triggers"

    CUSTOMERS ||--o{ PROJECTS : "owns"
    CUSTOMERS ||--o{ PAYMENTS : "billed_to"
    CUSTOMERS ||--o{ DOCUMENTS : "attaches"
    CUSTOMERS ||--o{ FOLLOWUPS : "records"
    CUSTOMERS ||--o{ TASKS : "targets"

    PROJECTS ||--o{ MILESTONES : "tracks"
    PROJECTS ||--o{ PAYMENT_TERMS : "schedules"
    PROJECTS ||--o{ PAYMENTS : "settles"
    PROJECTS ||--o{ DOCUMENTS : "stores"
    PROJECTS ||--o{ TASKS : "contains"

    MILESTONES ||--o{ PAYMENT_TERMS : "triggers"
    PAYMENT_TERMS ||--o{ PAYMENTS : "credits"
```

### 3.2 Data Dictionary & Foreign Key Constraints

| Table                    | Column                  | Type                                   | Constraints / Attributes                                                  | Description                                  |
|:-------------------------|:------------------------|:---------------------------------------|:--------------------------------------------------------------------------|:---------------------------------------------|
| `users`[cite: 1]         | `id`[cite: 1]           | `BIGINT UNSIGNED`[cite: 1]             | `PRIMARY KEY, AUTO_INCREMENT`[cite: 1]                                    | Unique user identity[cite: 1].               |
|                          | `email`[cite: 1]        | `VARCHAR(160)`[cite: 1]                | `NOT NULL, UNIQUE`[cite: 1]                                               | Sign-in email address[cite: 1].              |
|                          | `role`[cite: 1]         | `ENUM('Admin','Coordinator')`[cite: 1] | `NOT NULL, DEFAULT 'Coordinator'`[cite: 1]                                | Role permission level[cite: 1].              |
|                          | `pass_hash`[cite: 1]    | `VARCHAR(255)`[cite: 1]                | `NULLABLE`                                                                | Legacy password hash compatibility[cite: 1]. |
|                          | `password`              | `VARCHAR(255)`                         | `NOT NULL`                                                                | Standard Bcrypt password hash.               |
| `customers`[cite: 1]     | `sales_person_id`       | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> sales_persons(id) ON DELETE SET NULL`[cite: 14] | Linked sales representative.                 |
|                          | `created_by_id`         | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Creator user ID.                             |
| `projects`[cite: 1]      | `customer_id`[cite: 1]  | `BIGINT UNSIGNED`[cite: 1]             | `NOT NULL, FOREIGN KEY -> customers(id) ON DELETE CASCADE`[cite: 1]       | Parent customer reference[cite: 1].          |
|                          | `coordinator_id`        | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Assigned project coordinator.                |
|                          | `cost`                  | `DECIMAL(14,2)`                        | `NOT NULL, DEFAULT 0.00`[cite: 20]                                        | Internal delivery cost for margin tracking.  |
|                          | `amc_amount`            | `DECIMAL(14,2)`                        | `NOT NULL, DEFAULT 0.00`[cite: 20]                                        | Annual maintenance contract value.           |
|                          | `renew_alert_days`      | `SMALLINT UNSIGNED`                    | `NOT NULL, DEFAULT 7`[cite: 20]                                           | Alert threshold lead-time in days.           |
|                          | `created_by_id`         | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Creator user ID.                             |
| `milestones`[cite: 1]    | `project_id`[cite: 1]   | `BIGINT UNSIGNED`[cite: 1]             | `NOT NULL, FOREIGN KEY -> projects(id) ON DELETE CASCADE`[cite: 1]        | Target project reference[cite: 1].           |
| `payment_terms`[cite: 1] | `project_id`[cite: 1]   | `BIGINT UNSIGNED`[cite: 1]             | `NOT NULL, FOREIGN KEY -> projects(id) ON DELETE CASCADE`[cite: 1]        | Associated project reference[cite: 1].       |
|                          | `milestone_id`[cite: 1] | `BIGINT UNSIGNED`[cite: 1]             | `NULLABLE, FOREIGN KEY -> milestones(id) ON DELETE SET NULL`[cite: 1]     | Optional linked milestone trigger[cite: 1].  |
| `payments`[cite: 1]      | `project_id`[cite: 1]   | `BIGINT UNSIGNED`[cite: 1]             | `NOT NULL, FOREIGN KEY -> projects(id) ON DELETE CASCADE`[cite: 1]        | Credited project reference[cite: 1].         |
|                          | `term_id`[cite: 1]      | `BIGINT UNSIGNED`[cite: 1]             | `NULLABLE, FOREIGN KEY -> payment_terms(id) ON DELETE SET NULL`[cite: 1]  | Optional credited installment[cite: 1].      |
|                          | `tds`                   | `DECIMAL(14,2)`                        | `NOT NULL, DEFAULT 0.00`                                                  | Tax Deducted at Source (TDS).                |
|                          | `entered_by_id`         | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Recording coordinator ID.                    |
| `documents`[cite: 1]     | `customer_id`[cite: 1]  | `BIGINT UNSIGNED`[cite: 1]             | `NOT NULL, FOREIGN KEY -> customers(id) ON DELETE CASCADE`[cite: 1]       | Parent customer reference[cite: 1].          |
|                          | `project_id`[cite: 1]   | `BIGINT UNSIGNED`[cite: 1]             | `NULLABLE, FOREIGN KEY -> projects(id) ON DELETE CASCADE`[cite: 1]        | Optional project reference[cite: 1].         |
|                          | `uploaded_by_id`        | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Uploader user ID.                            |
| `followups`[cite: 1]     | `customer_id`[cite: 1]  | `BIGINT UNSIGNED`[cite: 1]             | `NOT NULL, FOREIGN KEY -> customers(id) ON DELETE CASCADE`[cite: 1]       | Contacted customer reference[cite: 1].       |
|                          | `created_by_id`         | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Author user ID.                              |
| `tasks`[cite: 1]         | `assigned_to_id`        | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Responsible coordinator.                     |
|                          | `created_by_id`         | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Creator user ID.                             |
| `notifications`[cite: 1] | `user_id`               | `BIGINT UNSIGNED`                      | `NOT NULL, FOREIGN KEY -> users(id) ON DELETE CASCADE`[cite: 14]          | Recipient staff account.                     |
| `audit_log`[cite: 1]     | `user_id`               | `BIGINT UNSIGNED`                      | `NULLABLE, FOREIGN KEY -> users(id) ON DELETE SET NULL`[cite: 14]         | Performing user identity.                    |

---

## 4. Domain Logic & Business Rules

### 4.1 Financial Calculation Formulations (`MoneyCalculationService`)

Financial figures are centralized in `MoneyCalculationService` and accessible via the `getMoneyAttribute` accessor on
the `Project` model[cite: 1]:

$$\text{Scheduled Gross} = \sum_{t \in \text{Terms}, \, t.\text{status} \neq \text{'Cancelled'}} t.\text{amount}$$

$$\text{Cash Received} = \sum_{p \in \text{Payments}} p.\text{amount}$$

$$\text{Withheld TDS} = \sum_{p \in \text{Payments}} p.\text{tds}$$

$$\text{Balance Due} = \max\left(0.00, \; \text{Scheduled Gross} - \text{Cash Received} - \text{Withheld TDS}\right)$$

$$\text{Overdue Amount} = \sum_{t \in \text{Terms}, \, t.\text{status} = \text{'Overdue'}} \max\left(0.00, \; t.\text{amount} - t.\text{received} - t.\text{tds}\right)$$

$$\text{Collection Rate (\%)} = \begin{cases} \text{round}\left(\frac{\text{Cash Received}}{\text{Scheduled Gross}} \times 100, 1\right) & \text{if } \text{Scheduled Gross} > 0 \\ 0.0 & \text{otherwise} \end{cases}$$

### 4.2 Installment State Transitions

Installment states (`payment_terms.status`) are evaluated automatically based on logged receipts, tax deductions, due
dates, and grace periods[cite: 1]:

```mermaid
stateDiagram-v2
    [*] --> Pending: Term Created
    
    Pending --> Overdue: Today > (DueDate + GraceDays)
    Overdue --> Pending: DueDate Extended into Future
    
    Pending --> Partial: Payment Logged (Received + TDS < Amount - 0.01)
    Overdue --> Partial: Payment Logged (Received + TDS < Amount - 0.01)
    
    Partial --> Paid: (Received + TDS) >= (Amount - 0.01)
    Pending --> Paid: Full Payment (Received + TDS >= Amount - 0.01)
    Overdue --> Paid: Full Payment (Received + TDS >= Amount - 0.01)
    
    Paid --> Partial: Payment Deleted (Remaining > 0)
    Partial --> Pending: All Payments Deleted (Received = 0)
    
    Pending --> Cancelled: Manual Cancellation
    Partial --> Cancelled: Manual Cancellation
    Overdue --> Cancelled: Manual Cancellation
```

* **Paid Condition**: Evaluated with a floating-point tolerance of $0.01$ to prevent rounding discrepancies[cite: 1]:
  $$(t.\text{received} + t.\text{tds}) \ge (t.\text{amount} - 0.01)$$
* **Overdue Evaluation**: Executed automatically via SQL date arithmetic in `refreshOverdueStatus()` without loading
  models into memory:
  $$\text{DATE\_ADD}(t.\text{due\_date}, \, \text{INTERVAL } t.\text{grace\_days} \text{ DAY}) < \text{CURDATE}()$$

### 4.3 Aging Buckets Engine

Overdue receivables are segmented into four distinct aging intervals[cite: 1]:

$$\text{Days Overdue} = \left\lfloor \frac{\text{CurrentDate} - \text{DueDate}}{86400} \right\rfloor$$

$$\text{Bucket}(t) = \begin{cases} \text{'0-30 days'}, & \text{if } \text{Days Overdue} \le 30 \\ \text{'31-60 days'}, & \text{if } 31 \le \text{Days Overdue} \le 60 \\ \text{'61-90 days'}, & \text{if } 61 \le \text{Days Overdue} \le 90 \\ \text{'90+ days'}, & \text{if } \text{Days Overdue} > 90 \end{cases}$$

### 4.4 Concurrency-Safe Identifier Sequencing (`CodeGeneratorService`)

Entity identifiers (`CUS-0001`, `PJT-0001`, `RCP-0001`) are generated sequentially using database-level row locking over
the `counters` table inside a transaction[cite: 1]:

```php
public function generate(string $name, string $prefix = ''): string
{
    return DB::transaction(function () use ($name, $prefix) {
        $counter = DB::table('counters')->where('name', $name)->lockForUpdate()->first();
        if (!$counter) {
            DB::table('counters')->insert(['name' => $name, 'value' => 1]);
            $val = 1;
        } else {
            $val = $counter->value + 1;
            DB::table('counters')->where('name', $name)->update(['value' => $val]);
        }
        return $prefix . str_pad((string)$val, 4, '0', STR_PAD_LEFT);
    });
}
```

---

## 5. Security & Authentication Architecture

### 5.1 Dual-Column Password Authentication

To maintain backward compatibility with legacy password hashes while supporting standard Laravel authentication, the
`User` model overrides `getAuthPassword()`[cite: 1]:

```php
public function getAuthPassword(): string
{
    return $this->pass_hash ?? $this->attributes['password'] ?? '';
}

public function setPasswordAttribute($value): void
{
    $hashed = Hash::needsRehash($value) ? Hash::make($value) : $value;
    $this->attributes['password'] = $hashed;
    $this->attributes['pass_hash'] = $hashed;
}
```

### 5.2 Role Scoping & Data Isolation

Data access for Project Coordinators is scoped to their assigned records using the `scopeForUser()` query
scope[cite: 1]:

```php
public function scopeForUser(Builder $query, ?User $user = null): Builder
{
    $user = $user ?? auth()->user();
    if ($user && $user->role !== 'Admin') {
        return $query->where('coordinator_id', $user->id);
    }
    return $query;
}
```

### 5.3 Password Strength Validation Invariants

New passwords must satisfy four complexity rules[cite: 1]:

1. Minimum length of 8 characters[cite: 1]: $\text{strlen}(p) \ge 8$.
2. At least one uppercase character[cite: 1]: `regex:/[A-Z]/`.
3. At least one lowercase character[cite: 1]: `regex:/[a-z]/`.
4. At least one numerical digit[cite: 1]: `regex:/[0-9]/`.

---

## 6. Complete Module Catalog & Livewire 4 Component Architecture

The CRM consists of 14 integrated modules grouped across 5 navigation sections[cite: 1]:

### 6.1 Module 1: Dashboard (`/dashboard`)

* **Component**: `App\Livewire\Dashboard\DashboardIndex`
* **Features**:
    * 5 KPI cards: Total Receivable, Received (with collection rate %), Due, Overdue (with late installment count), and
      Current Month Collections[cite: 1].
    * Today's Work Mini-Triage: Highlights the top 3 overdue payments, top 3 follow-ups, and top 2 delayed/at-risk
      projects with direct navigation links[cite: 1].
    * Project Health: Visual breakdown across `On Track`, `At Risk`, `Delayed`, and `Completed` with proportional
      percentage bars[cite: 1].
    * 6-Month Collection Trend: Historical monthly collection chart with month labels[cite: 1].
    * Secondary Grids: Upcoming payments, overdue payments, sales attribution table, AMC renewal alerts, and pending
      handover checklists[cite: 1].
    * Horizontal Aging Buckets: Receivables segmented into `0-30`, `31-60`, `61-90`, and `90+` days[cite: 1].
    * Reactive Listeners: Listens for `payment-saved`, `payment-deleted`, `project-saved`, and `customer-saved` to
      refresh summary figures instantly.

### 6.2 Module 2: Today's Work (`/today`)

* **Component**: `App\Livewire\Today\TodayWorkIndex`
* **Features**:
    * Actionable daily queue showing pending touchpoints, upcoming collections, and urgent recovery tasks[cite: 1].
    * Summary KPIs: Overdue amount, Due Soon amount, Follow-ups count, and Open Tasks count[cite: 1].
    * Overdue Payments: Installment cards showing days overdue and inline `+ Payment` buttons pre-populating project and
      term parameters via `Livewire.dispatch('open-payment-modal', ...)`[cite: 1].
    * Upcoming Payments & Follow-up Calls: Grouped cards with color-coded due dates[cite: 1].
    * Projects Needing Attention: Table displaying delayed and at-risk deliverables with delay reasons and amounts
      due[cite: 1].

### 6.3 Module 3: Customers (`/customers`)

* **Components**:
    * `App\Livewire\Customers\CustomerIndex`: Customer directory with multi-field search, sales representative filter,
      city filter, payment status filter (`due`, `over`, `clear`), and CSV export[cite: 1]. Listens for
      `customer-saved`[cite: 11].
    * `App\Livewire\Customers\CustomerDetail`: Tabbed client view containing Overview, Projects, Payments, Documents,
      and Follow-ups[cite: 1].
    * `App\Livewire\Customers\CustomerModal`: Full modal supporting creation and editing across 16 fields (GSTIN, PAN,
      tax details, internal notes)[cite: 1]. Uses `Rule::unique` for company names, sanitizes empty foreign keys, and
      provides real-time field blur validation.
    * `App\Livewire\Customers\CustomerImportModal`: Bulk CSV parser supporting spreadsheet pasting and file
      uploads[cite: 1].

### 6.4 Module 4: Projects (`/projects`)

* **Components**:
    * `App\Livewire\Projects\ProjectIndex`: Project directory with health filtering, status filtering, contract vs.
      balance rollups, and SPA navigation (`Livewire.navigate`)[cite: 1]. Listens for `project-saved`.
    * `App\Livewire\Projects\ProjectDetail`: Project management workspace with health banners, milestone timelines,
      financial summaries, AMC terms, and handover sign-offs[cite: 1].
    * `App\Livewire\Projects\ProjectModal`: Contract creation and editing modal with internal margin cost
      tracking[cite: 1]. Enforces non-null zero defaults on `cost`, `amc_amount`, and `renew_alert_days`[cite: 20],
      implements real-time blur validation, and triggers modal dirty checks.
    * `App\Livewire\Projects\ProjectMilestoneModal`: Milestone ordering and checklist management modal[cite: 1].
      Supports real-time checkmark sync and validates multi-row items.
    * `App\Livewire\Projects\ProjectScheduleModal`: Installment scheduler supporting Fixed Date, Milestone Linked, and
      Recurring modes[cite: 1]. Enforces array validation rules, locks settled installments from deletion, and matches
      schedule totals.
    * `App\Livewire\Projects\ProjectRecurringModal`: Single-click monthly retainer schedule generator[cite: 1]. Includes
      validation rules, month-overflow protections, and auto-generated term sequences[cite: 31].

### 6.5 Module 5: My Tasks (`/tasks`)

* **Component**: `App\Livewire\Tasks\TaskIndex`
* **Features**:
    * Personal checklist execution with priority levels (`Low`, `Medium`, `High`, `Urgent`)[cite: 1].
    * Live status summary (`N pending · N done`)[cite: 1].
    * Task creation form supporting project linking and admin assignee selection[cite: 34].
    * Overdue deadline tracking with prominent red late-day indicators[cite: 1].
    * Instant task completion toggling and deletion controls[cite: 1].

### 6.6 Module 6: Payments (`/payments`)

* **Components**:
    * `App\Livewire\Payments\PaymentIndex`: Inward payment ledger with payment mode filtering (`Cash`, `UPI`, `NEFT`,
      `RTGS`, `Cheque`, `Card`, `Other`), reference tracking, and CSV export[cite: 1]. Listens for `payment-saved` and
      recalculates term balances on receipt deletion.
    * `App\Livewire\Payments\PaymentModal`: Receipt modal that automatically calculates installment balances upon
      selection[cite: 1]. Sanitizes `tds` to prevent SQL NOT NULL violations and provides real-time field validation.

### 6.7 Module 7: Payment Schedule (`/schedule`)

* **Component**: `App\Livewire\Schedule\ScheduleIndex`
* **Features**:
    * Master chronological schedule tracking all upcoming and overdue installments[cite: 1].
    * Summary KPIs: Upcoming, Overdue, Collected, and Total Scheduled[cite: 1].
    * Urgency-ranked table with late-day badges and trigger indicators[cite: 1].
    * Inline payment button pre-populating project and installment parameters via
      `Livewire.dispatch('open-payment-modal', ...)`[cite: 1].
    * Listens for `payment-saved` and `payment-deleted` to maintain live balance tracking.
    * UTF-8 BOM CSV export for spreadsheet compatibility[cite: 1].

### 6.8 Module 8: Reports (`/reports`)

* **Component**: `App\Livewire\Reports\ReportIndex`
* **Features**:
    * Financial KPIs: Collection Rate, Total Overdue, TDS Deductions, and Average Project Value[cite: 1].
    * Visual Analytics: 6-month historical sparkline, payment mode breakdown bars, overdue aging bars, and project
      health distribution[cite: 1].
    * Milestone vs. Collection Gap Analysis: Table identifying projects where delivery progress outpaces cash
      collections[cite: 1].
    * SPA navigation via `Livewire.navigate` to prevent currency store resets.

### 6.9 Module 9: Documents (`/documents`)

* **Components**:
    * `App\Livewire\Documents\DocumentIndex`: File repository with search, category filtering (`Contract`, `KYC/GST`,
      `Invoice`, `Proposal`, `Quotation`, `Delivery Proof`, `AMC`, `Other`), and deletion permissions[cite: 1]. Listens
      for `document-saved`.
    * `App\Livewire\Documents\DocumentUploadModal`: File upload modal supporting attachments up to 10 MB[cite: 1].
      Explicitly stores files to the public storage disk root (`$file->storeAs('', $storedName, 'public')`) and
      normalizes empty project associations.
    * `App\Livewire\Documents\DocumentPreviewModal`: Lightbox viewer for previewing images and PDFs[cite: 1].

### 6.10 Module 10: Follow-ups (`/followups`)

* **Component**: `App\Livewire\Followups\FollowupIndex`
* **Features**:
    * Client communication logs tracking 5 channels (`Call`, `Email`, `Meeting`, `WhatsApp`, `Note`)[cite: 1].
    * Prominent warning banner listing overdue touchpoints[cite: 1].
    * Channel-specific color-coded icons and conversation detail notes[cite: 1].
    * Direct customer navigation and record deletion controls[cite: 1].
    * Role-scoped project dropdown binding and real-time field blur validation.

### 6.11 Module 11: Sales Team (`/sales`)

* **Components**:
    * `App\Livewire\Sales\SalesIndex`: Commercial team registry tracking customer volume, total contracted value,
      collections, and quota targets[cite: 1]. Listens for `sales-saved`.
    * `App\Livewire\Sales\SalesModal`: Modal for managing sales representatives and monthly targets[cite: 1]. Enforces
      `Rule::unique` on representative names and sanitizes numeric target fields.

### 6.12 Module 12: Notifications (`/notifications`)

* **Components**:
    * `App\Livewire\Notifications\NotificationIndex`: System alert feed with category icons, unread indicators, and
      mark-as-read actions[cite: 1].
    * `App\Livewire\Components\NotificationBell`: Real-time navigation bell displaying unread alert counts[cite: 1].
    * Deep Linking: Clicking an alert navigates directly to the target project or customer[cite: 1].

### 6.13 Module 13: Activity Log (`/audit`)

* **Component**: `App\Livewire\Audit\AuditIndex`
* **Features**:
    * Append-only system audit log tracking logins, creations, updates, deletions, and uploads[cite: 1].
    * Action-specific timeline indicator dots (Green for payments, Red for deletions, Navy for creates, Amber for
      updates)[cite: 1].
    * Full user attribution, entity code badges, and detailed diff notes[cite: 1].

### 6.14 Module 14: Settings & Users (`/settings`)

* **Components**:
    * `App\Livewire\Settings\SettingIndex`: Operational alert thresholds (`payment_alert_days`, `amc_alert_days`,
      `followup_alert_days`), display currency configuration, password updates with 4-point complexity rules, and user
      management[cite: 1]. Listens for `user-saved`.
    * `App\Livewire\Settings\UserModal`: Staff user creation and editing modal with temporary password generation,
      password complexity enforcement, and last active admin protection[cite: 1].

---

## 7. Frontend Layout, UI Components & Responsive State

### 7.1 Responsive Shell & Mini-Sidebar Architecture

The application layout in `resources/views/layouts/app.blade.php` supports responsive collapsing with persistent
state[cite: 1]:

* **Desktop Collapse**: Toggling the `#burger` button applies the `.collapsed` CSS class, collapsing the sidebar to a
  70px icon-only rail[cite: 1]. State is persisted in `localStorage` under `pjt_sidebar_collapsed`[cite: 1].
* **Mobile Drawer**: On screens $\le 860\text{px}$, the sidebar functions as an off-canvas slide-out drawer (
  `.sidebar.open`) that closes upon outside clicks[cite: 1].
* **Native CSS Layout**: Built with custom variables in `public/css/crm-style.css` using brand colors (Navy `#0b3d91`,
  Green `#5aa236`)[cite: 1].

### 7.2 In-Memory Currency Conversion (`Alpine.store('currency')`)

Amounts are stored natively in Indian Rupees (INR, `₹`) with client-side conversion to US Dollars (USD, `$`) via
Alpine.js initialized during `alpine:init`[cite: 1]:

```javascript
document.addEventListener('alpine:init', () => {
    Alpine.store('currency', {
        cur: localStorage.getItem('pjt_cur') || 'INR',
        rate: 83.0,
        toggle() {
            this.cur = this.cur === 'INR' ? 'USD' : 'INR';
            localStorage.setItem('pjt_cur', this.cur);
        },
        format(val, short = false) {
            let n = Number(val || 0);
            if (this.cur === 'USD') {
                n = n / this.rate;
                if (short && Math.abs(n) >= 1000) return '$' + (n / 1000).toFixed(n % 1000 === 0 ? 0 : 1) + 'K';
                return '$' + n.toLocaleString('en-US', {maximumFractionDigits: n < 100 ? 2 : 0});
            }
            if (short && Math.abs(n) >= 100000) return '₹' + (n / 100000).toFixed(n % 100000 === 0 ? 0 : 1) + 'L';
            return '₹' + n.toLocaleString('en-IN', {maximumFractionDigits: 0});
        }
    });
});
```

### 7.3 Universal Modal Component (`<x-modal>`) & Unsaved Changes Guard

All form dialogs utilize `resources/views/components/modal.blade.php`. The component implements dirty-state tracking (
`isDirty`) and handles Livewire event payload unwrapping for `open-modal` and `close-modal`[cite: 10]:

```blade
@props(['name', 'title', 'sub' => null, 'wide' => false])

<div x-data="{
        show: false,
        isDirty: false,
        confirmClose() {
            if (this.isDirty) {
                if (confirm('You have unsaved changes. Are you sure you want to discard them?')) {
                    this.forceClose();
                }
            } else {
                this.forceClose();
            }
        },
        forceClose() {
            this.show = false;
            this.isDirty = false;
        }
     }"
     x-show="show"
     x-on:open-modal.window="
        const d = $event.detail;
        if (d === '{{ $name }}' || d?.name === '{{ $name }}' || (Array.isArray(d) && d[0] === '{{ $name }}') || d?.[0] === '{{ $name }}') {
            show = true;
            isDirty = false;
        }
     "
     x-on:close-modal.window="
        const d = $event.detail;
        if (!d || d === '{{ $name }}' || d?.name === '{{ $name }}' || (Array.isArray(d) && d[0] === '{{ $name }}') || d?.[0] === '{{ $name }}') {
            forceClose();
        }
     "
     x-on:keydown.escape.window="if (show) confirmClose()"
     style="display: none;"
     class="overlay fixed inset-0 bg-[#08142a]/60 backdrop-blur-sm flex items-start justify-center p-6 z-50 overflow-y-auto">

    <div class="modal bg-white rounded-2xl w-full {{ $wide ? 'max-w-4xl' : 'max-w-2xl' }} shadow-2xl overflow-hidden my-auto"
         @click.outside="confirmClose()"
         @input="isDirty = true"
         @change="isDirty = true">

        <div class="modal-head flex items-center justify-between p-5 border-b border-[#eef2f9]">
            <div>
                <h3 class="text-base font-bold text-[#0d1b34]">{{ $title }}</h3>
                @if($sub)
                    <p class="text-xs text-[#8894ac] mt-0.5">{{ $sub }}</p>
                @endif
            </div>
            <button type="button" @click="confirmClose()" class="text-[#8894ac] hover:text-[#0d1b34] text-lg font-bold">
                ✕
            </button>
        </div>

        <div class="modal-body p-6 max-h-[75vh] overflow-y-auto">
            {{ $slot }}
        </div>
    </div>
</div>
```

---

## 8. Deployment, Operations & Runbook

### 8.1 System Prerequisites

* **PHP**: `^8.2` or later with extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `tokenizer`, `xml`, `ctype`.
* **Database**: MySQL `8.0+` or MariaDB `10.4+`.
* **Web Server**: Nginx or Apache with `mod_rewrite` enabled.
* **Build Tools**: Composer `2.x` and Node.js `18+` (NPM).

### 8.2 Production Deployment Setup

```bash
# 1. Clone repository to server root
git clone <repository_url> /var/www/pjt-crm
cd /var/www/pjt-crm

# 2. Install production dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. Environment configuration
cp .env.example .env
php artisan key:generate

# 4. Configure database connection inside .env:
# DB_HOST=127.0.0.1
# DB_DATABASE=project_manager
# DB_USERNAME=project_user
# DB_PASSWORD=secure_password

# 5. Run database migrations and seed default users
php artisan migrate --force
php artisan db:seed --class=UserSeeder --force

# 6. Establish storage symlink
php artisan storage:link

# 7. Set correct storage permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 8. Optimize application caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8.3 Web Server Configuration (Nginx)

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

## 9. AI Development & Maintenance Protocols

Adhere to the following rules when maintaining or extending this codebase:

1. **Centralize Financial Calculations in `MoneyCalculationService`**:
   Never calculate installment balances, receivables, or overdue states directly inside Blade views or controllers.
   Always invoke `MoneyCalculationService` or the `getMoneyAttribute` accessor on models to ensure consistency across
   views, dashboards, and CSV exports[cite: 1].

2. **Maintain Atomic Sequencing Invariants**:
   Never generate sequential entity codes using `Model::max('id') + 1`, which creates race conditions under concurrent
   requests. Always generate codes using `CodeGeneratorService::generate('entity', 'PREFIX-')` inside database
   transactions with row-level locking[cite: 1].

3. **Enforce Role-Based Query Scoping**:
   Always scope queries for coordinators using `Project::forUser(auth()->user())`. Coordinators must never access or
   manipulate projects, payments, or documents belonging to other coordinators[cite: 1].

4. **Separate Livewire PHP Classes from Blade Views**:
   Never include PHP class definitions or namespace declarations inside `.blade.php` view files. Keep PHP classes in
   `app/Livewire/` and views in `resources/views/livewire/` to avoid fatal class re-declaration errors.

5. **Sanitize Nullable Inputs & Prevent SQL NOT NULL Violations**:
   When columns are configured with `default(0.00)` but lack `->nullable()`, always pre-sanitize form input values (e.g.
   `amc_amount`, `cost`, `tds`, `target`) to `0.00` before persisting rather than passing explicit `null`
   values[cite: 20].

6. **Clean Up Files on Disk During Deletions**:
   When deleting document records or cascading customer deletions, always delete the corresponding file from disk using
   `Storage::disk('public')->delete(\$doc->stored_as)` to prevent orphaned files[cite: 1].
