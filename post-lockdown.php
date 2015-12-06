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
	add_action( 'init', array( 'PostLockdown', 'get_instance' ), 99 );
}

class PostLockdown {

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

	/** @var string Page hook returned by add_options_page(). */
	private $page_hook;

	/** @var object Reference to the unique instance of the class. */
	private static $instance;

	/**
	 * Returns a single instance of the PostLockdown class.
	 *
	 * @return PostLockdown object instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->load_options();

		add_action( 'admin_init', array( $this, '_register_setting' ) );

		add_action( 'admin_menu', array( $this, '_add_options_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_scripts' ) );
		add_action( 'wp_ajax_pl_autocomplete', array( $this, '_ajax_autocomplete' ) );

		add_filter( 'option_page_capability_' . self::KEY, array( $this, '_get_admin_cap' ) );

		add_action( 'admin_notices', array( $this, '_output_admin_notices' ) );
		add_action( 'delete_post', array( $this, '_update_option' ) );

		add_filter( 'user_has_cap', array( $this, '_filter_cap' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( $this, '_prevent_status_change' ), 10, 2 );
		add_filter( 'removable_query_args', array( $this, '_remove_query_arg' ) );

		register_uninstall_hook( __FILE__, array( __CLASS__, '_uninstall' ) );
	}

	public function get_locked_post_ids() {
		return apply_filters( 'postlockdown_locked_posts', $this->locked_post_ids );
	}

	public function get_protected_post_ids() {
		return apply_filters( 'postlockdown_protected_posts', $this->protected_post_ids );
	}

	/**
	 * Returns whether there are any locked or protected posts set.
	 *
	 * @return bool
	 */
	public function have_posts() {
		return (bool) ( $this->get_locked_post_ids() || $this->get_protected_post_ids() );
	}

	/**
	 * Returns whether a post is locked.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return bool
	 */
	public function is_post_locked( $post_id ) {
		$locked_post_ids = $this->get_locked_post_ids();

		return isset( $locked_post_ids[ $post_id ] );
	}

	/**
	 * Returns whether a post is protected.
	 *
	 * @param int $post_id The ID of the post to check.
	 * @return bool
	 */
	public function is_post_protected( $post_id ) {
		$protected_post_ids = $this->get_protected_post_ids();

		return isset( $protected_post_ids[ $post_id ] );
	}

	/**
	 * Returns the required capability a user must have to bypass all
	 * locked and protected post restrictions. Defaults to 'manage_options'.
	 *
	 * Also serves as a callback for the 'option_page_capability_{slug}' hook.
	 *
	 * @return string The required capability.
	 */
	public function _get_admin_cap() {
		return apply_filters( 'postlockdown_admin_capability', 'manage_options' );
	}

	/**
	 * Filter for the 'user_has_cap' hook.
	 *
	 * Sets the capability to false when current_user_can() has been called on
	 * one of the capabilities we're interested in on a locked or protected post.
	 */
	public function _filter_cap( $allcaps, $cap, $args ) {
		/* If there are no locked or protected posts, or the user
		 * has the required capability to bypass restrictions get out of here.
		 */
		if ( ! $this->have_posts() || ! empty( $allcaps[ $this->_get_admin_cap() ] ) ) {
			return $allcaps;
		}

		$the_caps = apply_filters( 'postlockdown_capabilities', array(
			'delete_post' => true,
			'edit_post' => true,
		) );

		// If it's not a capability we're interested in get out of here.
		if ( ! isset( $the_caps[ $args[0] ] ) ) {
			return $allcaps;
		}

		$post_id = $args[2];

		if ( ! $post_id ) {
			return $allcaps;
		}

		// If the post is locked set the capability to false.
		$has_cap = ! $this->is_post_locked( $post_id );

		/* If the user still has the capability and we're not editing a post,
		 * set the capability to false if the post is protected.
		 */
		if ( $has_cap && 'edit_post' !== $args[0] ) {
			$has_cap = ! $this->is_post_protected( $post_id );
		}

		$allcaps[ $cap[0] ] = $has_cap;

		return $allcaps;
	}

