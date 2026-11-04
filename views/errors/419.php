<?php
use FlowForge\Core\View;
?>

<div class="card" style="max-width: 520px; margin: 3rem auto; text-align: center; padding: 3rem 2rem;">
    <h1 style="font-size: 3rem; color: #d97706; margin-bottom: 0.5rem;">419</h1>
    <h2 style="font-size: 1.25rem; color: #1e293b; margin-bottom: 1rem;">Session Expired (CSRF)</h2>
    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
        <?= View::e($message ?? 'Your security token expired or is invalid. Please refresh the page and try again.') ?>
    </p>
    <a href="javascript:history.back()" class="btn btn-primary">&larr; Go Back</a>
</div>
