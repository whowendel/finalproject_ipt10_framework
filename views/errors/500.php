<?php
use FlowForge\Core\View;
?>

<div class="card" style="max-width: 580px; margin: 3rem auto; text-align: center; padding: 3rem 2rem;">
    <h1 style="font-size: 3rem; color: #dc2626; margin-bottom: 0.5rem;">500</h1>
    <h2 style="font-size: 1.25rem; color: #1e293b; margin-bottom: 1rem;">Internal Server Error</h2>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
        <?= View::e($message ?? 'An unexpected error occurred while processing your request.') ?>
    </p>
    <?php if (!empty($debug) && !empty($exception)): ?>
        <div style="text-align: left; background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.825rem; overflow-x: auto;">
            <strong><?= View::e(get_class($exception)) ?>:</strong> <?= View::e($exception->getMessage()) ?><br>
            <span style="color: #64748b;">in <?= View::e($exception->getFile()) ?> on line <?= (int)$exception->getLine() ?></span>
        </div>
    <?php endif; ?>
    <a href="/requests" class="btn btn-primary">&larr; Return to Dashboard</a>
</div>
