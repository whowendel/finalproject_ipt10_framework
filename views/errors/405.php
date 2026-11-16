<?php
use FlowForge\Core\View;
?>

<div class="container error-page">
    <div class="error-code" aria-hidden="true">405</div>
    <div>
        <span class="eyebrow">Error 405</span>
        <h1>Method not allowed</h1>
        <p><?= View::e($message ?? 'This page does not accept that kind of request.') ?></p>
        <a href="<?= View::url() ?>/" class="btn btn-primary"><span aria-hidden="true">&larr;</span> Return home</a>
    </div>
</div>
