<?php

namespace PostLockdown;

class AdminNotice
{
    /** Query arg used to determine if an admin notice should be displayed. */
    public const QUERY_ARG = 'plstatuschange';

    private $plugin_path;

    public function __construct($plugin_path)
    {
        $this->plugin_path = $plugin_path;
        add_action('admin_notices', [$this, '_output_admin_notices']);
        add_filter('removable_query_args', [$this, '_remove_query_arg']);
    }

    /**
     * Filter for the 'redirect_post_location' hook.
     *
     * @see PostLockdown::_prevent_status_change()
     *
     * @param string $location
     *
     * @return string
     */
    public function _add_query_arg($location)
    {
        return add_query_arg(self::QUERY_ARG, 1, $location);
    }

    /**
     * Filter for the 'removable_query_args' hook.
     *
     * Adds the plugin's query arg to the array of args
     * removed by WordPress using the JavaScript History API.
     *
     * @param array $args Array of query args to be removed.
     *
     * @return array
     */
    public function _remove_query_arg($args)
    {
        $args[] = self::QUERY_ARG;

        return $args;
    }

    /**
     * Callback for the 'admin_notices' hook.
     *
     * Outputs the plugin's admin notices if there are any.
     */
    public function _output_admin_notices()
    {
        $notices = [];

        if (isset($_GET[self::QUERY_ARG])) {
            $notices[] = [
                'class'   => 'error',
                'message' => __('This post is protected by Post Lockdown and must stay published.', 'post-lockdown'),
            ];
        }

        if (!empty($notices)) {
            include_once $this->plugin_path . 'view/admin-notices.php';
        }
    }
}
