# Mini-CRM API Detailed Demonstration & Testing Guide

This guide provides step-by-step instructions to test and demonstrate the core features of the Mini-CRM API using an API client (like Postman or Insomnia) or PowerShell terminal commands.

---

## 1. Setup Quick Reference (Windows PowerShell)

Before starting the demo, ensure the containers are running and the database is freshly seeded with clean mock data:

```powershell
# 1. Spin up Docker containers in the background
wsl bash -c "cd /mnt/c/Users/allle/OneDrive/Desktop/mini-crm-api && ./vendor/bin/sail up -d"

# 2. Reset database and run seeder
wsl bash -c "cd /mnt/c/Users/allle/OneDrive/Desktop/mini-crm-api && ./vendor/bin/sail artisan migrate:fresh --seed"
```

---

## 2. Default Seeded Credentials

Use these pre-configured user credentials during the demonstration:

* **Manager User**:
  * Email: `manager@crm.com`
  * Password: `password`
* **Sales Rep 1**:
  * Email: `rep1@crm.com`
  * Password: `password`
* **Sales Rep 2**:
  * Email: `rep2@crm.com`
  * Password: `password`

---

## 3. Core Demonstration Scenarios

### Request 1: Authenticate & Retrieve Tokens (`POST /api/login`)
* **Purpose**: Demonstrates user authentication, input validation, and role-based token issue.
* **HTTP Method**: `POST`
* **URL**: `http://localhost:8080/api/login`
* **Headers**:
  * `Accept`: `application/json`
  * `Content-Type`: `application/json`

#### Demonstration Steps:
1. **Login as Manager**: Send this body in the request:
   ```json
   {
     "email": "manager@crm.com",
     "password": "password"
   }
   ```
   * **Expected Response**: `200 OK`
     ```json
     {
       "access_token": "1|laravel_sanctum_token_string...",
       "token_type": "Bearer",
       "user": {
         "id": 1,
         "name": "Manager User",
         "email": "manager@crm.com",
         "role": "manager"
       }
     }
     ```
   * **Action**: Copy the `access_token` value. You will need it for subsequent requests.

2. **Login as Sales Rep 1**: Send this body:
   ```json
   {
     "email": "rep1@crm.com",
     "password": "password"
   }
   ```
   * **Expected Response**: `200 OK` showing role `"role": "rep"`. Copy Rep 1's token.

3. **PowerShell cURL Command**:
   ```powershell
   curl -X POST http://localhost:8080/api/login `
     -H "Accept: application/json" `
     -H "Content-Type: application/json" `
     -d '{"email":"manager@crm.com", "password":"password"}'
   ```

---

### Request 2: List Leads with Policies, Filters, Search & Sort (`GET /api/leads`)
* **Purpose**: Demonstrates role-based visibility policies, searching, filtering, and pagination.
* **HTTP Method**: `GET`
* **URL**: `http://localhost:8080/api/leads`
* **Headers**:
  * `Accept`: `application/json`
  * `Authorization`: `Bearer <paste_your_token_here>`

#### Demonstration Steps:
1. **Show Rep Visibility Restriction**: Use **Rep 1's Token** to make the request.
   * **Expected Result**: The API returns only leads assigned to Rep 1.
2. **Show Manager Unlimited Visibility**: Use the **Manager's Token** to make the request.
   * **Expected Result**: The manager receives all leads, including unassigned leads and leads assigned to other reps.
3. **Show Searching**: Add a search parameter (e.g. `?search=google`):
   * **URL**: `http://localhost:8080/api/leads?search=google`
   * **Expected Result**: Returns leads where the name, email, or company matches the query case-insensitively.
4. **Show Filtering**: Add status and source filters:
   * **URL**: `http://localhost:8080/api/leads?status=qualified&source=web`
5. **Show Sorting**: Sort by expected monetary value descending:
   * **URL**: `http://localhost:8080/api/leads?sort_by=expected_value&sort_order=desc`

