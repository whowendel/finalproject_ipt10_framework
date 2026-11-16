<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;

$status = (string)$request->status;
$isDecided = in_array($status, ['approved', 'rejected'], true);

// Workflow stepper state for the sidebar.
$stepOne = $status === 'draft' ? 'is-current' : 'is-done';
$stepTwo = $status === 'submitted' ? 'is-current' : ($isDecided ? 'is-done' : '');
$stepThree = $isDecided ? 'is-' . $status : '';
?>

<section class="page-hero">
    <div class="container">
        <div class="hero-inner">
            <a href="<?= View::url() ?>/requests" class="crumb"><span aria-hidden="true">&larr;</span> Back to requests</a>
            <div><span class="eyebrow">Request #<?= (int)$request->id ?></span></div>
            <h1><?= View::e($request->title) ?></h1>
            <div class="hero-meta">
                <span class="badge badge-lg badge-<?= View::e($status) ?>"><?= View::e($status) ?></span>
                <span>
                    Submitted by <strong><?= View::e($request->submitter_name ?? 'User #' . $request->user_id) ?></strong>
                </span>
            </div>
        </div>
    </div>
</section>

<div class="container page-body">
    <div class="detail-grid">
        <div>
            <!-- Workflow Action Panel -->
            <?php if ($status === 'draft' && $user->isSubmitter() && $request->user_id === $user->id): ?>
                <div class="action-panel">
                    <span class="eyebrow">Next step</span>
                    <h3>Ready to submit for approval?</h3>
                    <p>
                        Submitting this draft moves the request into the faculty review queue. Once submitted, details cannot be modified without reviewer determination.
                    </p>
                    <form action="<?= View::url() ?>/requests/<?= (int)$request->id ?>/submit" method="POST">
                        <?= Csrf::field() ?>
                        <div class="form-group">
                            <label for="submit_remarks" class="form-label">Submission notes (optional)</label>
                            <input type="text" id="submit_remarks" name="remarks" class="form-control" placeholder="Add any notes for the faculty reviewer...">
                        </div>
                        <button type="submit" class="btn btn-light">Submit for faculty review <span class="arrow" aria-hidden="true">&rarr;</span></button>
                    </form>
                </div>

                <div class="draft-tools">
                    <a href="<?= View::url() ?>/requests/<?= (int)$request->id ?>/edit" class="btn btn-secondary btn-sm">Edit draft</a>
                    <form action="<?= View::url() ?>/requests/<?= (int)$request->id ?>/delete" method="POST"
                          onsubmit="return confirm('Delete this draft permanently?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-danger btn-sm">Delete draft</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($status === 'submitted' && $user->isReviewer()): ?>
                <div class="action-panel">
                    <span class="eyebrow">Your decision</span>
                    <h3>Faculty reviewer actions</h3>
                    <p>
                        As an authorized reviewer, you may approve or reject this student activity proposal. Rejections require explanatory remarks.
                    </p>

                    <div class="decision-grid">
                        <!-- Approve Form -->
                        <div class="decision-card approve">
                            <h4>Approve request</h4>
                            <form action="<?= View::url() ?>/requests/<?= (int)$request->id ?>/approve" method="POST">
                                <?= Csrf::field() ?>
                                <div class="form-group">
                                    <label for="approve_remarks" class="form-label">Approval remarks (optional)</label>
                                    <input type="text" id="approve_remarks" name="remarks" class="form-control" placeholder="e.g. Approved with standard safety guidelines.">
                                </div>
                                <button type="submit" class="btn btn-success btn-block">Confirm approval</button>
                            </form>
                        </div>

                        <!-- Reject Form -->
                        <div class="decision-card reject">
                            <h4>Reject request</h4>
                            <form action="<?= View::url() ?>/requests/<?= (int)$request->id ?>/reject" method="POST">
                                <?= Csrf::field() ?>
                                <div class="form-group">
                                    <label for="reject_remarks" class="form-label">Rejection remarks <span class="req">*</span></label>
                                    <input type="text" id="reject_remarks" name="remarks" class="form-control" required placeholder="Specify reason for rejection...">
                                </div>
                                <button type="submit" class="btn btn-danger btn-block">Reject proposal</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel">
                <h2 class="panel-title">About this activity</h2>
                <p class="prose"><?= View::e($request->description) ?></p>
            </div>

            <!-- Chronological Approval & Transition History -->
            <div class="panel">
                <h2 class="panel-title">Approval &amp; workflow history</h2>
                <?php if (empty($history)): ?>
                    <p style="color: var(--muted);">No recorded workflow actions yet.</p>
                <?php else: ?>
                    <ol class="timeline">
                        <?php foreach ($history as $entry): ?>
                            <li class="to-<?= View::e($entry['to_status']) ?>">
                                <div class="timeline-time"><?= View::e($entry['created_at']) ?></div>
                                <div class="timeline-head">
                                    <span class="timeline-action"><?= View::e($entry['action']) ?></span>
                                    <span>by <strong><?= View::e($entry['actor_name'] ?? 'System') ?></strong></span>
                                    <span class="role-tag <?= View::e($entry['actor_role'] ?? '') ?>"><?= View::e($entry['actor_role'] ?? 'user') ?></span>
                                </div>
                                <div class="timeline-transition">
                                    <?php if ($entry['from_status']): ?>
                                        <span class="badge badge-<?= View::e($entry['from_status']) ?>"><?= View::e($entry['from_status']) ?></span>
                                        <span aria-label="to">&rarr;</span>
                                    <?php endif; ?>
                                    <span class="badge badge-<?= View::e($entry['to_status']) ?>"><?= View::e($entry['to_status']) ?></span>
                                </div>
                                <?php if (!empty($entry['remarks'])): ?>
                                    <div class="timeline-remark"><?= View::e($entry['remarks']) ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <aside>
            <div class="panel panel-accent">
                <h2 class="panel-title">Event details</h2>
                <ul class="fact-list">
                    <li>
                        <span class="fact-label">Event date</span>
                        <span class="fact-value"><?= View::e($request->event_date) ?></span>
                    </li>
                    <li>
                        <span class="fact-label">Venue</span>
                        <span class="fact-value"><?= View::e($request->venue) ?></span>
                    </li>
                    <li>
                        <span class="fact-label">Submitter</span>
                        <span class="fact-value"><?= View::e($request->submitter_name ?? 'User #' . $request->user_id) ?></span>
                        <span class="fact-sub"><?= View::e($request->submitter_email ?? '') ?></span>
                    </li>
                    <li>
                        <span class="fact-label">Created</span>
                        <span class="fact-sub mono"><?= View::e($request->created_at) ?></span>
                    </li>
                    <li>
                        <span class="fact-label">Last updated</span>
                        <span class="fact-sub mono"><?= View::e($request->updated_at) ?></span>
                    </li>
                </ul>
            </div>

            <div class="panel holiday-panel">
                <h2 class="panel-title">Calendar check</h2>
                <?php if (empty($holidayCheck) || !$holidayCheck['ok']): ?>
                    <p class="holiday-status is-muted">
                        Holiday check unavailable<?= !empty($holidayCheck['error']) ? ': ' . View::e($holidayCheck['error']) : '' ?>
                    </p>
                <?php elseif ($holidayCheck['is_holiday']): ?>
                    <p class="holiday-status is-warning">
                        <?= View::e($request->event_date) ?> is a public holiday:
                        <strong><?= View::e($holidayCheck['holiday']['name']) ?></strong>
                        <?php if (!empty($holidayCheck['holiday']['local_name'])): ?>
                            (<?= View::e($holidayCheck['holiday']['local_name']) ?>)
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="holiday-status is-ok">No public holiday on <?= View::e($request->event_date) ?>.</p>
                <?php endif; ?>
                <small class="fact-sub">Nager.Date public holiday API &bull; country <?= View::e($holidayCountry ?? '') ?></small>
            </div>

            <div class="panel">
                <h2 class="panel-title">Progress</h2>
                <ol class="stepper">
                    <li class="<?= $stepOne ?>">
                        <span>
                            <span class="step-name">Draft</span>
                            <span class="step-note">Prepared by the submitter</span>
                        </span>
                    </li>
                    <li class="<?= $stepTwo ?>">
                        <span>
                            <span class="step-name">Submitted</span>
                            <span class="step-note">Awaiting faculty review</span>
                        </span>
                    </li>
                    <li class="<?= View::e($stepThree) ?>">
                        <span>
                            <span class="step-name"><?= $isDecided ? View::e(ucfirst($status)) : 'Decision' ?></span>
                            <span class="step-note"><?= $isDecided ? 'Final status' : 'Approved or rejected' ?></span>
                        </span>
                    </li>
                </ol>
            </div>
        </aside>
    </div>
</div>
