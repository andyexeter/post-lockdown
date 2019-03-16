<?php

class PostLockdown_OptionsPage {
	const PAGE_TITLE = 'Post Lockdown';
	/** @var string Page hook returned by add_options_page(). */
	private $page_hook;
	/** @var  PostLockdown */
	private $postlockdown;

	public function __construct( PostLockdown $postlockdown ) {
		$this->postlockdown = $postlockdown;

		add_action( 'admin_init', array( $this, '_register_setting' ) );
		add_action( 'admin_menu', array( $this, '_add_options_page' ) );

		add_action( 'admin_enqueue_scripts', array( $this, '_enqueue_scripts' ) );
		add_action( 'wp_ajax_pl_autocomplete', array( $this, '_ajax_autocomplete' ) );

		add_filter( 'option_page_capability_' . PostLockdown::KEY, array( $this->postlockdown, 'get_admin_cap' ) );

		add_filter( 'admin_footer_text', array( $this, '_filter_admin_footer_text' ) );
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
			$this->postlockdown->get_admin_cap(),
			PostLockdown::KEY,
			array( $this, '_output_options_page' )
		);
	}

	/**
	 * Callback used by add_options_page().
	 *
	 * Outputs the options page HTML.
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

		include_once( $this->postlockdown->plugin_path . 'view/options-page.php' );
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
	 *
	 * @param string $hook The current admin screen.
	 */
	public function _enqueue_scripts( $hook ) {
		// If it's not the plugin options page get out of here.
		if ( $hook !== $this->page_hook ) {
			return;
		}

		$assets_path = $this->postlockdown->plugin_url . 'view/assets/';
		$extension   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( PostLockdown::KEY, $assets_path . 'css/postlockdown' . $extension . '.css', null, null );
		wp_enqueue_script( PostLockdown::KEY, $assets_path . 'js/postlockdown' . $extension . '.js', array( 'jquery-ui-autocomplete' ), null, true );

		$posts = $this->get_posts( array(
			'nopaging' => true,
			'post__in' => array_merge(
				$this->postlockdown->get_locked_post_ids( true ),
				$this->postlockdown->get_protected_post_ids( true )
			),
		) );

		$data = array();

		foreach ( $posts as $post ) {
			if ( $this->postlockdown->is_post_locked( $post->ID ) ) {
				$data['locked'][] = $post;
			}

			if ( $this->postlockdown->is_post_protected( $post->ID ) ) {
				$data['protected'][] = $post;
			}
		}

		wp_localize_script( PostLockdown::KEY, PostLockdown::KEY, $data );
	}

	/**
	 * Filter for the 'admin_footer_text' hook.
	 * Changes the footer message on the plugin options page.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function _filter_admin_footer_text( $html ) {
		$screen = get_current_screen();

		if ( $screen->id !== $this->page_hook ) {
			return $html;
		}

		$text = sprintf( __( 'Thank you for using Post Lockdown. If you like it, please consider <a href="%s" target="_blank">leaving a review.</a>' ), __( 'https://wordpress.org/support/view/plugin-reviews/post-lockdown?rate=5#postform' ) );

		$html = '<span id="footer-thankyou">' . $text . '</span>';

		return $html;
	}

	/**
	 * Convenience wrapper for get_posts().
	 *
	 * @param array $args Array of args to merge with defaults passed to get_posts().
	 *
	 * @return WP_Post[] Array of post objects.
	 */
	private function get_posts( $args = array() ) {
		$defaults = array(
			'post_type'   => $this->postlockdown->get_post_types(),
			'post_status' => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$args = apply_filters( 'postlockdown_get_posts', $args );

		$query = new WP_Query( $args );

		return $query->posts;
	}
}
