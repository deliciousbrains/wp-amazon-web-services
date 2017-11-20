<?php

class AWS_Plugin_Base {

	const DBRAINS_URL = 'https://deliciousbrains.com';

	protected $plugin_file_path;
	protected $plugin_dir_path;
	protected $plugin_slug;
	protected $plugin_basename;
	protected $plugin_version;

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @var array
	 */
	private $defined_settings;

	function __construct( $plugin_file_path ) {
		$this->plugin_file_path = $plugin_file_path;
		$this->plugin_dir_path  = rtrim( plugin_dir_path( $plugin_file_path ), '/' );
		$this->plugin_basename  = plugin_basename( $plugin_file_path );
		$this->plugin_version   = $GLOBALS['aws_meta'][ $this->plugin_slug ]['version'];
	}

	/**
	 * Accessor for plugin version
	 *
	 * @return mixed
	 */
	public function get_plugin_version() {
		return $this->plugin_version;
	}

	/**
	 * Accessor for plugin slug
	 *
	 * @return string
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Accessor for plugin basename
	 *
	 * @return string
	 */
	public function get_plugin_basename() {
		return $this->plugin_basename;
	}

	/**
	 * Accessor for plugin file path
	 *
	 * @return string
	 */
	public function get_plugin_file_path() {
		return $this->plugin_file_path;
	}

	/**
	 * Accessor for plugin dir path
	 *
	 * @return string
	 */
	public function get_plugin_dir_path() {
		return $this->plugin_dir_path;
	}

	/**
	 * Get the plugin's settings array
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	function get_settings( $force = false ) {
		if ( is_null( $this->settings ) || $force ) {
			$this->settings = $this->filter_settings( get_site_option( static::SETTINGS_KEY ) );
		}

		return $this->settings;
	}

	/**
	 * Get all settings that have been defined via constant for the plugin
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	function get_defined_settings( $force = false ) {
		if ( ! defined( static::SETTINGS_CONSTANT ) ) {
			$this->defined_settings = array();

			return $this->defined_settings;
		}

		if ( is_null( $this->defined_settings ) || $force ) {
			$this->defined_settings = array();
			$unserialized = maybe_unserialize( constant( static::SETTINGS_CONSTANT ) );
			$unserialized = is_array( $unserialized ) ? $unserialized : array();

			foreach ( $unserialized as $key => $value ) {
				if ( ! in_array( $key, $this->get_settings_whitelist() ) ) {
					continue;
				}

				if ( is_bool( $value ) || is_null( $value ) ) {
					$value = (int) $value;
				}

				if ( is_numeric( $value ) ) {
					$value = strval( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				$this->defined_settings[ $key ] = $value;
			}

			$this->listen_for_settings_constant_changes();

			// Normalize the defined settings before saving, so we can detect when a real change happens.
			ksort( $this->defined_settings );
			update_site_option( 'as3cf_constant_' . static::SETTINGS_CONSTANT, $this->defined_settings );
		}

		return $this->defined_settings;
	}

	/**
	 * Subscribe to changes of the site option used to store the constant-defined settings.
	 */
	protected function listen_for_settings_constant_changes() {
		if ( ! has_action( 'update_site_option_' . 'as3cf_constant_' . static::SETTINGS_CONSTANT, array( $this, 'settings_constant_changed' ) ) ) {
			add_action( 'add_site_option_' . 'as3cf_constant_' . static::SETTINGS_CONSTANT, array( $this, 'settings_constant_added' ), 10, 3 );
			add_action( 'update_site_option_' . 'as3cf_constant_' . static::SETTINGS_CONSTANT, array( $this, 'settings_constant_changed' ), 10, 4 );
		}
	}

	/**
	 * Translate a settings constant option addition into a change.
	 *
	 * @param string $option      Name of the option.
	 * @param mixed  $value       Value the option is being initialized with.
	 * @param int    $network_id  ID of the network.
	 */
	public function settings_constant_added( $option, $value, $network_id ) {
		$db_settings = get_site_option( static::SETTINGS_KEY, array() );
		$this->settings_constant_changed( $option, $value, $db_settings, $network_id );
	}

