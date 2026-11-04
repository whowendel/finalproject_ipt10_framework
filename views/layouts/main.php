<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'FlowForge - Activity Workflow System') ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
    <header class="navbar">
        <div class="container nav-inner">
            <a href="/" class="navbar-brand">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                Flow<span>Forge</span>
            </a>

            <nav>
                <ul class="nav-links">
                    <?php if (!empty($user)): ?>
                        <li><a href="/requests">Requests</a></li>
                        <?php if ($user->isReviewer()): ?>
                            <li><a href="/reviewer/queue">Reviewer Queue</a></li>
                        <?php else: ?>
                            <li><a href="/requests/create">+ New Request</a></li>
                        <?php endif; ?>

                        <li class="user-badge">
                            <strong><?= View::e($user->name) ?></strong>
                            <span class="role-pill <?= View::e($user->role) ?>"><?= View::e($user->role) ?></span>
                        </li>

                        <li>
                            <form action="/logout" method="POST" style="display:inline;">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn-secondary btn-sm" style="padding:0.25rem 0.6rem; font-size:0.8rem;">Logout</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li><a href="/login">Sign In</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success"><?= View::e($flash['success']) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-error"><?= View::e($flash['error']) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash['warning'])): ?>
                <div class="alert alert-warning"><?= View::e($flash['warning']) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash['info'])): ?>
                <div class="alert alert-info"><?= View::e($flash['info']) ?></div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </div>
    </main>

    <footer>
        <div class="container">
            FlowForge DIY PHP Microframework &bull; IPT10 Workflow & Approval Reference System
        </div>
    </footer>
</body>
</html>
