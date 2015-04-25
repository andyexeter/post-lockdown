<?php
/**
 * Plugin Name: PostLockdown
 * Plugin URI: http://www.andypalmer.me
 * Description: Allows admins to prevent certain posts of any post type from being deleted by lower users
 * Version: 0.4.1
 * Author: Andy Palmer
 * Author URI: http://www.andypalmer.me
 * License: GPL2
 */

PostLockdown::init();

class PostLockdown {

	const CAP = 'manage_options';
	const KEY = 'postlockdown_locked_posts';
	const SLUG = 'post-lockdown';

	public static $locked_post_ids = array();

	public static $caps = array( 'delete_post' => true, 'publish_pages' => true, 'publish_posts' => true );

	public static function init() {

		self::$locked_post_ids = array_flip( get_option( self::KEY, array() ) );

		add_filter( 'user_has_cap', array(__CLASS__, 'filter_cap'), 10, 3 );

		add_action( 'admin_init', array(__CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array(__CLASS__, 'add_options_page') );
	}

	/**
	 * Sets the capability to false when current_user_can() has been
	 * called on one of our self::$caps on a locked post. If the user has the
	 * self::CAP capability we bail out early
	 * @todo Stop users being able to change post_status to Draft / Review
	 */
	public static function filter_cap($allcaps, $cap, $args) {

		$post = get_post();

		if ( !isset( self::$caps[ $args[0] ] ) || !empty( $allcaps[ self::CAP ] ) ) {
			return $allcaps;
		}

		if ( isset( $args[2] ) ) {
			$post_id = $args[2];
		} elseif( isset( $post->ID ) ) {
			$post_id = $post->ID;
		} else {
			$post_id = (int)filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );
		}

		if( !$post_id ) {
			return $allcaps;
		}

		if ( isset( self::$locked_post_ids[ $post_id ] ) ) {
			$allcaps[ $cap[0] ] = false;
		}

		return $allcaps;
	}

	public static function register_setting() {
		register_setting( self::SLUG, self::KEY );
	}

	public static function add_options_page() {
		add_options_page( self::SLUG, 'Post Lockdown', self::CAP, self::SLUG, array( __CLASS__, 'output_options_page' ) );
	}

	public static function output_options_page() {

		$post_types = array();

		foreach( get_post_types( array(), 'objects' ) as $post_type ) {

			$posts = get_posts( array(
				'post_type' => $post_type->name,
				'posts_per_page' => -1
			) );

			if ( empty( $posts ) ) {
				continue;
			}

			$post_types[ $post_type->name ] = array( 'label' => $post_type->label, 'posts' => array() );

			foreach( $posts as $post ) {

				$selected = isset( self::$locked_post_ids[ $post->ID ] );

				$post_types[ $post_type->name ]['posts'][] = array(
					'ID' => $post->ID,
					'post_title' => $post->post_title,
					'selected' => $selected
				);

			}

		}

		$key = self::KEY;
		$slug = self::SLUG;

		include_once( __DIR__ . '/options-page.php' );
	}
}
