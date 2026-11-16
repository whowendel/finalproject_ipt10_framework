<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;

// Views rendered without a controller-supplied user (e.g. error pages) get the
// raw session array, so normalise it to a User model here.
if (isset($user) && is_array($user)) {
    $user = new \App\Models\User($user);
}

$currentPath = \FlowForge\Core\Url::currentPath();
$isActive = static function (string $section) use ($currentPath): string {
    $match = match ($section) {
        'events' => str_starts_with($currentPath, '/events'),
        'create' => $currentPath === '/requests/create',
        'queue' => str_starts_with($currentPath, '/reviewer'),
        'requests' => str_starts_with($currentPath, '/requests') && $currentPath !== '/requests/create',
        default => false,
    };
    return $match ? ' class="is-active" aria-current="page"' : '';
};

$initials = '';
if (!empty($user)) {
    foreach (preg_split('/\s+/', trim((string)$user->name)) ?: [] as $part) {
        $letter = preg_replace('/[^A-Za-z]/', '', $part);
        if ($letter !== '' && $letter !== null) {
            $initials .= strtoupper($letter[0]);
        }
    }
    $initials = substr($initials, -2) ?: '?';
}

$flashTypes = [
    'success' => '&#10003;',
    'error' => '!',
    'warning' => '!',
    'info' => 'i',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'FlowForge - Activity Workflow System') ?></title>
    <link rel="stylesheet" href="<?= View::url() ?>/assets/app.css">
</head>
<body>
    <header class="site-header">
        <div class="container nav-inner">
            <a href="<?= View::url() ?>/" class="brand" aria-label="FlowForge home">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </span>
                <div>Flow<span>Forge</span></div>
            </a>

            <nav aria-label="Primary" class="main-nav-wrap">
                <ul class="main-nav">
                    <li><a href="<?= View::url() ?>/events"<?= $isActive('events') ?>>Events</a></li>
                    <?php if (!empty($user)): ?>
                        <li><a href="<?= View::url() ?>/requests"<?= $isActive('requests') ?>>Requests</a></li>
                        <?php if ($user->isReviewer()): ?>
                            <li><a href="<?= View::url() ?>/reviewer/queue"<?= $isActive('queue') ?>>Review Queue</a></li>
                        <?php else: ?>
                            <li><a href="<?= View::url() ?>/requests/create"<?= $isActive('create') ?>>New Request</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>

            <?php if (!empty($user)): ?>
                <div class="nav-account">
                    <div class="account">
                        <span class="avatar" aria-hidden="true"><?= View::e($initials) ?></span>
                        <span class="account-meta">
                            <strong><?= View::e($user->name) ?></strong>
                            <span class="role-pill <?= View::e($user->role) ?>"><?= View::e($user->role) ?></span>
                        </span>
                    </div>
                    <form action="<?= View::url() ?>/logout" method="POST">
                        <?= Csrf::field() ?>
                        <button type="submit" class="link-button">Sign out</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="nav-account">
                    <a href="<?= View::url() ?>/login" class="btn btn-primary btn-sm">Sign in</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <?php foreach ($flashTypes as $type => $icon): ?>
            <?php if (!empty($flash[$type])): ?>
                <div class="flash flash-<?= $type ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
                    <div class="container">
                        <span class="flash-icon" aria-hidden="true"><?= $icon ?></span>
                        <span><?= View::e($flash[$type]) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?= $content ?? '' ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div>
                <div class="footer-brand">Flow<span>Forge</span></div>
                <div>A DIY PHP microframework &bull; IPT10 Workflow &amp; Approval Reference System</div>
            </div>
            <div class="footer-flow">Draft &rarr; Submitted &rarr; Approved / Rejected</div>
        </div>
    </footer>
</body>
</html>