	/**
	 * Callback for announcing when settings-defined values change.
	 *
	 * @param string $option        Name of the option.
	 * @param mixed  $new_settings  Current value of the option.
	 * @param mixed  $old_settings  Old value of the option.
	 * @param int    $network_id    ID of the network.
	 */
	public function settings_constant_changed( $option, $new_settings, $old_settings, $network_id ) {
		$old_settings = $old_settings ?: array();

		foreach ( $this->get_settings_whitelist() as $setting ) {
			$old_value = isset( $old_settings[ $setting ] ) ? $old_settings[ $setting ] : null;
			$new_value = isset( $new_settings[ $setting ] ) ? $new_settings[ $setting ] : null;

			if ( $old_value !== $new_value ) {
				/**
				 * Setting-specific hook for setting change.
				 *
				 * @param mixed $new_value
				 * @param mixed $old_value
				 * @param string $setting
				 */
				do_action( 'as3cf_constant_' . static::SETTINGS_CONSTANT . '_changed_' . $setting, $new_value, $old_value, $setting );

				/**
				 * Generic hook for setting change.
				 *
				 * @param mixed $new_value
				 * @param mixed $old_value
				 * @param string $setting
				 */
				do_action( 'as3cf_constant_' . static::SETTINGS_CONSTANT . '_changed', $new_value, $old_value, $setting );
			}
		}
	}

	/**
	 * Filter the plugin settings array
	 *
	 * @param array $settings
	 *
	 * @return array $settings
	 */
	function filter_settings( $settings ) {
		$defined_settings = $this->get_defined_settings();

		// Bail early if there are no defined settings
		if ( empty( $defined_settings ) ) {
			return $settings;
		}

		foreach ( $defined_settings as $key => $value ) {
			$settings[ $key ] = $value;
		}

		return $settings;
	}

	/**
	 * Get the whitelisted settings for the plugin.
	 * Meant to be overridden in child classes.
	 *
	 * @return array
	 */
	function get_settings_whitelist() {
		return array();
	}

	/**
	 * Get a specific setting
	 *
	 * @param        $key
	 * @param string $default
	 *
	 * @return string
	 */
	function get_setting( $key, $default = '' ) {
		$this->get_settings();

		if ( isset( $this->settings[ $key ] ) ) {
			$setting = $this->settings[ $key ];
		} else {
			$setting = $default;
		}

		return apply_filters( 'aws_get_setting', $setting, $key );
	}

	/**
	 * Gets a single setting that has been defined in the plugin settings constant
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	function get_defined_setting( $key, $default = '' ) {
		$defined_settings = $this->get_defined_settings();
		$setting = isset( $defined_settings[ $key ] ) ? $defined_settings[ $key ] : $default;

		return $setting;
	}

	/**
	 * Delete a setting
	 *
	 * @param $key
	 */
	function remove_setting( $key ) {
		$this->get_settings();

		if ( isset( $this->settings[ $key ] ) ) {
			unset( $this->settings[ $key ] );
		}
	}

	/**
	 * Removes a defined setting from the defined_settings array.
	 *
	 * Does not unset the actual constant.
	 *
	 * @param $key
	 */
	function remove_defined_setting( $key ) {
		$this->get_defined_settings();

		if ( isset( $this->defined_settings[ $key ] ) ) {
			unset( $this->defined_settings[ $key ] );
		}
	}

	/**
	 * Render a view template file
	 *
	 * @param string $view View filename without the extension
	 * @param array $args Arguments to pass to the view
	 */
	function render_view( $view, $args = array() ) {
		extract( $args );
		include $this->plugin_dir_path . '/view/' . $view . '.php';
	}

