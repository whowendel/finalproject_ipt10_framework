<?php
use FlowForge\Core\View;
?>

<div class="container error-page">
    <div class="error-code" aria-hidden="true">403</div>
    <div>
        <span class="eyebrow">Error 403</span>
        <h1>Access forbidden</h1>
        <p><?= View::e($message ?? 'You are not authorized to perform this action or view this resource.') ?></p>
        <a href="<?= View::url() ?>/requests" class="btn btn-primary"><span aria-hidden="true">&larr;</span> Return to dashboard</a>
    </div>
</div>
