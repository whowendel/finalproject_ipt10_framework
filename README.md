# FlowForge

**A DIY PHP microframework for workflow and approval systems.**

FlowForge is a lightweight, educational PHP microframework engineered from scratch to teach and demonstrate front-controller architecture, custom routing, request/response lifecycle, template rendering, data validation, and an auditable workflow state machine.

Built as an authoritative reference for **IPT10: Build Your Own PHP Framework**.

---

## 1. Project Requirements & Dependency Compliance

### Hard Dependency Rule Compliance
* **Zero Third-Party Core Libraries**: FlowForge does not install or wrap Bramus Router, Laravel components, Illuminate Database/Eloquent, Mustache, Twig, Symfony HttpFoundation, Slim, or any other third-party framework packages.
* **Native Autoloading**: Includes a custom PSR-4 autoloader (`FlowForge\Core\Autoloader`) in `src/Core/Autoloader.php`. Composer is provided purely for project metadata and optional scripts.
* **Database & Persistence**: Implemented directly using PHP standard `PDO` and SQLite (`pdo_sqlite`).

---

## 2. Prerequisites & Quick Setup

### Requirements
* PHP 8.1 or higher (PHP 8.2+ recommended; tested on PHP 8.4)
* PHP extensions: `pdo`, `pdo_sqlite`, `session`, `json`

### Setup Instructions
Clone the repository or navigate to the project directory:

```bash
# 1. Initialize and seed the SQLite database
php database/seed.php

# 2. Start the local PHP development server
php -S 127.0.0.1:8000 -t public

# 3. In another terminal, run the automated test suite
php tests/run.php
```

---

## 3. Demo Accounts & Credentials

The application is pre-seeded with test accounts for immediate local evaluation:

| Role | Name | Email | Password | Allowed Capabilities |
| :--- | :--- | :--- | :--- | :--- |
| **Submitter** (Student) | Alice Submitter | `student@flowforge.test` | `password123` | Create draft requests, view owned requests, submit drafts |
| **Reviewer** (Faculty) | Dr. Bob Reviewer | `reviewer@flowforge.test` | `password123` | View reviewer queue, approve submitted requests, reject with remarks |
| **Submitter** (Student) | Charlie Student | `charlie@flowforge.test` | `password123` | Additional submitter account for ownership boundary tests |

---

## 4. Route Contract Reference

### HTML Web Routes
| Method | Path | Access | Handler & Result |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | Public | Redirects to `/requests` if logged in, else `/login` |
| `GET` | `/login` | Public | Renders login page with demo credentials helper |
| `POST` | `/login` | Public | Authenticates credentials; regenerates session ID |
| `POST` | `/logout` | Authenticated | Clears session and redirects to `/login` |
| `GET` | `/requests` | Authenticated | Lists requests (filtered by user role) |
| `GET` | `/requests/create` | Submitter | Renders draft request creation form |
| `POST` | `/requests` | Submitter | Validates & creates draft; redirects (PRG) |
| `GET` | `/requests/{id}` | Authenticated | Displays request details, status badge & audit trail |
| `POST` | `/requests/{id}/submit` | Submitter (Owner) | Submits draft for faculty review |
| `GET` | `/reviewer/queue` | Reviewer | Lists all requests pending faculty determination |
| `POST` | `/requests/{id}/approve`| Reviewer | Approves submitted request (optional remarks) |
| `POST` | `/requests/{id}/reject` | Reviewer | Rejects submitted request (mandatory remarks) |

### JSON API Endpoints
| Method | Endpoint | Access | Status | Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/health` | Public | `200` | Health check and framework version payload |
| `GET` | `/api/requests` | Authenticated | `200` | JSON list of activity requests |
| `GET` | `/api/requests/{id}` | Authenticated | `200` / `404` | Request attributes and chronological audit history |
| `POST` | `/api/requests` | Submitter | `201` / `422` | Creates a new draft request |
| `PUT/PATCH`| `/api/requests/{id}` | Submitter (Owner) | `200` / `422` | Updates draft request attributes |
| `DELETE` | `/api/requests/{id}` | Submitter (Owner) | `200` / `422` | Deletes draft request (approved/rejected cannot be deleted) |
| `POST` | `/api/requests/{id}/submit` | Submitter (Owner) | `200` / `422` | Submits draft via WorkflowService |
| `POST` | `/api/requests/{id}/approve`| Reviewer | `200` / `422` | Approves request via WorkflowService |
| `POST` | `/api/requests/{id}/reject` | Reviewer | `200` / `422` | Rejects request via WorkflowService |

---

## 5. Workflow State Machine Rules

FlowForge enforces status transitions strictly in the central `FlowForge\Workflow\WorkflowEngine`:

```
                ┌─────────────────────────────────┐
                │             [Draft]             │
                └────────────────┬────────────────┘
                                 │
                   Action: submit│ (Role: Submitter / Owner)
                                 ▼
                ┌─────────────────────────────────┐
                │           [Submitted]           │
                └────────┬───────────────┬────────┘
                         │               │
           Action:approve│               │Action: reject (with remarks)
        (Role: Reviewer) │               │(Role: Reviewer)
                         ▼               ▼
                ┌────────────────┐ ┌────────────────┐
                │   [Approved]   │ │   [Rejected]   │
                │(Terminal State)│ │(Terminal State)│
                └────────────────┘ └────────────────┘
