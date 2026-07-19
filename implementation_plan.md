# Mini-CRM API Implementation Plan

We will build a high-performance, robust, and cleanly designed JSON API for a Mini-CRM.

Detailed tech stack specifications can be found in the [tech_stack.md](file:///C:/Users/allle/.gemini/antigravity-ide/brain/8d485034-ae5a-4982-90d8-52ba01d12958/tech_stack.md) document.

---

## User Decisions Incorporated

1. **Database**: PostgreSQL will be used as the database.
2. **Activity Count Metric**: **Option B** is selected. The activity count in the rep-performance report will count all activities logged *on leads assigned to each rep* (measuring lead engagement).

---

## Proposed Changes

We will bootstrap a fresh Laravel installation in the workspace root, clean up default placeholders, and implement the following components:

### 1. Database Schema & Models
We will define three main models and their migrations:
- **`User`**
  - Columns: `role` (enum: `manager`, `rep`), standard Laravel authentication fields.
- **`Lead`**
  - Columns: `name`, `email`, `phone`, `company` (nullable), `source` (enum: `web`, `referral`, `cold_call`, `event`, `other`), `status` (enum: `new`, `contacted`, `qualified`, `won`, `lost`), `expected_value` (decimal 12,2), `assigned_to` (nullable foreign key to `users`).
  - Indexes: `status`, `source`, `assigned_to`, `created_at`, `expected_value`.
- **`Activity`**
  - Columns: `lead_id` (foreign key, cascade delete), `user_id` (foreign key), `type` (enum: `call`, `email`, `meeting`, `note`), `body` (text), `occurred_at` (datetime).
  - Indexes: `lead_id`, `user_id`, `occurred_at`.

### 2. Authorization (Policies)
We will enforce role-based access control via a clean Policy-based authorization system:
- **`LeadPolicy`**:
  - `viewAny`: Managers see all. Reps see only leads where `assigned_to = auth()->id()`.
  - `view` / `update` / `logActivity`: Managers can access all. Reps can only access if `assigned_to = auth()->id()`.
  - `assign`: Only managers (`auth()->user()->role === 'manager'`).
- **`ReportPolicy`**:
  - `view`: Managers see all. Reps can only see their own performance metrics.

### 3. Business Rules (Transitions)
- **Won/Lost Transition Check**:
  - When updating a lead's status to `won` or `lost`, we check if `$lead->activities()->exists()`. If not, we abort/reject with a `ValidationException` (returning `422 Unprocessable Entity`) and a user-friendly error message.

### 4. API Endpoints
All endpoints will use Laravel API Resources for consistent response envelopes:
- **`POST /api/login`**: Authenticate credentials, return Sanctum token and user resource.
- **`GET /api/leads`**: Paginated search/filter/sort leads query.
  - Filter: `status`, `source`, `assigned_to`.
  - Search: `name`, `email`, `company` (using case-insensitive `LIKE` matching).
  - Sort: `created_at`, `expected_value` (asc/desc).
- **`POST /api/leads`**: Validate and create a lead.
- **`GET /api/leads/{id}`**: Show lead with eager loaded `activities` and `assignedRep` (preventing N+1).
- **`PATCH /api/leads/{id}`**: Update lead fields with validation and transition checks.
- **`POST /api/leads/{id}/assign`**: Assign or reassign lead to a rep (Manager only).
- **`POST /api/leads/{id}/activities`**: Log an activity for a lead.
- **`GET /api/reports/rep-performance`**: Highly optimized per-rep performance query:
  - We will use PostgreSQL aggregations (e.g. `SUM`, `COUNT`, conditional aggregations for lead status counts and won values) to perform the entire report calculation in a single query.
  - Since Option B is chosen, activity count will be calculated by counting activities joined on leads assigned to that rep.
  - Managers see all reps; a rep sees only their own row.

### 5. Bonus Implementations
- **Queued Job on Assignment**:
  - Dispatch a `NotifyRepOfAssignment` job when a lead is assigned. It will log the assignment event in the queue logs (supporting scaling to email/Slack notifications).
- **Status Change Activity Logger (Event + Listener)**:
  - Create a `LeadStatusChanged` event and an `AutoRecordStatusChangeActivity` listener. When a lead's status is changed, a system-generated activity of type `note` is automatically logged.

---

## Verification Plan

### Automated Tests
We will write feature tests to verify:
- **Authentication**: Token issue, unauthorized access checks.
- **Authorization**: Manager access to all leads vs. Rep access restricted to assigned leads.
- **Won/Lost Rule**: Rejection of status change to `won` or `lost` without activities, and acceptance with activities.
- **Lead Operations**: Filtering, searching, sorting, paginating, and editing leads.
- **Assignment**: Successful assignment by managers, rejection for reps, and queue dispatch verification.
- **Reporting**: Performance report correctness, N+1 check, and role restrictions.

Commands to run tests (via Laravel Sail):
```bash
./vendor/bin/sail test
```

### Manual Verification
- Seed the database with sample managers, reps, and leads.
- Run a local server via Laravel Sail.
- Provide a Postman collection or `curl` instructions in the README to test each endpoint.
