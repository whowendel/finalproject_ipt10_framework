<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>

<section class="page-hero">
    <div class="container">
        <div class="hero-inner">
            <a href="<?= View::url() ?>/requests/<?= (int)$request->id ?>" class="crumb"><span aria-hidden="true">&larr;</span> Back to request</a>
            <div><span class="eyebrow">Request #<?= (int)$request->id ?> &bull; Draft</span></div>
            <h1>Edit Draft</h1>
            <p class="hero-lede">
                Drafts can be changed freely. Once you submit a request for review, it is locked.
            </p>
        </div>
    </div>
</section>

<div class="container container-narrow page-body">
    <form action="<?= View::url() ?>/requests/<?= (int)$request->id ?>/update" method="POST" class="panel panel-accent">
        <?= Csrf::field() ?>

        <?php require __DIR__ . '/_form.php'; ?>

        <div class="form-actions">
            <a href="<?= View::url() ?>/requests/<?= (int)$request->id ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save changes <span class="arrow" aria-hidden="true">&rarr;</span></button>
        </div>
    </form>
</div>