```

### Transactional Transition Guarantees
Every state change executed by `WorkflowService::transition()`:
1. Loads the target request and confirms existence.
2. Validates user role and ownership.
3. Consults `WorkflowEngine` for legal transition paths.
4. Enforces rejection remark requirements.
5. Initiates a `PDO` transaction (`beginTransaction()`).
6. Atomically updates the request status and inserts an immutable row into `approval_history`.
7. Commits the transaction or rolls back on any write failure so status and audit history never disagree.

---

## 6. Architecture & Directory Tree

```
c:/Users/wende/OneDrive/Desktop/IPT10 Final Project/
├── public/
│   ├── index.php                # Front controller
│   └── assets/
│       └── app.css              # Custom responsive stylesheet
├── config/
│   └── app.php                  # Framework & database configuration
├── routes/
│   ├── web.php                  # HTML web routes
│   └── api.php                  # RESTful JSON API routes
├── src/
│   ├── Core/
│   │   ├── Application.php      # Kernel & lifecycle dispatcher
│   │   ├── Autoloader.php       # Built-in zero-dependency PSR-4 autoloader
│   │   ├── Database.php         # SQLite PDO wrapper with foreign keys
│   │   ├── Request.php          # HTTP request abstraction & JSON parser
│   │   ├── Response.php         # HTTP response builder (HTML/JSON/redirects)
│   │   ├── Router.php           # Custom regex route matcher
│   │   └── View.php             # Native PHP template renderer with layouts
│   ├── Support/
│   │   ├── Csrf.php             # Session-bound CSRF token guard
│   │   └── Validator.php        # Input validation engine
│   └── Workflow/
│       └── WorkflowEngine.php   # Centralized workflow state machine
├── app/
│   ├── Controllers/             # AuthController, RequestController, ApprovalController
│   ├── Models/                  # User, ActivityRequest entity objects
│   ├── Repositories/            # SQL query isolation with prepared statements
│   └── Services/                # AuthService, WorkflowService
├── views/
│   ├── layouts/main.php         # Global application layout
│   ├── auth/login.php           # Login page
│   ├── requests/                # index.php, create.php, show.php
│   ├── reviewer/queue.php       # Faculty review queue
│   └── errors/                  # 403.php, 404.php, 419.php, 500.php
├── database/
│   ├── schema.sql               # SQLite DDL schema
│   └── seed.php                 # Deterministic seed script
├── tests/
│   ├── TestCase.php             # Test assertion utility
│   ├── RouterTest.php           # Routing unit tests
│   ├── RequestTest.php          # Request parsing unit tests
│   ├── ValidatorTest.php        # Validation rules unit tests
│   ├── WorkflowEngineTest.php   # Workflow transition unit tests
│   ├── SecurityAndWorkflowIntegrationTest.php # End-to-end integration tests
│   └── run.php                  # Test runner executable
├── storage/                     # SQLite database file directory
├── composer.json                # Project metadata
├── DESIGN.md                    # Framework design notes & lifecycle explanation
├── DEMO.md                      # Repeatable step-by-step demonstration guide
└── README.md
```

---

## 7. Security Safeguards & Limitations

### Implemented Safeguards
* **SQL Injection**: All database operations in `Repositories/` use `PDO::prepare()` and parameterized bindings. No raw user input is concatenated into SQL queries.
* **Cross-Site Scripting (XSS)**: Templates escape dynamic output via `FlowForge\Core\View::e()` (`htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`).
* **Cross-Site Request Forgery (CSRF)**: All state-changing web POST forms include session-bound tokens verified server-side with `hash_equals()`.
* **Session Fixation**: Calls `session_regenerate_id(true)` upon successful user login.
* **Mass Assignment Protection**: Request controllers strictly allowlist accepted input keys before database insertion.

### Limitations & Non-Goals
* Educational microframework intended for single-node deployments.
* Multi-stage configurable workflow graphs, asynchronous message queues, OAuth2, and SPA client frameworks are intentionally omitted.
