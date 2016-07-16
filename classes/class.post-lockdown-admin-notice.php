<?php

class PostLockdown_AdminNotice {
	/** Query arg used to determine if an admin notice should be displayed. */
	const QUERY_ARG = 'plstatuschange';

	private $plugin_path;

	public function __construct( $plugin_path ) {
		$this->plugin_path = $plugin_path;
		add_action( 'admin_notices', array( $this, '_output_admin_notices' ) );
		add_filter( 'removable_query_args', array( $this, '_remove_query_arg' ) );
	}

	/**
	 * Filter for the 'redirect_post_location' hook.
	 * @see PostLockdown::_prevent_status_change()
	 *
	 * @param string $location
	 * @return string
	 */
	public function _add_query_arg( $location ) {
		return add_query_arg( self::QUERY_ARG, 1, $location );
	}

	/**
	 * Filter for the 'removable_query_args' hook.
	 *
	 * Adds the plugin's query arg to the array of args
	 * removed by WordPress using the JavaScript History API.
	 * @param array $args Array of query args to be removed.
	 * @return array
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

		if ( ! empty( $_GET[ self::QUERY_ARG ] ) ) {
			$notices[] = array(
				'class'   => 'error',
				'message' => esc_html( 'This post is protected by Post Lockdown and must stay published.' ),
			);
		}

		if ( ! empty( $notices ) ) {
			include_once( $this->plugin_path . 'view/admin-notices.php' );
		}
	}
}
