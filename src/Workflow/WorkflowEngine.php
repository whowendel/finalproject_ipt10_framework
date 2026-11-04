<?php

declare(strict_types=1);

namespace FlowForge\Workflow;

/**
 * WorkflowEngine.
 * Enforces allowed state transitions and approval roles centrally.
 * Version 1.0 strict specification:
 * - draft     -> submit  -> submitted (submitter / owner)
 * - submitted -> approve -> approved  (reviewer)
 * - submitted -> reject  -> rejected  (reviewer)
 * Final states: approved, rejected (cannot transition).
 */
class WorkflowEngine
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const ACTION_SUBMIT = 'submit';
    public const ACTION_APPROVE = 'approve';
    public const ACTION_REJECT = 'reject';

    public const ROLE_SUBMITTER = 'submitter';
    public const ROLE_REVIEWER = 'reviewer';

    /**
     * Complete transition configuration.
     */
    private const TRANSITIONS = [
        self::STATUS_DRAFT => [
            self::ACTION_SUBMIT => [
                'target' => self::STATUS_SUBMITTED,
                'role' => self::ROLE_SUBMITTER,
                'require_owner' => true,
            ],
        ],
        self::STATUS_SUBMITTED => [
            self::ACTION_APPROVE => [
                'target' => self::STATUS_APPROVED,
                'role' => self::ROLE_REVIEWER,
                'require_owner' => false,
            ],
            self::ACTION_REJECT => [
                'target' => self::STATUS_REJECTED,
                'role' => self::ROLE_REVIEWER,
                'require_owner' => false,
            ],
        ],
        // Final states: no outgoing transitions
        self::STATUS_APPROVED => [],
        self::STATUS_REJECTED => [],
    ];

    /**
     * Check if a transition is valid from a given status with a given action.
     */
    public function canTransition(string $currentStatus, string $action): bool
    {
        return isset(self::TRANSITIONS[$currentStatus][$action]);
    }

    /**
     * Get target status for an action from a current status.
     */
    public function getNextStatus(string $currentStatus, string $action): ?string
    {
        return self::TRANSITIONS[$currentStatus][$action]['target'] ?? null;
    }

    /**
     * Check if a status is a terminal/final state.
     */
    public function isFinalState(string $status): bool
    {
        return empty(self::TRANSITIONS[$status] ?? []);
    }

    /**
     * Get transition configuration.
     */
    public function getTransitionConfig(string $currentStatus, string $action): ?array
    {
        return self::TRANSITIONS[$currentStatus][$action] ?? null;
    }

    /**
     * Validate a transition attempt including status, action, user role, and ownership.
     *
     * @param string $currentStatus Current status of the request
     * @param string $action Action being attempted ('submit', 'approve', 'reject')
     * @param string $userRole Role of the acting user ('submitter' or 'reviewer')
     * @param bool $isOwner Whether the acting user is the owner (creator) of the request
     * @return array{allowed: bool, next_status: string|null, error: string|null}
     */
    public function validateTransition(
        string $currentStatus,
        string $action,
        string $userRole,
        bool $isOwner = false
    ): array {
        if ($this->isFinalState($currentStatus)) {
            return [
                'allowed' => false,
                'next_status' => null,
                'error' => "Cannot transition from final status '{$currentStatus}'.",
            ];
        }

        $config = $this->getTransitionConfig($currentStatus, $action);
        if ($config === null) {
            return [
                'allowed' => false,
                'next_status' => null,
                'error' => "Invalid action '{$action}' for request with status '{$currentStatus}'.",
            ];
        }

        // Check role permission
        if ($userRole !== $config['role']) {
            return [
                'allowed' => false,
                'next_status' => null,
                'error' => "Unauthorized: Role '{$userRole}' is not permitted to perform '{$action}'. Required role: '{$config['role']}'.",
            ];
        }

        // Check ownership if required
        if ($config['require_owner'] && !$isOwner) {
            return [
                'allowed' => false,
                'next_status' => null,
                'error' => "Unauthorized: Only the creator/owner of the request can submit it.",
            ];
        }

        return [
            'allowed' => true,
            'next_status' => $config['target'],
            'error' => null,
        ];
    }
}