	/**
	 * Set a setting
	 *
	 * @param $key
	 * @param $value
	 */
	function set_setting( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	/**
	 * Bulk set the settings array
	 *
	 * @param array $settings
	 */
	function set_settings( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Save the settings to the database
	 */
	public function save_settings() {
		$this->update_site_option( static::SETTINGS_KEY, $this->settings );
	}

	/**
	 * Update site option.
	 *
	 * @param string $option
	 * @param mixed  $value
	 * @param bool   $autoload
	 *
	 * @return bool
	 */
	public function update_site_option( $option, $value, $autoload = true ) {
		if ( is_multisite() ) {
			return update_site_option( $option, $value );
		}

		return update_option( $option, $value, $autoload );
	}

	/**
	 * The URL for the plugin action link for the plugin on the plugins page.
	 *
	 * @return string
	 */
	function get_plugin_page_url() {
		return network_admin_url( 'admin.php?page=' . $this->plugin_slug );
	}

	/**
	 * The text for the plugin action link for the plugin on the plugins page.
	 *
	 * @return string
	 */
	function get_plugin_action_settings_text() {
		return __( 'Settings', 'amazon-web-services' );
	}

	/**
	 * Add a link to the plugin row for the plugin on the plugins page.
	 * Needs to be implemented for an extending class using -
	 *     add_filter( 'plugin_action_links', array( $this, 'plugin_actions_settings_link' ), 10, 2 );
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return array
	 */
	function plugin_actions_settings_link( $links, $file ) {
		$url  = $this->get_plugin_page_url();
		$text = $this->get_plugin_action_settings_text();

		$settings_link = '<a href="' . $url . '">' . esc_html( $text ) . '</a>';

		if ( $file == $this->plugin_basename ) {
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * Enqueue script.
	 *
	 * @param string $handle
	 * @param string $path
	 * @param array  $deps
	 * @param bool   $footer
	 */
	public function enqueue_script( $handle, $path, $deps = array(), $footer = true ) {
		$version = $this->get_asset_version();
		$suffix  = $this->get_asset_suffix();

		$src = plugins_url( $path . $suffix . '.js', $this->plugin_file_path );
		wp_enqueue_script( $handle, $src, $deps, $version, $footer );
	}

	/**
	 * Enqueue style.
	 *
	 * @param string $handle
	 * @param string $path
	 * @param array  $deps
	 */
	public function enqueue_style( $handle, $path, $deps = array() ) {
		$version = $this->get_asset_version();

		$src = plugins_url( $path . '.css', $this->plugin_file_path );
		wp_enqueue_style( $handle, $src, $deps, $version );
	}

	/**
	 * Get the version used for script enqueuing
	 *
	 * @return mixed
	 */
	public function get_asset_version() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;
	}

	/**
	 * Get the filename suffix used for script enqueuing
	 *
	 * @return mixed
	 */
	public function get_asset_suffix() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Get all AWS regions
	 *
	 * @return array
	 */
	public function get_aws_regions() {
		$regions = array(
			'us-east-1'      => 'US Standard',
			'us-west-1'      => 'Northern California',
			'us-west-2'      => 'Oregon',
			'ca-central-1'   => 'Montreal',
			'eu-west-1'      => 'Ireland',
			'eu-west-2'      => 'London',
			'eu-central-1'   => 'Frankfurt',
			'ap-southeast-1' => 'Singapore',
			'ap-southeast-2' => 'Sydney',
			'ap-northeast-1' => 'Tokyo',
			'ap-northeast-2' => 'Seoul',
			'ap-south-1'     => 'Mumbai',
			'sa-east-1'      => 'Sao Paulo',
		);

		return apply_filters( 'aws_get_regions', $regions );
	}

	/**
	 * Generate site URL with correct UTM tags.
	 *
	 * @param string $path
	 * @param array  $args
	 * @param string $hash
	 *
	 * @return string
	 */
	public function dbrains_url( $path, $args = array(), $hash = '' ) {
		$args = wp_parse_args( $args, array(
			'utm_medium' => 'insideplugin',
			'utm_source' => $this->get_utm_source(),
		) );
		$args = array_map( 'urlencode', $args );
		$url  = trailingslashit( self::DBRAINS_URL ) . ltrim( $path, '/' );
		$url  = add_query_arg( $args, $url );

		if ( $hash ) {
			$url .= '#' . $hash;
		}

		return $url;
	}

	/**
	 * Get UTM source for plugin.
	 *
	 * @return string
	 */
	protected function get_utm_source() {
		return 'AWS';
	}
}