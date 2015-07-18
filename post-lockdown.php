<?php
/**
 * Plugin Name: Post Lockdown
 * Description: Allows admins to lock selected posts and pages so they cannot be edited or deleted by non-admin users.
 * Version: 1.1.1
 * Author: Andy Palmer
 * Author URI: http://www.andypalmer.me
 * License: GPL2
 * Text Domain: postlockdown
 */
if ( is_admin() ) {
	PostLockdown::get_instance();
}

final class PostLockdown {

	/** Plugin key for options and the option page. */
	const KEY = 'postlockdown';

	/** Option page title. */
	const TITLE = 'Post Lockdown';

	/** Query arg used to determine if an admin notice is displayed */
	const QUERY_ARG = 'plstatuschange';

	/** @var array List of post IDs which cannot be edited, trashed or deleted. */
	private $locked_post_ids = array();

	/** @var array List of post IDs which cannot be trashed or deleted. */
	private $protected_post_ids = array();

	/** @var boolean Whether there are any locked or protected posts. */
	private $have_posts = false;

	/** @var string Page hook returned by add_options_page(). */
	private $page_hook;
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds the required action and filter callbacks.
	 */
	private function __construct() {
		$this->have_posts = $this->load_options();

		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_pl_autocomplete', array( $this, 'ajax_autocomplete' ) );

		add_filter( 'option_page_capability_' . self::KEY, array( $this, 'get_admin_cap' ) );

		if ( $this->have_posts ) {
			add_action( 'admin_notices', array( $this, 'output_admin_notices' ) );
			add_action( 'delete_post', array( $this, 'update_option' ) );

			add_filter( 'user_has_cap', array( $this, 'filter_cap' ), 10, 3 );
			add_filter( 'wp_insert_post_data', array( $this, 'prevent_status_change' ), 10, 2 );
			add_filter( 'removable_query_args', array( $this, 'remove_query_arg' ) );
		}

		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	private function __clone() {

	}

	private function __wakeup() {

	}

	/**
	 * Filter for the 'user_has_cap' hook.
	 *
	 * Sets the capability to false when current_user_can() has been called on
	 * one of the capabilities we're interested in on a locked or protected post.
	 */
	public function filter_cap( $allcaps, $cap, $args ) {
		$the_caps = apply_filters( 'postlockdown_capabilities', array(
			'delete_post' => true,
			'edit_post' => true,
		) );

		// If it's not a capability we're interested in get out of here.
		if ( ! isset( $the_caps[ $args[0] ] ) ) {
			return $allcaps;
		}

		/* If the user has the required capability to bypass
		 * restrictions get out of here.
		 */
		if ( ! empty( $allcaps[ $this->get_admin_cap() ] ) ) {
			return $allcaps;
		}

		$post_id = $args[2];

		if ( ! $post_id ) {
			return $allcaps;
		}

		if ( 'edit_post' === $args[0] ) {
			$allcaps[ $cap[0] ] = ! $this->is_post_locked( $post_id );
		} else {
			$allcaps[ $cap[0] ] = ! $this->is_post_protected( $post_id ) && ! $this->is_post_locked( $post_id );
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
	public function prevent_status_change( $data, $postarr ) {
		/* If the user has the required capability to bypass
		 * restrictions get out of here.
		 */
		if ( current_user_can( $this->get_admin_cap() ) ) {
			return $data;
		}

		$post_id = $postarr['ID'];

		/* If it's not a protected post get out of here. No need
		 * to check for locked posts because they can't be edited.
		 */
		if ( ! $this->is_post_protected( $post_id ) ) {
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
			add_filter( 'redirect_post_location', array( $this, 'redirect_post_location' ) );
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
	public function redirect_post_location( $location ) {
		return add_query_arg( self::QUERY_ARG, 1, $location );
	}

	/**
	 * Filter for the 'removable_query_args' hook.
	 *
	 * Adds the plugin's query arg to the array of args
	 * removed by WordPress using the JavaScript History API.
	 */
	public function remove_query_arg( $args ) {
		$args[] = self::QUERY_ARG;

		return $args;
	}

	/**
	 * Callback for the 'admin_notices' hook.
	 *
	 * Outputs the plugin's admin notices if there are any.
	 */
	public function output_admin_notices() {
		$notices = array();

		if ( $this->filter_input( self::QUERY_ARG ) ) {

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
	public function register_setting() {
		register_setting( self::KEY, self::KEY );
	}

	/**
	 * Callback for the 'admin_menu' hook.
	 *
	 * Adds the plugin's options page.
	 */
	public function add_options_page() {
		$this->page_hook = add_options_page( self::TITLE, self::TITLE, $this->get_admin_cap(), self::KEY, array( $this, 'output_options_page' ) );
	}

	/**
	 * Callback used by add_options_page().
	 *
	 * Gets an array of post types and their posts and includes the options page HTML.
	 */
	public function output_options_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'view/options-page.php' );
	}

	/**
	 * Callback for the 'pl_autocomplete' AJAX action.
	 *
	 * Responds with a json encoded array of posts matching the query.
	 */
	public function ajax_autocomplete() {
		$posts = $this->get_posts( array(
			's' => $this->filter_input( 'term' ),
			'offset' => $this->filter_input( 'offset', 'int' ),
			'posts_per_page' => 10,
		) );

		wp_send_json_success( $posts );
	}

	/**
	 * Callback for the 'admin_enqueue_scripts' hook.
	 *
	 * Enqueues the required scripts and styles for the plugin options page.
	 */
	public function enqueue_scripts( $hook ) {
		// If it's not the plugin options page get out of here.
		if ( $hook !== $this->page_hook ) {
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

		if ( $this->have_posts ) {

			$posts = $this->get_posts( array(
				'nopaging' => true,
				'post__in' => array_merge( $this->locked_post_ids, $this->protected_post_ids ),
			) );

			foreach ( $posts as $post ) {
				if ( $this->is_post_locked( $post->ID ) ) {
					$data['locked'][] = $post;
				}

				if ( $this->is_post_protected( $post->ID ) ) {
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
	public function update_option( $post_id ) {
		unset( $this->locked_post_ids[ $post_id ], $this->protected_post_ids[ $post_id ] );

		update_option( self::KEY, array(
			'locked_post_ids' => $this->locked_post_ids,
			'protected_post_ids' => $this->protected_post_ids,
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
	public function is_post_locked( $post_id ) {
		return isset( $this->locked_post_ids[ $post_id ] );
	}

	/**
	 * Returns whether a post ID is protected.
	 * @param int $post_id
	 * @return bool
	 */
	public function is_post_protected( $post_id ) {
		return isset( $this->protected_post_ids[ $post_id ] );
	}

	/**
	 * Returns the required capability a user must be to bypass all
	 * locked and protected post restrictions. Defaults to 'manage_options'.
	 *
	 * Also serves as a callback for the 'option_page_capability_{slug}' hook.
	 * @return string The required capability.
	 */
	public function get_admin_cap() {
		return apply_filters( 'postlockdown_admin_capability', 'manage_options' );
	}

	/**
	 * Sets the array of locked and protected post IDs.
	 *
	 * The return value is used to bail out of functions early if
	 * there are no locked or protected posts set.
	 * @return bool Whether both arrays are empty.
	 */
	private function load_options() {
		$options = get_option( self::KEY, array() );

		if ( ! empty( $options['locked_post_ids'] ) && is_array( $options['locked_post_ids'] ) ) {
			$this->locked_post_ids = $options['locked_post_ids'];
		}

		$this->locked_post_ids = apply_filters( 'postlockdown_locked_posts', $this->locked_post_ids );

		if ( ! empty( $options['protected_post_ids'] ) && is_array( $options['protected_post_ids'] ) ) {
			$this->protected_post_ids = $options['protected_post_ids'];
		}

		$this->protected_post_ids = apply_filters( 'postlockdown_protected_posts', $this->protected_post_ids );

		$have_posts = ( ! empty( $this->locked_post_ids ) || ! empty( $this->protected_post_ids ) );

		return $have_posts;
	}

	/**
	 * Convenience wrapper for get_posts().
	 * @param array $args Array of args to merge with defaults passed to get_posts().
	 * @return array Array of posts.
	 */
	private function get_posts( $args = array() ) {
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
	private function filter_input( $key, $data_type = 'string', $type = INPUT_GET, $flags = 0 ) {
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
