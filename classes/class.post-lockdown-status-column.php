<?php

class PostLockdown_StatusColumn {
	const COLUMN_KEY = 'postlockdown_status';

	public function __construct() {
		foreach ( array( 'posts', 'pages' ) as $type ) {
			add_filter( "manage_{$type}_columns", array( $this, '_column_add' ) );
			add_action( "manage_{$type}_custom_column", array( $this, '_column_output' ), 10, 2 );
		}

		add_action( 'admin_init', array( $this, '_set_post_type_hooks' ) );
	}

	/**
	 * Adds filters for user options retrieval to modify their hidden columns lists
	 * for each post type.
	 */
	public function _set_post_type_hooks() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			/*
			 * Use the `get_user_option_{$option}` filter to change the output of the get_user_option
			 * function for the `manage{$screen}columnshidden` option, which is based on the current
			 * admin screen. The admin screen we want to target is the `edit-{$post_type}` screen.
			 */
			$filter = sprintf( 'get_user_option_%s', sprintf( 'manage%scolumnshidden', 'edit-' . $post_type ) );
			add_filter( $filter, array( $this, '_column_hidden' ), 10, 3 );
		}
	}

	/**
	 * @param         $result
	 * @param         $option
	 * @param WP_User $user
	 * @return array
	 */
	public function _column_hidden( $result, $option, $user ) {
		global $wpdb;

		$prefix = $wpdb->get_blog_prefix();
		if ( ! $user->has_prop( $prefix . $option ) && ! $user->has_prop( $option ) ) {
			if ( ! is_array( $result ) ) {
				$result = array();
			}

			$result[] = self::COLUMN_KEY;
		}

		return $result;
	}

	/**
	 * Adds our status column into the post list.
	 * @param array $columns
	 * @return array
	 */
	public function _column_add( $columns ) {
		$label = apply_filters( 'postlockdown_column_label', 'Post Lockdown' );

		$new_columns = array();

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;

			if ( 'title' === $key ) {
				$new_columns[ self::COLUMN_KEY ] = $label;
			}
		}

		return $new_columns;
	}

	/**
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public function _column_output( $column, $post_id ) {
		/** @var PostLockdown $postlockdown */
		global $postlockdown;
		if ( self::COLUMN_KEY !== $column ) {
			return;
		}

		if ( $postlockdown->is_post_locked( $post_id ) ) {
			echo '<span title="Locked" class="dashicons dashicons-lock"></span> Locked';
		} else if ( $postlockdown->is_post_protected( $post_id ) ) {
			echo '<span title="Protected" class="dashicons dashicons-lock"></span> Protected';
		}
	}
}
