<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to lock selected posts and pages so they cannot be edited or deleted by non-admin users.
 * Version: 1.0.1
 * Author: Andy Palmer
 * Author URI: http://www.andypalmer.me
 * License: GPL2
 * Text Domain: postlockdown
 */
if ( is_admin() ) {
	PostLockdown::init();
}

register_uninstall_hook( __FILE__, array( 'PostLockdown', 'uninstall' ) );

class PostLockdown {

	/** Plugin key for options and the option page. */
	const KEY = 'postlockdown';

	/** Option page title. */
	const TITLE = 'Post Lockdown';

	/** Query arg used to determine if an admin notice is displayed */
	const QUERY_ARG = 'plstatuschange';

	/** @var array List of post IDs which cannot be edited, trashed or deleted. */
	private static $locked_post_ids = array();

	/** @var array List of post IDs which cannot be trashed or deleted. */
	private static $protected_post_ids = array();

	/** @var string Page hook returned by add_options_page(). */
	private static $page_hook;

	/**
	 * Adds the required action and filter callbacks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) );
		add_action( 'admin_notices', array( __CLASS__, 'output_admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'delete_post', array( __CLASS__, 'update_option' ) );
		add_action( 'wp_ajax_pl_autocomplete', array( __CLASS__, 'ajax_autocomplete' ) );

		add_filter( 'user_has_cap', array( __CLASS__, 'filter_cap' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'prevent_status_change' ), 10, 2 );
		add_filter( 'removable_query_args', array( __CLASS__, 'remove_query_arg' ) );
		add_filter( 'option_page_capability_' . self::KEY, array( __CLASS__, 'get_admin_cap' ) );
	}

	/**
	 * Filter for the 'user_has_cap' hook.
	 *
	 * Sets the capability to false when current_user_can() has been called on
	 * one of the capabilities we're interested in on a locked or protected post.
	 */
	public static function filter_cap( $allcaps, $cap, $args ) {
		$the_caps = apply_filters( 'postlockdown_capabilities', array(
			'delete_post' => true,
			'edit_post' => true,
		) );

		// If it's not a capability we're interested in get out of here.
		if ( ! isset( $the_caps[ $args[0] ] ) ) {
			return $allcaps;
		}

		/* If there are no locked or protected posts, or the user has
		 * the required capability to bypass restrictions get out of here.
		 */
		if ( ! empty( $allcaps[ self::get_admin_cap() ] ) || ! self::load_options() ) {
			return $allcaps;
		}

		$post_id = $args[2];

		if ( ! $post_id ) {
			return $allcaps;
		}

		if ( 'edit_post' === $args[0] ) {
			$allcaps[ $cap[0] ] = ! self::is_post_locked( $post_id );
		} else {
			$allcaps[ $cap[0] ] = ! self::is_post_protected( $post_id ) && ! self::is_post_locked( $post_id );
		}

		return $allcaps;
	}

	/**
	 * Filter for the 'wp_insert_post_data' hook.
	 *
	 * Reverts any changes made by a non-admin to a published protected post's status, privacy and password.
	 * Also reverts any date changes if they're set to a future date. If anything is changed a filter for
	 * the 'redirect_post_location' hook is added to display an admin notice letting the user know we reverted it.
	 */
	public static function prevent_status_change( $data, $postarr ) {
		/* If there are no locked or protected posts, or the user has
		 * the required capability to bypass restrictions get out of here.
		 */
		if ( current_user_can( self::get_admin_cap() ) || ! self::load_options() ) {
			return $data;
		}

		$post_id = $postarr['ID'];

		/* If it's not a protected post get out of here. No need
		 * to check for locked posts because they can't be edited.
		 */
		if ( ! self::is_post_protected( $post_id ) ) {
			return $data;
		}

		$post = get_post( $post_id );

		$changed = false;

		if ( 'publish' === $post->post_status ) {
			if ( 'publish' !== $data['post_status'] ) {
				$changed = true;
				$data['post_status'] = $post->post_status;
			}

			if ( $data['post_password'] !== $post->post_password ) {
				$changed = true;
				$data['post_password'] = $post->post_password;
			}

			// Revert the post date if it's set to a future date.
			if ( $data['post_date'] !== $post->post_date && strtotime( $data['post_date'] ) > time() ) {
				$changed = true;
				$data['post_date'] = $post->post_date;
				$data['post_date_gmt'] = $post->post_date_gmt;
			}
		}

		if ( $changed ) {
			add_filter( 'redirect_post_location', array( __CLASS__, 'redirect_post_location' ) );
		}

		return $data;
	}

