<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to lock selected posts and pages so they cannot be edited or deleted by non-admin users.
 * Version: 2.0
 * Author: Andy Palmer
 * Author URI: http://www.andypalmer.me
 * License: GPL2
 * Text Domain: postlockdown
 */
if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/classes/class.post-lockdown.php';
	require_once dirname( __FILE__ ) . '/classes/class.post-lockdown-options-page.php';
	require_once dirname( __FILE__ ) . '/classes/class.post-lockdown-admin-notice.php';
	require_once dirname( __FILE__ ) . '/classes/class.post-lockdown-status-column.php';

	global $postlockdown;
	$postlockdown = new PostLockdown( plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ) );

	register_uninstall_hook( __FILE__, array( 'PostLockdown', '_uninstall' ) );
}
