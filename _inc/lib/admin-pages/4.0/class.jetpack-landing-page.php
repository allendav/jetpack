<?php
include_once( 'class.jetpack-admin-page.php' );

// Builds the landing page and its menu
class Jetpack_Landing_Page_4 extends Jetpack_Admin_Page_4 {
	protected $dont_show_if_not_active = false;

	function get_page_hook() {
		$title = _x( 'Jetpack 4.0', 'The menu item label', 'jetpack' );

		// Was a new module added since last version?
		list( $jetpack_version ) = explode( ':', Jetpack_Options::get_option( 'version' ) );
		if (
			$jetpack_version
		&&
			$jetpack_version != JETPACK__VERSION
		&&
			( $new_modules = Jetpack::get_default_modules( $jetpack_version, JETPACK__VERSION ) )
		&&
			is_array( $new_modules )
		&&
			( $new_modules_count = count( $new_modules ) )
		&&
			( Jetpack::is_active() || Jetpack::is_development_mode() )
		) {
			$new_count_i18n = number_format_i18n( $new_modules_count );
			$span_title     = esc_attr( sprintf( _n( 'One New Jetpack Module', '%s New Jetpack Modules', $new_modules_count, 'jetpack' ), $new_count_i18n ) );
			$format         = _x( 'Jetpack %s', 'The menu item label with a new module count as %s', 'jetpack' );
			$update_markup  = "<span class='update-plugins count-{$new_modules_count}' title='$span_title'><span class='update-count'>$new_count_i18n</span></span>";
			$title          = sprintf( $format, $update_markup );
		}

		// Add the main admin Jetpack menu with possible information about new
		// modules
		add_menu_page( 'Jetpack', $title, 'jetpack_admin_page', 'jetpack', array( $this, 'render' ), 'div' );
		// also create the submenu
		return add_submenu_page( 'jetpack', $title, $title, 'jetpack_admin_page', 'jetpack' );
	}

	function add_page_actions( $hook ) {
		// Add landing page specific underscore templates
		/**
		 * Filters the js_templates callback value
		 *
		 * @since 3.6.0
		 *
		 * @param array array( $this, 'js_templates' ) js_templates callback.
		 * @param string $hook Specific admin page.
		 */
		add_action( "admin_footer-$hook", apply_filters( 'jetpack_landing_page_js_templates_callback', array( $this, 'js_templates' ), $hook ) );

		/** This action is documented in class.jetpack.php */
		do_action( 'jetpack_admin_menu', $hook );

		// Place the Jetpack menu item on top and others in the order they appear
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order',        array( $this, 'jetpack_menu_order' ) );
	}

	/*
	 * Build an array of a specific module tag.
	 *
	 * @param  string Name of the module tag
	 * @return array  The module slug, config url, and name of each Jump Start module
	 */
	function jumpstart_module_tag( $tag ) {
		$modules = Jetpack_Admin::init()->get_modules();

		$module_info = array();
		foreach ( $modules as $module => $value ) {
			if ( in_array( $tag, $value['feature'] ) ) {
				$module_info[] = array(
					'module_slug'   => $value['module'],
					'module_name'   => $value['name'],
					'configure_url' => $value['configure_url'],
				);
			}
		}
		return $module_info;
	}

	/*
	 * Only show Jump Start on first activation.
	 * Any option 'jumpstart' other than 'new connection' will hide it.
	 *
	 * The option can be of 4 things, and will be stored as such:
	 * new_connection      : Brand new connection - Show
	 * jumpstart_activated : Jump Start has been activated - dismiss
	 * jetpack_action_taken: Manual activation of a module already happened - dismiss
	 * jumpstart_dismissed : Manual dismissal of Jump Start - dismiss
	 *
	 * @return bool | show or hide
	 */
	function jetpack_show_jumpstart() {
		$jumpstart_option = Jetpack_Options::get_option( 'jumpstart' );

		$hide_options = array(
			'jumpstart_activated',
			'jetpack_action_taken',
			'jumpstart_dismissed'
		);

		if ( ! $jumpstart_option || in_array( $jumpstart_option, $hide_options ) ) {
			return false;
		}

		return true;
	}

	/*
	 * List of recommended modules for the Jump Start paragraph text.
	 * Will only show up in the paragraph if they are not active.
	 *
	 * @return string | comma-separated recommended modules that are not active
	 */
	function jumpstart_list_modules() {
		$jumpstart_recommended = $this->jumpstart_module_tag( 'Jumpstart' );

		$module_name = array();
		foreach ( $jumpstart_recommended as $module => $val ) {
			if ( ! Jetpack::is_module_active( $val['module_slug'] ) ) {
				$module_name[] = $val['module_name'];
			}
		}

		return $module_name;
	}

	function jetpack_menu_order( $menu_order ) {
		$jp_menu_order = array();

		foreach ( $menu_order as $index => $item ) {
			if ( $item != 'jetpack' )
				$jp_menu_order[] = $item;

			if ( $index == 0 )
				$jp_menu_order[] = 'jetpack';
		}

		return $jp_menu_order;
	}

	function js_templates() {
		Jetpack::init()->load_view( 'admin/4.0/glance-tmpl.php' );
		Jetpack::init()->load_view( 'admin/4.0/single-feature-tab-tmpl.php' );
		Jetpack::init()->load_view( 'admin/4.0/more-tmpl.php' );
	}

