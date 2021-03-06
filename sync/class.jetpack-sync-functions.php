<?php

/*
 * Utility functions to generate data synced to wpcom
 */

class Jetpack_Sync_Functions {

	public static function get_modules() {
		require_once( JETPACK__PLUGIN_DIR . 'class.jetpack-admin.php' );
		return Jetpack_Admin::init()->get_modules();
	}

	public static function get_taxonomies() {
		global $wp_taxonomies;

		return $wp_taxonomies;
	}

	public static function get_post_types() {
		global $wp_post_types;

		return $wp_post_types;
	}

	/**
	 * Finds out if a site is using a version control system.
	 * @return bool
	 **/
	public static function is_version_controlled() {

		if ( ! class_exists( 'WP_Automatic_Updater' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		}
		$updater               = new WP_Automatic_Updater();
		return (bool) strval( $updater->is_vcs_checkout( $context = ABSPATH ) );
	}

	/**
	 * Returns true if the site has file write access false otherwise.
	 * @return bool
	 **/
	public static function file_system_write_access() {
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		require_once( ABSPATH . 'wp-admin/includes/template.php' );

		$filesystem_method = get_filesystem_method();
		if ( $filesystem_method === 'direct' ) {
			return true;
		}

		ob_start();
		$filesystem_credentials_are_stored = request_filesystem_credentials( self_admin_url() );
		ob_end_clean();
		if ( $filesystem_credentials_are_stored ) {
			return true;
		}

		return false;
	}

	public static function get_plugins() {
		return apply_filters( 'all_plugins', get_plugins() );
	}

	public static function wp_version() {
		global $wp_version;
		return $wp_version;
	}
}