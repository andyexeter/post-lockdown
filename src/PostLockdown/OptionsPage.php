<?php

namespace PostLockdown;

class OptionsPage
{
    public const PAGE_TITLE  = 'Post Lockdown';
    public const AJAX_ACTION = 'pl_autocomplete';
    /** @var string Page hook returned by add_options_page(). */
    private $page_hook;
    /** @var PostLockdown */
    private $postlockdown;

    public function __construct(PostLockdown $postlockdown)
    {
        $this->postlockdown = $postlockdown;

        add_action('admin_init', [$this, '_register_setting']);
        add_action('admin_menu', [$this, '_add_options_page']);

        add_action('admin_enqueue_scripts', [$this, '_enqueue_scripts']);
        add_action(sprintf('wp_ajax_%s', self::AJAX_ACTION), [$this, '_ajax_autocomplete']);

        add_filter('option_page_capability_' . PostLockdown::KEY, [$this->postlockdown, 'get_admin_cap']);

        add_filter('admin_footer_text', [$this, '_filter_admin_footer_text']);
    }

    /**
     * Callback for the 'admin_init' hook.
     *
     * Registers the plugin's option name so it gets saved.
     */
    public function _register_setting()
    {
        register_setting(PostLockdown::KEY, PostLockdown::KEY);
    }

    /**
     * Callback for the 'admin_menu' hook.
     *
     * Adds the plugin's options page.
     */
    public function _add_options_page()
    {
        $this->page_hook = add_options_page(
            self::PAGE_TITLE,
            self::PAGE_TITLE,
            $this->postlockdown->get_admin_cap(),
            PostLockdown::KEY,
            [$this, '_output_options_page']
        );
    }

    /**
     * Callback used by add_options_page().
     *
     * Outputs the options page HTML.
     */
    public function _output_options_page()
    {
        $blocks = [];

        $blocks[] = [
            'key'         => 'locked',
            'heading'     => __('Locked Posts', 'post-lockdown'),
            'input_name'  => 'locked_post_ids',
            'description' => __('Locked posts cannot be edited, trashed or deleted by non-admins', 'post-lockdown'),
        ];

        $blocks[] = [
            'key'         => 'protected',
            'heading'     => __('Protected Posts', 'post-lockdown'),
            'input_name'  => 'protected_post_ids',
            'description' => __('Protected posts cannot be trashed or deleted by non-admins', 'post-lockdown'),
        ];

        $bulk_actions_enabled = $this->postlockdown->is_bulk_actions_enabled();

        include_once $this->postlockdown->plugin_path . 'view/options-page.php';
    }

    /**
     * Callback for the 'pl_autocomplete' AJAX action.
     *
     * Responds with a json encoded array of posts matching the query.
     */
    public function _ajax_autocomplete()
    {
        check_ajax_referer(self::AJAX_ACTION);

        if (!current_user_can($this->postlockdown->get_admin_cap())) {
            wp_send_json_error(null, 403);
        }

        $posts = $this->postlockdown->get_posts([
            's'              => $_REQUEST['term'],
            'offset'         => (int)$_REQUEST['offset'],
            'posts_per_page' => 10,
        ]);

        wp_send_json_success($posts);
    }

    /**
     * Callback for the 'admin_enqueue_scripts' hook.
     *
     * Enqueues the required scripts and styles for the plugin options page.
     *
     * @param string $hook The current admin screen.
     */
    public function _enqueue_scripts($hook)
    {
        // If it's not the plugin options page get out of here.
        if ($hook !== $this->page_hook) {
            return;
        }

        $assets_path = $this->postlockdown->plugin_url . 'view/assets/';

        wp_enqueue_style(PostLockdown::KEY, $assets_path . 'postlockdown.css', null, null);
        wp_enqueue_script(PostLockdown::KEY, $assets_path . 'postlockdown.js', ['jquery-ui-autocomplete'], null, true);

        $posts = $this->postlockdown->get_posts([
            'nopaging' => true,
            'post__in' => array_merge(
                $this->postlockdown->get_locked_post_ids(true),
                $this->postlockdown->get_protected_post_ids(true)
            ),
        ]);

        $data = [];

        foreach ($posts as $post) {
            if ($this->postlockdown->is_post_locked($post->ID)) {
                $data['locked'][] = $post;
            }

            if ($this->postlockdown->is_post_protected($post->ID)) {
                $data['protected'][] = $post;
            }
        }

        wp_localize_script(PostLockdown::KEY, PostLockdown::KEY, $data);
    }

    /**
     * Filter for the 'admin_footer_text' hook.
     * Changes the footer message on the plugin options page.
     *
     * @param string $html
     *
     * @return string
     */
    public function _filter_admin_footer_text($html)
    {
        $screen = get_current_screen();

        if ($screen->id !== $this->page_hook) {
            return $html;
        }

        $text = sprintf(__('Thank you for using Post Lockdown. If you like it, please consider <a href="%s" target="_blank">leaving a review.</a>'), __('https://wordpress.org/support/view/plugin-reviews/post-lockdown?rate=5#postform'));

        $html = '<span id="footer-thankyou">' . $text . '</span>';

        return $html;
    }
}
