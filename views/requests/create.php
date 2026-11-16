<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>

<section class="page-hero">
    <div class="container">
        <div class="hero-inner">
            <a href="<?= View::url() ?>/requests" class="crumb"><span aria-hidden="true">&larr;</span> Back to requests</a>
            <div><span class="eyebrow">Step 1 of 3 &bull; Draft</span></div>
            <h1>Create Activity Request</h1>
            <p class="hero-lede">
                New activity requests are saved as a <strong>draft</strong>. You can review details before submitting.
            </p>
        </div>
    </div>
</section>

<div class="container container-narrow page-body">
    <form action="<?= View::url() ?>/requests" method="POST" class="panel panel-accent">
        <?= Csrf::field() ?>

        <?php require __DIR__ . '/_form.php'; ?>

        <div class="form-actions">
            <a href="<?= View::url() ?>/requests" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save draft <span class="arrow" aria-hidden="true">&rarr;</span></button>
        </div>
    </form>
</div>
