<?php
/**
 * Shared draft form fields for create.php and edit.php.
 * Expects: $old, $errors, $upcomingHolidays, $holidayCountry
 */
use FlowForge\Core\View;
?>
<div class="form-group">
    <label for="title" class="form-label">Activity title <span class="req">*</span></label>
    <input type="text" id="title" name="title" class="form-control"
           value="<?= View::e($old['title'] ?? '') ?>" required maxlength="200"
           placeholder="e.g. Annual Inter-College Hackathon 2026">
    <?php if (!empty($errors['title'])): ?>
        <div class="form-error"><?= View::e($errors['title'][0]) ?></div>
    <?php endif; ?>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="event_date" class="form-label">Event date <span class="req">*</span></label>
        <input type="date" id="event_date" name="event_date" class="form-control"
               value="<?= View::e($old['event_date'] ?? '') ?>" required
               aria-describedby="holiday-status">
        <?php if (!empty($errors['event_date'])): ?>
            <div class="form-error"><?= View::e($errors['event_date'][0]) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="venue" class="form-label">Venue / location <span class="req">*</span></label>
        <input type="text" id="venue" name="venue" class="form-control"
               value="<?= View::e($old['venue'] ?? '') ?>" required maxlength="200"
               placeholder="e.g. Student Center Multipurpose Hall">
        <?php if (!empty($errors['venue'])): ?>
            <div class="form-error"><?= View::e($errors['venue'][0]) ?></div>
        <?php endif; ?>
    </div>
</div>

<div id="holiday-status" class="holiday-status" role="status" aria-live="polite" hidden></div>

<div class="form-group">
    <label for="description" class="form-label">Activity description &amp; objectives <span class="req">*</span></label>
    <textarea id="description" name="description" class="form-control" rows="6" required
              placeholder="Outline the scope, intended participants, schedule, and purpose of the activity..."><?= View::e($old['description'] ?? '') ?></textarea>
    <?php if (!empty($errors['description'])): ?>
        <div class="form-error"><?= View::e($errors['description'][0]) ?></div>
    <?php endif; ?>
</div>

<?php if (!empty($upcomingHolidays)): ?>
    <div class="holiday-hint">
        <span class="eyebrow">Upcoming public holidays &bull; <?= View::e($holidayCountry ?? '') ?></span>
        <ul>
            <?php foreach ($upcomingHolidays as $holiday): ?>
                <li>
                    <span class="mono"><?= View::e($holiday['date']) ?></span>
                    <?= View::e($holiday['name']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <small>Source: Nager.Date public holiday API</small>
    </div>
<?php endif; ?>

<script>
    // Live check: ask our own API (which calls Nager.Date) whether the chosen date is a holiday.
    (function () {
        var input = document.getElementById('event_date');
        var box = document.getElementById('holiday-status');
        var endpoint = <?= json_encode(\FlowForge\Core\Url::to('/api/holidays/check')) ?>;

        function show(kind, text) {
            box.hidden = false;
            box.className = 'holiday-status is-' + kind;
            box.textContent = text;
        }

        function check() {
            if (!input.value) { box.hidden = true; return; }
            fetch(endpoint + '?date=' + encodeURIComponent(input.value), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
                .then(function (r) {
                    if (!r.ok) { show('muted', 'Holiday check unavailable: ' + (r.body.message || 'service error')); return; }
                    if (r.body.is_holiday) {
                        show('warning', input.value + ' is a public holiday: ' + r.body.holiday.name + '. Consider another date.');
                    } else {
                        show('ok', 'No public holiday on ' + input.value + '.');
                    }
                })
                .catch(function () { show('muted', 'Holiday check unavailable right now.'); });
        }

        input.addEventListener('change', check);
        if (input.value) { check(); }
    })();
</script>
