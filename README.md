# Mini-CRM API

A robust, high-performance, and cleanly designed JSON API for a Mini-CRM built with Laravel (using Laravel Sail & PostgreSQL).

---

## Technical Stack & Configuration

- **Framework**: Laravel 13.x
- **Database**: PostgreSQL 18
- **Authentication**: Laravel Sanctum (API Tokens)
- **Local Environment**: Laravel Sail (Docker-based)

---

## Getting Started

### 1. Requirements
Ensure you have Docker Desktop running on your host machine.

### 2. Environment Setup
Copy the environment template file:
```bash
cp .env.example .env
```
The `.env` file is pre-configured to link with Sail and forward the database port to **`5433`** to avoid conflicts on port `5432`.

### 3. Spin Up Docker Containers
Boot up the Docker containers in the background:
```powershell
# On Windows PowerShell
.\vendor\bin\sail up -d

# On Linux/macOS/Bash
./vendor/bin/sail up -d
```

### 4. Run Migrations & Seed Database
Reset the database tables, apply schema migrations, and seed mock CRM users (Managers & Reps), leads, and activities:
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

---

## Seeded Users (for Testing)

You can log in using these default credentials:

- **Manager User**:
  - Email: `manager@crm.com`
  - Password: `password`
- **Sales Reps**:
  - Email: `rep1@crm.com`, `rep2@crm.com`, or `rep3@crm.com`
  - Password: `password`

---

## Database Connection Settings (for TablePlus, DBeaver, etc.)

To connect your database management tool to the PostgreSQL container, use the following credentials:
- **Host**: `127.0.0.1` (or `localhost`)
- **Port**: `5433`
- **Database**: `mini_crm`
- **Username**: `sail`
- **Password**: `password`

---

## API Endpoints

All endpoints require the `Accept: application/json` header. Except for `/api/login`, all routes require authentication via a Sanctum Bearer Token (`Authorization: Bearer <token>`).

### 1. Authentication
- **`POST /api/login`**: Sign in and receive a Sanctum API token.
  - Body params: `email`, `password`.

### 2. Leads Management
- **`GET /api/leads`**: Get a list of leads (paginated, sorted, filtered).
  - Managers see all leads; Reps see only leads assigned to them.
  - Filters: `status` (`new`, `contacted`, `qualified`, `won`, `lost`), `source` (`web`, `referral`, `cold_call`, `event`, `other`), `assigned_to` (Manager only).
  - Search: `search` (filters by name, email, or company using case-insensitive partial match).
  - Sorting: `sort_by` (`created_at`, `expected_value`) and `sort_order` (`asc`, `desc`).
- **`POST /api/leads`**: Create a new lead.
- **`GET /api/leads/{id}`**: Show detailed lead card along with its activities history.
- **`PATCH /api/leads/{id}`**: Update lead fields.
  - **Won/Lost Transition Rule**: If changing status to `won` or `lost`, the lead must have at least one activity logged. Otherwise, it will fail validation with a `422 Unprocessable Entity` response.
- **`POST /api/leads/{id}/assign`**: Assign or reassign a lead to a sales rep (Manager-only access).

### 3. Activities
- **`POST /api/leads/{id}/activities`**: Log a CRM activity (call, email, meeting, note) against a lead.
  - Reps can only log activities for their own assigned leads.

### 4. Reports
- **`GET /api/reports/rep-performance`**: Aggregate sales rep performance report.
  - Reps can only see their own performance metrics row; Managers see all reps.
  - Leverages optimized, single-query PostgreSQL database aggregations for total leads, lead counts by status, expected values, and activity counts (Option B - counts all activities on leads assigned to the rep).

---

## Running Automated Tests

A comprehensive suite of feature tests is provided to verify authentication, RBAC policies, status transition validations, activity log permissions, and reporting correctness.

Run the test suite inside the container:
```bash
./vendor/bin/sail test
```
