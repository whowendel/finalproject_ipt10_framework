<?php
use FlowForge\Core\View;
?>

<div class="container error-page">
    <div class="error-code" aria-hidden="true">419</div>
    <div>
        <span class="eyebrow">Error 419</span>
        <h1>Session expired (CSRF)</h1>
        <p><?= View::e($message ?? 'Your security token expired or is invalid. Please refresh the page and try again.') ?></p>
        <a href="javascript:history.back()" class="btn btn-primary"><span aria-hidden="true">&larr;</span> Go back</a>
    </div>
</div>
