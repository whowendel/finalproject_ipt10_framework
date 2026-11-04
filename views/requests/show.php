<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>

<div style="margin-bottom: 1.25rem;">
    <a href="/requests" class="btn btn-secondary btn-sm">&larr; Back to Requests</a>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 class="card-title" style="margin: 0;">Request #<?= (int)$request->id ?>: <?= View::e($request->title) ?></h1>
                <span class="badge badge-<?= View::e($request->status) ?>" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;">
                    <?= View::e($request->status) ?>
                </span>
            </div>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.4rem;">
                Submitted by <strong><?= View::e($request->submitter_name ?? 'User #' . $request->user_id) ?></strong>
                (<?= View::e($request->submitter_email ?? '') ?>) &bull; Created: <?= View::e($request->created_at) ?>
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius); border: 1px solid var(--border);">
        <div>
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Event Date</span>
            <div style="font-size: 1.05rem; font-weight: 600; margin-top: 0.2rem;"><?= View::e($request->event_date) ?></div>
        </div>
        <div>
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Venue</span>
            <div style="font-size: 1.05rem; font-weight: 600; margin-top: 0.2rem;"><?= View::e($request->venue) ?></div>
        </div>
        <div>
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Last Updated</span>
            <div style="font-size: 1.05rem; font-weight: 600; margin-top: 0.2rem;"><?= View::e($request->updated_at) ?></div>
        </div>
    </div>

    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.05rem; margin-bottom: 0.5rem; color: #334155;">Description</h3>
        <p style="white-space: pre-line; line-height: 1.6; color: #1e293b; background: #ffffff; padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius);">
            <?= View::e($request->description) ?>
        </p>
    </div>

    <!-- Workflow Action Panel -->
    <?php if ($request->status === 'draft' && $user->isSubmitter() && $request->user_id === $user->id): ?>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius); padding: 1.25rem; margin-bottom: 2rem;">
            <h3 style="color: #1e40af; font-size: 1.1rem; margin-bottom: 0.35rem;">Ready to Submit for Approval?</h3>
            <p style="color: #1e3a8a; font-size: 0.875rem; margin-bottom: 1rem;">
                Submitting this draft moves the request into the faculty review queue. Once submitted, details cannot be modified without reviewer determination.
            </p>
            <form action="/requests/<?= (int)$request->id ?>/submit" method="POST">
                <?= Csrf::field() ?>
                <div class="form-group">
                    <label for="submit_remarks" class="form-label" style="color: #1e3a8a;">Submission Notes (Optional)</label>
                    <input type="text" id="submit_remarks" name="remarks" class="form-control" placeholder="Add any notes for the faculty reviewer...">
                </div>
                <button type="submit" class="btn btn-primary">Submit for Faculty Review</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($request->status === 'submitted' && $user->isReviewer()): ?>
        <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: var(--radius); padding: 1.25rem; margin-bottom: 2rem;">
            <h3 style="color: #6b21a8; font-size: 1.1rem; margin-bottom: 0.35rem;">Faculty Reviewer Actions</h3>
            <p style="color: #7e22ce; font-size: 0.875rem; margin-bottom: 1.25rem;">
                As an authorized reviewer, you may approve or reject this student activity proposal. Note: Rejections require explanatory remarks.
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Approve Form -->
                <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius); border: 1px solid #d8b4fe;">
                    <h4 style="color: var(--success); margin-bottom: 0.5rem;">Approve Request</h4>
                    <form action="/requests/<?= (int)$request->id ?>/approve" method="POST">
                        <?= Csrf::field() ?>
                        <div class="form-group">
                            <label class="form-label">Approval Remarks (Optional)</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Approved with standard university safety guidelines.">
                        </div>
                        <button type="submit" class="btn btn-success" style="width: 100%;">Confirm Approval</button>
                    </form>
                </div>

                <!-- Reject Form -->
                <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius); border: 1px solid #fca5a5;">
                    <h4 style="color: var(--danger); margin-bottom: 0.5rem;">Reject Request</h4>
                    <form action="/requests/<?= (int)$request->id ?>/reject" method="POST">
                        <?= Csrf::field() ?>
                        <div class="form-group">
                            <label class="form-label">Rejection Remarks <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="remarks" class="form-control" required placeholder="Specify reason for rejection...">
                        </div>
                        <button type="submit" class="btn btn-danger" style="width: 100%;">Reject Proposal</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Chronological Approval & Transition History -->
    <div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.75rem; color: #1e293b;">Approval & Workflow History</h3>
        <?php if (empty($history)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No recorded workflow actions yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Transition</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry): ?>
                            <tr>
                                <td style="font-size: 0.825rem; color: var(--text-muted);"><?= View::e($entry['created_at']) ?></td>
                                <td>
                                    <strong><?= View::e($entry['actor_name'] ?? 'System') ?></strong>
                                    <span class="role-pill <?= View::e($entry['actor_role'] ?? '') ?>" style="font-size: 0.7rem;">
                                        <?= View::e($entry['actor_role'] ?? 'user') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= strtoupper(View::e($entry['action'])) ?></strong>
                                </td>
                                <td>
                                    <?php if ($entry['from_status']): ?>
                                        <span class="badge badge-<?= View::e($entry['from_status']) ?>"><?= View::e($entry['from_status']) ?></span>
                                        &rarr;
                                    <?php endif; ?>
                                    <span class="badge badge-<?= View::e($entry['to_status']) ?>"><?= View::e($entry['to_status']) ?></span>
                                </td>
                                <td>
                                    <?= !empty($entry['remarks']) ? View::e($entry['remarks']) : '<em style="color:#94a3b8">None</em>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
