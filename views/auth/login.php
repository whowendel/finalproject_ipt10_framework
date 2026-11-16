<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>

<div class="auth-split">
    <section class="auth-pitch">
        <span class="eyebrow">Campus Activity Approvals</span>
        <h1>Every proposal, <em>one clear path</em> to a decision.</h1>
        <p>
            Draft an activity request, send it to faculty, and follow each step
            with a full audit trail of who decided what, and when.
        </p>
        <ol class="flow-steps" aria-label="Workflow stages">
            <li>Draft</li>
            <li class="sep" aria-hidden="true">&rarr;</li>
            <li>Submitted</li>
            <li class="sep" aria-hidden="true">&rarr;</li>
            <li>Approved / Rejected</li>
        </ol>
    </section>

    <section class="auth-form">
        <div class="auth-form-inner">
            <span class="eyebrow">Welcome back</span>
            <h2>Sign in to FlowForge</h2>
            <p class="sub">Use your campus account to continue.</p>

            <?php if (!empty($errors['auth'])): ?>
                <div class="alert alert-error" role="alert">
                    <?= View::e($errors['auth'][0]) ?>
                </div>
            <?php endif; ?>

            <form action="<?= View::url() ?>/login" method="POST">
                <?= Csrf::field() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= View::e($old['email'] ?? '') ?>" required autofocus
                           autocomplete="username" placeholder="you@flowforge.test">
                    <?php if (!empty($errors['email'])): ?>
                        <div class="form-error"><?= View::e($errors['email'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           required autocomplete="current-password" placeholder="Enter your password">
                    <?php if (!empty($errors['password'])): ?>
                        <div class="form-error"><?= View::e($errors['password'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Sign in <span class="arrow" aria-hidden="true">&rarr;</span>
                </button>
            </form>

            <div class="demo-accounts">
                <span class="eyebrow">Demo accounts for local evaluation</span>

                <button type="button" class="demo-account" data-email="student@flowforge.test" data-password="password123">
                    <span>
                        <strong>Submitter (Student)</strong>
                        <code>student@flowforge.test</code> &bull; <code>password123</code>
                    </span>
                    <span class="use">Use &rarr;</span>
                </button>

                <button type="button" class="demo-account" data-email="reviewer@flowforge.test" data-password="password123">
                    <span>
                        <strong>Reviewer (Faculty)</strong>
                        <code>reviewer@flowforge.test</code> &bull; <code>password123</code>
                    </span>
                    <span class="use">Use &rarr;</span>
                </button>
            </div>
        </div>
    </section>
</div>

<script>
    // Clicking a demo account fills the sign-in form (it does not submit it).
    document.querySelectorAll('.demo-account').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('email').value = button.dataset.email;
            var password = document.getElementById('password');
            password.value = button.dataset.password;
            password.focus();
        });
    });
</script>
