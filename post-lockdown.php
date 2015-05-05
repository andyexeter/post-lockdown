<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to lock selected posts and pages so they cannot be edited or deleted by non-admin users.
 * Version: 0.9.5
 * Author: Andy Palmer
 * Author URI: http://www.andypalmer.me
 * License: GPL2
 */

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	PostLockdown::init();
}

register_uninstall_hook( __FILE__, array( 'PostLockdown', 'uninstall' ) );

class PostLockdown {

	/** Plugin key for options and the option page. */
	const KEY = 'post_lockdown';
	/** Option page title. */
	const TITLE = 'Post Lockdown';

	/** @var array List of post IDs which cannot be edited, trashed or deleted. */
	private static $locked_post_ids = array();
	/** @var array List of post IDs which cannot be trashed or deleted. */
	private static $protected_post_ids = array();

	/**
	 * Plugin init method.
	 * Gets the plugin options and adds the required action and filter callbacks.
	 */
	public static function init() {

		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) );

		add_filter( 'user_has_cap', array( __CLASS__, 'filter_cap' ), 10, 3 );
	}

	/**
	 * Callback for the 'user_has_cap' hook.
	 * Sets the capability to false when {@link https://codex.wordpress.org/Function_Reference/current_user_can current_user_can()} has been called on
	 * one of our {@link PostLockdown::$caps} on a locked post. If the user has the
	 * {@link PostLockdown::CAP} capability we bail out early.
	 */
	public static function filter_cap($allcaps, $cap, $args) {

		// If there's no locked or protected posts get out of here
		if ( ! self::load_options() ) {
			return $allcaps;
		}

		$admin_cap = apply_filters( 'postlockdown_admin_capability', 'manage_options' );

		// Set the capabilities we want to return false for our posts
		$the_caps = apply_filters( 'postlockdown_capabilities', array(
			'delete_post' => true,
			'edit_post' => true,
			'publish_pages' => true,
			'publish_posts' => true,
		) );

		if ( ! isset( $the_caps[ $args[0] ] ) || ! empty( $allcaps[ $admin_cap ] ) ) {
			return $allcaps;
		}

		if ( isset( $args[2] ) ) {
			$post_id = $args[2];
		} else {

			$post = get_post();

			if ( isset( $post->ID ) ) {
				$post_id = $post->ID;
			} else {
				$post_id = filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );
			}
		}

		if ( ! $post_id ) {
			return $allcaps;
		}

		// If we get to this point and the post ID is a locked post,
		// or a protected post for anything except 'edit_posts', then
		// set the requested capability to false.

		$post_ids = self::$locked_post_ids;

		if ( 'edit_post' != $args[0] ) {
			$post_ids += self::$protected_post_ids;
		}

		if ( isset( $post_ids[ $post_id ] ) ) {
			$allcaps[ $cap[0] ] = false;
		}

		return $allcaps;
	}

	/**
	 * Callback for the 'admin_init' hook.
	 * Registers the plugin's option name so it gets saved.
	 */
	public static function register_setting() {
		register_setting( self::KEY, self::KEY );
	}

	/**
	 * Callback for the 'admin_menu' hook.
	 * Adds the plugin's options page.
	 */
	public static function add_options_page() {

		$admin_cap = apply_filters( 'postlockdown_admin_capability', 'manage_options' );

		add_options_page( self::TITLE, self::TITLE, $admin_cap, self::KEY, array( __CLASS__, 'output_options_page' ) );
	}

	/**
	 * Callback used by add_options_page().
	 * Gets an array of post types and their posts and includes the options page HTML.
	 */
	public static function output_options_page() {

		self::load_options();
		
		$posts = get_posts( apply_filters( 'postlockdown_get_posts', array(
			'post_type' => 'any',
			'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
			'nopaging' => true,
			'post__in' => array_merge( array_keys( self::$locked_post_ids ), array_keys( self::$protected_post_ids ) )
		) ) );

		include_once( plugin_dir_path( __FILE__ ) . 'options-page.php' );
	}
	
	public static function is_post_locked( $post_id ) {
		self::load_options();
		
		return isset( self::$locked_post_ids[ $post_id ] );
	}
	
	public static function is_post_protected( $post_id ) {
		self::load_options();
		
		return isset( self::$protected_post_ids[ $post_id ] );
	}

	/**
	 * Callback for register_uninstall_hook() function.
	 * Removes the plugin option from the database when it is uninstalled.
	 */
	public static function uninstall() {
		delete_option( self::KEY );
	}

	/**
	 * Sets the array of locked and protected post IDs.
	 * @return boolean Whether both arrays are empty
	 */
	private static function load_options() {
		
		if ( !empty( self::$locked_post_ids ) && !empty( self::$protected_post_ids ) ) {
			return true;
		}

		$options = get_option( self::KEY, array() );

		if ( empty( $options ) ) {
			return false;
		}

		$empty = true;

		// Set both options but flip the arrays so we can use isset() over in_array()
		if ( ! empty( $options['locked_post_ids'] ) ) {
			self::$locked_post_ids = array_flip( $options['locked_post_ids'] );

			$empty = false;
		}

		if ( ! empty( $options['protected_post_ids'] ) ) {
			self::$protected_post_ids = array_flip( $options['protected_post_ids'] );

			$empty = false;
		}

		return !$empty;
	}
}
