<div class="wrap">
    <h2><?= esc_html(PostLockdown\OptionsPage::PAGE_TITLE); ?></h2>
    <form action="options.php" method="post">
        <?php settings_fields(PostLockdown\PostLockdown::KEY); ?>
        <p>
            <?php esc_html_e('Select locked and protected posts by adding them to the boxes on the right. Use the search field to filter the list of posts.', 'postlockdown'); ?>
        </p>
        <table class="form-table">
            <tbody>
            <?php foreach ($blocks as $block): ?>
                <tr>
                    <th><?= esc_html($block['heading']); ?></th>
                    <td>
                        <div class="pl-posts-container">
                            <div class="pl-posts pl-posts-available">
                                <div class="pl-searchbox">
                                    <input type="text" autocomplete="off" class="pl-autocomplete" placeholder="<?php esc_attr_e('Search...', 'postlockdown'); ?>">
                                </div>
                                <span class="spinner"></span>
                                <ul class="pl-multiselect"></ul>
                            </div>
                            <div class="pl-posts pl-posts-selected">
                                <ul class="pl-multiselect"
                                    data-key="<?= esc_attr($block['key']); ?>"
                                    data-input_name="<?= esc_attr(PostLockdown\PostLockdown::KEY); ?>[<?= esc_attr($block['input_name']); ?>]"
                                >
                                </ul>
                            </div>
                        </div>
                        <p class="description"><?= esc_html($block['description']); ?></p>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <input name="submit" type="submit" class="button button-primary" value="<?= esc_attr('Save Changes'); ?>">
    </form>
</div>
