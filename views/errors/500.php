<?php
use FlowForge\Core\View;
?>

<div class="container error-page">
    <div class="error-code" aria-hidden="true">500</div>
    <div>
        <span class="eyebrow">Error 500</span>
        <h1>Internal server error</h1>
        <p><?= View::e($message ?? 'An unexpected error occurred while processing your request.') ?></p>
        <?php if (!empty($debug) && !empty($exception)): ?>
            <div class="debug-box">
                <strong><?= View::e(get_class($exception)) ?>:</strong> <?= View::e($exception->getMessage()) ?><br>
                <span>in <?= View::e($exception->getFile()) ?> on line <?= (int)$exception->getLine() ?></span>
            </div>
        <?php endif; ?>
        <a href="<?= View::url() ?>/requests" class="btn btn-primary"><span aria-hidden="true">&larr;</span> Return to dashboard</a>
    </div>
</div>
