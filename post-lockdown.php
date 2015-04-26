<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to prevent certain posts of any post type from being deleted or edited by non-admins.
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

	/** Capability required to edit the plugin options. */
	const CAP = 'manage_options';
	/** Plugin key for options and the option page. */
	const KEY = 'post_lockdown';
	/** Option page title. */
	const TITLE = 'Post Lockdown';

	/** @var array List of post IDs which cannot be edited, trashed or deleted. */
	private static $locked_post_ids = array();
	/** @var array List of post IDs which cannot be trashed or deleted. */
	private static $protected_post_ids = array();
	/** @var array List of capabilities to compare against in our capability filter. */
	private static $caps = array();

	/**
	 * Plugin init method.
	 * Gets the plugin options and adds the required action and filter callbacks.
	 */
	public static function init() {

		$options = get_option( self::KEY, array() );

		if ( ! empty( $options ) ) {

			// Set both options but flip the arrays so we can use isset() over in_array()
			if ( ! empty( $options['locked_post_ids'] ) ) {
				self::$locked_post_ids = array_flip( $options['locked_post_ids'] );
			}

			if ( ! empty( $options['protected_post_ids'] ) ) {
				self::$protected_post_ids = array_flip( $options['protected_post_ids'] );
			}

			// Set the capabilities we want to return false for our posts
			self::$caps = array(
				'delete_post' => true,
				'edit_post' => true,
				'publish_pages' => true,
				'publish_posts' => true,
			);

			add_filter( 'user_has_cap', array( __CLASS__, 'filter_cap' ), 10, 3 );
		}

		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) );
	}

	/**
	 * Callback for the 'user_has_cap' hook.
	 * Sets the capability to false when {@link https://codex.wordpress.org/Function_Reference/current_user_can current_user_can()} has been called on
	 * one of our {@link PostLockdown::$caps} on a locked post. If the user has the
	 * {@link PostLockdown::CAP} capability we bail out early.
	 */
	public static function filter_cap($allcaps, $cap, $args) {

		if ( ! isset( self::$caps[ $args[0] ] ) || ! empty( $allcaps[ self::CAP ] ) ) {
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
		add_options_page( self::TITLE, self::TITLE, self::CAP, self::KEY, array( __CLASS__, 'output_options_page' ) );
	}

	/**
	 * Callback used by add_options_page().
	 * Gets an array of post types and their posts and includes the options page HTML.
	 */
	public static function output_options_page() {

		$post_types = array();

		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {

			if ( $post_type->name == 'nav_menu_item' ) {
				continue;
			}

			$posts = get_posts( array(
				'post_type'		=> $post_type->name,
				'post_status'	=> array( 'publish', 'pending', 'draft', 'future' ),
				'nopaging'		=> true,
			) );

			if ( empty( $posts ) ) {
				continue;
			}

			$post_types[ $post_type->name ] = array( 'label' => $post_type->label, 'posts' => array() );

			foreach ( $posts as $post ) {

				$post_types[ $post_type->name ]['posts'][] = array(
					'ID'			=> $post->ID,
					'post_title'	=> $post->post_title,
					'locked'		=> isset( self::$locked_post_ids[ $post->ID ] ),
					'protected'		=> isset( self::$protected_post_ids[ $post->ID ] ),
				);
			}
		}

		include_once( plugin_dir_path( __FILE__ ) . 'options-page.php' );
	}

	/**
	 * Callback for register_uninstall_hook() function.
	 * Removes the plugin option from the database when it is uninstalled.
	 */
	public static function uninstall() {
		delete_option( self::KEY );
	}
}
