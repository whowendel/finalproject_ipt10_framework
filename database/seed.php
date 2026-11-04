<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Core/Autoloader.php';
\FlowForge\Core\Autoloader::register();

use FlowForge\Core\Database;

echo "--- FlowForge Database Setup & Seeding ---\n";

$config = require __DIR__ . '/../config/app.php';
$dbPath = $config['database']['path'];

echo "Database path: {$dbPath}\n";

$pdo = Database::connect($dbPath);

// Execute Schema
$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schemaSql);
echo "[✓] Schema created successfully.\n";

// Seed Users
$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, :created_at)');

$now = date('Y-m-d H:i:s');
$passwordHash = password_hash('password123', PASSWORD_DEFAULT);

$users = [
    [
        'name' => 'Alice Submitter',
        'email' => 'student@flowforge.test',
        'password_hash' => $passwordHash,
        'role' => 'submitter',
        'created_at' => $now,
    ],
    [
        'name' => 'Dr. Bob Reviewer',
        'email' => 'reviewer@flowforge.test',
        'password_hash' => $passwordHash,
        'role' => 'reviewer',
        'created_at' => $now,
    ],
    [
        'name' => 'Charlie Student',
        'email' => 'charlie@flowforge.test',
        'password_hash' => $passwordHash,
        'role' => 'submitter',
        'created_at' => $now,
    ]
];

foreach ($users as $user) {
    $stmt->execute($user);
}
echo "[✓] Seeded demo users.\n";
echo "    - Submitter: student@flowforge.test / password123\n";
echo "    - Reviewer:  reviewer@flowforge.test / password123\n";
echo "    - Submitter: charlie@flowforge.test / password123\n";

// Seed Initial Activity Requests & History
$reqStmt = $pdo->prepare('INSERT INTO activity_requests (id, user_id, title, description, event_date, venue, status, created_at, updated_at) VALUES (:id, :user_id, :title, :description, :event_date, :venue, :status, :created_at, :updated_at)');

$histStmt = $pdo->prepare('INSERT INTO approval_history (request_id, actor_id, action, remarks, from_status, to_status, created_at) VALUES (:request_id, :actor_id, :action, :remarks, :from_status, :to_status, :created_at)');

// 1. A draft request owned by Alice
$reqStmt->execute([
    'id' => 1,
    'user_id' => 1,
    'title' => 'Campus Hackathon 2026',
    'description' => 'A 24-hour collaborative coding sprint for software and computer engineering students.',
    'event_date' => '2026-12-15',
    'venue' => 'Engineering Innovation Hall B',
    'status' => 'draft',
    'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
    'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
]);

// 2. A submitted request owned by Alice
$reqStmt->execute([
    'id' => 2,
    'user_id' => 1,
    'title' => 'Annual Robotics Exhibition',
    'description' => 'Demonstration of autonomous robots built by the student robotics club.',
    'event_date' => '2026-11-28',
    'venue' => 'University Activity Center',
    'status' => 'submitted',
    'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
    'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
]);

$histStmt->execute([
    'request_id' => 2,
    'actor_id' => 1,
    'action' => 'submit',
    'remarks' => 'Submitted for faculty advisor review.',
    'from_status' => 'draft',
    'to_status' => 'submitted',
    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
]);

// 3. An approved request owned by Charlie
$reqStmt->execute([
    'id' => 3,
    'user_id' => 3,
    'title' => 'IPT10 Open Source Showcase',
    'description' => 'Interactive project presentations showcasing DIY PHP microframeworks.',
    'event_date' => '2026-12-05',
    'venue' => 'Auditorium 1',
    'status' => 'approved',
    'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
    'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
]);

$histStmt->execute([
    'request_id' => 3,
    'actor_id' => 3,
    'action' => 'submit',
    'remarks' => 'Ready for departmental review.',
    'from_status' => 'draft',
    'to_status' => 'submitted',
    'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
]);

$histStmt->execute([
    'request_id' => 3,
    'actor_id' => 2,
    'action' => 'approve',
    'remarks' => 'Outstanding initiative and educational alignment. Approved.',
    'from_status' => 'submitted',
    'to_status' => 'approved',
    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
]);

// 4. A rejected request owned by Alice
$reqStmt->execute([
    'id' => 4,
    'user_id' => 1,
    'title' => 'Midnight Rooftop Drone Race',
    'description' => 'Unsanctioned night time drone racing competition from the library rooftop.',
    'event_date' => '2026-11-20',
    'venue' => 'Library Rooftop Deck',
    'status' => 'rejected',
    'created_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
    'updated_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
]);

$histStmt->execute([
    'request_id' => 4,
    'actor_id' => 1,
    'action' => 'submit',
    'remarks' => 'Requesting special clearance.',
    'from_status' => 'draft',
    'to_status' => 'submitted',
    'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
]);

$histStmt->execute([
    'request_id' => 4,
    'actor_id' => 2,
    'action' => 'reject',
    'remarks' => 'Safety violation: Unsanctioned drone flights near library glass ceiling are prohibited.',
    'from_status' => 'submitted',
    'to_status' => 'rejected',
    'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
]);

echo "[✓] Seeded sample requests and approval histories.\n";
echo "Database ready at: {$dbPath}\n";
