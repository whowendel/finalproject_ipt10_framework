# FlowForge Demonstration Guide

This script walks through the end-to-end evaluation of the FlowForge microframework and the Student Activity Request System.

---

## 1. Start the Application

In your terminal, seed the SQLite database and start the built-in PHP development server:

```bash
# Seed database with initial users and requests
php database/seed.php

# Start PHP built-in web server
php -S 127.0.0.1:8000 -t public
```

Open your browser to: **`http://127.0.0.1:8000`**

---

## 2. Submitter Workflow (Draft & Submission)

1. **Sign In as Submitter**:
   - URL: `http://127.0.0.1:8000/login`
   - Email: `student@flowforge.test`
   - Password: `password123`
   - Notice the green success alert upon login and the top navigation bar reflecting the student submitter role.

2. **Create an Activity Request**:
   - Click **"+ Create Request"** or go to `http://127.0.0.1:8000/requests/create`.
   - Leave the title blank and click "Save Draft" &rarr; Observe the 422 validation error message: *"The Title field is required."*
   - Fill in valid information:
     - **Title**: `AI & Robotics Innovation Hackathon`
     - **Event Date**: `2026-12-18`
     - **Venue**: `Innovation Lab 4`
     - **Description**: `A 24-hour hackathon designed to promote hands-on robotics development.`
   - Click **"Save Draft"**.
   - Notice the application redirects to `http://127.0.0.1:8000/requests/{id}` using the **Post/Redirect/Get (PRG)** pattern, with status badge **DRAFT**.

3. **Submit the Draft for Review**:
   - In the blue workflow panel, optionally enter submission remarks: *"Ready for departmental review"*.
   - Click **"Submit for Faculty Review"**.
   - The status badge transitions from **DRAFT** to **SUBMITTED**.
   - Check the **Approval & Workflow History** table at the bottom: an audit entry records the submit action with timestamp and actor details.

4. **Log Out**:
   - Click **Logout** in the navigation bar.

---

## 3. Reviewer Workflow (Queue, Approval & Rejection)

1. **Sign In as Reviewer**:
   - Email: `reviewer@flowforge.test`
   - Password: `password123`
   - You are automatically routed to the **Reviewer Queue** (`/reviewer/queue`).

2. **Approve a Request**:
   - In the pending queue, select the newly submitted request or request `#2`.
   - Click **"Review & Decide"**.
   - Under *Approve Request*, add an optional remark: *"Approved under standard university guidelines."*
   - Click **"Confirm Approval"**.
   - Status updates to **APPROVED**.
   - Chronological history records the approval action.

3. **Reject a Request with Required Remarks**:
   - Open another submitted request.
   - Under *Reject Request*, leave the remarks empty and attempt to submit &rarr; The browser form validation and server validation reject the action.
   - Enter rejection remark: *"Venue is undergoing maintenance during the requested weekend."*
   - Click **"Reject Proposal"**.
   - Status updates to **REJECTED**.
   - Chronological history logs the rejection and reason.

---

## 4. Workflow Guardrails & Authorization Demonstrations

1. **Terminal State Immutability**:
   - On an approved or rejected request, observe that workflow action buttons are no longer displayed.
   - Even if a client manually sends a POST to `/requests/{id}/approve`, the `WorkflowEngine` returns an error: *"Cannot transition from final status"*.

2. **Server-Side Authorization**:
   - Log back in as `student@flowforge.test`.
   - Attempt to access the reviewer queue at `http://127.0.0.1:8000/reviewer/queue`.
   - The server displays a **403 Access Forbidden** page.

---

## 5. JSON API Demonstrations

Test the JSON API using `curl` or any API client:

### Health Check (Public)
```bash
curl -i http://127.0.0.1:8000/api/health
```
*Expected: `200 OK` with JSON payload containing system status and framework name.*

### Malformed JSON Detection
```bash
curl -i -X POST http://127.0.0.1:8000/api/requests \
  -H "Content-Type: application/json" \
  -d '{"title": "Broken JSON...'
```
*Expected: `400 Bad Request` with `Malformed JSON payload` error message.*

---

## 6. Run the Automated Test Suite

Run the zero-dependency test suite:

```bash
php tests/run.php
```

All 84 automated assertions will execute and pass, verifying the Router, Request parser, Validator, WorkflowEngine, and transactional database integrity.
