<?php

/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to lock selected posts and pages so they cannot be edited or deleted by non-admin users.
 * Version: 0.9.8
 * Author: Andy Palmer
 * Author URI: http://www.andypalmer.me
 * License: GPL2
 */
if ( is_admin() ) {
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

	/** @var string Page hook returned by add_options_page() */
	private static $page_hook;

	/**
	 * Plugin init method.
	 * Gets the plugin options and adds the required action and filter callbacks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'delete_post', array( __CLASS__, 'update_option' ) );
		add_action( 'wp_ajax_pl_autocomplete', array( __CLASS__, 'ajax_autocomplete' ) );

		add_filter( 'user_has_cap', array( __CLASS__, 'filter_cap' ), 10, 3 );
		add_filter( 'option_page_capability_' . self::KEY, array( __CLASS__, 'option_page_cap' ) );

		//add_filter( 'the_title', array( __CLASS__, 'the_title' ), 10, 2 );
	}

	public static function the_title( $title, $post_id ) {
		/** @todo Filter get_post_class function to get padlock showing on locked posts */
		if ( ! self::load_options() ) {
			return $title;
		}

		if ( isset( self::$locked_post_ids[ $post_id ] ) ) {
			$title = '<span class="dashicons dashicons-locks"></span> ' . $title;
		}

		return $title;
	}

	/**
	 * Callback for the 'user_has_cap' hook.
	 * Sets the capability to false when {@link https://codex.wordpress.org/Function_Reference/current_user_can current_user_can()} has been called on
	 * one of our {@link PostLockdown::$caps} on a locked post. If the user has the
	 * {@link PostLockdown::CAP} capability we bail out early.
	 */
	public static function filter_cap( $allcaps, $cap, $args ) {
		// If there's no locked or protected posts get out of here
		if ( ! self::load_options() ) {
			return $allcaps;
		}

		$admin_cap = apply_filters( 'postlockdown_admin_capability', 'manage_options' );

		// Set the capabilities we want to return false for our posts
		$the_caps = apply_filters( 'postlockdown_capabilities', array(
			'delete_post'	 => true,
			'edit_post'		 => true,
			'publish_pages'	 => true,
			'publish_posts'	 => true,
		) );

		if ( ! isset( $the_caps[ $args[ 0 ] ] ) || ! empty( $allcaps[ $admin_cap ] ) ) {
			return $allcaps;
		}

		if ( isset( $args[ 2 ] ) ) {
			$post_id = $args[ 2 ];
		} else {
			$post = get_post();

			if ( isset( $post->ID ) ) {
				$post_id = $post->ID;
			} else {
				$post_id = self::filter_input( 'post_ID', 'int', INPUT_POST );
			}
		}

		if ( ! $post_id ) {
			return $allcaps;
		}

		// If we get to this point and the post ID is a locked post,
		// or a protected post for anything except 'edit_posts', then
		// set the requested capability to false.

		$post_ids = self::$locked_post_ids;

		if ( 'edit_post' != $args[ 0 ] ) {
			$post_ids += self::$protected_post_ids;
		}

		if ( isset( $post_ids[ $post_id ] ) ) {
			$allcaps[ $cap[ 0 ] ] = false;
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

		self::$page_hook = add_options_page( self::TITLE, self::TITLE, $admin_cap, self::KEY, array( __CLASS__, 'output_options_page' ) );
	}

	public static function option_page_cap() {
		return apply_filters( 'postlockdown_admin_capability', 'manage_options' );
	}

	/**
	 * Callback used by add_options_page().
	 * Gets an array of post types and their posts and includes the options page HTML.
	 */
	public static function output_options_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'view/options-page.php' );
	}

	public static function ajax_autocomplete() {
		$query = self::filter_input( 'term' );

		$offset = self::filter_input( 'offset', 'int' );

		$posts = get_posts( array(
			'post_type'		 => 'any',
			'post_status'	 => array( 'publish', 'pending', 'draft', 'future' ),
			's'				 => $query,
			'offset'		 => $offset,
			'posts_per_page' => 10,
		) );

		wp_send_json_success( $posts );
	}

	public static function enqueue_scripts( $hook ) {
		if ( $hook !== self::$page_hook ) {
			return;
		}

		$assets_path = plugin_dir_url( __FILE__ ) . 'view/assets/';

		$ext = '';
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			$ext = '.min';
		}

		wp_enqueue_style( 'postlockdown', $assets_path . "css/postlockdown{$ext}.css", null, null );

		wp_enqueue_script( 'pl-multiselect', $assets_path . "js/jquery.plmultiselect{$ext}.js", array( 'jquery-ui-autocomplete' ), null, true );
		wp_enqueue_script( 'postlockdown', $assets_path . "js/postlockdown{$ext}.js", array( 'pl-multiselect' ), null, true );

		$data = array();

		if ( self::load_options() ) {

			$posts = get_posts( apply_filters( 'postlockdown_get_posts', array(
				'post_type'		 => 'any',
				'post_status'	 => array( 'publish', 'pending', 'draft', 'future' ),
				'nopaging'		 => true,
				'post__in'		 => array_merge( array_keys( self::$locked_post_ids ), array_keys( self::$protected_post_ids ) )
			) ) );

			foreach ( $posts as $post ) {
				if ( self::is_post_locked( $post->ID ) ) {
					$data[ 'locked' ][] = $post;
				}

				if ( self::is_post_protected( $post->ID ) ) {
					$data[ 'protected' ][] = $post;
				}
			}
		}

		wp_localize_script( 'postlockdown', 'postlockdown', $data );
	}

	/**
	 * Callback for the 'delete_post' hook.
	 * Removes the deleted post's ID from both locked and protected arrays
	 */
	public static function update_option( $post_id ) {
		if ( ! self::load_options() ) {
			return;
		}

		unset( self::$locked_post_ids[ $post_id ] );
		unset( self::$protected_post_ids[ $post_id ] );

		update_option( self::KEY, array( 'locked_post_ids' => self::$locked_post_ids, 'protected_post_ids' => self::$protected_post_ids ) );
	}

	/**
	 * Returns whether a post ID is locked.
	 * @param int $post_id
	 * @return bool
	 */
	public static function is_post_locked( $post_id ) {
		self::load_options();
		return isset( self::$locked_post_ids[ $post_id ] );
	}

	/**
	 * Returns whether a post ID is protected
	 * @param int $post_id
	 * @return bool
	 */
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
	 * @return bool Whether both arrays are empty
	 */
	private static function load_options() {
		if ( ! empty( self::$locked_post_ids ) && ! empty( self::$protected_post_ids ) ) {
			return true;
		}

		$options = get_option( self::KEY, array() );

		if ( empty( $options ) ) {
			return false;
		}

		$empty = true;

		// Set both options but flip the arrays so we can use isset() over in_array()
		if ( ! empty( $options[ 'locked_post_ids' ] ) && is_array( $options[ 'locked_post_ids' ] ) ) {
			self::$locked_post_ids = array_flip( $options[ 'locked_post_ids' ] );

			$empty = false;
		}

		if ( ! empty( $options[ 'protected_post_ids' ] ) && is_array( $options[ 'protected_post_ids' ] ) ) {
			self::$protected_post_ids = array_flip( $options[ 'protected_post_ids' ] );

			$empty = false;
		}

		return ! $empty;
	}

	private static function filter_input( $key, $data_type = 'string', $type = INPUT_GET, $flags = 0 ) {
		switch ( $data_type ) {
			case 'int':
				$filter	 = FILTER_SANITIZE_NUMBER_INT;
				break;
			case 'float':
				$filter	 = FILTER_SANITIZE_NUMBER_FLOAT;

				$flags |= FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND;
				break;
			default:
				$filter = FILTER_SANITIZE_STRING;
				$flags |= FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW;
				break;
		}

		return filter_input( $type, $key, $filter, $flags );
	}

}