	/**
	 * Filter for the 'wp_insert_post_data' hook.
	 *
	 * Reverts any changes made by a non-admin to a published protected post's status, privacy and password.
	 * Also reverts any date changes if they're set to a future date. If anything is changed a filter for
	 * the 'redirect_post_location' hook is added to display an admin notice letting the user know we reverted it.
	 */
	public function _prevent_status_change( $data, $postarr ) {
		/* If the user has the required capability to bypass
		 * restrictions or there are no locked or protected posts get out of here.
		 */
		if ( current_user_can( $this->_get_admin_cap() ) || ! $this->have_posts() ) {
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

		/* If the post is not published we don't need to revert
		 * anything so get out of here.
		 */
		if ( 'publish' !== $post->post_status ) {
			return $data;
		}

		$changed = false;

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

		if ( $changed ) {
			add_filter( 'redirect_post_location', array( $this, '_redirect_post_location' ) );
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
	public function _redirect_post_location( $location ) {
		return add_query_arg( self::QUERY_ARG, 1, $location );
	}

	/**
	 * Filter for the 'removable_query_args' hook.
	 *
	 * Adds the plugin's query arg to the array of args
	 * removed by WordPress using the JavaScript History API.
	 */
	public function _remove_query_arg( $args ) {
		$args[] = self::QUERY_ARG;

		return $args;
	}

	/**
	 * Callback for the 'admin_notices' hook.
	 *
	 * Outputs the plugin's admin notices if there are any.
	 */
	public function _output_admin_notices() {
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
	public function _register_setting() {
		register_setting( self::KEY, self::KEY );
	}

	/**
	 * Callback for the 'admin_menu' hook.
	 *
	 * Adds the plugin's options page.
	 */
	public function _add_options_page() {
		$this->page_hook = add_options_page( self::TITLE, self::TITLE, $this->_get_admin_cap(), self::KEY, array( $this, '_output_options_page' ) );
	}

	/**
	 * Callback used by add_options_page().
	 *
	 * Gets an array of post types and their posts and includes the options page HTML.
	 */
	public function _output_options_page() {
		$blocks = array();

		$blocks[] = array(
			'key' => 'locked',
			'heading' => __( 'Locked Posts', 'postlockdown' ),
			'input_name' => 'locked_post_ids',
			'description' => __( 'Locked posts cannot be edited, trashed or deleted by non-admins', 'postlockdown' ),
		);

		$blocks[] = array(
			'key' => 'protected',
			'heading' => __( 'Protected Posts', 'postlockdown' ),
			'input_name' => 'protected_post_ids',
			'description' => __( 'Protected posts cannot be trashed or deleted by non-admins', 'postlockdown' ),
		);

		include_once( plugin_dir_path( __FILE__ ) . 'view/options-page.php' );
	}

	/**
	 * Callback for the 'pl_autocomplete' AJAX action.
	 *
	 * Responds with a json encoded array of posts matching the query.
	 */
	public function _ajax_autocomplete() {
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
	public function _enqueue_scripts( $hook ) {
		// If it's not the plugin options page get out of here.
		if ( $hook !== $this->page_hook ) {
			return;
		}

		$assets_path = plugin_dir_url( __FILE__ ) . 'view/assets/';

		$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( self::KEY, $assets_path . 'css/postlockdown' . $ext . '.css', null, null );

		wp_enqueue_script( 'plmultiselect', $assets_path . 'js/jquery.plmultiselect' . $ext . '.js', array( 'jquery-ui-autocomplete' ), null, true );
		wp_enqueue_script( self::KEY, $assets_path . 'js/postlockdown' . $ext . '.js', array( 'plmultiselect' ), null, true );

		$data = array();

		if ( $this->have_posts() ) {
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
	public function _update_option( $post_id ) {
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
	public static function _uninstall() {
		delete_option( self::KEY );
	}

	/**
	 * Sets the arrays of locked and protected post IDs.
	 *
	 */
	private function load_options() {
		$options = get_option( self::KEY, array() );

		if ( ! empty( $options['locked_post_ids'] ) && is_array( $options['locked_post_ids'] ) ) {
			$this->locked_post_ids = $options['locked_post_ids'];
		}

		if ( ! empty( $options['protected_post_ids'] ) && is_array( $options['protected_post_ids'] ) ) {
			$this->protected_post_ids = $options['protected_post_ids'];
		}
	}

	/**
	 * Convenience wrapper for get_posts().
	 *
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
	 *
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
