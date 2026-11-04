-- FlowForge SQLite Database Schema
-- Foreign keys must be enabled in SQLite connection: PRAGMA foreign_keys = ON;

PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS approval_history;
DROP TABLE IF EXISTS activity_requests;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('submitter', 'reviewer')),
    created_at TEXT NOT NULL
);

CREATE TABLE activity_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    event_date TEXT NOT NULL,
    venue TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('draft', 'submitted', 'approved', 'rejected')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE approval_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    request_id INTEGER NOT NULL REFERENCES activity_requests(id) ON DELETE CASCADE,
    actor_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    action TEXT NOT NULL,
    remarks TEXT NULL,
    from_status TEXT NULL,
    to_status TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE INDEX idx_activity_requests_user ON activity_requests(user_id);
CREATE INDEX idx_activity_requests_status ON activity_requests(status);
CREATE INDEX idx_approval_history_request ON approval_history(request_id);
CREATE INDEX idx_approval_history_actor ON approval_history(actor_id);
