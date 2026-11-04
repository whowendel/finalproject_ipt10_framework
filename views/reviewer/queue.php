<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Pending Review Queue</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                Student activity requests awaiting formal faculty determination.
            </p>
        </div>
        <a href="/requests" class="btn btn-secondary btn-sm">&larr; All Requests</a>
    </div>

    <?php if (empty($requests)): ?>
        <div style="text-align: center; padding: 3rem 1rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎉</div>
            <h3 style="color: #334155; font-size: 1.15rem;">Review Queue is Empty</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                There are currently no pending submissions requiring your review.
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title & Description</th>
                        <th>Submitter</th>
                        <th>Event Date & Venue</th>
                        <th>Submitted On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>#<?= (int)$req->id ?></td>
                            <td style="max-width: 320px;">
                                <strong>
                                    <a href="/requests/<?= (int)$req->id ?>" style="color: var(--primary); text-decoration: none;">
                                        <?= View::e($req->title) ?>
                                    </a>
                                </strong>
                                <div style="color: var(--text-muted); font-size: 0.825rem; margin-top: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= View::e($req->description) ?>
                                </div>
                            </td>
                            <td>
                                <div><?= View::e($req->submitter_name ?? 'User #' . $req->user_id) ?></div>
                                <div style="color: var(--text-muted); font-size: 0.8rem;"><?= View::e($req->submitter_email ?? '') ?></div>
                            </td>
                            <td>
                                <div><strong><?= View::e($req->event_date) ?></strong></div>
                                <div style="color: var(--text-muted); font-size: 0.8rem;"><?= View::e($req->venue) ?></div>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                <?= View::e($req->updated_at) ?>
                            </td>
                            <td>
                                <a href="/requests/<?= (int)$req->id ?>" class="btn btn-primary btn-sm">
                                    Review &amp; Decide
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
