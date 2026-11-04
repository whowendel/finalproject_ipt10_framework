<?php
use FlowForge\Core\View;
use FlowForge\Support\Csrf;
?>

<div style="max-width: 680px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Create Activity Request</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                    New activity requests are saved as a <strong>draft</strong>. You can review details before submitting.
                </p>
            </div>
            <a href="/requests" class="btn btn-secondary btn-sm">&larr; Back</a>
        </div>

        <form action="/requests" method="POST">
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="title" class="form-label">Activity Title <span style="color:var(--danger)">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       value="<?= View::e($old['title'] ?? '') ?>" required maxlength="200"
                       placeholder="e.g. Annual Inter-College Hackathon 2026">
                <?php if (!empty($errors['title'])): ?>
                    <div class="form-error"><?= View::e($errors['title'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="event_date" class="form-label">Event Date <span style="color:var(--danger)">*</span></label>
                <input type="date" id="event_date" name="event_date" class="form-control"
                       value="<?= View::e($old['event_date'] ?? '') ?>" required>
                <?php if (!empty($errors['event_date'])): ?>
                    <div class="form-error"><?= View::e($errors['event_date'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="venue" class="form-label">Venue / Location <span style="color:var(--danger)">*</span></label>
                <input type="text" id="venue" name="venue" class="form-control"
                       value="<?= View::e($old['venue'] ?? '') ?>" required maxlength="200"
                       placeholder="e.g. Student Center Multipurpose Hall">
                <?php if (!empty($errors['venue'])): ?>
                    <div class="form-error"><?= View::e($errors['venue'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Activity Description & Objectives <span style="color:var(--danger)">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5" required
                          placeholder="Outline the scope, intended participants, schedule, and purpose of the activity..."><?= View::e($old['description'] ?? '') ?></textarea>
                <?php if (!empty($errors['description'])): ?>
                    <div class="form-error"><?= View::e($errors['description'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="/requests" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Draft</button>
            </div>
        </form>
    </div>
</div>
