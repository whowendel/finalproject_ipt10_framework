# FlowForge

**A DIY PHP microframework for workflow and approval systems.**

FlowForge is a lightweight, educational PHP microframework engineered from scratch to teach and demonstrate front-controller architecture, custom routing, request/response lifecycle, template rendering, data validation, and an auditable workflow state machine.

Built as an authoritative reference for **IPT10: Build Your Own PHP Framework**.

---

## 1. Project Requirements & Dependency Compliance

### Hard Dependency Rule Compliance
* **Zero Third-Party Core Libraries**: FlowForge does not install or wrap Bramus Router, Laravel components, Illuminate Database/Eloquent, Mustache, Twig, Symfony HttpFoundation, Slim, or any other third-party framework packages.
* **Native Autoloading**: Includes a custom PSR-4 autoloader (`FlowForge\Core\Autoloader`) in `src/Core/Autoloader.php`. Composer is provided purely for project metadata and optional scripts.
* **Database & Persistence**: Implemented directly using PHP standard `PDO`, with SQLite (default) or MySQL/MariaDB selected in `.env`.
* **External API**: Integrates the free [Nager.Date](https://date.nager.at) public holiday API through the framework's own `HttpClient`.

---

## 2. Prerequisites & Quick Setup

### Requirements
* PHP 8.1 or higher (PHP 8.2+ recommended; tested on PHP 8.4)
* PHP extensions: `pdo`, `pdo_sqlite`, `session`, `json`

### Setup Instructions

```bash
# 1. Create your environment file (optional: .env.example is used if .env is missing)
cp .env.example .env

# 2. Create and seed the database (SQLite by default)
php database/seed.php

# 3. Start the local PHP development server
php -S 127.0.0.1:8000 -t public

# 4. In another terminal, run the automated test suite
php tests/run.php
```

Then open http://127.0.0.1:8000.

### Running under XAMPP / Apache
Copy the project folder into `htdocs` and open `http://localhost/<folder-name>/`.
The root `.htaccess` sends every request into `public/` (so `.env`, `storage/` and the source code are never served),
and `public/.htaccess` routes everything to `public/index.php`. The base URL is detected automatically, so links work
from a sub-folder, even one with spaces in its name. `mod_rewrite` must be enabled (it is by default in XAMPP).

### Using MySQL instead of SQLite
Set these in `.env`, then run `php database/seed.php` (it creates the database if it does not exist):

```
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flowforge
DB_USERNAME=root
DB_PASSWORD=
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
| `GET` | `/` | Public | Redirects to `/requests` if logged in, else `/events` |
| `GET` | `/events` | Public | Published (approved) activities, with public-holiday flags |
| `GET` | `/login` | Public | Renders login page with demo credentials helper |
| `POST` | `/login` | Public | Authenticates credentials; regenerates session ID |
| `POST` | `/logout` | Authenticated | Clears session and redirects to `/login` |
| `GET` | `/requests` | Authenticated | Lists requests (filtered by user role) |
| `GET` | `/requests/create` | Submitter | Renders draft request creation form |
| `POST` | `/requests` | Submitter | Validates & creates draft; redirects (PRG) |
| `GET` | `/requests/{id}` | Authenticated | Displays request details, status badge & audit trail |
| `GET` | `/requests/{id}/edit` | Submitter (Owner) | Edit form for a draft |
| `POST` | `/requests/{id}/update` | Submitter (Owner) | Validates and saves draft changes |
| `POST` | `/requests/{id}/delete` | Submitter (Owner) | Deletes a draft |
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
| `GET` | `/api/holidays?year=2026` | Authenticated | `200` / `502` | Public holidays for a year (from Nager.Date, cached) |
| `GET` | `/api/holidays/check?date=2026-12-25` | Authenticated | `200` / `422` / `502` | Whether a date is a public holiday |

### Middleware
| Name | Type | Purpose |
| :--- | :--- | :--- |
| `RequestLogger` | Global | Logs method, path, status and duration to `storage/logs/app.log` |
| `RejectMalformedJson` | Global | Returns `400` for unparseable JSON bodies |
| `VerifyCsrfToken` | Global | Requires the `_csrf` token on web POST/PUT/PATCH/DELETE (`419` otherwise) |
| `auth` | Route | Requires a signed-in user (redirect to `/login`, or `401` JSON) |
| `guest` | Route | Only for signed-out visitors (e.g. `/login`) |
| `role:<name>` | Route | Restricts a route to `submitter` or `reviewer` (`403`) |

### External API: Nager.Date public holidays
* Endpoint: `GET https://date.nager.at/api/v3/PublicHolidays/{year}/{countryCode}` (JSON, no API key).
* Used to warn submitters and reviewers when an event falls on a public holiday (live check on the form,
  calendar panel on the request page, flags on lists and the public Events page).
* Responses are cached for 24 hours per country and year; failures are cached for 5 minutes (or the `Retry-After`
  value) so a slow or rate-limited API never slows every page. Configure with `HOLIDAY_*` keys in `.env`.

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
IPT10 Final Project/
├── public/
│   ├── index.php                # Front controller
│   └── assets/
│       └── app.css              # Custom responsive stylesheet
├── config/
│   └── app.php                  # Configuration built from .env values
├── routes/
│   ├── web.php                  # HTML web routes
│   └── api.php                  # RESTful JSON API routes
├── src/
│   ├── Core/
│   │   ├── Application.php      # Kernel: config, sessions, middleware, error handling
│   │   ├── Config.php / Env.php # .env loader and dot-notation config access
│   │   ├── Pipeline.php         # Middleware pipeline
│   │   ├── Url.php              # Base-URL detection and link generation
│   │   ├── Autoloader.php       # Built-in zero-dependency PSR-4 autoloader
│   │   ├── Database.php         # SQLite PDO wrapper with foreign keys
│   │   ├── Request.php          # HTTP request abstraction & JSON parser
│   │   ├── Response.php         # HTTP response builder (HTML/JSON/redirects)
│   │   ├── Router.php           # Custom regex route matcher
│   │   └── View.php             # Native PHP template renderer with layouts
│   ├── Http/                    # HttpClient + HttpResponse for external APIs
│   ├── Middleware/              # Global middleware (logging, JSON guard, CSRF)
│   ├── Support/
│   │   ├── Cache.php / Logger.php # File cache and file logger
│   │   ├── Csrf.php             # Session-bound CSRF token guard
│   │   └── Validator.php        # Input validation engine
│   └── Workflow/
│       └── WorkflowEngine.php   # Centralized workflow state machine
├── app/
│   ├── Controllers/             # Auth, Request, Approval, Event, Holiday controllers
│   ├── Middleware/              # Authenticate, RedirectIfAuthenticated, RequireRole
│   ├── Models/                  # User, ActivityRequest entity objects
│   ├── Repositories/            # SQL query isolation with prepared statements
│   └── Services/                # AuthService, WorkflowService, HolidayService (external API)
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
│   ├── FrameworkFeaturesTest.php # Env, URLs, middleware, holiday API (fake HTTP client)
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
