<?php
use Aws\Common\Aws;

class Amazon_Web_Services extends AWS_Plugin_Base {

	/**
	 * @var string
	 */
	private $plugin_title;

	/**
	 * @var string
	 */
	private $plugin_menu_title;

	/**
	 * @var string
	 */
	private $plugin_permission;

	/**
	 * @var
	 */
	private $client;

	const SETTINGS_KEY = 'aws_settings';
	const SETTINGS_CONSTANT = 'AWS_SETTINGS';

	/**
	 * @param string $plugin_file_path
	 */
	function __construct( $plugin_file_path ) {
		$this->plugin_slug = 'amazon-web-services';

		parent::__construct( $plugin_file_path );

		do_action( 'aws_init', $this );

		if ( is_admin() ) {
			do_action( 'aws_admin_init', $this );
		}

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			$this->plugin_permission = 'manage_network_options';
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			$this->plugin_permission = 'manage_options';
		}

		$this->plugin_title      = __( 'Amazon Web Services', 'amazon-web-services' );
		$this->plugin_menu_title = __( 'AWS', 'amazon-web-services' );

		add_filter( 'plugin_action_links', array( $this, 'plugin_actions_settings_link' ), 10, 2 );

		load_plugin_textdomain( 'amazon-web-services', false, dirname( plugin_basename( $plugin_file_path ) ) . '/languages/' );
	}

	/**
	 * Add the AWS menu item and sub pages
	 */
	function admin_menu() {
		if ( version_compare( $GLOBALS['wp_version'], '3.8', '<' ) ) {
			$icon_url = plugins_url( 'assets/img/icon16.png', $this->plugin_file_path );
		} else {
			$icon_url = false;
		}

		$hook_suffixes = array();
		$hook_suffixes[] = add_menu_page( $this->plugin_title, $this->plugin_menu_title, $this->plugin_permission, $this->plugin_slug, array(
				$this,
				'render_page',
			), $icon_url );

		$title           = __( 'Addons', 'amazon-web-services' );
		$hook_suffixes[] = $this->add_page( $title, $title, $this->plugin_permission, 'aws-addons', array(
				$this,
				'render_page',
			) );

		global $submenu;
		if ( isset( $submenu[ $this->plugin_slug ][0][0] ) ) {
			$submenu[ $this->plugin_slug ][0][0] = __( 'Access Keys', 'amazon-web-services' );
		}

		do_action( 'aws_admin_menu', $this );

		foreach ( $hook_suffixes as $hook_suffix ) {
			add_action( 'load-' . $hook_suffix, array( $this, 'plugin_load' ) );
		}

		if ( $icon_url === false ) {
			add_action( 'admin_print_styles', array( $this, 'enqueue_menu_styles' ) );
		}
	}

	/**
	 * Add sub page to the AWS menu item
	 *
	 * @param string       $page_title
	 * @param string       $menu_title
	 * @param string       $capability
	 * @param string       $menu_slug
	 * @param string|array $function
	 *
	 * @return string|false
	 */
	function add_page( $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
		return add_submenu_page( $this->plugin_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	/**
	 * Load styles for the AWS menu item
	 */
	public function enqueue_menu_styles() {
		$this->enqueue_style( 'aws-global-styles', 'assets/css/global' );
	}

	/**
	 * Plugin loading enqueue scripts and styles
	 */
	public function plugin_load() {
		$this->enqueue_style( 'aws-styles', 'assets/css/styles' );
		$this->enqueue_script( 'aws-script', 'assets/js/script', array( 'jquery' ) );

		if ( isset( $_GET['page'] ) && 'aws-addons' === sanitize_key( $_GET['page'] ) ) { // input var okay
			add_filter( 'admin_body_class', array( $this, 'admin_plugin_body_class' ) );
			wp_enqueue_script( 'plugin-install' );
			add_thickbox();
		}

		$this->handle_post_request();

		do_action( 'aws_plugin_load', $this );
	}

	/**
	 * Process the saving of the settings form
	 */
	function handle_post_request() {
		if ( empty( $_POST['action'] ) || 'save' != sanitize_key( $_POST['action'] ) ) { // input var okay
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'aws-save-settings' ) ) { // input var okay
			die( __( "Cheatin' eh?", 'amazon-web-services' ) );
		}

		// Make sure $this->settings has been loaded
		$this->get_settings();

		$post_vars = array( 'access_key_id', 'secret_access_key' );
		foreach ( $post_vars as $var ) {
			if ( ! isset( $_POST[ $var ] ) ) { // input var okay
				continue;
			}

			$value = sanitize_text_field( $_POST[ $var ] ); // input var okay

			if ( 'secret_access_key' == $var && '-- not shown --' == $value ) {
				continue;
			}

			$this->set_setting( $var, $value );
		}

		$this->save_settings();
	}

	/**
	 * Adds a class to admin page to style thickbox the same as the plugin directory pages.
	 *
	 * @param $classes
	 *
	 * @return string
	 */
	function admin_plugin_body_class( $classes ) {
		$classes .= 'plugin-install-php';

		return $classes;
	}

	/**
	 * Render the output of a page
	 */
	function render_page() {
		$view       = 'settings';
		$page_title = __( 'Amazon Web Services', 'amazon-web-services' );

		if ( empty( $_GET['page'] ) ) { // input var okay
			// Not sure why we'd ever end up here, but just in case
			wp_die( 'What the heck are we doin here?' );
		}

		if ( preg_match( '@^aws-(.*)$@', $_GET['page'], $matches ) ) {
			$allowed = array(
				'addons' => __( 'Amazon Web Services: Addons', 'amazon-web-services' ),
			);
			if ( array_key_exists( $matches[1], $allowed ) ) {
				$view       = $matches[1];
				$page_title = $allowed[ $view ];
			}
		}

		$this->render_view( 'header', array( 'page' => $view, 'page_title' => $page_title ) );
		$this->render_view( $view );
		$this->render_view( 'footer' );
	}

	/**
	 * Check if we are using constants for the AWS access credentials
	 *
	 * @return bool
	 */
	function are_key_constants_set() {
		return defined( 'AWS_ACCESS_KEY_ID' ) || defined( 'AWS_SECRET_ACCESS_KEY' );
	}

	/**
	 * Check if we are using the prefixed constants for the AWS access credentials
	 *
	 * @return bool
	 */
	function are_prefixed_key_constants_set() {
		return defined( 'DBI_AWS_ACCESS_KEY_ID' ) || defined( 'DBI_AWS_SECRET_ACCESS_KEY' );
	}

	/**
	 * Whether or not IAM access keys are needed.
	 *
	 * Keys are needed if we are not using EC2 roles or not defined/set yet.
	 *
	 * @return bool
	 */
	public function needs_access_keys() {
		if ( $this->use_ec2_iam_roles() ) {
			return false;
		}

		return ! $this->are_access_keys_set();
	}

	/**
	 * Check if access keys are defined either by constants or database
	 *
	 * @return bool
	 */
	function are_access_keys_set() {
		return $this->get_access_key_id() && $this->get_secret_access_key();
	}

	/**
	 * Get the AWS key from a constant or the settings
	 *
	 * Falls back to settings only if neither constant is defined.
	 *
	 * @return string
	 */
	function get_access_key_id() {
		if ( $this->are_prefixed_key_constants_set() || $this->are_key_constants_set() ) {
			if ( defined( 'DBI_AWS_ACCESS_KEY_ID' ) ) {
				return DBI_AWS_ACCESS_KEY_ID;
			} elseif ( defined( 'AWS_ACCESS_KEY_ID' ) ) {
				return AWS_ACCESS_KEY_ID; // Deprecated
			}
		} else {
			return $this->get_setting( 'access_key_id' );
		}

		return '';
	}

	/**
	 * Get the AWS secret from a constant or the settings
	 *
	 * Falls back to settings only if neither constant is defined.
	 *
	 * @return string
	 */
	function get_secret_access_key() {
		if ( $this->are_prefixed_key_constants_set() || $this->are_key_constants_set() ) {
			if ( defined( 'DBI_AWS_SECRET_ACCESS_KEY' ) ) {
				return DBI_AWS_SECRET_ACCESS_KEY;
			} elseif ( defined( 'AWS_SECRET_ACCESS_KEY' ) ) {
				return AWS_SECRET_ACCESS_KEY; // Deprecated
			}
		} else {
			return $this->get_setting( 'secret_access_key' );
		}

		return '';
	}

	/**
	 * Allows the AWS client factory to use the IAM role for EC2 instances
	 * instead of key/secret for credentials
	 * http://docs.aws.amazon.com/aws-sdk-php/guide/latest/credentials.html#instance-profile-credentials
	 *
	 * @return bool
	 */
	function use_ec2_iam_roles() {
		if ( defined( 'AWS_USE_EC2_IAM_ROLE' ) && AWS_USE_EC2_IAM_ROLE ) {
			return true;
		}

		return false;
	}

	/**
	 * Instantiate a new AWS service client for the AWS SDK
	 * using the defined AWS key and secret
	 *
	 * @return Aws
	 * @throws Exception
	 */
	function get_client() {
		if ( $this->needs_access_keys() ) {
			throw new Exception( sprintf( __( 'You must first <a href="%s">set your AWS access keys</a> to use this addon.', 'amazon-web-services' ), 'admin.php?page=' . $this->plugin_slug ) );
		}

		if ( is_null( $this->client ) ) {
			$args = array();

			if ( ! $this->use_ec2_iam_roles() ) {
				$args = array(
					'key'    => $this->get_access_key_id(),
					'secret' => $this->get_secret_access_key(),
				);
			}

			$args         = apply_filters( 'aws_get_client_args', $args );
			$this->client = Aws::factory( $args );
		}

		return $this->client;
	}


	/**
	 * Get a nonced, network safe install URL for a plugin
	 *
	 * @param string $slug Plugin slug
	 *
	 * @return string
	 */
	function get_plugin_install_url( $slug ) {
		return wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
	}

	/**
	 * Get a nonced, network safe activation URL for a plugin
	 *
	 * @param string $slug Plugin slug
	 *
	 * @return string
	 */
	function get_plugin_activate_url( $slug ) {
		$plugin_path = $this->get_plugin_path( $slug );

		return wp_nonce_url( self_admin_url( 'plugins.php?action=activate&amp;plugin=' . $plugin_path ), 'activate-plugin_' . $plugin_path );
	}

	/**
	 * Customize the link text on the plugins page
	 *
	 * @return string
	 */
	function get_plugin_action_settings_text() {
		return __( 'Access Keys', 'amazon-web-services' );
	}

	/**
	 * Get all defined addons that use this plugin
	 *
	 * @param bool $unfiltered
	 *
	 * @return array
	 */
	public function get_addons( $unfiltered = false ) {
		$addons = array(
			'amazon-s3-and-cloudfront'     => array(
				'title'   => __( 'WP Offload S3 Lite', 'amazon-web-services' ),
				'url'     => 'https://wordpress.org/plugins/amazon-s3-and-cloudfront/',
				'install' => true,
			),
			'amazon-s3-and-cloudfront-pro' => array(
				'title'  => __( 'WP Offload S3', 'amazon-web-services' ),
				'url'    => $this->dbrains_url( '/wp-offload-s3', array(
					'utm_campaign' => 'WP+Offload+S3',
				) ),
				'addons' => array(
					'amazon-s3-and-cloudfront-assets-pull' => array(
						'title' => __( 'Assets Pull', 'amazon-web-services' ),
						'url'   => $this->dbrains_url( '/wp-offload-s3/doc/assets-pull-addon/', array(
							'utm_campaign' => 'addons+install',
						) ),
						'label' => __( 'Feature', 'amazon-web-services' ),
						'icon'  => true,
					),
				),
			),
		);

		if ( $unfiltered ) {
			return $addons;
		}

		$addons = apply_filters( 'aws_addons', $addons );

		return $addons;
	}

	/**
	 * Recursively build addons list
	 *
	 * @param array|null $addons
	 */
	function render_addons( $addons = null ) {
		if ( is_null( $addons ) ) {
			$addons = $this->get_addons();
		}

		if ( class_exists( 'Amazon_S3_And_CloudFront_Pro' ) ) {
			unset( $addons['amazon-s3-and-cloudfront'] );
		}

		foreach ( $addons as $slug => $addon ) {
			$this->render_view( 'addon', array( 'slug' => $slug, 'addon' => $addon ) );
		}
	}

	/**
	 * Add install links to AWS addon page
	 *
	 * @param string $slug
	 * @param array  $addon Details of the addon
	 */
	function get_addon_install_link( $slug, $addon ) {
		$installed = file_exists( WP_PLUGIN_DIR . '/' . $slug );
		$activated = $this->is_plugin_activated( $slug );

		if ( $installed && $activated ) {
			echo '<li class="installed activated">' . esc_html( _x( 'Installed & Activated', 'Plugin already installed and activated', 'amazon-web-services' ) ) . '</li>';
		} elseif ( $installed ) {
			echo '<li class="installed">' . esc_html( _x( 'Installed', 'Plugin already installed', 'amazon-web-services' ) ) . '</li>';
			echo '<li class="activate"><a href="' . esc_url( $this->get_plugin_activate_url( $slug ) ) . '">' . esc_html( _x( 'Activate Now', 'Activate plugin now', 'amazon-web-services' ) ) . '</a></li>';
		} else {
			if ( isset( $addon['install'] ) && $addon['install'] ) {
				echo '<li class="install"><a href="' . esc_url( $this->get_plugin_install_url( $slug ) ) . '">' . esc_html( _x( 'Install Now', 'Install plugin now', 'amazon-web-services' ) ) . '</a></li>';
			}
		}

		// Other links
		if ( isset( $addon['links'] ) ) {
			foreach ( $addon['links'] as $link ) {
				if ( ! isset( $link['url'] ) || ! isset( $link['text'] ) ) {
					continue;
				}
				echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['text'] ) . '</a></li>';
			}
		}
	}

	/**
	 * Get the URL of the addon's icon
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	function get_addon_icon_url( $slug ) {
		$filename = str_replace( 'amazon-s3-and-cloudfront-', '', $slug );
		$filename = 'icon-' . $filename . '.svg';
		echo plugins_url( 'assets/img/' . $filename, $this->plugin_file_path );
	}

	/**
	 * Add details link to AWS addon page
	 *
	 * @param string $slug
	 * @param array  $addon Details of the addon
	 */
	function get_addon_details_link( $slug, $addon ) {
		$url   = $addon['url'];
		$title = __( 'Visit Site', 'amazon-web-services' );
		$class = '';
		if ( isset( $addon['free'] ) && $addon['free'] ) {
			$title = _x( 'View Details', 'View plugin details', 'amazon-web-services' );
			$url   = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=600&amp;height=800' );
			$class = 'thickbox';
		}

		echo '<li class="visit-site"><a class="' . $class . '" href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></li>';
	}

	/**
	 * Check if plugin is activated
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	function is_plugin_activated( $slug ) {
		$path = $this->get_plugin_path( $slug );

		return is_plugin_active( $path );
	}

	/**
	 * Get plugin path relative to plugins directory
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	function get_plugin_path( $slug ) {
		$path = $slug . '/' . $slug . '.php';

		// Workaround for dodgy AS3CF naming convention
		if ( 'amazon-s3-and-cloudfront' === $slug ) {
			$path = $slug . '/wordpress-s3.php';
		}

		return $path;
	}
}
