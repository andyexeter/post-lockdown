<div class="wrap">
    <h2><?php echo esc_html(PostLockdown\OptionsPage::PAGE_TITLE); ?></h2>
    <form action="options.php" method="post">
        <?php settings_fields(PostLockdown\PostLockdown::KEY); ?>
        <p>
            <?php esc_html_e('Select locked and protected posts by adding them to the boxes on the right. Use the search field to filter the list of posts.', 'post-lockdown'); ?>
        </p>
        <table class="form-table" data-ajax_action="<?php echo esc_attr(PostLockdown\OptionsPage::AJAX_ACTION); ?>" data-ajax_nonce="<?php echo esc_attr(wp_create_nonce(PostLockdown\OptionsPage::AJAX_ACTION)); ?>">
            <tbody>
            <?php foreach ($blocks as $block): ?>
                <tr>
                    <th scope="row"><?php echo esc_html($block['heading']); ?></th>
                    <td>
                        <div class="pl-posts-container">
                            <div class="pl-posts pl-posts-available">
                                <div class="pl-searchbox">
                                    <input type="text" autocomplete="off" class="pl-autocomplete" placeholder="<?php esc_attr_e('Search...', 'post-lockdown'); ?>">
                                </div>
                                <span class="spinner"></span>
                                <ul class="pl-multiselect"></ul>
                            </div>
                            <div class="pl-posts pl-posts-selected">
                                <ul class="pl-multiselect"
                                    data-key="<?php echo esc_attr($block['key']); ?>"
                                    data-input_name="<?php echo esc_attr(PostLockdown\PostLockdown::KEY); ?>[<?php echo esc_attr($block['input_name']); ?>]"
                                >
                                </ul>
                            </div>
                        </div>
                        <p class="description"><?php echo esc_html($block['description']); ?></p>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th scope="row">Bulk Actions</th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span>Bulk Actions</span></legend>
                        <label for="bulk_actions_enabled">
                            <input name="<?php echo esc_attr(PostLockdown\PostLockdown::KEY); ?>[bulk_actions_enabled]" type="checkbox" id="bulk_actions_enabled" value="1" <?php checked($bulk_actions_enabled); ?>>
                            Enable bulk actions on post list screens
                        </label>
                    </fieldset>
                </td>
            </tr>
            </tbody>
        </table>
        <input name="submit" type="submit" class="button button-primary" value="<?php echo esc_attr('Save Changes'); ?>">
    </form>
</div>
