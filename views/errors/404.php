<?php
use FlowForge\Core\View;
?>

<div class="container error-page">
    <div class="error-code" aria-hidden="true">404</div>
    <div>
        <span class="eyebrow">Error 404</span>
        <h1>Page or resource not found</h1>
        <p><?= View::e($message ?? 'The requested page or record could not be found.') ?></p>
        <a href="<?= View::url() ?>/requests" class="btn btn-primary"><span aria-hidden="true">&larr;</span> Return to dashboard</a>
    </div>
</div>
