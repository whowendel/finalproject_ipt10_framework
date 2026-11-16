<?php
use FlowForge\Core\View;

$counts = ['draft' => 0, 'submitted' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($requests as $req) {
    if (isset($counts[$req->status])) {
        $counts[$req->status]++;
    }
}
$total = count($requests);
$statusLabels = [
    'draft' => 'Drafts',
    'submitted' => 'In review',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
];
?>

<section class="page-hero has-tiles">
    <div class="container">
        <div class="hero-inner">
            <span class="eyebrow"><?= $user->isReviewer() ? 'Faculty workspace' : 'Student workspace' ?></span>
            <h1>Activity Requests</h1>
            <p class="hero-lede">
                <?= $user->isReviewer()
                    ? 'All campus activity submissions across all departments.'
                    : 'Your submitted and draft activity proposals.' ?>
            </p>
            <div class="hero-actions">
                <?php if ($user->isSubmitter()): ?>
                    <a href="<?= View::url() ?>/requests/create" class="btn btn-light">Create a request <span class="arrow" aria-hidden="true">&rarr;</span></a>
                <?php elseif ($user->isReviewer()): ?>
                    <a href="<?= View::url() ?>/reviewer/queue" class="btn btn-light">
                        Open review queue<?= $counts['submitted'] > 0 ? ' (' . (int)$counts['submitted'] . ')' : '' ?>
                        <span class="arrow" aria-hidden="true">&rarr;</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="container page-body" style="padding-top: 0;">
    <div class="stat-row">
        <?php foreach ($statusLabels as $status => $label): ?>
            <div class="stat-tile is-<?= $status ?>">
                <div class="stat-label"><?= View::e($label) ?></div>
                <div class="stat-value"><?= (int)$counts[$status] ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-head">
        <h2>
            <?= $user->isReviewer() ? 'All submissions' : 'Your proposals' ?>
            <span class="count" id="request-count"><?= (int)$total ?></span>
        </h2>
        <?php if ($total > 0): ?>
            <div class="filter-tabs" role="group" aria-label="Filter by status">
                <button type="button" class="filter-tab" data-filter="all" aria-pressed="true">All</button>
                <?php foreach ($statusLabels as $status => $label): ?>
                    <?php if ($counts[$status] > 0): ?>
                        <button type="button" class="filter-tab" data-filter="<?= $status ?>" aria-pressed="false"><?= View::e($label) ?></button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <div class="empty-mark" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="14 3 14 9 20 9"></polyline>
                </svg>
            </div>
            <h3>No activity requests yet</h3>
            <?php if ($user->isSubmitter()): ?>
                <p>Start a proposal as a draft. You can review the details before sending it to faculty.</p>
                <a href="<?= View::url() ?>/requests/create" class="btn btn-primary">Create your first request <span class="arrow" aria-hidden="true">&rarr;</span></a>
            <?php else: ?>
                <p>Submissions from students will appear here as soon as they are created.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <ul class="request-list" id="request-list">
            <?php foreach ($requests as $req): ?>
                <?php $ts = strtotime((string)$req->event_date); ?>
                <li class="request-row" data-status="<?= View::e($req->status) ?>">
                    <div class="date-block" title="Event date: <?= View::e($req->event_date) ?>">
                        <?php if ($ts !== false): ?>
                            <span class="month"><?= strtoupper(date('M', $ts)) ?></span>
                            <span class="day"><?= date('d', $ts) ?></span>
                            <span class="year"><?= date('Y', $ts) ?></span>
                        <?php else: ?>
                            <span class="year"><?= View::e($req->event_date) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="request-main">
                        <span class="request-id">REQUEST #<?= (int)$req->id ?></span>
                        <?php if (!empty($holidayNames[$req->event_date])): ?><span class="holiday-flag">Public holiday: <?= View::e($holidayNames[$req->event_date]) ?></span><?php endif; ?>
                        <h3 class="request-title">
                            <a href="<?= View::url() ?>/requests/<?= (int)$req->id ?>"><?= View::e($req->title) ?></a>
                        </h3>
                        <div class="request-meta">
                            <span><strong>Venue</strong> <?= View::e($req->venue) ?></span>
                            <span><strong>By</strong> <?= View::e($req->submitter_name ?? 'User #' . $req->user_id) ?></span>
                        </div>
                    </div>

                    <div class="request-side">
                        <span class="badge badge-<?= View::e($req->status) ?>"><?= View::e($req->status) ?></span>
                        <span class="row-arrow" aria-hidden="true">&rarr;</span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <script>
            // Client-side status filter for the request list.
            (function () {
                var tabs = document.querySelectorAll('.filter-tab');
                var rows = document.querySelectorAll('#request-list .request-row');
                var count = document.getElementById('request-count');

                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        var filter = tab.dataset.filter;
                        var shown = 0;

                        tabs.forEach(function (other) {
                            other.setAttribute('aria-pressed', other === tab ? 'true' : 'false');
                        });
                        rows.forEach(function (row) {
                            var visible = filter === 'all' || row.dataset.status === filter;
                            row.hidden = !visible;
                            if (visible) { shown++; }
                        });
                        count.textContent = shown;
                    });
                });
            })();
        </script>
    <?php endif; ?>
</div>
