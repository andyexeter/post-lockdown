<?php

class PostLockdown_OptionsPage {
	const PAGE_TITLE = 'Post Lockdown';
	/** @var string Page hook returned by add_options_page(). */
	private $page_hook;
	/** @var  PostLockdown */
	private $post_lockdown;

	public function __construct( $postlockdown ) {
		$this->post_lockdown = $postlockdown;

		add_action( 'admin_init', array( $this, '_register_setting' ) );
		add_action( 'admin_menu', array( $this, '_add_options_page' ) );

		add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_scripts' ) );
		add_action( 'wp_ajax_pl_autocomplete', array( $this, '_ajax_autocomplete' ) );

		add_filter( 'option_page_capability_' . PostLockdown::KEY, array( $this->post_lockdown, 'get_admin_cap' ) );
	}

	/**
	 * Callback for the 'admin_init' hook.
	 *
	 * Registers the plugin's option name so it gets saved.
	 */
	public function _register_setting() {
		register_setting( PostLockdown::KEY, PostLockdown::KEY );
	}

	/**
	 * Callback for the 'admin_menu' hook.
	 *
	 * Adds the plugin's options page.
	 */
	public function _add_options_page() {
		$this->page_hook = add_options_page(
			self::PAGE_TITLE,
			self::PAGE_TITLE,
			$this->post_lockdown->get_admin_cap(),
			PostLockdown::KEY,
			array( $this, '_output_options_page' )
		);
	}

	/**
	 * Callback used by add_options_page().
	 *
	 * Gets an array of post types and their posts and includes the options page HTML.
	 */
	public function _output_options_page() {
		$blocks = array();

		$blocks[] = array(
			'key'         => 'locked',
			'heading'     => __( 'Locked Posts', 'postlockdown' ),
			'input_name'  => 'locked_post_ids',
			'description' => __( 'Locked posts cannot be edited, trashed or deleted by non-admins', 'postlockdown' ),
		);

		$blocks[] = array(
			'key'         => 'protected',
			'heading'     => __( 'Protected Posts', 'postlockdown' ),
			'input_name'  => 'protected_post_ids',
			'description' => __( 'Protected posts cannot be trashed or deleted by non-admins', 'postlockdown' ),
		);

		include_once( $this->post_lockdown->plugin_path . 'view/options-page.php' );
	}

	/**
	 * Callback for the 'pl_autocomplete' AJAX action.
	 *
	 * Responds with a json encoded array of posts matching the query.
	 */
	public function _ajax_autocomplete() {
		$posts = $this->get_posts( array(
			's'              => $_REQUEST['term'],
			'offset'         => (int) $_REQUEST['offset'],
			'posts_per_page' => 10,
		) );

		wp_send_json_success( $posts );
	}

	/**
	 * Callback for the 'admin_enqueue_scripts' hook.
	 *
	 * Enqueues the required scripts and styles for the plugin options page.
	 * @param string $hook The current admin screen.
	 */
	public function _enqueue_scripts( $hook ) {
		// If it's not the plugin options page get out of here.
		if ( $hook !== $this->page_hook ) {
			return;
		}

		$assets_path = $this->post_lockdown->plugin_url . 'view/assets/';

		$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( PostLockdown::KEY, $assets_path . 'css/postlockdown' . $ext . '.css', null, null );

		wp_enqueue_script( 'plmultiselect', $assets_path . 'js/jquery.plmultiselect' . $ext . '.js', array( 'jquery-ui-autocomplete' ), null, true );
		wp_enqueue_script( PostLockdown::KEY, $assets_path . 'js/postlockdown' . $ext . '.js', array( 'plmultiselect' ), null, true );

		$data = array();

		if ( $this->post_lockdown->have_posts() ) {
			$posts = $this->get_posts( array(
				'nopaging' => true,
				'post__in' => array_merge(
					$this->post_lockdown->get_locked_post_ids( true ),
					$this->post_lockdown->get_protected_post_ids( true )
				),
			) );

			foreach ( $posts as $post ) {
				if ( $this->post_lockdown->is_post_locked( $post->ID ) ) {
					$data['locked'][] = $post;
				}

				if ( $this->post_lockdown->is_post_protected( $post->ID ) ) {
					$data['protected'][] = $post;
				}
			}
		}

		wp_localize_script( PostLockdown::KEY, PostLockdown::KEY, $data );
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
			'post_type'   => array_diff( get_post_types(), $excluded_post_types ),
			'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$args = apply_filters( 'postlockdown_get_posts', $args );

		return get_posts( $args );
	}
}
