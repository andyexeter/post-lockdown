<?php

class PostLockdown {
	/** Plugin key for options and the option page. */
	const KEY = 'postlockdown';
	const VERSION = '2.0';

	/** @var array List of post IDs which cannot be edited, trashed or deleted. */
	private $locked_post_ids = array();

	/** @var array List of post IDs which cannot be trashed or deleted. */
	private $protected_post_ids = array();

	public $plugin_path;
	public $plugin_url;
	public $db_version;

	public $registry = array();

	public function __construct( $plugin_path, $plugin_url ) {
		$this->plugin_path = $plugin_path;
		$this->plugin_url = $plugin_url;

		$this->setup_registry();
		$this->load_options();

		add_action( 'delete_post', array( $this, '_update_option' ) );
		add_filter( 'user_has_cap', array( $this, '_filter_cap' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( $this, '_prevent_status_change' ), 10, 2 );
	}

	/**
	 * Returns an array of locked post IDs.
	 *
	 * @param bool $suppress_filters Whether to suppress filters and only return IDs
	 *                               selected on the Post Lockdown options page.
	 * @return array
	 */
	public function get_locked_post_ids( $suppress_filters = false ) {
		if ( $suppress_filters ) {
			return $this->locked_post_ids;
		}

		return apply_filters( 'postlockdown_locked_posts', $this->locked_post_ids );
	}

	/**
	 * Returns an array of protected post IDs.
	 *
	 * @param bool $suppress_filters Whether to suppress filters and only return IDs
	 *                               selected on the Post Lockdown options page.
	 * @return array
	 */
	public function get_protected_post_ids( $suppress_filters = false ) {
		if ( $suppress_filters ) {
			return $this->protected_post_ids;
		}

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
	 * @param int  $post_id The ID of the post to check.
	 * @param bool $suppress_filters
	 * @return bool
	 */
	public function is_post_locked( $post_id, $suppress_filters = false ) {
		if ( $suppress_filters ) {
			return isset( $this->locked_post_ids[ $post_id ] );
		}

		$locked_post_ids = $this->get_locked_post_ids();

		return isset( $locked_post_ids[ $post_id ] );
	}

	/**
	 * Returns whether a post is protected.
	 *
	 * @param int  $post_id The ID of the post to check.
	 * @param bool $suppress_filters
	 * @return bool
	 */
	public function is_post_protected( $post_id, $suppress_filters = false ) {
		if ( $suppress_filters ) {
			return isset( $this->protected_post_ids[ $post_id ] );
		}

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
	public function get_admin_cap() {
		return apply_filters( 'postlockdown_admin_capability', 'manage_options' );
	}

	/**
	 * Filter for the 'user_has_cap' hook.
	 *
	 * Sets the capability to false when current_user_can() has been called on
	 * one of the capabilities we're interested in on a locked or protected post.
	 * @param array $allcaps All capabilities of the user.
	 * @param array $cap     [0] Required capability.
	 * @param array $args    [0] Requested capability.
	 *                       [1] User ID.
	 *                       [2] Post ID.
	 * @return array
	 */
	public function _filter_cap( $allcaps, $cap, $args ) {
		/* If there are no locked or protected posts, or the user
		 * has the required capability to bypass restrictions get out of here.
		 */
		if ( ! $this->have_posts() || ! empty( $allcaps[ $this->get_admin_cap() ] ) ) {
			return $allcaps;
		}

		$the_caps = apply_filters( 'postlockdown_capabilities', array(
			'delete_post' => true,
			'edit_post'   => true,
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
	 * @param array $data    Sanitized post data.
	 * @param array $postarr Raw post data. Contains post ID.
	 * @return array
	 */
	public function _prevent_status_change( $data, $postarr ) {
		$post_id = $postarr['ID'];
		$post = get_post( $post_id );

		/* If the user has the required capability to bypass
		 * restrictions or there are no protected posts get out of here.
		 */
		if ( current_user_can( $this->get_admin_cap() ) || 'publish' !== $post->post_status || ! $this->is_post_protected( $post_id ) ) {
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
			add_filter( 'redirect_post_location', array( 'PostLockdown_AdminNotice', '_add_query_arg' ) );
		}

		return $data;
	}

	/**
	 * Callback for the 'delete_post' hook.
	 *
	 * Removes the deleted post's ID from both locked and protected arrays.
	 * @param int $post_id Deleted post's ID.
	 */
	public function _update_option( $post_id ) {
		unset( $this->locked_post_ids[ $post_id ], $this->protected_post_ids[ $post_id ] );

		update_option( self::KEY, array(
			'locked_post_ids'    => $this->locked_post_ids,
			'protected_post_ids' => $this->protected_post_ids,
		) );
	}

	/**
	 * Callback for register_uninstall_hook() function.
	 *
	 * Removes the plugin option from the database when it is uninstalled.
	 * @access private
	 */
	public static function _uninstall() {
		delete_option( self::KEY );
	}

	/**
	 *
	 */
	private function setup_registry() {
		$this->registry = array(
			'Update'       => new PostLockdown_Update( $this->plugin_path ),
			'AdminNotice'  => new PostLockdown_AdminNotice( $this->plugin_path ),
			'OptionsPage'  => new PostLockdown_OptionsPage( $this ),
			'StatusColumn' => new PostLockdown_StatusColumn(),
		);
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
}
