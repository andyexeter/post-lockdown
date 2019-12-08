<?php foreach ($notices as $notice): ?>
    <div class="notice is-dismissible <?= esc_attr(\implode(' ', (array)$notice['class'])); ?>">
        <p><?= esc_html($notice['message']); ?></p>
    </div>
<?php endforeach; ?>
