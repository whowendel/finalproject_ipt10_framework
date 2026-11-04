# FlowForge Architecture and Framework Design Note

## 1. Overview
FlowForge is a lightweight, purpose-built PHP microframework engineered from scratch for workflow and approval systems. It strictly follows the "Core means custom" directive: no third-party routers, ORMs, template engines, or HTTP middleware packages are used. FlowForge demonstrates clear separation of concerns, defensive security baselines, and transactional workflow transition integrity.

---

## 2. Request Lifecycle

The lifecycle of an HTTP request through FlowForge proceeds through distinct architectural layers:

```
Browser / HTTP Client (Web or API)
         │
         ▼
public/index.php (Front Controller)
         │  • Autoloads classes via PSR-4 (Composer or FlowForge Autoloader)
         │  • Boots Application kernel
         ▼
FlowForge\Core\Application
         │  • Initializes configuration and sessions
         │  • Prepares views directory
         │  • Captures Request
         │  • Validates JSON payload syntax and CSRF tokens
         ▼
FlowForge\Core\Router
         │  • Matches HTTP method and normalized URI
         │  • Extracts route parameters (e.g. /requests/{id})
         │  • Dispatches to controller action or returns 404 / 405
         ▼
App\Controllers (e.g. RequestController, ApprovalController)
   ├── Input Validation: FlowForge\Support\Validator
   ├── Workflow Coordination: App\Services\WorkflowService
   │      ├── Rule Evaluation: FlowForge\Workflow\WorkflowEngine
   │      └── Data Persistence: App\Repositories (via FlowForge\Core\Database)
   └── Response Generation: FlowForge\Core\Response or FlowForge\Core\View
         │
         ▼
HTTP Response (HTML or JSON with proper status codes & headers)
```

---

## 3. Why Each Core Class Exists

### Core Framework (`src/Core/`)
* **`Autoloader.php`**: Provides a zero-dependency, PSR-4 compliant autoloader. Allows the framework and demo app to run immediately without requiring `composer install`.
* **`Application.php`**: The framework kernel. Boots configuration, controls session start, inspects request hygiene (detecting malformed JSON), enforces CSRF verification for state-changing browser requests, catches unhandled exceptions, and maps them to content-aware responses (JSON for `/api/*` and HTML error pages for browser clients).
* **`Request.php`**: Encapsulates incoming HTTP request data (`$_SERVER`, `$_GET`, `$_POST`, raw body, headers). Normalizes URI paths, provides accessors, and parses incoming JSON payloads while detecting syntax errors.
* **`Response.php`**: Represents the outgoing HTTP response. Manages HTTP status codes, headers (e.g. `Content-Type`, `Allow`, `Location`), and payload output (HTML, JSON, redirects).
* **`Router.php`**: Compiles route patterns into regexes with named parameters (e.g. `(?P<id>[^/]+)`). Matches requests by method and path. Distinguishes between unknown routes (404) and route matches with unsupported methods (405 with `Allow` header).
* **`View.php`**: Lightweight native PHP view renderer. Supports nested layouts, data isolation via `extract()`, output buffering, and safe HTML escaping via `View::e()` (`htmlspecialchars`).
* **`Database.php`**: Centralized SQLite PDO wrapper. Enforces SQLite foreign key constraints (`PRAGMA foreign_keys = ON;`), enables exception error modes, and configures WAL journaling.

### Support & Security (`src/Support/`)
* **`Validator.php`**: Field validation utility supporting `required`, `max:N`, `min:N`, `in:list`, `date` (YYYY-MM-DD), and `email`. Produces structured field-level errors consumed by both HTML forms and the JSON API (status 422).
* **`Csrf.php`**: Session-bound cryptographic anti-forgery token generator and verifier using `random_bytes()` and `hash_equals()`. Protects state-changing browser POST requests.

### Workflow Engine (`src/Workflow/`)
* **`WorkflowEngine.php`**: Pure domain state machine. Contains authoritative business rules defining valid transitions:
  * `draft` $\rightarrow$ `submit` $\rightarrow$ `submitted` (Owner / Submitter role only)
  * `submitted` $\rightarrow$ `approve` $\rightarrow$ `approved` (Reviewer role only)
  * `submitted` $\rightarrow$ `reject` $\rightarrow$ `rejected` (Reviewer role only, requires non-empty remarks)
  * Terminal states: `approved` and `rejected` cannot transition further.
  * Centralizes transition logic so state checks are never duplicated across controllers.

---

## 4. Application Architecture (`app/`)

* **Models (`User`, `ActivityRequest`)**: Simple typed entity representations.
* **Repositories (`UserRepository`, `RequestRepository`, `ApprovalHistoryRepository`)**: Own all SQL queries using prepared statements with parameter binding to prevent SQL injection.
* **Services**:
  * **`AuthService`**: Manages session-based authentication, password verification using `password_verify()`, session regeneration to prevent fixation attacks, and role checks.
  * **`WorkflowService`**: Coordinates the 8-step transactional transition lifecycle:
    1. Loads request and verifies existence (404 if missing).
    2. Validates user role and ownership.
    3. Queries `WorkflowEngine` for state validity.
    4. Enforces rejection remarks.
    5. Begins database transaction (`PDO::beginTransaction()`).
    6. Updates request status and `updated_at`.
    7. Inserts audit record into `approval_history`.
    8. Commits transaction (or rolls back on write failure).

---

## 5. Architectural Differences from Standard IPT10 Sample

1. **Dedicated Workflow Subsystem**: While standard IPT10 samples focus on simple CRUD tables, FlowForge introduces an auditable state machine with strict role-based transitions and transactional history logging.
2. **Shared Service Layer**: Both HTML controllers and JSON API endpoints consume the exact same `WorkflowService` and `Validator`, ensuring complete consistency across interfaces.
3. **Dual Content-Aware Error Handling**: Unhandled exceptions and validation errors automatically format as HTML views for web browsers or JSON envelopes for API clients.
