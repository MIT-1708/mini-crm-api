# Postman Step-by-Step Navigation Guide

This guide walks you through the exact clicks, tabs, and buttons in **Postman** to test and demonstrate the 4 core API requests.

---

## Preparation: Open Postman
1. Start the **Postman** application on your computer.
2. Make sure your local server is running by opening Windows PowerShell and running:
   ```powershell
   wsl bash -c "cd /mnt/c/Users/allle/OneDrive/Desktop/mini-crm-api && ./vendor/bin/sail up -d"
   ```

---

## Request 1: Authenticate & Login (`POST /api/login`)

This step retrieves your API Token.

### Step-by-Step Clicks:
1. Click the **`+` (New Tab)** button near the top of the Postman window.
2. In the request method dropdown (which currently says **`GET`**), click it and select **`POST`**.
3. In the URL input box next to the method dropdown, paste:
   ```text
   http://localhost:8080/api/login
   ```
4. Click the **Headers** tab (located below the URL bar, next to *Params* and *Authorization*).
5. In the **Key** column of the first row, type `Accept`. In the **Value** column of that row, type `application/json`.
6. Click the **Body** tab (located next to the *Headers* tab).
7. Directly below the tabs, select the **raw** radio button.
8. On the far right of those options, click the format dropdown (which says `Text` by default) and select **JSON**.
9. In the large text area below, copy and paste the manager credentials:
   ```json
   {
     "email": "manager@crm.com",
     "password": "password"
   }
   ```
10. Click the blue **Send** button on the far right of the URL bar.
11. Scroll down to the **Response** section at the bottom to see the output.
12. **Double-click and copy** the long string inside `"access_token"` (do not copy the quotes, just the token value, e.g., `1|laravel_sanctum_token...`).

---

## Request 2: List Leads with Scopes (`GET /api/leads`)

This step shows how a Manager sees all leads, while a Sales Rep only sees their own assigned leads.

### Step-by-Step Clicks:
1. Click the **`+` (New Tab)** button to open a new tab.
2. Leave the method dropdown set to **`GET`**.
3. In the URL input box, paste:
   ```text
   http://localhost:8080/api/leads
   ```
4. Click the **Headers** tab. In the **Key** column, type `Accept` and in the **Value** column, type `application/json`.
5. Click the **Authorization** tab (located next to *Params* and *Headers*).
6. Click the **Type** dropdown list and select **Bearer Token**.
7. In the **Token** text box on the right, paste the token you copied from Request 1.
8. Click the blue **Send** button.
9. **Show the Reviewer**: In the response body at the bottom, notice that all leads in the database are returned because you logged in as a **Manager**.
10. **Demo Sorting & Searching**:
    * Click the **Params** tab (next to *Authorization*).
    * Under **Query Params**, on the first row: Key = `search`, Value = `google`.
    * On the second row: Key = `sort_by`, Value = `expected_value`.
    * On the third row: Key = `sort_order`, Value = `desc`.
    * Click **Send** to show the filtered and sorted list.


---

## Request 3: Won/Lost Transition Restriction (`PATCH /api/leads/{id}`)

This step shows that a lead cannot be marked "won" or "lost" unless it has at least one logged activity.

### Step-by-Step Clicks:
1. Click the **`+` (New Tab)** button to open a new tab.
2. Click the request method dropdown and select **`PATCH`**.
3. In the URL input box, paste (we will use lead ID `1`):
   ```text
   http://localhost:8080/api/leads/1
   ```
4. Click the **Headers** tab. Add Key = `Accept`, Value = `application/json`.
5. Click the **Authorization** tab. Set Type = **Bearer Token**, and paste your Manager token.
6. Click the **Body** tab. Select **raw** and choose **JSON** in the format dropdown.
7. Paste this payload to try to change the status to `won`:
   ```json
   {
     "status": "won"
   }
   ```
