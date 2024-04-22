<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to protect selected posts and pages so they cannot be trashed or deleted by non-admin users.
 * Version: 3.0.6
 * Author: Andy Palmer
 * Author URI: https://andypalmer.me
 * License: GPL2
 * Text Domain: postlockdown
 * Domain Path: /languages
 */
if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
    require_once __DIR__ . '/src/PostLockdown/PostLockdown.php';
    require_once __DIR__ . '/src/PostLockdown/OptionsPage.php';
    require_once __DIR__ . '/src/PostLockdown/AdminNotice.php';
    require_once __DIR__ . '/src/PostLockdown/StatusColumn.php';
    require_once __DIR__ . '/src/PostLockdown/BulkActions.php';
    require_once __DIR__ . '/src/PostLockdown/WpCli.php';

    global $postlockdown;
    $postlockdown = new \PostLockdown\PostLockdown(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));

    register_uninstall_hook(__FILE__, ['PostLockdown', '_uninstall']);

    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('postlockdown', new \PostLockdown\WpCli($postlockdown));
    }
}
