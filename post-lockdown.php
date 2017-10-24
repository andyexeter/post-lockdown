<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to protect selected posts and pages so they cannot be trashed or deleted by non-admin users.
 * Version: 2.0.3
 * Author: Andy Palmer
 * Author URI: http://www.andypalmer.me
 * License: GPL2
 * Text Domain: postlockdown
 */
if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/classes/class-postlockdown.php';
	require_once dirname( __FILE__ ) . '/classes/class-postlockdown-optionspage.php';
	require_once dirname( __FILE__ ) . '/classes/class-postlockdown-adminnotice.php';
	require_once dirname( __FILE__ ) . '/classes/class-postlockdown-statuscolumn.php';

	global $postlockdown;
	$postlockdown = new PostLockdown( plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ) );

	register_uninstall_hook( __FILE__, array( 'PostLockdown', '_uninstall' ) );
}