	function page_render() {
		// Handle redirects to configuration pages
		if ( ! empty( $_GET['configure'] ) ) {
			return $this->render_nojs_configurable();
		}

		global $current_user;

		$is_connected      = Jetpack::is_active();
		$is_user_connected = Jetpack::is_user_connected( $current_user->ID );
		$is_master_user    = $current_user->ID == Jetpack_Options::get_option( 'master_user' );

		if ( Jetpack::is_development_mode() ) {
			$is_connected      = true;
			$is_user_connected = true;
			$is_master_user    = false;
		}

		// Set template data for the admin page template
		$data = array(
			'is_connected'      => $is_connected,
			'is_user_connected' => $is_user_connected,
			'is_master_user'    => $is_master_user,
			'show_jumpstart'    => $this->jetpack_show_jumpstart(),
			'jumpstart_list'    => $this->jumpstart_list_modules(),
			'recommended_list'  => $this->jumpstart_module_tag( 'Recommended' ),
		);

		Jetpack::init()->load_view( 'admin/4.0/admin-page.php', $data );
	}

	// Render the configuration page for the module if it exists and an error
	// screen if the module is not configurable
	function render_nojs_configurable() {
		echo '<div class="clouds-sm"></div>';
		echo '<div class="wrap configure-module">';

		$module_name = preg_replace( '/[^\da-z\-]+/', '', $_GET['configure'] );
		if ( Jetpack::is_module( $module_name ) && current_user_can( 'jetpack_configure_modules' ) ) {
			Jetpack::admin_screen_configure_module( $module_name );
		} else {
			echo '<h2>' . esc_html__( 'Error, bad module.', 'jetpack' ) . '</h2>';
		}

		echo '</div><!-- /wrap -->';
	}

	/*
     * Build an array of Jump Start stats urls.
     * requires the build URL args passed as an array
     *
	 * @param array $jumpstart_stats
     * @return (array) of built stats urls
     */
	function build_jumpstart_stats_urls( $jumpstart_stats ) {
		$jumpstart_urls = array();

		foreach ( $jumpstart_stats as $value) {
			$jumpstart_urls[ $value ] = Jetpack::build_stats_url( array( 'x_jetpack-jumpstart' => $value ) );
		}

		return $jumpstart_urls;

	}

	/*
	 * Build an array of NUX admin stats urls.
	 * requires the build URL args passed as an array
	 *
	 * @param array $nux_admin_stats
	 * @return (array) of built stats urls
	 */
	function build_nux_admin_stats_urls( $nux_admin_stats ) {
		$nux_admin_urls = array();

		foreach ( $nux_admin_stats as $value) {
			$nux_admin_urls[ $value ] = Jetpack::build_stats_url( array( 'x_jetpack-nux' => $value ) );
		}

		return $nux_admin_urls;

	}

	/*
	 * Data for displaying in Protect section of At A Glance
	 */
	function at_a_glance_site_security_protect_state() {
		if ( ! Jetpack::is_module_active( 'protect' ) ) {
			return array(
				'title'   => 'Protect',
				'size'    => 'large',
				'state'   => 'inactive',
				'data'    => null,
				'message' => __( 'Please activate Protect', 'jetpack' )
			);
		}

		return array(
			'title'   => 'Protect',
			'size'    => 'large',
			'state'   => 'active',
			'data'    => get_site_option( 'jetpack_protect_blocked_attempts' ),
			'message' => __( 'Malicious attacks blocked.', 'jetpack' )
		);
	}

	/*
	 * Data for displaying in Scan section of At A Glance
	 */
	function at_a_glance_site_security_scan_state() {
		return array(
			'title'   => __( 'Security Scan', 'jetpack' ),
			'size'    => 'small',
			'state'   => 'inactive',
			'data'    => 'No Threats Found',
			'message' => __( 'This is a placeholder until we get live data', 'jetpack' )
		);
	}

	/*
	 * Data for displaying in Monitor section of At A Glance
	 */
	function at_a_glance_site_security_monitor_state() {
		if ( ! Jetpack::is_module_active( 'monitor' ) ) {
			return array(
				'title'   => __( 'Site Monitoring', 'jetpack' ),
				'size'    => 'small',
				'state'   => 'inactive',
				'data'    => null,
				'message' => __( 'Please activate Monitor', 'jetpack' )
			);
		}

		// Calculate "Days Since" last downtime.
		$monitor       = new Jetpack_Monitor();
		$last_downtime = $monitor->monitor_get_last_downtime();
		$time_since    = human_time_diff( strtotime( $last_downtime ), strtotime( 'now' ) );

		return array(
			'title'   => __( 'Site Monitoring', 'jetpack' ),
			'size'    => 'small',
			'state'   => 'active',
			'data'    => $time_since,
			'message' => __( 'without downtime.', 'jetpack' )
		);
	}

	function page_admin_scripts() {
		wp_enqueue_script( 'jp-admin-js', plugins_url( '_inc/js-4.0/jp-admin.js', JETPACK__PLUGIN_FILE ),
			array( 'jquery-ui-tabs', 'jquery', 'wp-util', 'jquery-ui-accordion' ), JETPACK__VERSION . '-20160128' );

		wp_localize_script(
			'jp-admin-js',
			'jp_data',
			array(
				'modules' => array_values( Jetpack_Admin::init()->get_modules() ),
				'currentVersion' => JETPACK__VERSION,
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'activate_nonce' => wp_create_nonce( 'jetpack-jumpstart-nonce' ),
				'admin_nonce' => wp_create_nonce( 'jetpack-admin-nonce' ),
				'site_url_manage' => Jetpack::build_raw_urls( get_site_url() ),
				'glanceProtect' => $this->at_a_glance_site_security_protect_state(),
				'glanceScan'    => $this->at_a_glance_site_security_scan_state(),
				'glanceMonitor' => $this->at_a_glance_site_security_monitor_state(),
			)
		);
	}
}