8. Click **Send**.
9. **Show the Reviewer**: The request fails with a `422 Unprocessable Entity` status code, and the JSON error message explains that the lead has no activities logged.
10. **Log an Activity to Fix It**:
    * Click the **`+` (New Tab)** button to open a new tab.
    * Select **`POST`** in the method dropdown.
    * Enter URL: `http://localhost:8080/api/leads/1/activities`
    * Click **Headers** tab: Add `Accept` = `application/json`.
    * Click **Authorization** tab: Set Type = **Bearer Token** and paste the token.
    * Click **Body** tab: Select **raw** -> **JSON**.
    * Paste this payload:
      ```json
      {
        "type": "call",
        "body": "Spoke to the client, agreed on terms."
      }
      ```
    * Click **Send** (Returns `201 Created`).
11. **Complete the Transition**:
    * Switch back to the **`PATCH /api/leads/1`** tab.
    * Click **Send** again.
    * **Show the Reviewer**: The request now succeeds with `200 OK` because the activity check passes!

---

## Request 4: Scalable Rep Performance Report (`GET /api/reports/rep-performance`)

This step shows the aggregated performance report.

### Step-by-Step Clicks:
1. Click the **`+` (New Tab)** button to open a new tab.
2. Leave the method dropdown set to **`GET`**.
3. In the URL input box, paste:
   ```text
   http://localhost:8080/api/reports/rep-performance
   ```
4. Click the **Headers** tab. Add Key = `Accept`, Value = `application/json`.
5. Click the **Authorization** tab. Set Type = **Bearer Token** and paste your Manager token.
6. Click **Send**.
7. **Show the Reviewer**: You see the performance data for all sales reps in a single report (with status counts, expected values, and activity counts).

---

## Request 5: Verify Sales Rep Scoping (Role-based Checks)

This step verifies that a Sales Rep can only access their own data.

### Step 1: Login as a Sales Rep
1. Go back to your **`POST /api/login`** tab (or open a new tab with method `POST` and URL `http://localhost:8080/api/login`).
2. In the **Body** tab (raw, JSON), replace the manager credentials with the sales rep credentials:
   ```json
   {
     "email": "rep1@crm.com",
     "password": "password"
   }
   ```
3. Click **Send**.
4. Double-click and copy the new `"access_token"` (this is the Sales Rep's token).

### Step 2: Check Scoped Leads List
1. Switch to your **`GET /api/leads`** tab.
2. In the **Authorization** tab under **Bearer Token**, replace the Manager token with the **Sales Rep** token you just copied.
3. Click **Send**.
4. **Show the Reviewer**: In the response body, notice that the list of leads has been filtered. Only leads assigned to `rep1@crm.com` are shown; all unassigned leads and leads assigned to other reps are automatically hidden.

### Step 3: Check Scoped Performance Report
1. Switch to your **`GET /api/reports/rep-performance`** tab.
2. In the **Authorization** tab under **Bearer Token**, replace the Manager token with the **Sales Rep** token.
3. Click **Send**.
4. **Show the Reviewer**: Notice that the response contains only a single row with the performance statistics for `rep1@crm.com`. The rep is blocked from seeing any other representative's metrics.

---

## Request 6: Lead Assignment Validation (`POST /api/leads/{id}/assign`)

This step verifies that leads can only be assigned to users with the `rep` role.

### Step-by-Step Clicks:
1. Click the **`+` (New Tab)** button to open a new tab.
2. Click the request method dropdown and select **`POST`**.
3. In the URL input box, paste:
   ```text
   http://localhost:8080/api/leads/1/assign
   ```
4. Click the **Headers** tab. Add Key = `Accept`, Value = `application/json`.
5. Click the **Authorization** tab. Set Type = **Bearer Token** and paste your **Manager** token (from Request 1).
6. Click the **Body** tab. Select **raw** and choose **JSON** in the format dropdown.
7. Paste this payload to try to assign to a Manager (ID `1`):
   ```json
   {
     "assigned_to": 1
   }
   ```
8. Click **Send**.
9. **Show the Reviewer**: The request fails with a `422 Unprocessable Entity` error because managers cannot be assigned leads.
10. Update the payload to assign to a Rep (ID `2`):
    ```json
    {
      "assigned_to": 2
    }
    ```
11. Click **Send**.
12. **Show the Reviewer**: The request succeeds with `200 OK`, successfully assigning the lead to Rep 1.
