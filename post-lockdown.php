<?php
/**
 * Plugin Name: Post Lockdown
 * Plugin URI: https://github.com/andyexeter/post-lockdown
 * Description: Allows admins to protect selected posts and pages so they cannot be trashed or deleted by non-admin users.
 * Version: 4.0.3
 * Requires at least: 4.6
 * Requires PHP: 7.4
 * Author: Andy Palmer
 * Author URI: https://andypalmer.me
 * Text Domain: post-lockdown
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 */
require_once __DIR__ . '/src/PostLockdown/PostLockdown.php';
require_once __DIR__ . '/src/PostLockdown/OptionsPage.php';
require_once __DIR__ . '/src/PostLockdown/AdminNotice.php';
require_once __DIR__ . '/src/PostLockdown/StatusColumn.php';
require_once __DIR__ . '/src/PostLockdown/BulkActions.php';
require_once __DIR__ . '/src/PostLockdown/WpCli.php';

global $postlockdown;
$postlockdown = new PostLockdown\PostLockdown(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));

register_uninstall_hook(__FILE__, [PostLockdown\PostLockdown::class, '_uninstall']);

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('postlockdown', new PostLockdown\WpCli($postlockdown));
}