	/**
	 * Filter for the 'redirect_post_location' hook.
	 *
	 * Adds the plugin's query arg to the redirect URI when
	 * the status of a protected post changes to indicate that
	 * an error message should be displayed.
	 */
	public static function redirect_post_location( $location ) {
		return add_query_arg( self::QUERY_ARG, 1, $location );
	}

	/**
	 * Filter for the 'removable_query_args' hook.
	 *
	 * Adds the plugin's query arg to the array of args
	 * removed by WordPress using the JavaScript History API.
	 */
	public static function remove_query_arg( $args ) {
		$args[] = self::QUERY_ARG;

		return $args;
	}

	/**
	 * Callback for the 'admin_notices' hook.
	 *
	 * Outputs the plugin's admin notices if there are any.
	 */
	public static function output_admin_notices() {
		$notices = array();

		if ( self::filter_input( self::QUERY_ARG ) ) {

			$notices[] = array(
				'class' => 'error',
				'message' => esc_html( 'This post is protected by Post Lockdown and must stay published.', 'postlockdown' ),
			);
		}

		if ( ! empty( $notices ) ) {
			include_once( plugin_dir_path( __FILE__ ) . 'view/admin-notices.php' );
		}
	}

	/**
	 * Callback for the 'admin_init' hook.
	 *
	 * Registers the plugin's option name so it gets saved.
	 */
	public static function register_setting() {
		register_setting( self::KEY, self::KEY );
	}

	/**
	 * Callback for the 'admin_menu' hook.
	 *
	 * Adds the plugin's options page.
	 */
	public static function add_options_page() {
		self::$page_hook = add_options_page( self::TITLE, self::TITLE, self::get_admin_cap(), self::KEY, array( __CLASS__, 'output_options_page' ) );
	}

