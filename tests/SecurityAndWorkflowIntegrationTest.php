<?php

declare(strict_types=1);

namespace FlowForge\Tests;

use App\Models\User;
use App\Repositories\ApprovalHistoryRepository;
use App\Repositories\RequestRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\WorkflowService;
use FlowForge\Core\Database;
use FlowForge\Core\Response;
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
use FlowForge\Workflow\WorkflowEngine;
use PDO;

class SecurityAndWorkflowIntegrationTest
{
    public static function run(): void
    {
        echo "Running SecurityAndWorkflowIntegrationTest...\n";

        // Setup isolated in-memory SQLite database
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
        $pdo->exec($schema);

        $userRepo = new UserRepository($pdo);
        $reqRepo = new RequestRepository($pdo);
        $histRepo = new ApprovalHistoryRepository($pdo);
        $engine = new WorkflowEngine();
        $workflowService = new WorkflowService($reqRepo, $histRepo, $engine, $pdo);

        // Seed users into test DB
        $hash = password_hash('secret123', PASSWORD_DEFAULT);
        $aliceId = $userRepo->create([
            'name' => 'Alice Student',
            'email' => 'alice@test.com',
            'password_hash' => $hash,
            'role' => 'submitter',
        ]);
        $bobId = $userRepo->create([
            'name' => 'Dr. Bob Reviewer',
            'email' => 'bob@test.com',
            'password_hash' => $hash,
            'role' => 'reviewer',
        ]);
        $charlieId = $userRepo->create([
            'name' => 'Charlie Submitter',
            'email' => 'charlie@test.com',
            'password_hash' => $hash,
            'role' => 'submitter',
        ]);

        $alice = $userRepo->findById($aliceId);
        $bob = $userRepo->findById($bobId);
        $charlie = $userRepo->findById($charlieId);

        // 1. Security: Password Hash Verification
        TestCase::assertTrue(password_verify('secret123', $alice->password_hash), 'Password hash verifies with password_verify()');
        TestCase::assertFalse(password_verify('wrongpass', $alice->password_hash), 'Wrong password fails verification');

        // 2. Security: HTML Escaping
        $xss = '<script>alert("pwned")</script>';
        $escaped = View::e($xss);
        TestCase::assertEquals('&lt;script&gt;alert(&quot;pwned&quot;)&lt;/script&gt;', $escaped, 'View::e safely escapes XSS payload');

        // 3. Security: CSRF Generation and Verification
        $token = Csrf::getToken();
        TestCase::assertTrue(strlen($token) === 64, 'Generates valid 64-char hex CSRF token');
        TestCase::assertTrue(Csrf::verify($token), 'Csrf::verify succeeds with matching token');
        TestCase::assertFalse(Csrf::verify('invalid_token_123'), 'Csrf::verify fails with mismatched token');

        // 4. Persistence & Workflow: Create draft request
        $reqId = $reqRepo->create([
            'user_id' => $alice->id,
            'title' => 'Drone Tech Expo',
            'description' => 'Showcase of quadcopters.',
            'event_date' => '2026-11-20',
            'venue' => 'Sports Complex',
            'status' => 'draft',
        ]);
        TestCase::assertTrue($reqId > 0, 'Draft request created in database');
        $createdReq = $reqRepo->findById($reqId);
        TestCase::assertEquals('draft', $createdReq->status, 'New request status is draft');

        // 5. Authorization: Non-owner submitter cannot submit Alice\'s draft
        $failSubmit = $workflowService->transition($reqId, 'submit', $charlie);
        TestCase::assertFalse($failSubmit['success'], 'Non-owner cannot submit request');
        TestCase::assertEquals(403, $failSubmit['status_code'], 'Returns 403 Forbidden for non-owner');

        // 6. Authorization: Reviewer cannot approve a draft directly
        $failApproveDraft = $workflowService->transition($reqId, 'approve', $bob);
        TestCase::assertFalse($failApproveDraft['success'], 'Reviewer cannot approve a draft');

        // 7. Workflow: Owner submits draft
        $submitRes = $workflowService->transition($reqId, 'submit', $alice, 'Submitting for advisor sign-off');
        TestCase::assertTrue($submitRes['success'], 'Owner successfully submits draft');
        TestCase::assertEquals('submitted', $submitRes['request']->status, 'Request status updated to submitted');

        // Verify history entry
        $history = $histRepo->findByRequestId($reqId);
        TestCase::assertEquals(1, count($history), 'Exactly one history record created for submit');
        TestCase::assertEquals('submit', $history[0]['action'], 'History records action submit');
        TestCase::assertEquals('draft', $history[0]['from_status'], 'History records from_status draft');
        TestCase::assertEquals('submitted', $history[0]['to_status'], 'History records to_status submitted');

        // 8. Authorization: Submitter attempts approval -> REJECTED
        $unauthApprove = $workflowService->transition($reqId, 'approve', $alice);
        TestCase::assertFalse($unauthApprove['success'], 'Submitter cannot approve requests');
        TestCase::assertEquals(403, $unauthApprove['status_code'], 'Returns 403 Forbidden');

        // 9. Workflow: Rejection without remarks is rejected by validation
        $rejectNoRemarks = $workflowService->transition($reqId, 'reject', $bob, '');
        TestCase::assertFalse($rejectNoRemarks['success'], 'Rejection without remarks fails');
        TestCase::assertEquals(422, $rejectNoRemarks['status_code'], 'Returns 422 for missing remarks');

        // 10. Workflow: Successful Approval by Reviewer
        $approveRes = $workflowService->transition($reqId, 'approve', $bob, 'Looks great. Approved.');
        TestCase::assertTrue($approveRes['success'], 'Reviewer successfully approves request');
        TestCase::assertEquals('approved', $approveRes['request']->status, 'Request status becomes approved');

        $historyAfterApprove = $histRepo->findByRequestId($reqId);
        TestCase::assertEquals(2, count($historyAfterApprove), 'History contains 2 audit trail entries');
        TestCase::assertEquals('approve', $historyAfterApprove[1]['action'], 'Second history action is approve');

        // 11. Workflow Terminal State: An approved request cannot be approved again or rejected later
        $reApprove = $workflowService->transition($reqId, 'approve', $bob);
        TestCase::assertFalse($reApprove['success'], 'Cannot approve an already approved request');

        $rejectApproved = $workflowService->transition($reqId, 'reject', $bob, 'Changed my mind');
        TestCase::assertFalse($rejectApproved['success'], 'Cannot reject an already approved request');

        // History count remains 2
        TestCase::assertEquals(2, count($histRepo->findByRequestId($reqId)), 'No partial or phantom history written on rejected transitions');

        // 12. Transaction Rollback Integrity: Simulate failed transition
        // Verify that inTransaction rollback leaves DB consistent
        $mockErrorPdo = new PDO('sqlite::memory:');
        $mockErrorPdo->exec($schema);
        $badWorkflow = new WorkflowService(
            new RequestRepository($mockErrorPdo),
            new ApprovalHistoryRepository($mockErrorPdo),
            new WorkflowEngine(),
            $mockErrorPdo
        );
        $mockUserRepo = new UserRepository($mockErrorPdo);
        $uId = $mockUserRepo->create(['name' => 'User', 'email' => 'u@u.com', 'password_hash' => $hash, 'role' => 'submitter']);
        $u = $mockUserRepo->findById($uId);
        $rId = (new RequestRepository($mockErrorPdo))->create([
            'user_id' => $uId,
            'title' => 'Test',
            'description' => 'Desc',
            'event_date' => '2026-11-20',
            'venue' => 'Venue',
            'status' => 'draft',
        ]);
        // Drop approval_history table to force write failure during transition
        $mockErrorPdo->exec('DROP TABLE approval_history;');
        $txFail = $badWorkflow->transition($rId, 'submit', $u);
        TestCase::assertFalse($txFail['success'], 'Handles write failure safely');
        // Check that request status was rolled back and is still draft
        $statusCheck = $mockErrorPdo->query("SELECT status FROM activity_requests WHERE id = {$rId}")->fetchColumn();
        TestCase::assertEquals('draft', $statusCheck, 'Failed transaction rolls back status write');

        // 13. Responses
        $html404 = Response::html('<h1>Not Found</h1>', 404);
        TestCase::assertEquals(404, $html404->getStatusCode(), 'HTML Response status 404');
        TestCase::assertContains('text/html', $html404->getHeaders()['Content-Type'], 'HTML content type header');

        $json404 = Response::json(['message' => 'Request not found'], 404);
        TestCase::assertEquals(404, $json404->getStatusCode(), 'JSON Response status 404');
        TestCase::assertContains('application/json', $json404->getHeaders()['Content-Type'], 'JSON content type header');

        $redirect = Response::redirect('/requests/5');
        TestCase::assertEquals(302, $redirect->getStatusCode(), 'Redirect status code 302');
        TestCase::assertEquals('/requests/5', $redirect->getHeaders()['Location'], 'Redirect location header set');
    }
}
