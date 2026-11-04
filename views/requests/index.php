<?php
use FlowForge\Core\View;
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Activity Requests</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                <?= $user->isReviewer() ? 'All campus activity submissions across all departments.' : 'Your submitted and draft activity proposals.' ?>
            </p>
        </div>
        <div>
            <?php if ($user->isSubmitter()): ?>
                <a href="/requests/create" class="btn btn-primary">+ Create Request</a>
            <?php elseif ($user->isReviewer()): ?>
                <a href="/reviewer/queue" class="btn btn-primary">Go to Reviewer Queue</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($requests)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
            No activity requests found.
            <?php if ($user->isSubmitter()): ?>
                <a href="/requests/create">Click here to create one.</a>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Submitter</th>
                        <th>Event Date</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>#<?= (int)$req->id ?></td>
                            <td>
                                <strong>
                                    <a href="/requests/<?= (int)$req->id ?>" style="color: var(--primary); text-decoration: none;">
                                        <?= View::e($req->title) ?>
                                    </a>
                                </strong>
                            </td>
                            <td>
                                <?= View::e($req->submitter_name ?? 'User #' . $req->user_id) ?>
                            </td>
                            <td><?= View::e($req->event_date) ?></td>
                            <td><?= View::e($req->venue) ?></td>
                            <td>
                                <span class="badge badge-<?= View::e($req->status) ?>">
                                    <?= View::e($req->status) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/requests/<?= (int)$req->id ?>" class="btn btn-secondary btn-sm">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