	/**
	 * Callback used by add_options_page().
	 *
	 * Gets an array of post types and their posts and includes the options page HTML.
	 */
	public static function output_options_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'view/options-page.php' );
	}

	/**
	 * Callback for the 'pl_autocomplete' AJAX action.
	 *
	 * Responds with a json encoded array of posts matching the query.
	 */
	public static function ajax_autocomplete() {
		$posts = self::get_posts( array(
			's' => self::filter_input( 'term' ),
			'offset' => self::filter_input( 'offset', 'int' ),
			'posts_per_page' => 10,
		) );

		wp_send_json_success( $posts );
	}

	/**
	 * Callback for the 'admin_enqueue_scripts' hook.
	 *
	 * Enqueues the required scripts and styles for the plugin options page.
	 */
	public static function enqueue_scripts( $hook ) {
		// If it's not the plugin options page get out of here.
		if ( $hook !== self::$page_hook ) {
			return;
		}

		$assets_path = plugin_dir_url( __FILE__ ) . 'view/assets/';

		$ext = '.min';
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$ext = '';
		}

		wp_enqueue_style( self::KEY, $assets_path . 'css/postlockdown' . $ext . '.css', null, null );

		wp_enqueue_script( 'plmultiselect', $assets_path . 'js/jquery.plmultiselect' . $ext . '.js', array( 'jquery-ui-autocomplete' ), null, true );
		wp_enqueue_script( self::KEY, $assets_path . 'js/postlockdown' . $ext . '.js', array( 'plmultiselect' ), null, true );

		$data = array();

		if ( self::load_options() ) {

			$posts = self::get_posts( array(
				'nopaging' => true,
				'post__in' => array_merge( self::$locked_post_ids, self::$protected_post_ids ),
			) );

			foreach ( $posts as $post ) {
				if ( self::is_post_locked( $post->ID ) ) {
					$data['locked'][] = $post;
				}

				if ( self::is_post_protected( $post->ID ) ) {
					$data['protected'][] = $post;
				}
			}
		}

		wp_localize_script( self::KEY, self::KEY, $data );
	}

	/**
	 * Callback for the 'delete_post' hook.
	 *
	 * Removes the deleted post's ID from both locked and protected arrays.
	 */
	public static function update_option( $post_id ) {
		if ( ! self::load_options() ) {
			return;
		}

		unset( self::$locked_post_ids[ $post_id ], self::$protected_post_ids[ $post_id ] );

		update_option( self::KEY, array(
			'locked_post_ids' => self::$locked_post_ids,
			'protected_post_ids' => self::$protected_post_ids,
		) );
	}

	/**
	 * Callback for register_uninstall_hook() function.
	 *
	 * Removes the plugin option from the database when it is uninstalled.
	 */
	public static function uninstall() {
		delete_option( self::KEY );
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
	 * Returns whether a post ID is protected.
	 * @param int $post_id
	 * @return bool
	 */
	public static function is_post_protected( $post_id ) {
		self::load_options();
		return isset( self::$protected_post_ids[ $post_id ] );
	}

	/**
	 * Returns the required capability a user must be to bypass all
	 * locked and protected post restrictions. Defaults to 'manage_options'.
	 *
	 * Also serves as a callback for the 'option_page_capability_{slug}' hook.
	 * @return string The required capability.
	 */
	public static function get_admin_cap() {
		return apply_filters( 'postlockdown_admin_capability', 'manage_options' );
	}

	/**
	 * Sets the array of locked and protected post IDs.
	 *
	 * The return value is used to bail out of functions early if
	 * there are no locked or protected posts set.
	 * @return bool Whether both arrays are empty.
	 */
	private static function load_options() {
		if ( ! empty( self::$locked_post_ids ) && ! empty( self::$protected_post_ids ) ) {
			return true;
		}

		$options = get_option( self::KEY, array() );

		if ( ! empty( $options['locked_post_ids'] ) && is_array( $options['locked_post_ids'] ) ) {
			self::$locked_post_ids = $options['locked_post_ids'];
		}

		self::$locked_post_ids = apply_filters( 'postlockdown_locked_posts', self::$locked_post_ids );

		if ( ! empty( $options['protected_post_ids'] ) && is_array( $options['protected_post_ids'] ) ) {
			self::$protected_post_ids = $options['protected_post_ids'];
		}

		self::$protected_post_ids = apply_filters( 'postlockdown_protected_posts', self::$protected_post_ids );

		return ( ! empty( self::$locked_post_ids ) || ! empty( self::$protected_post_ids ) );
	}

	/**
	 * Convenience wrapper for get_posts().
	 * @param array $args Array of args to merge with defaults passed to get_posts().
	 * @return array Array of posts.
	 */
	private static function get_posts( $args = array() ) {
		$excluded_post_types = array( 'nav_menu_item', 'revision' );

		if ( class_exists( 'WooCommerce' ) ) {
			$excluded_post_types = array_merge( $excluded_post_types, array(
				'product_variation',
				'shop_order',
				'shop_coupon',
			) );
		}

		$excluded_post_types = apply_filters( 'postlockdown_excluded_post_types', $excluded_post_types );

		$defaults = array(
			'post_type' => array_diff( get_post_types(), $excluded_post_types ),
			'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
		);

		$args = wp_parse_args( $args, $defaults );

		return get_posts( apply_filters( 'postlockdown_get_posts', $args ) );
	}

	/**
	 * Convenience wrapper for PHP's filter_input() function.
	 * @param string $key Input key.
	 * @param string $data_type Input data type.
	 * @param int $type Type of input. INPUT_POST or INPUT_GET (Default).
	 * @param int $flags Additional flags to pass to filter_input().
	 * @return mixed Filtered input.
	 */
	private static function filter_input( $key, $data_type = 'string', $type = INPUT_GET, $flags = 0 ) {
		switch ( $data_type ) {
			case 'int':
				$filter = FILTER_SANITIZE_NUMBER_INT;
				break;
			case 'float':
				$filter = FILTER_SANITIZE_NUMBER_FLOAT;
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
