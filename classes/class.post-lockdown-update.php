<?php

class PostLockdown_Update {
	private $updates = array();
	private $plugin_path;

	public function __construct( $plugin_path ) {
		$this->plugin_path = $plugin_path;
		$current_version = get_option( 'postlockdown_version' );
		$this->current_version = $current_version;

		if ( version_compare( $this->current_version, PostLockdown::VERSION, '<' ) ) {
			add_action( 'init', array( $this, 'update' ) );
		}
	}

	public function update() {
		$this->load_updates();
		foreach ( $this->updates as $version => $file ) {
			if ( version_compare( $this->current_version, $version, '<' ) ) {
				include( $file );
				$this->update_db_version( $version );
			}
		}

		$this->update_db_version();
	}

	private function load_updates() {
		$files = glob( $this->plugin_path . 'updates/postlockdown-update-*.php' );

		if ( ! $files ) {
			return false;
		}

		foreach ( $files as $file ) {
			$base = wp_basename( $file, '.php' );
			$parts = explode( '-', $base );
			$version = end( $parts );

			$this->updates[ $version ] = $file;
		}

		return true;
	}

	private function update_db_version( $version = null ) {
		if ( null === $version ) {
			$version = PostLockdown::VERSION;
		}

		update_option( 'postlockdown_version', $version );
	}
}
