# Mini-CRM API Project Log

This document records the development phases, status updates, and modifications completed in each stage of the project. It serves as a historical record of what has occurred, and will only be updated upon user request.

---

## Current Status
- **Current Stage**: Stage 5 (Performance Reporting & Testing Setup)
- **Status**: In Progress (Running Docker containers and finalizing dependency installations)

---

## Stage History

### Stage 0: Requirements Analysis & Environment Check (2026-07-19)
- **Actions Taken**:
  - Read and analyzed [requrement for crm.txt](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/requrement%20for%20crm.txt).
  - Evaluated the host OS environment; PHP 8.5.8 is now configured in the host PATH. Docker and WSL are fully active.
  - Formulated a containerized development approach using Laravel Sail.
- **Key Discoveries**:
  - Requires strict validation for status transitions (won/lost require at least one activity).
  - Report performance needs to be highly optimized (avoiding N+1 queries) for scale (~100k leads).
  - Explicit role-based logic (Manager vs. Rep) must be handled using clean authorization policies/gates.

### Stage 1: Design, Planning & Tech Stack Definition (2026-07-19)
- **Actions Taken**:
  - Drafted the initial implementation strategy.
  - Structured the technical components and defined PostgreSQL as the chosen database.
  - Incorporated **Option B** for activity metrics (counting activities on leads assigned to the rep).
  - Created [tech_stack.md](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/tech_stack.md) specifying the environment and tools.
  - Created/updated [implementation_plan.md](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/implementation_plan.md) with database design, business logic, endpoints, and validation requirements.
  - Initialized this [project_log.md](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/project_log.md) to track project progress.

### Stage 2: Authentication & Seeders (2026-07-19)
- **Actions Taken**:
  - Configured Git global settings (user name and email) to ensure seamless commits.
  - Installed and configured `laravel/sanctum` for secure API token authentication.
  - Implemented [User.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Models/User.php) modifications including `HasApiTokens` trait and helper methods (`isManager()`, `isRep()`).
  - Created [LoginRequest.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Requests/LoginRequest.php) and [AuthController.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Controllers/Api/AuthController.php) for login authentication.
  - Created database factories ([LeadFactory.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/database/factories/LeadFactory.php) and [ActivityFactory.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/database/factories/ActivityFactory.php)).
  - Updated [DatabaseSeeder.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/database/seeders/DatabaseSeeder.php) to seed realistic managers, reps, leads, and activities.

### Stage 3: Leads API & Authorization (2026-07-19)
- **Actions Taken**:
  - Implemented [LeadPolicy.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Policies/LeadPolicy.php) to enforce Manager vs Rep visibility rules (Reps see only assigned leads; Managers see all).
  - Created JSON resources ([UserResource.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Resources/UserResource.php), [LeadResource.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Resources/LeadResource.php), [ActivityResource.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Resources/ActivityResource.php)) for standardized API responses.
  - Created request validators ([StoreLeadRequest.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Requests/StoreLeadRequest.php) and [UpdateLeadRequest.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Requests/UpdateLeadRequest.php)).
  - Implemented `LeadController.php` with searching, filtering, and sorting for leads.
  - Added assignment and reassignment endpoint `POST /api/leads/{id}/assign` with manager-only authorization checks.

### Stage 4: Activities API & Transition Rules (2026-07-19)
- **Actions Taken**:
  - Created request validator [StoreActivityRequest.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Requests/StoreActivityRequest.php).
  - Created [ActivityController.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Controllers/Api/ActivityController.php) to handle logging activities.
  - Enforced the Won/Lost Transition validation rule in [LeadController.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Controllers/Api/LeadController.php): status update to `won` or `lost` is blocked if no activities have been logged on the lead.
  - Created queued job [NotifyRepOfAssignment.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Jobs/NotifyRepOfAssignment.php) dispatched upon assignment.
  - Created event [LeadStatusChanged.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Events/LeadStatusChanged.php) and listener [AutoRecordStatusChangeActivity.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Listeners/AutoRecordStatusChangeActivity.php) to log status changes as history logs automatically.

### Stage 5: Performance Reporting (2026-07-19)
- **Actions Taken**:
  - Created [ReportPolicy.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Policies/ReportPolicy.php) for performance report authorization.
  - Added `activitiesThroughLeads` relation in [User.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Models/User.php) to count activities on leads assigned to reps (Option B).
  - Implemented [ReportController.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/app/Http/Controllers/Api/ReportController.php) using a highly optimized, single Eloquent aggregation query (using subqueries with `withCount` and `withSum`) to calculate the rep performance metrics.
  - Wired all endpoints in [api.php](file:///c:/Users/allle/OneDrive/Desktop/mini-crm-api/routes/api.php) under the Sanctum middleware.
