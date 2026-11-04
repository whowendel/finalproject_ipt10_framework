<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>

<div style="max-width: 480px; margin: 2rem auto;">
    <div class="demo-banner">
        <strong>Demo Accounts for Local Evaluation:</strong>
        <ul style="margin: 0.5rem 0 0 1.25rem; font-size: 0.85rem; line-height: 1.6;">
            <li><strong>Submitter (Student):</strong> <code>student@flowforge.test</code> &bull; <code>password123</code></li>
            <li><strong>Reviewer (Faculty):</strong> <code>reviewer@flowforge.test</code> &bull; <code>password123</code></li>
        </ul>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Sign In to FlowForge</h2>
        </div>

        <?php if (!empty($errors['auth'])): ?>
            <div class="alert alert-error">
                <?= View::e($errors['auth'][0]) ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= View::e($old['email'] ?? '') ?>" required autofocus placeholder="e.g. student@flowforge.test">
                <?php if (!empty($errors['email'])): ?>
                    <div class="form-error"><?= View::e($errors['email'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       required placeholder="password123">
                <?php if (!empty($errors['password'])): ?>
                    <div class="form-error"><?= View::e($errors['password'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
            </div>
        </form>
    </div>
</div>
