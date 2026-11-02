# PJT CRM — Complete User Guide & Operational Manual

> **Welcome to PJT CRM!**  
> This guide is written in simple, clear language to help every member of your team—whether you are an **Administrator**, **Manager**, **Project Coordinator**, or **Sales Representative**—use the CRM easily and confidently in your everyday work.

---

## Table of Contents

1. [Introduction & Role-Based Access](#1-introduction--role-based-access)
2. [Global Navigation & Workspace Layout](#2-global-navigation--workspace-layout)
3. [Module 1: Dashboard & Today's Work Queue](#3-module-1-dashboard--todays-work-queue)
4. [Module 2: Customers](#4-module-2-customers)
5. [Module 3: Projects](#5-module-3-projects)
6. [Module 4: Delivery Milestones](#6-module-4-delivery-milestones)
7. [Module 5: Payment Schedules & Invoicing Terms](#7-module-5-payment-schedules--invoicing-terms)
8. [Module 6: Payments & Collection Ledger](#8-module-6-payments--collection-ledger)
9. [Module 7: Payment Calendar (Datewise Cash Flow Grid)](#9-module-7-payment-calendar-datewise-cash-flow-grid)
10. [Module 8: Customer Touchpoints & Follow-ups](#10-module-8-customer-touchpoints--follow-ups)
11. [Module 9: Documents & File Vault](#11-module-9-documents--file-vault)
12. [Module 10: Team Tasks & Daily Checklist](#12-module-10-team-tasks--daily-checklist)
13. [Module 11: Sales Performance & Quotas](#13-module-11-sales-performance--quotas)
14. [Module 12: Reports & Business Analytics](#14-module-12-reports--business-analytics)
15. [Module 13: Notifications & Workspace Alerts](#15-module-13-notifications--workspace-alerts)
16. [Module 14: Activity History (Change Log)](#16-module-14-activity-history-change-log)
17. [Module 15: Team & Staff Management](#17-module-15-team--staff-management)
18. [Module 16: Profile, Password & Account Settings](#18-module-16-profile-password--account-settings)
19. [Frequently Asked Questions (FAQ) & Best Practices](#19-frequently-asked-questions-faq--best-practices)

---

## 1. Introduction & Role-Based Access

PJT CRM is built specifically for digital and creative agencies to run two vital business engines smoothly in one unified system:
1. **Client Project Delivery** (tracking project phases, delivery dates, client communications, and team responsibilities).
2. **Cash Flow & Payment Collections** (planning payment schedules, chasing milestones, logging incoming bank transfers, tracking TDS, and keeping payment delays at zero).

### The Five Roles in the Agency

Your agency uses five standard roles. Each role automatically sees the tools and information relevant to their job:

| Role | Primary Responsibilities | What They Can Access |
|:---|:---|:---|
| **Administrator** | Overall business management, staff provisioning, system configuration, executive reporting. | **Full unrestricted access** across all clients, projects, payments, staff accounts, permissions, and system settings. |
| **Manager** | Delivery leadership, team supervision, coordinator workload allocation, client escalation. | Global view of projects, milestones, payments, and reports for all team members reporting to them. |
| **Project Coordinator** | End-to-end client project management, delivery milestone tracking, payment chasing. | Assigned projects, client details, payment terms, follow-ups, documents, and Today's Work queue. Sensitive executive reports and user administration remain hidden. |
| **Tech Person** | Technical executor, development milestones, deliverable oversight, and handover. | Assigned projects (`tech_person_id`), milestones, tasks, documents, and technical delivery queue. Customer records and financial terms are excluded. |
| **Sales Representative** | Client onboarding, pipeline closing, basic project initiation, sales target tracking. | Onboard new customers, initiate new projects, view self-attributed accounts, and track personal sales targets. Financial collection ledgers and internal staff lists remain hidden. |

---

## 2. Global Navigation & Workspace Layout

The application is structured for fast, keyboard-friendly daily use:

### The Top Header (Always Visible)
- **Agency Logo & Home**: Clicking the logo in the top-left returns you immediately to your personal Dashboard.
- **Global Search**: Type any project code (`PJT-0001`), customer name, or phone number to jump straight to records.
- **Today's Work Alert Pill**: Shows a yellow warning pill when you have payment installments or milestones requiring action today.
- **Notification Bell**: Displays an unread badge counter. Clicking opens a slide-out drawer showing recent alerts.
- **User Profile Pill**: Displays your avatar and name. Clicking opens your **Profile Drawer** where you can edit your photo, phone number, and change your password.
- **Sign Out Button**: Safely ends your session with a confirmation popup so you never log out accidentally.

### The Left Navigation Sidebar
Organized into clean, easy-to-read sections:
- **Overview**: Dashboard, Today's Work
- **Clients & Projects**: Customers, Projects
- **Team & Access**: Users (Admin only) or Team Members (Managers & Coordinators)
- **Money**: Payments, Payment Schedule, Payment Calendar, Reports
- **System**: Notifications, Activity History, Settings

> [!TIP]
> **Mobile Friendly**: On mobile phones or tablets, tap the hamburger menu (`☰`) in the top header to slide the navigation in and out smoothly.

### In-App Confirmation Popups
Whenever you attempt to delete a record, sign out, or close a form where you typed unsaved changes, the app presents a clear confirmation window:
- Click **"Keep Editing"** to stay right where you are with your typed data safe.
- Click **"Discard Changes"** or **"Confirm"** only when you are sure you want to proceed.

---

## 3. Module 1: Dashboard & Today's Work Queue

### Purpose
The **Dashboard** gives you a real-time operational bird's-eye view tailored to your specific role. You immediately see how projects are progressing, what payments are expected this month, and what requires attention right now.

### Top Card Information & Metrics Explained Simply
- **Total Receivable**: Total value of all scheduled installments across active client projects.
- **Received**: Total actual cash collected into the agency bank account to date.
- **Due**: Outstanding balance awaiting collection from clients.
- **Overdue**: Installments that have crossed their due date and require immediate follow-up.
- **This Month**: Total payment receipts logged during the current calendar month.

### Downline Team Member Filter (Admins & Managers)
- In the top action bar of the Dashboard, **Administrators** and **Managers** can use the **Team Member Filter** dropdown.
- Selecting any staff member immediately recalculates the entire dashboard—including Total Receivable, Received, Due, Overdue, Project Health counts, and charts—scoped specifically to that person's assigned portfolio.
- Click **"Clear Filter"** or **"Reset"** to return to the full agency or department overview.

### Quick Switch to Payment Calendar
- Click the **"Payment Calendar"** button in the dashboard header to jump straight to the datewise monthly calendar grid.

### Today's Work Queue (`/today`)
Think of this page as your agency's daily **action checklist**:
1. **Due Today & Overdue Installments**: Displays every milestone payment that is due today or past due.
2. **One-Click Communication**:
   - Tap **"Call"** to dial the client's phone directly.
   - Tap **"WhatsApp"** to open a pre-filled, polite payment reminder message in WhatsApp Web or WhatsApp mobile.
3. **Log Follow-up Note**: Click to record what the client said (e.g., *"Client approved invoice, promised transfer on Friday"*).
4. **Milestone Chase Modal**: If a client's payment is delayed because a deliverable is waiting on them (such as waiting for client logo or content feedback), click **"Chase Milestone"** to record client feedback delays.

---

## 4. Module 2: Customers

### Purpose
The **Customers** module maintains your agency's client database. A single Customer record can have multiple projects over months or years.

### Actions & Step-by-Step Guide

#### 1. Viewing & Searching Customers (`/customers`)
- Use the search bar to find customers by **Company Name**, **Contact Person**, **Phone Number**, or **City**.
- Filter by status: `Active`, `On Hold`, `Completed`, or `Churned`.
- Clear any applied filter in 1 click using the red **"Clear"** button.

#### 2. Onboarding a New Customer (`New Customer`)
1. Click the blue **"New Customer"** button in the top right.
2. Fill in the customer details:
   - **Company Name** *(Optional)*: Enter their registered business name (e.g., *Sunrise Retails Pvt Ltd*). If the customer is an individual or freelancer without a company, leave this blank.
   - **Contact Person** *(Required)*: The name of the primary decision-maker (e.g., *Rakesh Gupta*).
   - **Phone** *(Required)*: Mobile or direct phone number.
   - **State** *(Required)*: Select their Indian state or Union Territory from the dropdown.
   - **Onboarded by (Salesperson)**: Select which sales team member brought in this customer (for performance commissions and reports).
   - **GST / PAN Number** *(Optional)*: Enter tax numbers if they require GST invoicing.
   - **Internal Notes** *(Optional)*: Private notes visible only to internal staff (e.g., *"Prefers communication on WhatsApp after 3 PM"*).
3. Click **"Create Customer"**. The system automatically issues a unique customer code (e.g., `CUS-0012`) and initiates the review workflow.

#### 3. Customer Approval & Re-Review Workflow
To ensure quality standards and proper onboarding verification, all new customers created by team members enter a **Pending Approval** state before becoming active accounts:
- **Pending Status Indicator**: Customers pending review display an amber **"Pending Approval"** badge in the customer directory and detail view.
- **Supervisor Actions (Admin, Manager, Coordinator)**:
  - In the customer listing and detail header, authorized supervisors see quick action icon buttons:
    - **Approve (Green Thumbs-Up Icon)**: Clicking this opens a confirmation modal. Once confirmed, the customer is activated (`is_approved = true`), an activity log entry is recorded, and an in-app notification is sent to the salesperson.
    - **Reject (Red Thumbs-Down Icon)**: Clicking this opens a rejection modal where the supervisor enters a mandatory explanation note (e.g., *"Missing GST certificate and company phone number"*).
- **Edit & Resubmit Workflow for Rejected Records**:
  - When a customer is rejected, an alert banner appears on their detail page clearly showing the supervisor's rejection remarks.
  - The salesperson or creator can click **"Edit & Resubmit"** to correct the missing information or resolve feedback.
  - Saving the updated details automatically changes the status back to **Pending Approval**, logs the resubmission in the activity history, and alerts supervisors that the customer is ready for re-review—eliminating redundant duplicate data entry!

#### 4. Customer Detail Page (`/customer/{id}`)
Displays everything related to that specific customer in one place:
- **Financial Summary Cards**: Total contract value booked, total cash received, and outstanding balance.
- **Projects Tab**: All active and past projects belonging to this customer.
- **Payment History Tab**: Full ledger of every payment received from them.
- **Documents Tab**: Contracts, NDA agreements, and GST certificates uploaded for this customer.
- **Communication History Tab**: Date-stamped notes from every call, meeting, and email.

#### 5. Exporting Customers to CSV (`Export CSV`)
Need to export your customer directory for sales reviews or accounting records?
1. Navigate to `/customers`.
2. Apply any filters (e.g., specific status or state) or leave filters empty to export all records.
3. Click the **"Export CSV"** button in the top action header.
4. The system immediately generates and downloads a clean, Excel-compatible `.csv` file (with full UTF-8 BOM encoding so special characters and regional names render flawlessly).

#### 6. Bulk Importing Customers via CSV (`Import CSV`)
Have an existing client list in Excel?
1. Click **"Import CSV"** on the customer index page.
2. Copy and paste your spreadsheet rows directly into the text box, or upload your `.csv` file.
3. Format columns as: `company,contact,phone,email,city,state,gst_number,source`.
4. The system validates each row, creates the customer records, and automatically skips any duplicate entries.

---

## 5. Module 3: Projects

### Purpose
The **Projects** module is the operational core of the agency. It tracks what you are building, who is managing it, what it is worth, and whether delivery is running on schedule.

### Project Statuses & Health Explained
- **Status**:
  - `Onboarding`: Contract signed, gathering initial requirements and assets.
  - `In Progress`: Active design and development in full swing.
  - `Review`: Deliverables shared with the client for feedback or user testing.
  - `Completed`: Delivered, accepted by client, final handover complete.
  - `On Hold`: Temporarily paused (e.g., waiting for client funding or strategy shift).
  - `Cancelled`: Terminated before completion.
- **Project Health**:
  - `On Track` (Green): Milestones and deliverables are running on schedule.
  - `At Risk` (Amber): A potential bottleneck or brief delay has occurred.
  - `Delayed` (Red): Target dates have been missed. **A mandatory Delay Reason must be entered** so managers can step in to assist.

### Actions & Step-by-Step Guide

#### 1. Creating a New Project (`New Project`)
1. Click **"New Project"**.
2. Select the **Customer** from the dropdown.
3. Enter the **Project Name** (e.g., *E-Commerce Mobile App & Admin Panel*).
4. Select the **Project Type** (*Website*, *Mobile App*, *Branding*, *Digital Marketing*, *Custom Software*).
5. Enter **Project Value** (Total contract price in ₹) and **Estimated Cost** (Internal budget/expense).
6. Pick **Start Date** and **Target Delivery Date**.
7. Assign the **Project Coordinator** (responsible for project delivery leadership).
8. Assign the **Tech Person** *(Optional/Required)*: Select the technical lead responsible for project development execution, milestone validation, and deliverable sign-off.
9. If originating from a commercial lead, assign the **Salesperson**.
10. If the project includes recurring maintenance, check **"Include AMC (Annual Maintenance Contract)"** and enter the annual renewal amount.
11. Click **"Create Project"**.

#### 2. Project Approval & Re-Review Workflow
To safeguard project scope, accurate pricing, and delivery bandwidth, every project submitted enters a **Pending Approval** verification state:
- **Pending Review Badge**: New projects are flagged with an amber **"Pending Approval"** badge in project tables and detail views.
- **Supervisor Approval & Rejection (Admin, Manager, Coordinator)**:
  - Authorized supervisors see dedicated action buttons in the project row and detail page:
    - **Approve (Green Thumbs-Up Icon)**: Prompts a confirmation popup. Approving the project activates it, logs the event in the audit trail, and notifies the project team.
    - **Reject (Red Thumbs-Down Icon)**: Prompts a rejection dialog requiring a mandatory explanation note (e.g., *"Target delivery date too aggressive for scope; adjust timeline or budget"*).
- **Edit & Resubmit Workflow for Rejected Projects**:
  - A rejected project displays a prominent warning banner with the reviewer's feedback.
  - The creator can click **"Edit & Resubmit"** to update timelines, budget, scope, or team assignments based on supervisor instructions.
  - Saving the revisions automatically transitions the project back to **Pending Approval**, logs the resubmission in the activity trail, and triggers an alert for supervisors to re-review.

#### 3. Updating Project Health & Managing Delays
If client feedback delays or technical bottlenecks push back a delivery date:
1. Open the project and click **"Edit Project"**.
2. Change Health to **"Delayed"** or **"At Risk"**.
3. Type the concrete reason in **"Reason for Delay"** (e.g., *"Waiting for client payment gateway credentials for 10 days"*).
4. Save the project. The system automatically notifies your delivery manager so they can help resolve the roadblock.

> [!NOTE]
> **Automatic Delay Reason Archiving**: When project health is restored back to **"On Track"**, previous delay notes are not discarded. The system automatically archives past delay logs into the project's permanent notes with an exact timestamp, maintaining a clean historical audit trail for future retrospectives.

---

## 6. Module 4: Delivery Milestones

### Purpose
Large projects should never be treated as one massive deliverable. The **Milestones** tool breaks complex projects into clear, trackable phases (e.g., *Phase 1: Wireframes & UI Designs*, *Phase 2: Frontend Development*, *Phase 3: Final Launch*).

### Actions & Step-by-Step Guide

#### 1. Adding a Milestone
1. On any project detail page, open the **Milestones** tab and click **"Add Milestone"**.
2. Enter the **Milestone Title** and target **Due Date**.
3. Enter the **Weightage (%)** (e.g., 25% of the total project).
4. Link it to an invoicing payment term if payment is released upon milestone sign-off.
5. Click **"Save Milestone"**.

#### 2. Completing a Milestone
- As your design or development team finishes a phase, open the project and toggle the milestone checkbox or click **"Mark Completed"**.
- The project's overall completion progress bar automatically updates.
- If this milestone was linked to a payment trigger, the corresponding payment term automatically moves to **"Due"**, notifying your team to send the invoice!

---

## 7. Module 5: Payment Schedules & Invoicing Terms

### Purpose
The **Payment Schedule** (`/schedule`) ensures your agency never delivers work without receiving agreed payments. It maps out exactly when each payment installment should be billed and collected.

### Actions & Step-by-Step Guide

#### 1. Setting Up Payment Terms for a Project
1. In the project detail view, open the **Payment Terms** tab and click **"Add installment"**.
2. Choose when the payment is triggered:
   - **Advance / On Signing**: Due immediately upon project kickoff.
   - **Milestone-Based**: Due as soon as a specific milestone is marked complete.
   - **Date-Based**: Due on a specific calendar date.
3. Enter the installment amount in ₹ and expected due date.

#### 2. One-Click Auto-Split Wizards (30/40/30 & 50/50)
For fast, standardized billing setup, the system provides automated milestone split templates:
- **30 / 40 / 30 Standard Split**:
  - Advance: **30%** upon signing
  - Mid-Term: **40%** at mid-development milestone
  - Final Delivery: **30%** before server launch or code handover
- **50 / 50 Milestone Split**:
  - Advance: **50%** upon signing
  - Final Delivery: **50%** upon completion and handover

> [!IMPORTANT]
> **Locked Installment Preservation**: If you run an Auto-Split on a project where payments or TDS deductions have already been recorded on existing installments, the CRM **automatically protects and locks** those paid terms. Only the **remaining uncollected balance** is divided across the new installments. This ensures past accounting entries and verified bank receipts are never deleted, overwritten, or miscalculated!

#### 3. Preserved Due Dates Across Schedule Modes
When switching payment term modes (for example, switching from milestone-linked billing to date-based calendar billing), your previously set due dates are safely retained in the form, preventing accidental date loss while restructuring terms.

---

## 8. Module 6: Payments & Collection Ledger

### Purpose
The **Payments Ledger** (`/payments`) is the official financial record of all money collected by your agency.

### Actions & Step-by-Step Guide

#### 1. Recording a Customer Payment (`Record Payment`)
When a customer sends money via bank transfer, cheque, or UPI:
1. Click **"Record Payment"** in the top navigation or on the customer/project page.
2. Select the **Customer** and **Project**.
3. Select which **Scheduled Term** this payment fulfills.
4. Enter the **Amount Received** in ₹.
5. Select the **Payment Date** and **Payment Method** (*Bank Transfer / NEFT / IMPS*, *UPI*, *Cheque*, *Credit Card*, *Cash*).
6. Enter the **Transaction ID / Reference Number (UTR)**.
7. **TDS Tracking**: If the client deducted 2% or 10% TDS under tax regulations, enter the deducted amount in the **TDS Deducted** field. The system properly credits the client's balance so their invoice doesn't show as unpaid!
8. Click **"Record Payment"**.

#### 2. Exporting Collection Data to Excel/CSV
1. Navigate to `/payments`.
2. Apply any desired date filters (e.g., *Current Month* or *Specific Customer*).
3. Click the **"Export CSV"** button in the header.
4. A clean `.csv` file downloads immediately to your computer, ready to import into Tally, Zoho Books, QuickBooks, or share with your agency accountant.

---

## 9. Module 7: Payment Calendar (Datewise Cash Flow Grid)

### Purpose
The **Payment Calendar** (`/calendar`) presents a full 7-column monthly view that maps out every payment installment due and every payment collected on its exact calendar date. It gives delivery leads, coordinators, and finance managers an intuitive, visual cash flow roadmap for any selected month.

### Features & How to Use the Calendar

#### 1. Navigating Months
- **Previous (`‹`) and Next (`›`) Buttons**: Jump backward and forward one month at a time. The calendar uses overflow-safe date calculations, ensuring that switching across months with 28, 30, or 31 days (such as January into February) never skips a month or causes calendar drift.
- **"Today" Button**: Instantly resets the view to the current month and highlights today's date.
- **Month & Year Picker**: Directly select any month and year (e.g., `October 2026`) from the date selector.

#### 2. Monthly Summary Metric Strip
Directly above the calendar grid, you see 4 real-time monthly indicators:
- **Total Scheduled Due**: Sum of all installments with due dates falling in the active month.
- **Total Received**: Actual cash received across payment receipts dated within the month.
- **Total Due (Pending)**: Balance remaining to be collected on this month's installments.
- **Total Overdue**: Sum of unpaid balances that have exceeded their due date.

#### 3. Filtering What You See
- **Type Filter Buttons**:
  - **All Records**: Displays both scheduled installment dues (blue) and payment receipts (green).
  - **Scheduled Dues Only**: Hides receipts to focus solely on upcoming collection deadlines.
  - **Received Payments Only**: Focuses strictly on incoming cash receipts logged by date.
- **Team Member Filter (Admins & Managers)**:
  - Filter the entire calendar down to a specific coordinator, manager, or salesperson to inspect their individual portfolio.

#### 4. Day Cells & Color Coding
- **Blue Due Badge**: Indicates scheduled installments due on that day, showing the aggregated total ₹ amount.
- **Green Paid Badge**: Indicates payment receipts verified on that day, showing the total collected ₹ amount.
- **Red Overdue Indicator Dot**: Appears on days where installments remain unpaid past their due date.
- **Current Day**: Highlighted with a distinct blue border ring and "Today" tag.

#### 5. Inspecting Day Details (Clicking Any Date)
- Click on any day cell in the calendar to open the **Date Details Modal**.
- Displays two organized tables:
  1. **Scheduled Installments Due**: Milestone/term label, customer, project code, installment amount, balance due, and status pill (`Pending`, `Partial`, `Overdue`, `Paid`) with a direct link to the project.
  2. **Payments Received**: Receipt code, customer, project, mode (`NEFT`, `UPI`, `Cheque`, etc.), bank name, and verified receipt amount.
- Press **Escape** or click **"Close"** or the dark backdrop to return to the calendar view.

---

## 10. Module 8: Customer Touchpoints & Follow-ups

### Purpose
A record of every important call, WhatsApp conversation, client meeting, and email so anyone on the team can step in without asking the customer to repeat themselves.

### How to Log a Touchpoint
1. On any Customer or Project page, click **"Log Touchpoint"**.
2. Select the **Channel** (*Phone Call*, *WhatsApp*, *Meeting*, *Email*).
3. Write a short summary (e.g., *"Discussed homepage revision; customer requested darker blue header"*).
4. Set the **Next Follow-up Date** (e.g., next Tuesday).
5. When that date arrives, the reminder automatically appears in your **Today's Work** queue!

---

## 11. Module 9: Documents & File Vault

### Purpose
The **Documents** module (`/documents`) provides an enterprise-grade, secure private file vault for customer contracts, proposals, GST certificates, invoices, and delivery sign-off sheets.

### Supported File Formats & Categories
- **Documents**: `PDF`, `DOC`, `DOCX`, `TXT`
- **Spreadsheets**: `XLS`, `XLSX`, `CSV`
- **Presentations**: `PPT`, `PPTX`
- **Images**: `JPG`, `JPEG`, `PNG`, `WEBP`, `GIF`
- **Archives**: `ZIP`
- **File size limit**: Up to **10 MB per file**.
- **Document Categories**: *Contract*, *KYC / GST*, *Invoice*, *Proposal*, *AMC Agreement*, *Other*.

### Security & Privacy Protections
All uploaded files are stored in an **isolated private storage disk** (`storage/app/private/documents`). Unlike conventional websites that place uploads in public folders, documents here have **zero public web-root exposure**. 
- Every file access is strictly authenticated.
- Access is restricted by team hierarchy and downline permissions (only the assigned Project Coordinator, supervising Manager, direct uploader, or Administrator can access documents associated with a project or customer).

### Actions & Dual-View Capabilities
Every file in the document directory, customer detail, and project view features two distinct quick-action buttons:
1. **Inline Preview (Eye Icon)**:
   - For `PDF` documents and images (`JPG`, `PNG`, `WEBP`), clicking the preview button opens an instant, interactive in-browser lightbox modal.
   - You can read agreements, inspect receipts, or verify GST certificates immediately without cluttering your computer's Downloads folder!
2. **Secure Download (Download Icon)**:
   - Clicking download streams the file securely with its **original human-readable filename preserved** (e.g., `ACME-Contract-Signed.pdf` rather than an unreadable system hash).
3. **Uploading Documents (`Upload Document`)**:
   - Click **"Upload Document"**, select your file, choose the associated Customer or Project, pick the category, and click **"Upload Document"**.

---

## 12. Module 10: Team Tasks & Daily Checklist

### Purpose
Internal task management to keep client deliverables on track without messy paper to-do lists or lost sticky notes.

### How to Use Tasks
1. Navigate to `/tasks`.
2. Click **"Add Task"**.
3. Enter the task title, set priority (*Low*, *Medium*, *High*, *Urgent*), pick a due date, and assign it to a team member.
4. When work is completed, click the checkbox to mark it done. Completed tasks automatically move to the finished section.

---

## 13. Module 11: Sales Performance & Quotas

### Purpose
Tracks sales pipeline revenue, individual salesperson booking quotas, and ensures every client is attributed to the correct sales representative.

### What Sales Representatives See
- When logged in as **Sales**, you see your personal monthly target, how much contract value you have closed this month, and the list of clients you brought in.
- You can onboard new customers and initiate projects, but sensitive agency profit margins and other salespeople's private performance metrics remain private.

---

## 14. Module 12: Reports & Business Analytics

### Purpose
Available to **Administrators** and **Managers** (`/reports`), this module answers critical executive business questions:
- *How much total contract revenue did we book this quarter?*
- *How much actual cash did we collect in the bank?*
- *What is our collection rate percentage?*
- *How much money is currently delayed past 60 days?*
- *Which project coordinators are delivering on time vs having delays?*

### Key Financial Formulas Simplified
- **Total Balance Due** = Contract Value − Cash Received − TDS Deductions
- **Collection Rate %** = (Cash Received ÷ Contract Value) × 100
- **Delivery Velocity** = (Completed Milestones ÷ Total Milestones) × 100

---

## 15. Module 13: Notifications & Workspace Alerts

### Purpose
Ensures team members never miss an urgent payment deadline or critical project update.

### How Notifications Work
1. **The Bell Icon**: Shows an unread count badge in the top right header whenever a new alert arrives.
2. **Slide-Out Drawer**: Click the bell to view recent alerts with direct links to the relevant project or client.
3. **Full Inbox (`/notifications`)**: Search past alerts, filter by unread, or click **"Mark All as Read"**.

### Automatic Alerts Generated by the System
- **Payment Reminders**: Sent 7 days, 3 days, and 1 day before an installment is due, and daily if overdue.
- **Project Delay Warnings**: Sent to managers when a project health is marked `Delayed` or `At Risk`.
- **Assignment Notifications**: Sent to coordinators and sales staff when a new project is assigned to them.
- **Password & Security Alerts**: Sent when temporary passwords are generated or account details change.

---

## 16. Module 14: Activity History (Change Log)

### Purpose
Available at `/activity-log` for Administrators and Managers to view a complete, permanent audit trail of every important action taken across the workspace.

### What is Recorded
- Who created, updated, or deleted a client or project.
- Who recorded or removed a payment entry.
- Visual before-and-after differences (e.g., showing exact field changes such as *Project Delivery Date: 15-Nov → 30-Nov*).
- Click any log item to jump directly to the affected record.

---

## 17. Module 15: Team & Staff Management

### Purpose
Available at `/users` (for Administrators) and `/team` (for Managers & Coordinators) to manage your agency's team members, roles, reporting lines, and granular module permissions.

### Actions & Step-by-Step Guide

#### 1. Adding a New Staff Member (`Add New User`)
1. Navigate to `/users` and click **"Add New User"**.
2. Enter their **Full Name**, **Work Email**, **Phone Number**, **City**, and **Job Designation** (e.g., *Senior UI/UX Designer*).
3. Select their **Role** (*Admin*, *Manager*, *Coordinator*, *Tech Person*, *Sales*).
4. Select their **Reporting Manager** from the dropdown to establish the team hierarchy.
5. Enter an initial password or generate a secure temporary password.
6. Check **"Require password change on first login"** so the staff member sets their own private password upon logging in.
7. Click **"Save User"**.

#### 2. Issuing Temporary Passwords & Clearing Account Lockouts
If a staff member forgets their password or types the wrong password 5 times in a row:
1. Open their user record or click the **"Reset & Issue"** button next to their name.
2. The system generates a clean temporary password (e.g., `Temp2026@8491`) and clears their login lockout immediately.
3. An in-app share modal pops up with a **"Copy Login Details"** button so you can send their credentials via Slack or WhatsApp.

#### 3. Granular Module Permissions Matrix
Need to give a specific Coordinator permission to view reports, or restrict a user from deleting documents?
- In the user modal, click the **Permissions Matrix** tab.
- You can toggle individual checkboxes (`View`, `Create`, `Edit`, `Delete`) across all 15 system modules to customize any staff member's access.

---

## 18. Module 16: Profile, Password & Account Settings

### The Profile Drawer
Click your avatar in the top header to slide open your personal **Profile Drawer**:
- **Profile Photo**: Click your avatar to upload your profile picture.
- **Contact Details**: Update your mobile phone number, city, and title.
- **Change Password**: Enter your current password and pick a new secure password.
- **Password Complexity Rules**: Passwords must contain a minimum of 8 characters, including at least one uppercase letter, one lowercase letter, and one number.
- **Random Password Generator**: Click the key/generator button inside the drawer or user creation modal to instantly generate a secure, compliant password.

### Account Lockout Security
To protect customer data and financial records, your account will automatically lock for **15 minutes** if an incorrect password is typed 5 times in a row. If this happens to you, ask your Administrator to generate a temporary password from `/users` to unlock your account immediately.

---

## 19. Frequently Asked Questions (FAQ) & Best Practices

### Q1: I started filling out a modal and clicked outside by mistake. Did I lose my text?
**Answer**: No! The system detects that you have unsaved changes and immediately displays a confirmation window (*"Discard Unsaved Changes?"*). Click **"Keep Editing"** to return right back to your form with every word intact.

### Q2: Can I add a customer who does not have a registered company name?
**Answer**: Absolutely. Company Name is completely optional. Simply enter their name in **Contact Person** (e.g., *Anil Sharma*). The system will display them clearly across all tables as *Anil Sharma (Customer)*.

### Q3: What happens if my new customer or project is rejected by a supervisor?
**Answer**: You never have to re-enter data from scratch! The rejected customer or project displays an alert banner explaining the supervisor's feedback. Simply click **"Edit & Resubmit"**, make the required changes, and save. The record automatically returns to **Pending Approval** for re-review.

### Q4: How do I preview a document without downloading it to my computer?
**Answer**: Next to any PDF or image document in the file vault, click the **Preview (Eye Icon)** button. It opens an instant lightbox directly in your browser so you can inspect contracts, invoices, or screenshots without saving them to your local downloads.

### Q5: Why does the system require a "Delay Reason" when marking a project Delayed?
**Answer**: Accountability and team collaboration. When a delivery date slips, recording the reason (e.g., *waiting for client API keys* vs *internal design revision*) ensures everyone is on the same page and management can step in to help.

### Q6: How do I handle TDS deducted by my customer?
**Answer**: When recording the payment, enter the net amount deposited in your bank into **Amount Received**, and enter the tax deducted into **TDS Deducted**. Both amounts together will be credited toward settling the customer's invoice balance!

### Q7: How do I export my records for accounting?
**Answer**: Go to `/payments` or `/customers` and click the **"Export CSV"** button in the header. The data will download immediately in universal spreadsheet format with Excel-friendly UTF-8 encoding.

---

*Document Version: 5.0 · Updated for PJT CRM · All Rights Reserved.*
