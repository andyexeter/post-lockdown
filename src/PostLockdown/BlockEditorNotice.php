<?php

namespace PostLockdown;

class BlockEditorNotice
{
    /** REST field that tells the block editor a status change was just reverted. */
    public const REST_FIELD = 'postlockdown_status_reverted';

    /** Transient prefix used to flag a revert between the save and its response. */
    private const TRANSIENT_PREFIX = 'postlockdown_reverted_';

    /** @var PostLockdown */
    private $postlockdown;

    public function __construct(PostLockdown $postlockdown)
    {
        $this->postlockdown = $postlockdown;

        add_action('rest_api_init', [$this, '_register_rest_field']);
        add_action('enqueue_block_editor_assets', [$this, '_enqueue_assets']);
    }

    /**
     * Records that a protected post's status change was reverted during the
     * current REST (block editor) save, so the response can report it back.
     *
     * @see PostLockdown::_prevent_status_change()
     *
     * @param int $post_id
     */
    public function flag_reverted($post_id)
    {
        // The classic editor surfaces this via a redirect notice instead.
        if (!\defined('REST_REQUEST') || !REST_REQUEST) {
            return;
        }

        set_transient($this->transient_key($post_id), 1, MINUTE_IN_SECONDS);
    }

    /**
     * Callback for the 'rest_api_init' hook.
     *
     * Adds a read-once boolean field to each editable post type's REST response.
     */
    public function _register_rest_field()
    {
        foreach ($this->postlockdown->get_post_types() as $post_type) {
            register_rest_field($post_type, self::REST_FIELD, [
                'get_callback' => [$this, '_get_reverted_field'],
                'schema'       => [
                    'type'        => 'boolean',
                    'description' => __('Whether Post Lockdown just reverted this post\'s status.', 'post-lockdown'),
                    'context'     => ['edit'],
                ],
            ]);
        }
    }

    /**
     * Get callback for the REST field.
     *
     * Returns (and clears) the revert flag so it is reported exactly once - in
     * the response to the save that triggered it.
     *
     * @param array $post
     *
     * @return bool
     */
    public function _get_reverted_field($post)
    {
        $key = $this->transient_key($post['id']);

        if (get_transient($key)) {
            delete_transient($key);

            return true;
        }

        return false;
    }

    /**
     * Callback for the 'enqueue_block_editor_assets' hook.
     */
    public function _enqueue_assets()
    {
        // Admins are never restricted, so they can never trigger a revert.
        if (current_user_can($this->postlockdown->get_admin_cap())) {
            return;
        }

        $handle = PostLockdown::KEY . '-block-editor';

        wp_enqueue_script(
            $handle,
            $this->postlockdown->plugin_url . 'view/assets/block-editor.js',
            ['wp-data', 'wp-editor', 'wp-notices', 'wp-i18n'],
            PostLockdown::VERSION,
            true
        );

        wp_set_script_translations($handle, 'post-lockdown');
    }

    /**
     * @param int $post_id
     *
     * @return string
     */
    private function transient_key($post_id)
    {
        return self::TRANSIENT_PREFIX . get_current_user_id() . '_' . (int)$post_id;
    }
}
