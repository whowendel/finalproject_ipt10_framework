<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Core/Autoloader.php';
\FlowForge\Core\Autoloader::register();

use FlowForge\Core\Config;
use FlowForge\Core\Database;

echo "--- FlowForge Database Setup & Seeding ---\n";

Config::load(__DIR__ . '/../config/app.php');
date_default_timezone_set((string)Config::get('timezone', 'UTC'));
$driver = Database::driver();

if ($driver === 'mysql') {
    // Create the database first if it does not exist yet.
    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', Config::get('database.host'), (int)Config::get('database.port')),
        (string)Config::get('database.username'),
        (string)Config::get('database.password'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbName = str_replace('`', '', (string)Config::get('database.database'));
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database: MySQL `{$dbName}` on " . Config::get('database.host') . "\n";
    $schemaFile = __DIR__ . '/schema.mysql.sql';
} else {
    echo "Database: SQLite " . Config::get('database.path') . "\n";
    $schemaFile = __DIR__ . '/schema.sql';
}

$pdo = Database::connect();
$dbPath = $driver === 'mysql' ? 'MySQL database ' . Config::get('database.database') : (string)Config::get('database.path');

// Execute schema (one statement at a time so it works on both drivers)
$schemaSql = (string)file_get_contents($schemaFile);
$schemaSql = (string)preg_replace('/^\s*--.*$/m', '', $schemaSql);
foreach (array_filter(array_map('trim', explode(';', $schemaSql))) as $statement) {
    $pdo->exec($statement);
}
echo "[OK] Schema created successfully.\n";

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
echo "[OK] Seeded demo users.\n";
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
    'event_date' => '2026-11-30', // Bonifacio Day (shows the holiday API warning)
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

echo "[OK] Seeded sample requests and approval histories.\n";
echo "Database ready at: {$dbPath}\n";
