<?php
/**
 * Plugin Name: PostLockdown
 * Plugin URI: http://www.exleysmith.com
 * Description: Allows admins to prevent certain posts of any post type from being deleted by lower users
 * Version: 0.1.0
 * Author: Exley and Smith Ltd
 * Author URI: http://www.exleysmith.com
 * License: GPL2
 * Text Domain: post-lockdown
 * Domain Path: /languages
 */

PostLockdown::init();

class PostLockdown {

	public static $locked_post_ids = array();
	public static $cap = 'manage_options';

	public static function init() {

		self::$locked_post_ids = array_flip( get_option( 'postlockdown_locked_posts', array() ) );

		add_filter( 'user_has_cap', array(__CLASS__, 'filter_cap'), 10, 3 );

		add_action( 'admin_menu', array(__CLASS__, 'add_options_page') );
		add_action( 'admin_init', array(__CLASS__, 'register_settings' ) );

	}

	public static function filter_cap($allcaps, $cap, $args) {

		if ( $args[0] != 'delete_post' && $args[0] != 'publish_pages' && $args[0] != 'publish_posts' ) {
			return $allcaps;
		}

		if ( !empty( $allcaps[ self::$cap ] ) ) {
			return $allcaps;
		}

		if ( isset( $args[2] ) ) {
			$post_id = $args[2];
		} else {
			global $post;
			$post_id = $post->ID;
		}

		if ( isset( self::$locked_post_ids[ $post_id ] ) ) {
			$allcaps[ $cap[0] ] = false;
		}

		return $allcaps;
	}

	public static function add_options_page() {
		add_options_page('post-lockdown', 'Post Lockdown', self::$cap, 'post-lockdown', array(__CLASS__, 'output_options_page') );
	}

	public static function register_settings() {
		register_setting( 'post-lockdown', 'postlockdown_locked_posts' );
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

		include_once( __DIR__ . '/options-page.php' );

	}

}
