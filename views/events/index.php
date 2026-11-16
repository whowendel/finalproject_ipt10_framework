<?php
use FlowForge\Core\View;

/** @var \App\Models\ActivityRequest[] $upcoming */
/** @var \App\Models\ActivityRequest[] $past */
$renderEvent = static function ($event) use ($holidayNames): void {
    $ts = strtotime((string)$event->event_date);
    $holiday = $holidayNames[$event->event_date] ?? null;
    ?>
    <li class="request-row event-row">
        <div class="date-block" title="Event date: <?= View::e($event->event_date) ?>">
            <?php if ($ts !== false): ?>
                <span class="month"><?= strtoupper(date('M', $ts)) ?></span>
                <span class="day"><?= date('d', $ts) ?></span>
                <span class="year"><?= date('Y', $ts) ?></span>
            <?php else: ?>
                <span class="year"><?= View::e($event->event_date) ?></span>
            <?php endif; ?>
        </div>
        <div class="request-main">
            <span class="request-id">
                <?= $ts !== false ? strtoupper(date('l', $ts)) : 'DATE TBA' ?>
                <?php if ($holiday): ?>&bull; <span class="holiday-flag">Holiday: <?= View::e($holiday) ?></span><?php endif; ?>
            </span>
            <h3 class="request-title"><?= View::e($event->title) ?></h3>
            <p class="event-desc"><?= View::e($event->description) ?></p>
            <div class="request-meta">
                <span><strong>Venue</strong> <?= View::e($event->venue) ?></span>
                <span><strong>Organizer</strong> <?= View::e($event->submitter_name ?? 'Student organization') ?></span>
            </div>
        </div>
    </li>
    <?php
};
?>

<section class="page-hero">
    <div class="container">
        <div class="hero-inner">
            <span class="eyebrow">Published activities</span>
            <h1>Campus Events</h1>
            <p class="hero-lede">
                Every activity listed here was proposed by a student organization and approved by a faculty reviewer.
            </p>
            <?php if (empty($user)): ?>
                <div class="hero-actions">
                    <a href="<?= View::url() ?>/login" class="btn btn-light">Sign in to propose an activity <span class="arrow" aria-hidden="true">&rarr;</span></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container page-body">
    <div class="section-head" style="margin-top: 0;">
        <h2>Upcoming <span class="count"><?= count($upcoming) ?></span></h2>
    </div>

    <?php if (empty($upcoming)): ?>
        <div class="empty-state">
            <h3>No upcoming events yet</h3>
            <p>Approved activities will appear here as soon as faculty reviewers sign off on them.</p>
        </div>
    <?php else: ?>
        <ul class="request-list">
            <?php foreach ($upcoming as $event) { $renderEvent($event); } ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($past)): ?>
        <div class="section-head">
            <h2>Past events <span class="count"><?= count($past) ?></span></h2>
        </div>
        <ul class="request-list is-past">
            <?php foreach ($past as $event) { $renderEvent($event); } ?>
        </ul>
    <?php endif; ?>
</div>
