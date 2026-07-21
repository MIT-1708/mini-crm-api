# Mini-CRM API

A robust, high-performance, and cleanly designed JSON API for a Mini-CRM built with Laravel (using Laravel Sail & PostgreSQL).

---

## Technical Stack & Configuration

- **Framework**: "Built on Laravel 13.x (latest stable at time of writing). Fully compatible with Laravel 11/12 conventions used in this project — no version-specific features were required."
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

## User Guides & API Testing

If you want to use the guide for project working, testing, and API navigation, please refer to the following files in the [guide/](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/guide) directory:
* **[Detailed Demo Guide](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/guide/detailed_demo_guide.md)**: Walks through all core demonstration scenarios, request payloads, response structures, and test cases using curl.
* **[Postman Step-by-Step Navigation Guide](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/guide/postman_navigation_guide.md)**: Provides a step-by-step click guide to manually build, configure, and test all CRM routes in Postman.

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
## Completed Bonus Features

We have implemented three of the bonus challenges specified in the requirements:
1. **Docker / Sail Setup**: Fully configured containerized local environment using Laravel Sail and PostgreSQL.
2. **Queued Job on Lead Assignment**: Dispatches a queued job `NotifyRepOfAssignment` to log and handle representative assignment notifications.
3. **Automated Status Change Logging (Event + Listener)**: Fires a `LeadStatusChanged` event and listener `AutoRecordStatusChangeActivity` to automatically create a history log activity note whenever a lead's status is updated.

---

## Running Automated Tests

A comprehensive suite of feature tests is provided to verify authentication, RBAC policies, status transition validations, activity log permissions, and reporting correctness.

Run the test suite inside the container:
```bash
./vendor/bin/sail test
```

---

## Project Considerations

### 1. Assumptions Made
- **Won/Lost Initial Creation**: A new lead cannot be created directly with `won` or `lost` status since activity logging requires a valid, pre-existing `lead_id` database record. The API rejects creating a lead with `won`/`lost` initially and prompts the user to create it in another status and log activities first.
- **Search Behavior**: Case-insensitive partial matching (`ilike`) is used for searching name, email, and company fields to support Postgres out-of-the-box.
- **Option B Metric**: The activity metric in the performance report counts all activities logged on the leads currently assigned to the rep, serving as a measure of lead engagement.

### 2. Deliberate Trade-offs
- **Single Query Reporting**: Used optimized database-level subqueries (`withCount` and `withSum`) for the rep performance report. While a Materialized View or an ELK-based read-model is standard for extremely large databases (millions of records), a single highly optimized SQL query runs efficiently for ~100k leads without the complexity of cache synchronization or background refresh lag.

### 3. Future Enhancements (With More Time)
- **Report Caching**: Implementing Redis caching with cache tag invalidations on lead/activity writes.
- **Rate Limiting**: Throttling login requests to prevent brute force attacks.
- **API Documentation**: Adding Scribe or OpenAPI/Swagger specifications for cleaner integration.

### 4. Multi-tenancy Sketch (Bonus Design Note)
To scale this application to support multiple tenants (different companies/organizations using the CRM) within a single codebase:
- **Database Architecture**: We would use a **Shared Database, Shared Schema** design for cost-effective hosting and simpler deployments.
- **Logical Isolation**: We would add a `tenant_id` column to all tenant-specific tables (`users`, `leads`, `activities`) and create a composite index on `(tenant_id, status)` or `(tenant_id, assigned_to)`.
- **Global Query Scope**: We would define a custom Laravel Global Scope (`TenantScope`) that automatically injects `where('tenant_id', TenantManager::getTenantId())` to all database queries for these models, preventing any cross-tenant data leaks.
- **Tenant Identification**: The active tenant would be resolved in a global middleware via subdomain detection (e.g. `{tenant}.crm.com`) or a request header (`X-Tenant-ID`).
- **Validation Scoping**: Model validations like unique email checks would be scoped per-tenant using `Rule::unique('users')->where('tenant_id', $tenantId)`.

