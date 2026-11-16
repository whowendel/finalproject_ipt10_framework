<?php
use FlowForge\Core\View;

$pending = count($requests);
?>

<section class="page-hero">
    <div class="container">
        <div class="hero-inner">
            <a href="<?= View::url() ?>/requests" class="crumb"><span aria-hidden="true">&larr;</span> All requests</a>
            <div><span class="eyebrow">Faculty workspace</span></div>
            <h1>Pending Review Queue</h1>
            <p class="hero-lede">
                Student activity requests awaiting formal faculty determination.
            </p>
        </div>
    </div>
</section>

<div class="container page-body">
    <div class="section-head" style="margin-top: 0;">
        <h2>Awaiting your decision <span class="count"><?= (int)$pending ?></span></h2>
    </div>

    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <div class="empty-mark" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h3>Review queue is empty</h3>
            <p>There are currently no pending submissions requiring your review.</p>
            <a href="<?= View::url() ?>/requests" class="btn btn-secondary">View all requests</a>
        </div>
    <?php else: ?>
        <ul class="request-list">
            <?php foreach ($requests as $req): ?>
                <?php $ts = strtotime((string)$req->event_date); ?>
                <li class="request-row">
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
                        <span class="request-id">REQUEST #<?= (int)$req->id ?> &bull; SUBMITTED <?= View::e($req->updated_at) ?></span>
                        <?php if (!empty($holidayNames[$req->event_date])): ?><span class="holiday-flag">Public holiday: <?= View::e($holidayNames[$req->event_date]) ?></span><?php endif; ?>
                        <h3 class="request-title">
                            <a href="<?= View::url() ?>/requests/<?= (int)$req->id ?>"><?= View::e($req->title) ?></a>
                        </h3>
                        <div class="request-excerpt"><?= View::e($req->description) ?></div>
                        <div class="request-meta">
                            <span><strong>Venue</strong> <?= View::e($req->venue) ?></span>
                            <span>
                                <strong>By</strong> <?= View::e($req->submitter_name ?? 'User #' . $req->user_id) ?>
                                <?php if (!empty($req->submitter_email)): ?>
                                    (<?= View::e($req->submitter_email) ?>)
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="request-side">
                        <span class="row-cta">Review &amp; decide</span>
                        <span class="row-arrow" aria-hidden="true">&rarr;</span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