6. **PowerShell cURL Command (Replace token)**:
   ```powershell
   curl -X GET "http://localhost:8080/api/leads?status=new" `
     -H "Accept: application/json" `
     -H "Authorization: Bearer <MANAGER_OR_REP_TOKEN>"
   ```

---

### Request 3: Won/Lost Transition Restriction (`PATCH /api/leads/{id}`)
* **Purpose**: Demonstrates strict pipeline transition validation rules and automatic status change logging.
* **HTTP Method**: `PATCH`
* **URL**: `http://localhost:8080/api/leads/1` (Pick a new lead ID with **0 activities**)
* **Headers**:
  * `Accept`: `application/json`
  * `Content-Type`: `application/json`
  * `Authorization`: `Bearer <rep_token_or_manager_token>`

#### Demonstration Steps:
1. **Step 1 - Try status transition to Won/Lost (Fails)**: Send a request to change status to `won`:
   ```json
   {
     "status": "won"
   }
   ```
   * **Expected Response**: `422 Unprocessable Entity`
     ```json
     {
       "message": "A lead must have at least one activity logged before its status can be changed to won or lost.",
       "errors": {
         "status": [
           "A lead must have at least one activity logged before its status can be changed to won or lost."
         ]
       }
     }
     ```
2. **Step 2 - Log an Activity on the Lead (Succeeds)**: Send a `POST` request to `http://localhost:8080/api/leads/1/activities` with this body:
   ```json
   {
     "type": "call",
     "body": "Discussed commercial terms and verified project scope."
   }
   ```
   * **Expected Response**: `201 Created`. An activity is now recorded.
3. **Step 3 - Re-try status transition to Won/Lost (Succeeds)**: Re-send the `PATCH` request to `http://localhost:8080/api/leads/1` with `"status": "won"`.
   * **Expected Response**: `200 OK` (Lead status updated successfully).
   * **Under the Hood**: An event is dispatched (`LeadStatusChanged`) and listener (`AutoRecordStatusChangeActivity`) automatically appends a system activity note recording this transition history log!

4. **PowerShell cURL Command for Step 1 (Fails)**:
   ```powershell
   curl -X PATCH http://localhost:8080/api/leads/1 `
     -H "Accept: application/json" `
     -H "Content-Type: application/json" `
     -H "Authorization: Bearer <TOKEN>" `
     -d '{"status":"won"}'
   ```

---

### Request 4: Rep Performance Report (`GET /api/reports/rep-performance`)
* **Purpose**: Demonstrates scalable PostgreSQL database query aggregations and data scoping.
* **HTTP Method**: `GET`
* **URL**: `http://localhost:8080/api/reports/rep-performance`
* **Headers**:
  * `Accept`: `application/json`
  * `Authorization`: `Bearer <token>`

#### Demonstration Steps:
1. **Show Rep Isolation**: Send the request using **Rep 1's Token**.
   * **Expected Result**: A single JSON object array containing only Rep 1's performance data.
2. **Show Manager Overview**: Send the request using the **Manager's Token**.
   * **Expected Result**: Complete overview array containing metrics for all reps.
     ```json
     [
       {
         "rep_id": 2,
         "name": "Rep One",
         "email": "rep1@crm.com",
         "total_leads": 10,
         "status_counts": {
           "new": 3,
           "contacted": 3,
           "qualified": 2,
           "won": 1,
           "lost": 1
         },
         "total_expected_value": "134500.00",
         "won_expected_value": "25000.00",
         "total_activities": 12
       },
       ...
     ]
     ```
   * *Highlight*: The query uses subqueries (`withCount` and `withSum`) to run entirely at the database level in a single query, preventing N+1 loops and easily supporting 100k+ leads.

3. **PowerShell cURL Command**:
   ```powershell
   curl -X GET http://localhost:8080/api/reports/rep-performance `
     -H "Accept: application/json" `
     -H "Authorization: Bearer <MANAGER_OR_REP_TOKEN>"
   ```
