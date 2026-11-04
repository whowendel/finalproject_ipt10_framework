<?php

declare(strict_types=1);

namespace FlowForge\Tests;

use FlowForge\Workflow\WorkflowEngine;

class WorkflowEngineTest
{
    public static function run(): void
    {
        echo "Running WorkflowEngineTest...\n";
        $engine = new WorkflowEngine();

        // 1. All three allowed transitions
        // 1a. draft -> submit -> submitted (owner submitter)
        $t1 = $engine->validateTransition('draft', 'submit', 'submitter', true);
        TestCase::assertTrue($t1['allowed'], 'Owner submitter can submit a draft');
        TestCase::assertEquals('submitted', $t1['next_status'], 'draft transitions to submitted');

        // 1b. submitted -> approve -> approved (reviewer)
        $t2 = $engine->validateTransition('submitted', 'approve', 'reviewer', false);
        TestCase::assertTrue($t2['allowed'], 'Reviewer can approve a submitted request');
        TestCase::assertEquals('approved', $t2['next_status'], 'submitted transitions to approved');

        // 1c. submitted -> reject -> rejected (reviewer)
        $t3 = $engine->validateTransition('submitted', 'reject', 'reviewer', false);
        TestCase::assertTrue($t3['allowed'], 'Reviewer can reject a submitted request');
        TestCase::assertEquals('rejected', $t3['next_status'], 'submitted transitions to rejected');

        // 2. Reject invalid transitions
        // 2a. Submitter attempts approval
        $inv1 = $engine->validateTransition('submitted', 'approve', 'submitter', false);
        TestCase::assertFalse($inv1['allowed'], 'Submitter cannot approve requests');
        TestCase::assertContains('Unauthorized', $inv1['error'] ?? '', 'Error identifies role violation');

        // 2b. Non-owner submitter attempts to submit someone else\'s draft
        $inv2 = $engine->validateTransition('draft', 'submit', 'submitter', false);
        TestCase::assertFalse($inv2['allowed'], 'Non-owner cannot submit a draft');
        TestCase::assertContains('owner', $inv2['error'] ?? '', 'Error identifies ownership violation');

        // 2c. Reviewer attempts action on draft
        $inv3 = $engine->validateTransition('draft', 'approve', 'reviewer', false);
        TestCase::assertFalse($inv3['allowed'], 'Reviewer cannot approve an unsubmitted draft');

        $inv4 = $engine->validateTransition('draft', 'reject', 'reviewer', false);
        TestCase::assertFalse($inv4['allowed'], 'Reviewer cannot reject an unsubmitted draft');

        // 3. Final states cannot transition
        TestCase::assertTrue($engine->isFinalState('approved'), 'approved is recognized as a final state');
        TestCase::assertTrue($engine->isFinalState('rejected'), 'rejected is recognized as a final state');

        $inv5 = $engine->validateTransition('approved', 'approve', 'reviewer', false);
        TestCase::assertFalse($inv5['allowed'], 'Cannot re-approve an approved request');
        TestCase::assertContains('final status', $inv5['error'] ?? '', 'Error notes final state restriction');

        $inv6 = $engine->validateTransition('approved', 'reject', 'reviewer', false);
        TestCase::assertFalse($inv6['allowed'], 'Cannot reject an already approved request');

        $inv7 = $engine->validateTransition('rejected', 'approve', 'reviewer', false);
        TestCase::assertFalse($inv7['allowed'], 'Cannot approve an already rejected request');

        $inv8 = $engine->validateTransition('rejected', 'submit', 'submitter', true);
        TestCase::assertFalse($inv8['allowed'], 'Cannot resubmit a rejected request in v1.0');
    }
}
