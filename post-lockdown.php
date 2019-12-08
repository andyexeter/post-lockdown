<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to protect selected posts and pages so they cannot be trashed or deleted by non-admin users.
 * Version: 2.1
 * Author: Andy Palmer
 * Author URI: https://andypalmer.me
 * License: GPL2
 * Text Domain: postlockdown.
 */
if (is_admin()) {
    require_once __DIR__ . '/src/PostLockdown/PostLockdown.php';
    require_once __DIR__ . '/src/PostLockdown/OptionsPage.php';
    require_once __DIR__ . '/src/PostLockdown/AdminNotice.php';
    require_once __DIR__ . '/src/PostLockdown/StatusColumn.php';

    global $postlockdown;
    $postlockdown = new \PostLockdown\PostLockdown(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));

    register_uninstall_hook(__FILE__, ['PostLockdown', '_uninstall']);
}
